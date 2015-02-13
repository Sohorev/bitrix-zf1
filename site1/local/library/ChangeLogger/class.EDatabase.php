<?php
require_once(realpath(dirname(__FILE__)) . '/class.ChangeLogger.php');

class EDatabase extends CDatabase
{
    public function edbInitFromObject(CDatabase $db){
        $state = get_object_vars($db);
        foreach ($state as $varName=>$varValue) {
            $this->$varName = $varValue;
        }
        unset($state);
    }

    public function addDebugQuery($strSql, $execTime) {
        ChangeLogger::getInstance()->logQuery($strSql);
    }
}