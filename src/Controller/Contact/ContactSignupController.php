<?php

namespace App\Controller\Contact;

use App\Controller\AbstractApiController;
use App\Repository\ContactAddressRepository;
use App\Repository\ContactRepository;
use App\Repository\SystemSettingRepository;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/signup')]
class ContactSignupController extends AbstractApiController
{
    public function __construct(
        private readonly ContactRepository  $contactRepository,
        private readonly ContactAddressRepository $addressRepository,
        private readonly HttpClientInterface      $httpClient,
    )
    {
        parent::__construct($this->httpClient);
    }

    #[Route('/step1', name: 'signup_step1', methods: ['POST'])]
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

        $contactExists = $this->contactRepository->findByEmail($data['email']);
        if ($contactExists) {
            throw new HttpException(400, 'E-Mail already exists');
        }

        //$systemUser = $this->userRepository->findOneBy(['username' => 'system']);

        $signupCode = Uuid::v4();
        $factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'bcrypt'],
        ]);

        $hasher = $factory->getPasswordHasher('common');
        $hash = str_replace(['$', '/','\\','.'], [], $hasher->hash($signupCode));

        $contact = $this->contactRepository->getDynamicDto();
        $contact->setTextField('email_private', $data['email']);
        $contact->setCreatedDate();
        $contact->setCreatedBy(0);
        $contact->setDateField('signup_date',new DateTimeImmutable());
        $contact->setTextField('signup_token', $hash);

        $this->contactRepository->save($contact);

        $emailSubject = $systemSettings->findOneBy(['settingKey' => 'contact-signup-email-subject']);
        $emailContent = $systemSettings->findOneBy(['settingKey' => 'contact-signup-email-content']);
        $emailText = $systemSettings->findOneBy(['settingKey' => 'contact-signup-email-text']);
        $emailName = $systemSettings->findOneBy(['settingKey' => 'contact-signup-email-name']);

        $email = (new Email())
            ->from(new Address($this->getParameter('mailer.from'), $emailName->getSettingValue()))
            ->to($data['email'])
            ->priority(Email::PRIORITY_HIGH)
            ->subject($emailSubject->getSettingValue())
            ->text(str_replace(['#HASH#'], [$hash], $emailText->getSettingValue()))
            ->html(str_replace(['#HASH#'], [$hash], $emailContent->getSettingValue()))
        ;

        $mailer->send($email);

        return $this->json(['success' => true]);
    }

    #[Route('/step2/{hash}', name: 'signup_step2', methods: ['POST'])]
    public function signupStep2(
        Request $request,
        string $hash,
    ): Response
    {
        if (!$this->getParameter('contact.signup_enabled')) {
            throw new HttpException(404);
        }

        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $contact = $this->contactRepository->findBySignupToken($hash);

        if (!$contact) {
            throw new HttpException(404, 'invalid signup token');
        }

        // update contact details
        $contact->setBoolField('is_company', true);
        $contact->setTextField('company_name', $data['name']);
        $contact->setTextField('first_name', $data['firstname']);
        $contact->setTextField('last_name', $data['lastname']);
        $contact->setTextField('company_uid', $data['uid']);
        $contact->setTextField('contact_identifier', $data['url']);

        $this->contactRepository->save($contact);

        // add address
        $address = $this->contactRepository->getDynamicDto();
        $address->setData([]);
        $address->setSelectField('contact_id', $contact->getId());
        $address->setIntField('zip', (int) $data['zip']);
        $address->setTextField('city', $data['city']);
        $address->setTextField('street', $data['street']);

        $this->addressRepository->save($address);

        // start installation of new system
        if ($this->getParameter('installer.queue_dir')) {
            $uuid = Uuid::v4();
            file_put_contents(
                $this->getParameter('installer.queue_dir') . '/install_' . $uuid,
                json_encode($data),
            );
        }


        return $this->json(['success' => true], 201);
    }
}