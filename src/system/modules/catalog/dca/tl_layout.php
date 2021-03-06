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
 * Table tl_layout 
 */
$GLOBALS['TL_DCA']['tl_layout']['fields']['catalogfeeds'] = array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_layout']['catalogfeeds'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_layout_catalog', 'getCatalogfeeds'),
			'eval'                    => array('multiple'=>true)
		);

$GLOBALS['TL_DCA']['tl_layout']['palettes']['default']=str_replace('calendarfeeds', 'calendarfeeds,catalogfeeds' , $GLOBALS['TL_DCA']['tl_layout']['palettes']['default']);
$GLOBALS['TL_DCA']['tl_layout']['palettes']['1cl']=str_replace('calendarfeeds', 'calendarfeeds,catalogfeeds' , $GLOBALS['TL_DCA']['tl_layout']['palettes']['1cl']);
$GLOBALS['TL_DCA']['tl_layout']['palettes']['2cll']=str_replace('calendarfeeds', 'calendarfeeds,catalogfeeds' , $GLOBALS['TL_DCA']['tl_layout']['palettes']['2cll']);
$GLOBALS['TL_DCA']['tl_layout']['palettes']['2clr']=str_replace('calendarfeeds', 'calendarfeeds,catalogfeeds' , $GLOBALS['TL_DCA']['tl_layout']['palettes']['2clr']);
$GLOBALS['TL_DCA']['tl_layout']['palettes']['3cl']=str_replace('calendarfeeds', 'calendarfeeds,catalogfeeds' , $GLOBALS['TL_DCA']['tl_layout']['palettes']['3cl']);

class tl_layout_catalog extends Backend
{
	/**
	 * Return all catalogs with XML feeds
	 * @return array
	 */
	 public function getCatalogfeeds() {
		$objFeed = $this->Database->execute("SELECT id, Name as title FROM tl_catalog_types WHERE makeFeed=1");
		if ($objFeed->numRows < 1)
		{
			return array();
		}
		$return = array();
		while ($objFeed->next())
		{
			$return[$objFeed->id] = $objFeed->title;
		}
		return $return;  
	}
}

?>