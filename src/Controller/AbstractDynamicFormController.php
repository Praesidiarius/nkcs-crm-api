<?php

namespace App\Controller;

use App\Form\DynamicType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AbstractDynamicFormController extends AbstractApiController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
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
}