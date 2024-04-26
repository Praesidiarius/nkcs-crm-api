<?php

namespace App\Controller\Item;

use App\Controller\AbstractDynamicFormController;
use App\Form\DynamicType;
use App\Form\Item\ItemType;
use App\Model\DynamicDto;
use App\Repository\AbstractRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\ItemPriceHistoryRepository;
use App\Repository\ItemRepository;
use App\Repository\SystemSettingRepository;
use App\Repository\TableFieldRelatedTableRepository;
use App\Repository\UserSettingRepository;
use App\Service\DataExporter;
use Doctrine\DBAL\Connection;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/item/{_locale}')]
class ItemController extends AbstractDynamicFormController
{
    public function __construct(
        private readonly ItemType $itemForm,
        private readonly ItemRepository $itemRepository,
        private readonly UserSettingRepository $userSettings,
        private readonly HttpClientInterface $httpClient,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly Connection $connection,
        private readonly DataExporter $dataExporter,
        private readonly SystemSettingRepository $systemSettings,
        #[Autowire(lazy: true)]
        private readonly ItemPriceHistoryRepository $priceHistoryRepository,
        private readonly SerializerInterface $serializer,
    ) {
        parent::__construct(
            $this->httpClient,
            $this->userSettings,
            $this->dynamicFormFieldRepository,
            $this->connection,
        );
    }

    #[Route('/add', name: 'item_add', methods: ['GET'])]
    public function getAddForm($form = null, $formKey = 'item'): Response {
        return parent::getAddForm($this->itemForm, 'item');
    }

    #[Route('/add', name: 'item_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        // Item Price History Extension
        $isPriceHistoryEnabled = (bool) $this->systemSettings
            ->findSettingByKey('item-price-history-extension-enabled'
            )?->getSettingValue() ?? false
        ;
        $itemHasPrice = false;
        $priceData = [];

        if ($isPriceHistoryEnabled) {
            // move price history related data out of data for validation
            $priceForm = $this->dynamicFormFieldRepository->getUserFieldsByFormKey('itemPrice');
            $priceFields = [];
            foreach ($priceForm as $priceField) {
                $priceFields[] = $priceField->getFieldKey();
            }

            foreach ($priceFields as $priceField) {
                if (!isset($data[$priceField])) {
                    continue;
                }
                $itemHasPrice = true;
                $priceData[$priceField] = $data[$priceField];
                unset($data[$priceField]);
            }
        }

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

        $item = $this->itemRepository->getDynamicDto();
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

        /** Price History Extension */
        if ($itemHasPrice) {
            $priceData['item_id'] = $item->getId();
            $price = new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
            $price->setData($priceData);
            // set readonly fields
            $price->setCreatedBy($this->getUser()->getId());
            $price->setCreatedDate();
            $this->priceHistoryRepository->save($price);
        }

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

        $item = new DynamicDto($this->dynamicFormFieldRepository, $this->connection);

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

    #[Route(
        path: '/add-table-field-entry/{itemId}/{tableField}',
        name: 'item_add_price',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['POST']
    )]
    public function addTableFieldEntry(
        int $itemId,
        string $tableField,
        Request $request,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        if (!$this->itemForm->hasTableFieldForUser($tableField)) {
            throw new HttpException(404, 'item form has no field "' . $tableField . '"');
        }

        $tableFieldFormFields = $this->dynamicFormFieldRepository->getUserFieldsByFormKey('item' . ucfirst($tableField));

        $body = $request->getContent();
        $data = json_decode($body, true);

        $tableFieldData = [];
        foreach ($tableFieldFormFields as $formField) {
            if (array_key_exists($formField->getFieldKey(), $data)) {
                $tableFieldData[$formField->getFieldKey()] = $data[$formField->getFieldKey()];
            }
        }

        $tableFieldData['item_id'] = $itemId;

        $model = new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
        $model->setData($tableFieldData);
        // set readonly fields
        $model->setCreatedBy($this->getUser()->getId());
        $model->setCreatedDate();

        // get repo for table field related table
        $repository = new TableFieldRelatedTableRepository(
            connection: $this->connection,
            dynamicFormFieldRepository: $this->dynamicFormFieldRepository,
            baseTable: 'item_price',
            relationField: 'item_id'
        );
        $repository->save($model);

        $item = $this->itemRepository->findById($itemId);

        return $this->itemResponse($item, 'item', $this->itemForm);
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

    #[Route('/export', name: 'item_export', methods: ['GET'])]
    public function export(): Response {
        $items = $this->itemRepository->findAll();

        $excelFileName = $this->dataExporter->getExcelExport($this->itemForm, $items);

        return $this->json([
            'document' => $this->dataExporter->getDocumentAsBase64Download($excelFileName),
            'name' => 'item_export.xlsx',
        ]);
    }

    protected function itemResponse(
        ?DynamicDto $dto,
        string $formKey = 'item',
        ?DynamicType $form = null,
        array $extraData = [],
    ): Response {
        return parent::itemResponse(
            $dto,
            'item',
            $this->itemForm,
            $extraData,
        );
    }
}