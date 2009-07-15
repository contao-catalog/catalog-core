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
 * @copyright  Martin Komara, Thyon Design, CyberSpectrum 2008, 2009
 * @author     Martin Komara, John Brand <john.brand@thyon.com>
 *             Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    Catalog 
 * @license    GPL 
 * @filesource
 */


/**
 * Back-end module
 */

$GLOBALS['BE_MOD']['content']['catalog'] = array
(
		'tables'			=> array('tl_catalog_types', 'tl_catalog_fields', 'tl_catalog_items'),
		'icon'				=> 'system/modules/catalog/html/icon.gif',
		'import'			=> array('Catalog', 'importCSV'),
		'export'			=> array('Catalog', 'exportItems'),
		'comments'		=> array('CatalogComments', 'run'),
		'upgrade'			=> array('CatalogUpgrade', 'upgrade'),

		// Added by c.schiffler to allow custom editors to register themselves.
		'fieldTypes' => array
		(
			'text' => array
				(
					'typeimage'    => 'system/modules/catalog/html/text.gif',
					'fieldDef'     => array('inputType' => 'text'),
					'sqlDefColumn' => "varchar(255) NOT NULL default ''",
				),
			'alias' => array
				(
					'typeimage'    => 'system/modules/catalog/html/alias.gif',
					'fieldDef'     => array
						(
							'inputType' => 'text',
							'eval'      => array('rgxp'=>'alnum', 'unique'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>64),
						),
					'sqlDefColumn' => "varchar(64) NOT NULL default ''",
				),
			'longtext' => array
				(
					'typeimage'    => 'system/modules/catalog/html/longtext.gif',
					'fieldDef'     => array
						(
							'inputType' => 'textarea'
						),
					'sqlDefColumn' => "text NULL",
				),
			'number' => array
				(
					'typeimage'    => 'system/modules/catalog/html/number.gif',
					'fieldDef'     => array
						(
							'inputType' => 'text',
							'eval'      => array('rgxp' => 'digit')
						),
					'sqlDefColumn' => 'int(10) NULL default NULL',
				),
			'decimal' => array
				(
					'typeimage'    => 'system/modules/catalog/html/decimal.gif',
					'fieldDef'     => array
						(
							'inputType' => 'text',
							'eval'      => array('rgxp' => 'digit')
						),
					'sqlDefColumn' => 'double NULL default NULL',
				),
			'date' => array
				(
					'typeimage'    => 'system/modules/catalog/html/date.gif',
					'fieldDef'     => array
						(
							'inputType' => 'text',
						),
					'sqlDefColumn' => "int(10) unsigned NOT NULL default '0'",
				),
			'select' => array
				(
					'typeimage'    => 'system/modules/catalog/html/select.gif',
					'fieldDef'     => array
						(
							'inputType' => 'select',
						),
					'sqlDefColumn' => "int(10) NOT NULL default 0",
				),
			'tags' => array
				(
					'typeimage'    => 'system/modules/catalog/html/tags.gif',
					'fieldDef'     => array
						(
							'inputType' => 'checkbox',
							'eval'      => array('multiple' => true)
						),
					'sqlDefColumn' => "text NULL",
				),
			'checkbox' => array
				(
					'typeimage'    => 'system/modules/catalog/html/checkbox.gif',
					'fieldDef'     => array
						(
							'inputType' => 'checkbox',
						),
					'sqlDefColumn' => "char(1) NOT NULL default ''",
				),
			'url' => array
				(
					'typeimage'    => 'page.gif',
					'fieldDef'     => array
						(
							'inputType' => 'text',
						),
					'sqlDefColumn' => "varchar(255) NOT NULL default ''",
				),
			'file' => array
				(
					'typeimage'    => 'iconPLAIN.gif',
					'fieldDef'     => array
						(
							'inputType' => 'fileTree',
							'eval'      => array('files' => true),
						),
					'sqlDefColumn' => "text NULL",
				),
			'taxonomy' => array
				(
					'typeimage'    => 'system/modules/taxonomy/html/icon.gif',
					'fieldDef'     => array
						(
							'inputType' => 'tableTree',
							'eval'      => array('fieldType' => 'radio', 'tableColumn'=> 'tl_taxonomy.name'),
						),
					'sqlDefColumn' => "text NULL",
				),
		),
	'typesFilterFields' => array('number', 'decimal', 'text', 'longtext', 'date', 'select', 'tags', 'checkbox'),
	'typesMatchFields' => array('text', 'alias', 'number', 'decimal', 'longtext', 'date', 'select', 'tags', 'checkbox', 'url', 'file'),
	'typesEditFields' => array('text', 'alias', 'number', 'decimal', 'longtext', 'date', 'select', 'tags', 'checkbox', 'url'), /* TODO: add file support later */
	'typesLinkFields' => array('text', 'alias', 'number', 'decimal', 'longtext', 'date', 'select', 'tags', 'checkbox', 'file'),
	'typesReferenceFields' => array('text', 'alias', 'number', 'decimal', 'longtext', 'date', 'select', 'tags', 'checkbox', 'url', 'file', 'taxonomy'),
	'typesCatalogFields' => array('text', 'alias', 'longtext', 'number', 'decimal', 'date', 'select', 'tags', 'checkbox', 'url', 'file', 'taxonomy'),
	'typesRSSFields' => array('text', 'alias', 'longtext'),
		
	// End of addition by c.schiffler to allow custom editors to register themselves.
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
		'catalogfilter'			=> 'ModuleCatalogFilter',
		'cataloglist'				=> 'ModuleCatalogList',
		'catalogreader'			=> 'ModuleCatalogReader',
		'catalogfeatured'		=> 'ModuleCatalogFeatured',
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
 * Register hook to preserve feeds 
 */
$GLOBALS['TL_HOOKS']['removeOldFeeds'][] = array('CatalogExt', 'removeOldFeeds');

/**
 * Register hook to add rss feeds to the layout
 */
$GLOBALS['TL_HOOKS']['parseFrontendTemplate'][] = array('CatalogExt', 'parseFrontendTemplate');

/**
 * Cron jobs
 */
$GLOBALS['TL_CRON']['daily'][] = array('CatalogExt', 'generateFeeds');  

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