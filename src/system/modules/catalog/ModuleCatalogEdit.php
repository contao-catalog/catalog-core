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

require_once(TL_ROOT . '/system/drivers/DC_DynamicTable.php');
/**
 * Class DC_DynamicTableEdit
 * NOTE TO EXTENSION DEVELOPERS!
 * watch out, this is a massive compromise and subject to change.
 * I came up with this ad-hoc solution in order to supply a valid DataContainer in the onload and onsave callbacks
 * but will rewrite the frontend editing from scratch in the future using a real FE DC driver.
 * Therefore you should not rely on the concrete implementation of this class below.
 *
 * @copyright	CyberSpectrum 2011
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
 *
 */
class DC_DynamicTableEdit extends DC_DynamicTable
{
	/**
	* Holds the Catalog information dataset from tl_catalog_types
	*/
	protected $objCatalogType = NULL;

	/**
	 * Holds the reference of the parent edit module
	 */	
	protected $objModule=NULL;

	protected function combiner($names) {}
	protected function generateButtons($arrRow, $strTable, $arrRootIds=array(), $blnCircularReference=false, $arrChildRecordIds=null, $strPrevious=null, $strNext=null) {}
	protected function generateGlobalButtons($blnForceSeparator=false) {}
	protected function row() {}
	protected function switchToEdit($id) {}
	protected function copyChilds($table, $insertID, $id, $parentId) {}
	protected function filterMenu() {}
	protected function formatCurrentValue($field, $value, $mode) {}
	protected function formatGroupHeader($field, $value, $mode, $row) {}
	protected function generateTree($table, $id, $arrPrevNext, $blnHasSorting, $intMargin=0, $arrClipboard=false, $blnCircularReference=false, $protectedPage=false) {}
	protected function getNewPosition($mode, $pid=null, $insertInto=false) {}
	protected function limitMenu($blnOptional=false) {}
	protected function listView() {}
	protected function panel() {}
	protected function parentView() {}
	protected function reviseTable() {}
	protected function save($varValue) {}
	protected function searchMenu() {}
	protected function sortMenu() {}
	protected function treeView() {}

	public function help() {}
	public function __get($strKey) {
		$varValue = parent::__get($strKey);
		return $varValue;
	}
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'field':
				$this->strField = $varValue;
				break;
			default:
				$this->$strKey = $varValue;
		}
	}

	public function ajaxTreeView($id, $level) {}
	public function copy($blnDoNotRedirect=false) {}
	public function copyAll() {}
	public function create($set=array()) {}
	public function cut($blnDoNotRedirect=false) {}
	public function cutAll() {}
	public function delete($blnDoNotRedirect=false) {}
	public function deleteAll() {}
	public function deleteChilds($table, $id, &$delete) {}
	public function edit($intID=false, $ajaxId=false) {}
	public function editAll($intId=false, $ajaxId=false) {}
	public function getPalette() {}
	public function move() {}
	public function overrideAll() {}
	public function show() {}
	public function showAll() {}
	public function undo() {}
	public function __construct($strTable, $objCatalogType, $objModule, $arrData)
	{
		// TODO: is anything missing in here? remember to only include stuff that we really need in this stub implementation.
		$this->objCatalogType = $objCatalogType;
		$this->objActiveRecord = (object)$arrData;
		$this->intId = $arrData['id'];
		$this->strTable = $strTable;
		$this->objModule = $objModule;
		$this->handleOnLoadCallbacks();
		$this->import('Database');
	}

	// our private functions to automate the catalog saving...

	/**
	 * Autogenerate a catalog alias if it has not been set yet
	 * @param mixed
	 * @param object
	 * @return string
	 */
	public function generateAlias()
	{
		// without an aliasCol there is no need for generating an alias
		if (!strlen($this->objCatalogType->aliasField))
		{
			return;
		}

		// Preferring the fields aliasTitle over the catalog titleField
		$aliasTitle = $this->objCatalogType->titleField;
		$objCatalogAliasField = $this->Database->prepare("SELECT aliasTitle FROM tl_catalog_fields WHERE tl_catalog_fields.pid=? AND tl_catalog_fields.colName=?")
												->execute($this->catalog,$this->objCatalogType->aliasField);
		if ($objCatalogAliasField->numRows && strlen($objCatalogAliasField->aliasTitle))
			$aliasTitle = $objCatalogAliasField->aliasTitle;

		$aliasCol = $this->objCatalogType->aliasField;
		$strAlias = $this->objActiveRecord->$aliasCol;
		// Generate alias if there is none
		$autoAlias = false;
		if (!strlen($strAlias))
		{
			$autoAlias = true;
			$strAlias = standardize($this->objActiveRecord->$aliasTitle);
		}
		// Check whether the catalog alias exists
		$objAlias = $this->Database->prepare("SELECT id FROM ".$this->strTable." WHERE ".$this->objCatalogType->aliasField.'=?'.($this->objActiveRecord->id?' AND id!=?':''))
								   ->execute($strAlias, $this->objActiveRecord->id);
		if ($objAlias->numRows && !$autoAlias)
		{
			//throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $arrData[$aliasCol]));
			// TODO: we can not throw an exception here as it would kill the FE => not an option.
			//       we can not reject saving as we might already have saved it (coming from insert).
			//       So I simply work as if it was autogenerated. Find a better solution for this! (c.schiffler 2009-09-10)
			$autoAlias = true;
		}

		// Add ID to alias if it exists.
		if ($objAlias->numRows && $autoAlias)
		{
			$strAlias .= '-' . $id;
		}
		$this->objActiveRecord->$aliasCol = $strAlias;
	}

	private function handleOnLoadCallbacks()
	{
		foreach($this->objActiveRecord as $field=>$data)
		{
			$fieldConf = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
			$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldConf['eval']['catalog']['type']];
			if(is_array($fieldType) && array_key_exists('fieldDef', $fieldType) && array_key_exists('load_callback', $fieldType['fieldDef']) && is_array($fieldType['fieldDef']['load_callback']))
			{
				foreach ($fieldType['fieldDef']['load_callback'] as $callback)
				{
					$this->field = $field;
					if (!is_object($this->$callback[0]))
						$this->import($callback[0]);
					$this->objActiveRecord->$field=$this->$callback[0]->$callback[1]($data, $this);
				}
				$this->field = '$field';
			}
		}
	}

	private function handleOnSaveCallbacks()
	{
		foreach($this->objActiveRecord as $field=>$data)
		{
			$fieldConf = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
			$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldConf['eval']['catalog']['type']];
			if(is_array($fieldType) && array_key_exists('fieldDef', $fieldType)  && array_key_exists('save_callback', $fieldType['fieldDef']) && is_array($fieldType['fieldDef']['save_callback']))
			{
				foreach ($fieldType['fieldDef']['save_callback'] as $callback)
				{
					$this->field = $field;
					if (!is_object($this->$callback[0]))
						$this->import($callback[0]);
					$this->objActiveRecord->$field=$this->$callback[0]->$callback[1]($data, $this);
				}
				$this->field = '$field';
			}
		}
	}

	protected function handleOnSubmit()
	{
		// Trigger the onsubmit_callback
		if (is_array($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback']))
		{
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['config']['onsubmit_callback'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($this);
			}
		}
	}

	public function itemUpdate()
	{
		$this->generateAlias();
		$arrData = (array)$this->objActiveRecord;
		$this->handleOnSaveCallbacks();
		// Update item
		$this->objActiveRecord->pid = $this->objCatalogType->id;
		$this->objActiveRecord->tstamp = time();
		$arrRecordData=array();
		foreach(array_merge($this->objModule->catalog_edit, array('tstamp', 'pid', $this->objCatalogType->aliasField)) as $field)
		{
			$arrRecordData[$field] = $this->objActiveRecord->$field;
		}

		$objUpdatedItem = $this->Database->prepare('UPDATE '.$this->strTable.' %s WHERE id=?')
				->set($arrRecordData)
				->execute($this->objActiveRecord->id);
		$this->handleOnSubmit();
		// HOOK: pass data to HOOKs to be able to do something when we updated an item.
		if (isset($GLOBALS['TL_HOOKS']['catalogFrontendUpdate']) && is_array($GLOBALS['TL_HOOKS']['catalogFrontendUpdate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['catalogFrontendUpdate'] as $callback)
			{
				if (!is_object($this->$callback[0]))
					$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($arrData, $this->objModule, $this->strTable);
			}
		}
	}

	/**
	 * Create a new item and redirect
	 * @param array
	 */
	public function itemInsert()
	{
		$this->objActiveRecord->pid = $this->objCatalogType->id;
		$this->objActiveRecord->tstamp = time();
		$this->generateAlias();
		$arrData = (array)$this->objActiveRecord;
		// insert an "empty" dataset to get an id.
		$objNewItem = $this->Database->prepare('INSERT INTO '.$this->strTable.' %s')
				->set(array('pid' => $this->objActiveRecord->pid, 'tstamp' => $this->objActiveRecord->tstamp))
				->execute();
		$arrData['id'] = $this->objActiveRecord->id = $objNewItem->insertId;
		$this->intId = $arrData['id'];
		$this->handleOnSaveCallbacks();
		$arrRecordData=array();
		foreach(array_merge($this->objModule->catalog_edit, array('tstamp', 'pid', $this->objCatalogType->aliasField)) as $field)
		{
			$arrRecordData[$field] = $this->objActiveRecord->$field;
		}
		$objUpdatedItem = $this->Database->prepare('UPDATE '.$this->strTable.' %s WHERE id=?')
				->set($arrRecordData)
				->execute($this->objActiveRecord->id);
		// HOOK: pass data to HOOKs to be able to do something when we inserted an item.
		$this->handleOnSubmit();
		if (isset($GLOBALS['TL_HOOKS']['catalogFrontendInsert']) && is_array($GLOBALS['TL_HOOKS']['catalogFrontendInsert']))
		{
			foreach ($GLOBALS['TL_HOOKS']['catalogFrontendInsert'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($arrData, $this->objModule, $this->strTable);
			}
		}
	}
}

/**
 * Class ModuleCatalogEdit
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara,
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
 *
 */
class ModuleCatalogEdit extends ModuleCatalog
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogedit';

	/**
	 * Redirect
	 * @var string
	 */
	protected $referrerUrl;

	protected $objDCEdit = NULL;

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG EDIT ###';

			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			if (version_compare(VERSION.'.'.BUILD, '2.9.0', '>='))
				$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
			else
				$objTemplate->href = 'typolight/main.php?do=modules&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;

		$this->catalog_edit = deserialize($this->catalog_edit);
		$this->catalog_edit_default_value = deserialize($this->catalog_edit_default_value, true);
		// needed for onsubmit_callback etc.
		$this->loadDataContainer('tl_catalog_items');
		define(CURRENT_ID, $this->catalog);

		return parent::generate();
	}


	protected function editRecordAllowed($field, $arrValues)
	{
		// HOOK: additional permission checks if this field allows editing of this record (for the current user).
		$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['catalog']['type']];
		if(is_array($fieldType) && array_key_exists('checkPermissionFERecordEdit', $fieldType) && is_array($fieldType['checkPermissionFERecordEdit']))
		{
			foreach ($fieldType['checkPermissionFERecordEdit'] as $callback)
			{
				$this->import($callback[0]);
				// TODO: Do we need more parameters here?
				if(!($this->$callback[0]->$callback[1]($this->strTable, $field, (array)$arrValues)))
				{
					$this->Template->error = $GLOBALS['TL_LANG']['MSC']['catalogItemEditingDenied'];
					// Send 403 header
					header('HTTP/1.0 403 Forbidden');
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Generate module
	 */
	protected function compile()
	{
		global $objPage;
		$this->refererUrl = $this->getReferer(ENCODE_AMPERSANDS);
		$this->Template->catalog = '';
		$this->Template->referer = $this->refererUrl;
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
		$objCatalogType = $this->Database->prepare('SELECT * FROM tl_catalog_types WHERE id=?')
										->execute($this->catalog);
		if (!$objCatalogType->numRows || !strlen($objCatalogType->tableName))
		{
			$this->Template->error = $GLOBALS['TL_LANG']['MSC']['catalogInvalid'];
			// Do not index the page
			$objPage->noSearch = 1;
			$objPage->cache = 0;
			// Send 404 header
			header('HTTP/1.0 404 Not Found');
			return;
		}

		// check permissions here


		// edit existing, else present add new screen
		$blnModeAdd = false;
		$arrValues = array();

		// check existing items/alias passed as parameter?
		/*
			$objCatalog = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE (CAST(id AS CHAR)=?".$strAlias.")")
											->limit(1)
											->execute($this->Input->get('items'), $this->Input->get('items'));
		*/
		// We have to handle numeric input data differently from string input, as otherwise
		// we have the problem that within MySQL the following is really true:
		// Given: id INT(10), alias VARCHAR(...) and a string to match in a query 'somestring'.
		// id=15
		// alias='15-some-alias-beginning-with-digits'
		// somestring='15'
		// in MySQL this all(!) matches in the original Query here, therefore we have to change it
		// (and in all other modules aswell).
		// So, if the input is numeric, do id lookup, otherwise do the alias lookup.
		// Note we are enforcing a "no numeric aliases policy here but we
		// can live with that as we would get random results anyway.
		$value=$this->Input->get('items');
		$objCatalog = false;
		if(strlen($value))
		{
			$strAlias = is_numeric($value) ? 'id' : ($objCatalogType->aliasField ? $objCatalogType->aliasField : '');
			if(strlen($strAlias))
			{
				$objCatalog = $this->Database->prepare('SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=' . $this->strTable . '.pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id='.$this->strTable.'.pid) AS parentJumpTo FROM '.$this->strTable.' WHERE '. $strAlias . '=?')
											->limit(1)
											->execute($value);
			}
		}

		// unpack restriction values.
		$arrValues = $objCatalog?$objCatalog->fetchAssoc():array();
		// initialize value to restricted value as we might not be allowed to edit this field but the field shall
		// revert to some default setting (published flag etc.)
		// NOTE: This affects all fields mentioned in "catalog_edit_default_value", not just those selected for editing.
		if (($this->Input->post('FORM_SUBMIT') == 'tl_catalog_items') 
		&& $this->catalog_edit_use_default 
		&& $this->catalog_edit_default_value)
		{
			$arrValues = array_merge($arrValues, $this->catalog_edit_default_value);
		}
		$this->objDCEdit = new DC_DynamicTableEdit($this->strTable, $objCatalogType, $this, $arrValues);

		// if no item, then check if add allowed and then show add form
		if (!$objCatalog || $objCatalog->numRows < 1 )
		{
			$blnModeAdd = true;
			// Load defaults.
			$arrValues = array();
		}
		else
		{
			// check if editing of this record is disabled for frontend.
			foreach ($this->catalog_edit as $key=>$field)
			{
				if(!$this->editRecordAllowed($field, $this->objDCEdit->activeRecord))
					return;
			}
		}

		// Captcha
		if (!$this->disableCaptcha)
		{
			$arrCaptcha = array
			(
				'id'=>'catalog',
				'label'=>$GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'mandatory'=>true,
				'required'=>true
			);

			$objCaptcha = new FormCaptcha($arrCaptcha);

			if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_items')
			{
				$objCaptcha->validate();

				if ($objCaptcha->hasErrors())
				{
					$doNotSubmit = true;
				}
			}
		}

		$i = 0;

		$this->import('FrontendUser', 'User');
		// we have to determine if we have upload fields for the form enctype.
		$hasUpload = false;
		// Build form
		$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'];
		foreach ($this->catalog_edit as $field)
		{

			$arrData = $fieldConf[$field];
			// check permissions here

			// check if editing of this field is restricted to a certain user group.
			if(is_array($arrData['eval']['catalog']['editGroups']) && count($arrData['eval']['catalog']['editGroups']))
			{
				$allow_field = false;
				foreach($arrData['eval']['catalog']['editGroups'] as $group)
				{
					if($this->User->isMemberOf($group))
					{
						$allow_field = true;
						break;
					}
				}
				if(!$allow_field)
					continue;
			}

			// HOOK: additional permission checks if this field may be edited (for the current user).
			$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$arrData['eval']['catalog']['type']];
			if(is_array($fieldType) && array_key_exists('checkPermissionFEEdit', $fieldType) && is_array($fieldType['checkPermissionFEEdit']))
			{
				foreach ($fieldType['checkPermissionFEEdit'] as $callback)
				{
					$this->import($callback[0]);
					// TODO: Do we need more parameters here?
					if(!($this->$callback[0]->$callback[1]($fieldConf)))
						continue;
				}
			}
			unset($objWidgetUpload);
			//$strUpload = '';
			$inputType = $arrData['inputType'];
			if ($inputType == 'fileTree')
			{
				$inputType = 'upload';
				$arrData['eval']['mandatory'] = false;
			}

			if ($inputType == 'tableTree')
			{
				// tags
				if ($arrData['eval']['fieldType'] == 'checkbox')
				{
					$inputType = 'checkbox';
					$arrData['eval']['multiple'] = true;
				}

				// select
				if ($arrData['eval']['fieldType'] == 'radio')
				{
					$inputType = 'select';
				}
			}
			$strClass = $GLOBALS['TL_FFL'][$inputType];
			// some things are only present in the backend for now, like the timePeriod, but 
			// are safe to be called also in FE. So we do here.
			// TODO: We should export this to some other location instead of hardcoding it here.
			if((!$strClass) && in_array($inputType, array('timePeriod')))
			{
				$strClass = $GLOBALS['BE_FFL'][$inputType];
			}

			// Continue if the class is not defined
			if (!$this->classFileExists($strClass))
			{
				continue;
			}

			$objWidget = new $strClass($this->prepareForWidget($arrData, $field));

			$objWidget->storeValues = true;
			$objWidget->rowClass = 'row_' . $i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');

			// Increase the row count if its a password field
			if ($objWidget instanceof FormPassword)
			{
				$objWidget->rowClassConfirm = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
			}

			// add Explanation with existing Files/Images
			if ($objWidget instanceof FormFileUpload)
			{
				$objWidgetUpload = new FormExplanation($this->prepareForWidget($arrData, $field));
				$objWidgetUpload->rowClass = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
			}

			// Validate input
			if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_items')
			{
				// We have to handle file inputs differently here, as FormFileUpload does not export a value, it saves to the $_SESSION instead.
				if($inputType == 'upload')
				{
					// prepare all widget settings as FormFileUpload expects them to be.
					$objWidget->storeFile = true;
					if($fieldConf[$field]['eval']['extensions'])
						$objWidget->extensions = $fieldConf[$field]['eval']['extensions'];
					else
						$objWidget->extensions = $fieldConf[$field]['eval']['catalog']['showImage'] ? $GLOBALS['TL_CONFIG']['validImageTypes'] : $GLOBALS['TL_CONFIG']['uploadTypes'];
					if($fieldConf[$field]['eval']['path'])
					{
						$objWidget->uploadFolder = $fieldConf[$field]['eval']['path'] . '/' . $this->strTable;
					} else {
						$objWidget->uploadFolder = 'tl_files/catalog_' . $this->strTable;
					}
					// ensure folder exists. So we create a Folder object which will create the folder if it
					// does not exist and unset it immediately again as we do not need it for anything else.
					// Maybe we can find a better solution sometime.
					$dummyFolder = new Folder($objWidget->uploadFolder);
					unset($dummyFolder);
					// Now validate, this will move the file to the folder if everything is ok and store the information
					// in the session.
					$objWidget->validate();
					// use existing value(s) from database as base.
					$varValue = deserialize($this->objDCEdit->activeRecord->$field);
					if(!is_array($varValue))
						$varValue = array($varValue);
					// was this file uploaded?
					if(isset($_SESSION['FILES'][$field]))
					{
						$filename = $objWidget->uploadFolder . '/' . $_SESSION['FILES'][$field]['name'];
						// now we have to remove this file from the session as we have processed it.
						unset($_SESSION['FILES'][$field]);
						if($fieldConf[$field]['eval']['catalog']['multiple']=='1')
						{
							// must allow multiple files, add to existing values from DB.
							$varValue[] = $filename;
						} else {
							// TODO: shall we delete the old file from disk?
							// overwrite old filename.
							$varValue = array($filename);
						}
					}
					// now for the deletion of images from the field.
					$remaining=array();
					foreach($varValue as $file)
					{
						// TODO: shall we delete the file from disk?
						if(!$this->Input->post('unlink_'.$field.'_' . md5($file)))
						{
							$remaining[]=$file;
						}
					}
					// convert back to string for single file fields.
					if($fieldConf[$field]['eval']['catalog']['multiple'] == '1')
						$varValue = serialize($remaining);
					else
						$varValue = $remaining[0];

					// set a flag that this input has changed.
					$isChangedFileField = ($varValue != $this->objDCEdit->activeRecord->$field);
				}
				else
				{
					$isChangedFileField = false;
					$objWidget->validate();
					$varValue = $objWidget->value;
				}


				// Convert date formats into timestamps
				if (in_array($arrData['eval']['rgxp'], array('date', 'time', 'datim')) && strlen($varValue))
				{
					$objDate = new Date($varValue, $GLOBALS['TL_CONFIG'][$arrData['eval']['rgxp'] . 'Format']);
					$varValue = $objDate->tstamp;
				}

				// Make sure that unique fields are unique
				if ($blnModeAdd && $fieldConf[$field]['eval']['unique'])
				{
					$objUnique = $this->Database->prepare('SELECT * FROM '.$this->strTable.' WHERE ' . $field . '=?')
												->limit(1)
												->execute($varValue);
					if ($objUnique->numRows)
					{
						$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['unique'], (strlen($arrData['label'][0]) ? $arrData['label'][0] : $field)));
					}
				}

				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
					// initialize to old value as otherwise we can not see anything in FE when there was an error in $objWidget->validate();
					if($inputType == 'upload')
						$objWidgetUpload->text = $this->formatValue($i, $field, $this->objDCEdit->activeRecord->$field, false);
				}
				// Store current value - NOTE: FormfileUpload does not set the flag for submitInput, therefore we can not use submitInput there.
				elseif ($objWidget->submitInput() || ($inputType == 'upload' && $isChangedFileField))
				{
					if ($arrData['eval']['catalog']['type'] == 'tags')
					{
						$varValue = implode(',', deserialize($objWidget->value, true));
					}
					if (strlen($fieldConf[$field]['eval']['rte']) && $GLOBALS['TL_CONFIG']['useRTE'])
					{
						$varValue = $this->Input->postHtml($field, $objWidget->decodeEntities);
					}
					// check if field has restricted default.
					if(!in_array($field, $this->catalog_edit_default_value))
						$this->objDCEdit->activeRecord->$field = $varValue;
				}
			} // end: if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_items')
			elseif (!$blnModeAdd)
			{
				// if in editing mode from here on, we have to restrict some values and correct the output.
				$objWidget->value = $this->objDCEdit->activeRecord->$field;

				if ($arrData['eval']['catalog']['type'] == 'checkbox' && count($objWidget->options) == 1)
				{
					$objWidget->label = '';
				}

				if ($arrData['eval']['catalog']['type'] == 'tags')
				{
					$objWidget->value = explode(',', $objWidget->value);
				}

				if ($arrData['eval']['catalog']['type'] == 'file')
				{
					// generate file list and add delete checkboxes.
					$showImage = $fieldConf[$field]['eval']['catalog']['showImage'];
					$files=$this->parseFiles($i, $field, $this->objDCEdit->activeRecord->$field);
					$output ='';
					$counter = 0;
					foreach($files['html'] as $file)
					{
						$class = (($counter == 0) ? ' first' : '') . ((($counter % 2) == 0) ? ' even' : ' odd');
						$name = 'unlink_'.$field.'_' . md5($files['files'][$counter]);
						$output .='<div class="'.($showImage ? 'image' : 'file').$class.'">'.$file.'<span class="delete unlinkcheckbox ' . $class . '"><input class="checkbox unlinkcheckbox ' . $class . '" type="checkbox" name="' . $name . '" id="' . $name . '" value="1" /><label for="'. $name .'">'. sprintf($GLOBALS['TL_LANG']['MSC']['removeImage'], basename($files['files'][$counter])) .'</label></span></div>';
						$counter++;
					}
					$objWidgetUpload->text = $output;
				}

			}

			// Add datepicker
			if (in_array($arrData['eval']['rgxp'], array('date', 'time', 'datim')))
			{
				$objDate = new Date($objWidget->value, $GLOBALS['TL_CONFIG'][$arrData['eval']['rgxp'] . 'Format']);
				$objWidget->value = $objDate->$arrData['eval']['rgxp'];
				//$objWidget->datepicker = '
				$GLOBALS['TL_HEAD'][]='
				<script type="text/javascript"><!--//--><![CDATA[//><!--
				window.addEvent(\'domready\', function() { ' . sprintf($this->getDatePickerString(), 'ctrl_' . $objWidget->id) . ' });
				//--><!]]></script>';
				// files moved since 2.8 RC1
				if(version_compare(VERSION.'.'.BUILD, '2.8.0', '<'))
				{
					$GLOBALS['TL_HEAD'][]='<script src="plugins/calendar/calendar.js" type="text/javascript"></script>';
					$GLOBALS['TL_CSS'][] = 'plugins/calendar/calendar.css';
				} else {
					$GLOBALS['TL_HEAD'][]='<script src="plugins/calendar/js/calendar.js" type="text/javascript"></script>';
					$GLOBALS['TL_CSS'][] = 'plugins/calendar/css/calendar.css';
				}
			}


			// Register field name for rich text editor usage
			if (strlen($fieldConf[$field]['eval']['rte']) && $GLOBALS['TL_CONFIG']['useRTE'])
			{
				// EXPERIMENTAL!! RTE in Frontend.
				$GLOBALS['TL_RTE']['type'] = $fieldConf[$field]['eval']['rte'];
				$GLOBALS['TL_RTE']['fields'][] = 'ctrl_' . $field;

				// TODO: make this configurable?
				$objWidget->cols = 70;
				$objWidget->rows = 12;
			}

			if(is_object($objWidgetUpload))
			{
				$arrFields[$field] .= $objWidgetUpload->parse();
				// file uploads need 'multipart/form-data'
				$hasUpload=true;
			}
			$arrFields[$field] .= $objWidget->parse();

			++$i;
		}

		// Captcha
		if (!$this->disableCaptcha)
		{
			$objCaptcha->rowClass = 'row_'.$i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');
			$strCaptcha = $objCaptcha->parse();

			$arrFields['captcha'] .= $strCaptcha;
		}

		$this->Template->rowLast = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');

		// Create new entry or update the old one if there are no errors
		if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_items' && !$doNotSubmit)
		{
			if ($blnModeAdd)
			{
				$this->objDCEdit->itemInsert();
			}
			else
			{
				$this->objDCEdit->itemUpdate();
			}
			// check which submit method was used and redirect then.
			if($this->Input->post('save'))
			{
				// stay on this page with current entry.
				global $objPage;
				$this->redirect(ampersand($this->generateFrontendUrl($objPage->row(), ($GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;items=' : '/items/') . ($strAlias && $this->objDCEdit->activeRecord->$strAlias ? $this->objDCEdit->activeRecord->$strAlias : $this->objDCEdit->activeRecord->id))));
			} else if($this->Input->post('saveNcreate'))
			{	// stay on this page but without id.
				global $objPage;
				$this->redirect(ampersand($this->generateFrontendUrl($objPage->row())));
			} else if($this->Input->post('saveNclose'))
			{	// follow jumpTo
				$this->redirect($this->generateCatalogNavigationUrl('items', $strAlias && $this->objDCEdit->activeRecord->$strAlias ? $this->objDCEdit->activeRecord->$strAlias : $this->objDCEdit->activeRecord->id));
			}
		}

		// Set template form
		$objTemplate = new FrontendTemplate($this->catalog_template);
		$objTemplate->enctype = $hasUpload ? 'multipart/form-data' : 'application/x-www-form-urlencoded';

		$objTemplate->field = implode('',$arrFields);
		$objTemplate->formId = 'tl_catalog_items';
		$objTemplate->action = ampersand($this->Environment->request, ENCODE_AMPERSANDS);

		// Rich text editor configuration
		if (count($GLOBALS['TL_RTE']) && $GLOBALS['TL_CONFIG']['useRTE'])
		{
			$this->base = $this->Environment->base;
			$this->brNewLine = $GLOBALS['TL_CONFIG']['pNewLine'] ? false : true;
			$this->rteFields = implode(',', $GLOBALS['TL_RTE']['fields']);
			if ($GLOBALS['TL_RTE']['type'] == 'tinyMCE')
			{
				$GLOBALS['TL_RTE']['type'] = 'tinyFrontend';
			}

			$strFile = sprintf('%s/system/config/%s.php', TL_ROOT, $GLOBALS['TL_RTE']['type']);

			if (!file_exists($strFile))
			{
				throw new Exception(sprintf('Cannot find rich text editor configuration file "%s.php". You can copy system/config/tinyMCE.php to this name. Edit it to fit your needs but to make it render in Frontend you must at least remove the line: save_callback : "TinyCallback.cleanXHTML",', $GLOBALS['TL_RTE']['type']));
			}

			$this->language = 'en';

			// Fallback to English if the user language is not supported
			if (file_exists(TL_ROOT . '/plugins/tinyMCE/langs/' . $GLOBALS['TL_LANGUAGE'] . '.js'))
			{
				$this->language = $GLOBALS['TL_LANGUAGE'];
			}

			ob_start();
			include($strFile);
			$objTemplate->rteConfig = ob_get_contents();
			ob_end_clean();
		}
		if($doNotSubmit)
			$this->Template->error = $GLOBALS['TL_LANG']['ERR']['general'];
		$this->Template->form = $objTemplate->parse();

	}
}

?>