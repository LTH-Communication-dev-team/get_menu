<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

/*\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'lth.' . $_EXTKEY,
    'Pi1',
    array(
        'Gallery' => 'show, list, category',
    ),
    // non-cacheable actions
    array(
        'Gallery' => '',
    )
);*/

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'lth.' . $_EXTKEY
);

//***************************************************DEAL WITH AUTO FE-COOKIE (from cacheinfo*********************************************
/*require_once(t3lib_extMgm::extPath('get_menu').'hooks/userauth.php');
$TYPO3_CONF_VARS['FE']['dontSetCookie'] = TRUE;    // we don't wantt set the fe_user cookie by default
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'][] = 'user_Tx_Cacheinfo_Hooks_Userauth->writeLoginSessionCookie';
*/

//************************************************CLEARCACHEPOSTPROCESS*********************************************************************
//$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:get_menu/res/class.tx_getmenu_tcemainprocdm.php:tx_getmenu_tcemainprocdm';
//require_once(t3lib_extMgm::extPath('get_menu').'/res/class.tx_getmenu_tcemainprocdm.php');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'LTH\\get_menu\\Hooks\\ProcessCmdmap';

//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'LTH\\get_menu\\Hooks\\ProcessCmdmap';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'LTH\\get_menu\\Hooks\\ProcessCmdmap->clearCachePostProc';


//$TYPO3_CONF_VARS['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = 'Snowflake\\Varnish\\Hooks\\ClearCacheMenu';


//hook in fe_login to fix redirect issue with cookies and varnish
//$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'][] = 'tx_getmenu_tcemainprocdm->initFEuser';


//************************************************DELETE FE-COOKIE ON LOGOUT
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'][] = 'tx_getmenu_tcemainprocdm->deleteFeBeCookie';
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'][] = 'tx_getmenu_tcemainprocdm->deleteFeCookie';
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = 'tx_getmenu_tcemainprocdm->logout_redirect';

//$TYPO3_CONF_VARS['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = 'user_get_menu_hooks_clearcachemenu';

switch (TYPO3_MODE) {
	case 'FE':
		// Hooks
		$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'][] = 'LTH\\get_menu\\Hooks\\Frontend->sendHeader';
		break;
}

//***********************************************AJAX
$TYPO3_CONF_VARS['FE']['eID_include']['get_menu'] = 'EXT:get_menu/res/ajax.php'; //New

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    'TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher'
);

$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd,
    'lth\\get_menu\\Hooks\\FileMutationSlot',
    'postFileAdd'
);
