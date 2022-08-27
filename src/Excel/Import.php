<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Etl\Excel;

class Import extends Prototype
{
    public function import($table, $fields, $data, array $constant = [])
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

            if (!empty($sqlParams)){
                try {
                    $this->db->insert($table, $sqlParams);
                    $insert++;
                } catch (\Exception $e) {
                    $this->error[] = "Row n. $k not imported";
                }
            }
        }

        return $insert;
    }
}
