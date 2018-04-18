<?php
/**
 * ClickBlocks.PHP v. 1.0
 *
 * Copyright (C) 2014  SARITASA LLC
 * http://www.saritasa.com
 *
 * This framework is free software. You can redistribute it and/or modify
 * it under the terms of either the current ClickBlocks.PHP License
 * viewable at theclickblocks.com) or the License that was distributed with
 * this file.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the ClickBlocks.PHP License
 * along with this program.
 *
 * @copyright  2007-2014 SARITASA LLC <info@saritasa.com>
 * @link       http://www.saritasa.com
 */

namespace ClickBlocks\Data\Validators;

use ClickBlocks\Core;

/**
 * This validator compares the given XML with another XML or checks whether the given XML has the specified structure.
 *
 * @version 1.0.0
 * @package cb.data.validators
 */
class XML extends Validator
{
  /**
   * XML string or path to the XML file to be compared with.
   *
   * @var string $xml
   * @access public
   */
  public $xml = null;
  
  /**
   * The XML schema which the validating XML should correspond to. 
   *
   * @var string $schema
   * @access public
   */
  public $schema = null;

  /**
   * Validates an XML string.
   *
   * @param string $entity - the XML for validation.
   * @return boolean
   * @access public
   */
  public function validate($entity)
  {
    if ($this->empty && $this->isEmpty($entity)) return $this->reason = true;
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    if (is_file($entity)) $dom->load($entity);
    else $dom->loadXML($entity);
    if ($this->schema)
    {
      libxml_clear_errors();
      if (is_file($this->schema)) 
      {
        if (!$dom->schemaValidate($this->schema)) 
        {
          $this->reason = ['code' => 0, 'reason' => 'invalid schema', 'details' => libxml_get_errors()];
          return false;
        }
      }
      else if (!$dom->schemaValidateSource($this->schema)) 
      {
        $this->reason = ['code' => 0, 'reason' => 'invalid schema', 'details' => libxml_get_errors()];
        return false;
      }
    }
    if (!$this->xml) return $this->reason = true;
    $xml = $dom->saveXML();
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    if (is_file($this->xml)) $dom->load($this->xml);
    else $dom->loadXML($this->xml);
    if ($xml === $dom->saveXML()) return $this->reason = true;
    $this->reason = ['code' => 1, 'reason' => 'XML are not equal'];
    return false;
  }  
}