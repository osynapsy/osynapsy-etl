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

class Export extends Prototype
{           
    public function exec(array $data, $title = 'Data export', $basePath = '/upload/export/')
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
                if ($k[0] == '_') {
                    continue;
                }
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
}
