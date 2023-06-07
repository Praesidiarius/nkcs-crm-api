<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\DocumentTemplate;
use App\Form\Document\DocumentTemplateType;
use App\Repository\DocumentRepository;
use App\Repository\DocumentTemplateRepository;
use App\Repository\DocumentTypeRepository;
use App\Repository\UserSettingRepository;
use App\Service\Document\DocumentGenerator;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/document')]
class DocumentController extends AbstractController
{
    public function __construct(
        private readonly UserSettingRepository $userSettings,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentTemplateRepository $templateRepository,
        private readonly DocumentTypeRepository $documentTypeRepository,
        private readonly DocumentTemplateType $templateForm,
        private readonly DocumentGenerator $documentGenerator,
        private readonly string $documentBaseDir,
    )
    {
    }

    #[Route('/add', name: 'document_template_add', methods: ['GET'])]
    #[Route('/add/{_locale}', name: 'document_template_add_translated', methods: ['GET'])]
    public function getAddForm(): Response {
        return $this->json([
            'form' => $this->templateForm->getFormFields(),
            'sections' => $this->templateForm->getFormSections(),
        ]);
    }

    #[Route('/add', name: 'document_template_add_save', methods: ['POST'])]
    #[Route('/add/{_locale}', name: 'document_template_add_save_translated', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
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

    #[Route('/generate/{id}/{entityId}/{entityType}', name: 'document_generate', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[Route('/generate/{_locale}/{id}/{entityId}/{entityType}', name: 'document_generate_translated', methods: ['GET'])]
    public function generate(
        DocumentTemplate $template,
        int $entityId,
        string $entityType,
    ): Response {
        $documentType = $this->documentTypeRepository->findOneBy(['identifier' => $entityType]);
        if (!$documentType) {
            throw new NotFoundHttpException('invalid document type');
        }

        $document = new Document();
        $document->setEntityId($entityId);
        $document->setType($documentType);
        $document->setFileName('#temp#');
        $document->setTemplate($template);
        $document->setCreatedDate(new DateTimeImmutable());
        $document->setCreatedBy($this->getUser()->getId());

        $document = $this->documentRepository->save($document, true);

        $fileName = match($documentType->getIdentifier()) {
            'contact' => $this->documentGenerator->generateContactDocument($template, $document, $entityId),
            'job' => $this->documentGenerator->generateJobDocument($template, $entityId),
        };

        $document->setFileName($fileName);
        $document = $this->documentRepository->save($document, true);

        return $this->json([
            'document' => $this->getDocumentAsBase64Download($document),
            'name' => $document->getFileName(),
        ]);
    }

    #[Route('/download/{id}', name: 'document_download', methods: ['GET'])]
    public function download(
        Document $document,
    ) : Response {
        return $this->json([
            'document' => $this->getDocumentAsBase64Download($document),
            'name' => $document->getFileName(),
        ]);
    }

    private function getDocumentAsBase64Download(
        Document $document
    ) : string {
        $fileContent = file_get_contents(
            $this->documentBaseDir
            . '/'
            . $document->getType()->getIdentifier()
            . '/'
            . $document->getFileName()
        );

        return base64_encode($fileContent);
    }

    #[Route('/', name: 'document_index', methods: ['GET'])]
    #[Route('/{_locale}', name: 'document_index_translated', methods: ['GET'])]
    public function list(
        Request $request,
    ): Response {
        $pageSize = $this->userSettings->getUserSetting(
            $this->getUser(),
            'pagination-page-size',
        );
        $page = $request->query->getInt('page', 1);
        $templates = $this->templateRepository->findBySearchAttributes($page, $pageSize, $request->query->getAlpha('type'));

        $data = [
            'headers' => $this->templateForm->getIndexHeaders(),
            'items' => $templates,
            'total_items' => count($templates),
            'pagination' => [
                'page_size' => $pageSize,
                'page' => $page,
            ],
        ];

        return $this->json($data, 200);
    }

    private function itemResponse(
        DocumentTemplate $template,
    ): Response {
        $data = [
            'item' => $template,
            'form' => $this->templateForm->getFormFields(),
            'sections' => $this->templateForm->getFormSections(),
        ];

        return $this->json($data);
    }
}