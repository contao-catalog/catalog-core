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
 * Class ModuleCatalogFeatured
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		ModuleCatalogFeatured
 *
 */
class ModuleCatalogFeatured extends ModuleCatalog
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogfeatured';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG FEATURED ###';

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
	
	
	
	protected function compile()
	{
		// Query Catalog
		$limit = is_numeric($this->catalog_limit)? $this->catalog_limit : 0;
	
		$strWhere = trim($this->replaceInsertTags($this->catalog_where));
		if(!BE_USER_LOGGED_IN && $this->publishField)
		{
			$strWhere .= (strlen($strWhere)?' AND ':'').$this->publishField.'=1';
		}
		$strOrder = ($this->catalog_random_disable) ? trim($this->catalog_order) : "RAND()";


		$arrQuery = $this->processFieldSQL($this->catalog_visible);
		
		// Run Query
		$objCatalogStmt = $this->Database->prepare("SELECT ".join(',',$this->systemColumns).",".join(',',$arrQuery).", (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE pid=?".($strWhere ? " AND ".$strWhere : "").(strlen($strOrder) ? " ORDER BY ".$strOrder : ""));
		
		if ($limit > 0)
		{
			$objCatalogStmt->limit($limit);
		}
		
		$objCatalog = $objCatalogStmt->execute($this->catalog);


		$this->Template->catalog = $this->parseCatalog($objCatalog, true, $this->catalog_template, $this->catalog_visible);
			
		// Template variables
		$this->Template->visible = $this->catalog_visible;

	}

}

?>