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
 * Class ModuleCatalogReader
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
 *
 */
class ModuleCatalogReader extends ModuleCatalog
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogreader';


	/**
	 * Display a wildcard in the back end
	 * @return string
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
	 * Generate module
	 */
	protected function compile()
	{
		global $objPage;

		$this->Template->catalog = '';
		$this->Template->referer = $this->getReferer(ENCODE_AMPERSANDS);
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

		$objCatalogType = $this->Database->prepare("SELECT aliasField,titleField FROM tl_catalog_types WHERE id=?")
										->execute($this->catalog);


		$strAlias = $objCatalogType->aliasField ? " OR ".$objCatalogType->aliasField."=?" : '';		
		
		$objCatalog = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE (id=?".$strAlias.")")
										->limit(1)
										->execute($this->Input->get('items'), $this->Input->get('items'));

		if ($objCatalog->numRows < 1)
		{
			$this->Template->catalog = '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['catalogItemInvalid'].'</p>';

			// Do not index the page
			$objPage->noSearch = 1;
			$objPage->cache = 0;

			// Send 404 header
			header('HTTP/1.0 404 Not Found');
			return;
		}
		
		$this->Template->catalog = $this->parseCatalog($objCatalog, false, $this->catalog_template, $this->catalog_visible);
		$this->Template->visible = $this->catalog_visible;

		// Overwrite page title
		if (strlen($objCatalogType->titleField)) 
		{
			$titleField = $objCatalogType->titleField;
			$objPage->pageTitle = $objCatalog->$titleField;
		}

		// Comments
		$this->processComments($objCatalog);
	
	}


}

?>