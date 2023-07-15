<?php

namespace App\Service\Document;

use App\Entity\Document;
use App\Entity\DocumentTemplate;
use App\Model\DynamicDto;
use App\Repository\ContactAddressRepository;
use App\Repository\ContactRepository;
use App\Repository\DynamicFormFieldRepository;
use App\Repository\JobRepository;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class DocumentGenerator
{
    public function __construct(
        private readonly ContactRepository $contactRepository,
        private readonly ContactAddressRepository $addressRepository,
        private readonly JobRepository $jobRepository,
        private readonly Security $security,
        private readonly TranslatorInterface$translator,
        private readonly DynamicFormFieldRepository $formFieldRepository,
        private readonly string $documentBaseDir,
    ) {
    }

    public function generateContactDocument(
        DocumentTemplate $template,
        Document $document,
        int $contactId,
    ): string {
        $contact = $this->contactRepository->findById($contactId);

        $name = $contact->getBoolField('is_company')
            ? $contact->getTextField('company_name')
            : $this->translator->trans($contact->getSelectField('salution_id')['name'])
                . ' '. $contact->getTextField('first_name')
                . ' ' . $contact->getTextField('last_name')
        ;

        $nameForFile = $contact->getBoolField('is_company')
            ? $contact->getTextField('company_name')
            : $contact->getTextField('last_name')
        ;

        $fileName = $template->getName() . '-' . $nameForFile . '-' . $document->getId() . '.docx';
        $templateProcessor = new TemplateProcessor(
            $this->documentBaseDir
            . '/templates/'
            . $template->getId()
            . '.docx'
        );

        $primaryAddress = $this->addressRepository->getPrimaryAddressForContact($contactId);

        if ($primaryAddress) {
            $title = new TextRun();
            $title->addText($name);
            $title->addTextBreak();
            $title->addText($primaryAddress->getTextField('street'));
            $title->addTextBreak();
            $title->addText(
                $primaryAddress->getTextField('zip')
                . ' ' . $primaryAddress->getTextField('city')
            );
            $templateProcessor->setComplexBlock('address', $title);
        } else {
            $templateProcessor->setValue('address', '');
        }

        $templateProcessor->setValue('salution', 'Grüezi ' . $name);
        $templateProcessor->setValue('userName', $this->security->getUser()->getName());
        $templateProcessor->setValue('userTitle', $this->security->getUser()->getFunction());
        $templateProcessor->setValue('date', date('d.m.Y', time()));

        $this->replaceDynamicPlaceHolders('contact', $templateProcessor, $contact);
        $this->replaceDynamicPlaceHolders('contactAddress', $templateProcessor, $contact);

        $templateProcessor->saveAs(
            $this->documentBaseDir
            . '/' . $template->getType()->getIdentifier()
            . '/' . $fileName
        );

        return $fileName;
    }

    public function generateJobDocument(
        DocumentTemplate $template,
        Document $document,
        int $jobId,
    ): string
    {
        $job = $this->jobRepository->findById($jobId);

        $fileName = $template->getName() . '-' . $job->getId() . '-' . $document->getId() . '.docx';
        $templateProcessor = new TemplateProcessor($this->documentBaseDir . '/templates/' . $template->getId() . '.docx');

        $contact = $job->getIntField('contact_id')
            ? $this->contactRepository->findById($job->getIntField('contact_id'))
            : null
        ;

        /**
         * Add Contact Address
         */
        $name = '';
        if ($contact) {
            if ($contact->getBoolField('is_company')) {
                $name = $contact->getTextField('company_name');
            } else {
                if ($contact->getTextField('first_name')) {
                    $name = $contact->getTextField('first_name');
                }
                if ($contact->getTextField('last_name')) {
                    if ($name !== '') {
                        $name .= ' ';
                    }
                    $name .= $contact->getTextField('last_name');
                }
            }
        }

        $primaryAddress = $this->addressRepository->getPrimaryAddressForContact($contact->getId());
        if ($primaryAddress) {
            $title = new TextRun();
            $title->addText($name);
            $title->addTextBreak();
            $title->addText($primaryAddress->getTextField('street'));
            $title->addTextBreak();
            $title->addText(
                $primaryAddress->getTextField('zip')
                . ' '
                . $primaryAddress->getTextField('city')
            );
            $templateProcessor->setComplexBlock('address', $title);
        } else {
            $templateProcessor->setValue('address', '');
        }


        /**
         * Add Job Positions
         */
        $jobPositions = $job->getJobPositions(false);
        $templateProcessor->cloneRow('posItem', count($jobPositions));

        $jobVouchersUsed = $job->getVouchersUsed();
        $templateProcessor->cloneRow('sPosItem', count($jobVouchersUsed));

        $row = 1;
        foreach ($jobVouchersUsed as $voucherUsed) {
            $templateProcessor->setValue('sPosItem#' . $row, $voucherUsed['name']);
            $templateProcessor->setValue('sPosAmount#' . $row, '-' . $voucherUsed['amount_text']);

            $row++;
        }

        $row = 1;
        $jobSubTotal = 0;
        foreach ($jobPositions as $jobPosition) {
            $posDescription = $jobPosition->getItem()
                ? $jobPosition->getItem()['name']
                    . ($jobPosition->getComment() ? ' ' . $jobPosition->getComment() : '')
                : $jobPosition->getComment();

            $title = new TextRun();
            $title->addText($posDescription);

            if ($jobPosition->getVoucherCodes()) {
                $title->addTextBreak();

                $codesText = 'Code: ';
                if (count($jobPosition->getVoucherCodes()) >1) {
                    $codesText = 'Codes: ';
                }
                foreach ($jobPosition->getVoucherCodes() as $code) {
                    $codesText .= $code . ', ';
                }
                $title->addText($codesText);
            }

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
           // $templateProcessor->setValue('posItem#' . $row, $posDescription);
            $templateProcessor->setComplexBlock('posItem#' . $row, $title);
            $templateProcessor->setValue('posAm#' . $row, $jobPosition->getAmount());
            $templateProcessor->setValue('posPrice#' . $row, $posPriceView);
            $templateProcessor->setValue('posTotal#' . $row, $posTotalView);

            $row++;
        }

        /**
         * Add Job Basic Data
         */
        $templateProcessor->setValue('id', $job->getId());
        $templateProcessor->setValue(
            'j_date',
            $job->getTextField('date')
                    ? date('d.m.Y', strtotime($job->getTextField('date')))
                    : ''
        );
        $templateProcessor->setValue('subTotal', number_format($job->getPriceField('sub_total'), 2, '.', '\''));
        $templateProcessor->setValue('total', number_format($job->getPriceField('total'), 2, '.', '\''));
        $templateProcessor->setValue('date', date('d.m.Y', time()));

        $templateProcessor->saveAs($this->documentBaseDir . '/' . $template->getType()->getIdentifier() . '/' . $fileName);

        return $fileName;
    }

    public function getDocumentAsBase64Download(
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

    private function replaceDynamicPlaceHolders(string $formKey, TemplateProcessor &$templateProcessor, DynamicDto $data): void
    {
        $contactFormFields = $this->formFieldRepository->getUserFieldsByFormKey($formKey);
        foreach ($contactFormFields as $field) {
            if ($field->getFieldType() === 'hidden') {
                continue;
            }
            $fieldValue = match ($field->getFieldType()) {
                'date' => date('d.m.Y', strtotime($data->getTextField($field->getFieldKey()))),
                default => $data->getTextField($field->getFieldKey())
            };
            $templateProcessor->setValue(substr($formKey,0,1). '_' . $field->getFieldKey(), $fieldValue);
        }
    }

}