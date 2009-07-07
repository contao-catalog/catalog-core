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
 * @package    Catalog 
 * @license    LGPL 
 * @filesource
 */


/**
 * Class ModuleCatalogNavigation
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com>
 * @package    ModuleCatalogNavigation
 *
 */

class ModuleCatalogNavigation extends ModuleCatalog
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalognavigation';


	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG NAVIGATION ###';

			return $objTemplate->parse();
		}

		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;

		$this->catalog_visible = deserialize($this->catalog_visible);
		$strBuffer = parent::generate();
		return strlen($this->Template->items) ? $strBuffer : '';
	}
	
	
	
	protected function compile()
	{

		$this->Template->skipId = 'skipNavigation_' . $this->id;
		$this->Template->request = ampersand($this->Environment->request, ENCODE_AMPERSANDS);
		$this->Template->skipNavigation = specialchars($GLOBALS['TL_LANG']['MSC']['skipNavigation']);

		// prepare and run
		$this->Template->items = $this->renderCatalogNavigation(0);
		
	}


}

?>