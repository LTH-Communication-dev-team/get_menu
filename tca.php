<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_getmenu_cache'] = array(
	'ctrl' => $TCA['tx_getmenu_cache']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'menuobject'
	),
	'feInterface' => $TCA['tx_getmenu_cache']['feInterface'],
	'columns' => array(
		'menuobject' => array(		
			'exclude' => 0,		
			'label' => 'LLL:EXT:get_menu/locallang_db.xml:tx_getmenu_cache.menuobject',		
			'config' => array(
				'type' => 'text',
				'cols' => '30',	
				'rows' => '5',
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'menuobject;;;;1-1-1')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>