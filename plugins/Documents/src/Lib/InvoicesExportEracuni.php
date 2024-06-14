<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\Core\Configure;
use Cake\I18n\I18n;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class InvoicesExportEracuni
{
    /**
     * Do a data export
     *
     * @param mixed $data Data
     * @return void
     */
    public function export(mixed $data): void
    {
        Settings::setLocale(I18n::getLocale());
            //if (!$validLocale) {
            //    return __('Unable to set locale.');
            //}

            $objPHPExcel = new Spreadsheet();

            // defaults
            //$objPHPExcel->getDefaultStyle()->getFont()->setSize(9);

            // data
            //$objPHPExcel->getProperties()->setCreator(__('www.arhint.com'));
            //$objPHPExcel->getProperties()->setLastModifiedBy('BIMquants');
            //$objPHPExcel->getProperties()->setTitle($project->title);
            //$objPHPExcel->getProperties()->setSubject(__('Building quantities and costs'));
            //$objPHPExcel->getProperties()->setDescription($project->descript);

            // write first page
            $objPHPExcel->setActiveSheetIndex(0);

            $activeSheet = $objPHPExcel->getActiveSheet();

            // header and footer
            $activeSheet->getHeaderFooter()->setOddFooter(
                '&L' . $objPHPExcel->getProperties()->getTitle() . '&R' . __('documents', 'Page {0} of {1}', '&P', '&N')
            );
            $activeSheet->getHeaderFooter()->setEvenFooter(
                '&L' . $objPHPExcel->getProperties()->getTitle() . '&R' . __('documents', 'Page {0} of {1}', '&P', '&N')
            );

            // title
           //$activeSheet->setTitle(__('Recapitulation'));

            // project
            $activeSheet->SetCellValue('A1', 'Tip');
            $activeSheet->SetCellValue('B1', 'Številka');
            $activeSheet->SetCellValue('C1', 'Datum');
            $activeSheet->SetCellValue('D1', 'Datum storitve');
            $activeSheet->SetCellValue('E1', 'do');
            $activeSheet->SetCellValue('F1', 'Rok plačila');
            $activeSheet->SetCellValue('G1', 'Valuta');
            $activeSheet->SetCellValue('H1', 'Način plačila');
            $activeSheet->SetCellValue('I1', 'Skupaj z davkom');

            $activeSheet->SetCellValue('J1', 'Vrsta DDV');
            $activeSheet->SetCellValue('K1', 'Naziv');
            $activeSheet->SetCellValue('L1', 'Naslov');
            $activeSheet->SetCellValue('M1', 'Poštna št.');
            $activeSheet->SetCellValue('N1', 'Mesto');
            $activeSheet->SetCellValue('O1', 'Davčna št.');
            $activeSheet->SetCellValue('P1', 'Država');

            $documentTypes = Configure::read('Documents.documentTypes');
            $pmtKinds = [0 => 'Nakazilo', 1 => 'Samodejno', 2 => 'Placano', 3 => 'BrezPlacila'];

            $taxExcelHeaders = [];
        $taxExcelHeadersLastIndex = 17;

            $i = 2;
        foreach ($data as $doc) {
            $activeSheet->setCellValueExplicit(
                'A' . $i,
                strtolower($documentTypes[$doc->documents_counter->doc_type]),
                DataType::TYPE_STRING
            );

            $activeSheet->setCellValueExplicit('B' . $i, $doc->no, DataType::TYPE_STRING);

            $activeSheet->getStyle('C' . $i)->getNumberFormat()->setFormatCode('d.m.yyyy');
            $activeSheet->SetCellValue('C' . $i, Date::PHPToExcel($doc->dat_issue->toUnixString()));

            $activeSheet->getStyle('D' . $i)->getNumberFormat()->setFormatCode('d.m.yyyy');
            $activeSheet->SetCellValue('D' . $i, Date::PHPToExcel($doc->dat_service->toUnixString()));

            $activeSheet->getStyle('F' . $i)->getNumberFormat()->setFormatCode('d.m.yyyy');
            $activeSheet->SetCellValue('F' . $i, Date::PHPToExcel($doc->dat_expire->toUnixString()));

            $activeSheet->SetCellValue('G' . $i, 'EUR');
            $activeSheet->SetCellValue('H' . $i, $pmtKinds[$doc->pmt_kind]);
            $activeSheet->SetCellValue('I' . $i, $doc->total);

            $activeSheet->SetCellValue('J' . $i, 0);

            $client = $doc->documents_counter->direction == 'issued' ? $doc->receiver : $doc->issuer;

            $activeSheet->setCellValueExplicit('K' . $i, $client->title, DataType::TYPE_STRING);
            $activeSheet->setCellValueExplicit('L' . $i, $client->street, DataType::TYPE_STRING);
            $activeSheet->setCellValueExplicit('M' . $i, $client->zip, DataType::TYPE_STRING);
            $activeSheet->setCellValueExplicit('N' . $i, $client->city, DataType::TYPE_STRING);
            $activeSheet->setCellValueExplicit('O' . $i, $client->tax_no, DataType::TYPE_STRING);
            $activeSheet->setCellValueExplicit(
                'P' . $i,
                empty($client->country_code) ? 'SI' : $client->country_code,
                DataType::TYPE_STRING
            );

            if ($doc->documents_counter->direction == 'issued' && !empty($doc->invoices_items)) {
                $tax_spec = [];
                foreach ($doc->invoices_items as $item) {
                    if (!isset($tax_spec[$item->vat_id])) {
                        $tax_spec[$item->vat_id] = ['title' => '', 'base' => 0, 'amount' => 0, 'percent' => 0];
                    }

                    $tax_spec[$item->vat_id]['title'] = $item->vat_title;
                    $tax_spec[$item->vat_id]['base'] += $item->net_total;
                    $tax_spec[$item->vat_id]['amount'] += $item->tax_total;
                    $tax_spec[$item->vat_id]['percent'] = $item->vat_percent;
                }

                foreach ($tax_spec as $vat_id => $tax) {
                    if (!isset($taxExcelHeaders[$vat_id])) {
                        // headers/columns for specified taxid dont exist yet
                        $taxExcelHeaders[$vat_id] = $taxExcelHeadersLastIndex;
                        $activeSheet->SetCellValue([$taxExcelHeaders[$vat_id], 1], $tax['title']);
                        $activeSheet->SetCellValue([$taxExcelHeaders[$vat_id] + 1, 1], 'Osnova za ' . $tax['title']);
                        $activeSheet->SetCellValue([$taxExcelHeaders[$vat_id] + 2, 1], 'Stopnja za ' . $tax['title']);

                        $taxExcelHeadersLastIndex += 3;
                    }

                    $activeSheet->SetCellValue([$taxExcelHeaders[$vat_id], $i], $tax['amount']);
                    $activeSheet->SetCellValue([$taxExcelHeaders[$vat_id] + 1, $i], $tax['base']);
                    $activeSheet->SetCellValue([$taxExcelHeaders[$vat_id] + 2, $i], $tax['percent']);
                }
            }

            if ($doc->documents_counter->direction == 'received' && !empty($doc->invoices_taxes)) {
                foreach ($doc->invoices_taxes as $itm) {
                    if (!isset($taxExcelHeaders[$itm->vat_id])) {
                        // headers/columns for specified taxid dont exist yet
                        $taxExcelHeaders[$itm->vat_id] = $taxExcelHeadersLastIndex;
                        $activeSheet->SetCellValue([$taxExcelHeaders[$itm->vat_id], 1], $itm->vat_title);
                        $activeSheet->SetCellValue(
                            [$taxExcelHeaders[$itm->vat_id] + 1, 1],
                            'Osnova za ' . $itm->vat_title
                        );
                        $activeSheet->SetCellValue(
                            [$taxExcelHeaders[$itm->vat_id] + 2, 1],
                            'Stopnja za ' . $itm->vat_title
                        );

                        $taxExcelHeadersLastIndex += 3;
                    }

                    $taxAmount = round($itm->base * $itm->vat_percent / 100, 2);
                    $activeSheet->SetCellValue([$taxExcelHeaders[$itm->vat_id], $i], $taxAmount);
                    $activeSheet->SetCellValue([$taxExcelHeaders[$itm->vat_id] + 1, $i], $itm->base);
                    $activeSheet->SetCellValue([$taxExcelHeaders[$itm->vat_id] + 2, $i], $itm->vat_percent);
                }
            }

            $i++;
        }

            $excelFile = 'eRacuniExport';

            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $excelFile . '.xlsx"');

            $writer = IOFactory::createWriter($objPHPExcel, 'Xlsx');
            $writer->save('php://output');

            $objPHPExcel->disconnectWorksheets();
            $objPHPExcel->garbageCollect();
            unset($objPHPExcel);
            die;
    }
}
