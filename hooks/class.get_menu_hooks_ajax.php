<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012  Andri Steiner  <support@snowflake.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * This class contains required hooks which are called by TYPO3
 *
 * @author	Tomas Havner
 * @package	TYPO3
 * @subpackage	get_menu
 */

require_once(dirname(__FILE__).'/../classes/class.get_menu_functions.php');

class user_get_menu_hooks_ajax
{


	/**
	 * Ban all pages from varnish cache.
	 */
	public function clearCacheForBranch()
	{
	    tslib_eidtools::connectDB();
	    $sql = 'CALL sp_get_menu_get_uids_for_empty_cache('.intval($this->id).')';
	    //echo $sql;
	    $res = $GLOBALS['TYPO3_DB']->sql_query($sql);
	    $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	    if(isset($row['uidarray'])) {
		$uidArray = explode(',',$row['uidarray']);
		$get_menu_functions = new get_menu_functions();
		$get_menu_functions->clearTypo3Cache($uidArray);
	    }
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/get_menu/hooks/class.tx_varnish_hooks_ajax.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/get_menu/hooks/class.tx_varnish_hooks_ajax.php']);
}
?>