<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class get_menu_functions {
    
    function getRootId($uid_page)
    {
	//tslib_eidtools::connectDB();
        $sql = "SELECT SUBSTRING_INDEX(GROUP_CONCAT(template.pid),',',-1) AS rootid
        FROM pages AS node
        JOIN pages AS parent
        LEFT JOIN sys_template AS template ON parent.uid=template.pid
        WHERE node.lft BETWEEN parent.lft AND parent.rgt
        AND node.uid = $uid_page
        ORDER BY node.lft";
	$res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
	$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        if(isset($row['rootid'])) {
            return $row['rootid'];
        } else {
            return false;
        }
    }
    
    
    function clearAllVarnishCache()
    {
	try {
	    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('domainName', 'sys_domain', 'hidden=0');
	    while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
		if(isset($row['domainName'])) {
                    $domainName = $row['domainName'];
                    $wholePath = str_replace('//','/', trim($domainName));
                    $this->ban('http://'.$wholePath);
                    //$this->purge('http://'.$wholePath.'/');
                }
	    }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
	} catch(Exception $e) {
	    //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $e, 'crdate' => time()));
	}
    }
    
    
    
    function clearVarnishCacheForDomain($uid_page)
    {
        $sql = "SELECT DISTINCT SD.domainName AS domainName
            FROM pages AS node
            LEFT JOIN tx_realurl_pathcache AS PC ON node.uid = PC.page_id
            LEFT JOIN tx_realurl_urldecodecache AS UDC ON node.uid = UDC.page_id
            LEFT JOIN sys_domain AS SD ON SD.pid = node.root
            WHERE node.uid = $uid_page
        ";
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);
        
        if(isset($row['domainName'])) {
            $domainName = $row['domainName'];
            $wholePath = str_replace('//','/', trim($domainName));
            $this->ban('http://'.$wholePath);
            //$this->purge('http://'.$wholePath.'/');
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
    }
    
    
    function clearVarnishCacheForPage($uid_page)
    {
        $sql = "SELECT DISTINCT UDC.spurl, PC.pagepath, SD.domainName AS domainName
            FROM pages AS node
            LEFT JOIN tx_realurl_pathcache AS PC ON node.uid = PC.page_id
            LEFT JOIN tx_realurl_urldecodecache AS UDC ON node.uid = UDC.page_id
            LEFT JOIN sys_domain AS SD ON SD.pid = node.root OR SD.pid = (SELECT pid FROM pages WHERE uid = (SELECT root FROM pages WHERE uid = $uid_page))
            WHERE node.uid = $uid_page
        ";
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
            if(isset($row['domainName'])) {
                $domainName = $row['domainName'];
            }
            if(isset($row['spurl'])) {
                $pagePath = $row['spurl'];
            } else if(isset($row['pagepath'])) {
                $pagePath = $row['pagepath'];
            }
            //Clear varnish cache
            if($domainName) {
                $wholePath = str_replace('//','/', trim($domainName).'/'.$pagePath.'sucker!');
                $this->ban($wholePath);
            }
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
    }
    
    
    function purge($pageUrl)
    {
	try {
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
	    curl_exec($curl);
            
            $curl = curl_init($pageUrl . '/');
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
	    curl_exec($curl);
            
            curl_close($curl);
	} catch(Exception $e) {
            echo 'no no';	
        }
    }
    
    
    function ban($pageUrl)
    {
	try {
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BAN");
            $res = curl_exec($curl);
            
            $curl = curl_init($pageUrl . '/');
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BAN");
            $res = curl_exec($curl);
	} catch(Exception $e) {
            //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
    
    
    function clearMenuCache($rootId)
    {
	//tslib_eidtools::connectDB();
        $sql = "DELETE t,c FROM cf_cache_hash_tags AS t JOIN cf_cache_hash AS c ON t.identifier = c.identifier AND t.tag LIKE 'hamburger_nav_" . $rootId . "%'";
	//$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => 'hamburger_nav_'.$rootId.'_sv', 'crdate' => time()));
        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $GLOBALS['typo3CacheManager']->getCache('cache_pages')->flushByTag('hamburger_nav_'.$rootId.'_sv');
        $GLOBALS['typo3CacheManager']->getCache('cache_pages')->flushByTag('hamburger_nav_'.$rootId.'_en');
    }
}