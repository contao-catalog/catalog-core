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
 * Class ModuleCatalogList
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
 *
 */

class ModuleCatalogList extends ModuleCatalog
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cataloglist';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### CATALOG LIST ###';

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
		$filterurl = $this->parseFilterUrl($this->catalog_search);

		$arrCondition = deserialize($this->catalog_condition, true);
		$blnCondition = false;
		if ($this->catalog_condition_enable && is_array($arrCondition))
		{
			$blnCondition = count($arrCondition) && count(array_intersect_key($filterurl['current'], array_flip($arrCondition)))>= count($arrCondition);
		}
		if (!$this->catalog_condition_enable || ($this->catalog_condition_enable && $blnCondition) || is_array($filterurl['procedure']['search']))
		{

			// Query Catalog
			$limit = null;
			$offset = 0;
	
			// add search as single query using OR, after foreach
			$this->catalog_search = deserialize($this->catalog_search, true); 

			if (is_array($this->catalog_search) && strlen($this->catalog_search[0]) && is_array($filterurl['procedure']['search']))
			{
				// reset arrays
				$searchProcedure = array();
				$searchValues = array();

				foreach($this->catalog_search as $field)
				{
					if (array_key_exists($field, $filterurl['procedure']['search']))
					{
						$searchProcedure[] = $filterurl['procedure']['search'][$field];
						if (is_array($filterurl['values']['search'][$field]))
						{
							foreach($filterurl['values']['search'][$field] as $item)
							{
								$searchValues[] = $item;
							}
						}
						else
						{
							$searchValues[] = $filterurl['values']['search'][$field];
						}
					}
				}

				$filterurl['procedure']['where'][] = ' ('.join(" OR ", $searchProcedure).')';
				$filterurl['values']['where'] = is_array($filterurl['values']['where']) ? (array_merge($filterurl['values']['where'],$searchValues)) : $searchValues;

			}
	
			$params[0] = $this->catalog;
			if (is_array($filterurl['values']['where'])) {
				$params = array_merge($params, $filterurl['values']['where']);
			}
	
			if (is_array($filterurl['values']['tags'])) {
				$params = array_merge($params, $filterurl['values']['tags']);
			}
	
	
			$strCondition = $this->replaceInsertTags($this->catalog_where);
			$strWhere = (strlen($strCondition) ? " AND ".$strCondition : "")
				.($filterurl['procedure']['where'] ? " AND ".join(" ".$this->catalog_query_mode." ", $filterurl['procedure']['where']) : "")
				.($filterurl['procedure']['tags'] ? " AND ".join(" ".$this->catalog_tags_mode." ", $filterurl['procedure']['tags']) : "");
		
			$strOrder = (strlen($filterurl['procedure']['orderby']) ? $filterurl['procedure']['orderby'] : trim($this->catalog_order));

			$this->perPage = strlen($this->Input->post('per_page')) ? $this->Input->post('per_page') : $this->perPage;

			// Split pages
			if ($this->perPage > 0)
			{
				// Get total number of items
				$objTotalStmt = $this->Database->prepare("SELECT id AS count FROM ".$this->strTable." WHERE pid=?".$strWhere);
	
				if (!is_null($limit))
				{
					$objTotalStmt->limit($limit);
				}
	
				$objTotal = $objTotalStmt->execute($params);
				$total = $objTotal->numRows;
	
				// Get the current page
				$page = $this->Input->get('page') ? $this->Input->get('page') : 1;
	
				if ($page > ($total/$this->perPage))
				{
					$page = ceil($total/$this->perPage);
				}
	
				// Set limit and offset
				$limit = ((is_null($limit) || $this->perPage < $limit) ? $this->perPage : $limit);
				$offset = ((($page > 1) ? $page : 1) - 1) * $this->perPage;
	
				// Add pagination menu
				$objPagination = new Pagination($total, $this->perPage);
				$this->Template->pagination = $objPagination->generate("\n  ");
			}
	
			// Run Query
			$objCatalogStmt = $this->Database->prepare("SELECT *, (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE pid=?".$strWhere.(strlen($strOrder) ? " ORDER BY ".$strOrder : "")); 
			
			// add filter and order later

			// Limit result
			if ($limit)
			{
				$objCatalogStmt->limit($limit, $offset);
			}
		
			$objCatalog = $objCatalogStmt->execute($params);
	
	
			$this->Template->catalog = $this->parseCatalog($objCatalog, true, $this->catalog_template, $this->catalog_visible);
			
		} // condition check
		else 
		{
			$labels = array();
			foreach ($arrCondition as $condition)
			{
				if (array_key_exists($condition, $filterurl['current']))
				{
					continue;
				}
				$labels[] = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$condition]['label']['0'];
			}
			// create template with no entries, but passing the condition instead
			$objTemplate = new FrontendTemplate($this->catalog_template);
			$objTemplate->entries = array();
			$objTemplate->catalog_condition = $labels;
			$objTemplate->condition = sprintf($GLOBALS['TL_LANG']['MSC']['catalogCondition'], join(', ',$labels));
			$this->Template->catalog = $objTemplate->parse();

		}

		// Editing variables
		$this->Template->editEnable = $this->catalog_edit_enable;
		if ($this->catalog_edit_enable)
		{
			// Add Url
			$objPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
										->limit(1)
										->execute($this->catalog_editJumpTo);
	
			$this->Template->addUrl = $objPage->numRows ? ampersand($this->generateFrontendUrl($objPage->fetchAssoc())): ampersand($this->Environment->request, ENCODE_AMPERSANDS);
		}

		// Template variables
		$this->Template->total = $total;
		$this->Template->visible = $this->catalog_visible;

	}

}

?>