<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

//***************************************************DEAL WITH AUTO FE-COOKIE (from cacheinfo*********************************************
require_once(t3lib_extMgm::extPath('get_menu').'hooks/userauth.php');
$TYPO3_CONF_VARS['FE']['dontSetCookie'] = TRUE;    // we don't wantt set the fe_user cookie by default
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'][] = 'user_Tx_Cacheinfo_Hooks_Userauth->writeLoginSessionCookie';


//***************************************************HANDLING UPLOAD OF FILES**************************************************************
// Uploads in fileadmin/
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'EXT:get_menu/hooks/class.user_fileupload_hooks.php:user_fileUpload_hooks';
// Uploads in uploads/
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'EXT:get_menu/hooks/class.user_fileupload_hooks.php:user_fileUpload_hooks';

//************************************************CLEARCACHEPOSTPROCESS*********************************************************************
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:get_menu/res/class.tx_getmenu_tcemainprocdm.php:tx_getmenu_tcemainprocdm';
require_once(t3lib_extMgm::extPath('get_menu').'/res/class.tx_getmenu_tcemainprocdm.php');
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'tx_getmenu_tcemainprocdm->clearCachePostProc';


//************************************************PROCESSCMDMAP******************************************************************************
//$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:get_menu/hooks/class.processcmdmap_postprocess.php:user_processCmdmap_postProcess';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:get_menu/hooks/class.processcmdmap_postprocess.php:user_processCmdmap_postProcess';


//hook in fe_login to fix redirect issue with cookies and varnish
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'][] = 'tx_getmenu_tcemainprocdm->initFEuser';


//************************************************DELETE FE-COOKIE ON LOGOUT
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'][] = 'tx_getmenu_tcemainprocdm->deleteFeBeCookie';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'][] = 'tx_getmenu_tcemainprocdm->deleteFeCookie';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = 'tx_getmenu_tcemainprocdm->logout_redirect';

//$TYPO3_CONF_VARS['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = 'user_get_menu_hooks_clearcachemenu';
  

//***********************************************TASKS FOR TYPO3 SCEDULER*******************************************************************
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_getmenu_crawl'] = array(
	'extension'        => 'get_menu',
	'title'            => 'Crawl',
	'description'      => '',
	'additionalFields' => '',
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_getmenu_tree_transformer'] = array(
	'extension'        => 'get_menu',
	'title'            => 'Tree transformer',
	'description'      => '',
	'additionalFields' => '',
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_getmenu_part_transformer_1'] = array(
	'extension'        => 'get_menu',
	'title'            => 'Part of tree transformer 1',
	'description'      => '',
	'additionalFields' => '',
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_getmenu_part_transformer_2'] = array(
	'extension'        => 'get_menu',
	'title'            => 'Part of tree transformer 2',
	'description'      => '',
	'additionalFields' => '',
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_getmenu_part_transformer_3'] = array(
	'extension'        => 'get_menu',
	'title'            => 'Part of tree transformer 3',
	'description'      => '',
	'additionalFields' => '',
);


//***********************************************AJAX
$TYPO3_CONF_VARS['FE']['eID_include']['get_menu'] = 'EXT:get_menu/res/ajax.php'; //New