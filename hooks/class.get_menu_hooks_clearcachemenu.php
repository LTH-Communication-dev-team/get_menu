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
 * @author	Andri Steiner  <support@snowflake.ch>
 * @package	TYPO3
 * @subpackage	tx_varnish
 */


class user_get_menu_hooks_clearcachemenu implements backend_cacheActionsHook {

	/**
	 * Add varnish cache clearing to clearcachemenu
	 *
	 * @param array $cacheActions
	 * @param array $optionValues
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		/** @var $LANG language */
		global $LANG;
		$title = $LANG->sL('LLL:EXT:get_menu/locallang.xml:be_clear_cache_menu_page_branch');
		$cacheActions[] = array(
			'id'    => 'varnish',
			'title' => $title,
			'href'  => 'ajax.php?ajaxID=get_menu::clearCacheForBranch',
			'icon'  => '<img src="/'.$GLOBALS['TYPO3_LOADED_EXT']['get_menu']['siteRelPath'].'ext_icon.gif" title="'.$title.'" alt="'.$title.'" />',
		);
	}

}

global $TYPO3_CONF_VARS;
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/get_menu/hooks/class.get_menu_hooks_clearcachemenu.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/get_menu/hooks/class.get_menu_hooks_clearcachemenu.php']);
}

?>
