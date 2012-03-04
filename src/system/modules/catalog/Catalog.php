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
 * Class Catalog
 *
 * the core class of the catalog.
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Controller
 */
class Catalog extends Backend
{

	/*
	 * Helper functions
	 */
	public static function array_replace_recursive_recurse($array, $array1)
	{
		foreach ($array1 as $key => $value)
		{
			// create new key in $array, if it is empty or not an array
			if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))
			{
				$array[$key] = array();
			}
			// overwrite the value in the base array
			if (is_array($value))
			{
				$value = self::array_replace_recursive_recurse($array[$key], $value);
			}
			$array[$key] = $value;
		}
		return $array;
	}

	/**
	 * array_replace_recursive — Replaces elements from passed arrays into the first array recursively
	 * work around for the method as it is only available in PHP >5.3
	 * Thanks to Gregor at der-meyer dot de (found on php.net)
	 */
	public static function array_replace_recursive($array, $array1)
	{
		// as of PHP 5.3.0 array_replace_recursive() does the work for us
		if (function_exists('array_replace_recursive'))
		{
			// ugly fix, I know but somehow we're getting a recursion in the array sometimes. see #1644
			return @call_user_func_array('array_replace_recursive', func_get_args());
		}

		// handle the arguments, merge one by one
		$args = func_get_args();
		$array = $args[0];
		if (!is_array($array))
		{
			return $array;
		}
		for ($i = 1; $i < count($args); $i++)
		{
			if (is_array($args[$i]))
			{
				$array = self::array_replace_recursive_recurse($array, $args[$i]);
			}
		}
		return $array;
	}

	/**
	 * Callbacks: tl_catalog_items
	 */

	public function initializeCatalogItems($strTable)
	{
		if ($strTable != 'tl_catalog_items')
		{
			return $strTable;
		}

		$this->import('Database');
		$this->import('Input');

		// Store the current ID in the current session if it was passed via the url (like i.e. from an email)
		$catid=$this->Input->get('catid')?(int)$this->Input->get('catid'):NULL;
		if ($catid && ($catid != $this->Session->get('CURRENT_ID')))
		{
			$this->Session->set('CURRENT_ID', $catid);
			$this->redirect(str_replace('&catid='.$catid, '', $this->Environment->request));
		}

		$objType = $this->Database->prepare("SELECT tableName FROM tl_catalog_types where id=?")
				->limit(1)
				->execute(CURRENT_ID);

		if(!$GLOBALS['TL_DCA'][$objType->tableName]['Cataloggenerated'])
		{
			// load language files and DC. langconfig.php and dcaconfig.php is loaded here but not the data in system/modules
			$this->loadLanguageFile($objType->tableName);
			$this->loadDataContainer($objType->tableName);

			// load default language
			$GLOBALS['TL_LANG'][$objType->tableName] = is_array($GLOBALS['TL_LANG'][$objType->tableName])
													 ? self::array_replace_recursive($GLOBALS['TL_LANG']['tl_catalog_items'], $GLOBALS['TL_LANG'][$objType->tableName])
													 : $GLOBALS['TL_LANG']['tl_catalog_items'];
			// load dca
			$GLOBALS['TL_DCA'][$objType->tableName] = is_array($GLOBALS['TL_DCA'][$objType->tableName])
													? self::array_replace_recursive($this->getCatalogDca(CURRENT_ID), $GLOBALS['TL_DCA'][$objType->tableName])
													: $this->getCatalogDca(CURRENT_ID);
			$GLOBALS['TL_DCA'][$objType->tableName]['Cataloggenerated'] = true;
		}

		return $objType->tableName;
	}

	protected $createTableStatement = "
			CREATE TABLE `%s` (
				`id` int(10) unsigned NOT NULL auto_increment,
				`pid` int(10) unsigned NOT NULL,
				`sorting` int(10) unsigned NOT NULL default '0',
				`tstamp` int(10) unsigned NOT NULL default '0',
				PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8";

	protected $renameTableStatement = "ALTER TABLE `%s` RENAME TO `%s`";
	protected $dropTableStatement = "DROP TABLE `%s`";

	public function renameTable($varValue, DataContainer $dc)
	{
		if (!preg_match('/^[a-z_][a-z\d_]*$/iu', $varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['invalidTableName'], $varValue));
		}
		$objType = $this->Database->prepare("SELECT tableName FROM tl_catalog_types WHERE id=?")
				->limit(1)
				->execute($dc->id);
		if ($objType->numRows == 0)
		{
			return $varValue;
		}
		$oldTableName = $objType->tableName;
		if (strlen($oldTableName))
		{
			$statement = sprintf($this->renameTableStatement, $oldTableName, $varValue);
		}
		else
		{
			$statement = sprintf($this->createTableStatement, $varValue);
		}
		$needToCheckIfExists = (!strlen($oldTableName) || $oldTableName != $varValue);
		if ($needToCheckIfExists && $this->Database->tableExists($varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['tableExists'], $varValue));
		}
		$this->Database->execute($statement);
		$this->checkCatalogFields($dc->id, $varValue);
		return $varValue;
	}

	public function dropTable($tableName)
	{
		$this->Database->execute(sprintf($this->dropTableStatement, $tableName));
	}

	/**
	 * Callbacks: tl_catalog_fields
	 */

	protected $systemColumns = array('id', 'pid', 'sorting', 'tstamp');

	protected $renameColumnStatement = "ALTER TABLE %s CHANGE COLUMN %s %s %s";

	protected $createColumnStatement = "ALTER TABLE %s ADD %s %s";

	protected $dropColumnStatement = "ALTER TABLE %s DROP COLUMN %s";

	protected $publishField = '';

	protected function checkCatalogFields($pid, $newTableName)
	{
		$objFields = $this->Database->prepare("SELECT t.tableName, t.id, f.type, f.colName FROM tl_catalog_fields f INNER JOIN tl_catalog_types t ON f.pid=t.id WHERE t.id=? ORDER BY sorting")
			->execute($pid);
		while ($objFields->next())
		{
			$tableName = strlen($newTableName) ? $newTableName : $objFields->tableName;
			$colName = $objFields->colName;
			$fieldType = $objFields->type ? $objFields->type : 'text';

			if (!preg_match('/^[a-z_][a-z\d_]*$/i', $colName))
			{
				throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['invalidColumnName'], $colName));
			}
			if (in_array($colName, $this->systemColumns))
			{
				throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['systemColumn'], $colName));
			}
			if ($this->Database->fieldExists($colName, $tableName))
			{
				$statement = sprintf($this->renameColumnStatement, $tableName, $colName, $colName, $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType]['sqlDefColumn']);
			}
			else
			{
				$statement = sprintf($this->createColumnStatement, $tableName, $colName, $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType]['sqlDefColumn']);
			}

			$this->Database->execute($statement);
		}
	}

	public function renameColumn($varValue, DataContainer $dc)
	{
		if (!preg_match('/^[a-z_][a-z\d_]*$/i', $varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['invalidColumnName'], $varValue));
		}
		if (in_array($varValue, $this->systemColumns))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['systemColumn'], $varValue));
		}

		$objField = $this->Database->prepare("SELECT t.id, t.tableName, f.type, f.colName FROM tl_catalog_fields f INNER JOIN tl_catalog_types t ON f.pid=t.id WHERE f.id=?")
				->limit(1)
				->execute($dc->id);

		if (!$objField->numRows)
		{
			return $varValue;
		}

		$tableName = $objField->tableName;
		$oldColName = $objField->colName;
		$fieldType = $objField->type ? $objField->type : 'text';

		$objItems = $this->Database->prepare("SELECT COUNT(*) as itemCount FROM tl_catalog_fields WHERE pid=? AND id<>? AND colName=?")
				->limit(1)
				->execute($objField->id, $dc->id, $varValue);

		if ($objItems->itemCount > 0)
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['columnExists'], $varValue));
		}

		$objOld = $this->Database->prepare("SELECT COUNT(*) as itemCount FROM tl_catalog_fields WHERE pid=? AND id<>? AND colName=?")
				->limit(1)
				->execute($objField->id, $dc->id, $oldColName);

		if ($objOld->itemCount == 0 && $this->Database->fieldExists($oldColName, $tableName))
		{
			$statement = sprintf($this->renameColumnStatement, $tableName, $oldColName, $varValue, $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType]['sqlDefColumn']);
		}
		else
		{
			$statement = sprintf($this->createColumnStatement, $tableName, $varValue, $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType]['sqlDefColumn']);
		}

		$this->Database->execute($statement);

		return $varValue;
	}

	public function changeColumn($varValue, DataContainer $dc)
	{
		$objField = $this->Database->prepare("SELECT f.colName, f.type, t.tableName FROM tl_catalog_fields f INNER JOIN tl_catalog_types t ON f.pid = t.id WHERE f.id=?")
				->limit(1)
				->execute($dc->id);

		if ($objField->numRows == 0)
		{
			return $varValue;
		}

		$tableName = $objField->tableName;
		$colName = $objField->colName;
		$fieldType = $objField->type;

		if ($varValue != $fieldType)
		{
			$this->Database->execute(sprintf($this->renameColumnStatement, $tableName, $colName, $colName, $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$varValue]['sqlDefColumn']));
		}
		return $varValue;
	}

	public function deleteColumn($ids)
	{
		$objType = $this->Database->prepare("SELECT f.colName, t.tableName FROM tl_catalog_fields f INNER JOIN tl_catalog_types t ON f.pid = t.id WHERE f.id IN (?)")
				->execute(implode(',', $ids));

		while ($objType->next())
		{
			$colName = $objType->colName;
			$tableName = $objType->tableName;

			if ($this->Database->fieldExists($colName, $tableName))
			{
					$this->dropColumn($tableName, $colName);
			}
		}
	}

	public function dropColumn($tableName, $colName)
	{
		$this->Database->execute(sprintf($this->dropColumnStatement, $tableName, $colName));
	}

	/**
	 * Regenerate sitemaps on save.
	 */
	public function generateSitemaps()
	{
		// if we have the GoogleSitemap extension, we have to trigger that one, trigger the core sitemap otherwise.
		if(in_array('googlesitemap', $this->Config->getActiveModules()))
		{
			$this->import('GoogleSitemap');
			$this->GoogleSitemap->generateSitemap();
		} else {
			$this->import('Automator');
			$this->Automator->generateSitemap();
		}
	}

/**
 * DCA Update functions
 */

	private $tableNames	= array();
	private $strFormat	= array();
		public function getDefaultDca()
	{
		$this->loadLanguageFile('tl_catalog_items');
		$arrDCA = array
		(
			'config' => array
			(
				'dataContainer'               => 'Table',
				'ptable'                      => 'tl_catalog_types',
				'switchToEdit'                => true,
				'enableVersioning'            => false,
				'onload_callback'							=> array
					(
						array('Catalog', 'checkPermission')
					),
				'onsubmit_callback'			=> array
				(
					array('Catalog', 'generateSitemaps'),
					array('tl_catalog_items', 'generateFeed'),
				),
			),

			'list' => array
			(
				'sorting' => array
				(
					'mode'                    => 1, // 1 default sorting value, 2 switchable sorting value
					// panelLayout is now built dynamically in getCatalogDca() to solve issue #199
					'headerFields'            => array('name', 'tstamp'),
					'fields'                  => array(),
					'child_record_callback'   => array('Catalog', 'renderField')
				),
				'global_operations' => array
				(
					'export' => array
					(
						'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['export'],
						'href'                => 'key=export',
						'class'               => 'header_css_export',
						'attributes'          => 'onclick="Backend.getScrollOffset();"'
					),
					'all' => array
					(
						'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
						'href'                => 'act=select',
						'class'               => 'header_edit_all',
						'attributes'          => 'onclick="Backend.getScrollOffset();"'
					)
				),

				'operations' => array
				(
					'edit' => array
					(
						'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['edit'],
						'href'                => 'act=edit',
						'icon'                => 'edit.gif',
					),
					'copy' => array
					(
						'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['copy'],
						'href'                => 'act=copy',
						'icon'                => 'copy.gif'
					),
					'cut' => array
					(
						'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['cut'],
						'href'                => 'act=paste&amp;mode=cut',
						'icon'                => 'cut.gif',
						'attributes'          => 'onclick="Backend.getScrollOffset();"'
					),
					'delete' => array
					(
						'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['delete'],
						'href'                => 'act=delete',
						'icon'                => 'delete.gif',
						'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
					),
					'show' => array
					(
						'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['show'],
						'href'                => 'act=show',
						'icon'                => 'show.gif'
					)
				),
			),
			'palettes' => array
			(
			),
			'subpalettes' => array
			(
			),
			'fields' => array
			(
			)
		);

		// add a direct link to the fields but for admins only
		$this->import('BackendUser', 'User');
		if(!$this->User->isAdmin)
		{
			return $arrDCA;
		}

		$arrDCA['list']['global_operations']['fields'] = array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['fields'],
			'href'                => 'table=tl_catalog_fields&id=' . $this->Input->get('id'),
			'class'               => 'header_css_fields',
			'attributes'          => 'onclick="Backend.getScrollOffset();"'
		);

		return $arrDCA;
	}

	/**
	 * Check permissions to edit catalog tableName
	 * @param object
	 */

	public function checkPermission(DataContainer $dc)
	{

		$this->import('BackendUser', 'User');

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (!is_array($this->User->catalogs) || count($this->User->catalogs) < 1)
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->catalogs;
		}

		$id = strlen($this->Input->get('id')) ? $this->Input->get('id') : CURRENT_ID;

		$urlError = ((version_compare(VERSION.'.'.BUILD, '2.9.0', '>='))?'contao':'typolight').'/main.php?act=error';

		// Check current action
		switch ($this->Input->get('act'))
		{
			case 'select':
			case 'paste':
				// Allow
				break;

			case 'create':

				$checkid = $this->Input->get('id');
				if ($this->Input->get('mode') == 2 && strlen($this->Input->get('pid')) && !strlen($this->Input->get('id')))
				{
					// sorted mode
					$checkid = $this->Input->get('pid');
				}

				if (!strlen($checkid) || !in_array($checkid, $root))
				{
					$this->log('Not enough permissions to create catalog items in catalog type ID "'.$checkid.'"', 'Catalog checkPermission', 5);
					$this->redirect($urlError);
				}
				break;

			case 'edit':
			case 'show':
			case 'create':
			case 'copy':
			case 'cut':
			case 'delete':

				$rows = 0;
				$objTable = $this->Database->prepare("SELECT tableName FROM tl_catalog_types WHERE id=?")
						->limit(1)
						->execute(CURRENT_ID);
				if ($objTable->numRows)
				{
					$tableName = $objTable->tableName;
					$objType = $this->Database->prepare("SELECT pid FROM ".$tableName." WHERE id=?")
											 ->limit(1)
											 ->execute($id);
					$rows = $objType->numRows;
				}

				if ($rows < 1)
				{
					$this->log('Invalid catalog item ID "'.$id.'"', 'Catalog checkPermission', 5);
					$this->redirect($urlError);
				}

				if (!in_array($objType->pid, $root))
				{
					$this->log('Not enough permissions to '.$this->Input->get('act').' catalog item ID "'.$id.'" of catalog type ID "'.$objType->pid.'"', 'Catalog checkPermission', 5);
					$this->redirect($urlError);
				}
				break;

			case 'editAll':
			case 'deleteAll':
				if (!in_array($id, $root))
				{
					$this->log('Not enough permissions to access catalog type ID "'.$id.'"', 'Catalog checkPermission', 5);
					$this->redirect($urlError);
				}

				$rows = 0;
				$objTable = $this->Database->prepare("SELECT tableName FROM tl_catalog_types WHERE id=?")
						->limit(1)
						->execute(CURRENT_ID);
				if ($objTable->numRows)
				{
					$tableName = $objTable->tableName;
					$objType = $this->Database->prepare("SELECT id FROM ".$tableName." WHERE pid=?")
											 ->execute($id);
					$rows = $objType->numRows;
				}

				if ($rows < 1)
				{
					$this->log('Invalid catalog type ID "'.$id.'"', 'Catalog checkPermission', 5);
					$this->redirect($urlError);
				}

				$session = $this->Session->getData();
				$session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $objType->fetchEach('id'));
				$this->Session->setData($session);
				break;

			default:
				if (strlen($this->Input->get('act')))
				{
					$this->log('Invalid command "'.$this->Input->get('act').'"', 'Catalog checkPermission', 5);
					$this->redirect($urlError);
				}
				if (!in_array($id, $root))
				{
					$this->log('Not enough permissions to access catalog type ID "'.$id.'"', 'Catalog checkPermission', 5);
					$this->redirect($urlError);
				}
				break;
		}
	}

	/**
	 * Autogenerate a catalog alias if it has not been set yet
	 * @param mixed
	 * @param object
	 * @return string
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$objField = $this->Database->prepare("SELECT pid FROM ".$dc->table." WHERE id=?")
				->limit(1)
				->execute($dc->id);

		if (!$objField->numRows)
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['aliasTitleMissing']);
		}
		$pid = $objField->pid;

		$objAliasTitle = $this->Database->prepare("SELECT colName, aliasTitle FROM tl_catalog_fields WHERE pid=? AND type=?")
									   ->limit(1)
									   ->execute($pid, 'alias');

		if ($objAliasTitle->numRows)
		{
			$aliasTitle = $objAliasTitle->aliasTitle;
			$aliasCol = $objAliasTitle->colName;
		}
		else
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['aliasTitleMissing']);
		}

		$autoAlias = false;

		// Generate alias if there is none
		if (!strlen($varValue))
		{
			$objTitle = $this->Database->prepare("SELECT ".$aliasTitle." FROM ".$dc->table." WHERE id=?")
									   ->limit(1)
									   ->execute($dc->id);

			$autoAlias = true;
			$varValue = standardize($objTitle->$aliasTitle);
		}

		$objAlias = $this->Database->prepare("SELECT id FROM ".$dc->table." WHERE ".$aliasCol."=? AND id!=?")
								   ->execute($varValue, $dc->id);

		// Check whether the catalog alias exists
		if ($objAlias->numRows && !$autoAlias)
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		// Add ID to alias
		if ($objAlias->numRows && $autoAlias)
		{
			$varValue .= '.' . $dc->id;
		}
		return $varValue;
	}

	public function loadSelect($varValue, DataContainer $dc)
	{
		if (strlen($varValue))
		{
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['title'] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['options'][$varValue];
		}
		return ($varValue);
	}

	/**
	 * Updates the relations to tags for one field for one catalog item
	 * @param int $intCatalogId
	 * @param int $intFieldId
	 * @param int $intItemId
	 * @param array $arrValues
	 * @return void
	 */
	public static function setTags($intCatalogId, $intFieldId, $intItemId, array $arrValues)
	{
		$db = Database::getInstance();

		$objTags=$db->prepare('SELECT * FROM tl_catalog_tag_rel WHERE catid=? AND itemid=? AND fieldid=?')
							->execute($intCatalogId, $intItemId, $intFieldId);
		$arrTags=$objTags->fetchEach('valueid');
		// all new tags.
		$arrNew = array_diff($arrValues, $arrTags);
		// all tags that shall get removed.
		$arrOld = array_diff($arrTags, $arrValues);

		$arrData = array(
						'catid' => $intCatalogId,
						'itemid' => $intItemId,
						'fieldid' => $intFieldId,
						'valueid' => 0
					);

		// add missing tags to db.
		foreach($arrNew as $id)
		{
			$arrData['valueid'] = $id;
			$db->prepare('INSERT INTO tl_catalog_tag_rel %s')->set($arrData)->execute();
		}

		// delete tags that shall not be attached anymore from db.
		foreach($arrOld as $id)
		{
			$db->prepare('DELETE FROM tl_catalog_tag_rel WHERE catid=? AND itemid=? AND fieldid=? AND valueid=?')
							->execute($intCatalogId, $intItemId, $intFieldId, $id);
		}
	}

	/**
	 * HOOK which updates tag relations for a catalog item field
	 * @param mixed $varValue
	 * @param DataContainer $dc
	 * @return string comma separated tag ids
	 */
	public function saveTags($varValue, DataContainer $dc)
	{
		$options = deserialize($varValue, true);
		$objField = $this->Database->prepare('SELECT * FROM tl_catalog_fields WHERE pid=? AND colName=?')->execute($dc->activeRecord->pid, $dc->field);
		self::setTags($dc->activeRecord->pid, $objField->id, $dc->activeRecord->id, $options);

		if (!is_array($options))
		{
			return '';
		}

		return implode(',', $options);
	}

	public function loadTags($varValue, DataContainer $dc)
	{
		$objTags=$this->Database->prepare('SELECT *
										FROM tl_catalog_tag_rel
										WHERE catid=?
										AND itemid=?
										AND fieldid=?')
							->execute($dc->activeRecord->pid,
							          $dc->activeRecord->id,
							          $dc->field);
		// TODO: either move this to update routine or remove after some grace period in the future.
		$values = array_filter(array_merge(explode(',', trim($varValue)),
		                       $objTags->fetchEach('valueid')));
		//$values = explode(',', trim($varValue));
		$valueList = array();
		foreach($values as $value)
		{
			if (strlen($value))
			{
			 	$valueList[] = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['options'][$value];
			}
		}
		if (count($valueList))
		{
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['title'] =  implode(', ',$valueList);
		}

		if(TL_MODE == 'BE')
		{
			// expand the tree
			$session = $this->Session->getData();
			$this->expandTree($values, $session, $dc);
			$this->Session->setData($session);
		}
		return serialize($values);
	}

	protected function expandTree($values, &$session, DataContainer $dc)
	{
		$values = array_filter(array_map('intval', $values));

		if (count($values)) {
			$node = 'tree_' . $dc->table . '_' . $dc->field;
			foreach ($values as $value)
			{
				$session[$node][$value] = 1;
			}
			$table = preg_replace('#\..*$#', '', $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['tableColumn']);
			$objData = $this->Database->execute('SELECT * FROM ' . $table . ' WHERE id IN (' . implode(',', $values) . ') AND pid > 0 AND id!=pid');
			if ($objData->numRows)
			{
				$this->expandTree($objData->fetchEach('pid'), $session, $dc);
			}
		}
	}

	public function getCalc($varValue, DataContainer $dc, $blnReturn=false)
	{
		$objCalc = $this->Database->prepare("SELECT f.calcValue FROM tl_catalog_fields f WHERE f.pid=(SELECT c.id FROM tl_catalog_types c WHERE c.tableName=?) AND f.colName=?")
							   ->limit(1)
							   ->execute($dc->table, $dc->field);

		// set default value to forumla (for load_callback display)
		$value = $objCalc->calcValue;

		try
		{
			$objValue = $this->Database->prepare("SELECT ".$objCalc->calcValue." as calcValue FROM ".$dc->table." WHERE id=?")
								   ->limit(1)
								   ->execute($dc->id);
			if ($objValue->numRows)
			{
				$value = $objValue->calcValue;
			}
		}

		catch (Exception $e)
		{
			// set error into label (it appears as it we can't use exceptions on load_callback methods)
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0] = '<span class="tl_error">'.$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0].'</span>';
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][1] = '<span class="tl_error">'.sprintf($GLOBALS['TL_LANG']['ERR']['calcError'], trim($e->getMessage())).'</span>';

		}

		return (($blnReturn) ? $value : '');
	}

	public function loadCalc($varValue, DataContainer $dc)
	{
		return $this->getCalc($varValue, $dc, true);
	}

	public function saveCalc($varValue, DataContainer $dc)
	{
		return $this->getCalc($varValue, $dc, false);
	}

	/**
	 * Check if number and decimal values are within limits
	 * @param mixed
	 * @param object
	 * @return string
	 */
	public function checkLimits($varValue, DataContainer $dc)
	{
		$catConfig = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['catalog'];

		if (strlen($catConfig['maxValue']) && $varValue > $catConfig['maxValue'])
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['limitMax'], $catConfig['maxValue']));
		}
		elseif (strlen($catConfig['minValue']) && $varValue < $catConfig['minValue'])
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['limitMin'], $catConfig['minValue']));
		}

		return $varValue;
	}

	/**
	 * Row Label
	 */
	public function renderField($row)
	{
		if (!$row['pid'])
		{
			return 'ID:'.$row['id'];
		}

		if (isset($this->tableNames[$row['pid']]) && isset($this->strFormat[$row['pid']]))
		{
			$tableName = $this->tableNames[$row['pid']];
			$strFormat = $this->strFormat[$row['pid']];
		}
		else
		{
			$objType = $this->Database->prepare("SELECT tableName, format FROM tl_catalog_types WHERE id=?")
					->limit(1)
					->execute($row['pid']);

			$tableName = $objType->tableName;
			$strFormat = $objType->format;
			$this->tableNames[$row['pid']] = $tableName;
			$this->strFormat[$row['pid']] = $strFormat;
		}

		$fields = $GLOBALS['TL_DCA'][$tableName]['list']['label']['fields'];

		$values = array();
		foreach($fields as $field)
		{
			$values[$field] = $this->formatTitle($row[$field], $GLOBALS['TL_DCA'][$tableName]['fields'][$field], $tableName, $row['id']);
		}

		if (!strlen($strFormat))
		{
			return implode(', ', $values);
		}
		else
		{
			return $this->generateTitle($strFormat, $values, $tableName);
		}
	}

	private function generateTitle($strFormat, $values, $tableName)
	{
		$fields = $GLOBALS['TL_DCA'][$tableName]['list']['label']['fields'];
		preg_match_all('/{{([^}]+)}}/', $strFormat, $matches);
		//$strFormat = '';
		foreach ($matches[1] as $match)
		{
			$params = explode('::', $match);
			$fieldConf = $GLOBALS['TL_DCA'][$tableName]['fields'][$params[0]];
			if ($fieldConf)
			{
				$replace = $values[$params[0]];
				if ($params[1])
				{
					switch ($fieldConf['eval']['catalog']['type'])
					{
						case 'file':
							if ($fieldConf['eval']['catalog']['showImage'])
							{
								$replace = $this->generateThumbnail($replace, $params[1], $fieldConf['label'][0]);
							}
							break;

						case 'checkbox':
							// only use image if checkbox == true
							$replace = ($replace ? $this->generateThumbnail($replace, $params[1], $fieldConf['label'][0]) : '');
							break;

						default:
							parse_str($params[1], $formats);
							if (strlen($replace) && array_key_exists('pre', $formats))
							{
								$replace = stripslashes(stripslashes($formats['pre'])).$replace;
							}
							if (strlen($replace) && array_key_exists('post', $formats))
							{
								$replace = $replace.stripslashes(stripslashes($formats['post']));
							}
					}
				}
				$strFormat = str_replace('{{'.$match.'}}', $replace, $strFormat);
			}
		}
		// now we have a final string but we would also like to let the users use if-else conditions like for the newsletter
		$strFormat = $this->parseSimpleTokens($strFormat, $values);
		return $strFormat;
	}

	private function generateThumbnail($value, $query, $label)
	{
		// parse query parameters if set
		parse_str($query, $params);
		$src = $params['src'] ? $params['src'] :  $value;
		if (strpos($src, '/') === false)
		{
			$src = sprintf('system/themes/%s/images/%s', $this->getTheme(), $src);
		}
		if ($value == '' || !file_exists(TL_ROOT.'/'.$src))
		{
			return '';
		}
		try
		{
			$file = $this->getImage($src, $params['w'], $params['h'], $params['mode']);
			return '<img src="' . $file . '" alt="'.specialchars($label).'" />';
		}
		// catch an error when the image is invalid and return a span instead.
		catch(Exception $e)
		{
			return '<span class="image_not_found" style="width:'.$params['w'].'px;height:'.$params['h'].'px;">'.specialchars($label).'</span>';
		}
	}

	private function formatTitle($value, &$fieldConf, $tableName, $id)
	{
		$blnCalc = ($fieldConf['eval']['catalog']['type'] == 'calc' && strlen($fieldConf['eval']['catalog']['calcValue']));
		if (strlen($value) || $blnCalc)
		{

			switch ($fieldConf['eval']['catalog']['type'])
			{
				case 'select':
						$value = $fieldConf['options'][$value];
						break;

				case 'tags':
						$tags = trimsplit(',',$value);
						$arrTags = array();
						foreach ($tags as $tag)
						{
							$arrTags[] = $fieldConf['options'][$tag];
						}
						$value = implode(', ', $arrTags);
						break;

				case 'checkbox':
						if ($value)
						{
							$value = $fieldConf['label'][0];
						}
						break;

				case 'calc':
						$value = '';
						try
						{
							$objValue = $this->Database->prepare("SELECT ".$fieldConf['eval']['catalog']['calcValue']." as calcValue FROM ".$tableName." WHERE id=?")
														->limit(1)
														->execute($id);

							if ($objValue->numRows)
							{
								$value = $objValue->calcValue;
							}
						}

						catch (Exception $e)
						{
							$value = '';
						}
						break;

				default:;
			}

			switch ($fieldConf['eval']['catalog']['formatFunction'])
			{
				case 'string':
						$value = sprintf($fieldConf['eval']['catalog']['formatStr'], $value);
						break;

				case 'number':
						$decimalPlaces = is_numeric($fieldConf['eval']['catalog']['formatStr']) ?
							intval($fieldConf['eval']['catalog']['formatStr']) :
							0;
						$value = number_format($value, $decimalPlaces,
							$GLOBALS['TL_LANG']['MSC']['decimalSeparator'],
							$GLOBALS['TL_LANG']['MSC']['thousandsSeparator']);
						break;

				case 'date':
						$value = date($fieldConf['eval']['catalog']['formatStr'], intval($value));
						break;

				default:
						if ($fieldConf['eval']['rgxp'] == 'date' || $fieldConf['eval']['rgxp'] == 'datim')
						{
							$value = date($GLOBALS['TL_CONFIG'][$fieldConf['eval']['rgxp'].'Format'], intval($value));
						}
			}

			// add prefix and suffix format strings
			if (is_array($fieldConf['eval']['catalog']['formatPrePost']) && count($fieldConf['eval']['catalog']['formatPrePost'])>0)
			{
				$value = $fieldConf['eval']['catalog']['formatPrePost'][0].$value.$fieldConf['eval']['catalog']['formatPrePost'][1];
				// only restore basic entities for visual display in back-end
				$value = $this->restoreBasicEntities($value);
			}

		}

		return $value;
	}

	public function pagePicker(DataContainer $dc)
	{
		return ' ' . $this->generateImage('pickpage.gif', $GLOBALS['TL_LANG']['MSC']['pagepicker'], 'style="vertical-align:top; cursor:pointer;" onclick="Backend.pickPage(\'ctrl_'.$dc->field.'\')"');
	}

	public function getCatalogDca($catalogId)
	{
		$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=? ORDER BY sorting")
					->execute($catalogId);

		// date picker was changed in 2.10
		if (version_compare(VERSION, '2.10', '>='))
			$GLOBALS['BE_MOD']['content']['catalog']['fieldTypes']['date']['fieldDef']['eval'] = array('datepicker' => true);
		else
			$GLOBALS['BE_MOD']['content']['catalog']['fieldTypes']['date']['fieldDef']['eval'] = array('datepicker' => $this->getDatePickerString());

		// load default catalog dca
		$dca = $this->getDefaultDca();

		$fields = array();
		$titleFields = array();
		$sortingFields = $dca['list']['sorting']['fields'];
		$groupingFields = array();
		$selectors = array();

		// load DCA, as we're calling it now in Catalog
		$this->loadDataContainer('tl_catalog_fields');

		// check if import is enabled
		$objCatalog = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=?")
				->limit(1)
				->execute($catalogId);

		if ($objCatalog->numRows && $objCatalog->import)
		{
			// check user setting as well
			$this->import('BackendUser', 'User');
			if (!$objCatalog->importAdmin || $objCatalog->importAdmin && $this->User->isAdmin)
			{
				array_insert($dca['list']['global_operations'], 0, array(
					'import' => array
						(
							'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['import'],
							'href'                => 'key=import',
							'class'               => 'header_css_import',
							'attributes'          => 'onclick="Backend.getScrollOffset();"'
						)
					)
				);
			}
		}
		if($objCatalog->publishField && version_compare(VERSION.'.'.BUILD, '2.8.0', '>='))
		{
			$this->publishField=$objCatalog->publishField;
			array_insert($dca['list']['operations'], 3, array('toggle' => array
						(
						'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['toggle'],
						'icon'                => 'visible.gif',
						'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this, %s);"',
						'button_callback'     => array('Catalog', 'toggleIcon')
						))
					);
			if(version_compare(VERSION, '2.10', '>='))
			{
				if($this->Input->get('tid'))
				{
					// Update database
					$this->Database->prepare('UPDATE '.$objCatalog->tableName.' SET ' . $this->publishField . '=? WHERE id=?')
									->execute($this->Input->get('state')=='1'?'1':'', $this->Input->get('tid'));
					exit;
				}
			} else {
				if($this->Input->post('action')=='toggleVisibility')
				{
					// Update database
					$this->Database->prepare('UPDATE '.$objCatalog->tableName.' SET ' . $this->publishField . '=? WHERE id=?')
								   ->execute($this->Input->post('state')=='1'?'1':'', $this->Input->post('id'));
					exit;
				}
			}
		}

		// allow manual sorting - thanks to Ueli Kunz <elun@gmx.ch> for the idea
		if($objCatalog->allowManualSort)
		{
			$this->manualsort=$this->Input->get('manualsort');
			$dca['list']['global_operations']['manualsort'] = array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_items']['manualsort_'.($this->manualsort?'leave':'enter')],
				'href'                => 'manualsort='.($this->manualsort?'0':'1'),
				'class'               => 'header_manualsort',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			);
		}

		$filter=$search=$sort=false;
		while ($objFields->next())
		{
			$colName = $objFields->colName;
			$colType = $objFields->type;

			$visibleOptions = trimsplit('[,;]', $GLOBALS['TL_DCA']['tl_catalog_fields']['palettes'][$colType]);

			$field = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$colType]['fieldDef'];
			$fields[] = $colName;

			if($objFields->insertBreak && strlen($objFields->legendTitle))
			{
				$legendName = $colName.'_legend';
				$GLOBALS['TL_LANG'][$objCatalog->tableName][$legendName] = $objFields->legendTitle;
				$separators[] = ((count($separators)>0 ? ';':'') . '{'.$legendName.(($objFields->legendHide)?':hide':'').'},');
			} else
				$separators[] = ',';

			// Ammend field with catalog field settings
			if(!$GLOBALS['TL_LANG'][$objCatalog->tableName][$colName])
				$GLOBALS['TL_LANG'][$objCatalog->tableName][$colName] = array($objFields->name, $objFields->description);
			$field['label'] = &$GLOBALS['TL_LANG'][$objCatalog->tableName][$colName];

			$field['eval']['mandatory'] = $field['eval']['mandatory'] || ($objFields->mandatory && in_array('mandatory', $visibleOptions) ? true : false);
			if ($objFields->includeBlankOption && $colType == 'select')
			{
				$field['eval']['includeBlankOption'] = true;
			}
			$field['eval']['unique'] = $field['eval']['unique'] || ($objFields->uniqueItem && in_array('uniqueItem', $visibleOptions) ? true : false);
			$field['eval']['catalog']['type'] = $colType;
			if($objFields->defValue)
				$field['default'] = $objFields->defValue;
			$field['filter'] = ($objFields->filteredField && in_array('filteredField', $visibleOptions) ? true : false);
			$field['search'] = ($objFields->searchableField && in_array('searchableField', $visibleOptions) ? true : false);
			$field['sorting'] = ((!$this->manualsort) && $objFields->sortingField && in_array('sortingField', $visibleOptions) ? true : false);

			if ($objFields->width50)
			{
				$field['eval']['tl_class'] = 'w50' . (in_array($colType, $GLOBALS['BE_MOD']['content']['catalog']['typesWizardFields']) ? ' wizard' : '' )
											. (($colType == 'checkbox') ? ' m12' : '' );
			}

			$dca['fields'][$colName] = $field;

			// dynamically call the config function for the field type
			$configFunction = $colType . "Config";
			if (method_exists($this, $configFunction))
			{
				$this->$configFunction($dca['fields'][$colName], $objFields);
			}

			// HOOK: try to format the fieldtype as it might be a custom added one.
			$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$colType];
			if(is_array($fieldType) && array_key_exists('generateFieldEditor', $fieldType) && is_array($fieldType['generateFieldEditor']))
			{
				foreach ($fieldType['generateFieldEditor'] as $callback)
				{
					$this->import($callback[0]);
					$this->$callback[0]->$callback[1]($dca['fields'][$colName], $objFields);
				}
			}

			if ($objFields->titleField && in_array('titleField', $visibleOptions))
			{
				$titleFields[] = $colName;
			}

			if ((!$this->manualsort) && $objFields->sortingField && in_array('sortingField', $visibleOptions))
			{
				$sortingFields[] = $colName;
				$dca['fields'][$colName]['flag'] = $objFields->groupingMode;
			}

			if ($objFields->parentCheckbox)
			{
				$dca['fields'][$colName]['eval']['catalog']['parentCheckbox'] = $objFields->parentCheckbox;
				if (isset($selectors[$objFields->parentCheckbox]))
				{
					$selectors[$objFields->parentCheckbox][] = $colName;
				}
				else
				{
					$selectors[$objFields->parentCheckbox] = array($colName);
				}
			}

			if ($objFields->parentFilter)
			{
				$dca['fields'][$colName]['eval']['catalog']['parentFilter'] = $objFields->parentFilter;
				$dca['fields'][$objFields->parentFilter]['eval']['submitOnChange'] = true;
			}
			if ($objFields->editGroups)
			{
				$dca['fields'][$colName]['eval']['catalog']['editGroups'] = unserialize($objFields->editGroups);
			}

			$filter=$filter||$field['filter'];
			$search=$search||$field['search'];
			$sort=$sort||$field['sorting'];

			// HOOK: allow third party extension developers to modify the dca on the fly
			if(is_array($GLOBALS['TL_HOOKS']['getCatalogDca']) && count($GLOBALS['TL_HOOKS']['getCatalogDca']))
			{
				foreach($GLOBALS['TL_HOOKS']['getCatalogDca'] as $callback)
				{
					$this->import($callback[0]);
					$this->$callback[0]->$callback[1]($objFields, $dca, $objCatalog, $this);
				}
			}
		}
		$panelLayout=array();
		if($filter)
			$layout='filter,limit';
		else
			$layout='limit';
		if($search)
		{
			$layout.=';search';
			if($sort)
				$layout.=',sort';
		}
		else
		{
			if($sort)
				$layout.=';sort';
		}
		$dca['list']['sorting']['panelLayout'] = $layout;

		// build palettes and subpalettes
		$selectors = array_intersect_key($selectors, array_flip($fields));
		$fieldsInSubpalette = array();
		foreach ($selectors as $selector=>$subpaletteFields)
		{
			$dca['fields'][$selector]['eval']['submitOnChange'] = true;
			$dca['subpalettes'][$selector] = implode(',', $subpaletteFields);
			$fieldsInSubpalette = array_merge($fieldsInSubpalette, $subpaletteFields);
		}
		$dca['palettes']['__selector__'] = array_keys($selectors);

		// legends
		$strPalette = '';
		$palettes = array_diff($fields, $fieldsInSubpalette);
		foreach ($palettes as $id=>$field)
		{
			$strPalette .= $separators[$id] . $field;
		}
		$dca['palettes']['default'] = $strPalette;

		// set title fields
		$titleFields = count($titleFields) ? $titleFields : array('id');
		$titleFormat = implode(', ', array_fill(0, count($titleFields), '%s'));
		$dca['list']['label'] = array
		(
			'fields' => $titleFields,
			'format' => $titleFormat,
			'label_callback' => array('Catalog', 'renderField'),
		);

		// set sorting fields
		if (count($sortingFields) > 1 || (count($sortingFields) == 1 && $dca['fields'][$sortingFields[0]]['flag'] > 0))
		{
			// switchable sorting/grouping value
			$dca['list']['sorting']['fields'] = $sortingFields;
			$dca['list']['sorting']['mode'] = 2;
			unset($dca['list']['operations']['cut']);
		}
		elseif (count($sortingFields) == 1 && $dca['fields'][$sortingFields[0]]['flag'] == 0)
		{
			// set as parent-child, ignore sorting DB field
			$dca['list']['sorting']['mode'] = 4;
			$dca['list']['sorting']['fields'] = $sortingFields;
			unset($dca['list']['operations']['cut']);
		}
		else
		{
			$dca['list']['sorting']['mode'] = 4;
			$dca['list']['sorting']['fields'] = array('sorting');
		}

		// return dynamic catalog DCA
		return $dca;

	}

	private function numberConfig(&$field, $objRow)
	{
		$field['eval']['catalog']['minValue'] = $objRow->minValue;
		$field['eval']['catalog']['maxValue'] = $objRow->maxValue;

		$field['save_callback'][] =	array('Catalog', 'checkLimits');

		$this->formatConfig($field, $objRow);
	}
		private function decimalConfig(&$field, $objRow)
	{
		$this->numberConfig($field, $objRow);
	}
		private function textConfig(&$field, $objRow)
	{
		$this->formatConfig($field, $objRow);
	}
		private function longtextConfig(&$field, $objRow)
	{
		$field['eval']['rte'] = $objRow->rte ? ($objRow->rte_editor ? $objRow->rte_editor : 'tinyMCE') : '';
		$field['eval']['allowHtml'] = !!$objRow->allowHtml;
		if ($objRow->textHeight)
		{
			$field['eval']['style'] .= 'height:'.$objRow->textHeight.'px;';
		}
	}
		private function selectConfig(&$field, $objRow)
	{
		$this->configOptions($field, $objRow, false);

		$field['load_callback'][] = array('Catalog', 'loadSelect');
	}
		/**
	 * adds configuration like hooks to a field's config
	 * @param array $field
	 * @param object $objRow
	 * @post $arrField is properly configured
	 */
	private function tagsConfig(array &$arrField, $objRow)
	{
		$this->configOptions($arrField, $objRow, true);
		$arrField['eval']['catalog']['fieldId'] = $objRow->id;
		$arrField['eval']['alwaysSave'] = true;
		$arrField['save_callback'][] = array('Catalog', 'saveTags');
		$arrField['load_callback'][] = array('Catalog', 'loadTags');
	}

	private function aliasConfig(&$field, $objRow)
	{
		$field['save_callback'][] = array('Catalog', 'generateAlias');
	}

	public function configOptions(&$field, $objRow, $blnTags)
	{
		$foreignKey = $objRow->itemTable . '.' . $objRow->itemTableValueCol;
		$limitItems = $objRow->limitItems ? true : false;

		$ids = deserialize($objRow->items);
		$sortCol = $objRow->itemSortCol;

		if (! $limitItems
			|| ($limitItems
				&& ($objRow->childrenSelMode == 'items'
					|| $objRow->childrenSelMode == 'children')))
		{
			if (!$limitItems)
			{
				// select all items in tree starting at root
				$ids = array(0);
			}

			if ($blnTags)
			{
				$field['inputType'] = 'checkbox';
				$field['eval']['multiple'] = true;
			}
			else
			{
				$field['inputType'] = 'select';
			}
		}

		// we have to keep mandatory no matter if we are a select or tag field.
		if ($objRow->mandatory)
		{
			$field['eval']['mandatory'] = true;
		}

		// setup new findInSet options
		$field['eval']['findInSet'] = true;

		$field['eval']['catalog']['foreignKey'] = $foreignKey;
		$field['eval']['catalog']['limitItems'] = $limitItems;
		$field['eval']['catalog']['selectedIds'] = $ids;
		$field['eval']['catalog']['sortCol'] = $sortCol;
		$field['eval']['catalog']['childrenSelMode'] = $limitItems ? $objRow->childrenSelMode : '';
		$field['eval']['catalog']['itemFilter'] = $objRow->itemFilter;

		$blnItems = (!$limitItems || ($limitItems && $objRow->childrenSelMode == 'items'));

		$parentFilter = $limitItems ? $objRow->parentFilter : '';

		// only filter items further if in editing mode in BE
		$idsFilter = '';
		if (strlen($parentFilter) && strlen($this->Input->get('id')) && $this->Input->get('act') == 'edit')
		{
			$objTable = $this->Database->prepare("SELECT tableName FROM tl_catalog_types WHERE id=?")
					->limit(1)
					->execute(CURRENT_ID);

			if ($objTable->numRows)
			{
				$objParents = $this->Database->prepare("SELECT ".$parentFilter." FROM ". $objTable->tableName . " WHERE id=?")
										->execute($this->Input->get('id'));
				$idsFilter = ($objParents->$parentFilter != 0) ? $objParents->$parentFilter : '';
				$ids = strlen($idsFilter) ? explode(',',$idsFilter) : $ids;
				$ids = is_array($ids) ? $ids : array($ids);
			}
		}
		$field['options'] = $this->loadAllOptions($foreignKey, $ids, 0, $sortCol, $blnItems, $objRow->itemFilter, $idsFilter);
		$field['eval']['tableColumn'] = $foreignKey;
		$field['eval']['sortColumn'] = $sortCol;
		$field['eval']['root'] = $ids;

		// default is "Select %s"
		$field['eval']['title'] = sprintf($GLOBALS['TL_LANG']['MSC']['optionsTitle'], $objRow->name);

		if (!$objRow->limitItems || ($objRow->limitItems && ($objRow->childrenSelMode == 'treeAll' || $objRow->childrenSelMode == 'treeChildrenOnly')))
		{
			$field['eval']['titleValues'] = true;
		}

		if ($objRow->limitItems && ($objRow->childrenSelMode == 'treeAll' || $objRow->childrenSelMode == 'treeChildrenOnly'))
		{
			// setup tabletree children selection
			$field['eval']['children'] = ($objRow->childrenSelMode == 'treeAll' || $objRow->childrenSelMode == 'treeChildrenOnly');
			$field['eval']['childrenOnly'] = $objRow->childrenSelMode == 'treeChildrenOnly';

		}
		if($objRow->limitItems && ($objRow->childrenSelMode == 'treeAll') && ($objRow->treeMinLevel || $objRow->treeMaxLevel))
		{
			$field['eval']['minLevel'] = $objRow->treeMinLevel;
			$field['eval']['maxLevel'] = $objRow->treeMaxLevel;
		}
	}

	/**
	 * Replace Catalog InsertTags including TL InsertTags
	 * @param string
	 * @param array
	 * @return string
	 */

	protected function replaceCatalogTags($strValue, $arrCatalog)
	{
		$strValue = trim($strValue);

		// search for catalog tags
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
					$strValue = str_replace($tag, str_replace("\n", " ", $arrCatalog[$key]), $strValue);
				}
			}
		}

/*
		// Replace standard insert tags
		if (strlen($strValue))
		{
			$strValue = $this->replaceInsertTags($strValue);
		}

*/
		return $strValue;
	}

	/**
	 *
	 * @param string $foreignKey
	 * @param mixed $ids array or single id
	 * @param int $level optional
	 * @param string $sortColumn optional
	 * @param boolean $blnItems optional
	 * @param string $itemFilter optional
	 * @param string $idsFilter optional
	 * @return array
	 */
	private function loadAllOptions($foreignKey, $ids, $level=0, $sortColumn='sorting', $blnItems=false, $itemFilter='', $idsFilter='')
	{
		list($sourceTable, $sourceColumn) = explode('.', $foreignKey);
		$ids = is_array($ids) ? $ids : array($ids);

		// replace insert tags (issue #1418)
		$itemFilter = $this->replaceInsertTags($itemFilter);

		// check if this tree has a pid or a flat table
		try
		{
			$sortColumn = strlen($sortColumn) ? $sortColumn : 'sorting';
			$sort = $this->Database->fieldExists($sortColumn, $sourceTable) ? $sortColumn : $sourceColumn;

			$arrWhere = array();
			if (strlen($itemFilter))
			{
				$arrWhere['filter'] = '('.$itemFilter.')';
			}

			$objCatalog = $this->Database->prepare("SELECT tableName FROM tl_catalog_types WHERE tableName=?")
					->limit(1)
					->execute($sourceTable);
			$blnCatalog = ($objCatalog->numRows == 1);

			// tree view only self referenced tables
			$treeView = $this->Database->fieldExists('pid', $sourceTable) && !$blnCatalog;
			$this->loadDataContainer($sourceTable);
			$ptable=$GLOBALS['TL_DCA'][$sourceTable]['config']['ptable'];
			$treeView = (!$ptable || ($ptable === $sourceTable)) && $this->Database->fieldExists('pid', $sourceTable) && !$blnCatalog;

			if ($treeView)
			{
				if (strlen($idsFilter) && $level == 0 && strlen($this->Input->get('id')) && $this->Input->get('act') == 'edit')
				{
					$arrWhere['items'] = 'pid IN ('.$idsFilter.')';
					$idsFilter = '';
				}
				else
				{
					// added check for $ids array to either contain more than one element or the single element is not zero to fix #2204 addendum
					$arrWhere['items'] = ($blnItems && (count($ids)!=1 || $ids[0]!=0) ? 'id' : 'pid')." IN (" . implode(',', $ids) . ")";
				}

				$strWhere = (is_array($arrWhere) && count($arrWhere)) ? implode(' AND ', $arrWhere) : '';

				$objNodes = $this->Database->prepare("SELECT id, (SELECT COUNT(*) FROM ". $sourceTable ." i WHERE i.pid=o.id) AS childCount, " . $sourceColumn . " FROM ". $sourceTable. " o WHERE ".$strWhere." ORDER BY ". $sort)
										 ->execute();
			}

			if (!$treeView || ($treeView && $objNodes->numRows == 0 && $level == 0))
			{
				$strWhere = (is_array($arrWhere) && count($arrWhere)) ? ' WHERE '. implode(' AND ', $arrWhere) : '';

				$objNodes = $this->Database->execute("SELECT id, 0 AS childCount, ". $sourceColumn ." FROM ". $sourceTable . $strWhere . " ORDER BY ".$sort);
			}
		}

		catch (Exception $ee)
		{
			return array();
		}

		// Return if there are no items
		if ($objNodes->numRows < 1)
		{
			return array();
		}

		// Add options
		$arrNodes = array();
		while ($objNodes->next())
		{
			$arrNodes[$objNodes->id] = str_repeat('  ', $level) . $objNodes->$sourceColumn;
			if ($objNodes->childCount > 0 && !$blnItems)
			{
				$arrChildren = $this->loadAllOptions($foreignKey, $objNodes->id, ($level+1), $sortColumn, $blnItems, $itemFilter, $idsFilter);
				$arrNodes += $arrChildren;
			}
		}

		return $arrNodes;
	}


	private function dateConfig(&$field, $objRow)
	{
		$field['eval']['rgxp'] = $objRow->includeTime ? 'datim' : 'date';

		$this->formatConfig($field, $objRow);
	}

	private function fileConfig(&$field, $objRow)
	{
		$field['eval']['catalog']['showLink'] = $objRow->showLink ? true : false;
		$field['eval']['catalog']['showImage'] = $objRow->showImage ? true : false;

		$field['eval']['catalog']['imageSize'] = deserialize($objRow->imageSize, true);
		$field['eval']['catalog']['multiple'] = $objRow->multiple;
		$field['eval']['catalog']['sortBy'] = $objRow->sortBy;

		$field['eval']['fieldType'] = $objRow->multiple ? 'checkbox' : 'radio';

		if ($objRow->customFiletree)
		{
			if (strlen($objRow->uploadFolder))
			{
				$field['eval']['path'] = $objRow->uploadFolder;
			}
			if (strlen($objRow->validFileTypes))
			{
				$field['eval']['extensions'] = $objRow->validFileTypes;
			}
			if (strlen($objRow->filesOnly))
			{
				$field['eval']['filesOnly'] = true;
			}
		}
	}

	private function urlConfig(&$field, $objRow)
	{
		$field['wizard'] = array(array('Catalog', 'pagePicker'));
		$field['eval']['catalog']['showLink'] = $objRow->showLink ? true : false;

		$arrHosts = deserialize($objRow->allowedHosts, true);
		if(count($arrHosts))
		{
			$field['eval']['rgxp'] = 'url_allowed_hosts_only';
			$field['eval']['catalog']['allowedHosts'] = $arrHosts;
		} else {
			$field['eval']['rgxp'] = 'url';
		}
	}

	public function catalogRgxp($strRegexp, $varValue, Widget $objWidget)
	{
		switch ($strRegexp)
		{
			case 'url_allowed_hosts_only':
				if(!$objWidget->mandatory && strlen($varValue)==0)
				{
					return true;
				}
				$varValue = $this->idnaEncodeUrl($varValue);
				if (!preg_match('/^[a-zA-Z0-9\.\+\/\?#%:,;\{\}\(\)\[\]@&=~_-]*$/', $varValue) || (!($strHost = parse_url($varValue, PHP_URL_HOST))))
				{
					$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['url'], $objWidget->label));
				} else {
					if (!in_array($strHost, $objWidget->catalog['allowedHosts']))
					{
						$objWidget->addError(sprintf($GLOBALS['TL_LANG']['tl_catalog_items']['wrongUrl'], $strHost));
					}
				}
				return true;
				break;
		}
		return false;
	}

	private function calcConfig(&$field, $objRow)
	{
		$field['eval']['catalog']['calcValue'] = $objRow->calcValue;

		$field['load_callback'][] = array('Catalog', 'loadCalc');
		$field['save_callback'][] = array('Catalog', 'saveCalc');

		$this->formatConfig($field, $objRow);
	}

	private function formatConfig(&$field, $objRow)
	{
		$field['eval']['catalog']['formatPrePost'] = deserialize($objRow->formatPrePost, true);
		$field['eval']['catalog']['formatFunction'] = $objRow->format ? $objRow->formatFunction : '';
		$field['eval']['catalog']['formatStr'] = $objRow->formatStr;
	}

	/**
	 * Return a form to choose a CSV file and import it
	 * @param object
	 * @return string
	 */
	public function exportItems(DataContainer $dc)
	{
		if ($this->Input->get('key') != 'export')
		{
			return '';
		}

		$objCatalog = $this->Database->prepare("SELECT id,tableName FROM tl_catalog_types WHERE id=?")
					->limit(1)
					->execute($dc->id);

		if (!$objCatalog->numRows)
		{
			return '';
		}

		// get fields
		$objFields = $this->Database->prepare("SELECT colName, type, calcValue FROM tl_catalog_fields WHERE pid=? ORDER BY sorting")
					->execute($objCatalog->id);

		$arrFields = array();
		while ($objFields->next())
		{
			$arrFields[] = ($objFields->type != 'calc') ? $objFields->colName : '('.$objFields->calcValue . ') AS '.$objFields->colName.'_calc';
		}

		// get records
		$arrExport = array();
		$objRow = $this->Database->prepare("SELECT ". implode(', ', $arrFields) ." FROM ".$objCatalog->tableName." WHERE pid=?")
					->execute($objCatalog->id);

		if ($objRow->numRows)
		{
			$arrExport = $objRow->fetchAllAssoc();
		}

		// start output
		$exportFile =  'export_'.$objCatalog->tableName.'_' . date("Ymd-Hi");

		header('Content-Type: application/csv');
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: attachment; filename="' . $exportFile .'.csv"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Expires: 0');

		$output = '';
		$output .= '"'.implode('","', array_keys($arrExport[0])).'"'. "\n" ;

		foreach ($arrExport as $export)
		{
			$output .= '"' . implode('"'.$GLOBALS['TL_CONFIG']['catalog']['csvDelimiter'].'"', str_replace("\"", "\"\"", $export)).'"' . "\n";
		}

		echo $output;
		exit;

	}

	/**
	 * Return a form to choose a CSV file and import it
	 * @param object
	 * @return string
	 */
	public function importCSV(DataContainer $dc)
	{
		if ($this->Input->get('key') != 'import')
		{
			return '';
		}

		// check if import is enabled
		$objCatalog = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=?")
				->limit(1)
				->execute($this->Input->get('id'));

		if (!$objCatalog->numRows || !$objCatalog->import)
		{
			return;
		}

		if ($objCatalog->import)
		{
			// check user setting as well
			$this->import('BackendUser', 'User');
			if ($objCatalog->importAdmin && !$this->User->isAdmin)
			{
				return;
			}
		}

		$blnDelete = ($objCatalog->importDelete);

		// Import CSV
		if (strlen($this->Input->get('token')) && $this->Input->get('token') == $this->Session->get('tl_csv_import'))
		{

			$referer = preg_replace('/&(amp;)?(start|dpc|token|source|separator|source_save|import|removeData)=[^&]*/', '', $this->Environment->request);

			$_SESSION['TL_CONFIRM'] = null;
			$_SESSION['TL_ERROR'] = null;

			if (!$this->Input->get('source'))
			{
				$this->Session->set('tl_csv_import', null);
				$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['ERR']['noCSVFile'];

				$this->redirect($referer);
			}

			$objFile = new File($this->Input->get('source'));
			if ($objFile->extension != 'csv')
			{
				$this->Session->set('tl_csv_import', null);
				$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $objFile->extension);

				$this->redirect($referer);
			}

			// Get separator
			switch ($this->Input->get('separator'))
			{
				case 'semicolon':
					$strSeparator = ';';
					break;

				case 'tabulator':
					$strSeparator = '\t';
					break;

				default:
					$strSeparator = ',';
					break;
			}

			// open file
			if (!($csvFile = fopen(TL_ROOT .'/'. $this->Input->get('source'), 'r')))
			{
				$this->Session->set('tl_csv_import', null);
				$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['ERR']['noCSVData'];

				$this->redirect($referer);
			}


			$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=?")
						->execute($this->Input->get('id'));
			if (!$objFields->numRows)
			{
				return '';
			}
			$fieldlist = $objFields->fetchEach('colName');
			unset($objFields);

			$headercount = 0;
			$headerRow = fgetcsv($csvFile, 4096, $strSeparator);
			foreach ($headerRow as $field)
			{
				if (in_array($field, $fieldlist))
				{
					$headercount++;
				}
			}

			if ($headercount != count($fieldlist))
			{
				$this->Session->set('tl_csv_import', null);
				$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['ERR']['noHeaderFields'];
				$this->redirect($referer);
			}

			if (!$objCatalog->numRows)
			{
				$this->Session->set('tl_csv_import', null);
				$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['ERR']['noCatalog'];
				$this->redirect($referer);
			}

			if ($blnDelete && $this->Input->get('removeData'))
			{
				$this->Database->prepare("DELETE FROM ".$objCatalog->tableName." WHERE pid=?")
														->execute($this->Input->get('id'));
			}

			echo '<div style="font-family:Verdana, sans-serif; font-size:11px; line-height:16px; margin-bottom:12px;">';
			$addcount = 0;

			// keep fetching a line from the file until end of file
			while ($row = fgetcsv($csvFile, 4096, $strSeparator))
			{
				$objRow = $this->Database->prepare("REPLACE INTO ".$objCatalog->tableName." (pid, tstamp, ".implode(',',$headerRow).") VALUES (?,?".str_repeat(',?', $headercount).")")
													->execute(array_merge(array($this->Input->get('id'), time()), $row));

				$addcount ++;
				echo ($addcount % 1000 == 0) ? 'Imported '.$addcount.' items.<br />' : '';
			}
			fclose($csvFile);

			echo '<br />Imported '.$addcount.' items to <strong>' . $objCatalog->name . '</strong> catalog<br />';

			echo '<div style="margin-top:12px;">';

			$this->Session->set('tl_csv_import', null);

			$_SESSION['TL_CONFIRM'][] = sprintf($GLOBALS['TL_LANG']['ERR']['importSuccess'], $addcount);

			echo '<script type="text/javascript">setTimeout(\'window.location="' . $this->Environment->base . $referer . '"\', 1000);</script>';
			echo '<a href="' . $this->Environment->base . $referer . '">Please click here to proceed if you are not using JavaScript</a>';

			echo '</div></div>';
			exit;

		}

		$strToken = md5(uniqid('', true));
		$this->Session->set('tl_csv_import', $strToken);

		$objTree = new FileTree($this->prepareForWidget($GLOBALS['TL_DCA']['tl_catalog_items']['fields']['source'], 'source', null, 'source', 'tl_catalog_items'));

		// Return form
		return '
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=import', '', $this->Environment->request)).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBT']).'">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['tl_catalog_items']['import'][1].'</h2>'.$this->getMessages().'

<form action="'.ampersand($this->Environment->script, ENCODE_AMPERSANDS).'" id="tl_csv_import" class="tl_form" method="get">
<div class="tl_formbody_edit">
<input type="hidden" name="do" value="' . $this->Input->get('do') . '" />
<input type="hidden" name="table" value="' . $this->Input->get('table') . '" />
<input type="hidden" name="key" value="' . $this->Input->get('key') . '" />
<input type="hidden" name="id" value="' . $this->Input->get('id') . '" />
<input type="hidden" name="token" value="' . $strToken . '" />

<div class="tl_tbox">
  <h3><label for="separator">'.$GLOBALS['TL_LANG']['MSC']['separator'][0].'</label></h3>
  <select name="separator" id="separator" class="tl_select" onfocus="Backend.getScrollOffset();">
    <option value="comma">'.$GLOBALS['TL_LANG']['MSC']['comma'].'</option>
    <option value="semicolon">'.$GLOBALS['TL_LANG']['MSC']['semicolon'].'</option>
    <option value="tabulator">'.$GLOBALS['TL_LANG']['MSC']['tabulator'].'</option>
  </select>'.(strlen($GLOBALS['TL_LANG']['MSC']['separator'][1]) ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['MSC']['separator'][1].'</p>' : '').'
  <h3><label for="source">'.$GLOBALS['TL_LANG']['tl_catalog_items']['source'][0].'</label> <a href="'.((version_compare(VERSION.'.'.BUILD, '2.9.0', '>='))?'contao':'typolight').'/files.php" title="' . specialchars($GLOBALS['TL_LANG']['MSC']['fileManager']) . '" onclick="Backend.getScrollOffset(); this.blur(); Backend.openWindow(this, 750, 500); return false;">' . $this->generateImage('filemanager.gif', $GLOBALS['TL_LANG']['MSC']['fileManager'], 'style="vertical-align:text-bottom;"') . '</a></h3>
'.$objTree->generate().(strlen($GLOBALS['TL_LANG']['tl_catalog_items']['source'][1]) ? '
  <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_catalog_items']['source'][1].'</p>' : '').
($blnDelete ? '
  <div id="ctrl_removeData" class="tl_checkbox_single_container"><input type="checkbox" name="removeData" id="opt_removeData_0" value="1" class="tl_checkbox" onfocus="Backend.getScrollOffset();" onclick="if (this.checked && !confirm(\''.sprintf($GLOBALS['TL_LANG']['MSC']['removeDataConfirm'],$objCatalog->name).'\')) return false; Backend.getScrollOffset();" /> <label for="opt_removeData_0">' . $GLOBALS['TL_LANG']['tl_catalog_items']['removeData'][0] . '</label></div>
' . (($GLOBALS['TL_LANG']['tl_catalog_items']['removeData'][1] && $GLOBALS['TL_CONFIG']['showHelp']) ? '
  <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['tl_catalog_items']['removeData'][1] . '</p>' : '') : '') . '
</div>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
<input type="submit" name="import" id="import" class="tl_submit" alt="CSV import" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['tl_catalog_items']['import'][0]).'" />
</div>

</div>
</form>';
	}

	/**
	 * Return the "toggle visibility" button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		if(!$this->publishField)
		{
			return;
		}
		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row[$this->publishField] ? '0' : '1');
		if (!$row[$this->publishField])
		{
			$icon = 'invisible.gif';
		}
		return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}
}

?>