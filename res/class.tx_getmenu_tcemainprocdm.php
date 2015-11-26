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
        /*
         * Array
        (
            [table] => tt_news
            [uid] => 12775
            [uid_page] => 73417
            [TSConfig] => Array
                (
                    [clearCacheCmd] => 99495,47661,58763,91959,86369,4,12158,91203,97371,86729,102777,93051,88221,102283,100083,94955,106937,101325,92983,83403,83765,86785,91889,95729,97337,95725,12159,12160,101487,101551,101757,101887,101965
                )

        )
        */
        
        $get_menuObj = new get_menu_functions;
        $pagePath = '';
	if(isset($_params['cacheCmd']) && $_params['cacheCmd'] == 'all') {
	    $get_menuObj->clearAllVarnishCache();
	} else {
	    if($_params['table']==='pages') {
                $uid_page = $_params['uid_page'];
                $domain = t3lib_BEfunc::firstDomainRecord(t3lib_BEfunc::BEgetRootLine($uid_page));
                if(isset($_params['uid_page'])) {
                    $get_menuObj->clearVarnishCacheForPage($domain, $uid_page, 'pages');
                }
	    } else if($_params['table']=='tt_content') {
		//content has been added or updated ...
                $uid_page = $_params['uid_page'];
                $domain = t3lib_BEfunc::firstDomainRecord(t3lib_BEfunc::BEgetRootLine($uid_page));
                $get_menuObj->clearVarnishCacheForPage($domain, $uid_page, 'tt_content');
	    } else if($_params['table']=='tx_dam') {
                //User is clearing cache for specific page
                $uid_page = $_params['uid_page'];
                $domain = t3lib_BEfunc::firstDomainRecord(t3lib_BEfunc::BEgetRootLine($uid_page));
                $get_menuObj->clearVarnishCacheForPath($_params['uid']);
            } else if(is_numeric($_params['cacheCmd'])) {
                //User is clearing cache for specific page
                $uid_page = $_params['cacheCmd'];
                $domain = t3lib_BEfunc::firstDomainRecord(t3lib_BEfunc::BEgetRootLine($uid_page));
                $get_menuObj->clearVarnishCacheForPage($domain, $uid_page, 'pages');
            }
	}
    }
}