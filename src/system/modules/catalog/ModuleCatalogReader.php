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
 * Class ModuleCatalogReader
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com>
 * @package    CatalogReader
 *
 */

class ModuleCatalogReader extends ModuleCatalog
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogreader';


	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG READER ###';

			return $objTemplate->parse();
		}

		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;

		$this->catalog_visible = deserialize($this->catalog_visible);

		return parent::generate();
	}


	/**
	 * Generate module
	 */
	protected function compile()
	{
		global $objPage;

		$this->Template->catalog = '';
		$this->Template->referer = $this->getReferer(ENCODE_AMPERSANDS);
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

		$objCatalogType = $this->Database->prepare("SELECT aliasField,titleField FROM tl_catalog_types WHERE id=?")
										->execute($this->catalog);


		$strAlias = $objCatalogType->aliasField ? " OR ".$objCatalogType->aliasField."=?" : '';		
		
		$objCatalog = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE (id=?".$strAlias.")")
										->limit(1)
										->execute($this->Input->get('items'), $this->Input->get('items'));

		if ($objCatalog->numRows < 1)
		{
			$this->Template->catalog = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['catalogItemInvalid'].'</p>';

			// Do not index the page
			$objPage->noSearch = 1;
			$objPage->cache = 0;

			// Send 404 header
			header('HTTP/1.0 404 Not Found');
			return;
		}
		
		$this->Template->catalog = $this->parseCatalog($objCatalog, false, $this->catalog_template, $this->catalog_visible);
		$this->Template->visible = $this->catalog_visible;

		// Overwrite page title
		if (strlen($objCatalogType->titleField)) 
		{
			$titleField = $objCatalogType->titleField;
			$objPage->pageTitle = $objCatalog->$titleField;
		}

		// Comments
		$this->processComments($objCatalog);
	
	}


}

?>