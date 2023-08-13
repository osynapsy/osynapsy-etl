<?php
namespace Osynapsy\Etl\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Description of XlsToArray
 *
 * @author pietr
 */
class XlsToArray
{
    protected $delimeter;
    protected $filename;
    protected $dimensions = [];
    protected $spreadsheet;

    public function __construct($filename, $delimeter = null)
    {
        $this->filename = $filename;
        $this->delimeter = $delimeter;        
        $this->spreadsheet = $this->spreadsheetFactory($filename, $delimeter);
    }
    
    protected function spreadsheetFactory($filename, $delimiter)
    {
        $filetype = IOFactory::identify($filename);
        $reader = IOFactory::createReader($filetype);
        if ($filetype === 'CSV' && !empty($delimiter)) {                           
            $reader->setDelimiter($this->delimiter);                
        }
        return $reader->load($filename);
    }
    
    public function get($sheetIdx = 0, $limits = [])
    {        
        $sheet = $this->spreadsheet->getSheet($sheetIdx);
        $this->setDimension($sheet);
        return $this->arrayFactory($sheet, $limits);        
    }
    
    protected function setDimension($sheet)
    {
        $this->dimensions['rows'] = $sheet->getHighestRow();
        $this->dimensions['cols'] = $sheet->getHighestDataColumn();
    }
    
    protected function arrayFactory($sheet, $limits = [])
    {
        $iMin = sprintf('%s%s', 'A', $limits[0] ?? 1);
        $iMax = sprintf('%s%s', $this->getDimension('cols'), $limits[1] ?? $this->getDimension('rows'));
        return $sheet->rangeToArray(sprintf('%s:%s', $iMin, $iMax), null, true, false);            
    }
    
    public function getDimension($key = null)
    {
        return empty($key) ? $this->dimensions : $this->dimensions[$key];
    }
}
