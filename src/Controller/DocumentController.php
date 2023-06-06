<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Document;
use App\Form\Document\DocumentType;
use App\Repository\DocumentRepository;
use App\Repository\DocumentTypeRepository;
use App\Repository\UserSettingRepository;
use App\Service\Document\DocumentGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/document')]
class DocumentController extends AbstractController
{
    public function __construct(
        private readonly UserSettingRepository $userSettings,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentTypeRepository $documentTypeRepository,
        private readonly DocumentType $documentForm,
        private readonly DocumentGenerator $documentGenerator,
        private readonly string $documentBaseDir,
    )
    {
    }

    #[Route('/add', name: 'document_add', methods: ['GET'])]
    #[Route('/add/{_locale}', name: 'document_add_translated', methods: ['GET'])]
    public function getAddForm(): Response {

        return $this->json([
            'form' => $this->documentForm->getFormFields(),
            'sections' => $this->documentForm->getFormSections(),
        ]);
    }

    #[Route('/add', name: 'document_add_save', methods: ['POST'])]
    #[Route('/add/{_locale}', name: 'document_add_save_translated', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        $body = $request->getContent();
        $data = json_decode($body, true);

        $docType = $this->documentTypeRepository->find($data['document']['type']);

        $document = new Document();
        $document->setName($data['document']['name']);
        $document->setType($docType);
        $document = $this->documentRepository->save($document, true);

        $template = base64_decode($data['file']);
        file_put_contents($this->documentBaseDir . '/templates/' . $document->getId() . '.docx', $template);

        return $this->itemResponse($document);
    }

    #[Route('/generate/{id}/{entityId}/{entityType}', name: 'document_generate', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[Route('/generate/{_locale}/{id}/{entityId}/{entityType}', name: 'document_generate_translated', methods: ['GET'])]
    public function view(
        Document $document,
        int $entityId,
        string $entityType,
    ): Response {
        $fileName = match($entityType) {
            'contact' => $this->documentGenerator->generateContactDocument($document, $entityId),
            'job' => $this->documentGenerator->generateJobDocument($document, $entityId),
            default => throw new NotFoundHttpException('invalid document type')
        };

        return $this->json([
            'file' => 'http://' . $_SERVER['HTTP_HOST']. $fileName
        ]);
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
        $documents = $this->documentRepository->findBySearchAttributes($page, $pageSize, $request->query->getAlpha('type'));

        $data = [
            'headers' => $this->documentForm->getIndexHeaders(),
            'items' => $documents,
            'total_items' => count($documents),
            'pagination' => [
                'page_size' => $pageSize,
                'page' => $page,
            ],
        ];

        return $this->json($data, 200);
    }

    private function itemResponse(
        Document $document,
    ): Response {
        $data = [
            'item' => $document,
            'form' => $this->documentForm->getFormFields(),
            'sections' => $this->documentForm->getFormSections(),
        ];

        return $this->json($data);
    }
}