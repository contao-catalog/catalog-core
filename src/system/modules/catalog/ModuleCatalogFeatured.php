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
 * Class ModuleCatalogFeatured
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com>
 * @package    ModuleCatalogFeatured
 *
 */

class ModuleCatalogFeatured extends ModuleCatalog
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogfeatured';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG FEATURED ###';

			return $objTemplate->parse();
		}

		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;

		$this->catalog_visible = deserialize($this->catalog_visible);

		return parent::generate();
	}
	
	
	
	protected function compile()
	{
		// Query Catalog
		$limit = is_numeric($this->catalog_limit)? $this->catalog_limit : 0;
	
		$strWhere = trim($this->replaceInsertTags($this->catalog_where));
		// Bugfix c.schiffler. The name is catalog_order not list_sort :(
		// $strOrder = ($this->catalog_random_disable) ? trim($this->list_sort) : "RAND()";
		$strOrder = ($this->catalog_random_disable) ? trim($this->catalog_order) : "RAND()";

		// Run Query
		$objCatalogStmt = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE pid=?".($strWhere ? " AND ".$strWhere : "").(strlen($strOrder) ? " ORDER BY ".$strOrder : ""));
		
		if ($limit > 0)
		{
			$objCatalogStmt->limit($limit);
		}
		
		$objCatalog = $objCatalogStmt->execute($this->catalog);


		$this->Template->catalog = $this->parseCatalog($objCatalog, true, $this->catalog_template, $this->catalog_visible);
			
		// Template variables
		$this->Template->visible = $this->catalog_visible;

	}

}

?>