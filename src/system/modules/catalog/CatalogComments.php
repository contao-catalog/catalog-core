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


		// Get Catalog and set TableName
		$objArchive = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=?")
								  ->execute($this->Input->get('id'));

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
			else
			{
				$GLOBALS['TL_DCA']['tl_catalog_comments']['list']['sorting']['root'] = array(0);
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

?>