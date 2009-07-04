<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com> 
 * @package    CatalogExtension 
 * @license    LGPL
 * @filesource
 */


/**
 * Class ModuleCatalogFilter
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com>
 * @package    CatalogFilter
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