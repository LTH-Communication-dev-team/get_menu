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
        if (is_object($GLOBALS['TSFE']->fe_user)) {
            $GLOBALS['TSFE']->fe_user->cookieId = 'dummy';
        }
    }
    
    
    function clearCachePostProc($_params, $pObj)
    {
/*
 * Array
(
    [cacheCmd] => 1342
)
 */        
        //$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_devlog', array('msg' => print_r($_params,true), 'crdate' => time()));
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
	    } else if(is_numeric($_params['cacheCmd'])) {
                //User is clearing cache for specific page
                $uid_page = $_params['cacheCmd'];
                $domain = t3lib_BEfunc::firstDomainRecord(t3lib_BEfunc::BEgetRootLine($uid_page));
                $get_menuObj->clearVarnishCacheForPage($domain, $uid_page, 'pages');
            }
	}
    }
}