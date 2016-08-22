<?php
namespace LTH\get_menu\Hooks;

$orgPid;

class ProcessCmdmap {
   /**
    *
    * @param string $table the table of the record
    * @param integer $id the ID of the record
    * @param array $record The accordant database record
    * @param boolean $recordWasDeleted can be set so that other hooks or
    * @param DataHandler $tcemainObj reference to the main tcemain object
    * @return   void
    */
    
    function processCmdmap_preProcess($command, $table, $id, $value, $dataHandler)
    {
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => "$command, $table, $id, $value", 'crdate' => time()));
        $rootLine;
        $domain;
        $domainStartPage;
        $startPageId;
        $tx_newfields_hamburgerroot;
        
        $this->orgPid = $dataHandler->getPid($table, $id);

        if($table=='pages' && ($command=='delete' || $command=='move')) {
            //$this->clearCacheStartPage($id);
        }
        if($table=='pages') {
            //$this->clearRealurlCache($id);
        }
    }
    
    
    function processCmdmap_postProcess($command, $table, $id, $value, $dataHandler)
    {
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => "$command, $table, $id, $value", 'crdate' => time()));
        if(($table=='tt_content' || $table=='pages') && $command=='move') {
            $oTce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_TCEmain');
            $oTce->start(array(), array());
            $oTce->clear_cache('pages', $this->orgPid);
        }
    }
    
    
    /*public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$pObj)
    {
        
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => print_r($status,true), 'crdate' => time()));
        if($table == 'pages' && $status =='update') {
            $this->clearCacheStartPage($id);
            $rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id);
            if($rootLine) $domain = \TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord($rootLine);
            if($domain) {
                //$this->purge('http://' . rtrim($domain, '/') . '/');
                $this->purge('http://' . rtrim($domain, '/') . '/?type=200');
            }
            //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => 'http://' . rtrim($domain, '/') . '/?type=200' . "$table, $id", 'crdate' => time()));
        }
    }*/
    
    
    public function clearCachePostProc($_params, $pObj)
    {
        $pagePath;
        $domain;
        $rootLine;
        $pid;
        $fullPath;
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => print_r($_params, true), 'crdate' => time()));
        if($_params['table']==='tt_news') {
            if(is_array($_params['TSConfig'])) {
                if($_params['TSConfig']['clearCacheCmd']) {
                    $clearCacheCmdArray = explode(',', $_params['TSConfig']['clearCacheCmd']);
                    foreach($clearCacheCmdArray as $key => $value) {
                        $rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($value);
                        if($rootLine) $domain = \TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord($rootLine);
                        if($domain) $this->ban($value);
                    }
                }
            }
        } elseif($_params['uid_page']) {
            if($_params['table']==='tt_content') {
                $rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($_params['uid_page']);
            } elseif($_params['table']==='pages') {
                $pid = $this->getPagePid($_params['uid_page']);
                //$this->clearRealurlCache(intval($_params['uid_page']));
                if($pid) $rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pid);
            }
            if($rootLine) $domain = \TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord($rootLine); 
            //if($domain) $pagePath = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($_params['uid_page'],'','');

            if($domain) {
                //$fullPath = 'http://' . rtrim($domain,'/') . '/' . rtrim(ltrim($row['pagepath'], '/'), '/') . '/';
                $domainPath = 'http://' . rtrim($domain,'/') . '/?type=200';
                if($_params['table']==='tt_content' || $_params['cacheCmd']) {
                    //$this->purge(rtrim($fullPath, '/') . '/');
                    $this->ban($_params['uid_page']);
                } elseif($_params['table']==='pages') {
                    $subPagesArray = $this->getSubPages($this->getPagePid($_params['uid_page']));
                    foreach($subPagesArray as $key => $value) {
                        $this->ban($value);
                    }
                    $this->banURL($domainPath);
                }
            }
        }
    }
    
    
    function ban($pid)
    {
        $domain = 'http://127.0.0.1';

        try {
            if($pid) {
                //echo $domain . $pid;
                $headers = array(
                    'Varnish-Ban-TYPO3-Pid:' . $pid, 
                    'Varnish-Ban-TYPO3-Sitename:'. \TYPO3\CMS\Core\Utility\GeneralUtility::hmac($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])
                );
                $curl = curl_init($domain);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BAN");                
                $res = curl_exec($curl);

                if(curl_exec($curl) === false)
                {
                   // echo 'Curl error: ' . curl_error($curl);
                }
                else
                {
                    //echo 'Operation completed without any errors';
                }
            }
	} catch(Exception $e) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
   
    
    function banURL($pageUrl)
    {
        try {
            //echo $pageUrl;
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BANURL");
            $res = curl_exec($curl);
	} catch(Exception $e) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
    
    
    function getPagePid($uid)
    {
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid', 'pages', 'uid='.intval($uid));
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $pid = $row['pid'];
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        if(intval($pid)===0) return intval($uid);
        return $pid;
    }
    
    
    function getSubPages($pid)
    {
        $uidArray = array();
        $sql = 'SELECT p2.uid AS uid2,p3.uid AS uid3,p4.uid AS uid4,
            p5.uid AS uid5,p6.uid AS uid6,p7.uid AS uid7,p8.uid AS uid8,p9.uid AS uid9,p10.uid AS uid10 FROM pages p1 
            LEFT JOIN pages p2 ON p1.uid=p2.pid AND p2.deleted=0 AND p2.hidden=0
            LEFT JOIN pages p3 ON p2.uid=p3.pid AND p3.deleted=0 AND p3.hidden=0
            LEFT JOIN pages p4 ON p3.uid=p4.pid AND p4.deleted=0 AND p4.hidden=0
            LEFT JOIN pages p5 ON p4.uid=p5.pid AND p5.deleted=0 AND p5.hidden=0
            LEFT JOIN pages p6 ON p5.uid=p6.pid AND p6.deleted=0 AND p6.hidden=0
            LEFT JOIN pages p7 ON p6.uid=p7.pid AND p7.deleted=0 AND p7.hidden=0
            LEFT JOIN pages p8 ON p7.uid=p8.pid AND p8.deleted=0 AND p8.hidden=0
            LEFT JOIN pages p9 ON p8.uid=p9.pid AND p9.deleted=0 AND p9.hidden=0
            LEFT JOIN pages p10 ON p9.uid=p10.pid AND p10.deleted=0 AND p10.hidden=0
            WHERE p1.uid=' . intval($pid);
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $uidArray[] = $pid;
        while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
            $uidArray[] = $row['uid2'];
            $uidArray[] = $row['uid3'];
            $uidArray[] = $row['uid4'];
            $uidArray[] = $row['uid5'];
            $uidArray[] = $row['uid6'];
            $uidArray[] = $row['uid7'];
            $uidArray[] = $row['uid8'];
            $uidArray[] = $row['uid9'];
            $uidArray[] = $row['uid10'];
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        if(count($uidArray) > 0) {
            $uidArray = array_filter($uidArray);
            $uidArray = array_unique($uidArray);
        }
        return $uidArray;
    }
    
    
       /*function processDatamap_afterDatabaseOperations()
    {
                $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => "???", 'crdate' => time()));

    }*/
    
    
   /* function clearCacheStartPage($id)
    {
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_newfields_hamburgerroot','pages','uid='.intval($id));
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $tx_newfields_hamburgerroot = $row['tx_newfields_hamburgerroot'];
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        if($tx_newfields_hamburgerroot > 0) {
            $startPageId = $tx_newfields_hamburgerroot;
        } else {
            $rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id);
            if($rootLine) $domain = \TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord($rootLine);
            if($domain) {
                $domainStartPage = \TYPO3\CMS\Backend\Utility\BackendUtility::getDomainStartPage($domain);
                $pagePath = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($id,'','');
            }
            if($domainStartPage) $startPageId = $domainStartPage;
            //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => print_r($domainStartPage,true), 'crdate' => time()));
        }
        if($startPageId) {
            $oTce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_TCEmain');
            $oTce->start(array(), array());
            $oTce->clear_cache('pages', $startPageId);
        }
    }*/
    
    
    /*function clearRealurlCache($id)
    {
        
        if($id) {
            //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $id, 'crdate' => time()));
            //$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
            $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_pathcache', 'page_id=' . intval($id));
            //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery, 'crdate' => time()));
            $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_urldecodecache', 'page_id=' . intval($id));
            $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_urlencodecache', 'page_id=' . intval($id));
        }
    }
    */
    
     /*function purge($pageUrl)
    {
	try {
            if($pageUrl) {
                //echo str_replace(' ', '-', rtrim($pageUrl, '/'));
                $curl = curl_init(str_replace(' ', '-', rtrim($pageUrl, '/')));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BAN");                
                $res = curl_exec($curl);
                
                //echo str_replace(' ', '-', rtrim($pageUrl, '/')) . '/';
                $curl = curl_init(str_replace(' ', '-', rtrim($pageUrl, '/')) . '/');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BAN");
                $res = curl_exec($curl);
                

                //$res = curl_exec($curl);
                if(curl_exec($curl) === false)
                {
                   // echo 'Curl error: ' . curl_error($curl);
                }
                else
                {
                    //echo 'Operation completed without any errors';
                }
            }
            
	} catch(Exception $e) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
    */
    
    /*
    function banDown($pageUrl)
    {
        try {
            //echo $pageUrl;
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BANDOWN");
            $res = curl_exec($curl);
	} catch(Exception $e) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
    */
    
    /*function banDomain($pageUrl)
    {
	try {
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BANDOMAIN");
            $res = curl_exec($curl);
	} catch(Exception $e) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
    */
    /*
    function banPath($pageUrl)
    {
	try {
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BANPATH");
            $res = curl_exec($curl);
	} catch(Exception $e) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
    */
    
        /*function getFullPath($pagePath, $domain, $cacheCmd)
    {
        $pagePathArray = explode('/', $pagePath);
        //print_r($pagePathArray);
        array_shift($pagePathArray);
        array_shift($pagePathArray);
        array_shift($pagePathArray);
        $pagePathArray = array_map('trim',$pagePathArray);
        
        //if($cacheCmd) array_shift($pagePathArray);
        $pagePath = implode('/', $newPathArray);
        $pagePath = str_replace('  ',' ',$pagePath);
        $pagePath = str_replace('å','aa',$pagePath);
        $pagePath = str_replace('ä','ae',$pagePath);
        $pagePath = str_replace('ö','oe',$pagePath);
        $pagePath = str_replace(' & ','-',$pagePath);
        $pagePath = str_replace(',','-',$pagePath);
        $pagePath = str_replace(' - ','-',$pagePath);
        $pagePath = str_replace(' -','-',$pagePath);
        $pagePath = str_replace('- ','-',$pagePath);
        $pagePath = str_replace('_','-',$pagePath);
        //$pagePath = $this->encodeTitle($pagePath);
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pagePath, 'crdate' => time()));
        $fullPath = 'http://' . rtrim($domain,'/') . '/' . ltrim($pagePath, '/');
        $domainPath = 'http://' . rtrim($domain,'/') . '/?type=200';
        return array($fullPath, $domainPath);
    }
    */
}