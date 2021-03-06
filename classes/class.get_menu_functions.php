<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class get_menu_functions {
    
    function clearAllVarnishCache()
    {
	try {
	    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('domainName', 'sys_domain', 'hidden=0');
	    while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
		if(isset($row['domainName'])) {
                    $domainName = $row['domainName'];
                    $wholePath = str_replace('//','/', trim($domainName));
                    $this->ban('http://'.$wholePath, $domain, '');
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
            $wholePath = str_replace('//','/', trim($domainName) . '/.*');
            $this->banDomain('http://'.$wholePath);
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
    }
    
    
    function clearVarnishCacheForPage($domain, $uid_page, $table)
    {
        //130.235.208.15, 1342, pages
        $sql = "SELECT DISTINCT UDC.spurl, PC.pagepath, node.pid 
            FROM pages AS node
            LEFT JOIN tx_realurl_pathcache AS PC ON node.uid = PC.page_id
            LEFT JOIN tx_realurl_urldecodecache AS UDC ON node.uid = UDC.page_id
            WHERE node.uid = $uid_page
        ";
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);
        if(isset($row['pid'])) {
            $pid = $row['pid'];
        }
        if(isset($row['pagepath'])) {
            $pagePath = $row['pagepath'];
        } else if(isset($row['spurl'])) {
            $pagePath = $row['spurl'];
        }  
        //$tSql = $sql;
        //Clear varnish cache
        if($pid && $table === 'pages') {
            if($pid > 0) {
                //We have to clear cache of parent page as well
                $sql = "SELECT DISTINCT UDC.spurl, PC.pagepath
                    FROM pages AS node
                    LEFT JOIN tx_realurl_pathcache AS PC ON node.uid = PC.page_id
                    LEFT JOIN tx_realurl_urldecodecache AS UDC ON node.uid = UDC.page_id
                    WHERE node.uid = $pid
                ";
                //$tSql .= $sql;
                $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
                $row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);
                
                if(isset($row['pagepath'])) {
                    $pagePath = $row['pagepath'];
                } else if(isset($row['spurl'])) {
                    $pagePath = $row['spurl'];
                }  
                
                //Clear varnish cache
                if($domain && $pagePath) {
                    $wholePath = str_replace('//','/', $domain . '/' . $pagePath);
                    $this->banDown('http://' . $wholePath);
                    $this->banDown('http://' . $wholePath);
                    //$this->fillCache('http://' . $wholePath);
                } else if($domain) {
                    $wholePath = str_replace('//','/', $domain);
                    $this->banDown('http://' . $wholePath);
                    //$this->fillCache('http://' . $wholePath);
                }
            }
        } else if($domain && $pagePath) {
            $wholePath = str_replace('//','/', $domain . '/' . $pagePath);
            $this->ban('http://' . $wholePath);
            //$this->fillCache('http://' . $wholePath);
        } else if($domain) {
            $wholePath = str_replace('//','/', $domain);
            $this->ban('http://' . $wholePath);
            //$this->fillCache('http://' . $wholePath);
        }
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $wholePath . $tSql, 'crdate' => time()));
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
    }
    
    
    function clearVarnishCacheForPath($uid)
    {
        $sql = "SELECT CONCAT('http://localhost/', file_path, file_name) AS wholePath FROM tx_dam WHERE uid = " . intval($uid);
        $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
        $row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);
        $wholePath = $row['wholePath'];
        if($wholePath) {
            $this->banPath($wholePath);
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
    }
    
   /* 
    function purge($pageUrl)
    {
	try {
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
	    curl_exec($curl);
            
            curl_close($curl);
	} catch(Exception $e) {
            echo 'no no';	
        }
    }
    */
    
    private function fillCache($pageUrl)
    {
        try {
            $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($curl);
        } catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
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
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
    
    
    function banDown($pageUrl)
    {
        try {
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BANDOWN");
            $res = curl_exec($curl);
	} catch(Exception $e) {
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $pageUrl, 'crdate' => time()));
	}
    }
    
    
    function banDomain($pageUrl)
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