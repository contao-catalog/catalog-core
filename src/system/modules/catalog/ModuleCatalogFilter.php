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
 * Class ModuleCatalogFilter
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
 *
 */
class ModuleCatalogFilter extends ModuleCatalog
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogfilter';


	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG FILTER ###';

			return $objTemplate->parse();
		}

		if (!strlen($this->catalog))
		{
			return '';
		}
		
		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;
		
		return parent::generate();
	}


	/**
	 * Generate module
	 */
	protected function compile()
	{

		$filter = $this->generateFilter();

		// Add Filter template
		$template = (strlen($this->catalog_filtertemplate) ? $this->catalog_filtertemplate : 'filter_default'); 
		$objTemplate = new FrontendTemplate($template);
		
		$objTemplate->clearall	= $filter['url'];
		$objTemplate->clearallText	= $GLOBALS['TL_LANG']['MSC']['clearFilter'];

		$objTemplate->url 			= $filter['url'];
		$objTemplate->action 		= $filter['action'];
		$objTemplate->table 		= $this->strTable;

		$objTemplate->widgets = $filter['widgets'];

		$arrHeadline = deserialize($this->catalog_filter_headline);
		$objTemplate->filter_headline = is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
		$objTemplate->filter_hl = is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';
		$objTemplate->filterOptions = $filter['filter'];

		$arrHeadline = deserialize($this->catalog_range_headline);
		$objTemplate->range_headline = is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
		$objTemplate->range_hl = is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';
		$objTemplate->rangeOptions = $filter['range'];

		$arrHeadline = deserialize($this->catalog_date_headline);
		$objTemplate->date_headline = is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
		$objTemplate->date_hl = is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';
		$objTemplate->dateOptions = $filter['date'];

		$arrHeadline = deserialize($this->catalog_search_headline);
		$objTemplate->search_headline = is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
		$objTemplate->search_hl = is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';
		$objTemplate->searchOptions = $filter['search'];

		$arrHeadline = deserialize($this->catalog_sort_headline);
		$objTemplate->sort_headline = is_array($arrHeadline) ? $arrHeadline['value'] : $arrHeadline;
		$objTemplate->sort_hl = is_array($arrHeadline) ? $arrHeadline['unit'] : 'h1';
		$objTemplate->sortOptions = $filter['sort'];
		$objTemplate->sortDropdown = $this->catalog_sort_dropdown;
		
		// set template as filter parameter
		$this->Template->filter = $objTemplate->parse();
		
	}
	
}

?>