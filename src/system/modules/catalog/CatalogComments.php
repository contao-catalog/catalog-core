<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 *
 * The TYPOlight webCMS is an accessible web content management system that 
 * specializes in accessibility and generates W3C-compliant HTML code. It 
 * provides a wide range of functionality to develop professional websites 
 * including a built-in search engine, form generator, file and user manager, 
 * CSS engine, multi-language support and many more. For more information and 
 * additional TYPOlight applications like the TYPOlight MVC Framework please 
 * visit the project website http://www.typolight.org.
 * 
 * The Catalog extension allows the creation of multiple catalogs of custom items,
 * each with its own unique set of selectable field types, with field extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the 
 * data in each catalog.
 * 
 * PHP version 5
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Catalog
 * @license		LGPL 
 * @filesource
 */


/**
 * Class CatalogComments
 *
 * Provide methods regarding news comments.
 * @copyright  Leo Feyer 2005
 * @author     Leo Feyer <leo@typolight.org>
 * @modified   John Brand <john.brand@thyon.com>
 * @package    Controller
 */
class CatalogComments extends Backend
{

	/**
	 * Run the back end comments module
	 */
	public function run()
	{
		$this->import('BackendUser', 'User');

		// Load the language and DCA file
		$this->loadLanguageFile('tl_catalog_comments');
		$this->loadDataContainer('tl_catalog_comments');

		// Include all excluded fields which are allowed for the current user
		if ($GLOBALS['TL_DCA']['tl_catalog_comments']['fields'])
		{
			foreach ($GLOBALS['TL_DCA']['tl_catalog_comments']['fields'] as $k=>$v)
			{
				if ($v['exclude'])
				{
					if ($this->User->hasAccess('tl_catalog_comments::'.$k, 'alexf'))
					{
						$GLOBALS['TL_DCA']['tl_catalog_comments']['fields'][$k]['exclude'] = false;
					}
				}
			}
		}

		// Add style sheet
		$GLOBALS['TL_CSS'][] = 'system/modules/catalog/html/comment.css';

		// issue #52 - deleting does drop the catalog.
		if($this->Input->get('act')=='delete') {
			$tmpCat=$this->Database->prepare("SELECT catid FROM tl_catalog_comments WHERE id=?")
								  ->execute($this->Input->get('id'));
			$catId=$tmpCat->catid;
		} else {
			$catId=$this->Input->get('id');
		}
		// Get Catalog and set TableName
		$objArchive = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=?")
								  ->execute($catId);

		$GLOBALS['TL_DCA']['tl_catalog_comments']['list']['sorting']['root'] = array(0);
		if ($objArchive->numRows && strlen($objArchive->tableName))
		{

			$GLOBALS['TL_DCA']['tl_catalog_comments']['config']['ptable'] = $objArchive->tableName;	
			
			// Limit results to the current catalog archive
			$objChilds = $this->Database->prepare("SELECT id FROM tl_catalog_comments WHERE (catid=?)")
										->execute($catId);
		
			if ($objChilds->numRows)
			{
				$GLOBALS['TL_DCA']['tl_catalog_comments']['list']['sorting']['root'] = $objChilds->fetchEach('id');
			}
			else
			{
				$GLOBALS['TL_DCA']['tl_catalog_comments']['list']['sorting']['root'] = array(0);
			}
		}
		
		// Create data container
		$dc = new DC_CatalogCommentTable('tl_catalog_comments');
		$act = $this->Input->get('act');

		// Run the current action
		if (!strlen($act) || $act == 'paste' || $act == 'select')
		{
			$act = ($dc instanceof listable) ? 'showAll' : 'edit';
		}

		switch ($act)
		{
			case 'delete':
			case 'show':
			case 'showAll':
			case 'undo':
				if (!$dc instanceof listable)
				{
					$this->log('Data container tl_catalog_comments is not listable', 'Main openModule()', TL_ERROR);
					trigger_error('The current data container is not listable', E_USER_ERROR);
				}
				break;

			case 'create':
			case 'cut':
			case 'copy':
			case 'move':
			case 'edit':
				if (!$dc instanceof editable)
				{
					$this->log('Data container tl_catalog_comments is not editable', 'Main openModule()', TL_ERROR);
					trigger_error('The current data container is not editable', E_USER_ERROR);
				}
				break;
		}

		// Store the referer URL even if it includes the "key" parameter
		if ($this->Input->get('key') == 'comments')
		{
			$session = $this->Session->getData();

			// Main script
			if ($this->Environment->script == 'typolight/main.php' && $session['referer']['current'] != $this->Environment->requestUri && !$this->Input->get('act') && !$this->Input->get('token'))
			{
				$session['referer']['last'] = $session['referer']['current'];
				$session['referer']['current'] = $this->Environment->requestUri;
			}

			$this->Session->setData($session);

			// Store session data
			$this->Database->prepare("UPDATE tl_user SET session=? WHERE id=?")
						   ->execute(serialize($session), $this->User->id);
		}
		return $dc->$act();
	}
}

/**
 * Class DC_CatalogCommentTable
 *
 * Driver class that allows a callback to define multiple parent tables.
 * This is needed as otherwise the DC_Table would kill all comments not related to the currently visible catalog.
 * @copyright  CyberSpectrum 2009
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    Controller
 */

class DC_CatalogCommentTable extends DC_Table
{
	/**
	 * Delete all incomplete and unrelated records
	 */
	protected function reviseTable()
	{
		$reload = false;
		$ptables=array();
		if(isset($GLOBALS['TL_DCA'][$this->strTable]['config']['ptable']) && strlen($GLOBALS['TL_DCA'][$this->strTable]['config']['ptable']))
			$ptables[] = $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'];
		if(!empty($this->ptable))
			$ptables[] = $this->ptable;
			
		// now fetch all catalog tables.
		$catalogs=$this->Database->prepare("SELECT * FROM tl_catalog_types")
								->execute();
		while($catalogs->next())
		{
			$ptables[] = $catalogs->tableName;
		}
		$ptables=array_unique($ptables);
			
		$ctable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ctable'];

		$new_records = $this->Session->get('new_records');

		// HOOK: addCustomLogic
		if (is_array($ptables) && isset($GLOBALS['TL_HOOKS']['reviseTable']) && is_array($GLOBALS['TL_HOOKS']['reviseTable']))
		{
			foreach ($GLOBALS['TL_HOOKS']['reviseTable'] as $callback)
			{
				foreach($ptables as $ptable)
				{
					$this->import($callback[0]);
					$status = $this->$callback[0]->$callback[1]($this->strTable, $new_records[$this->strTable], $ptable, $ctable);
					if ($status === true)
					{
						$reload = true;
					}
				}
			}
		}

		// Delete all new but incomplete records (tstamp=0)
		if (is_array($new_records[$this->strTable]) && count($new_records[$this->strTable]) > 0)
		{
			$objStmt = $this->Database->execute("DELETE FROM " . $this->strTable . " WHERE id IN(" . implode(',', $new_records[$this->strTable]) . ") AND tstamp=0");

			if ($objStmt->affectedRows > 0)
			{
				$reload = true;
			}
		}

		// Delete all records of the current table that are not related to the parent tables
		if (is_array($ptables))
		{
			$subsql=array();
			foreach($ptables as $ptable)
			{
				$subsql[]="(NOT EXISTS (SELECT * FROM " . $ptable . " WHERE " . $this->strTable . ".pid = " . $ptable . ".id))";
			}
			$objStmt = $this->Database->execute("DELETE FROM " . $this->strTable . " WHERE " . implode(" AND ", $subsql));

			if ($objStmt->affectedRows > 0)
			{
				$reload = true;
			}
		}

		// Delete all records of the child table that are not related to the current table
		if (is_array($ctable) && count($ctable))
		{
			foreach ($ctable as $v)
			{
				if (strlen($v))
				{
					$objStmt = $this->Database->execute("DELETE FROM " . $v . " WHERE NOT EXISTS (SELECT * FROM " . $this->strTable . " WHERE " . $v . ".pid = " . $this->strTable . ".id)");

					if ($objStmt->affectedRows > 0)
					{
						$reload = true;
					}
				}
			}
		}

		// Reload the page
		if ($reload)
		{
			$this->reload();
		}
	}
}

?>