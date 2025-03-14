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

class CsvToArray
{
    public $max = [
        'row' => 0,
        'col' => 0
    ];

    public function loadFile($filename, $delimiter = ';', $enclosure = '')
    {
        try {
            $this->validateFile($filename);
            $filehandler = $this->openFile($filename);
            $dataset = [];
            while($row = fgetcsv($filehandler, null, $delimiter, $enclosure)) {
               $this->max['col'] = max($this->max['col'], count($row));
               $dataset[] = $row;
            }                         
            $this->max['row'] = count($row);            
            return $dataset;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    
    protected function openFile($filename)
    {
        
        return fopen($filename, 'r');
    }

    public function validateFile($filename)
    {
        if (!is_file($filename)) {
            throw new \Exception(sprintf('File %s not found', $filename));
        }
        if (!is_readable($filename)) {
            throw new \Exception(sprintf('File %s is not readable', $filename));
        }
    }
}
