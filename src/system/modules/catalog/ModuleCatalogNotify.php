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
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com> 
 * @package    Catalog 
 * @license    LGPL
 * @filesource
 */


/**
 * Class ModuleCatalogNotify
 *
 * @copyright  Thyon Design 2008 
 * @author     John Brand <john.brand@thyon.com>
 * @package    CatalogNotify
 *
 */

class ModuleCatalogNotify extends ModuleCatalog
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalognotify';


	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG NOTIFY ###';

			return $objTemplate->parse();
		}

		// Fallback template
		if (!strlen($this->catalog_layout))
			$this->catalog_layout = $this->strTemplate;

		$this->strTemplate = $this->catalog_layout;

		$this->catalog_notify_fields = deserialize($this->catalog_notify_fields);
		$this->catalog_recipients = deserialize($this->catalog_recipients);

		return parent::generate();
	}


	/**
	 * Generate module
	 */
	protected function compile()
	{

		global $objPage;

		$this->Template->erromsg = '';
		
		$objCatalogType = $this->Database->prepare("SELECT name,aliasField FROM tl_catalog_types WHERE id=?")
										->execute($this->catalog);

		$strAlias = $objCatalogType->aliasField ? " OR ".$objCatalogType->aliasField."=?" : '';		
		
		$objCatalog = $this->Database->prepare("SELECT * FROM ".$this->strTable." WHERE (id=?".$strAlias.")")
										->limit(1)
										->execute($this->Input->get('items'), $this->Input->get('items'));

		if ($objCatalog->numRows < 1)
		{
			$this->Template->errormsg = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['catalogItemInvalid'].'</p>';
		
			// Do not index the page
			$objPage->noSearch = 1;
			$objPage->cache = 0;

			// Send 404 header
			header('HTTP/1.0 404 Not Found');
			return;
		}

		$this->Template->fields = '';
		$doNotSubmit = false;
		$formId = 'tl_catalog_notify';

		// Captcha
		if (!$this->disableCaptcha)
		{
			$arrCaptcha = array
			(
				'id'=> 'captcha',
				'label'=> $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'mandatory'=>true,
				'required'=>true
			);

			$objCaptcha = new FormCaptcha($arrCaptcha);

			if ($this->Input->post('FORM_SUBMIT') == $formId)
			{
				$objCaptcha->validate();

				if ($objCaptcha->hasErrors())
				{
					$doNotSubmit = true;
				}
			}
		}

		$inputType = 'text';
		$arrFields = array();
		$i = 0;

		
		// Build form
		foreach ($this->catalog_notify_fields as $field)
		{
			$label = $field;
			$field = standardize($field);
			
			$arrData = array
			(
				'label'                   => $label,
				'inputType'               => $inputType,
				'eval'                    => array('mandatory'=>true)
			);

			$strClass = $GLOBALS['TL_FFL'][$inputType];

			// Continue if the class is not defined
			if (!$this->classFileExists($strClass))
			{
				continue;
			}

			$objWidget = new $strClass($this->prepareForWidget($arrData, $field));

			$objWidget->storeValues = true;
			$objWidget->rowClass = 'row_' . $i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');

			// Validate input
			if ($this->Input->post('FORM_SUBMIT') == $formId)
			{
				$objWidget->validate();
				$varValue = $objWidget->value;

				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}

				// Store current value
				elseif ($objWidget->submitInput())
				{
					$arrStore[$field]['label'] = $label;
					$arrStore[$field]['value'] = $varValue;
				}
			}

			$temp = $objWidget->parse();

			$this->Template->fields .= $temp;
			$arrFields[$field] .= $temp;

			++$i;
		}

		// Captcha
		if (!$this->disableCaptcha)
		{
			$objCaptcha->rowClass = 'row_'.$i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');
			$strCaptcha = $objCaptcha->parse();

			$this->Template->fields .= $strCaptcha;
			$arrFields['captcha'] .= $strCaptcha;
		}

		$this->Template->rowLast = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
		$this->Template->enctype = 'application/x-www-form-urlencoded';

		// Send catalog notification e-mail, if there are no errors
		if ($this->Input->post('FORM_SUBMIT') == $formId && !$doNotSubmit)
		{
			$arrCatalog = $objCatalog->fetchAllAssoc();
			$arrCatalog = $arrCatalog[0];

			$objEmail = new Email();
	
			$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
			$objEmail->subject = $this->replaceCatalogTags($this->catalog_subject, $arrCatalog);
				
			// replace catalog and other inserttags
			$text = $this->replaceCatalogTags($this->catalog_notify, $arrCatalog);

			// replace catalog name
			$text = str_replace('##catalog##', $objCatalogType->name, $text);
			// replace catalog url
			$url = $this->Environment->base . ampersand($this->Environment->request, ENCODE_AMPERSANDS);
			$text = str_replace('##link##', $url, $text);
			
			$notify = '';
			foreach($arrStore as $k=>$v)
			{
				$notify .= $arrStore[$k]['label'] . ': ' . $arrStore[$k]['value'] . "\n";
			}
			// compile body text
			$objEmail->text =  $text. "\n\n" . $notify;


			foreach($this->catalog_recipients as $recipient) 
			{
				$objEmail->sendTo($recipient);
			}
			
			$this->log('A user has notified you of interest in the following catalog item: '.$url, 'ModuleCatalogNotify compile()', TL_GENERAL);
			$_SESSION[$formId]['TL_CONFIRM'][0] = $GLOBALS['TL_LANG']['MSC']['notifyConfirm'];

		// initialize
		$this->initializeSession($formId);
			
		}

		$this->Template->captcha = $arrFields['captcha'];
		$this->Template->formId = $formId;
		$this->Template->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['notifySubmit']);
		$this->Template->action = ampersand($this->Environment->request, ENCODE_AMPERSANDS);
	
	}


	/**
	 * Initialize the form in the current session
	 * @param string
	 */
	private function initializeSession($formId)
	{
		if ($this->Input->post('FORM_SUBMIT') != $formId)
		{
			return;
		}

		$arrMessageBox = array('TL_ERROR', 'TL_CONFIRM', 'TL_INFO');
		$_SESSION['FORM_DATA'] = is_array($_SESSION['FORM_DATA']) ? $_SESSION['FORM_DATA'] : array();

		foreach ($arrMessageBox as $tl)
		{
			if (is_array($_SESSION[$formId][$tl]))
			{
				$_SESSION[$formId][$tl] = array_unique($_SESSION[$formId][$tl]);

				foreach ($_SESSION[$formId][$tl] as $message)
				{
					$objTemplate = new FrontendTemplate('form_message');

					$objTemplate->message = $message;
					$objTemplate->rowClass = strtolower($tl);

					$this->Template->fields = $objTemplate->parse() . "\n" . $this->Template->fields;
				}

				$_SESSION[$formId][$tl] = array();
			}
		}
	}


}

?>