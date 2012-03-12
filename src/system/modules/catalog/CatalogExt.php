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
 * Class CatalogExt
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Controller
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

		$objArchive = $this->fetchCatalogType();

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
			$this->import('String');
			$where = $this->String->decodeEntities($objArchive->searchCondition);
			if(strlen($objArchive->publishField))
			{
				$where.=(strlen($where)? ' AND ':'').$objArchive->publishField.'=1';
			}
			$objCatalog = $this->fetchCatalogItems($objArchive, $where);

			// Add items to the indexer
			while ($objCatalog->next())
			{
				$arrPages[] = $this->getLink($objCatalog, $strUrl, $objArchive->aliasField);
			}
		}

		return $arrPages;
	}

	/**
	 * Fetch information about the catalog if an id is provided or all catalogs if no id was supplied
	 * @param int $intId (optional) the id of the catalogtype to be fetched.
	 * @return Database_Result
	 */
	protected function fetchCatalogType($intId=0)
	{
		return $this->Database->prepare('SELECT * FROM tl_catalog_types'.($intId?' WHERE id=?':''))
									 ->execute($intId);
	}

	/**
	 * Gets all Catalog items from a catalog
	 * @param Database_Result $objCatalogType which owns the items
	 * @param string $strWhere
	 * @param string $strOrderBy optional
	 * @param int $intLimit optional
	 * @return Database_Result with all published items
	 */
	protected function fetchCatalogItems(Database_Result $objCatalogType, $strWhere, $strOrderBy='tstamp DESC', $intLimit=0)
	{
		$stmt = $this->Database->prepare('SELECT *
										FROM ' . $objCatalogType->tableName .
										' WHERE pid=? ' . (strlen($strWhere) ? " AND " . $strWhere : "") .
										(strlen($strOrderBy) ? " ORDER BY " . $strOrderBy : ""));
		if ($intLimit > 0)
			$stmt->limit($intLimit);
		return $stmt->execute($objCatalogType->id);
	}

	/**
	 * Return the link of an item
	 * @param Database_Result $objCatalog
	 * @param string $strUrl with %s for the item alias or id
	 * @param string $strAliasField name of the alias field
	 * @return string
	 */
	private function getLink(Database_Result $objCatalog, $strUrl, $strAliasField)
	{
		if ($strAliasField && strlen($objCatalog->$strAliasField)
		&& !$GLOBALS['TL_CONFIG']['disableAlias'])
			$item = $objCatalog->$strAliasField;
		else
			$item = $objCatalog->id;
		return sprintf($strUrl, $item);
	}

	/**
	 * Generate a XML file and save it to the root directory
	 * @param object
	 */
	protected function generateFiles(Database_Result $arrCatalog)
	{
		// If we do not have a table name, we can not work.
		// This should not happen under normal circumstances but as issue #57 proves, it can happen
		// when activating RSS before saving the catalog.
		if(!strlen($arrCatalog->tableName))
			return;

		$time = time();
		$strType = ($arrCatalog->feedFormat == 'atom') ? 'generateAtom' : 'generateRss';
		$strLink = strlen($arrCatalog->feedBase) ? $arrCatalog->feedBase : $this->Environment->base;
		$strFile = $arrCatalog->feedName;

		$objFeed = new Feed($strFile);

		$objFeed->link = $strLink;
		$objFeed->title = $arrCatalog->feedTitle;
		$objFeed->description = $arrCatalog->description;
		$objFeed->language = $arrCatalog->language;
		$objFeed->published = $arrCatalog->tstamp;

		// Get default URL
		$objParent = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
									->limit(1)
									->execute($arrCatalog->jumpTo);

		$strUrl = $this->generateFrontendUrl($objParent->fetchAssoc(), '/items/%s');

		// Get items
		$this->import('String');
		$where = $this->String->decodeEntities($objArchive->searchCondition);
		if(strlen($arrCatalog->publishField))
		{
			$where.=(strlen($where)? ' AND ':'').$arrCatalog->publishField.'=1';
		}
		$datefield = (strlen($arrCatalog->datesource) ? $arrCatalog->datesource : 'tstamp');
		$objArticle = $this->fetchCatalogItems($arrCatalog, $where, $datefield . ' DESC', $arrCatalog->maxItems);
		// Parse items
		while ($objArticle->next())
		{
			$objItem = new FeedItem();
			$objItem->title = $objArticle->{$arrCatalog->titleField};
			$objItem->description = (strlen($arrCatalog->source) ? $objArticle->{$arrCatalog->source} : '');
			$objItem->link = $strLink . $this->getLink($arrCatalog->jumpTo, $objArticle, $strUrl, $arrCatalog->aliasField);
			$objItem->published = $objArticle->$datefield;
			$objFeed->addItem($objItem);
		}

		// Create file
		$objRss = new File($strFile . '.xml');
		$objRss->write($this->replaceInsertTags($objFeed->$strType()));
		$objRss->close();
	}

	/**
	 * Update a particular RSS feed
	 * @param integer
	 */
	public function generateFeed($intId)
	{
		$objCatalog = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=? AND makeFeed=?")
									  ->limit(1)
									  ->execute($intId, 1);

		if ($objCatalog->numRows < 1)
		{
			return;
		}

		$objCatalog->feedName = strlen($objCatalog->alias) ? $objCatalog->alias : 'catalog' . $objCatalog->id;

		// Delete XML file
		if ($this->Input->get('act') == 'delete')
		{
			$this->import('Files');
			$this->Files->delete($objCatalog->feedName . '.xml');
		}
		// Update XML file
		else
		{
			$this->generateFiles($objCatalog);
			$this->log('Generated catalog feed "' . $objCatalog->feedName . '.xml"', 'Catalog generateFeed()', TL_CRON);
		}
	}

	/**
	 * Update all RSS feeds
	 */
	public function generateFeeds()
	{
		$objCatalog = $this->Database->prepare("SELECT id FROM tl_catalog_types WHERE makeFeed=?")
									  ->limit(1)
									  ->execute(1);
		if ($objCatalog->numRows < 1)
		{
			return;
		}
		while ($objCatalog->next())
		{
			$this->generateFeed($objCatalog->id);
		}
	}

	/**
	 * gets called by hook to prevent RSS feeds from deletion by cronjob
	 * @return array
	 */
	public function removeOldFeedsHOOK($blnReturn=false)
	{
		$objCatalog = $this->Database->prepare("SELECT id, alias FROM tl_catalog_types WHERE makeFeed=?")
									  ->limit(1)
									  ->execute(1);
		if ($objCatalog->numRows < 1)
		{
			return array();
		}
			$tmp=array();
		while ($objCatalog->next())
		{
			$tmp[]=strlen($objCatalog->alias) ? $objCatalog->alias : 'catalog' . $objCatalog->id;
		}
		$this->log('Protected catalog feeds ' . implode(', ', $tmp) . ' from deletion"', 'Catalog removeOldFeeds()', TL_CRON);
		return $tmp;
	}

	/**
	 * Get a page layout and return it as database result object.
	 * This is a copy from PageRegular, see comments in parseFrontendTemplate() below for the reason why this is here.
	 * @param integer $intId id of the requested layout.
	 * @return DatabaseResult
	 */
	protected function getPageLayout($intId)
	{
		if (version_compare(VERSION.'.'.BUILD, '2.9.0', '>='))
		{
			$objLayout = $this->Database->prepare('SELECT l.*, t.templates FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id WHERE l.id=?')
										->limit(1)
										->execute($intId);
			// Fallback layout
			if ($objLayout->numRows < 1)
			{
				$objLayout = $this->Database->prepare('SELECT l.*, t.templates FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id WHERE l.fallback=?')
											->limit(1)
											->execute(1);
			}
		}
		else // pre Contao phase, no themes available.
		{
			$objLayout = $this->Database->prepare('SELECT * FROM tl_layout WHERE id=?')
										->limit(1)
										->execute($intId);
			// Fallback layout
			if ($objLayout->numRows < 1)
			{
				$objLayout = $this->Database->prepare('SELECT * FROM tl_layout WHERE fallback=?')
											->limit(1)
											->execute(1);
			}
		}
		// Return the defined value  of NULL to allow the calling function to cope with this issue.
		if ($objLayout->numRows < 1)
		{
			return NULL;
		}
		return $objLayout;
	}

	/**
	 * get called by hook to inject all RSS feeds for the current layout into the template
	 * @param string
	 */
	public function parseFrontendTemplate($strBuffer, $strTemplate)
	{
		// when called in Backend - this happens within editing of articles for example as we are getting called from a content element.
		if(TL_MODE!='FE')
			return $strBuffer;

		// I totally admit it. This function sucks big time. It is ugly, a hack, but non the less the only possibility to get this working.
		// We can't even check to only parse templates starting with 'fe_', as fe_page will get parsed before(!) the hook get's called.
		// So we will store our calculated value into the global and hope that no one will ever want to use this.
		// We also hope, that Leo will never ever change his for each inclusion in the page rendering.
		// And we definately hope that all of this will get more easy and Leo will add a hook.
		if(!isset($GLOBALS['TL_HEAD']['CATALOGFEED']))
		{
			global $objPage;
			$GLOBALS['TL_HEAD']['CATALOGFEED']='';
			// here we are getting dirty, we have to import the page layout as we have no other way to get the layout from it.
			// I know it does exist already as we are being called from it but hey, we got no Hook in PageRegular::createStyleSheets
			// and therefore have to suffer the hard way... :(
			$objLayout = $this->getPageLayout($objPage->layout);
			// if the layout has not been found, we skip generating the RSS feeds, see issue #2549
			if ($objLayout)
			{
				$catalogfeeds = deserialize($objLayout->catalogfeeds);
				// Add catalogfeeds
				if (is_array($catalogfeeds) && count($catalogfeeds) > 0)
				{
					$objFeeds = $this->Database->execute("SELECT * FROM tl_catalog_types WHERE id IN(" . implode(',', $catalogfeeds) . ")");
					while($objFeeds->next())
					{
						$objFeeds->feedName = strlen($objFeeds->alias) ? $objFeeds->alias : 'catalog' . $objFeeds->id;
						$base = strlen($objFeeds->feedBase) ? $objFeeds->feedBase : $this->Environment->base;
						$GLOBALS['TL_HEAD']['CATALOGFEED'] .= '<link rel="alternate" href="' . $base . $objFeeds->feedName . '.xml" type="application/' . $objFeeds->feedFormat . '+xml" title="' . $objFeeds->description . '" />' . "\n";
					}
				}
			}
		}
		// Return buffer no matter if we added something to the global array or not.
		// We simply to not want to tamper with it.
		return $strBuffer;
	}

	/**
	 * get called by hook to inject all catalog names into the comments module.
	 * @param string
	 */
	public function addCatalogsToComments($strName)
	{
		if($strName!='tl_comments')
			return;
		$objCatalogs = $this->Database->execute('SELECT id, tableName, name, titleField FROM tl_catalog_types;');
		while($objCatalogs->next())
		{
			$GLOBALS['TL_LANG']['tl_comments'][$objCatalogs->tableName] = $objCatalogs->name;
			$GLOBALS['TL_CATALOG_ITEMS'][$objCatalogs->tableName]['title'] = $objCatalogs->titleField;
			$GLOBALS['TL_CATALOG_ITEMS'][$objCatalogs->tableName]['id'] = $objCatalogs->id;
		}
	}

	/**
	 * get called by hook to show the item title in the comments module.
	 * @param string
	 */
	public function listComments($arrRow)
	{
		// did we already look up this item? if so we can take this value now otherwise look it up.
		if($GLOBALS['TL_CATALOG_ITEMS'][$arrRow['source']]['items'][$arrRow['parent']])
			return $GLOBALS['TL_CATALOG_ITEMS'][$arrRow['source']]['items'][$arrRow['parent']];
		$titleField=$GLOBALS['TL_CATALOG_ITEMS'][$arrRow['source']]['title'];
		if(!$titleField)
			return '';
		$objItem = $this->Database->prepare('SELECT id,'.$titleField.' FROM '.$arrRow['source'].' WHERE id=?;')
									->execute($arrRow['parent']);
		if($objItem->numRows)
		{
			$GLOBALS['TL_CATALOG_ITEMS'][$arrRow['source']]['items'][$objItem->id] = $objItem->$titleField;
			return ' (<a href="contao/main.php?do=catalog&amp;table=tl_catalog_items&amp;act=edit&amp;id=' . $objItem->id . '&amp;catid=' . $GLOBALS['TL_CATALOG_ITEMS'][$arrRow['source']]['id'] . '">' . $objItem->$titleField . '</a>)';
		}
	}

	public function isAllowedToEditComment($intParent, $strSource)
	{
		$this->import('BackendUser', 'User');
		if($GLOBALS['TL_CATALOG_ITEMS'][$strSource]['id'])
		{
			if ($this->User->hasAccess('catalog', 'modules'))
			{
				if($this->User->hasAccess($GLOBALS['TL_CATALOG_ITEMS'][$strSource]['id'], 'catalogs'))
				{
					return true;
				}
			}
		}
		return false;
	}
}

?>