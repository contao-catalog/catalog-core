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
 * Class ModuleCatalogNotify
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
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
		
		$objCatalog = $this->Database->prepare('SELECT * FROM '.$this->strTable.' WHERE '.(!BE_USER_LOGGED_IN && $this->publishField ? $this->publishField.'=1 AND ' : '').'(id=?'.$strAlias.')')
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

			$additionalRecipients = array();
			foreach(deserialize($this->catalog_recipient_fields) as $field)
			{
				if($arrCatalog[$field])
					$additionalRecipients[] = $arrCatalog[$field];
			}
			$this->catalog_recipients = array_unique(array_merge($this->catalog_recipients, $additionalRecipients));
			
			foreach($this->catalog_recipients as $recipient) 
			{
				if($recipient)
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