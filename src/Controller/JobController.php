<?php

namespace App\Controller;

use App\Entity\JobPosition;
use App\Enum\JobVatMode;
use App\Form\DynamicType;
use App\Form\Job\JobType;
use App\Model\DynamicDto;
use App\Model\JobDto;
use App\Repository\AbstractRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\ItemRepository;
use App\Repository\ItemUnitRepository;
use App\Repository\JobPositionRepository;
use App\Repository\JobRepository;
use App\Repository\UserSettingRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/job/{_locale}')]
class JobController extends AbstractDynamicFormController
{
    public function __construct(
        private readonly JobType $jobForm,
        private readonly JobRepository $jobRepository,
        private readonly JobPositionRepository $jobPositionRepository,
        private readonly UserSettingRepository $userSettings,
        private readonly ItemRepository $itemRepository,
        private readonly ItemUnitRepository $itemUnitRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly TranslatorInterface $translator,
        private readonly DynamicFormFieldRepository $dynamicFormFieldRepository,
        private readonly Connection $connection,
    ) {
        parent::__construct(
            $this->httpClient,
            $this->userSettings,
            $this->dynamicFormFieldRepository,
            $this->connection,
        );
    }

    #[Route('/add', name: 'job_add', methods: ['GET'])]
    public function getAddForm($form = null, $formKey = 'job'): Response {
        return parent::getAddForm($this->jobForm, 'jobType1');
    }

    #[Route('/add', name: 'job_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $form = $this->createForm(JobType::class);
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
        /**
        if ($job->getTitle() === null) {
            return $this->json([
                ['message' => 'You must provide a title']
            ], 400);
        } **/

        $job = new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
        $job->setData($data);

        $job->setSelectField('type_id', 1);

        // set readonly fields
        $job->setCreatedBy($this->getUser()->getId());
        $job->setCreatedDate();

        // save job
        $this->jobRepository->save($job);

        return $this->itemResponse($job);
    }

    #[Route('/edit/{jobId}', name: 'job_edit', methods: ['GET'])]
    public function getEditForm(int $jobId): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $job = $this->jobRepository->findById($jobId);

        return $this->itemResponse($job);
    }

    #[Route('/edit/{jobId}', name: 'job_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        int $jobId,
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

        $form = $this->createForm(JobType::class);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $job = new DynamicDto($this->dynamicFormFieldRepository, $this->connection);
        $job->setData($data);
        $job->setId($jobId);

        $this->jobRepository->save($job);

        return $this->itemResponse($job);
    }

    #[Route('/view/{entityId}', name: 'job_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        int $entityId,
        ?AbstractRepository $repository = null,
        string $formKey = 'item',
        ?DynamicType $form = null,
    ): Response {
        $job = $this->jobRepository->findById($entityId);
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $extraData = [
            'positions' => $job->getJobPositions(),
            'position_units' => $this->listUnits()
        ];

        return $this->itemResponse($job, 'job', $this->jobForm, $extraData);
    }

    #[Route('/remove/{jobId}', name: 'job_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(
        int $jobId,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        if ($jobId > 0) {
            $this->jobRepository->removeById($jobId);
        }

        return $this->json(['state' => 'success']);
    }

    #[Route('/list', name: 'job_index', methods: ['GET'])]
    #[Route('/list/{page}', name: 'job_index_with_pagination', methods: ['GET'])]
    public function list(
        ?int $page,
        ?AbstractRepository $repository = null,
        ?DynamicType $form = null,
        string $formKey = 'jobType1',
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $pageSize = $this->userSettingRepository->getUserSetting(
            $this->getUser(),
            'pagination-page-size',
        );
        $page = $page ?? 1;
        $items = $this->jobRepository->findBySearchAttributes($page, $pageSize);

        $itemsApi = [];
        foreach ($items as $itemRaw) {
            $itemApi = $this->jobRepository->getDynamicDto();
            $itemApi->setData($itemRaw);
            $itemApi->serializeDataForApiByFormModel($formKey);
            $itemsApi[] = $itemApi->getDataSerialized();
        }

        $data = [
            'headers' => $this->jobForm->getIndexHeaders($formKey),
            'items' => $itemsApi,
            'total_items' => count($items),
            'pagination' => [
                'page_count' => ceil(count($items) / $pageSize),
                'page_size' => $pageSize,
                'page' => $page,
            ],
        ];

        return $this->json($data);
    }

    #[Route('/position', name: 'job_position_add', methods: ['POST'])]
    public function savePositionAddForm(
        Request $request,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $position = new JobPosition();

        $jobId = (int) $data['job'];
        $unitId = (int) $data['unit'];

        if ($jobId <= 0) {
            return $this->json([
                ['message' => 'Invalid Job ID']
            ], 400);
        }

        $job = $this->jobRepository->findById($jobId);

        $position->setJobId($jobId);
        $position->setComment($data['title']);
        $position->setAmount($data['amount']);

        $itemId = (int) $data['item'];
        if ($itemId > 0) {
            $item = $this->itemRepository->findById($itemId);
            if (!$item) {
                return $this->json([
                    ['message' => 'item not found']
                ], 404);
            }
            $unit = $this->itemUnitRepository->find($item->getIntField('unit_id'));
            $position->setItemId($itemId);
            $position->setUnit($unit);
        } else {
            if ($unitId <= 0) {
                return $this->json([
                    ['message' => 'Invalid Unit']
                ], 400);
            }
            $unit = $this->itemUnitRepository->find($unitId);
            $position->setUnit($unit);
            $position->setPrice($data['price']);
        }

        // save position
        $this->jobPositionRepository->save($position, true);

        $jobPositionsNew = $job->getJobPositions();
        $jobSubTotal = 0;
        foreach ($jobPositionsNew as $jobPosition) {
            $jobSubTotal += $jobPosition->getTotal();
        }
        $job->setPriceField('sub_total',$jobSubTotal);
        if ($job->getIntField('vat_mode') !== JobVatMode::VAT_NONE->value) {
            $job->setPriceField('vat_rate', $this->getParameter('job.vat_rate_default'));
            $jobVat = $jobSubTotal * ($this->getParameter('job.vat_rate_default') / 100);
            $job->setPriceField('vat_total', $jobVat);
            $job->setPriceField('total', $jobSubTotal + $jobVat);
        } else {
            $job->setPriceField('total', $jobSubTotal);
        }
        $this->jobRepository->save($job);

        return $this->json([
            'positions' => $jobPositionsNew,
            'job' => [
                'subTotal' => $job->getPriceField('sub_total'),
                'vatTotal' => $job->getPriceField('vat_total'),
                'vatRate' => $job->getPriceField('vat_rate'),
                'total' => $job->getPriceField('total'),
            ],
        ]);
    }

    private function listUnits(): array
    {
        $units = $this->itemUnitRepository->findAll();
        $unitsTranslated = [];

        foreach ($units as $unit) {
            $unitsTranslated[] = [
                'id' => $unit->getId(),
                'text' => $this->translator->trans($unit->getName()),
            ];
        }

        return $unitsTranslated;
    }

    protected function itemResponse(
        ?DynamicDto $dto,
        string $formKey = 'jobType1',
        ?DynamicType $form = null,
        array $extraData = []
    ): Response {
        return parent::itemResponse(
            $dto,
            'jobType1',
            $this->jobForm,
            $extraData
        );
    }
}