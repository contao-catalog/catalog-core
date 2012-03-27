<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * The Catalog extension allows the creation of multiple catalogs of custom items,
 * each with its own unique set of selectable field types, with field extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each catalog.
 *
 * PHP version 5
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Catalog
 * @license		LGPL
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
		'upgrade'			=> array('CatalogUpgrade', 'upgrade'),
		'maintenance'		=> array('CatalogMaintenance', 'compile'),

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
					'sqlDefColumn' => 'int(10) unsigned NULL default NULL',
				),
			'select' => array
				(
					'typeimage'    => 'system/modules/catalog/html/select.gif',
					'fieldDef'     => array
						(
							'inputType' => 'tableTree',
							'eval'      => array('fieldType' => 'radio')
						),
					'sqlDefColumn' => "int(10) NOT NULL default 0",
				),
			'tags' => array
				(
					'typeimage'    => 'system/modules/catalog/html/tags.gif',
					'fieldDef'     => array
						(
							'inputType' => 'tableTree',
							'eval'      => array('fieldType' => 'checkbox', 'multiple' => true),
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
							'eval'      => array('rgxp' => 'url')
						),
					'sqlDefColumn' => "varchar(400) NOT NULL default ''",
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
			'calc' => array
				(
					'typeimage'    => 'system/modules/catalog/html/calc.gif',
					'fieldDef'     => array
						(
							'inputType' => 'text',
							'eval'			=> array('disabled'=>true, 'style'=>'padding-right:25px;background: #eee url(system/modules/catalog/html/calc.gif) no-repeat 99% center;'),
						),
					'sqlDefColumn' => "varchar(255) NOT NULL default ''",
				),
		),
	'typesCheckboxSelectors' => array('checkbox'),
	'typesOptionSelectors' => array('select', 'tags'),
	'typesFilterFields' => array('number', 'decimal', 'text', 'longtext', 'date', 'select', 'tags', 'checkbox'),
	'typesMatchFields' => array('text', 'alias', 'number', 'decimal', 'longtext', 'date', 'select', 'tags', 'checkbox', 'url', 'file'),
	'typesEditFields' => array('text', 'alias', 'number', 'decimal', 'longtext', 'date', 'select', 'tags', 'checkbox', 'url', 'file'),
	'typesLinkFields' => array('text', 'alias', 'number', 'decimal', 'longtext', 'date', 'select', 'tags', 'checkbox', 'file'),
	'typesReferenceFields' => array('text', 'alias', 'number', 'decimal', 'longtext', 'date', 'select', 'tags', 'checkbox', 'url', 'file', 'calc'),
	'typesCatalogFields' => array('text', 'alias', 'longtext', 'number', 'decimal', 'date', 'select', 'tags', 'checkbox', 'url', 'file', 'calc'),
	'typesRSSFields' => array('text', 'alias', 'longtext'),
	'typesWizardFields' => array('date', 'calc', 'url'),
		
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
		'cataloglist'			=> 'ModuleCatalogList',
		'catalogreader'			=> 'ModuleCatalogReader',
		'catalogfeatured'		=> 'ModuleCatalogFeatured',
		'catalogrelated'		=> 'ModuleCatalogRelated',
		'catalogreference'		=> 'ModuleCatalogReference',
		'catalognavigation'		=> 'ModuleCatalogNavigation',
		'catalognotify'			=> 'ModuleCatalogNotify',
		'catalogedit'			=> 'ModuleCatalogEdit',
	);

/**
 * Register hook to add items to the indexer
 */
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('CatalogExt', 'getSearchablePages');

/**
 * Register hook to preserve feeds 
 */
$GLOBALS['TL_HOOKS']['removeOldFeeds'][] = array('CatalogExt', 'removeOldFeedsHOOK');

/**
 * Register hook to add rss feeds to the layout
 */
$GLOBALS['TL_HOOKS']['parseFrontendTemplate'][] = array('CatalogExt', 'parseFrontendTemplate');

// register hook to inject our catalog names into the comments module as source.
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('CatalogExt', 'addCatalogsToComments');
$GLOBALS['TL_HOOKS']['listComments'][] = array('CatalogExt', 'listComments');
$GLOBALS['TL_HOOKS']['isAllowedToEditComment'][] = array('CatalogExt', 'isAllowedToEditComment');

// additional regular expressions
$GLOBALS['TL_HOOKS']['addCustomRegexp'][] = array('Catalog', 'catalogRgxp');

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

$GLOBALS['TL_CONFIG']['catalog']['csvDelimiter']	= ',';
$GLOBALS['TL_CONFIG']['catalog']['safeCheck']		= array('/', '\'');
$GLOBALS['TL_CONFIG']['catalog']['safeReplace']	= array('-slash-', '-apos-');
$GLOBALS['TL_CONFIG']['catalog']['keywordsInvalid'] = array( ' ','.','?','!',';',':','-','/','&','"','\'','�');
$GLOBALS['TL_CONFIG']['catalog']['keywordCount'] = 15;

array_insert($GLOBALS['BE_FFL'], 15, array
(
	'CatalogMultiWidget'    => 'CatalogMultiWidget'
));
?>