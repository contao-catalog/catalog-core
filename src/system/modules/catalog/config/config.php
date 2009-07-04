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
 * This is the catalog configuration file.
 *
 * PHP version 5
 * @copyright  Martin Komara, Thyon Design 2008
 * @author     Martin Komara, John Brand <john.brand@thyon.com> 
 * @package    CatalogModule 
 * @license    GPL 
 * @filesource
 */


/**
 * Back-end module
 */

$GLOBALS['BE_MOD']['content']['catalog'] = array
(
    'tables'       	=> array('tl_catalog_types', 'tl_catalog_fields', 'tl_catalog_items'),
    'icon'         	=> 'system/modules/catalog/html/icon.gif',
    'import'				=> array('Catalog', 'importCSV'),
    'export'				=> array('Catalog', 'exportItems'),
		'comments' 			=> array('CatalogComments', 'run'),
    'upgrade'				=> array('CatalogUpgrade', 'upgrade'),
);

if (TL_MODE == 'BE')
{
	$GLOBALS['TL_CSS'][] = 'system/modules/catalog/html/style.css'; 
}

/**
 * Front-end modules
 */

$GLOBALS['FE_MOD']['catalog'] = array
	(
		'catalogfilter' 		=> 'ModuleCatalogFilter',
		'cataloglist' 			=> 'ModuleCatalogList',
		'catalogreader'			=> 'ModuleCatalogReader',
		'catalogfeatured'		=> 'ModuleCatalogFeatured',
		'catalognavigation'	=> 'ModuleCatalogNavigation',
		'catalogrelated'		=> 'ModuleCatalogRelated',
		'catalogreference'	=> 'ModuleCatalogReference',
		'catalognavigation'	=> 'ModuleCatalogNavigation',
		'catalognotify'			=> 'ModuleCatalogNotify',
		'catalogedit'				=> 'ModuleCatalogEdit',
	);


/**
 * Register hook to add items to the indexer
 */
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('CatalogExt', 'getSearchablePages');


/**
 * HOOK Permissions
 */
 
$GLOBALS['TL_PERMISSIONS'][] = 'catalogs'; 


/**
 * CONFIG Parameters
 */

$GLOBALS['TL_CONFIG']['catalog']['safeCheck']		= array('/', '\'');
$GLOBALS['TL_CONFIG']['catalog']['safeReplace']	= array('-slash-', '-apos-');


?>