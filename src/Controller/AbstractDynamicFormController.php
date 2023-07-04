<?php

namespace App\Controller;

use App\Form\DynamicType;
use App\Model\DynamicDto;
use App\Repository\AbstractRepository;
use App\Repository\UserSettingRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AbstractDynamicFormController extends AbstractApiController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UserSettingRepository $userSettingRepository,
        private readonly DynamicDto $dynamicDto,
    ) {
        parent::__construct($this->httpClient);
    }

    protected function getAddForm(?DynamicType $form = null, string $formKey = ''): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        if (!$form) {
            throw new HttpException(404, 'form not found found');
        }

        return $this->json([
            'form' => $form->getFormFields($formKey),
            'sections' => $form->getFormSections($formKey),
        ]);
    }

    protected function getAddFormCompany(?DynamicType $form = null, string $formKey = ''): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->json([
            'form' => $form->getFormFields($formKey),
            'sections' => $form->getFormSections($formKey),
        ]);
    }

    protected function list(
        ?int $page,
        AbstractRepository $repository,
        DynamicType $form,
        string $formKey,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $pageSize = $this->userSettingRepository->getUserSetting(
            $this->getUser(),
            'pagination-page-size',
        );
        $page = $page ?? 1;
        $items = $repository->findBySearchAttributes($page, $pageSize);

        $itemsApi = [];
        foreach ($items as $itemRaw) {
            $itemApi = $this->dynamicDto;
            $itemApi->setData($itemRaw);
            $itemApi->serializeDataForApiByFormModel($formKey);
            $itemsApi[] = $itemApi->getDataSerialized();
        }

        $data = [
            'headers' => $form->getIndexHeaders($formKey),
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

    protected function view(
        int $entityId,
        AbstractRepository $repository,
        string $formKey,
        DynamicType $form,
    ): Response {
        $entity = $repository->findById($formKey, $entityId);
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($entity, $formKey, $form);
    }

    protected function itemResponse(
        ?DynamicDto $dto,
        string $formKey,
        DynamicType $form,
    ): Response {
        if (!$dto) {
            return $this->json(['message' => 'contact not found'], 404);
        }

        $dto->serializeDataForApiByFormModel($formKey);

        $data = [
            'item' => $dto->getDataSerialized(),
            'form' => $form->getFormFields($formKey),
            'sections' => $form->getFormSections($formKey),
        ];

        return $this->json($data);
    }
}