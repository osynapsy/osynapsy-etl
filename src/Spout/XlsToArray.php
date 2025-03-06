<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Etl\Spout;

use \OpenSpout\Reader\XLSX\Reader;

/**
 * Description of XlsToArray
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class XlsToArray 
{
    public function load($filePath, array $sheetIdToLoad = [0], $firstRowContainsTitle = false)
    {
        $reader = new Reader();
        $reader->open($filePath);
        $data = [];
        $i = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            if (!empty($sheetIdToLoad) &&  in_array($sheet->getIndex(), $sheetIdToLoad)) {
                continue;
            }
            $data[$i] = $this->loadSheet($sheet);
        }
        $reader->close();
        return $data;        
    }
    
    protected function loadSheet($sheet)
    {
        $sheetDataset = [];
        foreach ($sheet->getRowIterator() as $orow) {                
            $sheetDataset[] = $orow->getCells();                
        }
        return $sheetDataset;
    }
}
