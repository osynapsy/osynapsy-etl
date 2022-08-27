<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Etl;


class XlsToArray
{
    private $db;
    private $error = array();
    private $delimiter = null;
    private $lineending = null;
    public $max = [
        'row' => 0,
        'col' => 0
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function loadExcel($fileName, $grabNumRow = null)
    {
        try {
            $fileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($fileName);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($fileType);
            switch($fileType) {
                case 'CSV':
                    if (!is_null($this->delimiter)) {
                        $reader->setDelimiter($this->delimiter);
                    }
                    break;
            }
            $excel = $reader->load($fileName);
            //  Get worksheet dimensions
            $sheet = $excel->getSheet(0);
            $this->max['row'] = $sheet->getHighestRow();
            $this->max['col'] = $sheet->getHighestDataColumn();
            $data = array();
            for ($row = 1; $row <= $this->max['row']; $row++) {
                $data[] = $sheet->rangeToArray('A' . $row . ':' . $this->max['col'] . $row, NULL, TRUE, FALSE);
                if (!empty($grabNumRow) && $row <= $grabNumRow){
                    break;
                }
            }
            return $data;
        } catch (\Exception $e) {
            return 'Errore nell\'apertura del file "'.pathinfo($fileName,PATHINFO_BASENAME).'": '.$e->getMessage();
        }
    }

    public function isValidFile($fileName)
    {
        try {
            $fileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($fileName);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($fileType);
            $excel = $reader->load($fileName);
            return $excel;
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    public function import($table, $fields, $data, $constant=array())
    {
        if (empty($table)) {
            $this->error[] = 'Table is empty';
        }
        if (empty($fields)) {
            $this->error[] = 'Fields is empty';
        }
        if (!empty($this->error)) {
            return false;
        }
        //  Loop through each row of the worksheet in turn
        $insert = 0;
        //die(print_r($data,true));
        foreach ($data as $k => $rec) {
            if (empty($rec)) {
                continue;
            }
            $sqlParams = array();
            foreach ($fields as $column => $field) {
                if (empty($field)) {
                    continue;
                }
                $sqlParams[$field] = !empty($rec[0][$column]) ? $rec[0][$column] : null ;
            }

            foreach($constant as $field => $value) {
                $sqlParams[$field] = $value;
            }
            $IDs = [];
            if (!empty($sqlParams)){
                try {
                    $IDs[] = $this->db->insert($table, $sqlParams);
                    $insert++;
                } catch (\Exception $e) {
                    $this->error[] = "Row n. $k not imported";
                }
            }
            return $IDs;
        }

        return $insert;
    }

    private function buildXls($title)
    {
        $xls = new PHPExcel();
        $xls->getProperties()->setCreator("Osynapsy");
        $xls->getProperties()->setLastModifiedBy("Osynapsy");
        $xls->getProperties()->setTitle($title);
        $xls->getProperties()->setSubject("Data Export");
        $xls->getProperties()->setDescription("Data export from Osynapsy");
        return $xls;
    }

    public function export($data, $title = 'Data export', $basePath = '/upload/export/')
    {
        $xls = $this->buildXls($title);
        function getColumnId($n) {
            $l = range('A','Z');
            if ($n <= 26) {
                return $l[$n-1];
            }
            $r = ($n % 26);
            $i = (($n - $r) / 26) - (empty($r) ? 1 : 0);
            return getColumnId($i).(!empty($r) ? getColumnId($r) : 'Z');
        }

        for ($i = 0; $i < count($data); $i++) {
            $j = 0;
            foreach ($data[$i] as $k => $v) {
                if ($k[0] == '_') continue;
                $col = getColumnId($j+1);
                $cel = $col.($i+2);
                try{
                    if (empty($i)) {
                        $xls->getActiveSheet()->SetCellValue($col.($i+1), str_replace(array('_X','!'),'',strtoupper($k)));
                    }
                    $xls->getActiveSheet()->SetCellValue($cel, str_replace('<br/>',' ',$v));
                } catch (Exception $e){
                }
                $j++;
            }
        }

        $xls->getActiveSheet()->setTitle($title);
        //Generate filename
        $filename  = $basePath;
        $filename .= str_replace(' ','-',strtolower($title));
        $filename .= date('-Y-m-d-H-i-s');
        $filename .= '.xlsx';
        //Init writer
        $writer = new \PHPExcel_Writer_Excel2007($xls);
        //Write
        $writer->save($_SERVER['DOCUMENT_ROOT'].$filename);
        //return filename
        return $filename;
    }

    public function getError()
    {
        return implode("\n",$this->error);
    }

    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    public function setLineEnding($linending)
    {
        $this->lineending = $linending;
    }
}
