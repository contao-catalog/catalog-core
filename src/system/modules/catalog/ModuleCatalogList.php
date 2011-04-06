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

				$filterurl['procedure']['where'][] = ' ('.implode(" OR ", $searchProcedure).')';
				$filterurl['values']['where'] = is_array($filterurl['values']['where']) ? (array_merge($filterurl['values']['where'],$searchValues)) : $searchValues;

			}
	
			$params[0] = $this->catalog;
			if (is_array($filterurl['values']['where'])) {
				$params = array_merge($params, $filterurl['values']['where']);
			}
	
// add tags combination here...

			if (is_array($filterurl['values']['tags'])) {
				$params = array_merge($params, $filterurl['values']['tags']);
			}
	
	
			$strCondition = $this->replaceInsertTags($this->catalog_where);
			$strWhere = (strlen($strCondition) ? " AND ".$strCondition : "")
				.($filterurl['procedure']['where'] ? " AND ".implode(" ".$this->catalog_query_mode." ", $filterurl['procedure']['where']) : "")
				// TODO: changing the catalog_tags_mode to catalog_query_mode here will allow us to filter multiple tags.
				// 		 but this beares side kicks in ModuleCatalog aswell. Therefore we might rather want to add another combination method
				//		 here?
				.($filterurl['procedure']['tags'] ? " AND ".implode(" ".$this->catalog_tags_mode." ", $filterurl['procedure']['tags']) : "");

			if(!BE_USER_LOGGED_IN && $this->publishField)
			{
				$strWhere.=' AND '.$this->publishField.'=1';
			}

			$strOrder = (strlen($filterurl['procedure']['orderby']) ? $filterurl['procedure']['orderby'] : trim($this->catalog_order));

			$this->perPage = strlen($this->Input->post('per_page')) ? $this->Input->post('per_page') : $this->perPage;

			$limit = NULL;
			$offset = 0;
			// issue #81
			if($this->catalog_list_use_limit && ($this->catalog_limit || $this->catalog_list_offset))
			{
				$limit = (is_numeric($this->catalog_limit)/* && $limit*/)? $this->catalog_limit : NULL;
				if($this->catalog_list_offset)
					$offset = $this->catalog_list_offset;
			}
			// Split pages
			if ($this->perPage > 0)
			{
				// Get total number of items
				$objTotalStmt = $this->Database->prepare("SELECT COUNT(id) AS count FROM ".$this->strTable." WHERE pid=?".$strWhere);
	
				$objTotal = $objTotalStmt->execute($params);
				$total = $objTotal->count;
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
				$offset += (max($page, 1) - 1) * $this->perPage;
				$limit = is_null($limit)?$this->perPage:min($limit - $offset, $this->perPage);

				// Add pagination menu
				$objPagination = new Pagination($total, $this->perPage);
				$this->Template->pagination = $objPagination->generate("\n  ");
			}

			$arrQuery = $this->processFieldSQL($this->catalog_visible);
			if($this->strAliasField)
				$arrQuery[] = $this->strAliasField;
			// Run Query
			$objCatalogStmt = $this->Database->prepare("SELECT ".implode(',',$this->systemColumns).",".implode(',',$arrQuery).", (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS catalog_name, (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=".$this->strTable.".pid) AS parentJumpTo FROM ".$this->strTable." WHERE pid=?".$strWhere.(strlen($strOrder) ? " ORDER BY ".$strOrder : "")); 


			// add filter and order later

			// Limit result
			if ($limit)
			{
				$objCatalogStmt->limit($limit, $offset);
			}
			$objCatalog = $objCatalogStmt->execute($params);

			if (!$limit)
				$total = $objCatalog->numRows;
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
			$objTemplate->condition = sprintf($GLOBALS['TL_LANG']['MSC']['catalogCondition'], implode(', ',$labels));
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