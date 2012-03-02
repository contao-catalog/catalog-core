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
 * Class ModuleCatalogFilter
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
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

			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			if (version_compare(VERSION.'.'.BUILD, '2.9.0', '>='))
				$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
			else
				$objTemplate->href = 'typolight/main.php?do=modules&amp;act=edit&amp;id=' . $this->id;

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