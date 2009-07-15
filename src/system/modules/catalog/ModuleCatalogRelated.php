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
 * Class ModuleCatalogRelated
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com>
 * @package    ModuleCatalogRelated
 *
 */

class ModuleCatalogRelated extends ModuleCatalog
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogrelated';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG RELATED ###';

			return $objTemplate->parse();
		}

		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;

		$this->catalog_visible = deserialize($this->catalog_visible);
		$this->catalog_related = deserialize($this->catalog_related);

		return parent::generate();
	}
	
// archived!=1 AND price>{{catalog::price}}-100 AND price<{{catalog::price}}+100 AND date<{{date}}+7*24*60*60
	
	protected function compile()
	{
		$objCatalogType = $this->Database->prepare("SELECT aliasField,titleField FROM tl_catalog_types WHERE id=?")
										->execute($this->catalog);

		$strAlias = $objCatalogType->aliasField ? " OR ".$objCatalogType->aliasField."=?" : '';		
		
		$objCatalog = $this->Database->prepare("SELECT * FROM ".$this->strTable." WHERE (id=?".$strAlias.")")
										->limit(1)
										->execute($this->Input->get('items'), $this->Input->get('items'));

		if ($objCatalog->numRows)
		{
			// Query Catalog
			$limit = is_numeric($this->catalog_limit)? $this->catalog_limit : 0;
		
			$arrCatalog = $objCatalog->fetchAllAssoc();
			$arrCatalog = $arrCatalog[0];
			$strWhere = $this->replaceCatalogTags($this->catalog_where, $arrCatalog);
			$strOrder = ($this->catalog_random_disable) ? trim($this->catalog_order) : "RAND()";
	
			// Add Related Query
			$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'];
			$strRelated = array();
			$arrRelated = array();

			foreach($this->catalog_related as $related)
			{
				//if tags split into multiple FIND_IN_SET();
				// optimized by c.schiffler, we want to have matching on all tags instead of only one match to have a relation.
				switch ($fieldConf[$related]['eval']['catalog']['type'])
				{
					case 'tags':
						$tags = split(',', $objCatalog->$related);
						$tmpRelated = array();
/*						foreach ($tags as $tag)
						{
							$tmpRelated[] = 'FIND_IN_SET(?,'.$related.')>0';
							$arrRelated[] = $tag;
						}
						$strRelated[] = '('.join(' OR ', $tmpRelated).')';
*/
						foreach ($tags as $tag)
						{
							$tmpRelated[] = '(FIND_IN_SET(?,'.$related.')>0)';
							$arrRelated[] = $tag;
						}
						$strRelated[] = '(('.join('+', $tmpRelated).')>=' . $this->catalog_related_tagcount . ')';
						break;

					default:
						$strRelated[] = $related.'=?';
						$arrRelated[] = $objCatalog->$related;

				}
			}
			
			// convert to string
			$strRelated = join(' AND ', $strRelated);
	
			// Run Query
			$objCatalogStmt = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE pid=? AND id!=?".($strRelated ? " AND ".$strRelated : "").($strWhere ? " AND ".$strWhere : "").(strlen($strOrder) ? " ORDER BY ".$strOrder : ""));
			
			if ($limit > 0)
			{
				$objCatalogStmt->limit($limit);
			}
			
			$objCatalog = $objCatalogStmt->execute(array_merge(array($this->catalog, $objCatalog->id ), $arrRelated));
	
	
			$this->Template->catalog = $this->parseCatalog($objCatalog, true, $this->catalog_template, $this->catalog_visible);
				
			// Template variables
			$this->Template->visible = $this->catalog_visible;

		}

	}


}

?>