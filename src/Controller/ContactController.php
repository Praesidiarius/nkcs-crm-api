<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactAddress;
use App\Form\Contact\ContactType;
use App\Repository\ContactAddressRepository;
use App\Repository\ContactRepository;
use App\Repository\SystemSettingRepository;
use App\Repository\UserRepository;
use App\Repository\UserSettingRepository;
use App\Service\Contact\ContactManager;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/contact/{_locale}')]
class ContactController extends AbstractApiController
{
    public function __construct(
        private readonly ContactType $contactForm,
        private readonly ContactRepository $contactRepository,
        private readonly ContactAddressRepository $addressRepository,
        private readonly UserSettingRepository $userSettings,
        private readonly HttpClientInterface $httpClient,
        private readonly TranslatorInterface $translator,
    )
    {
        parent::__construct($this->httpClient);
    }

    #[Route('/add', name: 'contact_add', methods: ['GET'])]
    public function getAddForm(): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->json([
            'form' => $this->contactForm->getFormFields(),
            'sections' => $this->contactForm->getFormSections(),
        ]);
    }

    #[Route('/add-company', name: 'contact_company_add', methods: ['GET'])]
    public function getAddFormCompany(): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->json([
            'form' => $this->contactForm->getFormFields(true),
            'sections' => $this->contactForm->getFormSections(),
        ]);
    }

    #[Route('/add', name: 'contact_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        // todo: do this the symfony way - how?
        $addressFields = ['street', 'zip', 'city'];
        $address = new ContactAddress();
        $contactHasAddress = false;

        foreach ($addressFields as $addressField) {
            if (!isset($data[$addressField])) {
                continue;
            }
            $contactHasAddress = true;
            $setterName = 'set' . ucfirst($addressField);
            $address->$setterName($data[$addressField]);
            unset($data[$addressField]);
        }

        $contact = new Contact();

        $form = $this->createForm(ContactType::class, $contact);
        $form->submit($data);

        if (!$form->isValid()) {
            if ($this->contactRepository->checkDuplicateEmail($contact->getEmailPrivate())) {
                return $this->json([
                    ['message' => $this->translator->trans('contact.form.validation.emailDuplicate')]
                ], 400);
            }
            if ($this->contactRepository->checkDuplicateEmail($contact->getEmailBusiness())) {
                return $this->json([
                    ['message' => $this->translator->trans('contact.form.validation.emailDuplicate')]
                ], 400);
            }
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        // manual validation
        if ($contact->getFirstName() === null && $contact->getLastName() === null && $contact->getCompanyName() === null) {
            return $this->json([
                ['message' => 'You must provide a first or last name']
            ], 400);
        }

        // set readonly fields
        $contact->setCreatedBy($this->getUser()->getId());
        $contact->setCreatedDate(new DateTimeImmutable());

        // save contact
        $this->contactRepository->save($contact, true);

        // attach address to contact
        $address->setContact($contact);

        // save address
        if ($contactHasAddress) {
            $this->addressRepository->save($address, true);
        }

        return $this->itemResponse($contact);
    }

    #[Route('/edit/{id}', name: 'contact_edit', methods: ['GET'])]
    public function getEditForm(Contact $contact): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($contact);
    }

    #[Route('/edit/{id}', name: 'contact_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        Contact $contact,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        // unset readonly fields
        unset($data['address']);
        unset($data['jobs']);
        unset($data['createdBy']);
        unset($data['createdDate']);
        unset($data['id']);

        $form = $this->createForm(ContactType::class, $contact);
        $form->submit($data);

        if (!$form->isValid()) {
            if ($this->contactRepository->checkDuplicateEmail($contact->getEmailPrivate(), $contact->getId())) {
                return $this->json([
                    ['message' => 'E-Mail is already used by another contact']
                ], 400);
            }
            if ($this->contactRepository->checkDuplicateEmail($contact->getEmailBusiness(), $contact->getId())) {
                return $this->json([
                    ['message' => 'E-Mail is already used by another contact']
                ], 400);
            }

            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $this->contactRepository->save($contact, true);

        return $this->itemResponse($contact);
    }

    #[Route('/signup/step1', name: 'contact_signup_step1', methods: ['POST'])]
    public function signupStep1(
        Request $request,
        MailerInterface $mailer,
        SystemSettingRepository $systemSettings,
    ): Response {
        if (!$this->getParameter('contact.signup_enabled')) {
            throw new HttpException(404);
        }

        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        if (!$data['email']) {
            throw new HttpException(400, 'Please provide a valid e-mail');
        }

        $contactExists = $this->contactRepository->findOneBy(['emailPrivate' => $data['email']]);
        if ($contactExists) {
            throw new HttpException(400, 'E-Mail already exists');
        }

        //$systemUser = $this->userRepository->findOneBy(['username' => 'system']);

        $contact = new Contact();
        $contact->setEmailPrivate($data['email']);
        $contact->setCreatedDate(new DateTimeImmutable());
        $contact->setCreatedBy(0);

        $this->contactRepository->save($contact, true);

        $emailSubject = $systemSettings->findOneBy(['settingKey' => 'contact-signup-email-subject']);
        $emailContent = $systemSettings->findOneBy(['settingKey' => 'contact-signup-email-content']);
        $emailText = $systemSettings->findOneBy(['settingKey' => 'contact-signup-email-text']);

        $email = (new Email())
            ->from($this->getParameter('mailer.from'))
            ->to($data['email'])
            ->priority(Email::PRIORITY_HIGH)
            ->subject($emailSubject->getSettingValue())
            ->text($emailText->getSettingValue())
            ->html($emailContent->getSettingValue())
        ;

        $mailer->send($email);

        return $this->json(['success' => true]);
    }

    #[Route('/view/{id}', name: 'contact_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        Contact $contact,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($contact);
    }

    #[Route('/remove/{id}', name: 'contact_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(
        Contact $contact,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $addresses = $this->addressRepository->findBy(['contact' => $contact]);
        foreach ($addresses as $address) {
            $this->addressRepository->remove($address);
        }
        $this->contactRepository->remove($contact, true);

        return $this->json(['state' => 'success']);
    }

    #[Route('/list', name: 'contact_index', methods: ['GET'])]
    #[Route('/list/{page}', name: 'contact_index_pagination', methods: ['GET'])]
    public function list(
        ?int $page,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $pageSize = $this->userSettings->getUserSetting(
            $this->getUser(),
            'pagination-page-size',
        );
        $page = $page ?? 1;
        $contacts = $this->contactRepository->findBySearchAttributes($page, $pageSize);

        $data = [
            'headers' => $this->contactForm->getIndexHeaders(),
            'items' => $contacts,
            'total_items' => count($contacts),
            'pagination' => [
                'page_size' => $pageSize,
                'page' => $page,
            ],
        ];

        return $this->json($data, 200);
    }

    private function itemResponse(
        Contact $contact,
    ): Response {
        $addresses = $this->addressRepository->findBy(['contact' => $contact]);
        foreach ($addresses as $address) {
            $contact->addAddress($address);
        }

        $data = [
            'item' => $contact,
            'form' => $this->contactForm->getFormFields($contact->isIsCompany()),
            'sections' => $this->contactForm->getFormSections(),
        ];

        return $this->json($data);
    }
}