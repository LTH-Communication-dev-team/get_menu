<?php
/*
 * Register necessary class names with autoloader
 */

return array(
    'tx_getmenu_baseurl_due' => t3lib_extMgm::extPath('get_menu') .'tasks/class.tx_getmenu_baseurl_due.php',
    'tx_getmenu_crawl' => t3lib_extMgm::extPath('get_menu') .'tasks/class.tx_getmenu_crawl.php',
    'tx_getmenu_tree_transformer' => t3lib_extMgm::extPath('get_menu') . 'tasks/class.tx_getmenu_tree_transformer.php',
    'user_get_menu_hooks_ajax' => t3lib_extMgm::extPath('get_menu') . 'hooks/class.get_menu_hooks_ajax.php',
    'get_menu_functions' => t3lib_extMgm::extPath('get_menu') . 'classes/class.get_menu_functions.php',
);