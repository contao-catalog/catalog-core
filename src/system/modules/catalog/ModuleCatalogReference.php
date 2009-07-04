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
 * Class ModuleCatalogReference
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com>
 * @package    ModuleCatalogReference
 *
 */

class ModuleCatalogReference extends ModuleCatalog
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogreference';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG REFERENCE ###';

			return $objTemplate->parse();
		}

		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;

		$this->catalog_visible = deserialize($this->catalog_visible);
		$this->catalog_reference = deserialize($this->catalog_reference);

		return parent::generate();
	}
	
	
	protected function compile()
	{
		// get parent catalog aliasfield
		$objCatalogSelected = $this->Database->prepare("SELECT aliasField,titleField,tableName FROM tl_catalog_types WHERE id=?")
										->execute($this->catalog_selected);

		$strAlias = $objCatalogSelected->aliasField ? " OR ".$objCatalogSelected->aliasField."=?" : '';		
		
		$objCatalog = $this->Database->prepare("SELECT * FROM ".$objCatalogSelected->tableName." WHERE (id=?".$strAlias.")")
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
			$strReference = array();
			$arrReference = array();

			$valueRef = $arrCatalog[$this->catalog_reference];

			if ($this->catalog_match != 'id')
			{
				// retrieve the match catalog's field type
				$objCatalogMatch = $this->Database->prepare("SELECT colName, type FROM tl_catalog_fields WHERE pid=? AND colName=?")
											->execute($this->catalog, $this->catalog_match);
				$fieldMatch = ($objCatalogMatch->numRows && $objCatalogMatch->type) ? $objCatalogMatch->type : '';
			}
			else
			{
				$fieldMatch = 'id';
			}

			if ($this->catalog_reference != 'id')
			{
				// retrieve the reference catalog's field type
				$objCatalogRef = $this->Database->prepare("SELECT colName, type FROM tl_catalog_fields WHERE pid=? AND colName=?")
											->execute($this->catalog_selected, $this->catalog_reference);

				$fieldRef = ($objCatalogRef->numRows && $objCatalogRef->type) ? $objCatalogRef->type : '';
			}
			else
			{
				$fieldRef = 'id';
			}

			//if tags split into multiple FIND_IN_SET();
			if ($fieldRef == ''  || $fieldMatch == '' || ($fieldRef == 'tags' && $fieldMatch == 'tags'))
			{
				return '';
			}

			if ($fieldRef == 'tags' || $fieldMatch == 'tags')
			{
				if ($fieldRef == 'tags')
				{
					$arrRef = split(',', $valueRef);
					foreach ($arrRef as $value)
					{
						if (trim($value)) 
						{
							$strReference[] = $this->catalog_match.'=?';
							$arrReference[] = $value;
						}
					}
				}

				if ($fieldMatch == 'tags')
				{
					$strReference[] = 'FIND_IN_SET(?,'.$this->catalog_match.')>0';
					$arrReference[] = $valueRef;
				}

			}
			else 
			{
				$strReference[] = $this->catalog_match.'=?';
				$arrReference[] = $valueRef;
			}
			
			// convert to string
			$strReference = join(' OR ', $strReference);

			// Run Query
			$objCatalogStmt = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE pid=? ".($strReference ? " AND ".$strReference : "").($strWhere ? " AND ".$strWhere : "").(strlen($strOrder) ? " ORDER BY ".$strOrder : ""));

		
			if ($limit > 0)
			{
				$objCatalogStmt->limit($limit);
			}
			
			$objCatalog = $objCatalogStmt->execute(array_merge(array($this->catalog), $arrReference));
	
	
			$this->Template->catalog = $this->parseCatalog($objCatalog, true, $this->catalog_template, $this->catalog_visible);
				
			// Template variables
			$this->Template->visible = $this->catalog_visible;

		}

	}

	protected function replaceCatalogTags($strValue, $arrCatalog)
	{
		$strValue = trim($strValue);

		// Replace tags in messageText and messageHtml
		$tags = array();
		preg_match_all('/{{[^}]+}}/i', $strValue, $tags);

		// Replace tags of type {{catalog::fieldname}}
		foreach ($tags[0] as $tag)
		{
			$elements = explode('::', trim(str_replace(array('{{', '}}'), array('', ''), $tag)));

			// {{catalog::fieldname}}
			if (strtolower($elements[0]) == 'catalog')
			{
				$key = $elements[1];
				if (array_key_exists($key, $arrCatalog))
				{
					$strValue = str_replace($tag, str_replace("\n", "<br>", $arrCatalog[$key]), $strValue);
				}
			}
		}
		

		// Replace standard insert tags
		if (strlen($strValue))
		{
			$strValue = $this->replaceInsertTags($strValue);
		}

		return $strValue;
	} 


}

?>