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
 * The Catalog extension allows the creation of multiple catalogs of custom items,
 * each with its own unique set of selectable field types, with field extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the 
 * data in each catalog.
 * 
 * PHP version 5
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Catalog
 * @license		LGPL 
 * @filesource
 */


/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_catalog_comments']['name']      = array('Name', 'Please enter the real name of the author.');
$GLOBALS['TL_LANG']['tl_catalog_comments']['email']     = array('E-mail address', 'Please enter the author\'s e-mail address (will not be published).');
$GLOBALS['TL_LANG']['tl_catalog_comments']['website']   = array('Website', 'Please enter an optional website address.');
$GLOBALS['TL_LANG']['tl_catalog_comments']['comment']   = array('Comment', 'Please enter the comment.');
$GLOBALS['TL_LANG']['tl_catalog_comments']['published'] = array('Published', 'Only published comments will be shown on the website.');
$GLOBALS['TL_LANG']['tl_catalog_comments']['date']      = array('Date', 'Please enter the comment date.');


/**
 * Reference
 */
$GLOBALS['TL_LANG']['tl_catalog_comments']['approved'] = 'Approved';
$GLOBALS['TL_LANG']['tl_catalog_comments']['pending']  = 'Awaiting moderation';


/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_catalog_comments']['edit']       = array('Edit comment', 'Edit comment ID %s');
$GLOBALS['TL_LANG']['tl_catalog_comments']['delete']     = array('Delete comment', 'Delete comment ID %s');
$GLOBALS['TL_LANG']['tl_catalog_comments']['show']       = array('Comment details', 'Show details of comment ID %s');
$GLOBALS['TL_LANG']['tl_catalog_comments']['editheader'] = array('Edit archive', 'Edit the current archive');

?>