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

require_once(TL_ROOT . '/system/drivers/DC_DynamicTable.php');

/**
 * Class DC_DynamicTableEdit
 * NOTE TO EXTENSION DEVELOPERS!
 * watch out, this is a massive compromise and subject to change.
 * I came up with this ad-hoc solution in order to supply a valid DataContainer in the onload and onsave callbacks
 * but will rewrite the frontend editing from scratch in the future using a real FE DC driver.
 * Therefore you should not rely on the concrete implementation of this class below.
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Controller
 *
 */
class DC_DynamicTableEdit extends DC_DynamicTable
{
	/**
	 * Maximum length of the alias field
	 * @var int
	 */
	const MAXALIASLENGTH = 64;

	/**
	 * Holds the Catalog information dataset from tl_catalog_types
	 * @var Database_Result
	 */
	protected $objCatalogType = NULL;
		/**
	 * // TODO what's this good for?
	 * @var string
	 */
	protected $strField;

	/**
	 * parent edit module
	 * @var ModuleCatalogEdit
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
	public function __construct($strTable, Database_Result $objCatalogType, ModuleCatalogEdit $objModule, array $arrData)
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
	 * Checks all tag fields which are stored and fixes the tag relations
	 * @pre $this->objDCEdit->activeRecord is up to date, especially the id
	 * @return void
	 */
	protected function fixTags()
	{
	$values = (array) $this->objActiveRecord;
	$fieldsConf = $GLOBALS['TL_DCA'][$this->strTable]['fields'];
	foreach ($values as $fieldName => $value)
	{
		$fieldConfCatalog = $fieldsConf[$fieldName]['eval']['catalog'];
		if ($fieldConfCatalog['type'] == 'tags')
		{
			// explode always returns at least one element
			$tags = strlen($value) ? explode(',', $value) : array();
			Catalog::setTags($values['pid'], $fieldConfCatalog['fieldId'], $values['id'], $tags);
			}
		}
	}

	/**
	 * Autogenerate a catalog alias if it has not been set yet
	 * @pre isset($this->objActiveRecord->id)
	 * @return void
	 */
	public function generateAlias()
	{
		// without an aliasCol there is no need for generating an alias
		if (! strlen($this->objCatalogType->aliasField))
			return;

		$aliasCol = $this->objCatalogType->aliasField;
		$strAlias = $this->objActiveRecord->$aliasCol;

		// Generate alias if there is none
		$autoAlias = false;
		if (! strlen($strAlias))
		{
			/* get the field from which the alias should be generated
			 * aliasTitle is obligatory for alias fields,
			 * and only those can be set as aliasField
			 */
			$objCatalogAliasField = $this->Database->prepare("SELECT aliasTitle
															FROM tl_catalog_fields
															WHERE tl_catalog_fields.pid=?
															AND tl_catalog_fields.colName=?")
													->execute($this->objCatalogType->id, $aliasCol);
			$aliasTitle = $objCatalogAliasField->aliasTitle;
			$strAlias = substr(standardize($this->objActiveRecord->$aliasTitle), 0, self::MAXALIASLENGTH);
		}
		// Check whether the catalog alias is taken by another item
		$objAlias = $this->Database->prepare("SELECT id FROM " . $this->strTable
											." WHERE " . $this->objCatalogType->aliasField . '=?'
											. ($this->objActiveRecord->id?' AND id !=?':''))
								->execute($strAlias, $this->objActiveRecord->id);

		// append id also if alias was not generated
		if ($objAlias->numRows)
		{
			$strAliasSuffix = '-' . $this->objActiveRecord->id;
			$strAlias = substr($strAlias, 0, self::MAXALIASLENGTH - strlen($strAliasSuffix)) . $strAliasSuffix;
		}
		$this->objActiveRecord->$aliasCol = $strAlias;
	}

	/**
	 * HOOK: load_callback for each field
	 */
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

	/**
	 * HOOK: save_callback for each field
	 * @return void
	 */
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

	/**
	 * HOOK: onsubmit_callback
	 * @return void
	 */
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

	/**
	 * @return array : string field name
	 */
	protected function getEditableFieldnames()
	{
		$arrFields=array_merge(array('tstamp', 'pid'), $this->objModule->catalog_edit);

		if($this->objModule->catalog_edit_use_default && $this->objModule->catalog_edit_default_value)
			$arrFields=array_merge($arrFields, array_keys($this->objModule->catalog_edit_default_value));

		if($this->objCatalogType->aliasField)
			$arrFields[] = $this->objCatalogType->aliasField;

		return $arrFields;
	}

	/**
	 * Updates generated information of the item and stores it in db
	 * @return void
	 * @post $this->objActiveRecord is up to date
	 */
	public function itemUpdate()
	{
		$this->generateAlias();
		$arrData = (array)$this->objActiveRecord;
		$this->handleOnSaveCallbacks();

		// Update item
		$this->objActiveRecord->pid = $this->objCatalogType->id;
		$this->objActiveRecord->tstamp = time();

		$arrRecordData=array();
		foreach($this->getEditableFieldnames() as $field)
		{
			$arrRecordData[$field] = $this->objActiveRecord->$field;
		}

		$objUpdatedItem = $this->Database->prepare('UPDATE '.$this->strTable.' %s WHERE id=?')
				->set($arrRecordData)
				->execute($this->objActiveRecord->id);

		$this->fixTags();
		$this->handleOnSubmit();

		// HOOK: pass data to HOOKs to be able to do something when we updated an item.
		$this->handleFrontendUpdateCallbacks($arrRecordData);
	}
		/**
	 * HOOK: catalogFrontendUpdate
	 * @param array $arrItemData
	 * @return void
	 */
	private function handleFrontendUpdateCallbacks(array $arrItemData)
	{
		if (isset($GLOBALS['TL_HOOKS']['catalogFrontendUpdate'])
			&& is_array($GLOBALS['TL_HOOKS']['catalogFrontendUpdate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['catalogFrontendUpdate'] as $callback)
			{
				if (!is_object($this->$callback[0]))
					$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($arrItemData, $this->objModule, $this->strTable);
			}
		}
	}

	/**
	 * Create a new item and redirect
	 * @return void
	 */
	public function itemInsert()
	{
		$this->objActiveRecord->pid = $this->objCatalogType->id;
		$this->objActiveRecord->tstamp = time();

		// insert an "empty" dataset to get an id.
		$objNewItem = $this->Database->prepare('INSERT INTO '.$this->strTable.' %s')
				->set(array('pid' => $this->objActiveRecord->pid,
				            'tstamp' => $this->objActiveRecord->tstamp))
				->execute();
		$this->objActiveRecord->id = $objNewItem->insertId;

		$this->generateAlias();
		$arrData = (array)$this->objActiveRecord;
		$this->intId = $arrData['id'];
		$this->handleOnSaveCallbacks();
		$arrRecordData=array();
		foreach($this->getEditableFieldnames() as $field)
		{
			$arrRecordData[$field] = $this->objActiveRecord->$field;
		}
		$objUpdatedItem = $this->Database->prepare('UPDATE '.$this->strTable.' %s WHERE id=?')
				->set($arrRecordData)
				->execute($this->objActiveRecord->id);

		$this->fixTags();
			// HOOK: pass data to HOOKs to be able to do something when we inserted an item.
		$this->handleOnSubmit();
		$this->handleFrontendInsertCallbacks($arrData);
	}

	/**
	 * HOOK: catalogFrontendInsert
	 * @param array $arrData the item
	 * @return void
	 */
	private function handleFrontendInsertCallbacks(array $arrItemData)
	{
		if (isset($GLOBALS['TL_HOOKS']['catalogFrontendInsert'])
		    && is_array($GLOBALS['TL_HOOKS']['catalogFrontendInsert']))
		{
			foreach ($GLOBALS['TL_HOOKS']['catalogFrontendInsert'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($arrItemData, $this->objModule, $this->strTable);
			}
		}
	}
}

/**
 * Frontend module to edit a catalog item or add a new one
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Controller
 *
 */
class ModuleCatalogEdit extends ModuleCatalog
{
	/**
	 * One extra Widget for File Uploads.
	 * Shows the already uploaded files.
	 * Is class var because it's an additional widget that
	 * needs to be configured additionally to the real widget
	 * @var FormExplanation
	 */
	protected $objWidgetUpload;

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

	/**
	 * dynamic table driver as interface to the actual table
	 * @var DC_DynamicTableEdit
	 */
	protected $objDCEdit = NULL;
	/**
	 * ID for identifying the submitted form
	 * @var string
	 */
	const FORMID = 'tl_catalog_items';

	/**
	 * (non-PHPdoc)
	 * @see ModuleCatalogEdit::generate()
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

	/**
	 * Checks if the whole record can be edited in the frontend, based on one field
	 * @param string $strFieldName
	 * @param array $arrValues
	 * @return bool may the record be edited in the frontend?
	 */
	protected function editRecordAllowed($strFieldName, array $arrValues)
	{
		if (! $this->checkPermissionFERecordEditHook($strFieldName, $arrValues))
		{
			$this->Template->error = $GLOBALS['TL_LANG']['MSC']['catalogItemEditingDenied'];
			// Send 403 header
			header('HTTP/1.0 403 Forbidden');
			return false;
		}
		return true;
	}
	/**
	 * (non-PHPdoc)
	 * @see ModuleCatalogEdit::compile()
	 */
	public function compile()
	{
		$this->refererUrl = $this->getReferer(ENCODE_AMPERSANDS);
		$this->basicVarsToTemplate();

		if (!$this->objCatalogType)
		{
			return $this->compileInvalidCatalog();
		}
		$blnModeAdd = false;

		// get catalog item as an array
		$objItem = $this->fetchCatalogItemFromRequest();

		if($objItem)
			$arrValues = $objItem->fetchAssoc();
		else
			$arrValues = array();

		// initialize value to restricted value as we might not be allowed to edit
		// this field but the field shall revert to some default setting
		// (published flag etc.)
		// NOTE: This affects all fields mentioned in "catalog_edit_default_value",
		// not just those selected for editing.
		if (($this->Input->post('FORM_SUBMIT') == self::FORMID)
			&& $this->catalog_edit_use_default
			&& $this->catalog_edit_default_value)
		{
			$arrValues = array_merge($arrValues, $this->catalog_edit_default_value);
		}

		$this->objDCEdit = new DC_DynamicTableEdit($this->strTable, $this->objCatalogType, $this, $arrValues);

		// if no item, then check if add allowed and then show add form
		if (!is_object($objItem))
			$blnModeAdd = true;
		else
		{
			// check if editing of this record is disabled for frontend
			foreach ($this->catalog_edit as $key=>$field)
			{
				if (!$this->editRecordAllowed($field, (array) $this->objDCEdit->activeRecord))
					return;
			}
		}

		// Captcha initalization and validation
		if (!$this->disableCaptcha)
		{
			$objCaptcha = $this->captchaInit();
			if ($objCaptcha->hasErrors())
			{
				$doNotSubmit = true;
			}
		}

		$i = 0;

		// we have to determine if we have upload fields for the form enctype.
		$hasUpload = false;

		// Build form

		$fieldConf = $GLOBALS['TL_DCA'][$this->strTable]['fields'];
		$arrWidgets = array();

		foreach ($this->catalog_edit as $field)
		{
			$arrData = $fieldConf[$field];

			if(! $this->fieldAllowedForCurrentUser($arrData))
			{
				continue; // with next field
			}

			$arrData = $this->adjustFieldConfig($arrData);
			$objWidget = $this->constructFieldWidget($field, $arrData);

			// maybe the field config is invalid
			if(! $objWidget)
			{
				continue; // with next field
			}

			$objWidget->storeValues = true;
			$objWidget->rowClass = 'row_' . $i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');

			// Increase the row count if its a password field
			if ($objWidget instanceof FormPassword)
			{
				$objWidget->rowClassConfirm = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
			}

			// add Explanation with existing Files/Images
			unset($this->objWidgetUpload);

			if ($objWidget instanceof FormFileUpload)
			{
				$this->objWidgetUpload = new FormExplanation($this->prepareForWidget($arrData, $field));
				$this->objWidgetUpload->rowClass = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
				$arrWidgets[$field.'_upload'] = $this->objWidgetUpload;

				// file uploads need 'multipart/form-data'
				$hasUpload = true;
			}

			// Validate input
			if ($this->Input->post('FORM_SUBMIT') == self::FORMID)
			{
				$varValue = $this->validateInput($field, $arrData, $objWidget, $blnModeAdd);

				// set a flag if this input has changed.
				if ($arrData['inputType'] == 'upload')
				{
					$isChangedFileField = ($varValue != $this->objDCEdit->activeRecord->$field);
				} else {
					$isChangedFileField = false;
				}

				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;

					// initialize to old value as otherwise we can not see anything in FE
					// when there was an error in $objWidget->validate();
					if ($inputType == 'upload')
						$this->objWidgetUpload->text = $this->formatValue($i, $field, $this->objDCEdit->activeRecord->$field, false);
				}
				// Store current value - NOTE: FormfileUpload does not set the flag for
				// submitInput, therefore we can not use submitInput there.
				elseif ($objWidget->submitInput()
						|| ($arrData['inputType'] == 'upload' && $isChangedFileField))
				{
					// check if field has restricted default.
					if (!in_array($field, $this->catalog_edit_default_value))
						$this->storeFieldValue($field, $arrData, $varValue, $objWidget);
				}
			}
			elseif (!$blnModeAdd)
			{
				// if in editing mode from here on, we have to restrict some values
				// and correct the output.
				$objWidget->value = $this->objDCEdit->activeRecord->$field;

				$this->configureWidgetForOutput($field, $arrData, $objWidget, $i);
			}

			// Register field name for rich text editor usage
			if (strlen($arrData['eval']['rte']) && $GLOBALS['TL_CONFIG']['useRTE'])
			{
				// EXPERIMENTAL!! RTE in Frontend.
				$GLOBALS['TL_RTE']['type'] = $arrData['eval']['rte'];
				$GLOBALS['TL_RTE']['fields'][] = 'ctrl_' . $field;

				// TODO: make this configurable?
				$objWidget->cols = 70;
				$objWidget->rows = 12;
			}

			$arrFields[$field] = $this->parseInputWidget($field, $arrData, $objWidget);
			$arrWidgets[$field] = $objWidget;

			++$i;
		}

		// Captcha
		if ($objCaptcha)
		{
			$objCaptcha->rowClass = 'row_'.$i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');
			$strCaptcha = $objCaptcha->parse();

			$arrFields['captcha'] .= $strCaptcha;
			$arrWidgets['captcha'] = $objCaptcha;
		}

		$this->Template->rowLast = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');

		// Create new entry or update the old one if there are no errors
		if ($this->Input->post('FORM_SUBMIT') == self::FORMID && !$doNotSubmit)
		{
			if ($blnModeAdd)
			{
				$this->objDCEdit->itemInsert();
			}
			else
			{
				$this->objDCEdit->itemUpdate();
			}
			$this->submitRedirect();
		}

		// Set template form
		$this->Template->form = $this->parseFormTemplate($arrFields, $arrWidgets, $doNotSubmit, $hasUpload);
		// also pass the widgets to the template
		$this->Template->arrWidgets = $arrWidgets;
	}

	/**
	 * Creates and initializes the Captcha Widget
	 * @return FormCaptcha
	 */
	protected function captchaInit()
	{
		$arrCaptcha = array
		(
			'id'		=> 'catalog',
			'label'		=> $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
			'mandatory'	=> true,
			'required'	=> true
		);

		$objCaptcha = new FormCaptcha($arrCaptcha);

		if ($this->Input->post('FORM_SUBMIT') == self::FORMID)
		{
			$objCaptcha->validate();
		}

		return $objCaptcha;
	}

	/**
	 * Writes basic catalog information into the template
	 * @return void
	 */
	protected function basicVarsToTemplate()
	{
		$this->Template->catalog = '';
		$this->Template->referer = $this->refererUrl;
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
	}

	/**
	 * Includes the RTE into the environment
	 * @throws Exception
	 * @return string configuration file contents
	 */
	protected function includeRTE()
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

		// Fallback to English if the user language is not supported
		$this->language = 'en';

		if (file_exists(TL_ROOT . '/plugins/tinyMCE/langs/' . $GLOBALS['TL_LANGUAGE'] . '.js'))
		{
			$this->language = $GLOBALS['TL_LANGUAGE'];
		}

		ob_start();
		include($strFile);
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * Checks for group membership and also calls the checkPermissionFEEdit HOOKS
	 * @param array $fieldConfig from DCA
	 * @return bool is the current user allowed to edit the field
	 */
	protected function fieldAllowedForCurrentUser(array $fieldConfig)
	{
		$this->import('FrontendUser', 'User');
		$result = true;

		// check if editing of this field is restricted to a certain user group.
		// fallback is false
		if(is_array($fieldConfig['eval']['catalog']['editGroups'])
			&& count($fieldConfig['eval']['catalog']['editGroups']))
		{
			// one positive is enough
			$result = false;

			foreach($fieldConfig['eval']['catalog']['editGroups'] as $group)
			{
				if($this->User->isMemberOf($group))
				{
					$result = true;
					break;
				}
			}
		}

		if (! $this->fieldAllowedForCurrentUserHooks($fieldConfig))
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * Checks the checkPermissionFEEdit hooks for the field
	 *
	 * @see ModuleCatalog::checkPermissionFERecordEditHook()
	 * @param array $fieldConfig
	 * @return bool do all hooks allow editing this field for the current user?
	 */
	private function fieldAllowedForCurrentUserHooks(array $fieldConfig)
	{
		// HOOK result must be boolean!
		$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldConfig['eval']['catalog']['type']];

		if(is_array($fieldType)
			&& array_key_exists('checkPermissionFEEdit', $fieldType)
			&& is_array($fieldType['checkPermissionFEEdit']))
		{
			foreach ($fieldType['checkPermissionFEEdit'] as $callback)
			{
				$this->import($callback[0]);

				// TODO: Do we need more parameters here?
				if(!($this->$callback[0]->$callback[1]($fieldConf)))
				{
					// one false is enough
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Adjusts certain fields types to the special needs of this class
	 * @param array $fieldConfig
	 * @return array $fieldConfig with adjusted values
	 */
	protected function adjustFieldConfig(array $fieldConfig)
	{
		$result = $fieldConfig;

		switch($fieldConfig['inputType'])
		{
			case 'fileTree':
				$result['inputType'] = 'upload';
				$result['eval']['mandatory'] = false;
			break;

			case 'tableTree':
				switch($fieldConfig['eval']['fieldType'])
				{
					// tags
					case 'checkbox':
						$result['inputType'] = 'checkbox';
						$result['eval']['multiple'] = true;
					break;

					// select
					case 'radio':
						$result['inputType'] = 'select';
					break;
				}
			break;
		}
		return $result;
	}

	/**
	 * @param string $fieldName
	 * @param array $fieldConfig
	 * @return null|Widget for the field if valid
	 */
	protected function constructFieldWidget($fieldName, array $fieldConfig)
	{
		$strClass = $GLOBALS['TL_FFL'][$fieldConfig['inputType']];

		// some things are only present in the backend for now, like the timePeriod, but
		// are safe to be called also in FE. So we do here.
		// TODO: We should export this to some other location instead of hardcoding it here.
		if((!$strClass) && in_array($fieldConfig['inputType'], array('timePeriod')))
		{
			$strClass = $GLOBALS['BE_FFL'][$fieldConfig['inputType']];
		}
		// Continue if the class is not defined
		if (! $this->classFileExists($strClass))
		{
			return null;
		}
		else
		{
			$objWidget = new $strClass($this->prepareForWidget($fieldConfig, $fieldName));
			// add required if needed.
			$objWidget->required = $objWidget->mandatory;
			$objWidget->tip = $fieldConfig['label'][1];
			return $objWidget;
		}
	}

	/**
	 * Validates a upload field's input
	 * @param string $fieldName
	 * @param array $fieldConfig for this one field
	 * @param Widget $objWidget
	 * @param bool $blnModeAdd or Update?
	 * @return array field's new value
	 */
	protected function validateFileUpload($fieldName, array $fieldConfig, Widget $objWidget, $blnModeAdd)
	{
		// prepare all widget settings as FormFileUpload expects them to be.
		$objWidget->storeFile = true;

		if($fieldConfig['eval']['extensions'])
			$objWidget->extensions = $fieldConfig['eval']['extensions'];
		else
			$objWidget->extensions = $fieldConfig['eval']['catalog']['showImage'] ? $GLOBALS['TL_CONFIG']['validImageTypes'] : $GLOBALS['TL_CONFIG']['uploadTypes'];

		if($fieldConfig['eval']['path'])
		{
			$objWidget->uploadFolder = $fieldConfig['eval']['path'] . '/' . $this->strTable;
		} else {
			$objWidget->uploadFolder = $GLOBALS['TL_CONFIG']['uploadPath'] . '/catalog_' . $this->strTable;
		}

		// ensure folder exists. So we create a Folder object which will create the
		// folder if it does not exist and unset it immediately again as we do not
		// need it for anything else.
		// Maybe we can find a better solution sometime.
		$dummyFolder = new Folder($objWidget->uploadFolder);
		unset($dummyFolder);

		// Now validate, this will move the file to the folder if everything is ok
		// and store the information in the session.
		$objWidget->validate();

		// use existing value(s) from database as base.
		$varValue = deserialize($this->objDCEdit->activeRecord->$fieldName);
		if (!is_array($varValue))
			$varValue = array($varValue);

		// was this file uploaded?
		if (isset($_SESSION['FILES'][$fieldName]))
		{
			$filename = $objWidget->uploadFolder . '/' . $_SESSION['FILES'][$fieldName]['name'];

			// now we have to remove this file from the session as we have processed it.
			unset($_SESSION['FILES'][$fieldName]);

			if ($fieldConfig['eval']['catalog']['multiple']=='1')
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
		$remaining = array();
		foreach($varValue as $file)
		{
			// TODO: shall we delete the file from disk?
			if (!$this->Input->post('unlink_'.$fieldName.'_' . md5($file)))
			{
				$remaining[]=$file;
			}
		}

		// convert back to string for single file fields.
		if($fieldConfig['eval']['catalog']['multiple'] == '1')
			$varValue = serialize($remaining);
		else
			$varValue = $remaining[0];

		return $varValue;
	}

	/**
	 * Validates fields' input
	 * @param string $fieldName
	 * @param array $fieldConf for this one field
	 * @param Widget $objWidget
	 * @param bool $blnModeAdd or Update item
	 * @return mixed (new) value of the field
	 */
	protected function validateInput($fieldName, array $fieldConfig, Widget $objWidget, $blnModeAdd)
	{
		// We have to handle file inputs differently here, as FormFileUpload does
		// not export a value, it saves to the $_SESSION instead.
		if ($fieldConfig['inputType'] == 'upload')
		{
			$varValue = $this->validateFileUpload($fieldName, $fieldConfig, $objWidget, $blnModeAdd);
		}
		else
		{
			$objWidget->validate();
			$varValue = $objWidget->value;
		}
		// Convert date formats into timestamps
		if (in_array($fieldConfig['eval']['rgxp'], array('date', 'time', 'datim'))
			&& strlen($varValue))
		{
			if(strlen($varValue))
			{
				$objDate = new Date($varValue, $GLOBALS['TL_CONFIG'][$arrData['eval']['rgxp'] . 'Format']);
				$varValue = $objDate->tstamp;
			} else {
				$varValue = NULL;
			}
		}

		// Make sure that unique fields are unique
		if ($blnModeAdd && $fieldConfig['eval']['unique']
			&& $this->fieldValueTaken($fieldName,$varValue))
		{
			$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['unique'],
								(strlen($fieldConfig['label'][0]) ? $fieldConfig['label'][0] : $fieldName)));
		}

		return $varValue;
	}

	/**
	 *
	 * @param string $fieldName
	 * @param mixed $newValue
	 * @return bool is the value already taken?
	 */
	protected function fieldValueTaken($fieldName, $newValue)
	{
		 $objUnique = $this->Database->prepare('SELECT * FROM ' . $this->strTable . ' WHERE ' . $fieldName . '=?')
									->limit(1)
									->execute($newValue);

		return $objUnique->numRows > 0;
	}

	/**
	 * Stores the field's value in the active record
	 * Some input types are special for this class, so their value
	 * needs to be treated non-standard
	 * @post $this->objDCEdit->activeRecord->$fieldName has a value
	 * @param string $fieldName
	 * @param array $fieldConfig
	 * @param mixed $fieldValue
	 * @param Widget $objWidget
	 * @return void
	 */
	protected function storeFieldValue($fieldName, array $fieldConfig, $fieldValue, Widget $objWidget)
	{
		if ($fieldConfig['eval']['catalog']['type'] == 'tags')
		{
			$fieldValue = implode(',', deserialize($objWidget->value, true));
		}
		if (strlen($fieldConfig['eval']['rte']) && $GLOBALS['TL_CONFIG']['useRTE'])
		{
			$fieldValue = $this->Input->postHtml($fieldName, $objWidget->decodeEntities);
		}

		// check if field has restricted default.
		if (!in_array($fieldName, $this->catalog_edit_default_value))
			$this->objDCEdit->activeRecord->$fieldName = $fieldValue;
	}

	/**
	 * Reconfigures a widget for use in this class
	 * @param string $fieldName
	 * @param array $fieldConfig
	 * @param Widget $objWidget
	 * @param int $row current input row
	 * @return void
	 */
	protected function configureWidgetForOutput($fieldName, array $fieldConfig, Widget $objWidget, $row)
	{
		switch($fieldConfig['eval']['catalog']['type'])
		{
			case 'checkbox':
				// single checkboxes already got a label
				if (count($objWidget->options) == 1)
				{
					$objWidget->label = '';
				}
			break;

			case 'tags':
				// tags have multiple values
				$objWidget->value = explode(',', $objWidget->value);
			break;

			case 'file':
				// generate file list and add delete checkboxes.
				$showImage = $fieldConfig['eval']['catalog']['showImage'];
				$files = $this->parseFiles($row, $fieldName, $this->objDCEdit->activeRecord->$fieldName);
				$output ='';
				$counter = 0;

				foreach($files['html'] as $file)
				{
					$class = (($counter == 0) ? ' first' : '') . ((($counter % 2) == 0) ? ' even' : ' odd');
					$name = 'unlink_'.$fieldName.'_' . md5($files['files'][$counter]);
					$output .= '<div class="' . ($showImage ? 'image' : 'file') . $class.'">'
						. $file
						. '<span class="delete unlinkcheckbox ' . $class . '">'
						. '<input class="checkbox unlinkcheckbox ' . $class . '" type="checkbox" name="' . $name . '" id="' . $name . '" value="1" />'
						. '<label for="'. $name .'">'. sprintf($GLOBALS['TL_LANG']['MSC']['removeImage'], basename($files['files'][$counter]))
						.'</label></span></div>';

					$counter++;
				}

				$this->objWidgetUpload->text = $output;
			break;
		}
		if(in_array($fieldConfig['eval']['rgxp'], array('date', 'time', 'datim')))
		{
			if($objWidget->value)
			{
				$objDate = new Date($objWidget->value, $GLOBALS['TL_CONFIG'][$arrData['eval']['rgxp'] . 'Format']);
				$objWidget->value = $objDate->$arrData['eval']['rgxp'];
			}
		}
	}

	/**
	 * Parses the widget and adds extra content, if necessarry
	 * @param string $fieldName
	 * @param array $fieldConfig of this one field
	 * @param Widget $objWidget
	 * @return string HTML Code for the widget + extras
	 */
	protected function parseInputWidget($fieldName, array $fieldConfig, Widget $objWidget)
	{
		$result = '';

		if ($objWidget instanceof FormFileUpload)
		{
			$result = $this->objWidgetUpload->parse();
		}

		elseif (in_array($fieldConfig['eval']['rgxp'], array('date', 'time', 'datim')))
		{
			// date picker was changed in 2.10
			if(version_compare(VERSION.'.'.BUILD, '2.10.0', '>='))
			{
				$rgxp = $fieldConfig['eval']['rgxp'];

				switch ($rgxp)
				{
					case 'datim':
						$time = ",\n      timePicker: true";
						break;
					case 'time':
						$time = ",\n      timePickerOnly: true";
						break;
					default:
						$time = '';
						break;
				}
				$datepicker = '<img src="plugins/datepicker/icon.gif" width="20" height="20" id="toggle_' . $objWidget->id . '" class="datepicker_' . $objWidget->id . '">';

				$format = $GLOBALS['TL_CONFIG'][$rgxp.'Format'];

				$GLOBALS['TL_CSS'][] = 'plugins/datepicker/dashboard.css';
				$GLOBALS['TL_JAVASCRIPT'][] = 'plugins/datepicker/datepicker.js';
				$GLOBALS['TL_HEAD'][]=
				'<script type="text/javascript"><!--//--><![CDATA[//><!-- 
				window.addEvent(\'domready\', function() {
					new DatePicker(\'#ctrl_' . $objWidget->id . '\', {
						allowEmpty: true,
						toggleElements: \'#toggle_' . $objWidget->id . '\',
						pickerClass: \'datepicker_dashboard\',
						format: \'' . $format . '\',
						inputOutputFormat: \'' . $format . '\',
						positionOffset: { x:130, y:-185 }' . $time . ',
						startDay: ' . $GLOBALS['TL_LANG']['MSC']['weekOffset'] . ',
						days: [\''. implode("','", $GLOBALS['TL_LANG']['DAYS']) . '\'],
						dayShort: ' . $GLOBALS['TL_LANG']['MSC']['dayShortLength'] . ',
						months: [\''. implode("','", $GLOBALS['TL_LANG']['MONTHS']) . '\'],
						monthShort: ' . $GLOBALS['TL_LANG']['MSC']['monthShortLength'] . '
					});
				});
				//--><!]]></script>';

				$objWidget->datepicker = $datepicker;
				$result = preg_replace('#(</td>.+)(</td>.+</tr>)#s', ' \\1' . $datepicker . ' \\2', $objWidget->parse(), 1);
			} else {
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

				$result = $objWidget->parse();
			}
		}
		return $result;
	}

	/**
	 * Creates the form and generates the inputs
	 * @param array $inputFields HTML for the inputs
	 * @param array $arrWidgets of widget objects
	 * @param bool $addGeneralError add a general error message to the top of the form?
	 * @param bool $useMultipart
	 */
	protected function parseFormTemplate(array $inputFields, array $arrWidgets, $addGeneralError, $useMultipart)
	{
		$objTemplate = new FrontendTemplate($this->catalog_template);
		$objTemplate->enctype = $useMultipart ? 'multipart/form-data' : 'application/x-www-form-urlencoded';

		$objTemplate->field = implode('',$inputFields);
		$objTemplate->arrWidgets = $arrWidgets;
		$objTemplate->formId = self::FORMID;
		$objTemplate->action = ampersand($this->Environment->request, ENCODE_AMPERSANDS);

		if (count($GLOBALS['TL_RTE']) && $GLOBALS['TL_CONFIG']['useRTE'])
		{
			$objTemplate->rteConfig = $this->includeRTE();
		}

		if($addGeneralError)
			$this->Template->error = $GLOBALS['TL_LANG']['ERR']['general'];

		return $objTemplate->parse();
	}

	/**
	 * Set redirect according to which button has been used to submit
	 * @return void
	 */
	protected function submitRedirect()
	{
		// use the alias if a field is set and the field got a value
		$strAliasField = $this->strAliasField;
		$strAlias = ($strAliasField && $this->objDCEdit->activeRecord->$strAliasField ? $strAliasField : 'id');

		// check which submit method was used and redirect then.
		if($this->Input->post('save'))
		{
			// stay on this page with current entry.
			global $objPage;

			$entryUrl = $this->generateFrontendUrl($objPage->row(), ($GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;items=' : '/items/') . $this->objDCEdit->activeRecord->$strAlias);
			$this->redirect(ampersand($entryUrl));
		}
		else if($this->Input->post('saveNcreate'))
		{	// stay on this page but without id.
			global $objPage;
			$this->redirect(ampersand($this->generateFrontendUrl($objPage->row())));
		}
		else if($this->Input->post('saveNclose'))
		{	// follow jumpTo
			$this->redirect($this->generateCatalogNavigationUrl('items', $this->objDCEdit->activeRecord->$strAlias));
		}
	}
}
?>