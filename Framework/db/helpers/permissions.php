<?php

namespace ClickBlocks\DB;

use ClickBlocks\Core;

class PMS
{
  protected static $sql = array('mysql' => 'SET FOREIGN_KEY_CHECKS=0;
SET AUTOCOMMIT=0;
START TRANSACTION;
DROP TABLE IF EXISTS `Permissions`;
DROP TABLE IF EXISTS `RolePermissions`;
DROP TABLE IF EXISTS `Roles`;
DROP TABLE IF EXISTS `UserRoles`;
CREATE TABLE `Permissions` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `node` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `permission` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `IDX_Permissions_NN` (`node`(255),`permission`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
CREATE TABLE `RolePermissions` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `roleID` int(10) unsigned NOT NULL,
  `permissionID` bigint(20) unsigned NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `roleID` (`roleID`),
  KEY `permissionID` (`permissionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
CREATE TABLE `Roles` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
CREATE TABLE `UserRoles` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `userID` bigint(20) unsigned NOT NULL,
  `roleID` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `roleID` (`roleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
ALTER TABLE `UserRoles` ADD CONSTRAINT `Users_UserRoles` FOREIGN KEY (`userID`) REFERENCES `##table##` (`##field##`) ON DELETE CASCADE;
ALTER TABLE `RolePermissions`ADD CONSTRAINT `rolepermissions_ibfk_2` FOREIGN KEY (`permissionID`) REFERENCES `Permissions` (`ID`) ON DELETE CASCADE, ADD CONSTRAINT `rolepermissions_ibfk_1` FOREIGN KEY (`roleID`) REFERENCES `Roles` (`ID`) ON DELETE CASCADE;
ALTER TABLE `UserRoles`ADD CONSTRAINT `userroles_ibfk_1` FOREIGN KEY (`roleID`) REFERENCES `Roles` (`ID`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;');

  public static function create($dbAlias, $table, $field)
  {
    $db = ORM::getInstance()->getDB($dbAlias);
    $db->execute(strtr(self::$sql[$db->getEngine()], array('##table##' => $table, '##field##' => $field)));
    $xml = Core\IO::dir('engine') . '/db.xml';
    $pdom = new \DOMDocument('1.0', 'utf-8');
    $pdom->preserveWhiteSpace = false;
    $pdom->loadXML(str_replace('##db##', $dbAlias, file_get_contents(Core\IO::dir('framework') . '/db/helpers/permissions.xml')));
    $xpath2 = new \DOMXPath($pdom);
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    $dom->load($xml);
    $xpath1 = new \DOMXPath($dom);
    foreach (array('Physical', 'Logical') as $type)
    {
      $tables = $xpath1->query('//DataBase[@Name="' . $dbAlias . '"]/Model' . $type . '/Tables')->item(0);
      foreach ($xpath2->query('//Model' . $type . '/Tables')->item(0)->childNodes as $table)
      {
        $tb = $xpath1->query('//DataBase[@Name="' . $dbAlias . '"]/Model' . $type . '/Tables/Table[@Name="' . $table->getAttribute('Name') . '"]');
        if ($tb->length) $tables->removeChild($tb->item(0));
        $tables->appendChild($dom->importNode($table, true));
      }
    }
    $classes = $xpath1->query('//Mapping/Classes')->item(0);
    foreach ($xpath2->query('//Mapping/Classes')->item(0)->childNodes as $class)
    {
      $tb = $xpath1->query('//Mapping/Classes/Class[@Name="' . $class->getAttribute('Name') . '"]');
      if ($tb->length) $classes->removeChild($tb->item(0));
      $classes->appendChild($dom->importNode($class, true));
    }
    file_put_contents($xml, $dom->saveXML());
    ORM::getInstance()->generateClasses();
  }
  
  public static function check($userID, $node, $permissions)
  {
    $db = Core\Register::getInstance()->db;
    $permissions = (array)$permissions;
    foreach ($permissions as &$permission) $permission = $db->quote($permission);
    $sql = 'SELECT COUNT(*) FROM Permissions AS p
            INNER JOIN RolePermissions AS rp ON rp.permissionID = p.ID
            INNER JOIN UserRoles AS ur ON ur.roleID = rp.roleID
            WHERE ur.userID = ? AND p.node = ? AND p.permission IN (' . implode(',', $permissions) . ')';
    return $db->cell($sql, array($userID, $node)) > 0;
  }
}

?>