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
 * @copyright  Leo Feyer 2005
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Gallery
 * @license    LGPL
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


		// Get Catalog and set TableName
		$objArchive = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=?")
								  ->execute($this->Input->get('id'));


//print_r($objArchive);

		$GLOBALS['TL_DCA']['tl_catalog_comments']['list']['sorting']['root'] = array(0);
		if ($objArchive->numRows && strlen($objArchive->tableName))
		{

			$GLOBALS['TL_DCA']['tl_catalog_comments']['config']['ptable'] = $objArchive->tableName;	
			
			// Limit results to the current catalog archive
			$objChilds = $this->Database->prepare("SELECT id FROM tl_catalog_comments WHERE pid IN (SELECT id FROM ".$objArchive->tableName."  WHERE pid=?)")
										->execute(CURRENT_ID);
		
			if ($objChilds->numRows)
			{
				$GLOBALS['TL_DCA']['tl_catalog_comments']['list']['sorting']['root'] = $objChilds->fetchEach('id');
			}
		}
			
		// Create data container
		$dc = new DC_Table('tl_catalog_comments');
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

		return $dc->$act();
	}
}

?>