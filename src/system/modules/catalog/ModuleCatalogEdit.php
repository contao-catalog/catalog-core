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
			$objTemplate->href = 'typolight/main.php?do=modules&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;

		$this->catalog_edit = deserialize($this->catalog_edit);

		return parent::generate();
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

		$objCatalogType = $this->Database->prepare("SELECT tableName,aliasField,titleField FROM tl_catalog_types WHERE id=?")
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
			$strAlias = is_numeric($value) ? "id" : ($objCatalogType->aliasField ? $objCatalogType->aliasField : '');
			if(strlen($strAlias))
			{
				$objCatalog = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE " . $strAlias . "=?")
											->limit(1)
											->execute($value);
			}
		}
	
		// if no item, then check if add allowed and then show add form
		if (!$objCatalog || $objCatalog->numRows < 1)
		{
			$blnModeAdd = true;
			// Load defaults.
			$arrValues = array();
		} 
		else
		{
			$arrValues = $objCatalog->fetchAssoc();
			// check if editing of this record is disabled for frontend.
			foreach ($this->catalog_edit as $key=>$field)
			{
				// HOOK: additional permission checks if this field allows editing of this record (for the current user).
				$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['catalog']['type']];
				if(is_array($fieldType) && array_key_exists('checkPermissionFERecordEdit', $fieldType) && is_array($fieldType['checkPermissionFERecordEdit']))
				{
					foreach ($fieldType['checkPermissionFERecordEdit'] as $callback)
					{
						$this->import($callback[0]);
						// TODO: Do we need more parameters here?
						if(!($this->$callback[0]->$callback[1]($this->strTable, $field, $arrValues)))
						{
							$this->Template->error = $GLOBALS['TL_LANG']['MSC']['catalogItemEditingDenied'];
							// Send 403 header
							header('HTTP/1.0 403 Forbidden');
							return;
						}
					}
				}
			}
		}
		$arrValues=$this->handleOnLoadCallbacks($arrValues);

		// unpack restriction values.
		$arrValuesDefault=deserialize($this->catalog_edit_default_value);
		// initialize value to restricted value as we might not be allowed to edit this field but the field shall
		// revert to some default setting (published flag etc.)
		// NOTE: This affects all fields mentioned in "catalog_edit_default_value", not just those selected for editing.
		if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_items')
		{
			$arrItem=$arrValuesDefault;
			$arrItem=$this->handleOnLoadCallbacks($arrItem);
		}

		//echo "<pre>"; print_r($arrValues); echo "</pre>";
		
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

		// we have to determine if we have upload fields for the form enctype.
		$hasUpload = false;
		// Build form
		$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'];
		foreach ($this->catalog_edit as $field)
		{
			
			$arrData = $fieldConf[$field];
			// check permissions here
			if (!is_object($this->User))
				$this->import('FrontendUser', 'User');

			// check if editing of this field is restricted to a certain user group.
			if(isset($arrData['eval']['catalog']['editGroups']))
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
					$varValue = deserialize($arrValues[$field]);
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
					$isChangedFileField = ($varValue != $arrValues[$field]);
				}
				else
				{
					$isChangedFileField = false;
					$objWidget->validate();
					$varValue = $objWidget->value;
				}
					

				// Convert date formats into timestamps
				if (strlen($varValue) && in_array($arrData['eval']['rgxp'], array('date', 'time', 'datim')))
				{
					$objDate = new Date($varValue, $GLOBALS['TL_CONFIG'][$arrData['eval']['rgxp'] . 'Format']);
					$varValue = $objDate->tstamp;
				}

				// Make sure that unique fields are unique
				if ($blnModeAdd && $fieldConf[$field]['eval']['unique'])
				{
					$objUnique = $this->Database->prepare("SELECT * FROM ".$this->strTable." WHERE " . $field . "=?")
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
						$objWidgetUpload->text = $this->formatValue($i, $field, $arrValues[$field], false);
				}

				// Store current value - NOTE: FormfileUpload does not set the flag for submitInput, therefore we can not use submitInput there.
				elseif ($objWidget->submitInput() || ($inputType == 'upload' && $isChangedFileField))
				{
					if ($arrData['eval']['catalog']['type'] == 'tags')
					{
						$varValue = $this->saveTags($objWidget->value);
					}

					if (strlen($fieldConf[$field]['eval']['rte']) && $GLOBALS['TL_CONFIG']['useRTE'])
					{
						$varValue = $this->Input->postHtml($field, $objWidget->decodeEntities);
					}

					$arrItem[$field] = $varValue;
				}
			} // end: if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_items')
			elseif (!$blnModeAdd) 
			{
				// if in editing mode from here on, we have to restrict some values and correct the output.
				$objWidget->value = $arrValues[$field];

				if ($arrData['eval']['catalog']['type'] == 'checkbox' && count($objWidget->options) == 1)
				{
					$objWidget->label = '';	
				}

				if ($arrData['eval']['catalog']['type'] == 'tags')
				{
					$objWidget->value = $this->loadTags($objWidget->value);
				}

				if ($arrData['eval']['catalog']['type'] == 'file')
				{
					//$arrUpload = deserialize($arrValues[$field], true);
					//$strUpload = $this->formatValue($i, $field, $arrValues[$field], false);
					// TODO: Add delete button to images for frontend editing.
					//$objWidgetUpload->text = $this->formatValue($i, $field, $arrValues[$field], false);
					// generate file list and add delete checkboxes.
					$showImage = $fieldConf[$field]['eval']['catalog']['showImage'];
					$files=$this->parseFiles($i, $field, $arrValues[$field]);
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
				$objDate = new Date($varValue, $GLOBALS['TL_CONFIG'][$arrData['eval']['rgxp'] . 'Format']);
				$objWidget->value = $objDate->$arrData['eval']['rgxp'];
				//$objWidget->datepicker = '
				$GLOBALS['TL_HEAD'][]='
				<script type="text/javascript"><!--//--><![CDATA[//><!--
				window.addEvent(\'domready\', function() { ' . sprintf($this->getDatePickerString(), 'ctrl_' . $objWidget->id) . ' });
				//--><!]]></script>';
				$GLOBALS['TL_HEAD'][]='<script src="plugins/calendar/calendar.js" type="text/javascript"></script>';
				$GLOBALS['TL_CSS'][] = 'plugins/calendar/calendar.css';
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
				$this->itemInsert($arrItem, $objCatalogType->titleField, $objCatalogType->aliasField);
			} 
			else 
			{
				$this->itemUpdate($arrItem, $arrValues['id'], $objCatalogType->titleField, $objCatalogType->aliasField);
			}
		}


		// Set template form
		$objTemplate = new FrontendTemplate($this->catalog_template);
		$objTemplate->enctype = $hasUpload ? 'multipart/form-data' : 'application/x-www-form-urlencoded';

		$objTemplate->field = join('',$arrFields);
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

	/**
	 * Autogenerate a catalog alias if it has not been set yet
	 * @param mixed
	 * @param object
	 * @return string
	 */
	public function generateAlias(&$arrData, $id, $aliasTitle, $aliasCol)
	{
		$autoAlias = false;

		// Generate alias if there is none
		if (!strlen($arrData[$aliasCol]))
		{
			$autoAlias = true;
			$arrData[$aliasCol] = standardize($arrData[$aliasTitle]);
		}

		$objAlias = $this->Database->prepare("SELECT id FROM ".$this->strTable." WHERE ".$aliasCol."=? AND id!=?")
								   ->execute($arrData[$aliasCol], $id);

		// Check whether the catalog alias exists
		if ($objAlias->numRows && !$autoAlias)
		{
			//throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $arrData[$aliasCol]));
			// TODO: we can not throw an exception here as it would kill the FE => not an option.
			//       we can not reject saving as we might already have saved it (coming from insert).
			//       So I simply work as if it was autogenerated. Find a better solution for this! (c.schiffler 2009-09-10)
			$autoAlias = true;
		}

		// Add ID to alias
		if ($objAlias->numRows && $autoAlias)
		{
			$arrData[$aliasCol] .= '.' . $id;
		}
	}

/*

	private function saveTags($varValue, $id, $field)
	{
		$options = $varValue;
//		$options = @deserialize($varValue);
		if (!is_array($options))
		{
				$options = array();
		}

		$fieldId = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field]['eval']['catalog']['fieldId'];
		if ($fieldId)
		{
				$this->Database->prepare("DELETE FROM tl_catalog_rel WHERE item_id=? AND field_id=?")->execute($id, $fieldId);
				
				foreach ($options as $option)
				{
						$this->Database->prepare("INSERT INTO tl_catalog_rel %s")
								->set(array('item_id' => $id, 'field_id' => $fieldId, 'related_id' => $option))
								->execute();
				}
		}
		
		return join(',', $options);
	}
*/


	private function saveTags($varValue)
	{
		return join(',', deserialize($varValue, true));
	}
	
	private function loadTags($varValue)
	{
		return split(',', $varValue);
	}
    

	private function handleOnLoadCallbacks($arrData)
	{
		require_once(TL_ROOT . '/system/drivers/DC_DynamicTable.php');
		$tmptbl=new DC_DynamicTable($this->strTable);
		foreach($arrData as $field=>$data)
		{
			$fieldConf = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
			$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldConf['eval']['catalog']['type']];
			if(is_array($fieldType) && array_key_exists('fieldDef', $fieldType) && array_key_exists('load_callback', $fieldType['fieldDef']) && is_array($fieldType['fieldDef']['load_callback']))
			{
				foreach ($fieldType['fieldDef']['save_callback'] as $callback)
				{
					$this->import($callback[0]);
					// TODO: Do we need more parameters here?
					$arrData[$field]=$this->$callback[0]->$callback[1]($data, $tmptbl);
				}
			}
		}
		unset($tmptbl);
		return $arrData;
	}

	private function handleOnSaveCallbacks($arrData)
	{
		require_once(TL_ROOT . '/system/drivers/DC_DynamicTable.php');
		$tmptbl=new DC_DynamicTable($this->strTable);
		foreach($arrData as $field=>$data)
		{
			$fieldConf = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
			$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldConf['eval']['catalog']['type']];
			if(is_array($fieldType) && array_key_exists('save_callback', $fieldType['fieldDef']) && is_array($fieldType['fieldDef']['save_callback']))
			{
				foreach ($fieldType['fieldDef']['save_callback'] as $callback)
				{
					$this->import($callback[0]);
					// TODO: Do we need more parameters here?
					$arrData[$field]=$this->$callback[0]->$callback[1]($data, $tmptbl);
				}
			}
		}
		unset($tmptbl);
		return $arrData;
	}

	/**
	 * Update existing item and redirect
	 * @param array
	 */
	private function itemUpdate($arrData, $id, $aliasTitle, $aliasCol)
	{
		$arrData=$this->handleOnSaveCallbacks($arrData);
		$arrData['tstamp'] = time();
		$this->generateAlias($arrData, $id, $aliasTitle, $aliasCol);
		// Update item
		$objUpdatedItem = $this->Database->prepare("UPDATE ".$this->strTable." %s WHERE id=?")
				->set($arrData)
				->execute(intval($id));
		// HOOK: pass data to HOOKs to be able to do something when we updated an item.
		if (isset($GLOBALS['TL_HOOKS']['catalogFrontendUpdate']) && is_array($GLOBALS['TL_HOOKS']['catalogFrontendUpdate']))
		{
			$objEntry = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE id=?")
											->limit(1)
											->execute($id);
			if($objEntry->numRows)
				$arrData=$this->handleOnLoadCallbacks($objEntry->fetchAssoc());
			foreach ($GLOBALS['TL_HOOKS']['catalogFrontendUpdate'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($arrData);
			}
		}
		
		// check which submit method was used and redirect then.
		if($this->Input->post('save'))
		{
			// stay on this page with current entry.
			global $objPage;
			$this->redirect(ampersand($this->generateFrontendUrl($objPage->row(), ($GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;items=' : '/items/') . ($aliasCol && $arrData[$aliasCol] ? $arrData[$aliasCol] : $id))));
		} else if($this->Input->post('saveNcreate'))
		{	// stay on this page but without id.
			global $objPage;
			$this->redirect(ampersand($this->generateFrontendUrl($objPage->row())));
		} else if($this->Input->post('saveNclose'))
		{	// follow jumpTo
			$this->redirect($this->generateCatalogNavigationUrl('items', $aliasCol && $arrData[$aliasCol] ? $arrData[$aliasCol] : $id));
		}
	}

	/**
	 * Create a new item and redirect
	 * @param array
	 */
	private function itemInsert($arrData, $aliasTitle, $aliasCol)
	{
		$arrData=$this->handleOnSaveCallbacks($arrData);
		$arrData['tstamp'] = time();
		$arrData['pid'] = $this->catalog;
		// Create item
		$objNewItem = $this->Database->prepare("INSERT INTO ".$this->strTable." %s")->set($arrData)->execute();
		$insertId = $objNewItem->insertId;
		// we have to update now, as we need to generate an alias.
		$this->itemUpdate($arrData, $insertId, $aliasTitle, $aliasCol);
		// HOOK: pass data to HOOKs to be able to do something when we inserted an item.
		if (isset($GLOBALS['TL_HOOKS']['catalogFrontendInsert']) && is_array($GLOBALS['TL_HOOKS']['catalogFrontendInsert']))
		{
			$objEntry = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE id=?")
											->limit(1)
											->execute($id);
			if($objEntry->numRows)
				$arrData=$this->handleOnLoadCallbacks($objEntry->fetchAssoc());
			foreach ($GLOBALS['TL_HOOKS']['catalogFrontendInsert'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($arrData);
			}
		}
/*
		$fieldConf = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
		foreach($arrData as $field=>$data)
		{
			if ($fieldConf['eval']['catalog']['type'] == 'tags')
			{
				$this->saveTags($data, $insertId, $field);
			}
		}
*/
	}
}

?>