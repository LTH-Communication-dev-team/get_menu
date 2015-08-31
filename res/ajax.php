<?php

// Exit, if script is called directly (must be included via eID in index_ts.php)
if (!defined ('PATH_typo3conf')) die ('Could not access this script directly!');

// Initialize FE user object:
//$feUserObj = tslib_eidtools::initFeUser();

// Connect to database:
tslib_eidtools::connectDB();

$uid = t3lib_div::_GP("uid");
$action = t3lib_div::_GP("action");
$sid = t3lib_div::_GP("sid");

switch($action) {
    case 'getLoginBox':
	$content = getLoginBox();
	break;
    case 'clearallvarnishcache':
	$content = clearAllVarnishCash();
	break;
    case 'getRightContentInMegamenu':
	$content = getRightContentInMegamenu($uid);
	break;
}

echo json_encode($content);

function getLoginBox()
{
    return 'sucker';
}

function clearAllVarnishCash()
{
    try {
	$curl = curl_init('~/');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
	$res = curl_exec($curl);
    } catch(Exception $e) {

    }
}

function getRightContentInMegamenu($uid)
{
    $content = array();
    $bodytext = null;
    try {
	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('t.bodytext', 'pages p JOIN tt_content t ON p.uid=t.pid', 'p.uid='.intval($uid) . ' AND t.header=\'megamenu\'');
	$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	$bodytext = $row['bodytext'];
    } catch(Exception $e) {

    }
    $content['bodytext'] = $bodytext;
    $content['uid'] = $uid;
    return $content;
}