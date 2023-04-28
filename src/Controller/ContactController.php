<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\Contact\ContactType;
use App\Repository\ContactRepository;
use App\Service\Contact\ContactManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactManager $contactManager,
    )
    {
    }

    #[Route('/', name: 'contact_index', methods: ['GET'])]
    public function list(
        ContactRepository $contactRepository,
    ): Response
    {
        $contacts = $contactRepository->findBySearchAttributes();

        $data = [
            'headers' => $this->contactManager->getIndexHeaders(),
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

    #[Route('/add', name: 'contact_add', methods: ['GET'])]
    public function getAddForm(
        CsrfTokenManagerInterface $tokenManager,
    ): Response {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);

        return $this->json([
            'form' => $form,
            'token' => $tokenManager->getToken('contact')->getValue(),
        ]);
    }

    #[Route('/add', name: 'contact_add_save', methods: ['POST'])]
    public function saveAddForm(
        Request $request,
        ContactRepository $contactRepository,
    ): Response {
        $body = $request->getContent();
        $data = json_decode($body, true);

        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->json(
                $form->getErrors(),
                400
            );
        }

        $contact->setCreatedBy($this->getUser()->getId());
        $contact->setCreatedDate(new \DateTime());

        $contactRepository->save($contact, true);

        return $this->itemResponse($contact);
    }

    #[Route('/edit/{id}', name: 'contact_edit', methods: ['GET'])]
    public function getEditForm(
        CsrfTokenManagerInterface $tokenManager,
        Contact $contact,
    ): Response {
        $form = $this->createForm(ContactType::class, $contact);

        return $this->json([
            'form' => $form,
            'token' => $tokenManager->getToken('contact')->getValue(),
            'data' => $contact
        ]);
    }

    #[Route('/edit/{id}', name: 'contact_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        ContactRepository $contactRepository,
        Contact $contact,
    ): Response {
        $body = $request->getContent();
        $data = json_decode($body, true);

        $form = $this->createForm(ContactType::class, $contact);
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->json(
                $form->getErrors(),
                400
            );
        }

        $contactRepository->save($contact, true);

        return $this->itemResponse($contact);
    }

    #[Route('/{id}', name: 'contact_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        Contact $contact,
    ): Response {
        return $this->itemResponse($contact);
    }

    private function itemResponse(
        Contact $contact,
    ): Response {
        $data = [
            'item' => $contact,
        ];

        return $this->json($data);
    }
}