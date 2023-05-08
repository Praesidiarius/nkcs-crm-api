<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactAddress;
use App\Form\Contact\ContactType;
use App\Repository\ContactAddressRepository;
use App\Repository\ContactRepository;
use App\Service\Contact\ContactManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactManager $contactManager,
        private readonly ContactType $contactForm,
        private readonly ContactRepository $contactRepository,
        private readonly ContactAddressRepository $addressRepository,
    )
    {
    }

    #[Route('/add', name: 'contact_add', methods: ['GET'])]
    #[Route('/add/{_locale}', name: 'contact_add_translated', methods: ['GET'])]
    public function getAddForm(): Response {

        return $this->json([
            'form' => $this->contactForm->getFormFields(),
            'sections' => $this->contactForm->getFormSections(),
        ]);
    }

    #[Route('/add', name: 'contact_add_save', methods: ['POST'])]
    #[Route('/add/{_locale}', name: 'contact_add_save_translated', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
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
                    ['message' => 'E-Mail is already used by another contact']
                ], 400);
            }
            if ($this->contactRepository->checkDuplicateEmail($contact->getEmailBusiness())) {
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

        // manual validation
        if ($contact->getFirstName() === null && $contact->getLastName() === null) {
            return $this->json([
                ['message' => 'You must provide a first or last name']
            ], 400);
        }

        // set readonly fields
        $contact->setCreatedBy($this->getUser()->getId());
        $contact->setCreatedDate(new \DateTime());

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
    #[Route('/edit/{_locale}/{id}', name: 'contact_edit_translated', methods: ['GET'])]
    public function getEditForm(Contact $contact): Response {
        return $this->itemResponse($contact);
    }

    #[Route('/edit/{id}', name: 'contact_edit_save', methods: ['POST'])]
    #[Route('/edit/{_locale}/{id}', name: 'contact_edit_save_translated', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        Contact $contact,
    ): Response {
        $body = $request->getContent();
        $data = json_decode($body, true);

        // unset readonly fields
        unset($data['address']);
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

    #[Route('/{id}', name: 'contact_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[Route('/{_locale}/{id}', name: 'contact_view_translated', methods: ['GET'])]
    public function view(
        Contact $contact,
    ): Response {
        return $this->itemResponse($contact);
    }

    #[Route('/{id}', name: 'contact_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[Route('/{_locale}/{id}', name: 'contact_delete_translated', methods: ['DELETE'])]
    public function delete(
        Contact $contact,
    ): Response {
        $addresses = $this->addressRepository->findBy(['contact' => $contact]);
        foreach ($addresses as $address) {
            $this->addressRepository->remove($address);
        }
        $this->contactRepository->remove($contact, true);

        return $this->json(['state' => 'success']);
    }

    #[Route('/', name: 'contact_index', methods: ['GET'])]
    #[Route('/{_locale}', name: 'contact_index_translated', methods: ['GET'])]
    public function list(): Response
    {
        $contacts = $this->contactRepository->findBySearchAttributes();

        $data = [
            'headers' => $this->contactForm->getIndexHeaders(),
            'items' => $contacts,
            'total_items' => count($contacts),
            'pagination' => [
                'pages' => 1,
                'page_size' => 35,
                'page' => 1,
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
            'form' => $this->contactForm->getFormFields(),
            'sections' => $this->contactForm->getFormSections(),
        ];

        return $this->json($data);
    }
}