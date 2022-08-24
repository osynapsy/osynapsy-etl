<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Etl\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Prototype
{
    private $db;
    protected $xls;
    private $error = array();
    private $delimiter = null;
    private $lineending = null;
    public $dimensions = [
        'row' => 0,
        'col' => 0
    ];

    public function __construct($db)
    {
        $this->db = $db;
        $this->xls = new Spreadsheet();
        $this->xls->getProperties()->setCreator("Osynapsy");
        $this->xls->getProperties()->setLastModifiedBy("Osynapsy");
        $this->xls->getProperties()->setSubject("Data Export");
        $this->xls->getProperties()->setDescription("Data export from Osynapsy");
    }

    public function isValidFile($fileName)
    {
        try {
            $fileType = IOFactory::identify($fileName);
            $reader = IOFactory::createReader($fileType);
            $excel = $reader->load($fileName);
            return $excel;
        } catch(\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getDimension()
    {
        return $this->dimensions;
    }

    public function getError()
    {
        return implode("\n",$this->error);
    }

    public function getXls()
    {
        return $this->xls;
    }

    public function load($fileName, $grabNumRow = null)
    {
        try {
            $fileType = IOFactory::identify($fileName);
            $reader = IOFactory::createReader($fileType);
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
            $this->dimensions['rows'] = $sheet->getHighestRow();
            $this->dimensions['cols'] = $sheet->getHighestDataColumn();
            $data = [];
            for ($row = 1; $row <= $this->getDimension()['rows']; $row++) {
                $data[] = $sheet->rangeToArray('A' . $row . ':' . $this->getDimension()['cols'] . $row, NULL, TRUE, FALSE);
                if (!empty($grabNumRow) && $row <= $grabNumRow){
                    break;
                }
            }
            return $data;
        } catch (\Exception $e) {
            return 'Errore nell\'apertura del file "'.pathinfo($fileName,PATHINFO_BASENAME).'": '.$e->getMessage();
        }
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
