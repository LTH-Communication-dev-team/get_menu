<?php
class get_menu_file_handling implements TYPO3\CMS\Core\Utility\File\ExtendedFileUtilityProcessDataHookInterface {
    
    public function processData_postProcessAction($action, array $cmdArr, array $result, \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $parentObject)
    {
        $files = array_pop( $result );
        if ( !is_array( $files ) ) {
            return;
        }
        foreach ( $files as $file ) {
            $fileUid .= $file->getUid();  // Uid of the file
            $fileName .= $file->getName();  // Name of the file
        }

        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $action . $fileUid.$fileName, 'crdate' => time()));
    }
} 