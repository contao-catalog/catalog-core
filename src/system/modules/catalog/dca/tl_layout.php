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
 * This is the data container array for table tl_catalog_fields.
 *
 * PHP version 5
 * @copyright  CyberSpectrum 2009
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    Catalog 
 * @license    GPL 
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