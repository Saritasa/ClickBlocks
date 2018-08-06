<?php
/**
 * Created by PhpStorm.
 * User: warchylde
 * Date: 15.04.16
 * Time: 13:17
 */

namespace ClickBlocks\Utils;


class Excel
{
    /**
     * Writes data collection into a xlsx-file.
     *
     * Available properties:
     * - creator
     * - last_modified_by
     * - title
     * - subject
     * - description
     * - keywords
     * - category
     *
     * Example:
     *
     * $rows = [
     *     ['Name', 'Date', 'Status'],
     *     ['Chupa', '2016-04-15', true],
     *     ['Cabra', '2016-04-15', false],
     * ];
     *
     * $properties = [
     *   'creator' => 'Chupa Cabra',
     *   'last_modified_by' => 'Chupa Cabra',
     *   'title' => 'Office 2007 XLSX Test Document',
     *   'subject' => 'Office 2007 XLSX Test Document',
     *   'description' => 'Test document for Office 2007 XLSX, generated using PHP classes.',
     *   'keywords' => 'office 2007 openxml php',
     *   'category' => 'Test result file'
     * ];
     *
     * Excel::writeArray($rows, 'somewhere.xlsx', $properties);
     *
     * @param array $rows The data collection (2d - array)
     * @param string $filepath The path to saving file
     * @param array|null $props Properties for creating xlsx-file
     * @throws \Exception When set-property method not found
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public static function writeArray(array $rows, $filepath, array $props = null)
    {
        $excel = new \PHPExcel();
        
        if(is_array($props)) {
            $properties = $excel->getProperties();

            foreach ($props as $name => $value) {
                $method = Inflector::camelize('set_'.$name);

                if(!method_exists($properties, $method)) {
                    throw new \Exception('Method "'.$method.'" for the property "'.$name.'" not found');
                }

                $properties->$method($value);
            }
        }


        $excel
            ->setActiveSheetIndex(0)
            ->fromArray($rows, NULL, 'A1')
        ;

        \PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($filepath);
    }
}