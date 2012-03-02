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
 * Module for sending some notification to a person related
 * to the catalog entry (maybe the creator)
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Controller
 *
 */
class ModuleCatalogNotify extends ModuleCatalog
{  /**
   * id of the notification form
   * @var string
   */
  const FORMID = 'tl_catalog_notify';

  /**
   * input type for all notification form values
   * @var string
   */
  const FORM_INPUTTYPE = 'text';

	/**
	 * Default Template
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
	 * (non-PHPdoc)
	 * @see Module::compile()
	 */
	protected function compile()
	{
		global $objPage;

		if(! $this->objCatalogType)
		{
		  return $this->compileInvalidCatalog();
		}
		
		$objCatalog = $this->fetchCatalogItemFromRequest();
		
		if(! $objCatalog)
		{
		  return $this->compileInvalidItem();
		}

		$this->Template->fields = '';
		$this->Template->sent = false;
		
		$doNotSubmit = false;

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

			if ($this->Input->post('FORM_SUBMIT') == self::FORMID)
			{
				$objCaptcha->validate();

				if ($objCaptcha->hasErrors())
				{
					$doNotSubmit = true;
				}
			}
		}

		$inputType = self::FORM_INPUTTYPE;
		$arrFields = array();
		$arrWidgets = array();
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
			if ($this->Input->post('FORM_SUBMIT') == self::FORMID)
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
			$arrFields[$field] = $temp;
			$arrWidgets[$field] = $objWidget;
			++$i;
		}

		// add long message to form
		$field = 'message';
		$inputType = 'textarea';

		$strClass = $GLOBALS['TL_FFL'][$inputType];
		// Continue if the class is not defined
		if ($this->classFileExists($strClass))
		{
		  // Long text message
			$arrMessage = array
			(
				'id'         => $field,
				'label'      => $GLOBALS['TL_LANG']['MSC'][$field],
				'mandatory'  => true,
				'required'   => true,
				'inputType'  => $inputType,
				'eval'       => array('mandatory'=>true)
			);
		
  		$objMessage = new $strClass($this->prepareForWidget($arrMessage, $field));
  		$objMessage->rowClass = 'row_'.$i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');
  		if($this->Input->post('FORM_SUBMIT') == self::FORMID)
  		{
  			$objMessage->validate();
  			
  			if ($objMessage->hasErrors())
  			{
  				$doNotSubmit = true;
  			} elseif ($objWidget->submitInput())
  			{
  			  $arrStore[$field]['label'] = $arrMessage['label'];
          $arrStore[$field]['value'] = $objWidget->value;
  			}
  		}
  		$strMessage = $objMessage->parse();
      $arrStore[$field]['label'] = $arrMessage['label'];
      $arrStore[$field]['value'] = $objMessage->value;
  		$arrFields[$field] = $strMessage;
  		$arrWidgets[$field] = $objMessage;

  		$this->Template->fields .= $strMessage;
  		++$i;
		}

		// Captcha
		if (!$this->disableCaptcha)
		{
			$objCaptcha->rowClass = 'row_'.$i . (($i == 0) ? ' row_first' : '') . ((($i % 2) == 0) ? ' even' : ' odd');
			$strCaptcha = $objCaptcha->parse();

			$this->Template->fields .= $strCaptcha;
			$arrFields['captcha'] = $strCaptcha;
			$arrWidgets['captcha'] = $objCaptcha;
		}

		$this->Template->rowLast = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
		$this->Template->enctype = 'application/x-www-form-urlencoded';
		$this->Template->hasError = $doNotSubmit;
		
		// Send catalog notification e-mail, if there are no errors
		if ($this->Input->post('FORM_SUBMIT') == self::FORMID && !$doNotSubmit)
		{
			$arrCatalog = $objCatalog->fetchAssoc();
			
			$this->sendNotification($arrCatalog, $arrStore);
			
			$this->Template->sent = true;
			
			
			if($this->catalog_useJumpTo)
			{
				$objJump = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
										->execute($this->jumpTo);
				if($objJump->numRows)
				{
					$this->redirect($this->generateFrontendUrl($objJump->row()));
				}
			}
		}

		// initialize
		$this->initializeSession(self::FORMID);

		$this->Template->captcha = $arrFields['captcha'];
		$this->Template->arrFields = $arrFields;
		$this->Template->arrWidgets = $arrWidgets;
		$this->Template->formId = self::FORMID;
		$this->Template->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['notifySubmit']);
		$this->Template->action = ampersand($this->Environment->request, ENCODE_AMPERSANDS);
	}
		/**
	 * Actually sends the email
	 * @param array $arrItem all values of the catalog item
	 * @param array $arrNotification all values from the form
	 * @return void
	 * @post $this->catalog_recipients contains the one from the item
	 */
	protected function sendNotification(array $arrItem, array $arrNotification)
	{
		$objEmail = new Email();

		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->subject = $this->replaceCatalogTags($this->catalog_subject, $arrItem);
			
		// replace catalog and other inserttags
		$text = $this->replaceCatalogTags($this->catalog_notify, $arrItem);

		// replace catalog name
		$text = str_replace('##catalog##', $objCatalogType->name, $text);
		
		// replace catalog url
		$url = $this->Environment->base . ampersand($this->Environment->request,
		                                            ENCODE_AMPERSANDS);
		$text = str_replace('##link##', $url, $text);

		$notify = '';
		foreach($arrNotification as $k=>$v)
		{
			$notify .= $v['label'] . ': ' . $v['value'] . "\n";
		}
		
		// compile body text
		$objEmail->text =  $text. "\n\n" . $notify;

		$additionalRecipients = array();
    $arrRecipientFields = deserialize($this->catalog_recipient_fields);

    if(is_array($arrRecipientFields) && count($arrRecipientFields))
    {
      foreach($arrRecipientFields as $field)
      {
        if($arrItem[$field])
        	$additionalRecipients[] = $arrItem[$field];
      }

      $this->catalog_recipients =
        array_unique(array_merge($this->catalog_recipients,
                                 $additionalRecipients));
    }

    if (! is_array($this->catalog_recipients))
    {
      $this->catalog_recipients = array();
    }

  	foreach($this->catalog_recipients as $recipient)
  	{
  		// prevent uncool Swift_RfcComplianceExceptions when having checked
  		// recipient fields that aren't valid email addresses
  		if($this->isValidEmailAddress($recipient))
  			$objEmail->sendTo($recipient);
  	}
		
		// write log
		$this->log('A user has notified you of interest in the following catalog item: ' . $url,
							 'ModuleCatalogNotify compile()', TL_GENERAL);
		
		$_SESSION[self::FORMID]['TL_CONFIRM'][0] = $GLOBALS['TL_LANG']['MSC']['notifyConfirm'];
	}

	/**
	 * Initialize the form in the current session
	 * @param string $formId
	 * @return void
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
		/**
	 * (non-PHPdoc)
	 * @see ModuleCatalog::fetchCatalogItemFromRequest()
	 */
	protected function fetchCatalogItemFromRequest(array $arrFields =array()) {
    $objResult = parent::fetchCatalogItemFromRequest($arrFields);

    // restrict to published items
    if($objResult
       && (!BE_USER_LOGGED_IN)
       && $this->publishField
       && (! $objResult->__get($this->publishField)))
    {
      $objResult = null;
    }

    return $objResult;
	}
}?>