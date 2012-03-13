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
 * Class ModuleCatalogList
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
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
		
		$this->catalog_visible = deserialize($this->catalog_visible, true);
		
		return parent::generate();
	}
		/**
	 * (non-PHPdoc)
	 * @see Module::compile()
	 */
	protected function compile()
	{
		$filterurl = $this->parseFilterUrl($this->catalog_search);
		
		$arrCondition = deserialize($this->catalog_condition, true);
		$blnCondition = false;

		if ($this->catalog_condition_enable
		    && count($arrCondition))
		{
			$blnCondition = count(array_intersect_key($filterurl['current'],
			                      array_values($arrCondition))) == count($arrCondition);
		}

		if (!$this->catalog_condition_enable
			|| ($this->catalog_condition_enable && $blnCondition)
			|| count($filterurl['procedure']['search']))
		{
			$this->catalog_search = deserialize($this->catalog_search, true);

			// Query Catalog
			$filterurl = $this->addSearchFilter($filterurl);
			$arrParams = $this->generateStmtParams($filterurl);
			$strWhere = $this->generateStmtWhere($filterurl);
			$strOrder = $this->generateStmtOrderBy($filterurl);
			
			if (strlen($this->Input->post('per_page')))
			{
				$this->perPage = $this->Input->post('per_page');
			}

			$limit = NULL;
			$offset = 0;

			// issue #81
			if($this->catalog_list_use_limit && ($this->catalog_limit || $this->catalog_list_offset))
			{
				$limit = (is_numeric($this->catalog_limit))? $this->catalog_limit : NULL;
				
				if($this->catalog_list_offset)
					$offset = $this->catalog_list_offset;
			}

			// Split pages
			if ($this->perPage > 0)
			{
				// Get total number of items
				$total = $this->fetchItemCount($strWhere, $arrParams);
				
				if (!is_null($limit))
				{
					$total -= $limit;
				}
				
				$total -= $offset;

				// Get the current page
				$page = $this->Input->get('page') ? $this->Input->get('page') : 1;

				if ($page > ($total/$this->perPage))
				{
					$page = ceil($total/$this->perPage);
				}

				// Set limit and offset
				$pageOffset = (max($page, 1) - 1) * $this->perPage;
				$offset += $pageOffset;
				$limit = is_null($limit)?$this->perPage:min($limit - $offset, $this->perPage);

				// Add pagination menu
				$objPagination = new Pagination($total, $this->perPage);
				$this->Template->pagination = $objPagination->generate("\n  ");
			}
			
			$objCatalog = $this->fetchCatalogItems($this->catalog_visible, $strWhere,
																						$arrParams, $strOrder, $limit, $offset);
			
			if (!$limit)
				$total = $objCatalog->numRows;
			
			// for orientation in the pages
			$this->Template->total = $total;

			if ($total > 0)
			{
				if ($limit)
					$pageLimit = min($pageOffset + $limit, $total);
				else
					$pageLimit = $total;
				$this->Template->header = sprintf($GLOBALS['TL_LANG']['MSC']['catalogSearchResults'], $pageOffset +1, $pageLimit, $total);
				// page stats
				if ($this->perPage > 0)
				{
					$this->Template->header .= ' ' . sprintf($GLOBALS['TL_LANG']['MSC']['catalogSearchPages'],
															$page, ceil($total/$this->perPage));
				}
			}
			else
				$this->Template->header = $GLOBALS['TL_LANG']['MSC']['catalogSearchEmpty'];
			
			$this->Template->catalog = $this->parseCatalog($objCatalog, true, $this->catalog_template, $this->catalog_visible);
		} // condition check
		else
		{
			$this->parseConditionsNotMet($arrCondition, $filterurl);
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
		$this->Template->visible = $this->catalog_visible;
		$this->Template->arrSystemColumns = $this->systemColumns;
	}
	
	/**
	 * Replaces the catalog var in the template with a message that the
	 * conditions where not met.
	 * @param array $arrCondition
	 * @param array $filterurl
	 * @return void
	 */
	protected function parseConditionsNotMet(array $arrCondition, array $filterurl)
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
		$objTemplate->condition = sprintf($GLOBALS['TL_LANG']['MSC']['catalogCondition'], implode(', ',$labels));
		$this->Template->catalog = $objTemplate->parse();
	}

	/**
	 * @param array $filterurl
	 * @return array of parameters for use in WHERE
	 */
	protected function generateStmtParams(array $arrFilterUrl)
	{
		$params = array();
		
		if (count($arrFilterUrl['values']['where']))
		{
			$params = array_merge($params, ModuleCatalog::flatParams($arrFilterUrl['values']['where']));
		}

		// add tags combination here...

		if (count($arrFilterUrl['values']['tags'])) {
			$params = array_merge($params, ModuleCatalog::flatParams($arrFilterUrl['values']['tags']));
		}
		
		return $params;
	}
	
	/**
	 * @param array $arrFilterUrl
	 * @return string for the ORDER BY part
	 */
	protected function generateStmtOrderBy(array $arrFilterUrl)
	{
		$result = '';
		
		if (strlen($arrFilterUrl['procedure']['orderby']))
		{
			$result = 'ORDER BY ' . $arrFilterUrl['procedure']['orderby'];
		} else {
			$result = trim($this->replaceInsertTags($this->catalog_order));
		}
		
		return $result;
	}

	/**
	 * @pre isset($this->strTable)
	 * @param string $strWhere
	 * @param array $arrParams
	 * @return int total count of items
	 */
	protected function fetchItemCount($strWhere, array $arrParams)
	{
		// pid
		$params = array_merge(array($this->objCatalogType->id), $arrParams);
	  
		$objTotalStmt = $this->Database->prepare(sprintf("SELECT COUNT(id) AS count FROM %s WHERE pid=? %s", 
													$this->objCatalogType->tableName,
													$strWhere?" AND " . $strWhere:''
												));

		$objTotal = $objTotalStmt->execute($params);
		return $objTotal->count;
	}

	/**
	 * Creates the WHERE part for the statement to fetch items
	 * @param array $arrFilterUrl
	 * @return string for use after WHERE
	 */
	protected function generateStmtWhere(array $arrFilterUrl)
	{
		$where = array();

		if(strlen($this->catalog_where))
			$where[] = $this->replaceInsertTags($this->catalog_where);
		
		if (count($arrFilterUrl['procedure']['where']))
		{
			$where[] = implode(" " . $this->catalog_query_mode . " ", $arrFilterUrl['procedure']['where']);
		}

		// TODO: changing the catalog_tags_mode to catalog_query_mode here will allow us to filter multiple tags.
		// 		 but this beares side kicks in ModuleCatalog aswell. Therefore we might rather want to add another combination method
		//		 here?
		if (count($arrFilterUrl['procedure']['tags']))
		{
			$where[] = implode(" " . $this->catalog_tags_mode . " ", $arrFilterUrl['procedure']['tags']);
		}

		// restrict to published items
		if(!BE_USER_LOGGED_IN && $this->publishField)
		{
			$where[] = $this->publishField . '=1';
		}

		return implode(' AND ', $where);
	}

	/**
	 * Adds the search configuration to the where
	 * @param array $arrFilters
	 * @return array $arrFilters with additional where fields for search
	 */
	protected function addSearchFilter(array $filterurl)
	{
		if (is_array($this->catalog_search)
			&& strlen($this->catalog_search[0])
			&& count($filterurl['procedure']['search']))
		{
			$searchProcedure = array();
			$searchValues = array();
			
			foreach($this->catalog_search as $field)
			{
				if (array_key_exists($field, $filterurl['procedure']['search']))
				{
					$searchProcedure[] = $filterurl['procedure']['search'][$field];
					
					if (is_array($filterurl['values']['search'][$field]))
					{
						$searchValues = array_merge($searchValues, $filterurl['values']['search'][$field]);
					}
					else
					{
						$searchValues[] = $filterurl['values']['search'][$field];
					}
				}
			}

			// field binding not possible here
			$filterurl['procedure']['where'][] = ' ('.implode(" OR ", $searchProcedure).')';
			$filterurl['values']['where'][] = $searchValues;
		}
		
		return $filterurl;
	}
}
?>