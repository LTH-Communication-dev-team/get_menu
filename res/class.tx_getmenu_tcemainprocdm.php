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
        //$pagepath = tx_pagepath_api::getPagePath($_params['uid_page']);
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => print_r($_params, true), 'crdate' => time()));
        $domain = t3lib_BEfunc::firstDomainRecord(t3lib_BEfunc::BEgetRootLine($_params['uid_page']));
        
        $this->createTSFE($_params['uid_page']);

			$cObj = t3lib_div::makeInstance('tslib_cObj');
			/* @var $cObj tslib_cObj */
			$typolinkConf = array(
				'parameter' => $_params['uid_page']
			);

			$url = $cObj->typoLink_URL($typolinkConf);
                        echo '???????????????????';
                        echo $url;
 
        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => $domain . $url, 'crdate' => time()));
        $uid_page = $_params['uid_page'];
        $get_menuObj = new get_menu_functions;
        $pagePath = '';
	if(isset($_params['cacheCmd']) && $_params['cacheCmd'] == 'all') {
	    $get_menuObj->clearAllVarnishCache();
	} else {
	    if($_params['table']==='pages') {
                //Check if new page has been created directly under parent

               /* $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid,lft,rgt', 'pages', 'uid='.$uid_page);
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
                } else */if(isset($_params['uid_page'])) {
                    $get_menuObj->clearVarnishCacheForPage($_params['uid_page']);
                }
	    } else if($_params['table']=='tt_content') {
		//content has been added or updated ...
                $get_menuObj->clearVarnishCacheForPage($uid_page);
	    } else if($_params['table']=='tx_dam') {
                //User is clearing cache for specific page
                //$get_menuObj->clearVarnishCacheForPage($_params['cacheCmd']);
            } else if(is_numeric($_params['cacheCmd'])) {
                //User is clearing cache for specific page
                $get_menuObj->clearVarnishCacheForPage($_params['cacheCmd']);
            }
	}
    }
    
    protected function createTSFE($pageId) {
        $GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $pageId, '');

        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->getCompressedTCarray();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();

        // Set linkVars, absRefPrefix, etc
        TSpagegen::pagegenInit();
    }
        
}