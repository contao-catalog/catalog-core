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
 * Frontend module to present one single catalog item
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Controller
 *
 */
class ModuleCatalogReader extends ModuleCatalog
{	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogreader';

  /**
   * (non-PHPdoc)
   * @see ModuleCatalog::generate()
   */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG READER ###';

			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			
			// URL scheme changed since 2.9.0
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

		$this->catalog_visible = deserialize($this->catalog_visible);

		return parent::generate();
	}
   /**
   * (non-PHPdoc)
   * @see Module::compile()
   */
	protected function compile()
	{
	  $this->basicVarsToTemplate();
	
    if(! $this->objCatalogType) {
      return $this->compileInvalidCatalog();
    }
		
    $objItem = $this->fetchCatalogItemFromRequest($this->catalog_visible);

    // give error if nothing found
    if(! $objItem)
		{
		  return $this->compileInvalidItem();
		}
		
		$this->Template->visible = $this->catalog_visible;

		// The reader is on its own page, so we can extend page
		// information with item information
		global $objPage;
		
		// Extend page title
		if (strlen($this->objCatalogType->titleField))
		{
		  $objPage->pageTitle .= ' ' . $objItem->{$this->objCatalogType->titleField};
		}

		// Extend page description
		if (strlen($objCatalogType->descriptionField))
		{
			$objPage->description .= ' ' . strip_tags($objItem->{$this->objCatalogType->descriptionField});
		}

		// Extend page keywords
		if (strlen($objCatalogType->keywordsField))
		{
			$GLOBALS['TL_KEYWORDS'] .= ' ' . $this->generateKeywords($objItem->{$this->objCatalogType->keywordsField});
		}

		// Process Comments if not disabled
		if (!$this->catalog_comments_disable)
		{
			$this->processComments($objItem);	
		}
		
		// add a reporting form if activated
		if ($objCatalogType->activateReporting)
		{
		  $this->processReporting($objItem);
		}
		
		// Keep this at the end to allow the reader template to manipulate $objPage
		$this->Template->catalog = $this->parseCatalog($objItem, false,
		                                               $this->catalog_template,
		                                               $this->catalog_visible);
	}
		/**
	 * Offers a form to report the catalog item to some configured users
	 * @return void
	 * @post $this->Template contains all information to present the report form
	 */
	protected function processReporting(Database_Result $objCatalog)
	{
	  $this->Template->activateReporting = true;
	
	  // prepare the form
	  $arrWidgets = $this->prepareReportingForm();
	
	  // check if the form has been submitted already
	  if ($this->Input->post('FORM_SUBMIT') == 'catalog_reporting')
	  {
	    $arrIds = deserialize($objCatalogType->notifyUsers, true);
	
	    foreach ($arrWidgets as $name => $objWidget)
	    {
	      $objWidget->validate();
	
	      if ($objWidget->hasErrors())
	      {
	        $doNotSubmit = true;
	      }
	    }
	
	    if (!$doNotSubmit)
	    {
	      // get all the mail adresses of all users
	      if (empty($arrIds))
	      {
	        return;
	      }
	
	      $arrRecipients = $this->Database->query('SELECT email
	                                               FROM tl_user
	                                               WHERE id IN(' . implode(',', $arrIds) . ')')->fetchEach('email');
	
	      $objEMail = new Email();
	
	      // Set the admin e-mail as "from" address
	      $objEMail->from = $GLOBALS['TL_ADMIN_EMAIL'];
	      $objEMail->fromName = $GLOBALS['TL_ADMIN_NAME'];
	
	      $objEMail->subject = 'New abuse report on a catalog item';
	
	      // Send e-mail
	      $strText = 'Hi, somebody reported an abuse. The id of the suspicious catalog entry is: ' . $objCatalog->id . "\n";
	      $strText .= 'The message of the user was the following:' . "\n\n";
	      $strText .= $this->Input->post('catalog_reporting_msg');
	
	      $objEMail->text = $strText;
	      $objEMail->sendTo($arrRecipients);
	    }
	  }
	
	  $objForm = new FrontendTemplate('form');
	  $objForm->formSubmit = 'catalog_reporting';
	  $objForm->tableless = true;
	  $objForm->method = 'post';
	  $objForm->hasError = $doNotSubmit;
	  $objForm->enctype = 'application/x-www-form-urlencoded';
	  $objForm->formId = 'catalog_reporting';
	  $objForm->action = $this->Environment->request;
	
	  $strFields = '';
	
	  foreach ($arrWidgets as $name => $objWidget)
	  {
	    $strFields .= $objWidget->parse();
	  }
	
	  $objForm->fields = $strFields;
	
	  $this->Template->reportingFormRaw = new stdClass();
	  $this->Template->reportingFormRaw->arrWidgets = $arrWidgets;
	  $this->Template->reportingFormRaw->objForm = $objForm;
	  $this->Template->reportingForm = $objForm->parse();
  }
		/**
	 * Prepare a reporting form
	 * @return array
	 */
	protected function prepareReportingForm()
	{
	  $arrReturn = array();
		  // text area
	  $arrData = array();
	  $arrData['mandatory']		= true;
	  $arrData['required']		= true;
	  $arrData['id']				= 'catalog_reporting_msg';
	  $arrData['name']			= 'catalog_reporting_msg';
		  $objTextArea = new FormTextArea($arrData);
		  $arrReturn['textarea'] = $objTextArea;
		  // captcha
	  $arrCaptcha = array();
	  $arrCaptcha['id']			= 'catalog_reporting_captcha';
	  $arrCaptcha['label']		= $GLOBALS['TL_LANG']['MSC']['securityQuestion'];
	  $arrCaptcha['mandatory']	= true;
	  $arrCaptcha['required']		= true;
		  $objCaptcha = new FormCaptcha($arrCaptcha);
		  $arrReturn['captcha'] = $objCaptcha;
		  // submit button
	  $arrSubmit = array();
	  $arrSubmit['slabel'] = $GLOBALS['TL_LANG']['MSC']['reportAbuse'];
		  $objSubmit = new FormSubmit($arrSubmit);
		  $arrReturn['submit'] = $objSubmit;
		  return $arrReturn;
	}

	/**
	 * Generate keywords from a raw string
	 * @param string $strInput
	 * @return string
	 */
	protected function generateKeywords($strInput)
	{
		$strKeywords = '';

		// remove html
		$strInput = strip_tags($strInput);

		// remove special characters
		$strInput = str_replace($GLOBALS['TL_CONFIG']['catalog']['keywordsInvalid'], ',', $strInput);

		// remove linebreaks
		$strInput = preg_replace('/(\n|\r|\r\n)+/', ' ', $strInput);

		// divide input string into single words
		$arrKeywords = explode(',', $strInput);
		
		foreach($arrKeywords as $strKeyword)
		{
			// ignore unimportant words, empty strings and words shorter than 3 chars
			if (in_array($strKeyword, $GLOBALS['TL_LANG']['MSC']['keywordsBlacklist']) || strlen($strKeyword) < 3)
				continue;

			// add nice keywords to output string
			else $strKeywords .= (strlen($strKeywords) ? ', ' : '') . $strKeyword;
		}

		// reduce to max. keywords
		if (count($arrKeywords)>$GLOBALS['TL_CONFIG']['catalog']['keywordCount'])
			$arrKeywords = array_slice($arrKeywords, 0, $GLOBALS['TL_CONFIG']['catalog']['keywordCount']);

		return($strKeywords);
	}
		
	/**
	 * (non-PHPdoc)
	 * @see ModuleCatalog::fetchCatalogItemFromRequest()
	 */
	protected function fetchCatalogItemFromRequest(array $arrFields) {
    $objResult = parent::fetchCatalogItemFromRequest($arrFields);
    
    // restrict to published items
    if($objResult
       && (!BE_USER_LOGGED_IN)
       && strlen($this->objCatalogType->publishField)
       && (! $objResult->{$this->objCatalogType->publishField}))
    {
      $objResult = null;
    }

    return $objResult;
	}
}?>