<?php

namespace App\Service;

use App\Form\DynamicType;
use App\Model\DynamicDto;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class DataExporter
{
    public function __construct(
        private TranslatorInterface $translator,
        private string $documentBaseDir,
        private Security $security,
    ) {
    }

    /**
     * @param DynamicDto[] $data
     */
    public function getExcelExport(DynamicType $form, array $data): ?string
    {
        $spreadsheet = new Spreadsheet();

        // set document information
        $spreadsheet->getProperties()
            ->setTitle('Export')
            ->setCreator($this->security->getUser()->getUserIdentifier())
            ->setLastModifiedBy($this->security->getUser()->getUserIdentifier())
        ;

        // select first sheet
        $spreadsheet->setActiveSheetIndex(0);

        $formFields = $form->getFormFields();

        $this->printExcelHeaderRow($spreadsheet, $formFields);

        $this->printExcelDataRows($spreadsheet, $formFields, $data);

        $spreadsheet->getActiveSheet()->setTitle('Export');

        $filename =  '/item/export' . $this->security->getUser()->getId() . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($this->documentBaseDir . $filename);

        return $filename;
    }

    /**
     * @param array $formFields
     */
    private function printExcelHeaderRow(Spreadsheet $spreadsheet, array $formFields): void
    {
        $column = 'A';
        foreach ($formFields as $field) {
            $spreadsheet->getActiveSheet()->getStyle($column . '1')->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],

            ]);
            $spreadsheet->getActiveSheet()->setCellValue($column . '1', $field['text']);
            $column++;
        }
    }

    /**
     * @param array $formFields
     * @param DynamicDto[] $data
     */
    private function printExcelDataRows(Spreadsheet $spreadsheet, array $formFields, array $data): void
    {
        $row = 2;
        foreach ($data as $dataDynamicDto) {
            $dataDynamicDto->serializeDataForApiByFormModel('item');
            $serializedData = $dataDynamicDto->getDataSerialized();

            $column = 'A';
            foreach ($formFields as $field) {
                $cellValue = array_key_exists($field['key'] . '_text', $serializedData)
                    ? $serializedData[$field['key'] . '_text']
                    : $serializedData[$field['key']]
                ;
                if (is_array($cellValue)) {
                    $cellValue = $this->translator->trans($cellValue['name']);
                }
                $spreadsheet->getActiveSheet()->setCellValue($column . $row, $cellValue);
                $column++;
            }
            $row++;
        }
    }

}