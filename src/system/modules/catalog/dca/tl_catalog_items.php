<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 *
 * The TYPOlight webCMS is an accessible web content management system that 
 * specializes in accessibility and generates W3C-compliant HTML code. It 
 * provides a wide range of functionality to develop professional websites 
 * including a built-in search engine, form generator, file and user manager, 
 * CSS engine, multi-language support and many more. For more information and 
 * additional TYPOlight applications like the TYPOlight MVC Framework please 
 * visit the project website http://www.typolight.org.
 *
 * This is the data container array for table tl_catalog_types.
 *
 * PHP version 5
 * @copyright  2008 Thyon Design 
 * @author     John Brand <john.brand@thyon.com>
 * @package    Catalog
 * @license    GPL 
 * @filesource
 */


/**
 * Table tl_catalog_items 
 */
$GLOBALS['TL_DCA']['tl_catalog_items'] = array
(

	// DC_Catalog container config
	'config' => array
	(
		'dataContainer'               => 'DynamicTable',
		'ptable'                      => 'tl_catalog_types',
		'switchToEdit'                => false,
		'enableVersioning'            => false,
		'oncreate_callback'						=> array
			(
				array('Catalog', 'initializeCatalogItems'),
			)
	),
	

	// Fields
	'fields' => array
	(
		'source' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_items']['source'],
			'eval'                    => array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'extensions'=>'csv')
		)
	)
		
	
);


class tl_catalog_items extends Backend
{
}

?>