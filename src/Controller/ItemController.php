<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\Contact\ContactType;
use App\Form\Item\ItemType;
use App\Repository\ItemRepository;
use App\Repository\UserSettingRepository;
use DateTimeImmutable;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/item')]
class ItemController extends AbstractController
{
    public function __construct(
        private readonly ItemType $itemType,
        private readonly ItemRepository $itemRepository,
        private readonly UserSettingRepository $userSettings,
    )
    {
    }

    #[Route('/add', name: 'item_add', methods: ['GET'])]
    #[Route('/add/{_locale}', name: 'item_add_translated', methods: ['GET'])]
    public function getAddForm(): Response {

        return $this->json([
            'form' => $this->itemType->getFormFields(),
            'sections' => $this->itemType->getFormSections(),
        ]);
    }

    #[Route('/add', name: 'item_add_save', methods: ['POST'])]
    #[Route('/add/{_locale}', name: 'item_add_save_translated', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        $body = $request->getContent();
        $data = json_decode($body, true);

        $item = new Item();

        $form = $this->createForm(ItemType::class, $item);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        // manual validation
        if ($item->getName() === null) {
            return $this->json([
                ['message' => 'You must provide a name']
            ], 400);
        }

        // set readonly fields
        $item->setCreatedBy($this->getUser()->getId());
        $item->setCreatedDate(new DateTimeImmutable());

        // save contact
        $this->itemRepository->save($item, true);

        /**
         * Stripe Integration
         */
        if ($this->getParameter('payment.stripe_key') && $item->isStripeEnabled()) {
            $stripeClient = new StripeClient($this->getParameter('payment.stripe_secret'));

            $stripeProduct = $stripeClient->products->create([
                'name' => $item->getName(),
                'description' => 'A Test Item'
            ]);

            $stripePrice = $stripeClient->prices->create([
                // stripe wants amount in cents
                'unit_amount' => $item->getPrice() * 100,
                'currency' => 'chf',
                // todo: need a flag for subscriptions on items
                'recurring' => ['interval' => 'month'],
                'product' => $stripeProduct['id']
            ]);
        }

        return $this->itemResponse($item);
    }

    #[Route('/edit/{id}', name: 'item_edit', methods: ['GET'])]
    #[Route('/edit/{_locale}/{id}', name: 'item_edit_translated', methods: ['GET'])]
    public function getEditForm(Item $item): Response {
        return $this->itemResponse($item);
    }

    #[Route('/edit/{id}', name: 'item_edit_save', methods: ['POST'])]
    #[Route('/edit/{_locale}/{id}', name: 'item_edit_save_translated', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        Item $item,
    ): Response {
        $body = $request->getContent();
        $data = json_decode($body, true);

        // unset readonly fields
        unset($data['createdBy']);
        unset($data['createdDate']);
        unset($data['id']);

        $form = $this->createForm(ContactType::class, $item);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $this->itemRepository->save($item, true);

        return $this->itemResponse($item);
    }

    #[Route('/{id}', name: 'item_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[Route('/{_locale}/{id}', name: 'item_view_translated', methods: ['GET'])]
    public function view(
        Item $item,
    ): Response {
        return $this->itemResponse($item);
    }

    #[Route('/{id}', name: 'item_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[Route('/{_locale}/{id}', name: 'item_delete_translated', methods: ['DELETE'])]
    public function delete(
        Item $item,
    ): Response {
        $this->itemRepository->remove($item, true);

        return $this->json(['state' => 'success']);
    }

    #[Route('/', name: 'item_index', methods: ['GET'])]
    #[Route('/{_locale}', name: 'item_index_translated', methods: ['GET'])]
    public function list(
        Request $request,
    ): Response
    {
        $pageSize = $this->userSettings->getUserSetting(
            $this->getUser(),
            'pagination-page-size',
        );
        $page = $request->query->getInt('page', 1);
        $items = $this->itemRepository->findBySearchAttributes($page, $pageSize);

        $data = [
            'headers' => $this->itemType->getIndexHeaders(),
            'items' => $items,
            'total_items' => count($items),
            'pagination' => [
                'page_size' => $pageSize,
                'page' => $page,
            ],
        ];

        return $this->json($data, 200);
    }

    private function itemResponse(
        Item $item,
    ): Response {
        $data = [
            'item' => $item,
            'form' => $this->itemType->getFormFields(),
            'sections' => $this->itemType->getFormSections(),
        ];

        return $this->json($data);
    }
}