<?php

namespace App\Service\Document;

use App\Entity\Document;
use App\Entity\DocumentTemplate;

class TemplateManager
{
    public function __construct(
        private readonly string $documentBaseDir,
    ) {
    }

    public function getTemplateAsBase64Download(
        DocumentTemplate $template
    ) : string {
        $fileContent = file_get_contents(
            $this->documentBaseDir . '/templates/' . $template->getId() . '.docx'
        );

        return base64_encode($fileContent);
    }
}