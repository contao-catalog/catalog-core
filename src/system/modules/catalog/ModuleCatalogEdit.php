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
		

		// edit exiting, else present add new screen
		$blnModeAdd = false;
		$arrValues = array();

		// check existing items/alias passed as parameter?
		$strAlias = $objCatalogType->aliasField ? " OR ".$objCatalogType->aliasField."=?" : '';		

		$objCatalog = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE (id=?".$strAlias.")")
										->limit(1)
										->execute($this->Input->get('items'), $this->Input->get('items'));


		// if no item, then check if add allowed and then show add form
		if ($objCatalog->numRows < 1)
		{
			$blnModeAdd = true;
			$arrValues = array();
		} 
		else
		{
			$arrValues = $objCatalog->fetchAssoc();
		}

//		echo "<pre>"; print_r($arrValues); echo "</pre>";
		
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
			if(array_key_exists('checkPermissionFEEdit', $fieldType) && is_array($fieldType['checkPermissionFEEdit']))
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

/*
				if (strlen($fieldConf[$field]['eval']['rte']) && $GLOBALS['TL_CONFIG']['useRTE'])
				{
					echo "[".$this->Input->post($field) . "]<br />";
					$objWidget->value = $this->Input->postHtml($field, $objWidget->decodeEntities);
					print_r($objWidget->value);
				}
*/

				$objWidget->validate();
				$varValue = $objWidget->value;

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
				}

				// Store current value
				elseif ($objWidget->submitInput())
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
			} 
			elseif (!$blnModeAdd) 
			{

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
					$objWidgetUpload->text = $this->formatValue($i, $field, $arrValues[$field], false);
				}

			}

			// Add datepicker
			if (in_array($arrData['eval']['rgxp'], array('date', 'time', 'datim')))
			{
				$objDate = new Date($varValue, $GLOBALS['TL_CONFIG'][$arrData['eval']['rgxp'] . 'Format']);
				$objWidget->value = $objDate->$arrData['eval']['rgxp'];
				$objWidget->datepicker = '
				<script type="text/javascript"><!--//--><![CDATA[//><!--
				window.addEvent(\'domready\', function() { ' . sprintf($this->getDatePickerString(), 'ctrl_' . $objWidget->id) . ' });
				//--><!]]></script>';
			}


			// Register field name for rich text editor usage
			if (strlen($fieldConf[$field]['eval']['rte']) && $GLOBALS['TL_CONFIG']['useRTE'])
			{
/*
				$GLOBALS['TL_RTE']['type'] = $fieldConf[$field]['eval']['rte'];
				$GLOBALS['TL_RTE']['fields'][] = 'ctrl_' . $field;
*/

				$objWidget->cols = 70;
				$objWidget->rows = 12;
			}

			$arrFields[$field] .= (is_object($objWidgetUpload) ? $objWidgetUpload->parse() : ''). $objWidget->parse() . ($objWidget->datepicker ? $objWidget->datepicker : '') ;

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

		// Create new user if there are no errors
		if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_items' && !$doNotSubmit)
		{
			if ($blnModeAdd)
			{
				$this->itemInsert($arrItem);
			} 
			else 
			{
				$this->itemUpdate($arrItem, $arrValues['id']);
			}
		}


		// Set template form
		$objTemplate = new FrontendTemplate($this->catalog_template);

		$objTemplate->field = join('',$arrFields);
		$objTemplate->formId = 'tl_catalog_items';
		$objTemplate->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['saveNclose']);
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
				throw new Exception(sprintf('Cannot find rich text editor configuration file "%s.php"', $GLOBALS['TL_RTE']['type']));
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

		$this->Template->form = $objTemplate->parse();
	
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
    


	/**
	 * Update existing item and redirect
	 * @param array
	 */
	private function itemUpdate($arrData, $id)
	{
		$arrData['tstamp'] = time();

		// Create user
		$objNewItem = $this->Database->prepare("UPDATE ".$this->strTable." %s WHERE id=?")
				->set($arrData)
				->execute(intval($id));

		$this->redirect($this->refererUrl);
	}

	/**
	 * Create a new item and redirect
	 * @param array
	 */
	private function itemInsert($arrData)
	{
		$arrData['tstamp'] = time();
		$arrData['pid'] = $this->catalog;

		// Create user
		$objNewItem = $this->Database->prepare("INSERT INTO ".$this->strTable." %s")->set($arrData)->execute();
		$insertId = $objNewItem->insertId;
		
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

		$this->redirect($this->refererUrl);
	}

}

?>