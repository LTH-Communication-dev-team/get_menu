<?php
class tx_getmenu_crawl extends tx_scheduler_Task {
	var $feGroupArray = array();
	var $feUserArray = array();
	var $titleCategoriesArray = array();
	
    function execute()
    {
	$executionSucceeded = FALSE;

	//$this->configuration = Tx_Solr_Util::getSolrConfigurationFromPageId($this->site->getRootPageId());
	tslib_eidtools::connectDB();
	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('domainName', 'sys_domain', 'hidden=0', '', '', '');
	while ($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
	    $domainName = $row['domainName'];
	    try {
		if (substr($domainName, -1) !== '/') {
		    $domainName = $domainName.'/';
		}
		    if($this->checkUrlExist($domainName.'swedish')) {
			//echo '103'.$fp;
			$html = file_get_contents($domainName.'swedish');
		    } 
		    if($this->checkUrlExist($domainName)) {
			//echo '106';
			$html = file_get_contents($domainName);
		    }
		    if($this->checkUrlExist($domainName.'english')) {
			//echo '111';
			$html = file_get_contents($domainName.'english');
		    } 
		} catch(Exception $e) {
		    //echo $e.'no no!';
		}
	}
	/*print '<pre>';
	    print_r($constantsArray);
	    print '</pre>';*/
	$GLOBALS['TYPO3_DB']->sql_free_result($res);

	$executionSucceeded = TRUE;

	return $executionSucceeded;
    }
    
    function checkUrlExist($url)
    {
	$array = get_headers($url);
	$string = $array[0];
	if(strpos($string,"200")) {
	    return true;
	} else {
	    return false;
	}
    }
}