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
		'oncreate_callback'	          => array
			(
				array('Catalog', 'initializeCatalogItems'),
			),
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
	/**
	 * Update the RSS-feed
	 */
	public function generateFeed()
	{
		$this->import('CatalogExt');
		$this->CatalogExt->generateFeed(CURRENT_ID);
	}
}

?>