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
 * Language file for modules (en).
 *
 * PHP version 5
 * @copyright  Martin Komara 2007, Thyon Design 2008
 * @author     Martin Komara, John Brand <john.brand@thyon.com> 
 * @package    Catalog 
 * @license    LGPL
 * @filesource 
 */


/**
 * Back end modules
 */
$GLOBALS['TL_LANG']['MOD']['catalog'] = array('Catalog', 'This module allows you to manage catalog of items.');


/**
 * Front end modules
 */
$GLOBALS['TL_LANG']['FMD']['catalog'] = 'Catalog';
$GLOBALS['TL_LANG']['FMD']['catalogfilter'] = array('Catalog Filter', 'This module display a catalog filter with selected filter fields, range and search options. A Catalog List module is required on the same page.');
$GLOBALS['TL_LANG']['FMD']['cataloglist'] = array('Catalog List', 'This module shows a list of  catalog items filtered by a catalog filter module.');
$GLOBALS['TL_LANG']['FMD']['catalogreader'] = array('Catalog Reader', 'This module shows a single catalog item.');
$GLOBALS['TL_LANG']['FMD']['catalogfeatured'] = array('Catalog Featured', 'This module displays a catalog featured list of  items in random order. You can enter custom where, number of items, and disable random in favour of your own order clause, e.g. tstamp DESC (edit date descending).');
$GLOBALS['TL_LANG']['FMD']['catalogrelated'] = array('Catalog Related', 'This module displays a catalog related list of  items in random order. Select the matching related fields and enter custom where, number of items, and disable random in favour of your own order clause, e.g. tstamp DESC (edit date descending).');
$GLOBALS['TL_LANG']['FMD']['catalogreference'] = array('Catalog Reference', 'This module displays a list of items (e.g. paintings) from a catalog (child) where the selected reference field (e.g. artist) matches in the currently viewed catalog item (parent item).');

$GLOBALS['TL_LANG']['FMD']['catalognavigation'] = array('Catalog Navigation', 'This module displays navigation tree, using a selected category field, with optional setting to include links to items in that category.');
$GLOBALS['TL_LANG']['FMD']['catalognotify'] = array('Catalog Notify', 'This module presents a form with selectable fields, which the user completes, upon which an admin notification is sent via e-mail.');

$GLOBALS['TL_LANG']['FMD']['catalogedit'] = array('Catalog Edit', 'This module allows you to edit single catalog item in the front-end.');

?>