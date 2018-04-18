<?php

namespace ClickBlocks\DB;

use ClickBlocks\Core,
    ClickBlocks\Exceptions;

class ORMSynchronizer
{  
  public function sync($xmlfile = null)
  {
    $xml1 = \CB::dir('temp') . '/db.xml';
    $xml2 = \CB::dir('engine') . '/db.xml';
    $orm = ORM::getInstance();
    if (!is_file($xml2))
    {
      $orm->generateXML('ClickBlocks\\DB');
      return;
    }
    $orm->generateXML('ClickBlocks\\DB', $xml1);
    $dom1 = new \DOMDocument('1.0', 'utf-8');
    $dom1->preserveWhiteSpace = false;
    $dom1->load($xml1);
    $xpath1 = new \DOMXPath($dom1);
    $dom2 = new \DOMDocument('1.0', 'utf-8');
    $dom2->formatOutput = true;
    $dom2->preserveWhiteSpace = false;
    $dom2->load($xml2);
    $xpath2 = new \DOMXPath($dom2);
    foreach ($xpath2->query('//DataBase') as $db2)
    {
      $dbName = $db2->getAttribute('Name');
      $db1 = $xpath1->query('//DataBase[@Name="' . $dbName . '"]');
      if ($db1->length == 0) $dom2->documentElement->removeChild($db2);
    }
    foreach ($xpath1->query('//DataBase') as $n => $db1)
    {
      $dbName = $db1->getAttribute('Name');
      $db2 = $xpath2->query('//DataBase[@Name="' . $dbName . '"]');
      if ($db2->length == 0)
      {
        $dom2->documentElement->insertBefore($dom2->importNode($db1, true), $dom2->documentElement->childNodes->item($n));
        continue;
      }
      $db2 = $db2->item(0);
      $db2->replaceChild($dom2->importNode($db1->childNodes->item(0), true), $db2->childNodes->item(0));
      $tables = $xpath2->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables')->item(0);
      foreach ($xpath2->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table') as $tb2)
      {
        $repo = $tb2->getAttribute('Repository');
        $tb1 = $xpath1->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]');
        if ($tb1->length == 0) $tables->removeChild($tb2);
      }
      foreach ($xpath1->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table') as $n => $tb1)
      {
        $repo = $tb1->getAttribute('Repository');
        $tb2 = $xpath2->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]');
        if ($tb2->length == 0)
        {
          $tables->insertBefore($dom2->importNode($tb1, true), $tables->childNodes->item($n));
          continue;
        }
        $tb2 = $tb2->item(0);
        $fields = $xpath2->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]/Fields')->item(0);
        foreach ($xpath2->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]/Fields/Field') as $field2)
        {
          $link = $field2->getAttribute('Link');
          $field1 = $xpath1->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]/Fields/Field[@Link="' . $link . '"]');
          if ($field1->length == 0) $fields->removeChild($field2);
        }
        foreach ($xpath1->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]/Fields/Field') as $n => $field1)
        {
          $link = $field1->getAttribute('Link');
          $field2 = $xpath2->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]/Fields/Field[@Link="' . $link . '"]');
          if ($field2->length == 0)
          {
            $fields->insertBefore($dom2->importNode($field1, true), $fields->childNodes->item($n));
            continue;
          }
        }
        $tmp1 = $tmp2 = array();
        foreach (array(1, 2) as $n)
        {
          foreach (${'xpath' . $n}->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]/NavigationProperties/Property') as $property)
          {
            $html = $this->getInnerHTML($property);
            ${'tmp' . $n}[substr($html, 0, strrpos($html, '<Select'))] = $property;
          }
        }
        $properties = $xpath2->query('//DataBase[@Name="' . $dbName . '"]/ModelLogical/Tables/Table[@Repository="' . $repo . '"]/NavigationProperties')->item(0);
        if ($tmp = array_diff_key($tmp1, $tmp2))
        {
          foreach ($tmp as $property)
          {
            $properties->appendChild($dom2->importNode($property, true));
          }
        }
        if ($tmp = array_diff_key($tmp2, $tmp1))
        {
          foreach ($tmp as $property)
          {
            $properties->removeChild($property);
          }
        }
      }
    }
    $classes = $xpath2->query('//Mapping/Classes')->item(0);
    foreach ($xpath2->query('//Mapping/Classes/Class') as $n => $class2)
    {
      $repo = $class2->getAttribute('Repository');
      $class1 = $xpath1->query('//Mapping/Classes/Class[@Repository="' . $repo . '"]');
      if ($class1->length == 0) $classes->removeChild($class2);
    }
    foreach ($xpath1->query('//Mapping/Classes/Class') as $n => $class1)
    {
      $repo = $class1->getAttribute('Repository');
      $class2 = $xpath2->query('//Mapping/Classes/Class[@Repository="' . $repo . '"]');
      if ($class2->length == 0)
      {
        $classes->insertBefore($dom2->importNode($class1, true), $classes->childNodes->item($n));
        continue;
      }
      $repo = $class1->getAttribute('Repository');
      $parts = explode('.', $repo);
      $properties = $dom2->createElement('Properties');
      foreach ($xpath2->query('//DataBase[@Name="' . $parts[0] . '"]/ModelLogical/Tables/Table[@Name="' . $parts[1] . '"]/NavigationProperties/Property') as $property)
      {
        $prop = $dom2->createElement('Property');
        $prop->setAttribute('Name', $repo . '.' . $property->getAttribute('Name'));
        $prop->setAttribute('Navigation', '1');
        $properties->appendChild($prop);
      }
      foreach ($xpath2->query('//DataBase[@Name="' . $parts[0] . '"]/ModelLogical/Tables/Table[@Name="' . $parts[1] . '"]/Fields/Field') as $field)
      {
        $prop = $dom2->createElement('Property');
        $prop->setAttribute('Name', $repo . '.' . $field->getAttribute('Name'));
        $properties->appendChild($prop);
      }
      $class2->item(0)->replaceChild($properties, $xpath2->query('//Mapping/Classes/Class[@Repository="' . $repo . '"]/Properties')->item(0));
    }
    $dom2->save($xml2);
  }
  
  protected function getInnerHTML(\DOMNode $node)
  {
    $html = '';
    foreach ($node->childNodes as $child)
    {
      $dom = new \DOMDocument();
      $dom->appendChild($dom->importNode($child, true));
      $html .= trim($dom->saveHTML());
    }
    return $html;
  }
}

?>