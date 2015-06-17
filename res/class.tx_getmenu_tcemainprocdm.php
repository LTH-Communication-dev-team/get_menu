<?php
if (!defined ('PATH_typo3conf')) die ('Could not access this script directly!');

class tx_getmenu_tcemainprocdm {
    
    function logout_redirect($_params, $pObj)
    {
        if(get_class($pObj) == 't3lib_beUserAuth') {
            unset($_COOKIE['be_typo_user']);
            setcookie('be_typo_user', null, time() - 3600,'/');
            $_POST['redirect'] = '../';
	}
    }
    
    
    function debug($input)
    {
	print '<pre>';
	print_r($input);
	print '</pre>';
    }
    
    
    function deleteFeCookie($_params, $pObj)
    {
	if($pObj->usergroup_table == 'fe_groups' && $_GET['logintype'] == 'logout') {
	    if(!is_array($pObj->user) && isset($_COOKIE['fe_typo_user'])) {
                unset($_COOKIE['fe_typo_user']);
		setcookie('fe_typo_user', null, time() - 3600,'/');
	    }
	}	
    }

    
    //Add a fake value to $GLOBALS['TSFE']->fe_user->cookieId. This is needed for fe login redirect to work when fe_typo_user cookie is deleted to enable varnish
    function initFEuser()
    {
	$GLOBALS['TSFE']->fe_user->cookieId = 'dummy';
    }
    
    
    function clearCachePostProc($_params, $pObj)
    {
	//var_dump($pObj);
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $_params['cacheCmd']. ','.$_params['table']. ','.$_params['uid_page'], 'crdate' => time()));

        $uid_page = $_params['uid_page'];
        $get_menuObj = new get_menu_functions;
        $pagePath = '';
	if(isset($_params['cacheCmd']) && $_params['cacheCmd'] == 'all') {
	    $get_menuObj->clearAllVarnishCache();
	} else {
	    if($_params['table']=='pages') {
                //Check if new page has been created directly under parent

                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid,lft,rgt', 'pages', 'uid='.$uid_page);
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                if(!isset($row['lft']) && !isset($row['rgt'])) {
                    $newParentId = $row['pid'];
                    $sql = "SELECT lft, root FROM pages WHERE uid = $newParentId";
                    $res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
                    $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                    if(isset($row['lft'])) {
                        $lft = $row['lft'];
                        $newRoot =  $row['root'];
                        $sql = "UPDATE pages SET rgt = rgt + 2 WHERE rgt > $lft";
                        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
                        $sql = "UPDATE pages SET lft = lft + 2 WHERE lft > $lft";
                        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
                        $sql = "UPDATE pages SET lft = $lft + 1, rgt = $lft + 2, root = $newRoot, pid = $newParentId WHERE uid = $uid_page";
                        $GLOBALS['TYPO3_DB'] -> sql_query($sql);
                        $GLOBALS['TYPO3_DB']->sql_free_result($res);
                        $get_menuObj->clearVarnishCacheForDomain($uid_page);
                        //Clear hamburger cache
                        $rootId = $get_menuObj->getRootId($uid_page);
                        if($rootId) {
                            $get_menuObj->clearMenuCache($rootId);
                        }
                    }
                }
                
	    } else if($_params['table']=='tt_content') {
		//content has been added or updated ...
                $get_menuObj->clearVarnishCacheForPage($uid_page);
	    } else if(is_numeric($_params['cacheCmd'])) {
                //User is clearing cache for specific page
                //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $uid_page, 'crdate' => time()));
                $get_menuObj->clearVarnishCacheForPage($_params['cacheCmd']);
            }
	}
    }
    
    
        /*function deleteBeCookie($_params, $pObj)
    {
	if($pObj->usergroup_table == 'be_groups') {
	    if(!is_array($pObj->user) && isset($_COOKIE['be_typo_user'])) {
		setcookie('be_typo_user', null, time() - 3600,'/');
	    }
	}	
    }*/
    
    /*function getRootId($uid_page)
    {
	tslib_eidtools::connectDB();
        $sql = "SELECT SUBSTRING_INDEX(GROUP_CONCAT(template.pid),',',-1) AS rootid
        FROM pages AS node
        JOIN pages AS parent
        LEFT JOIN sys_template template ON parent.uid=template.pid
        WHERE node.lft BETWEEN parent.lft AND parent.rgt
        AND node.uid = $uid_page
        ORDER BY node.lft;";
	$res = $GLOBALS['TYPO3_DB'] -> sql_query($sql);
	$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        if(isset($row['rootid'])) {
            return $row['rootid'];
        } else {
            return false;
        }
    }*/
    
  
    /*function clearTypo3Cache($pid, $cHash=false)
    {
	if(is_array($pid)) {
	    $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id IN (' . implode(',', $pid) . ')');
	    $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', 'page_id IN (' . implode(',', $pid) .')');
	} else {
	    $addWhere = $cHash ? ' and cHash = "' . $cHash . '"' : '';
	    $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id = ' . $pid . $addWhere);
	    $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', 'page_id = ' . $pid . $addWhere);
	}
    }*/
    
    
    /*function purge($pageUrl)
    {
	try {
	    $curl = curl_init($pageUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
	    $res = curl_exec($curl);
	} catch(Exception $e) {

	}
    }*/
    
    
    /*function getDomainUrl($pageId)
    {
	try {
	    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('domainName', 'sys_domain', 'pid='.intval($pageId), '', '', '0,1');
	    $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	    $domainName = $row['domainName'];
	    $GLOBALS['TYPO3_DB']->sql_free_result($res);
	    if($domainName) {
		$content = str_replace('//','/',$domainName . '/');
		return 'http://'.$content;
	    } else {
		return null;
	    }
	} catch(Exception $e) {
	    var_dump($e);
	}
    }*/
    
        
    /*function deleteFeBeCookie($_params, $pObj)
    {
	if($pObj->usergroup_table == 'be_groups') {
	    if(!is_array($pObj->user) && isset($_COOKIE['be_typo_user'])) {
                unset($_COOKIE['be_typo_user']);
		setcookie('be_typo_user', null, time() - 3600, '/');
	    }
	}
	if($pObj->usergroup_table == 'fe_groups') {
	    if(!is_array($pObj->user) && isset($_COOKIE['fe_typo_user'])) {
		setcookie('fe_typo_user', null, time() - 3600, '/');
	    }
	}	
    }*/
    
    
   /* function getPageUrl($pageId)
    {
	try {
	    //SELECT P.pagepath, S.domainName FROM tx_realurl_pathcache P JOIN sys_domain S ON S.pid = P.rootpage_id WHERE P.page_id = 1363;
	    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('P.pagepath,S.domainName', 'tx_realurl_pathcache P JOIN sys_domain S ON S.pid = P.rootpage_id', 'P.page_id='.intval($pageId), '', '', '0,1');
	    $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	    $pagePath = $row['pagepath'];
	    $domainName = $row['domainName'];
	    $GLOBALS['TYPO3_DB']->sql_free_result($res);
	    if($pagePath) {
		$content = str_replace('//','/',$domainName . '/' . $pagePath);
		return 'http://'.$content;
	    } else {
		return null;
	    }
	} catch(Exception $e) {
	    var_dump($e);
	}
    }
    
    */
     
    
    /*
     * SELECT 
  FROM WHERE 34)
     * function clearVarnish($baseurl, $pp)
    {
	if($pp) {
	    $ppArray = explode(',',$pp);
	    $ppArray = array_unique($ppArray);
	    foreach ($ppArray as $key => $value) {
		$curl = curl_init($baseurl.$value);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		$res = curl_exec($curl);
	    }
	    curl_close ($curl);
	}
    }*/
    
    
    /*function checkUrlExist($url)
    {
	$array = get_headers($url);
	$string = $array[0];
	if(strpos($string,"200")) {
	    return true;
	} else {
	    return false;
	}
    }*/
}