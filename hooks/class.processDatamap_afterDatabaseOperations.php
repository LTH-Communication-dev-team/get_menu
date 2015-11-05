<?php
class user_processDatamap_afterDatabaseOperations {
      
    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, t3lib_TCEmain $pObj)
    {
        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => "$status, $table, $id", 'crdate' => time()));
    }
    
}