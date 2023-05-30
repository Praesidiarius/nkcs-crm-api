<?php

namespace App\Service\Document;

use App\Entity\Document;
use App\Repository\ContactRepository;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Bundle\SecurityBundle\Security;

class DocumentGenerator
{
    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly Security $security,
    )
    {
    }

    public function generateContactDocument(Document $template, int $contactId): string
    {
        $contact = $this->contactRepository->find($contactId);
        $fileName = '/data/documents/'.$template->getName().'-'.$contact->getLastName().'.docx';

        $templateProcessor = new TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/data/templates/' . $template->getId() . '.docx');

        $primaryAddress = $contact->getAddress()->first();

        $name = $contact->getFirstName()
            ? $contact->getFirstName() . ' ' . $contact->getLastName()
            : $contact->getLastName();

        $title = new TextRun();
        $title->addText($name);
        $title->addTextBreak();
        $title->addText($primaryAddress->getStreet());
        $title->addTextBreak();
        $title->addText($primaryAddress->getZip() . ' ' . $primaryAddress->getCity());
        $templateProcessor->setComplexBlock('address', $title);

        $templateProcessor->setValue('salution', 'Grüezi ' . $name);
        $templateProcessor->setValue('userName', $this->security->getUser()->getUserIdentifier());
        $templateProcessor->setValue('userTitle', 'Geschäftsführer');
        $templateProcessor->setValue('date', date('d.m.Y', time()));

        $templateProcessor->saveAs($_SERVER['DOCUMENT_ROOT'] . $fileName);

        return $fileName;
    }
}