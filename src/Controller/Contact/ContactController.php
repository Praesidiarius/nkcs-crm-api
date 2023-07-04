<?php

namespace App\Controller\Contact;

use App\Controller\AbstractApiController;
use App\Controller\AbstractDynamicFormController;
use App\Form\Contact\ContactCompanyType;
use App\Form\Contact\ContactType;
use App\Model\DynamicDto;
use App\Repository\ContactAddressRepository;
use App\Repository\ContactRepository;
use App\Repository\UserSettingRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/contact/{_locale}')]
class ContactController extends AbstractDynamicFormController
{
    public function __construct(
        private readonly ContactType              $contactForm,
        private readonly ContactCompanyType $companyForm,
        private readonly ContactRepository        $contactRepository,
        private readonly ContactAddressRepository $addressRepository,
        private readonly UserSettingRepository    $userSettings,
        private readonly HttpClientInterface      $httpClient,
        private readonly TranslatorInterface      $translator,
        private readonly DynamicDto               $dynamicDto,
    )
    {
        parent::__construct($this->httpClient);
    }

    #[Route('/add', name: 'contact_add', methods: ['GET'])]
    public function getAddForm($form = null, $formKey = 'contact'): Response {
        return parent::getAddForm($this->contactForm, 'contact');
    }

    #[Route('/add-company', name: 'contact_company_add', methods: ['GET'])]
    public function getAddFormCompany($form = null, string $formKey = 'company'): Response {
        return parent::getAddFormCompany($this->companyForm, 'company');
    }

    #[Route('/add', name: 'contact_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $data['is_company'] = (int) $data['is_company'];

        // move address related data out of data for validation
        $addressFields = ['street', 'zip', 'city'];
        $addressData = [];
        $contactHasAddress = false;

        foreach ($addressFields as $addressField) {
            if (!isset($data[$addressField])) {
                continue;
            }
            $contactHasAddress = true;
            $addressData[$addressField] = $data[$addressField];
            unset($data[$addressField]);
        }

        $form = $this->createForm($data['is_company'] === 1 ? ContactCompanyType::class : ContactType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $contact = $this->dynamicDto;

        $contact->setData($data);

        // manual validation
        if ($contact->getTextField('email_private')) {
            if ($this->contactRepository->isEmailAddressAlreadyInUse($contact->getTextField('email_private'))) {
                return $this->json([
                    ['message' => $this->translator->trans('contact.form.validation.emailDuplicate')]
                ], 400);
            }
        }
        if (
            $contact->getTextField('first_name') === null
            && $contact->getTextField('last_name') === null
            && $contact->getTextField('company_name') === null
        ) {
            return $this->json([
                ['message' => 'You must provide a first or last name']
            ], 400);
        }

        // set readonly fields
        $contact->setCreatedBy($this->getUser()->getId());
        $contact->setCreatedDate();

        // save contact
        $contact = $this->contactRepository->save($contact);

        // save address
        if ($contactHasAddress) {
            $addressData['contact_id'] = $contact->getId();
            $address = $this->dynamicDto;
            $address->setData($addressData);
            $this->addressRepository->save($address);
        }

        return $this->itemResponse($contact);
    }

    #[Route('/edit/{contactId}', name: 'contact_edit', methods: ['GET'])]
    public function getEditForm(int $contactId): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $contact = $this->contactRepository->findById($contactId);

        return $this->itemResponse($contact);
    }

    #[Route('/edit/{contactId}', name: 'contact_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        int $contactId,
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

        $form = $this->createForm(ContactType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $contact = $this->dynamicDto;

        $contact->setData($data);
        $contact->setId($contactId);

        $this->contactRepository->save($contact);

        return $this->itemResponse($contact);
    }

    #[Route('/view/{contactId}', name: 'contact_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        int $contactId,
    ): Response {
        $contact = $this->contactRepository->findById($contactId);
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($contact);
    }

    #[Route('/remove/{contactId}', name: 'contact_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(
        int $contactId,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $this->addressRepository->removeByContactId($contactId);
        $this->contactRepository->removeById($contactId);

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

        $contactsApi = [];
        foreach ($contacts as $contactRaw) {
            $contactApi = $this->dynamicDto;
            $contactApi->setData($contactRaw);
            $contactApi->serializeDataForApiByFormModel('contact');
            $contactsApi[] = $contactApi->getDataSerialized();
        }

        $data = [
            'headers' => $this->contactForm->getIndexHeaders(),
            'items' => $contactsApi,
            'total_items' => count($contacts),
            'pagination' => [
                'page_count' => ceil(count($contacts) / $pageSize),
                'page_size' => $pageSize,
                'page' => $page,
            ],
        ];

        return $this->json($data);
    }

    private function itemResponse(
        ?DynamicDto $contact,
    ): Response {
        if (!$contact) {
            return $this->json(['message' => 'contact not found'], 404);
        }

        $contact->serializeDataForApiByFormModel('contact');

        $data = [
            'item' => $contact->getDataSerialized(),
            'form' => $this->contactForm->getFormFields($contact->getBoolField('is_company'), true),
            'sections' => $this->contactForm->getFormSections(),
        ];

        return $this->json($data);
    }
}