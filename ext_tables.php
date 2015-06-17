<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$tempColumns = array(
	'lft' => array(		
		'exclude' => 0,		
		'label' => 'LLL:EXT:get_menu/locallang_db.xml:pages.lft',		
		'config' => array(
			'type'     => 'input',
			'size'     => '4',
			'max'      => '4',
			'eval'     => 'int',
			'checkbox' => '0',
			'range'    => array(
				'upper' => '100000000',
				'lower' => '0'
			),
			'default' => null
		)
	),
	'rgt' => array(		
		'exclude' => 0,		
		'label' => 'LLL:EXT:get_menu/locallang_db.xml:pages.rgt',		
		'config' => array(
			'type'     => 'input',
			'size'     => '4',
			'max'      => '4',
			'eval'     => 'int',
			'checkbox' => '0',
			'range'    => array(
				'upper' => '100000000',
				'lower' => '0'
			),
			'default' => null
		)
	),
	'root' => array(		
		'exclude' => 0,		
		'label' => 'LLL:EXT:get_menu/locallang_db.xml:pages.root',		
		'config' => array(
			'type'     => 'input',
			'size'     => '4',
			'max'      => '4',
			'eval'     => 'int',
			'checkbox' => '0',
			'range'    => array(
				'upper' => '100000000',
				'lower' => '0'
			),
			'default' => null
		)
	),
);


t3lib_div::loadTCA('pages');
t3lib_extMgm::addTCAcolumns('pages',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('pages','lft;;;;1-1-1, rgt, root');
?>