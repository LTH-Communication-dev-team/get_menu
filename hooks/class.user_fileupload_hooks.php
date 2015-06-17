<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2013 Xavier Perseguers <xavier@causal.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This class extends t3lib_extFileFunctions and hooks into DAM to
 * automatically resize huge pictures upon upload.
 *
 * @category    Hook
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @deprecated  This class is kept for compatibility with TYPO3 4.5, 4.6 and 4.7
 */
class user_fileUpload_hooks implements t3lib_extFileFunctions_processDataHook, t3lib_TCEmain_processUploadHook {

    /**
     * @var array
     */
    protected $rulesets = array();

    /**
     * Default constructor.
     */
    public function __construct() {
	/*$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['image_autoresize_ff'];
	if (!$config) {
		$this->notify(
			$GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:message.emptyConfiguration'),
			t3lib_FlashMessage::ERROR
		);
	}
	$config = unserialize($config);
	if (is_array($config)) {
		$this->initializeRulesets($config);
	}*/
    }

    /**
     * Post processes upload of a picture and makes sure it is not too big.
     *
     * @param string $filename The uploaded file
     * @param t3lib_TCEmain $parentObject
     * @return void
     */
    public function processUpload_postProcessAction(&$filename, t3lib_TCEmain $parentObject)
    {
	    //$filename = $this->processFile($filename);
    }

    /**
     * Post processes upload of a picture and makes sure it is not too big.
     *
     * @param string $action The action
     * @param array $cmdArr The parameter sent to the action handler
     * @param array $result The results of all calls to the action handler
     * @param t3lib_extFileFunctions $pObj The parent object
     * @return void
     */
    public function processData_postProcessAction($action, array $cmdArr, array $result, t3lib_extFileFunctions $pObj)
    {
	    if ($action === 'upload') {
		    // Get the latest uploaded file name
		    $filename = array_pop($result);
		    //$this->processFile($filename);
		    echo '89';
	    }
    }

    /**
     * Post-processes a file operation that has already been handled by DAM.
     *
     * @param string $action
     * @param array|NULL $data
     * @return void
     */
    public function filePostTrigger($action, $data)
    {
	    if ($action === 'upload' && is_array($data)) {
		
		
		    $filename = $data['target_file'];
		    if (is_file($filename)) {
			$filenameArray = explode('fileadmin/',$filename);
			$res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_dam', "CONCAT(file_path,file_name) = 'fileadmin/".$filenameArray[1]."'");
			$row1 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1);
			if(isset($row1['uid'])) {
			    $res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('p.uid AS uid', 'pages p JOIN tt_content t ON t.pid = p.uid', "t.bodytext LIKE '%txdam=".$row1['uid']."%' OR t.bodytext LIKE '%<media ".$row1['uid']."%' OR bodytext LIKE '%<link fileadmin/" . $filenameArray[1]. "%'");
			    while ($row1 = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res1)) {
				if(isset($row1['uid'])) {
				    $rootLineArray = t3lib_BEfunc::BEgetRootLine($row1['uid']);
				     if(isset($rootLineArray)) {
					if(is_array($rootLineArray)) {
					    $res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('domainName', 'sys_domain', 'pid IN('.implode(',',$this->getUidsFromRootLine($rootLineArray)).')', '', '', '0,1');
					    $row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2);
					    if(isset($row2['domainName'])) {
						$this->purge('http://' . str_replace('//','/',$row2['domainName'].'/') . 'fileadmin/'.$filenameArray[1]);
					    }				    
					}
				    }
				}
			    }
			}
			
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			//$this->purge('fileadmin/' . $filenameArray[1]);
		    }
	    }
    }

    
    protected function purge($pageUrl)
    {
	try {
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
	    $res = curl_exec($curl);
	} catch(Exception $e) {

	}
    }


    protected function getUidsFromRootLine(array $rootLine)
    {
	$uidArray = array();

	foreach ($rootLine as $page) {
	    $uidArray[] = $page['uid'];
	}

	return $uidArray;
    }
    
    
    protected function getSiteRootPageIdFromRootLine(array $rootLine)
    {
	$siteRootPageId = 0;

	foreach ($rootLine as $page) {
		if ($page['is_siteroot']) {
			$siteRootPageId = $page['uid'];
			break;
		}
	}

	return $siteRootPageId;
    }
}