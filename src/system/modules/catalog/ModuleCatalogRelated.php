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
 * Class ModuleCatalogRelated
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
 *
 */
class ModuleCatalogRelated extends ModuleCatalog
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_catalogrelated';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG RELATED ###';

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
		$this->catalog_related = deserialize($this->catalog_related);

		return parent::generate();
	}
	
// archived!=1 AND price>{{catalog::price}}-100 AND price<{{catalog::price}}+100 AND date<{{date}}+7*24*60*60
	
	protected function compile()
	{
		$objCatalogType = $this->Database->prepare("SELECT aliasField,titleField FROM tl_catalog_types WHERE id=?")
										->execute($this->catalog);

		$strAlias = $objCatalogType->aliasField ? " OR ".$objCatalogType->aliasField."=?" : '';		
		
		$objCatalog = $this->Database->prepare("SELECT * FROM ".$this->strTable." WHERE (id=?".$strAlias.")")
										->limit(1)
										->execute($this->Input->get('items'), $this->Input->get('items'));

		if ($objCatalog->numRows)
		{
			// Query Catalog
			$limit = is_numeric($this->catalog_limit)? $this->catalog_limit : 0;
		
			$arrCatalog = $objCatalog->fetchAllAssoc();
			$arrCatalog = $arrCatalog[0];
			$strWhere = $this->replaceCatalogTags($this->catalog_where, $arrCatalog);
			$strOrder = ($this->catalog_random_disable) ? trim($this->catalog_order) : "RAND()";
	
			// Add Related Query
			$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'];
			$strRelated = array();
			$arrRelated = array();

			foreach($this->catalog_related as $related)
			{
				//if tags split into multiple FIND_IN_SET();
				// optimized by c.schiffler, we want to have matching on all tags instead of only one match to have a relation.
				switch ($fieldConf[$related]['eval']['catalog']['type'])
				{
					case 'tags':
						$tags = split(',', $objCatalog->$related);
						$tmpRelated = array();
/*						foreach ($tags as $tag)
						{
							$tmpRelated[] = 'FIND_IN_SET(?,'.$related.')>0';
							$arrRelated[] = $tag;
						}
						$strRelated[] = '('.join(' OR ', $tmpRelated).')';
*/
						foreach ($tags as $tag)
						{
							$tmpRelated[] = '(FIND_IN_SET(?,'.$related.')>0)';
							$arrRelated[] = $tag;
						}
						$strRelated[] = '(('.join('+', $tmpRelated).')>=' . $this->catalog_related_tagcount . ')';
						break;

					default:
						$strRelated[] = $related.'=?';
						$arrRelated[] = $objCatalog->$related;

				}
			}
			
			// convert to string
			$strRelated = join(' AND ', $strRelated);


			$arrQuery = $this->processFieldSQL($this->catalog_visible);		
	
			// Run Query
			$objCatalogStmt = $this->Database->prepare("SELECT ".join(',',$this->systemColumns).",".join(',',$arrQuery).", (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE pid=? AND id!=?".($strRelated ? " AND ".$strRelated : "").($strWhere ? " AND ".$strWhere : "").(strlen($strOrder) ? " ORDER BY ".$strOrder : ""));
			
			if ($limit > 0)
			{
				$objCatalogStmt->limit($limit);
			}
			
			$objCatalog = $objCatalogStmt->execute(array_merge(array($this->catalog, $objCatalog->id ), $arrRelated));
	
	
			$this->Template->catalog = $this->parseCatalog($objCatalog, true, $this->catalog_template, $this->catalog_visible);
				
			// Template variables
			$this->Template->visible = $this->catalog_visible;

		}

	}


}

?>