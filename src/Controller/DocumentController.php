<?php

namespace App\Controller;

use App\Dto\GenerateDocumentRequest;
use App\Entity\Document;
use App\Entity\DocumentTemplate;
use App\Form\Document\DocumentTemplateType;
use App\Repository\DocumentRepository;
use App\Repository\DocumentTemplateRepository;
use App\Repository\DocumentTypeRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\UserSettingRepository;
use App\Service\Document\DocumentGenerator;
use App\Service\Document\TemplateManager;
use DateTimeImmutable;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/document/{_locale}')]
class DocumentController extends AbstractApiController
{
    public function __construct(
        private readonly UserSettingRepository $userSettings,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentTemplateRepository $templateRepository,
        private readonly DocumentTypeRepository $documentTypeRepository,
        private readonly DocumentTemplateType $templateForm,
        private readonly DocumentGenerator $documentGenerator,
        private readonly HttpClientInterface $httpClient,
        private readonly TemplateManager $templateManager,
        private readonly DynamicFormFieldRepository $formFieldRepository,
        private readonly string $documentBaseDir,
    )
    {
        parent::__construct($this->httpClient);
    }

    private function getDynamicPlaceHolders(string $formKey): array
    {
        $contactDynamicPlacerholders = [];
        $contactFormFields = $this->formFieldRepository->getUserFieldsByFormKey($formKey);
        foreach ($contactFormFields as $field) {
            if ($field->getFieldType() === 'hidden' ||$field->getFieldType() === 'table') {
                continue;
            }
            $contactDynamicPlacerholders[] = [
                'key' => '${'.substr($formKey, 0, 1) .'_' . $field->getFieldKey() . '}',
                'description' => $field->getLabel(),
            ];
        }

        return $contactDynamicPlacerholders;
    }

    #[Route('/add', name: 'document_template_add', methods: ['GET'])]
    public function getAddForm(): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->json([
            'form' => $this->templateForm->getFormFields(),
            'sections' => $this->templateForm->getFormSections(),
            'document_placeholders' => [
                [
                    'name' => 'Allgemeine Platzhalter',
                    'key' => 'general',
                    'placeholders' => [
                        ['key' => '${date}', 'description' => 'Aktuelles Datum, formatiert in TT.MM.JJJJ'],
                        ['key' => '${userName}', 'description' => 'Name des aktuellen Benutzers'],
                        ['key' => '${userTitle}', 'description' => 'Funktion des aktuellen Benutzers'],
                    ]
                ],
                [
                    'name' => 'Kundendokumente',
                    'key' => 'general',
                    'placeholders' => [
                        ['key' => '${salution}', 'description' => 'Anrede mit Namen des Kontaktes'],
                        ['key' => '${address}', 'description' => 'Adresse des Kontaktes'],
                        ...$this->getDynamicPlaceHolders('contact'),
                        ...$this->getDynamicPlaceHolders('contactAddress')
                    ]
                ],
                [
                    'name' => 'Artikeldokumente',
                    'key' => 'general',
                    'placeholders' => [
                        ...$this->getDynamicPlaceHolders('item')
                    ]
                ],
                [
                    'name' => 'Auftragsdokumente - Allgemein',
                    'key' => 'general',
                    'placeholders' => [
                        ['key' => '${id}', 'description' => 'Auftrags-Nr'],
                        ['key' => '${address}', 'description' => 'Adresse des Kunden'],
                        ['key' => '${subTotal}', 'description' => 'Sub-Total des Auftrags (ohne Steuern/Zulagen)'],
                        ['key' => '${total}', 'description' => 'Total des Auftrags (inkl. Steuern/Zulagen)'],
                        ...$this->getDynamicPlaceHolders('contact'),
                        ...$this->getDynamicPlaceHolders('contactAddress')
                    ]
                ],
                [
                    'name' => 'Auftragsdokumente - Auftrags-Positionen (Zur Verwendung in einer Tabelle)',
                    'key' => 'general',
                    'placeholders' => [
                        ['key' => '${posAm}', 'description' => 'Anzahl der Position'],
                        ['key' => '${posItem}', 'description' => 'Name der Position'],
                        ['key' => '${posPrice}', 'description' => 'Einzelpreis der Position'],
                        ['key' => '${posTotal}', 'description' => 'Total der Position'],
                    ]
                ]
            ]
        ]);
    }

    #[Route('/add', name: 'document_template_add_save', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $docType = $this->documentTypeRepository->find($data['document']['type']);

        $template = new DocumentTemplate();
        $template->setName($data['document']['name']);
        $template->setType($docType);
        $template = $this->templateRepository->save($template, true);

        $templateContent = base64_decode($data['file']);
        $templateSavePath = $this->documentBaseDir . '/templates/' . $template->getId() . '.docx';
        file_put_contents($templateSavePath, $templateContent);

        return $this->itemResponse($template);
    }

    #[Route('/generate', name: 'document_generate', methods: ['POST'])]
    public function generate(
        #[MapRequestPayload] GenerateDocumentRequest $generateDocumentRequest,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $template = $this->templateRepository->findOneBy([
            'id' => $generateDocumentRequest->getDocumentId(),
        ]);

        if (!$template) {
            throw new HttpException(404, 'Template not found');
        }

        $documentType = $this->documentTypeRepository->findOneBy([
            'identifier' => $generateDocumentRequest->getEntityType(),
        ]);
        if (!$documentType) {
            throw new NotFoundHttpException('invalid document type');
        }

        $document = new Document();
        $document->setEntityId($generateDocumentRequest->getEntityId());
        $document->setType($documentType);
        $document->setFileName('#temp#');
        $document->setTemplate($template);
        $document->setCreatedDate(new DateTimeImmutable());
        $document->setCreatedBy($this->getUser()->getId());

        $document = $this->documentRepository->save($document, true);

        $fileName = match($documentType->getIdentifier()) {
            'contact' => $this->documentGenerator->generateContactDocument(
                $template,
                $document,
                $generateDocumentRequest->getEntityId(),
                $generateDocumentRequest->getAddressId(),
            ),
            'job' => $this->documentGenerator->generateJobDocument(
                $template,
                $document,
                $generateDocumentRequest->getEntityId(),
            ),
        };

        $document->setFileName($fileName);
        $document = $this->documentRepository->save($document, true);

        return $this->json([
            'document' => $this->documentGenerator->getDocumentAsBase64Download($document),
            'name' => $document->getFileName(),
        ]);
    }

    #[Route('/edit/{id}', name: 'document_template_edit', methods: ['GET'])]
    public function getEditForm(DocumentTemplate $template): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($template);
    }

    #[Route('/edit/{id}', name: 'document_template_edit_save', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        DocumentTemplate $template,
        Filesystem $filesystem,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        $template->setName($data['document']['name']);
        $template = $this->templateRepository->save($template, true);

        if (array_key_exists('file', $data) && $data['file'] !== '') {
            $templateContent = base64_decode($data['file']);
            $templateFile = $this->documentBaseDir . '/templates/' . $template->getId() . '.docx';
            if ($filesystem->exists($templateFile)) {
                $filesystem->remove($templateFile);
            }
            file_put_contents($templateFile, $templateContent);
        }

        return $this->itemResponse($template);
    }

    #[Route('/download/{id}', name: 'document_download', methods: ['GET'])]
    public function download(
        Document $document,
    ) : Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->json([
            'document' => $this->documentGenerator->getDocumentAsBase64Download($document),
            'name' => $document->getFileName(),
        ]);
    }

    #[Route('/download-template/{id}', name: 'document_template_download', methods: ['GET'])]
    public function downloadTemplate(
        DocumentTemplate $template,
    ) : Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->json([
            'document' => $this->templateManager->getTemplateAsBase64Download($template),
            'name' => $template->getName() . '.docx',
        ]);
    }

    #[Route('/list', name: 'document_index', methods: ['GET'])]
    #[Route('/list/{page}', name: 'document_index_with_pagination', methods: ['GET'])]
    #[Route('/list/{page}/{filter}', name: 'document_index_with_filter', methods: ['GET'])]
    public function list(
        ?int $page,
        ?string $filter,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $pageSize = $this->userSettings->getUserSetting(
            $this->getUser(),
            'pagination-page-size',
        );
        $page = $page ?? 1;
        $templates = $this->templateRepository->findBySearchAttributes($page, $pageSize, $filter);

        $data = [
            'filter' => $filter,
            'headers' => $this->templateForm->getIndexHeaders(),
            'items' => $templates,
            'total_items' => count($templates),
            'pagination' => [
                'page_count' => ceil(count($templates) / $pageSize),
                'page_size' => $pageSize,
                'page' => $page,
            ],
        ];

        return $this->json($data, 200);
    }

    #[Route('/remove/{id}', name: 'document_template_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(
        DocumentTemplate $template,
        Filesystem $filesystem,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $templateFile = $this->documentBaseDir . '/templates/' . $template->getId() . '.docx';
        if ($filesystem->exists($templateFile)) {
            $filesystem->remove($templateFile);
        }

        $this->templateRepository->remove($template, true);

        return $this->json(['state' => 'success']);
    }

    #[Route('/view/{id}', name: 'document_template_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    public function view(
        DocumentTemplate $template,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($template);
    }

    private function itemResponse(
        DocumentTemplate $template,
    ): Response {
        $template->setTemplate($this->templateManager->getTemplateAsBase64Download($template));
        $template->setFileName($template->getName() . '.docx');

        $data = [
            'item' => $template,
            'form' => $this->templateForm->getFormFields(),
            'sections' => $this->templateForm->getFormSections(),
        ];

        return $this->json($data);
    }
}