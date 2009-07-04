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
 * Language file for table tl_catalog_fields (en).
 *
 * PHP version 5
 * @copyright  Martin Komara 2007 
 * @author     Martin Komara 
 * @package    CatalogModule 
 * @license    GPL 
 * @filesource
 */


/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_catalog_items']['import'] = array('CSV import', 'Import data to the catalog from a CSV file');
$GLOBALS['TL_LANG']['tl_catalog_items']['export'] = array('CSV export', 'Export items data to a CSV file');
$GLOBALS['TL_LANG']['tl_catalog_items']['source'] = array('File source', 'Please choose the CSV file you want to import from the files directory.');
$GLOBALS['TL_LANG']['tl_catalog_items']['dataPerCycle'] = array('Data imports per cycle', 'Enter the amount of data rows to import per cycle.');
$GLOBALS['TL_LANG']['tl_catalog_items']['removeData'] = array('Remove existing data', 'Select to remove all existing data in the database, before importing.');

/**
 * Reference
 */

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_catalog_items']['new']    = array('New item', 'Create new item.');
$GLOBALS['TL_LANG']['tl_catalog_items']['edit']   = array('Edit item', 'Edit item ID %s');
$GLOBALS['TL_LANG']['tl_catalog_items']['copy']   = array('Copy item', 'Copy item ID %s');
$GLOBALS['TL_LANG']['tl_catalog_items']['cut']   = array('Move item', 'Move item ID %s');
$GLOBALS['TL_LANG']['tl_catalog_items']['delete'] = array('Delete item', 'Delete item ID %s');
$GLOBALS['TL_LANG']['tl_catalog_items']['show']   = array('Item details', 'Show details of item ID %s');
$GLOBALS['TL_LANG']['tl_catalog_items']['editheader'] = array('Edit item type', 'Edit the item type');


?>
