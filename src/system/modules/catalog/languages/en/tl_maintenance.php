<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * The Catalog extension allows the creation of multiple catalogs of custom items,
 * each with its own unique set of selectable field types, with field extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the 
 * data in each catalog.
 * 
 * PHP version 5
 * @copyright	CyberSpectrum 2011
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Catalog
 * @license		LGPL 
 * @filesource
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsRebuild']  = array('Tag fields', 'Please select all the tag fields you want to have the lookup table rebuilt for.');

/*
 * Headlines
 */
$GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsUpdate'] = 'Update tag field lookup tables';
$GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsSubmit'] = 'Rebuild tag fields';

$GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsNote']     = 'Please wait for the page to load completely before you proceed!';
$GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsLoading']  = 'Please wait while the tag fields are being rebuilt.';
$GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsComplete'] = 'The tag fields have been rebuilt. You can now proceed.';


?>