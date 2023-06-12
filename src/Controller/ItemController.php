<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\Contact\ContactType;
use App\Form\Item\ItemType;
use App\Repository\ItemRepository;
use App\Repository\UserSettingRepository;
use DateTimeImmutable;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/item/{_locale}')]
class ItemController extends AbstractApiController
{
    public function __construct(
        private readonly ItemType $itemType,
        private readonly ItemRepository $itemRepository,
        private readonly UserSettingRepository $userSettings,
        private readonly HttpClientInterface $httpClient,
    )
    {
        parent::__construct($this->httpClient);
    }

    #[Route('/add', name: 'item_add', methods: ['GET'])]
    public function getAddForm(): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->json([
            'form' => $this->itemType->getFormFields(),
            'sections' => $this->itemType->getFormSections(),
        ]);
    }

    #[Route('/add', name: 'item_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

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
                'description' => $item->getDescription(),
            ]);

            $stripePriceData = [
                // stripe wants amount in cents
                'unit_amount' => $item->getPrice() * 100,
                'currency' => $this->getParameter('payment.currency'),
                'product' => $stripeProduct['id']
            ];

            if ($item->getUnit()->getType() === 'sub-month') {
                $stripePriceData['recurring'] = ['interval' => 'month'];
            }
            if ($item->getUnit()->getType() === 'sub-year') {
                $stripePriceData['recurring'] = ['interval' => 'year'];
            }
            $stripePrice = $stripeClient->prices->create($stripePriceData);

            $item->setStripePriceId($stripePrice['id']);

            $this->itemRepository->save($item, true);
        }

        return $this->itemResponse($item);
    }

    #[Route('/edit/{id}', name: 'item_edit', methods: ['GET'])]
    public function getEditForm(Item $item): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($item);
    }

    #[Route('/edit/{id}', name: 'item_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        Item $item,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

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

    #[Route('/view/{id}', name: 'item_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        Item $item,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($item);
    }

    #[Route('/remove/{id}', name: 'item_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(
        Item $item,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $this->itemRepository->remove($item, true);

        return $this->json(['state' => 'success']);
    }

    #[Route('/list', name: 'item_index', methods: ['GET'])]
    #[Route('/list/{page}', name: 'item_index_with_pagination', methods: ['GET'])]
    public function list(
        ?int $page,
    ): Response
    {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $pageSize = $this->userSettings->getUserSetting(
            $this->getUser(),
            'pagination-page-size',
        );
        $page = $page ?? 1;
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