<?php

namespace App\Controller;

use App\Form\Contact\ContactType;
use App\Form\DynamicType;
use App\Form\Item\ItemType;
use App\Model\DynamicDto;
use App\Repository\AbstractRepository;
use App\Repository\ItemRepository;
use App\Repository\UserSettingRepository;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/item/{_locale}')]
class ItemController extends AbstractDynamicFormController
{
    public function __construct(
        private readonly ItemType              $itemForm,
        private readonly ItemRepository        $itemRepository,
        private readonly UserSettingRepository $userSettings,
        private readonly HttpClientInterface   $httpClient,
        private readonly DynamicDto            $dynamicDto,
    )
    {
        parent::__construct($this->httpClient, $this->userSettings, $this->dynamicDto);
    }

    #[Route('/add', name: 'item_add', methods: ['GET'])]
    public function getAddForm($form = null, $formKey = 'contact'): Response {
        return parent::getAddForm($this->itemForm, 'item');
    }

    #[Route('/add', name: 'item_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $form = $this->createForm(ItemType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $item = $this->dynamicDto;
        $item->setData($data);

        // manual validation
        if ($item->getTextField('name') === null) {
            return $this->json([
                ['message' => 'You must provide a name']
            ], 400);
        }

        // set readonly fields
        $item->setCreatedBy($this->getUser()->getId());
        $item->setCreatedDate();

        // save contact
        $item = $this->itemRepository->save($item);

        /**
         * Stripe Integration
         */
        if ($this->getParameter('payment.stripe_key') && $item->getBoolField('stripe_enabled')) {
            $stripeClient = new StripeClient($this->getParameter('payment.stripe_secret'));

            $stripeProduct = $stripeClient->products->create([
                'name' => $item->getTextField('name'),
                'description' => $item->getTextField('description'),
            ]);

            $stripePriceData = [
                // stripe wants amount in cents
                'unit_amount' => ($item->getPriceField('price') ?? 0) * 100,
                'currency' => $this->getParameter('payment.currency'),
                'product' => $stripeProduct['id']
            ];

            if ($item->getSelectField('unit_id')['type'] === 'sub-month') {
                $stripePriceData['recurring'] = ['interval' => 'month'];
            }
            if ($item->getSelectField('unit_id')['type'] === 'sub-year') {
                $stripePriceData['recurring'] = ['interval' => 'year'];
            }
            $stripePrice = $stripeClient->prices->create($stripePriceData);

            $item->setTextField('stripe_price_id', $stripePrice['id']);

            $item = $this->itemRepository->save($item);
        }

        return $this->itemResponse($item);
    }

    #[Route('/edit/{itemId}', name: 'item_edit', methods: ['GET'])]
    public function getEditForm(int $itemId): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $item = $this->itemRepository->findById($itemId);

        return $this->itemResponse($item);
    }

    #[Route('/edit/{id}', name: 'item_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        int $itemId,
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

        $item = $this->dynamicDto;

        $item->setData($data);
        $item->setId($itemId);

        $item = $this->itemRepository->save($item);

        return $this->itemResponse($item);
    }

    #[Route('/view/{entityId}', name: 'item_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        int $entityId,
        ?AbstractRepository $repository = null,
        string $formKey = 'item',
        ?DynamicType $form = null,
    ): Response {
        $item = $this->itemRepository->findById($entityId);
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($item, 'item', $this->itemForm);
    }

    #[Route('/remove/{itemId}', name: 'item_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(
        int $itemId,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        if ($itemId > 0) {
            $this->itemRepository->removeById($itemId);
        }

        return $this->json(['state' => 'success']);
    }

    #[Route('/list', name: 'item_index', methods: ['GET'])]
    #[Route('/list/{page}', name: 'item_index_with_pagination', methods: ['GET'])]
    public function list(
        ?int $page,
        ?AbstractRepository $repository = null,
        ?DynamicType $form = null,
        string $formKey = '',
    ): Response {
        return parent::list($page, $this->itemRepository, $this->itemForm, 'item');
    }

    protected function itemResponse(
        ?DynamicDto $dto,
        string $formKey = 'item',
        ?DynamicType $form = null,
    ): Response {
        return parent::itemResponse(
            $dto,
            'item',
            $this->itemForm
        );
    }
}