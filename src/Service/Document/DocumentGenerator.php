<?php

namespace App\Service\Document;

use App\Entity\Document;
use App\Repository\ContactRepository;
use App\Repository\JobRepository;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Bundle\SecurityBundle\Security;

class DocumentGenerator
{
    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly JobRepository $jobRepository,
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

    public function generateJobDocument(Document $template, int $jobId): string
    {
        $job = $this->jobRepository->find($jobId);

        $fileName = '/data/documents/'.$template->getName().'-'.$job->getId().'.docx';
        $templateProcessor = new TemplateProcessor($_SERVER['DOCUMENT_ROOT'] . '/data/templates/' . $template->getId() . '.docx');

        $contact = $job->getContact();
        /**
         * Add Contact Address
         */
        $name = $contact->getFirstName()
            ? $contact->getFirstName() . ' ' . $contact->getLastName()
            : $contact->getLastName();

        $primaryAddress = $contact->getAddress()->first();
        $title = new TextRun();
        $title->addText($name);
        $title->addTextBreak();
        $title->addText($primaryAddress->getStreet());
        $title->addTextBreak();
        $title->addText($primaryAddress->getZip() . ' ' . $primaryAddress->getCity());
        $templateProcessor->setComplexBlock('address', $title);

        /**
         * Add Job Positions
         */
        $templateProcessor->cloneRow('posItem', count($job->getJobPositions()));

        $row = 1;
        $jobSubTotal = 0;
        foreach ($job->getJobPositions() as $jobPosition) {
            $posDescription = $jobPosition->getItem()
                ? $jobPosition->getItem()->getName()
                    . ($jobPosition->getComment() ? ' ' . $jobPosition->getComment() : '')
                : $jobPosition->getComment();

            $posTotal = round($jobPosition->getAmount() * $jobPosition->getPrice(), 2);
            $posTotalView = number_format($posTotal, 2, '.', '\'');
            if (fmod($posTotal, 1) === 0.0) {
                $posTotalView = number_format($posTotal, 0, '.', '\'') . '.-';
            }
            $jobSubTotal += $posTotal;

            $posPriceView = $jobPosition->getPrice();
            if (fmod($posPriceView, 1) === 0.0) {
                $posPriceView = number_format($jobPosition->getPrice(), 0, '.', '\'') . '.-';
            }
            $templateProcessor->setValue('posItem#' . $row, $posDescription);
            $templateProcessor->setValue('posAm#' . $row, $jobPosition->getAmount());
            $templateProcessor->setValue('posPrice#' . $row, $posPriceView);
            $templateProcessor->setValue('posTotal#' . $row, $posTotalView);

            $row++;
        }

        /**
         * Add Job Basic Data
         */
        $templateProcessor->setValue('id', $job->getId());
        $templateProcessor->setValue('subTotal', number_format($jobSubTotal, 2, '.', '\''));
        $templateProcessor->setValue('total', number_format($jobSubTotal, 2, '.', '\''));
        $templateProcessor->setValue('date', date('d.m.Y', time()));

        $templateProcessor->saveAs($_SERVER['DOCUMENT_ROOT'] . $fileName);

        return $fileName;
    }

}