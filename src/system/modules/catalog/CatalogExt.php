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
 * @copyright  Thyon Design, CyberSpectrum 2008, 2009
 * @author     John Brand <john.brand@thyon.com>, Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    Catalog
 * @license    LGPL
 * @filesource
 */


/**
 * Class CatalogExt 
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com>, Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    Catalog
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
		// TODO: Why is this here queried? does not get used at all. Removed for now. (c.schiffler)
		//$objParent = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
		//							->limit(1)
		//							->execute($jumpTo);

		$item = ($alias && strlen($objCatalog->$alias) && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $objCatalog->$alias : $objCatalog->id;

		return sprintf($strUrl, $item);
	}


	/**
	 * Generate a XML file and save it to the root directory
	 * @param object
	 */
	protected function generateFiles(Database_Result $arrCatalog)
	{
		$time = time();
		$strType = ($arrCatalog->feedFormat == 'atom') ? 'generateAtom' : 'generateRss';
		$strLink = strlen($arrCatalog->feedBase) ? $arrCatalog->feedBase : $this->Environment->base;
		$strFile = $arrCatalog->feedName;

		$objFeed = new Feed($strFile);

		$objFeed->link = $strLink;
		$objFeed->title = $arrCatalog->title;
		$objFeed->description = $arrCatalog->description;
		$objFeed->language = $arrCatalog->language;
		$objFeed->published = $arrCatalog->tstamp;

		// Get default URL
		$objParent = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
									->limit(1)
									->execute($arrCatalog->jumpTo);

		$strUrl = $this->generateFrontendUrl($objParent->fetchAssoc(), '/items/%s');

		// Get items
		$where = $arrCatalog->searchCondition;
		$datefield=(strlen($arrCatalog->datesource) ? $arrCatalog->datesource : 'tstamp');
		$objArticleStmt = $this->Database->prepare("SELECT * FROM " . $arrCatalog->tableName . " WHERE pid=? ".(strlen($where)? " AND ".$where : "")." ORDER BY " . $datefield . " DESC");

		if ($arrCatalog->maxItems > 0)
		{
			$objArticleStmt->limit($arrArchive['maxItems']);
		}

		$objArticle = $objArticleStmt->execute($arrCatalog->id, $time, $time);

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
			$this->log('Generated event feed "' . $objCatalog->feedName . '.xml"', 'Catalog generateFeed()', TL_CRON);
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
	 */
	public function removeOldFeeds()
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
			$objCatalog->feedName = strlen($objCatalog->alias) ? $objCatalog->alias : 'catalog' . $objCatalog->id;
			$tmp[]=$objCatalog->feedName;
		}
		return $tmp;
	}


	/**
	 * Get a page layout and return it as database result object.
	 * This is a copy from PageRegular, see comments in parseFrontendTemplate() below for the reason why this is here.
	 * @param integer
	 * @return object
	 */
	protected function getPageLayout($intId)
	{
		$objLayout = $this->Database->prepare("SELECT * FROM tl_layout WHERE id=?")
									->limit(1)
									->execute($intId);

		// Fallback layout
		if ($objLayout->numRows < 1)
		{
			$objLayout = $this->Database->prepare("SELECT * FROM tl_layout WHERE fallback=?")
										->limit(1)
										->execute(1);
		}
		
		// Die if there is no layout at all
		if ($objLayout->numRows < 1)
		{
			$this->log('Could not find layout ID "' . $intId . '"', 'PageRegular getPageLayout()', TL_ERROR);

			header('HTTP/1.1 501 Not Implemented');
			die('No layout specified');
		}

		return $objLayout;
	} 
		
	/**
	 * get called by hook to inject all RSS feeds for the current layout into the template
	 */
	public function parseFrontendTemplate($strBuffer, $strTemplate) {
		// I totally admit it. This function sucks big time. It is ugly, a hack, but non the less the only possibility to get this working.
		// We can't even check to only parse templates starting with 'fe_', as fe_page will get parsed before(!) the hook get's called.
		// So we will store our calculated value into the global and hope that no one will ever want to use this.
		// We also hope, that Leo will never ever change his for each inclusion in the page rendering.
		// And we definately hope that all of this will get more easy and Leo will add a hook.
		if(!isset($GLOBALS['TL_HEAD']['CATALOGFEED'])) {
			global $objPage;
			// here we are getting dirty, we have to import the page layout as we have no other way to get the layout from it.
			// I know it does exist already as we are being called from it but hey, we got no Hook in PageRegular::createStyleSheets
			// and therefore have to suffer the hard way... :(
			$objLayout=$this->getPageLayout($objPage->layout);

			$catalogfeeds = deserialize($objLayout->catalogfeeds); 
			// Add catalogfeeds
			if (is_array($catalogfeeds) && count($catalogfeeds) > 0)
			{
				$objFeeds = $this->Database->execute("SELECT * FROM tl_catalog_types WHERE id IN(" . implode(',', $catalogfeeds) . ")");
				while($objFeeds->next())
				{
					$objFeeds->feedName = strlen($objFeeds->alias) ? $objFeeds->alias : 'catalog' . $objFeeds->id;
					$base = strlen($objFeeds->feedBase) ? $objFeeds->feedBase : $this->Environment->base;
					$GLOBALS['TL_HEAD']['CATALOGFEED']= '<link rel="alternate" href="' . $base . $objFeeds->feedName . '.xml" type="application/' . $objFeeds->feedFormat . '+xml" title="' . $objFeeds->description . '" />' . "\n";
				}
			} 
		}
		// Return buffer no matter if we added something to the global array or not.
		// We simply to not want to tamper with it.
		return $strBuffer;
	}
}

?>