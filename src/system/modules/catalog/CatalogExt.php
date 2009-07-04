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
 * Class CatalogExt 
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com> 
 * @package    Controller
 */
class CatalogExt extends Frontend
{

	/**
	 * Add news items to the indexer
	 * @param array
	 * @param integer
	 * @return array
	 */
	public function getSearchablePages($arrPages, $intRoot=0)
	{
		$arrRoot = array();

		if ($intRoot > 0)
		{
			$arrRoot = $this->getChildRecords($intRoot, 'tl_page', true);
		}

		$objArchive = $this->Database->prepare("SELECT id,tableName,jumpTo,aliasField,searchable,searchCondition,titleField FROM tl_catalog_types")
									 ->execute();

		// Walk through each archive
		while ($objArchive->next())
		{
			if (!$objArchive->searchable)
			{
				continue;
			}

			if (is_array($arrRoot) && count($arrRoot) > 0 && !in_array($objArchive->jumpTo, $arrRoot))
			{ 
				continue;
			}
						
			// Get default URL
			$objParent = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=? AND (start=? OR start<?) AND (stop=? OR stop>?) AND published=?")
										->limit(1)
										->execute($objArchive->jumpTo, '', time(), '', time(), 1);

			if ($objParent->numRows < 1)
			{
				continue;
			}

			$domain = $this->Environment->base;
			$objParent = $this->getPageDetails($objParent->id);

			if (strlen($objParent->domain))
			{
				$domain = ($this->Environment->ssl ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
			}

			$strUrl = $domain . $this->generateFrontendUrl($objParent->row(), '/items/%s');

			// Get items
			$where = $objArchive->searchCondition;
			$objCatalog = $this->Database->prepare("SELECT * FROM ".$objArchive->tableName." WHERE pid=?".(strlen($where)? " AND ".$where : "")." ORDER BY tstamp DESC")
										 ->execute($objArchive->id);

			// Add items to the indexer
			while ($objCatalog->next())
			{
				$arrPages[] = $this->getLink($objArchive->jumpTo, $objCatalog, $strUrl, $objArchive->aliasField);
			}
		}

		return $arrPages;
	}


	/**
	 * Return the link of a news article
	 * @param object
	 * @param string
	 * @return string
	 */
	private function getLink($jumpTo, Database_Result $objCatalog, $strUrl, $alias)
	{
		$objParent = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
									->limit(1)
									->execute($jumpTo);

		$item = ($alias && strlen($objCatalog->$alias) && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $objCatalog->$alias : $objCatalog->id;

		return sprintf($strUrl, $item);
	}
}

?>