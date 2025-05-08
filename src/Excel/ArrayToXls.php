<?php
namespace Osynapsy\Etl\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

/**
 * Description of XlsExport
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class ArrayToXls
{
    const TYPE_DATE = 'date';
    const TYPE_MONEY_EURO = 'euro';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_STRING = 'string';

    public static $CSV_DELIMITER = ';';
    public static $CSV_ENCLOSURE = '';
    protected $columns = [];
    protected $appendHead = true;
    protected $rowIndex = 0;
    protected $xls;

    public function __construct($title = 'Export', $subject = "Export", $creator = '')
    {
        $this->xls = $this->xlsFactory($title, $subject, $creator);
    }

    public function exportDataset($dataset, $filename, )
    {
        $this->beforeExportStart($this->xls, $dataset);
        $this->appendDatasetToCurrentSheet($this->xls, $dataset);
        $this->saveXlsObject($this->xls, $filename);
        $this->afterExportEnd($this->xls, $dataset);
        return $filename;
    }

    protected function xlsFactory($title, $subject, $creator)
    {
        $xls = new Spreadsheet();
        $xls->getProperties()->setCreator($creator);
        $xls->getProperties()->setLastModifiedBy($creator);
        $xls->getProperties()->setTitle($title);
        $xls->getProperties()->setSubject($subject);
        $xls->getProperties()->setDescription($subject);
        $xls->getActiveSheet()->setTitle($title);
        return $xls;
    }

    protected function appendDatasetToCurrentSheet($xls, $dataset)
    {
        if (!empty($dataset) && empty($this->columns)) {
            $this->columns = array_map(fn($columnLabel) => ['label' => $columnLabel, 'field' => $columnLabel, 'type' => 'string'], array_keys($dataset[0]));
        }
        $this->beforeHeadAppend($this->columns);
        if (!empty($this->appendHead) && !empty($this->columns)) {
            $this->appendHeadToSheet($xls, $this->columns);
            $this->rowIndex++;
        }
        $this->afterHeadAppend($this->columns);
        foreach ($dataset as $row) {
            $this->appendRowToSheet($xls, $this->rowIndex, $row);
            $this->rowIndex++;
        }
    }

    public function appendArrayToSheet(array $values)
    {
        foreach($values as $i => $v) {
            $cellAddress = $this->getCellAddress($i + 1, $this->rowIndex);
            $this->xls->getActiveSheet()->setCellValue($cellAddress, trim(strip_tags($v)));
        }
        $this->rowIndex++;
    }

    protected function appendHeadToSheet($xls, $columns)
    {

        $iColumn = 1;
        foreach($columns as $idx => $column) {
            $rawColumnLabel = $column['label'];
            if (is_string($rawColumnLabel) && $rawColumnLabel[0] === '_') {
                unset($this->columns[$idx]);
                continue;
            }
            $cellAddress = $this->getCellAddress($iColumn, 0);
            $columnLabel = str_replace(array('_X','!','$'),'', $rawColumnLabel);
            $xls->getActiveSheet()->SetCellValue($cellAddress, $columnLabel);
            $iColumn++;
        }
    }

    protected function appendRowToSheet($xls, $iRow, $row)
    {
        $this->beforeRowAppend($row);
        $iColumn = 1;
        foreach ($this->columns as $column) {
            $fieldName = $column['field'];
            $fieldValue = array_key_exists($fieldName, $row) ? $row[$fieldName] : $fieldName;
            $fieldType = $column['type'] ?? 'string';
            if (is_callable($fieldType)) {
                $fieldValue = $fieldType($fieldValue, $row);
            } elseif ($fieldType === 'money') {
                $fieldValue = number_format($fieldValue, '2', ',', '.');
            }
            $iColumn += $this->appendCellValue($xls, $fieldName, $fieldValue, $fieldType, $iRow, $iColumn);
        }
        $this->afterRowAppend($row);
    }

    protected function appendCellValue($xls, $fieldName, $rawFieldValue, $fieldType, $iRow, $iColumn)
    {
        if (is_string($fieldName) && $fieldName[0] == '_') {
            return 0;
        }
        $cellAddress = $this->getCellAddress($iColumn, $iRow);
        $fieldValue = trim(strip_tags($rawFieldValue));
        $sheet = $xls->getActiveSheet();
        $sheet->SetCellValue($cellAddress, $fieldValue);
        if (is_string($fieldName) && $fieldName[0] === '$' && is_numeric($fieldValue)) {
            $sheet->getStyle($cellAddress)->getNumberFormat()->setFormatCode('General;[Red]General');
        }
        switch($fieldType) {
            case 'euro':
                $sheet->getStyle($cellAddress)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR);
                break;
            case 'numeric':
                $sheet->getStyle($cellAddress)->getNumberFormat()->setFormatCode('General;[Red]General');
                break;
            case 'percentage':
                $sheet->getStyle($cellAddress)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);
                break;
        }
        return 1;
    }

    protected function getCellAddress($columnNumericalIndex, $rowNumericalIndex)
    {
        return Coordinate::stringFromColumnIndex($columnNumericalIndex) . ($rowNumericalIndex + 1);
    }

    protected function saveXlsObject($xls, $fileName)
    {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $filePath = $_SERVER['DOCUMENT_ROOT'] . pathinfo($fileName, PATHINFO_DIRNAME);
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
        switch($fileExtension) {
            case 'csv':
            case 'txt':
                $csvWriter = new Csv($xls);
                $csvWriter->setDelimiter(self::$CSV_DELIMITER);
                $csvWriter->setEnclosure(self::$CSV_ENCLOSURE);
                $csvWriter->save($_SERVER['DOCUMENT_ROOT'] . $fileName);
                break;
            case 'xls':
                (new Xls($xls))->save($_SERVER['DOCUMENT_ROOT'] . $fileName);
                break;
            default :
                (new Xlsx($xls))->save($_SERVER['DOCUMENT_ROOT'] . $fileName);
                break;
        }
    }

    public function addColumn($label, $field, $type = 'string')
    {
        $this->columns[] = ['label' => $label, 'field' => $field, 'type' => $type];
    }

    protected function beforeExportStart($xls, $dataset)
    {
    }

    protected function beforeHeadAppend(&$head)
    {
    }

    protected function beforeRowAppend(&$row)
    {

    }

    protected function afterHeadAppend($head)
    {
    }

    protected function afterRowAppend($row)
    {
    }

    function afterExportEnd($xls)
    {
    }

    public function appendHead(bool $val)
    {
        $this->appendHead = $val;
    }
}
