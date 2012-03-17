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
 * Base class for the Catalog frontend Modules
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Controller
 */
abstract class ModuleCatalog extends Module
{
	/**
	 * Search String
	 * @var string
	 */
	const PARAMSEARCH = 'search';

	/**
	 * Sort String
	 * @var string
	 */
	const PARAMSORT = 'sort';

	/**
	 * OrderBy String
	 * @var string
	 */
	const PARAMORDERBY	= 'orderby';

	/**
	 * Columns which are needed for the catalog to work and have to be used
	 * in addition to the configured fields
	 * @var array
	 */
	protected $systemColumns = array('id', 'pid', 'sorting', 'tstamp');
	
	/**
	 * @var string
	 */
	protected	$cacheJumpTo;

	/**
	 * The catalog which's items should be worked on
	 * @var Database_Result
	 */
	protected $objCatalogType;
	
	/**
	 * (non-PHPdoc)
	 * @see Module::__get()
	 */
	public function __get($strKey)
	{
		switch ($strKey) {
			case 'strTable':
				return $this->objCatalogType->tableName;
				break;
				
			case 'strAliasField':
				return $this->objCatalogType->aliasField;
				break;
				
			case 'strPublishField':
			case 'publishField':
				return $this->objCatalogType->publishField;
				break;
				
			default:
				return parent::__get($strKey);
		}
	}

	/**
	 * @post $this->objCatalogType isset
	 * (non-PHPdoc)
	 * @see Module::generate()
	 */
	public function generate()
	{
		if (! strlen($this->catalog))
		{
			return '';
		}

		// fallback tags mode
		if (! strlen($this->catalog_tags_mode))
		{
			$this->catalog_tags_mode = 'AND';
		}

		$this->objCatalogType = $this->getValidCatalogType($this->catalog);

		// get DCA
		if ($this->objCatalogType)
		{
			$table = $this->objCatalogType->tableName;
			
			// dynamically load dca for catalog operations
			$this->Import('Catalog');
			if (!$GLOBALS['TL_DCA'][$table]['Cataloggenerated'])
			{
				// load language files and DC.
				$this->loadLanguageFile($table);
				$this->loadDataContainer($table);

				// load default language
				if (is_array($GLOBALS['TL_LANG'][$table]))
				{
					$GLOBALS['TL_LANG'][$table] =
						Catalog::array_replace_recursive($GLOBALS['TL_LANG']['tl_catalog_items'],
														$GLOBALS['TL_LANG'][$table]);
				}
				else
				{
					$GLOBALS['TL_LANG'][$table] = $GLOBALS['TL_LANG']['tl_catalog_items'];
				}

				// load dca
				if (is_array($GLOBALS['TL_DCA'][$table]))
				{
					$GLOBALS['TL_DCA'][$table] =
						Catalog::array_replace_recursive($this->Catalog->getCatalogDca($this->catalog),
														$GLOBALS['TL_DCA'][$table]);
				}
				else
				{
					$GLOBALS['TL_DCA'][$table] = $this->Catalog->getCatalogDca($this->catalog);
				}

				$GLOBALS['TL_DCA'][$table]['Cataloggenerated'] = true;
			}
	
			// Send file to the browser (reading Modules only)
			$blnDownload = ($this instanceof ModuleCatalogList
							|| $this instanceof ModuleCatalogFeatured
							|| $this instanceof ModuleCatalogRelated
							|| $this instanceof ModuleCatalogReference
							|| $this instanceof ModuleCatalogReader);
	
			if ($blnDownload && strlen($this->Input->get('file')) && $this->catalog_visible)
			{
				foreach ($this->catalog_visible as $k)
				{
					$fieldConf = &$GLOBALS['TL_DCA'][$table]['fields'][$k];
					if ($fieldConf['eval']['catalog']['type'] == 'file' && !$fieldConf['eval']['catalog']['showImage'])
					{
						// check file in Catalog
						$objDownload = $this->Database->prepare('SELECT id FROM ' . $table .
																' WHERE ' . (!BE_USER_LOGGED_IN && $this->publishField ? $this->publishField.'=1 AND ' : '') .
																'(LOCATE(?,'.$k.')>0 OR LOCATE(?,'.$k.')>0)')
								->limit(1)
								->execute($this->Input->get('file'), dirname($this->Input->get('file')));
						
						if ($objDownload->numRows)
						{
							$this->sendFileToBrowser($this->Input->get('file'));
						}
					}
				}
			}
		}

		return parent::generate();
	}

	/**
	 * Gets all modules from the layout and from the articles for this page
	 * @return array : enumerated int ids of the modules
	 */
	protected function getModulesForThisPage()
	{
		global $objPage;
		if($this->cachePageModules)
			return $this->cachePageModules;

		$objLayout = $this->fetchLayout();

		// check if there is a layout and fetch modules if so.
		if ($objLayout->numRows)
		{
			$arrModules = deserialize($objLayout->modules);
		} else {
			$arrModules = array();
		}

		// fetch all content element modules from this page.
		$objContent = $this->Database->prepare('SELECT module FROM tl_content WHERE pid IN (SELECT id FROM tl_article WHERE pid=?) AND type="module"')
									->execute($objPage->id);
		while($objContent->next())
		{
			$arrModules[] = array('mod' => $objContent->module);

		}

		$ids=array();
		foreach ($arrModules as $arrModule)
		{
			$ids[] = $arrModule['mod'];
		}
		$this->cachePageModules=$ids;
		return $this->cachePageModules;
	}

	/**
	 * Get either the set or the fallback layout
	 * @return Database_Result with id, modules
	 */
	protected function fetchLayout() {
		global $objPage;

		if($objPage->layout)
			$objLayout = $this->Database->prepare('SELECT id,modules FROM tl_layout WHERE id=?')
								->limit(1)
								->execute($objPage->layout);

		// Fallback layout
		if (!$objPage->layout || $objLayout->numRows == 0)
		{
			$objLayout = $this->Database->prepare('SELECT id, modules FROM tl_layout WHERE fallback=?')
										->limit(1)
										->execute(1);
		}

		return $objLayout;
	}

	/**
	 * Gets the catalog's fields' labels and types from db
	 * @param array $arrTypes optional
	 * @return array with 'label' and 'type'
	 */
	protected function getCatalogFields(array $arrTypes =array())
	{
		// fall back to all types
		if (count($arrTypes) == 0)
			$types = $GLOBALS['BE_MOD']['content']['catalog']['typesCatalogFields'];
		
		else
			$types = $arrTypes;

		$fields = array();
		
		$objFields = $this->Database->prepare(sprintf("SELECT * FROM tl_catalog_fields WHERE pid=? AND type IN ('%s') ORDER BY sorting",
																									implode("', '", $types)))
									->execute($this->catalog);
		
		while ($objFields->next())
		{
			$fields[$objFields->colName] = array (
				'label' => $objFields->name,
				'type' => $objFields->type,
			);
		}
		
		return $fields;
	}

	/**
	 * Builds the tree of catalog filters
	 * (children are offered when a parent got a value)
	 * @return array : string fieldname
	 */
	protected function getTree()
	{
		$result = array();

		if ($this->type == 'catalogfilter'
			&& $this->catalog_filter_enable
			&& $this->catalog_filters)
		{
			$arrFilters = deserialize($this->catalog_filters, true);

			foreach ($arrFilters as $fieldconfig)
			{
				list($field, $config) = each($fieldconfig);

				if ($config['checkbox'] == 'tree')
				{
					$result[] = $field;
				}
			}
		}

		return $result;
	}

	/**
	 * Sets the values of all filters according to either POST or GET values
	 * @param string $strSearchFields optional
	 * @return array ('procedure' => array('where'  => array(string fieldname => string query),
	 *                                     'tags'   => array(string fieldname => string query),
	 *                                     'search' => array(string fieldname => string query),
	 *                                     'tree'   => array(string fieldname => string query)),
	 *                'params' => array('where'  => array(string fieldname => array params),
	 *                                  'tags'   => array(string fieldname => array params),
	 *                                  'search' => array(string fieldname => array params),
	 *                                  'tree'   => array(string fieldname => array params)),
	 */
	public function parseFilterUrl($strSearchFields =null)
	{
		$arrTree = $this->getTree();
		$blnTree = (count($arrTree)>0);

		$current = $this->convertAliasInput();
		$searchFields = deserialize($strSearchFields, true);

		// Setup Fields
		$fields = $this->getCatalogFields();

		// Process POST redirect() settings
		$doPost = false;
		if ($this->Input->post('FORM_SUBMIT') == $this->strTable)
		{
			// search string POST
			if (array_key_exists(self::PARAMSEARCH, $_POST))
			{
				$doPost = true;

				if ($this->Input->post(self::PARAMSEARCH))
				{
					$current[self::PARAMSEARCH] = $this->Input->post(self::PARAMSEARCH);
				}
				else
				{
					unset($current[self::PARAMSEARCH]);
				}
			}

			// filters POST
			foreach ($fields as $field=>$data)
			{
				// check if this is a filter
				if (array_key_exists($field, $_POST)
					&& in_array($field, $searchFields))
				{
					$doPost = true;
					$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];

					// check if array posted (range and dates)
					if (is_array($this->Input->post($field)))
					{
						$range = $this->Input->post($field);
						sort($range);
						
						// restrict field values to their configured limits
						
						if (strlen($fieldConf['eval']['catalog']['minValue'])) 
						  $range[0] = max($range[0], $fieldConf['eval']['catalog']['minValue']);
						
						if (strlen($fieldConf['eval']['catalog']['maxValue'])) 
							$range[1] = min($range[1], $fieldConf['eval']['catalog']['maxValue']);
						
						// special sorts
						if ($fieldConf['eval']['catalog']['type'] == 'date')
						{
						  $date0 = new Date($range[0]);
						  $date1 = new Date($range[1]);
						  
						  if ($date0->timestamp > $date1->timestamp)
						    $range = array_reverse($range);
						}
						
						// either redirect to a range or to a filter
						if (strlen($range[0]) && strlen($range[1]))
						  $current[$field] = $range[0] . '__' . $range[1];
						
						else
						  $current[$field] = $range[0] . $range[1];
					}
					// regular filter value
					else
					{
						// use TL safe function
						$v = $this->Input->post($field);
						$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
						if (strlen($v) && $this->getAliasFieldConf($fieldConf) != 'id')
						{
							$arrAlias = array_flip($this->getAliasOptionList($fieldConf));
							switch ($fieldConf['eval']['catalog']['type'])
							{
								case 'tags':
									$tags = explode(',', $v);
									$newtags = array();
									foreach($tags as $tag)
									{
										$newtags[] = $arrAlias[$tag];
									}
									$v = implode(',', $newtags);
									break;

								case 'select':
									$v = $arrAlias[$v];
									break;
							}
						}
						$current[$field] = $v;
					}
				}
			}
			
			// Redirect POST variables to GET, for [search] and [ranges]
			if ($doPost)
			{
				$this->redirect($this->generateFilterUrl($current, false, false));
			}
		}

		// GET from here on

		$procedure = array('search' => array(), 'where' => array(), 'tags' => array(), 'tree' => array());
		$values = array('search' => array(), 'where' => array(), 'tags' => array(), 'tree' => array());
		
		// return if no filter parameters in URL
		if (! count($current))
		{
			return array('procedure' => $procedure, 'values' => $values, 'current' => $current);
		}
		
		// Setup Variables, procedures and values are stored per field
		$baseurl = $this->generateFilterUrl();
		$searchPhrases = self::splitSearchKeywords($this->Input->get(self::PARAMSEARCH));

		// GET search value if several phrases are used
		// all search fields need to be used to find search phrases which are
		// contained in different fields
		if (count($searchPhrases) > 1
			&& count($searchFields))
		{
			$searchFieldNames = array();

			foreach ($searchFields as $fieldName)
			{
				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$fieldName];
				// deleted field but still mentioned in module configuration?
				if(!$fieldConf)
					continue;
				
				$sqlFieldName = self::sqlFieldName($fieldName, $fieldConf['eval']['catalog']);
				
				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'date':
						// month only search
						$sqlFieldName = 'CAST(MONTHNAME(FROM_UNIXTIME(' . $fieldName .')) AS CHAR)';
						break;

					case 'select' :
						list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
						$sqlFieldName = '(SELECT '.$valueCol.' FROM ' .$itemTable . ' WHERE id='.$fieldName . ')';
						break;
					// TODO: add support for other fieldtypes here (tags, ...)
				}
				
				$searchFieldNames[] = $sqlFieldName;
			}

			$searchFieldName = 'CONCAT(' . implode($searchFieldNames, ',') . ') LIKE ? AND ';
			$procedure['search'][$searchFields[0]] = str_repeat($searchFieldName, count($searchPhrases)) . '1'; // terminate the last AND
			$values['search'][$searchFields[0]] = self::searchFor($searchPhrases);
		}
		foreach ($fields as $field=>$data)
		{
			$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
			$sqlFieldName = self::sqlFieldName($field, $fieldConf['eval']['catalog']);

			// GET search value
			if (count($searchPhrases) == 1
				&& strlen($fieldConf['eval']['catalog']['type'])
				&& in_array($field, $searchFields))
			{
				// remember: only one search phrase here
				$searchPhrase = $searchPhrases[0];

				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'text':
					case 'longtext':
					case 'number':
					case 'decimal':
					case 'file':
					case 'url':
					case 'calc':
						$procedure['search'][$field] = '(' . $sqlFieldName . ' LIKE ?)';
						$values['search'][$field][] = '%' . $searchPhrase . '%';
						break;

					case 'date':
						// add numeric day, month, year search
						if (is_numeric($searchPhrase))
						{
							foreach (array('YEAR','MONTH','DAY') as $function)
							{
								$tmpDate[] = "CAST(" . $function . "(FROM_UNIXTIME(" . $field . ")) AS CHAR) LIKE ?";
								$values['search'][$field][] = '%' . $searchPhrase . '%';
							}

							$procedure['search'][$field] = '('.implode(' OR ',$tmpDate).')';
						}
						// add month only search
						else
						{
							$procedure['search'][$field] = "CAST(MONTHNAME(FROM_UNIXTIME(" . $field . ")) AS CHAR) LIKE ?";
							$values['search'][$field][] = '%'.$searchPhrase.'%';
						}
						break;

					case 'checkbox' :
						// search only if true
						if (substr_count(strtolower($fieldConf['label']['0']),
										strtolower($this->Input->get(self::PARAMSEARCH))))
						{
							$procedure['search'][$field] = '('.$field.'=?)';
							$values['search'][$field][] = '1';
						}
						break;

					case 'select' :
						list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
						$procedure['search'][$field] = '('.$field.' IN (SELECT id FROM '.$itemTable.' WHERE '.$valueCol.' LIKE ?'.($fieldConf['options']? ' AND id IN ('.implode(',',array_keys($fieldConf['options'])).')':'').'))';
						$values['search'][$field][] = '%' . $searchPhrase . '%';
						break;

					case 'tags' :
						list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
						// perform search by using a subselect over the tags table
						$tagQuery = $this->Database->prepare(sprintf('SELECT DISTINCT(itemid) as id
																		FROM tl_catalog_tag_rel
																		WHERE fieldid=%u
																		AND valueid IN (SELECT id
																						FROM %s
																						WHERE %s LIKE ?
																						AND id IN (%s))',
																	$fieldConf['eval']['catalog']['fieldId'],
																	$itemTable,
																	$valueCol,
																	implode(',',array_keys($fieldConf['options']))))
													->execute('%' . $searchPhrase . '%');
						if ($tagQuery->numRows)
						{
							$procedure['search'][$field] = sprintf('id IN(%s)',
																	implode(',', $tagQuery->fetchEach('id')));
						}
						break;

					default:
						// HOOK: Might be a custom field type, check if that one has registered a hook
						$hookQuery = $this->generateFilterHook($field, $fieldConf, $searchPhrase);
						if($hookQuery['procedure'])
							$procedure['search'][$field] = $hookQuery['procedure'];
						if($hookQuery['values'])
							$values['search'][$field] = array_merge($values['search'][$field], $hookQuery['values']);
				}
			} // /search

			// GET range values
			if (substr_count($this->Input->get($field),'__'))
			{
				$rangeValues = trimsplit('__', $this->Input->get($field), 2);
				$minValue =	$rangeValues[0];
				$maxValue = $rangeValues[1];
								
				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'number':
						$minValue = intval($rangeValues[0]);
						$maxValue = intval($rangeValues[1]);
						break;

					case 'decimal':
						$minValue = floatval($rangeValues[0]);
						$maxValue = floatval($rangeValues[1]);
						break;

					case 'date':
					  $minDate = new Date($rangeValues[0]);
					  $maxDate = new Date($rangeValues[1]);
					  
						$minValue = $minDate->timestamp;
						$maxValue = $maxDate->timestamp;
						break;

					case 'calc':
					  if ($fieldConf['eval']['catalog']['formatFunction'] == 'date')
					  {
  					  $minDate = new Date($rangeValues[0]);
  					  $maxDate = new Date($rangeValues[1]);
  					  
  						$minValue = $minDate->timestamp;
  						$maxValue = $maxDate->timestamp;
					  }
					  
					  break;
				}

				// one or two limits?
				
				$strSqlWhereClause = '(' . $sqlFieldName . ' BETWEEN ? AND ?)';

				if (strlen($minValue))
					$values['where'][$field][] = $minValue;

				else
					$strSqlWhereClause = '(' . $sqlFieldName . ' < ?)';

				if (strlen($maxValue))
					$values['where'][$field][] = $maxValue;

				else
					$strSqlWhereClause = '(' . $sqlFieldName . ' > ?)';

				$procedure['where'][$field] = $strSqlWhereClause;

				// use the __ notation again
				$current[$field] = $this->Input->get($field);
			}

			// GET filter values
			elseif (strlen($this->Input->get($field)))
			{
				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'tags':
						// tags are comma separated in the URL
						$tags = explode(',', $current[$field]);

						// some former valid values might have been deleted
						if (count($fieldConf['options']))
							$tags = array_intersect($tags, array_keys($fieldConf['options']));

						$tagsInt = array();
						//  make sure they are no arbitrary data.
						foreach ($tags as $tag)
							$tagsInt[] = (int)$tag;

						$tagQuery = $this->buildTagsQuery($fieldConf, $tagsInt);

						$procedure['where'][$field] = $procedure['tags'][$field] = $tagQuery;

						if ($blnTree && in_array($field, $arrTree))
						{
							$procedure['tree'][$field] = $tagQuery;
						}
						break;

					case 'checkbox':
						$procedure['where'][$field] = $field."=?";
						$values['where'][$field][] = ($this->Input->get($field) == 'true' ? 1 : 0);

						if ($blnTree && in_array($field, $arrTree))
						{
							$procedure['tree'][$field] = $field . "=?";
							$values['tree'][$field][] = ($this->Input->get($field) == 'true' ? 1 : 0);
						}
						break;

					case 'text':
					case 'longtext':
					case 'number':
					case 'decimal':
					case 'select':
						$value = $current[$field];

						if ($value !== null)
						{
							$procedure['where'][$field] = $field."=?";
							$values['where'][$field][] = $value;
							if ($blnTree && in_array($field, $arrTree))
							{
								$procedure['tree'][$field] = $field."=?";
								$values['tree'][$field][] = $value;
							}
						}
						break;

					case 'date':
						$procedure['where'][$field] = $field . "=?";
						$value = new Date($this->Input->get($field));
						$values['where'][$field][] = $value->timestamp;

						if ($blnTree && in_array($field, $arrTree)) 
						{
							$procedure['tree'][$field] = $field . "=?";
							$values['tree'][$field][] = $value->timestamp;
						}
						break;

					case 'file':
					case 'url':
						$procedure['where'][$field] = $field . "=?";
						$values['where'][$field][] = urldecode($this->Input->get($field));
						$current[$field] = $this->Input->get($field);

						if ($blnTree && in_array($field, $arrTree))
						{
							$procedure['tree'][$field] = $field . "=?";
							$values['tree'][$field][] = urldecode($this->Input->get($field));
						}
						break;

					default: // f.e. calc
						if ($fieldConf['eval']['catalog']['formatFunction'] == 'date')
						{
							$dayBegin = strtotime($this->Input->get($field));
							$dayEnd = $dayBegin + 24*60*60 -1;
							$procedure['where'][$field] = $sqlFieldName . ' BETWEEN ' . $dayBegin . ' AND ' . $dayEnd; 
						}

						else
						{
							$procedure['where'][$field] = $sqlFieldName ."=?";
							$values['where'][$field][] = $this->Input->get($field);
						}

						$current[$field] = $this->Input->get($field);
  
						if ($blnTree && in_array($field, $arrTree))
						{
							$procedure['tree'][$field] = $sqlFieldName ."=?";
							$values['tree'][$field][] = $this->Input->get($field);
						}
						break;
				}
			} // /filter

			// GET sort values
			if ($this->Input->get(self::PARAMORDERBY) == $field && in_array($this->Input->get(self::PARAMSORT), array('asc','desc')))
			{
				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'select':
						list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
						$procedure['orderby'] = '(SELECT '.$valueCol.' from '.$itemTable.' WHERE id='.$field.') '.$this->Input->get(self::PARAMSORT);
						break;
					default:
						$procedure['orderby'] = $field.' '.$this->Input->get(self::PARAMSORT);
				}
				$current[self::PARAMORDERBY] = $this->Input->get(self::PARAMORDERBY);
				$current[self::PARAMSORT] = $this->Input->get(self::PARAMSORT);
			} //sort

		} // foreach $filter

		$arrQuery = array
			(
				'current' 	=> $current,
				'procedure' => $procedure,
				'values' 		=> $values,
			);

		// HOOK: allow other extensions to manipulate the filter settings before passing it to the template
		$arrQuery = $this->filterCatalogHook($arrQuery);

		return $arrQuery;
	}

	/**
	 * generateFilter HOOK for searching custom fields
	 * @param string $strFieldName
	 * @param array $arrFieldConf
	 * @param string $strSearchPhrase
	 * @return array : 'procedure' => string, 'values' => array
	 */
	protected function generateFilterHook($strFieldName, array $arrFieldConf, $strSearchPhrase)
	{
		$searchProcedures = array();
		$searchValues = array();

		$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$arrFieldConf['eval']['catalog']['type']];

		if(array_key_exists('generateFilter', $fieldType)
			&& is_array($fieldType['generateFilter']))
		{
			foreach ($fieldType['generateFilter'] as $callback)
			{
				$this->import($callback[0]);
				$hookResult = $this->$callback[0]->$callback[1]($strFieldName, $arrFieldConf, $strSearchPhrase);
				$searchProcedures = $hookResult['procedure'];
				if(array_key_exists('search', $hookResult))
				{
					if (is_array($hookResult['search']))
						$searchValues = array_merge($hookResult['search'], $searchValues);
					else
						$searchValues[] = $hookResult['search'];
				}
			}
		}

		return array
		(
			'procedure' => implode(' AND ', $searchProcedures),
			'values' => $searchValues
		);
	}
		/**
	 * Recursively build the query to filter alle items
	 * to just those which match the tags
	 * @param array $arrFieldConf
	 * @param array $arrTags as int or string
	 * @return string part for SELECT query on the items table
	 */
	protected function buildTagsQuery(array $arrFieldConf, array $arrTags)
	{
		$result = '';
		$fieldId = $arrFieldConf['eval']['catalog']['fieldId'];

		// TODO: add support for string values here and get rid of the convertAliasInput call on the beginning.

		$strAllTags = implode(',', $arrTags);

		if (count($arrTags) == 0)
			$result = 'false';

		else
		{
			// recursivly builds subselects for each tag
			if($this->catalog_tags_mode == 'AND')
			{
				$subSql = sprintf('SELECT itemid FROM tl_catalog_tag_rel WHERE fieldid=%u AND valueid=%u',
								$fieldId,
								array_shift($arrTags));

				foreach($arrTags as $tag)
				{
					// recursion
					$subSql = sprintf('SELECT itemid FROM tl_catalog_tag_rel WHERE fieldid=%u AND valueid=%u AND itemid IN(%s)',
									$fieldId,
									$tag,
									$subSql);
				}
				$result = sprintf('id IN (SELECT DISTINCT(itemid) FROM tl_catalog_tag_rel WHERE fieldid=%u AND valueid IN (%s)) AND id IN (%s)',
								$fieldId,
								$strAllTags,
								$subSql);
			} else {
				// perform search by using a subselect over the tables.
				$result = sprintf('id IN(SELECT DISTINCT(itemid) as id FROM tl_catalog_tag_rel WHERE fieldid=%u AND valueid IN (%s))',
								$fieldId,
								$strAllTags);
			}
		}

		return $result;
	}
	
	/**
	 * Return the filter configuration from the listers on this very page
	 * for ModuleCatalogFilter and descendants only (checks via instanceof)!
	 * @return array ('query' => string query, 'params' => array(mixed values))
	 */
	public function getFilterFromListersOnSamePage()
	{
	  $queries = array();
	  $params = array();

		if($this instanceof ModuleCatalogFilter
			&& $this->catalog_filter_cond_from_lister)
		{
			$ids = $this->getModulesForThisPage();
			$objModules = $this->Database->prepare('SELECT catalog_search, catalog_where, catalog_query_mode, catalog_tags_mode
			                                        FROM tl_module
			                                        WHERE id IN (' . implode(', ', $ids) . ')
			                                          AND deny_catalog_filter_cond_from_lister=0
			                                          AND type=\'cataloglist\'
			                                          AND catalog='.$this->catalog)
					->execute();

			while($objModules->next())
			{
				$objModules->catalog_search = deserialize($objModules->catalog_search);
				$moduleFilterUrl = $this->parseFilterUrl($objModules->catalog_search);

				// search
				if (count($moduleFilterUrl['procedure']['search']))
				{
					$searchProcedures = array();
					$searchValues = array();

					foreach($objModules->catalog_search as $searchfield)
					{
						if (array_key_exists($searchfield, $moduleFilterUrl['procedure']['search']))
						{
							$searchProcedures[] = $moduleFilterUrl['procedure']['search'][$searchfield];
							$searchValues = array_merge($searchValues, $moduleFilterUrl['values']['search'][$searchfield]);
						}
					}

					// mix search into where
					if (count($searchProcedures))
					{
						$queries[] = ' (' . implode(' OR ', $searchProcedures) .')';
						$params = array_merge($params, $searchValues);
					}
				}

				// condition
				if ($objModules->catalog_where)
				{
					$strCondition = $this->replaceInsertTags($objModules->catalog_where);

					if (strlen($strCondition))
						$queries[] = $strCondition;
				}

				// where
				if (count($moduleFilterUrl['procedure']['where']))
				{
					$queries[] = implode(' '.$objModules->catalog_query_mode.' ',
					                     $moduleFilterUrl['procedure']['where']);
  
  				if (count($moduleFilterUrl['values']['where'])) {
  					$params = array_merge($params, self::flatParams($moduleFilterUrl['values']['where']));
  				}
				}
				
				// tags
				if (count($moduleFilterUrl['procedure']['tags']))
				{
					$queries[] = implode(' '.$objModules->catalog_tags_mode.' ',
					                     $moduleFilterUrl['procedure']['tags']);
				
					if (count($moduleFilterUrl['values']['tags']))
					{
						$params = array_merge($params, self::flatParams($moduleFilterUrl['values']['tags']));
					}
				}
			}
		}
		
		return array
		(
			'query' => implode(' AND ', $queries),
			'params' => $params
		);
	}
	
	/**
	 * Takes out one dimension from a filterUrl params array 
	 * @param array $arrParams (string fieldname => array values)
	 * @return array values for all fields
	 */
	protected static function flatParams(array $arrParams)
	{
		$params = array();

		foreach ($arrParams as $field => $values)
		{
			$params = array_merge($params, $values);
		}

		return $params;
	}

	/**
	 * Splits the keywords used for the search query
	 * One can use "word1 word2" for a phrase
	 * @see Search::searchFor()
	 * @param string $strKeywords
	 * @return array with the single string phrases to search for
	 */
	protected static function splitSearchKeywords($strKeywords)
	{
		$result = array();
		$arrChunks = array();

		preg_match_all('/"[^"]+"|[^ ]+/', $strKeywords, $arrChunks);

		foreach ($arrChunks[0] as $phrase)
		{
			switch (substr($phrase, 0, 1))
			{
				case '"':
					$result[] = trim(substr($phrase, 1, -1));
					break;

				default:
					$result[] = $phrase;
			}
		}

		return $result;
	}

	/**
	 * Prepares several search phrases for usage in the LIKE statement
	 * @param array $arrPhrases
	 * @return array string $searchValue with wildcards added
	 */
	protected static function searchFor(array $arrPhrases)
	{
		$result = array();

		foreach ($arrPhrases as $phrase)
		{
			$result[] = '%' . $phrase . '%';
		}

		return $result;
	}

	/**
	 * HOOK: allow other extensions to manipulate the filter settings before passing it to the template
	 * @param array $arrQuery
	 * @return array $arrQuery wicth changes from the hooks
	 */
	protected function filterCatalogHook(array $arrQuery)
	{
		$result = $arrQuery;
		if(is_array($GLOBALS['TL_HOOKS']['filterCatalog']))
		{
			foreach ($GLOBALS['TL_HOOKS']['filterCatalog'] as $callback)
			{
				$this->import($callback[0]);
				$result = $this->$callback[0]->$callback[1]($result);
			}
		}

		return $result;
	}

	/**
	 * Retrieve Alias field from table, checks if the desired table is a catalog
	 * @param string
	 * @return string
	 */
	public function getAliasField($sourceTable)
	{
		if($GLOBALS['TL_CONFIG']['disableAlias'])
		{
			return 'id';
		}
		// check alias field
		$objAlias = $this->Database->prepare("SELECT aliasField FROM tl_catalog_types WHERE tableName=?")
										->execute($sourceTable);
		$aliasField = ($objAlias->numRows && strlen($objAlias->aliasField)) ? $objAlias->aliasField : 'alias';

		return ($this->Database->fieldExists($aliasField, $sourceTable)) ? $aliasField : 'id';
	}

	/**
	 * Retrieve alias field for current catalog field configuration
	 * @param array $arrFieldConf
	 * @return string name of the alias field
	 */
	private function getAliasFieldConf(array $arrFieldConf)
	{
		if ((!$arrFieldConf['eval']['catalog']['foreignKey']) || $GLOBALS['TL_CONFIG']['disableAlias'])
		{
			return 'id';
		}

		// get alias column
		list($itemTable, $valueCol) = explode('.', $arrFieldConf['eval']['catalog']['foreignKey']);

		return $this->getAliasField($itemTable);
	}

	/**
	 * Retrieve alias values with id as index
	 * @param array $fieldConf
	 * @return array id => alias
	 * @post $fieldConf['optionlist'] is populated with the return value
	 */
	private function getAliasOptionList(array &$fieldConf)
	{
		if($fieldConf['optionslist'])
			return $fieldConf['optionslist'];

		// get alias column
		list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
		$aliasField = $this->getAliasField($itemTable);

		// determine item sorting.
		$strSorting=($fieldConf['eval']['catalog']['sortCol']
					?' ORDER BY '.$fieldConf['eval']['catalog']['sortCol']
					:($this->Database->fieldExists('sorting', $itemTable)?' ORDER BY sorting':''));
		// get existing alias values of options in DB
		$objList = $this->Database->prepare('SELECT id,'.$aliasField.' FROM '.$itemTable .
						($fieldConf['options'] ? ' WHERE id IN ('.implode(',',array_keys($fieldConf['options'])).')':'') . $strSorting)
				->execute();

		$return = array();
		while ($objList->next())
		{
			// check if this is still ok to use id if alias is empty
			$return[$objList->id] = strlen($objList->$aliasField) ? $objList->$aliasField : $objList->id;
		}
		$fieldConf['optionslist'] = $return;
		return $return;
	}

	/**
	 * Detect input $_GET variables and convert alias values to id's, if table supports it
	 * @return array the currently active filters with their filter values
	 * @post $_GET some alias values are replaced by their respective IDs
	 */
	protected function convertAliasInput()
	{
		$return = array();

		// convert $_GET filter parameters in Url
		foreach ($_GET as $k=>$v)
		{
			// exclude special parameters
			if (! in_array($k, array('page')))
			{
				$_GET[$k] = str_replace($GLOBALS['TL_CONFIG']['catalog']['safeReplace'],
										$GLOBALS['TL_CONFIG']['catalog']['safeCheck'],
										$v);

				// use TL safe function
				$v = $this->Input->get($k);

				if (! in_array($k, array(self::PARAMSEARCH, self::PARAMSORT, self::PARAMORDERBY)))
				{
					$fieldConf = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];

					if (strlen($v) && $fieldConf && $this->getAliasFieldConf($fieldConf) != 'id')
					{
						$arrAlias = array_flip($this->getAliasOptionList($fieldConf));
						switch ($fieldConf['eval']['catalog']['type'])
						{
							case 'tags':
								$tags = explode(',', $v);
								$newtags = array();
								foreach($tags as $tag)
								{
									$newtags[] = $arrAlias[$tag];
								}
								$v = implode(',', $newtags);
								break;

							case 'select':
								$v = $arrAlias[$v];
								break;
						}
					}
				}

				$return[$k] = $v;
			}
		}

		return $return;
	}

	/**
	 * @param string $field
	 * @param array $current
	 * @param array $tree
	 * @return boolean is the ield the last one in the $tree?
	 */
	protected function lastInTree($field, array $current, array $tree)
	{
		return (!count($tree)
				|| (in_array($field, $tree)
					&& array_search($field, $tree) == (count($tree)-1)));
	}

	protected function hideTree($field, array $current, array $tree)
	{
		if ($this->catalog_filter_hide && in_array($field, $tree))
		{
			$pos = array_search($field, $tree);
			for ($i=0,$total=0;$i<=$pos;$i++)
			{
				$total += array_key_exists($tree[$i], $current);
			}
			return ($total<$pos);
		}
		return false;
	}

	/**
	 * @param string $strField
	 * @param array $arrFilterUrl
	 * @param array $arrTree
	 * @return array ('query' => string, 'params' => array)
	 */
	protected static function buildTreeQuery($strField, array $arrFilterUrl, array $arrTree)
	{
		$params = array();
		$queries = array();
		
		// tree
		if (count($arrTree)
				&& array_key_exists('tree', $arrFilterUrl['procedure'])
				&& count($arrFilterUrl['procedure']['tree']))
		{
			$pos = array_search($strField, $arrTree);

			if ($pos !== false)
			{
				for ($i=0; $i < $pos; $i++)
				{
					$fieldName = $arrTree[$i];

					if (strlen($arrFilterUrl['procedure']['tree'][$fieldName]))
						$queries[] = $arrFilterUrl['procedure']['tree'][$fieldName];
          
  				if (strlen($arrFilterUrl['values']['tree'][$fieldName]))
  					$params = array_merge($params, $arrFilterUrl['values']['tree'][$fieldName]);
  			}
			}
		}

		return array (
			'query' => implode(' AND ', $queries),
			'params' => $params
		);
	}

	/**
	 * Combines all queries from $arrBasicQuery, $arrFilterUrl, filtered to $arrTree
	 * @param string $strField
	 * @param array $arrFilterUrl
	 * @param array $arrTree
	 * @param array $arrBasicQuery
	 * @return array ('query' => string, 'params' => array)
	 */
	protected function buildQuery($strField, array $arrFilterUrl, array $arrTree, array $arrBasicQuery)
	{
		$queries = array();
		$params = array();

		$treeQuery = self::buildTreeQuery($strField, $arrFilterUrl, $arrTree);
		if (count($treeQuery['query']))
		{
			$queries = $treeQuery['query'];
			$params = $treeQuery['params'];
		}

		if (count($arrBasicQuery['query']))
		{
			$queries[] = $arrBasicQuery['query'];
			$params = array_merge($params, $arrBasicQuery['params']);
		}

		// combine together all query params we are using
		// TODO: take the tree into account here
		if (count($arrFilterUrl['procedure']['where']))
		{
			foreach($arrFilterUrl['procedure']['where'] as $field => $where)
			{
				// ignore conditions generated by the field or which use this field
				// would be nice to filter tags in OR mode, but this mode is selected
				// in the lister module
				if ($field != $strField && ! self::fieldInSql($where, $strField))
				{
					$queries[] = $where;

					if (count($arrFilterUrl['values']['where'][$field]))
					{
						$params = array_merge($params, $arrFilterUrl['values']['where'][$field]);
					}
				}
			}
		}

		return array (
			'query' => implode(' AND ', $queries),
			'params' => $params
		);
	}
	
	/**
	 * checks if a field is used in a sql statement.
	 * also works for empty sql statements
	 * @param string $strSql
	 * @param string $strFieldname
	 * @return boolean is the field used in the statement?
	 */
	protected static function fieldInSql($strSql, $strFieldname)
	{
	  return preg_match('/\b' . $strFieldname. '\b/i',
	                        $strSql) > 0;
	}

	/**
	 * Build query based on filters from the list module and publish field settings,
	 * field independent
	 * @return array ('query' => string, 'params' => array)
	 */
	protected function buildBasicQuery()
	{
		$params = array();
		$queries = array();

		// take into account settings from the lister module
		$queryFromLister = $this->getFilterFromListersOnSamePage();

		if (strlen($queryFromLister['query']))
			$queries[] = $queryFromLister['query'];

		if(count($queryFromLister['params']))
			$params = $queryFromLister['params'];

		// optionally restrict to published items
		if ((! BE_USER_LOGGED_IN) && $this->publishField)
		{
			if (array_key_exists($this->publishField, $query))
		  		$queries[] = $this->publishField . '=1';
		}

		return array (
			'query' => implode(' AND ', $queries),
			'params' => $params
		);
	}

	/**
	 * Deletes all queries from $current which come after the current field
	 * @param string $strField
	 * @param array $arrCurrent
	 * @param array $arrTree
	 * @return array $arrCurrent without irrelevant fields
	 */
	protected static function clearTree($strField, array $arrCurrent, array $arrTree)
	{
		$result = $arrCurrent;

		if (in_array($strField, $arrTree))
		{
			$pos = (array_search($strField, $arrTree)+1);
			
			if ($pos !== false)
			{
				for ($i=$pos; $i<=count($arrTree); $i++)
  				{
  					unset($result[$arrTree[$i]]);
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $inputType
	 * @param string $label of the input (eg. select group)
	 * @param boolean $multi
	 * @return string label for the ALL input
	 */
	protected function makeAllLabel($inputType, $label, $multi=false)
	{
		if ($inputType=='select' && !$multi)
		{
			return sprintf($GLOBALS['TL_LANG']['MSC']['selectNone'], $label);
		}

		return sprintf($GLOBALS['TL_LANG']['MSC']['clearAll'], $label);
	}

	/**
	 * @return array : 'url', 'action', 'widgets', 'filter', 'range', 'sort', 'search', 'date'
	 */
	protected function generateFilter()
	{
		$filterurl = $this->parseFilterUrl($this->catalog_search);
		$current = $filterurl['current'];
		$arrFilters = deserialize($this->catalog_filters, true);

		if ($this->catalog_filter_enable && count($arrFilters))
		{
			// Get Tree View
			$tree = $this->getTree();
			$basicQuery = $this->buildBasicQuery();

			// Setup filters and option values
			$filterOptions = array();
			foreach ($arrFilters as $fieldconfig)
			{
				list($field, $config) = each($fieldconfig);
				$input = $config['radio'];
				$blnTree = ($config['checkbox'] == 'tree');

				if ($input == 'none' || $this->hideTree($field, $current, $tree))
				{
					continue;
				}

				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
				$fieldType = $fieldConf['eval']['catalog']['type'];

				$blnLast = self::lastInTree($field, $current, $tree);
				$query = self::buildQuery($field, $filterurl, $tree, $basicQuery);

				// HOOK: let custom fields mimic another fieldtype to generate a filter
				$fieldType = $this->generateFilterWidgetHook($field, $fieldType,
															$fieldConf, $config,
															$filterurl, $query,
															$tree);

				$options = array();

				switch ($fieldType)
				{
					case 'checkbox':
						// Build Widget Options
						$labels = array
						(
							'none' 	=> $this->makeAllLabel($input, $fieldConf['label'][0]),
							'true' 	=> $GLOBALS['TL_LANG']['MSC']['true'],
							'false'	=> $GLOBALS['TL_LANG']['MSC']['false'],
						);

						foreach ($labels as $key=>$label)
						{
							$newcurrent = $current;
							$newcurrent[$field] = ($key == 'none' ? '' : $key);
							$selected = ($current[$field] == $newcurrent[$field]);
							$newcurrent = self::clearTree($field, $newcurrent, $tree);
							$url = $this->generateFilterUrl($newcurrent, true, $blnLast);

							$addOption = array();
							$addOption['value'] = $url;
							$addOption['label'] = $label;
							$addOption['id'] = ($key == 'none' ? '' : $key);
							if ($selected)
							{
								$addOption['selected'] = true;
							}
							$options[] = $addOption;

						}

						$widget = array
						(
							'name'		=> $field,
							'id'		=> 'filter_field_'.$field,
							'label'		=> $fieldConf['label'][0],
							'options'	=> serialize($options),
							'value' 	=> htmlentities($this->generateFilterUrl($current, true, $blnLast)),
							'tableless'	=> true,
							'inputType' => $input,
						);

						// parse Widget
						$settings['filter'][] = $this->parseWidget($widget, false);
						$widgets['filter'][] = $widget;

						break;

					case 'select':
						// get existing options in DB
						$rows = $this->fetchAvailableFilterOptions($field, $query['query'], $query['params']);

						if (count($rows))
						{
							$newcurrent = $current;
							unset($newcurrent[$field]);

							$newcurrent = self::clearTree($field, $newcurrent, $tree);
							$url = $this->generateFilterUrl($newcurrent, true, $blnLast);

							$addOption = array();
							$addOption['value'] = $url;
							$addOption['label'] = $this->makeAllLabel($input, $fieldConf['label'][0]);
							$addOption['id'] = '';

							if (! strlen($current[$field]))
							{
								$addOption['selected'] = true;
							}

							$options[] = $addOption;

							if($fieldConf['options'])
							{
								$arrResultCount = $this->fetchTagCounts($field, array_keys($fieldConf['options']), $query['query'], $query['params']);

								foreach ($fieldConf['options'] as $id => $option)
								{
									if (in_array($id, $rows))
									{
										$newcurrent = $current;
										$newcurrent[$field] = $id;
										$newcurrent = self::clearTree($field, $newcurrent, $tree);

										$addOption = array();
										$addOption['value'] = $this->generateFilterUrl($newcurrent, true, $blnLast);
										$addOption['label'] = $option;
										$addOption['id'] = $id;
										$addOption['alias'] = $id;
										$addOption['resultcount'] = $arrResultCount[$field . $id];

										if ($current[$field] == $id)
										{
											$addOption['selected'] = true;
										}

										$options[] = $addOption;
									}
								}
							}

							$widget = array
							(
								'name'		=> $field,
								'id'		=> 'filter_field_'.$field,
								'label'		=> $fieldConf['label'][0],
								'value' 	=> $this->generateFilterUrl($current, true, $blnLast),
								'options'	=> serialize($options),
								'tableless'	=> true,
								'inputType'	=> $input,
							);
								$settings['filter'][] =  $this->parseWidget($widget, false);
							$widgets['filter'][] = $widget;
						}
						break;
						case 'tags':
						$widget = $this->generateWidgetConfigTags($field, $fieldConf, $input, $current, $tree, $query['query'], $query['params'], $blnLast);

						$settings['filter'][] = $this->parseWidget($widget, false);
						$widgets['filter'][] = $widget;

						break;

					case 'text':
					case 'file':
					case 'url':
					case 'number':
					case 'decimal':
					case 'date':
						// get existing options in DB
						$rows = $this->fetchAvailableFilterOptions($field, $query['query'], $query['params'], true);
						if (count($rows))
						{
							$options = array();

							// setup ALL option
							$newcurrent = $current;
							unset($newcurrent[$field]);
							$newcurrent = self::clearTree($field, $newcurrent, $tree);
							$url = $this->generateFilterUrl($newcurrent, true, $blnLast);

							$addOption = array();
							$addOption['value'] = $url;
							$addOption['label'] = $this->makeAllLabel($input, $fieldConf['label'][0]);
							$addOption['id'] = '';
							if (!strlen($current[$field]))
							{
								$addOption['selected'] = true;
							}
							$options[] = $addOption;

							foreach ($rows as $fieldValue)
							{
								if (!strlen(trim($fieldValue)))
									continue;

								$label = $this->formatValue(0, $field, $fieldValue, false);
								switch ($fieldConf['eval']['catalog']['type'])
								{
									case 'url':
										$label = $fieldValue;
										$fieldValue = urlencode(urlencode($fieldValue));
										break;

									case 'file':
										$label = implode(',',deserialize($fieldValue, true));
										$fieldValue = urlencode(urlencode($fieldValue));
										break;

									case 'date':
										$fieldValue = $label;
										break;
								}

								$newcurrent = $current;
								$newcurrent[$field] = htmlspecialchars($fieldValue);
								$newcurrent = self::clearTree($field, $newcurrent, $tree);
								$url = $this->generateFilterUrl($newcurrent, true, $blnLast);

								$addOption = array();
								$addOption['value'] = $url;
								$addOption['label'] = $label;
								$addOption['id'] = $fieldValue;
								if ($current[$field] == $newcurrent[$field])
								{
									$addOption['selected'] = true;
								}
								$options[] = $addOption;
							}

							$widget = array
							(
								'name'		=> $field,
								'id'		=> 'filter_field_'.$field,
								'label'		=> $fieldConf['label'][0],
								'options'	=> serialize($options),
								'value' 	=> htmlentities($this->generateFilterUrl($current, true, $blnLast)),
								'tableless'	=> true,
								'inputType'	=> $input,
							);
							// parse Widget
							$settings['filter'][] = $this->parseWidget($widget, false);
							$widgets['filter'][] = $widget;
						}
						break;

					case 'longtext':
						// No, we really have no clue how to present a longtext in a select box, so please do not file an issue.
						$options[] = array
						(
							'label' => &$GLOBALS['TL_LANG']['MSC']['invalidFilter'],
							'value' => '',
						);
						$widget = array
						(
							'name'			=> $field,
							'id'			=> 'filter_field_'.$field,
							'label'			=> $fieldConf['label'][0],
							'options'		=> serialize($options),
							'value' 		=> '',
							'tableless'		=> true,
							'inputType'		=> 'list',
						);
						// parse Widget
						$settings['filter'][] =  $this->parseWidget($widget, true);
						$widgets['filter'][] = $widget;
						break;

					default:
						// HOOK: let custom fields generate a filter widget
						if ($customField = $this->generateFilterForFieldHook($field, $fieldType, $fieldConf, $config, $filterurl, $query, $tree))
						{
							$settings['filter'][] = $customField['settings'];
							$widgets['filter'][] = $customField['widget'];
						}
						break;
				} // / switch
			} // foreach field
		} // /filter enabled

		if ($rangeWidget = $this->generateWidgetConfigRange($current))
		{
			$settings['range'][] = $this->parseWidget($rangeWidget, true);
			$widgets['range'][] = $rangeWidget;
		}

		if ($dateWidget = $this->generateWidgetConfigDate($current))
		{
			$settings['date'][] = $this->parseWidget($dateWidget, false);
			$widgets['date'][] = $dateWidget;
		}

		if ($sortWidget = $this->generateWidgetConfigSort($current))
		{
			$settings['sort'] = $this->parseWidget($sortWidget, false);
			$widgets['sort'] = $sortWidget;
		}

		if ($searchWidget = $this->generateWidgetConfigSearch())
		{
			$settings['search'] = $this->parseWidget($searchWidget);
			$widgets['search'] = $searchWidget;
		}

		$settings['url'] 		= $this->generateFilterUrl();
		$settings['action']		= $this->generateFilterUrl($current);
		$settings['widgets']	= $widgets;

		$settings = $this->generateFilterCatalogHook($settings);

		return $settings;
	}

	/**
	 * Counts the catalog items for each filter option
	 * by using a relation table
	 * @param int $intFieldId
	 * @param string $strQuery
	 * @param array $arrParams for the $strQuery
	 * @return DatabaseResult with fields itemcount, valueid
	 */
	protected function fetchRelTagCounts($intFieldId, $strQuery, array $arrParams)
	{
		$stmtWhere = '1';
		if (strlen($strQuery))
			$stmtWhere = $strQuery;

		$otherFiltersQuery = sprintf('SELECT COUNT(itemid) as itemcount, valueid FROM tl_catalog_tag_rel WHERE
									fieldid=%u AND itemid IN (SELECT id
										FROM %s
										WHERE %s)
									GROUP BY valueid',
									$intFieldId,
									$this->strTable,
									$stmtWhere);

		$objFilter = $this->Database->prepare($otherFiltersQuery)
									->execute($arrParams);
		return $objFilter;
	}

	/**
	 * @param string $strFieldName
	 * @param string $strWhere optional
	 * @param array $arrParams optional
	 * @param boolean $blnOrder optional order by the field's value
	 * @return array enumerated with all existing values
	 */
	protected function fetchAvailableFilterOptions($strFieldName, $strWhere='', array $arrParams = array(), $blnOrder=false)
	{
		$result = array();
		// get existing options in DB
		$objFilter = $this->Database->prepare('SELECT DISTINCT(' . $strFieldName . ')'
											. ' FROM ' . $this->strTable
											. (strlen($strWhere) ? ' WHERE ' . $strWhere : '')
											. ($blnOrder ? ' ORDER BY ' . $strFieldName : ''))
									->execute($arrParams);
		if ($objFilter->numRows)
		{
			$result = $objFilter->fetchEach($strFieldName);
		}
		return $result;
	}

	/**
	 * @param string $strFieldName
	 * @param array $arrOptionIds
	 * @param string $strWhere optional
	 * @param array $arrParams optional
	 * @return array : string $strFieldName . $id => int $count
	 */
	protected function fetchTagCounts($strFieldName, array $arrOptionIds, $strWhere='', array $arrParams = array())
	{
		$tmpTags = array();
		foreach ($arrOptionIds as $id)
		{
			// use field name as prefix for valid names
			$tmpTags[] = 'SUM(FIND_IN_SET(' . $id . ',' . $strFieldName . ')) AS ' . $strFieldName . $id;
		}
		$objResultCount = $this->Database->prepare('SELECT ' . implode(', ', $tmpTags)
													. ' FROM ' . $this->strTable
													. ($strWhere ? ' WHERE '. $strWhere : ''))
										->execute($arrParams);
		return $objResultCount->row();
	}

	/**
	 * Generates the widget configuration for tags
	 * @param string $strFieldName
	 * @param array $fieldConf
	 * @param string $input
	 * @param array $arrCurrent filters
	 * @param array $arrTree
	 * @param string $strQuery from the other filters
	 * @param array $arrParams for that $strQuery
	 * @param $blnLastInTree
	 * @return array Widget configuration
	 */
	protected function generateWidgetConfigTags($strFieldName, array $fieldConf,
												$input, array $arrCurrent,
												array $arrTree, $strQuery,
												array $arrParams, $blnLastInTree)
	{
		$newcurrent = $arrCurrent;
		unset($newcurrent[$strFieldName]);
		$newcurrent = self::clearTree($strFieldName, $newcurrent, $arrTree);

		// first the clear option
		$addOption = array();
		$addOption['value'] = $this->generateFilterUrl($newcurrent, true, $blnLastInTree);
		$addOption['label'] = $this->makeAllLabel($input, $fieldConf['label'][0], $this->catalog_tags_multi);
		if (! strlen($arrCurrent[$strFieldName]))
		{
			$addOption['selected'] = true;
		}
		$options[] = $addOption;

		// then all real options
		$filterOptionCounts = $this->fetchRelTagCounts($fieldConf['eval']['catalog']['fieldId'], $strQuery, $arrParams);
		while ($filterOptionCounts->next())
		{
			$id = $filterOptionCounts->valueid;
			// maybe there's still relations to options which are already deleted
			if (array_key_exists($id, $fieldConf['options']))
			{
				$optionLabel = $fieldConf['options'][$id];
				if ($filterOptionCounts->itemcount > 0)
				{
					$currentIds=strlen($arrCurrent[$strFieldName]) ? explode(',', $arrCurrent[$strFieldName]) : array();
					$selected = count($currentIds) && in_array($id, $currentIds);
					$newcurrent = $arrCurrent;
					$newids = array_unique(!$selected ? array_merge($currentIds, array($id)) : array_diff($currentIds, array($id)));
					$newcurrent[$strFieldName] = ($this->catalog_tags_multi ? implode(',',$newids) : $id);
					$newcurrent = self::clearTree($strFieldName, $newcurrent, $arrTree);
					$url = $this->generateFilterUrl($newcurrent, true, $blnLast);
					$blnList = ($selected && $input=='list');
					$addOption = array();
					$addOption['value'] = $url;
					$addOption['label'] = $blnList ? sprintf($GLOBALS['TL_LANG']['MSC']['optionselected'], $optionLabel) : $optionLabel;
					$addOption['id'] = $id;
					$addOption['resultcount'] = $filterOptionCounts->itemcount;
					$addOption['selected'] = $selected;
					$options[] = $addOption;
				}
			}
		}
		$result = array
		(
			'name'			=> $strFieldName,
			'id'				=> 'filter_field_'.$strFieldName,
			'label'			=> $fieldConf['label'][0],
			'value' 		=> $this->generateFilterUrl($arrCurrent, true, $blnLast),
			'options'		=> serialize($options),
			'tableless'	=> true,
			'inputType' => ($this->catalog_tags_multi && $input=='radio' ? 'checkbox' : $input),
		);
		if ($this->catalog_tags_multi && $input=='select')
		{
			$result['multiple'] = true;
		}
		return $result;
	}

	/**
	 * Creates the widget configuration to choose a range
	 * @result null || array config for widget
	 */
	protected function generateWidgetConfigRange(array $arrCurrent)
	{
		$result = null;
		$arrRange = deserialize($this->catalog_range, true);
		if ($this->catalog_range_enable
			&& count($arrRange)
			&& strlen($arrRange[0]))
		{
			// GET range values
			$rangeOptions = array();
			foreach ($arrRange as $field)
			{
				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];

				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'text':
					case 'longtext':
					case 'number':
					case 'decimal':
					case 'date':
						$rangeValues =  trimsplit('__', $arrCurrent[$field]);

						$result = array
						(
							'name'		=> $field,
							'id'		=> 'filter_range_' . $field,
							'label'		=> ($i==0 ? $fieldConf['label'][0]:''),
							'inputType' => 'range',
							'value' 	=> serialize($rangeValues),
							'multiple'	=> true,
							'size'		=> 2,
							'tableless' => true,
							'addSubmit' => true,
							'slabel' 	=> $GLOBALS['TL_LANG']['MSC']['catalogSearch']
						);

						// date picker
						if ($fieldConf['eval']['catalog']['type'] == 'date')
						{
							$result['maxlength']	= 10;
							$result['rgxp']			= 'date';
							$result['datepicker']	= true;
						}

						break;
				}
			}
		}
		return $result;
	}

	/**
	 * @param array $arrCurrent
	 * @return null || array with configuration for the date widget
	 */
	protected function generateWidgetConfigDate(array $arrCurrent)
	{
		$result = null;
		$arrDates = deserialize($this->catalog_dates, true);
		if ($this->catalog_date_enable && count($arrDates))
		{
			foreach ($arrDates as $fieldconfig)
			{
				list($field, $config) = each($fieldconfig);

				$input = $config['radio'];
				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
				$options = array();

				$selected = !strlen($arrCurrent[$field]);

				$newCurrent = $arrCurrent;
				unset($newCurrent[$field]);

				$addOption = array();
				$addOption['value'] = $this->generateFilterUrl($newCurrent, true);
				$addOption['label'] = $this->makeAllLabel($input, $fieldConf['label'][0]);
				$addOption['id'] = '';

				if ($selected)
				{
					$addOption['selected'] = true;
				}

				$options[] = $addOption;

				$arrRanges = deserialize($this->catalog_date_ranges, true);
				foreach ($arrRanges as $id => $range)
				{
					$now = new Date();
					$strToday = '';
					$strYesterday = date($now->format, (strtotime('-1 days', $now->dayBegin) + 1));
					$strTomorrow = date($now->format, (strtotime('+1 days', $now->dayBegin) + 1));
					$strPast = '';
					$strFuture = '';

					switch ($range)
					{
						case 'd':
							//$strPast = date($now->format, $now->dayEnd);
							$strPast = date($now->format, (strtotime('-1 days', $now->dayBegin) + 1));
							break;

						case 'w':
							$strPast = date($now->format, (strtotime('-7 days', $now->dayBegin) + 1));
							break;

						case 'm':
							$strPast = date($now->format, (strtotime('-1 month', $now->dayBegin) + 1));
							break;

						case 'h':
							$strPast = date($now->format, (strtotime('-6 month', $now->dayBegin) + 1));
							break;

						case 'y':
							$strPast = date($now->format, (strtotime('-1 year', $now->dayBegin) + 1));
							break;

						case 't':
							$strToday = date($now->format, $now->dayBegin);
							break;

						case 'df':
							$strFuture = date($now->format, (strtotime('+1 days', $now->dayBegin) + 1));
							break;

						case 'wf':
							$strFuture = date($now->format, (strtotime('+7 days', $now->dayBegin) + 1));
							break;

						case 'mf':
							$strFuture = date($now->format, (strtotime('+1 month', $now->dayBegin) + 1));
							break;

						case 'hf':
							$strFuture = date($now->format, (strtotime('+6 month', $now->dayBegin) + 1));
							break;

						case 'yf':
							$strFuture = date($now->format, (strtotime('+1 year', $now->dayBegin) + 1));
							break;

					}
					$newCurrent = $arrCurrent;
					$newCurrent[$field] = ($strPast ? $strPast . '__' .$strYesterday : '')
										. $strToday
										. ($strFuture ? $strTomorrow . '__' . $strFuture : '');
					$addOption = array();
					$addOption['value'] = $this->generateFilterUrl($newCurrent, true);
					$addOption['label'] = &$GLOBALS['TL_LANG']['MSC']['daterange'][$range];
					$addOption['id'] = $range;
					if ($arrCurrent[$field] == $newCurrent[$field])
					{
						$addOption['selected'] = true;
					}
					$options[] = $addOption;
				}

				$result = array
				(
					'name' 		=> $field,
					'id'		=> 'filter_date_' . $field,
					'label'		=> $fieldConf['label'][0],
					'value'		=> $this->generateFilterUrl($arrCurrent, true),
					'tableless'	=> true,
					'options'	=> serialize($options),
					'inputType'	=> $input
				);
			}
		}
		return $result;
	}

	/**
	 * @param array $arrCurrent
	 * @return null || array with configuration for the sort widget
	 */
	protected function generateWidgetConfigSort(array $arrCurrent)
	{
		$result = null;
		$arrSort = deserialize($this->catalog_sort, true);
		if ($this->catalog_sort_enable && count($arrSort))
		{
			$options = array();
			// offer an option for unsorted
			if (count($arrSort) && $this->catalog_sort_type != 'list')
			{
				$newCurrent = $arrCurrent;
				unset($newCurrent[self::PARAMORDERBY]);
				unset($newCurrent[self::PARAMSORT]);

				$addOption = array();
				$addOption['value'] = $this->generateFilterUrl($newCurrent, true);
				$addOption['label'] = $GLOBALS['TL_LANG']['MSC']['unsorted'];
				$addOption['id'] = '';
				if (empty($arrCurrent[self::PARAMSORT])
					&& empty($arrCurrent[self::PARAMORDERBY]))
				{
					$addOption['selected'] = true;
				}
				$options[] = $addOption;
			}

			foreach ($arrSort as $id=>$field)
			{
				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
				$newCurrent = $arrCurrent;
				$newCurrent[self::PARAMORDERBY] = $field;
				foreach (array('asc','desc') as $order)
				{
					$newCurrent[self::PARAMSORT] = $order;

					$addOption = array();
					$addOption['value'] = $this->generateFilterUrl($newCurrent, true);
					$addOption['label'] = $fieldConf['label'][0] . ' ' . $this->selectSortLabel($fieldConf['eval']['catalog']['type'], ($order=='asc'));
					$addOption['id'] = $field.'__'.$order;
					if ($arrCurrent[self::PARAMSORT] == $newCurrent[self::PARAMSORT]
						&& $arrCurrent[self::PARAMORDERBY] == $newCurrent[self::PARAMORDERBY])
					{
						$addOption['selected'] = true;
					}
					$options[] =  $addOption;
				}
			}
			$result = array
			(
				'name'		=> self::PARAMSORT,
				'id'		=> 'filter_' . self::PARAMSORT,
				'value'		=>  $this->generateFilterUrl($arrCurrent, true),
				'tableless'	=> true,
				'options'	=> serialize($options),
				'inputType'	=> $this->catalog_sort_type
			);
		}
		return $result;
	}

	/**
	 * @return null || array configuration for the search widget
	 */
	protected function generateWidgetConfigSearch()
	{
		$result = null;
		if ($this->catalog_search_enable)
		{
			$result = array
			(
				'name'		=> self::PARAMSEARCH,
				'id'		=> 'filter_'.self::PARAMSEARCH,
				'inputType'	=> 'text',
				'value'		=> $this->Input->get(self::PARAMSEARCH),
				'tableless'	=> true,
				'addSubmit'	=> true,
				'slabel'	=> $GLOBALS['TL_LANG']['MSC']['catalogSearch'],
			);
		}
		return $result;
	}

	/**
	 * HOOK: let custom fields mimic another fieldtype to generate a filter
	 * @param string $fieldName
	 * @param string $fieldType
	 * @param array $fieldConfig
	 * @param array $config
	 * @param array $filterUrl
	 * @param array $query
	 * @param array $tree
	 * @return string field type (new one or $fieldType)
	 */
	protected function generateFilterWidgetHook ($fieldName, $fieldType, array $fieldConfig, array $config, array $filterUrl, array $query, array $tree)
	{
		$result = $fieldType;

		if(array_key_exists($fieldType, $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes']))
		{
			$fieldTypeArr = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType];

			if (array_key_exists('generateFilterWidget', $fieldTypeArr)
				&& is_array($fieldTypeArr['generateFilterWidget']))
			{
				foreach ($fieldTypeArr['generateFilterWidget'] as $callback)
				{
					$this->import($callback[0]);
					$tmp = $this->$callback[0]->$callback[1]($fieldType, $fieldName, $config, $fieldConfig, $filterUrl, $query, $tree);
					if($tmp)
					{
						$result = $tmp;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * HOOK: let custom fields generate a filter widget
	 * @param string $fieldName
	 * @param string $fieldType
	 * @param array $fieldConfig
	 * @param array $config
	 * @param array $filterUrl
	 * @param array $query
	 * @param array $tree
	 * @return null || array with keys 'settings', 'widget';
	 */
	protected function generateFilterForFieldHook($fieldName, $fieldType, array $fieldConfig, array $config, array $filterUrl, array $query, array $tree)
	{
		$result = null;
		if($fieldType
			&& array_key_exists($fieldType,
								$GLOBALS['BE_MOD']['content']['catalog']['fieldTypes']))
		{
			$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType];

			if (array_key_exists('generateFilterForField', $fieldType)
				&& is_array($fieldType['generateFilterForField']))
			{
				$callback = $fieldType['generateFilterForField'];
				$tmp = $this->$callback[0]->$callback[1]($fieldType, $fieldName, $config, $fieldConfig, $filterUrl, $query, $tree);
				if($tmp)
				{
					$result = $tmp;
				}
			}
		}

		return $result;
	}
	/**
	 * HOOK: generateFilterCatalog
	 * @param array $arrSettings
	 * @return array $arrSettings with changes from the hooks
	 */
	protected function generateFilterCatalogHook(array $arrSettings)
	{
		if(is_array($GLOBALS['TL_HOOKS']['generateFilterCatalog']))
		{
			foreach ($GLOBALS['TL_HOOKS']['generateFilterCatalog'] as $callback)
			{
				$this->import($callback[0]);
				$arrSettings = $this->$callback[0]->$callback[1]($this,$arrSettings);
			}
		}

		return $arrSettings;
	}

	/**
	 *
	 * Creates HTML code for the widget, fitting for catalog
	 * @param array $widget
	 * @return string HTML code for the widget
	 */
	public function parseWidget(array $widget)
	{
		$widget = $this->addWidgetAttributes($widget);
		$class = $widget['inputType'];
		$options = deserialize($widget['options']);
		$return = '';
		if($widget['label'])
			$label = sprintf('<h3><label for="%s">%s</label></h3>', 'ctrl_'.$widget['id'], $widget['label']);
		else
			$label = '';

		switch ($widget['inputType'])
		{
			case 'list':
				$return = sprintf('%s<div id="%s" class="list_container">
	<ul class="list%s">',
							$label,
							'ctrl_'.$widget['id'],
							(strlen($widget['class']) ? ' ' . $widget['class'] : '')
					);
				foreach ($options as $id=>$option)
				{
					$class = standardize($option['id']);
					$class = ($class == '' ? 'none' : $class);

					$selected = $option['selected'];
					$return .= sprintf('
		<li class="option%s%s"><a href="%s" title="%s">%s</a></li>',
						' list_'.$class,
						($selected ? ' active' : ''),
						$option['value'],
						$option['label'],
						$option['label']
					);
				}
				$return .= '
	</ul></div>';
				break;

			case 'range':
				$widget['value'] = deserialize($widget['value'], true);
				$arrFields = array();

				foreach (array('From', 'To') as $i => $bound)
				{
					$inputId = $widget['id'] . '_' . $bound;
					$datepicker = '';

					if ($widget['datepicker'])
						$datepicker = $this->datePicker(0, $widget['rgxp'], $inputId);

					// adding a <label for=""> to the inputs
					$strLabel = $GLOBALS['TL_LANG']['MSC']['range' . $bound];
		      
					$arrFields[] = sprintf('%s<input type="text" name="%s[]" id="ctrl_%s" class="text%s" value="%s"%s />',
							(strlen($strLabel)) ? '<label for="ctrl_' . $inputId . '">' . $strLabel . '</label>' : '',
							$widget['name'],
							$inputId,
							(strlen($widget['class']) ? ' ' . $widget['class'] : ''),
							specialchars($widget['value'][$i]),
							$widget['attributes'])
							. $datepicker . ($bound == 'To' ? $this->addSubmit($widget) : '');
				}

				$return = sprintf('%s<div id="ctrl_%s"%s>%s</div>',
						$label,
						$widget['id'],
						(strlen($widget['class']) ? ' class="' . $widget['class'] . '"' : ''),
						implode(($widget['separator'] ? $widget['separator'] : ' '), $arrFields));

				break;

			case 'text':
				$return = sprintf('<input type="text" name="%s" id="ctrl_%s" class="text%s" value="%s"%s />',
								$widget['name'],
								$widget['id'],
								(strlen($widget['class']) ? ' ' . $widget['class'] : ''),
								specialchars($widget['value']),
								$widget['attributes'])
								. $this->addSubmit($widget);
				break;

			case 'radio':

				$arrOptions = array();
				foreach ($options as $i=>$option)
				{
					$arrOptions[] = sprintf('<input type="radio" name="%s" id="opt_%s" class="radio%s" value="%s"%s%s /> <label for="opt_%s">%s</label>',
											$widget['name'],
											$widget['id'].'_'.$option['id'],
											(strlen($widget['class']) ? ' ' . $widget['class'] : ''),
											specialchars($option['value']),
											($option['selected'] ? ' checked="checked"' : ''),
											$widget['attributes'],
											// $widget['id'].'_'.$i,
											$widget['id'].'_'.$option['id'], // we have to use the proper ID otherwise the label won't be attached to the input.
											$option['label']);
				}

				// Add a "no entries found" message if there are no options
				if (!count($options))
				{
					$arrOptions[]= '<p class="tl_noopt">'.$GLOBALS['TL_LANG']['MSC']['noResult'].'</p>';
				}

				$return = sprintf('%s<div id="ctrl_%s" class="radio_container%s">%s</div>',
							$label,
							$widget['id'],
							(strlen($widget['class']) ? ' ' . $widget['class'] : ''),
							implode(($widget['separator'] ? $widget['separator'] : '<br />'), $arrOptions));

				break;


			case 'checkbox':

				$arrOptions = array();
				foreach ($options as $i=>$option)
				{
					$arrOptions[] = sprintf('<span><input type="checkbox" name="%s" id="opt_%s" class="checkbox%s" value="%s"%s%s /> <label for="opt_%s">%s</label></span>',
						$widget['name'] . ($widget['multiple'] ? '[]' : ''), // name
						$widget['id'].'_'.$option['id'], // id
						(strlen($widget['class']) ? ' ' . $widget['class'] : ''), // class
						($widget['multiple'] ? specialchars($option['value']) : 1), // value
						($option['selected'] ? ' checked="checked"' : ''), // state
						$widget['attributes'], // more attributes
						$widget['id'].'_'.$option['id'], // the id again
						$option['label']); // and the label
				}
				// Add a "no entries found" message if there are no options
				if (!count($options))
				{
					$arrOptions[]= '<p class="noopt">'.$GLOBALS['TL_LANG']['MSC']['noResult'].'</p>';
				}

				$return = sprintf('%s<div id="ctrl_%s" class="%s%s">%s</div>',
						$label,
						$widget['id'],
						($widget['multiple'] ? 'checkbox_container' : 'checkbox_single_container'),
						(strlen($widget['class']) ? ' ' . $widget['class'] : ''),
						implode(($widget['separator'] ? $widget['separator'] : ''), $arrOptions));
				break;

			case 'select':
				// Add empty option (XHTML) if there are none
				if (!count($options))
				{
					$options = array(array('value'=>'', 'label'=>'-'));
				}
				foreach ($options as $option)
				{
					$strOptions .= sprintf('<option value="%s"%s>%s</option>',
									$option['value'],
									($option['selected'] ? ' selected="selected"' : ''),
									$option['label']);
				}

				$return = sprintf('%s<select name="%s" id="ctrl_%s" class="%s%s"%s%s>%s</select>',
						$label,
						$widget['name'] . ($widget['multiple'] ? '[]' : ''),
						$widget['id'],
						($widget['multiple'] ? 'multi'.$class : $class),
						(strlen($widget['class']) ? ' ' . $widget['class'] : ''),
						($widget['multiple'] ? ' multiple="multiple"' : ''),
						$widget['attributes'],
						$strOptions);
				break;
		}
		return '<div class="widget'.(strlen($widget['id']) ? ' ' . $widget['id'] : '').'">
'.$return.'
</div>
';
	}
	
	/**
	 * Generates the code to include a date picker for a field
	 * and includes the libraries and the styles for it 
	 * @param mixed $initValue
	 * @param string $strRgxp
	 * @param string $strWidgetId
	 * @return string HTML to add after the field 
	 * @post $GLOBALS['TL_JAVASCRIPT'] contains the datepicker library
	 * @post $GLOBALS['TL_CSS'] contains the datepicker styles
	 */
	protected function datePicker($initValue, $strRgxp, $strWidgetId)
	{
		// load datepicker library and styles
		// Contao takes care of duplicates
	  if (version_compare(VERSION, '2.10', '>='))
	  {
	    $GLOBALS['TL_CSS'][] = 'plugins/datepicker/dashboard.css';
	    $GLOBALS['TL_JAVASCRIPT'][] = 'plugins/datepicker/datepicker.js';
	  }
	  else if(version_compare(VERSION.'.'.BUILD, '2.8.0', '<'))
		{
			$GLOBALS['TL_HEAD'][]='<script src="plugins/calendar/calendar.js" type="text/javascript"></script>';
			$GLOBALS['TL_CSS'][] = 'plugins/calendar/calendar.css';
		} else {
			$GLOBALS['TL_HEAD'][]='<script src="plugins/calendar/js/calendar.js" type="text/javascript"></script>';
			$GLOBALS['TL_CSS'][] = 'plugins/calendar/css/calendar.css';
		}
	  
	  $loaderScript = '<script type="text/javascript"><!--//--><![CDATA[//><!--
	    window.addEvent(\'domready\', function() {'
	    . $this->datePickerString($initValue, $strRgxp, $strWidgetId) . '
	    });
	    //--><!]]></script>';
	  
	  $result = $loaderScript;
	  
	  // image needed in HTML for the new datepicker script *sigh* 
	  if (version_compare(VERSION, '2.10', '>='))
	  {
	    $result = '<img src="plugins/datepicker/icon.gif" width="20" height="20" id="toggle_'
				   . $strWidgetId . '" style="vertical-align:-6px;" />' . $result;
	  }
	  
	  return $result;
	}
	
	/**
	 * Creates the JS call for a new datepicker instance
	 * @see Controller::getDatePickerString()
	 * @param mixed $initValue
	 * @param string $strRgxp name of the regular expression from eval
	 * @param string $strWidgetId to identify the widget
	 * @return string HTML and JS source code 
	 */
	protected function datePickerString($initValue, $strRgxp, $strWidgetId)
	{ 
	  $result = '';
	  
    if (version_compare(VERSION, '2.10', '<'))
      $result = $this->getDatePickerString();
    
    else
    {
      $time = '';
      
      /**
       * @see DataContainer::row()
       */
	    switch ($strRgxp)
	    {
	      case 'datim':
	        $time = ",\n      timePicker: true";
	        break;
	        
	      case 'time':
	        if (version_compare(VERSION, '2.11', '<'))
	          $time = ",\n      timePickerOnly: true";
	        
	        else
	          $time = ",\n      pickOnly:\"time\"";
	        
	        break;
	      break;
	    }
	    
	    $format = $GLOBALS['TL_CONFIG'][$strRgxp.'Format'];
	    
	    if (version_compare(VERSION, '2.11', '<'))
	    {
	      $result = "new DatePicker('#ctrl_" . $strWidgetId . "', {
  	      allowEmpty: true,
  	      toggleElements: '#toggle_" . $strWidgetId . "',
  	      pickerClass: 'datepicker_dashboard',
  	      format: '" . $format . "',
  	      inputOutputFormat: '" . $format . "',
  	      positionOffset:{x:130,y:-185}" . $time . ",
  	      startDay: " . $GLOBALS['TL_LANG']['MSC']['weekOffset'] . ",
  	      days:[' " . implode("','", $GLOBALS['TL_LANG']['DAYS']) . "'],
  	      dayShort: " . $GLOBALS['TL_LANG']['MSC']['dayShortLength'] . ",
  	      months:['" . implode("','", $GLOBALS['TL_LANG']['MONTHS']) . "'],
  	      monthShort: " . $GLOBALS['TL_LANG']['MSC']['monthShortLength'] . "
  	      });";
	    }
	    
	    else
	    {
	      $format = Date::formatToJs($format);
	      
	      $result = 'new Picker.Date($$("#ctrl_' . $strWidgetId . '"), {
	        draggable:false,
	        toggle:$$("#toggle_' . $strWidgetId . '"),
	        format:"' . $format . '",
	        positionOffset:{
	          x:-197,y:-182}' . $time . ',
	          pickerClass:"datepicker_dashboard",
	          useFadeInOut:!Browser.ie,
	          startDay:' . $GLOBALS['TL_LANG']['MSC']['weekOffset'] . ',
	          titleFormat:"' . $GLOBALS['TL_LANG']['MSC']['titleFormat'] . '"
	      });';
	    }
    }
	    
    return $result;
	}

	/**
	 * @param array $widget
	 * @return string HTML for the submit button for the widget
	 */
	protected function addSubmit(array $widget)
	{
		return (strlen($widget['slabel']) ? sprintf(' <input type="submit" id="ctrl_%s_submit" class="submit" value="%s" />',
						$widget['id'],
						specialchars($widget['slabel']))
						: '');
	}

	/**
	 *
	 * Adds the javascript attributes to input types which allow that
	 * @param array $widget
	 * @return array $widget with extra fields
	 */
	private function addWidgetAttributes(array $widget)
	{
		switch ($widget['inputType'])
		{
			case 'radio':
				$types = array
				(
					'attributes'	=> ' onclick="window.location=this.value"',
				);
				break;

			case 'checkbox':
				$types = array
				(
					'multiple'		=> true,
					'attributes'	=> ' onclick="window.location=this.value"',
				);
				break;

			case 'select':
				$types = array
				(
					'attributes'		=> $widget['multiple']
									? ' onclick="window.location=this.options[this.selectedIndex].value"'
									: ' onchange="window.location=this.options[this.selectedIndex].value"',
				);

				break;
		}
		if (is_array($types))
		{
			$widget = array_merge($widget, $types);
		}

		return $widget;
	}

	protected function selectSortLabel($inputType, $blnAsc =true)
	{
		$labelSuffix = '';
		switch ($inputType)
		{
			case 'text':
			case 'longtext':
			case 'select':
			case 'tags':
			case 'url':
			case 'file':
				$labelSuffix = $blnAsc ? $GLOBALS['TL_LANG']['MSC']['AtoZ']: $GLOBALS['TL_LANG']['MSC']['ZtoA'];
				break;

			case 'number':
			case 'decimal':
				$labelSuffix = $blnAsc ? $GLOBALS['TL_LANG']['MSC']['lowhigh']: $GLOBALS['TL_LANG']['MSC']['highlow'];
				break;

			case 'checkbox':
				$labelSuffix = $blnAsc ? $GLOBALS['TL_LANG']['MSC']['falsetrue']: $GLOBALS['TL_LANG']['MSC']['truefalse'];
				break;

			case 'date':
				$labelSuffix = $blnAsc ? $GLOBALS['TL_LANG']['MSC']['dateasc']: $GLOBALS['TL_LANG']['MSC']['datedesc'];
				break;

			default:
				if($blnAsc)
				{
					if($GLOBALS['TL_LANG']['MSC'][$inputType.'asc'])
						$labelSuffix=$GLOBALS['TL_LANG']['MSC'][$inputType.'asc'];
				} else {
					if($GLOBALS['TL_LANG']['MSC'][$inputType.'desc'])
						$labelSuffix=$GLOBALS['TL_LANG']['MSC'][$inputType.'desc'];
				}
		}
		return $labelSuffix;
	}

	private function getJumpTo($catalogJump, $blnJumpTo=true)
	{
		global $objPage;
		if(!$blnJumpTo)
			return $objPage->row();

		if ($this->cacheJumpTo[$catalogJump])
		{
			return $this->cacheJumpTo[$catalogJump];
		}
		else
		{
			if ($catalogJump && $blnJumpTo)
			{
				// Get current "jumpTo" page
				$objJump = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
										->execute($catalogJump);
				if ($objJump->numRows)
				{
					$pageRow = $objJump->row();
				}
			}
			else
			{
				$pageRow = $objPage->row();
			}
			// cacheJumpTo
			$this->cacheJumpTo[$pageRow['id']] = $pageRow;
			return $pageRow;
		}
	}

	/**
	 * Create a proper URL to redirect to based on the filters
	 * @param array $arrGet optional
	 * @param boolean $blnRoot optional
	 * @param boolean $blnJumpTo optional
	 * @return string URL to redirect to
	 */
	public function generateFilterUrl(array $arrGet =array(), $blnRoot =false, $blnJumpTo =true)
	{
		$arrPage = $this->getJumpTo($this->catalog_jumpTo, $blnJumpTo);
		$strParams = '';

		if (count($arrGet))
		{
			foreach ($arrGet as $k=>$v)
			{
				if (strlen($v))
				{
					$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];

					if (in_array($fieldConf['eval']['catalog']['type'], array('select', 'tags'))
							&& $this->getAliasFieldConf($fieldConf) != 'id')
					{
						$arrAlias = $this->getAliasOptionList($fieldConf);

						if ($fieldConf['eval']['catalog']['type'] == 'tags')
						{
							$tags = explode(',', $v);
							$newtags = array();
							foreach($tags as $tag)
							{
								$newtags[] = $arrAlias[$tag];
							}
							$v = implode(',', $newtags);
						}
						else
						{
							$v = $arrAlias[$v];
						}
					}
					$v = str_replace($GLOBALS['TL_CONFIG']['catalog']['safeCheck'],
									$GLOBALS['TL_CONFIG']['catalog']['safeReplace'],
									$v);
					$v = urlencode($v);
					$strParams .= $GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;' . $k . '=' . $v  : '/' . $k . '/' . $v;
				}
			}
		}
		return ($blnRoot ? $this->Environment->base : '') . $this->generateFrontEndUrl($arrPage, $strParams);
	}

	/**
	 * Translate SQL if needed (needed for calculated fields)
	 * @param array $arrFields : string fieldname
	 * @param string $strTable
	 * @return array : string fieldname or alias for sql
	 */
	protected function processFieldSQL(array $arrFields, $strTable)
	{
		$arrConverted = array();

		// iterate all catalog fields
		$objFields = $this->Database->prepare("SELECT *
											FROM tl_catalog_fields f
											WHERE f.pid=(SELECT c.id
											FROM tl_catalog_types c
											WHERE c.tableName=?)")
									->execute($strTable);
		
		$fieldConfigs = array();
		
		if ($objFields->numRows)
		{
			while ($objFields->next())
				$fieldConfigs[$objFields->colName] = $objFields->row();
			
			foreach ($arrFields as $id => $field)
			{
				if (array_key_exists($field, $fieldConfigs))
				{
					$arrConverted[$id] = self::sqlFieldAlias($field, $fieldConfigs[$field]);
				}
			}
			
			// allow extension developers to prepare SQL data
			foreach ($arrConverted as $id => $alias)
			{
				$this->processFieldSQLHook($id, $arrFields[$id], $fieldConfigs, $arrConverted);
			}
		}
		return $arrConverted;
	}

	/**
	 * Replaces the field name by the calc formula and alias for a calc field,
	 * keeps the field name for every other field
	 * @param string $strFieldName
	 * @param array $arrFieldConfigCatalog catalog part
	 * @return string field name to use f.e. in field list
	 */
	protected static function sqlFieldAlias($strFieldName, array $arrFieldConfigCatalog)
	{
		$result = $strFieldName;
		if ($arrFieldConfigCatalog['type'] == 'calc')
		{
			// set query value to formula
			$result = '(' . $arrFieldConfigCatalog['calcValue'] . ') AS ' . $strFieldName;
		}
		return $result;
	}
		/**
	 * Replaces the field name by the calculation formula, if applicable
	 * @param string $strFieldName
	 * @param array $arrFieldConfigCatalog
	 * @return string field name to use f.e. in WHERE statement
	 */
	protected static function sqlFieldName($strFieldName, array $arrFieldConfigCatalog)
	{
		$result = $strFieldName;

		if ($arrFieldConfigCatalog['type'] == 'calc')
		{
			// set query value to forumla
			$result = '(' . $arrFieldConfigCatalog['calcValue'] . ')';
		}
		return $result;
	}

	/**
	 * HOOK: allow third party extension developers to prepare the SQL data
	 * @param int $id
	 * @param string $fieldName
	 * @param array $arrFields
	 * @param array $arrConverted
	 * @return void
	 */
	protected function processFieldSQLHook($id, $fieldName, array $arrFields, array $arrConverted)
	{
		if(is_array($GLOBALS['TL_HOOKS']['processFieldSQL']) && count($GLOBALS['TL_HOOKS']['processFieldSQL']))
		{
			foreach($GLOBALS['TL_HOOKS']['processFieldSQL'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($this->catalog, $id, $fieldName, $arrFields, $arrConverted, $this->strTable);
			}
		}
	}

	/**
	 * Generate one or more items and return them as array
	 * @param Database_Result $objCatalog
	 * @param boolean $blnLink
	 * @param array $arrVisible optional
	 * @param boolean $blnImageLink optional
	 * @return array
	 */
	protected function generateCatalog(Database_Result $objCatalog, $blnLink=true, array $arrVisible=array(), $blnImageLink=false)
	{
		$i=0;
		$arrCatalog = array();
		
		$objCatalog->reset();
		while ($objCatalog->next())
		{
			foreach ($this->systemColumns as $sysCol)
			{
				$arrCatalog[$i][$sysCol] = $objCatalog->{$sysCol};
			}
			$arrCatalog[$i]['catalog_name'] = $objCatalog->catalog_name;
			$arrCatalog[$i]['parentJumpTo'] = $objCatalog->parentJumpTo;
			$arrCatalog[$i]['tablename'] = $this->strTable;
			$arrCatalog[$i]['showLink'] = (!$this->catalog_link_override);

			// get alias field WARNING -- check if blnLink is needed for edit mode where alias is also used
			if ($i==0 && $blnLink)
			{
				$objArchive = $this->Database->prepare("SELECT aliasField FROM tl_catalog_types where id=?")
											->limit(1)
											->execute($objCatalog->pid);

				$aliasField = $objArchive->numRows ? $objArchive->aliasField : 'alias';
			}

			$class = (($i == 0) ? ' first' : '') . ((($i + 1) == $objCatalog->numRows) ? ' last' : '') . ((($i % 2) == 0) ? ' even' : ' odd');
			$arrCatalog[$i]['class'] = $class;

			if ($blnLink)
			{
				$arrCatalog[$i]['link'] = $this->generateLink($objCatalog, $aliasField, $this->strTable, $this->catalog_link_window);
				$arrCatalog[$i]['url'] = $this->generateCatalogUrl($objCatalog, $aliasField, $this->strTable);
			}

			$arrData = $objCatalog->row();

			if ($this->catalog_edit_enable && $this->fieldsAllowFEEdit($arrData, $arrVisible))
			{
				$arrCatalog[$i]['linkEdit'] = $this->generateLink($objCatalog, $aliasField, $this->strTable, $this->catalog_link_window, true);
				$arrCatalog[$i]['urlEdit'] = $this->generateCatalogEditUrl($objCatalog, $aliasField, $this->strTable);
			}

			// reduce to only the visible fields
			$tmpData = array();
			foreach ($arrVisible as $fieldName)
			{
				$tmpData[$fieldName] = $arrData[$fieldName];
			}
			$arrData = $tmpData;

			// fields
			foreach ($arrData as $k=>$v)
			{
				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];

				$blnParentCheckbox = $fieldConf['eval']['catalog']['parentCheckbox']
									&& !$arrData[$fieldConf['eval']['catalog']['parentCheckbox']];

				if (in_array($k, $this->systemColumns)
					|| $fieldConf['inputType'] == 'password'
					|| $blnParentCheckbox)
					continue;

				$strLabel = strlen($label = $fieldConf['label'][0]) ? $label : $k;
				$strType = $fieldConf['eval']['catalog']['type'];

				$arrValues = $this->parseValue($this->type.$i, $k, $v, $blnImageLink, $objCatalog);

				$linked = deserialize($this->catalog_islink, true);
				if ($this->catalog_link_override && is_array($linked) && in_array($k, $linked))
				{
					$arrValues['html'] = $this->generateLink($objCatalog, $aliasField, $this->strTable, $this->catalog_link_window, false, $arrValues['html']);
				}

				$arrCatalog[$i]['data'][$k] = array
				(
					'label' => $strLabel,
					'type'	=> $strType,
					'raw' => $v,
					'value' => ($arrValues['html'] ? $arrValues['html'] : '')
				);

				switch ($strType)
				{
					case 'select':
					case 'tags':
						list($refTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);

						if (strlen(trim($v)))
						{
							// set sort order
							$sortCol =	$fieldConf['eval']['catalog']['sortCol'];
							if (!strlen($sortCol))
							{
								$sortCol = 'sorting';
							}

							$sortOrder = $this->Database->fieldExists($sortCol, $refTable) ? $sortCol : $refCol;

							// Get referenced fields
							$objRef = $this->Database->prepare('SELECT * FROM '.$refTable.' WHERE id IN ('.trim($v).')'.(strlen($sortOrder)?' ORDER BY '.$sortOrder:''))
													->execute();

							if ($objRef->numRows)
							{
								// Get Ref Catalog JumpTo
								$objJump = $this->Database->prepare("SELECT tableName, aliasField, jumpTo FROM tl_catalog_types WHERE tableName=?")
														->limit(1)
														->execute($refTable);

								// Add Ref Catalog Links
								if ($objJump->numRows)
								{
									while ($objRef->next())
									{
											$objRef->parentJumpTo = $objJump->jumpTo;
											$objRef->parentLink = $this->generateLink($objRef, $objJump->aliasField, $objJump->tableName, $this->catalog_link_window);
											$objRef->parentUrl = $this->generateCatalogUrl($objRef, $objJump->aliasField, $objJump->tableName);
									}
								}

								// add to reference array
								$arrCatalog[$i]['data'][$k]['ref'] = $objRef->fetchAllAssoc();
							}
						}
						break;

					case 'file':
					case 'image':
						// add file and image information
						$arrCatalog[$i]['data'][$k]['files'] = $arrValues['items'];
						$arrCatalog[$i]['data'][$k]['meta'] = $arrValues['values'];
						break;

					default:
						// per default we deliver all other keys aswell to allow custom field types to transport custom data.
						foreach($arrValues as $kk => $vv)
						{
							if($kk != 'html')
								$arrCatalog[$i]['data'][$k][$kk] = $vv;
						}
				}
			}
			// HOOK: allow other extensions to manipulate the item before returning them in the array
			$arrCatalog[$i] = $this->generateCatalogItemHook($arrCatalog[$i], $arrData);
			$i++;
		}
		return $arrCatalog;
	}

	/**
	 * @param array $arrData
	 * @param array $arrVisible
	 * @return boolean do all field configurations/hooks allow frontend editing
	 * of this record?
	 */
	protected function fieldsAllowFEEdit(array $arrData, array $arrVisible)
	{
		$result = true;
		foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $fieldName => $fieldConfig)
		{
			// only check permission for visible fields
			if (count($arrVisible) && ! in_array($fieldName, $arrVisible))
				continue;
			// HOOK: additional permission checks if this field allows editing of this record (for the current user).
			if (! $this->checkPermissionFERecordEditHook($fieldName, $arrData))
			{
				// one false is enough
				$result = false;
				break;
			}
		}
		return $result;
	}

	/**
	 * Checks the checkPermissionFEEdit hooks for the field
	 *
	 * @see ModuleCatalogEdit::fieldAllowedForCurrentUserHooks()
	 * @param string $strFieldName
	 * @param array $arrData
	 * @return bool do all hooks allow editing this field for the current user?
	 */
	protected function checkPermissionFERecordEditHook($strFieldName, array $arrData)
	{
		// HOOK result must be boolean!
		$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$GLOBALS['TL_DCA'][$this->strTable]['fields'][$strFieldName]['eval']['catalog']['type']];
		if (is_array($fieldType)
			&& array_key_exists('checkPermissionFERecordEdit', $fieldType)
			&& is_array($fieldType['checkPermissionFERecordEdit']))
		{
			foreach ($fieldType['checkPermissionFERecordEdit'] as $callback)
			{
				$this->import($callback[0]);
				// TODO: Do we need more parameters here?
				if (!($this->$callback[0]->$callback[1]($this->strTable, $strFieldName, $arrData)))
				{
					// one false is enough
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * HOOK: generateCatalogItem allow other extensions to manipulate the item before returning them in the array
	 * @param array $arrCatalogItem
	 * @param array $arrData
	 * @return array $arrCatalogItem with changes from the hooks
	 */
	protected function generateCatalogItemHook(array $arrCatalogItem, array $arrData)
	{
		if(is_array($GLOBALS['TL_HOOKS']['generateCatalogItem']))
		{
			foreach ($GLOBALS['TL_HOOKS']['generateCatalogItem'] as $callback)
			{
				$this->import($callback[0]);
				$arrCatalogItem = $this->$callback[0]->$callback[1]($arrCatalogItem, $arrData, $this);
			}
		}
		return $arrCatalogItem;
	}

	/**
	 * Parse one or more items and pipe them through the given template
	 * @param Database_Result $objCatalog
	 * @param boolean $blnLink optional
	 * @param string $strTemplate optional
	 * @param array $arrVisible optional
	 * @return string
	 */
	protected function parseCatalog(Database_Result $objCatalog, $blnLink=true, $strTemplate='catalog_full', $arrVisible=array(), $blnImageLink=false)
	{
		$objTemplate = new FrontendTemplate($strTemplate);
		$arrCatalog = $this->generateCatalog($objCatalog, $blnLink, $arrVisible, $blnImageLink);
		// HOOK: allow other extensions to manipulate the items before passing it to the template
		$arrCatalog = $this->parseCatalogHook($arrCatalog, $objTemplate);

		$objTemplate->entries         = $arrCatalog;
		$objTemplate->moduleTemplate  = $this->Template;
		$objTemplate->searchEmptyMsg  = $GLOBALS['TL_LANG']['MSC']['catalogSearchEmpty'];
		$objTemplate->noItemsMsg      = $GLOBALS['TL_LANG']['MSC']['noItemsMsg'];
		return $objTemplate->parse();
	}

	/**
	 * HOOK: allow other extensions to manipulate the items list
	 * @param array $arrCatalog
	 * @param FrontendTemplate $objTemplate
	 * @return array $arrCatalog with changes made by hooks
	 */
	protected function parseCatalogHook(array $arrCatalog, FrontendTemplate $objTemplate)
	{
		if(is_array($GLOBALS['TL_HOOKS']['parseCatalog']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseCatalog'] as $callback)
			{
				$this->import($callback[0]);
				$arrCatalog = $this->$callback[0]->$callback[1]($arrCatalog, $objTemplate, $this);
			}
		}
		return $arrCatalog;
	}

	/**
	 * Generate the url to a catalog item.
	 * @param Database_Result $objCatalog the database result holding the current item.
	 * @param string $strAliasField the alias field to use, optional. If empty the routine will try "alias" and if that does not exist fallback to id
	 * @param string $strTable the tablename to use, optional. If empty, the current table will get used.
	 * @param string $strPrependParams parameters that shall be prepended to the /items/ part of the url, optional.
	 * @return string the generated url.
	 */
	protected function generateCatalogUrl(Database_Result $objCatalog, $strAliasField='alias', $strTable='', $strPrependParams='')
	{
		// fallback alias field
		$aliasField = 'id';
		if (strlen($strAliasField)
			&& (! $GLOBALS['TL_CONFIG']['disableAlias'])
			&& $this->Database->fieldExists($strAliasField, ($strTable?$strTable:$this->strTable))
			&& strlen($objCatalog->$strAliasField))
		{
			$aliasField = $strAliasField;
		}
		$useJump = ($this instanceof ModuleCatalogList
					|| $this instanceof ModuleCatalogFeatured
					|| $this instanceof ModuleCatalogRelated
					|| $this instanceof ModuleCatalogReference
					|| $this instanceof ModuleCatalogNavigation);
		$jumpTo = ($useJump && $this->jumpTo) ? $this->jumpTo : $objCatalog->parentJumpTo;
		$arrPage=$this->getJumpTo($jumpTo, true);
		return ampersand($this->generateFrontendUrl($arrPage, $strPrependParams . '/items/' . $objCatalog->$aliasField));
	}

	/**
	 * Create the URL leading to the edit page for the catalog item
	 * @param Database_Result $objCatalog the database result holding the current item.
	 * @param string $strAliasField the alias field to use, optional. If empty the routine will try "alias" and if that does not exist fallback to id
	 * @param string $strTable the tablename to use, optional. If empty, the current table will get used.
	 * @param string $strPrependParams parameters that shall be prepended to the /items/ part of the url, optional.
	 * @return string the generated url.
	 */
	protected function generateCatalogEditUrl(Database_Result $objCatalog, $strAliasField='alias', $strTable='', $strPrependParams='')
	{
		// fallback alias field
		$aliasField = 'id';
		if (strlen($strAliasField)
			&& (! $GLOBALS['TL_CONFIG']['disableAlias'])
			&& $this->Database->fieldExists($strAliasField, ($strTable?$strTable:$this->strTable))
			&& strlen($objCatalog->$strAliasField))
		{
			$aliasField = $strAliasField;
		}
		$arrPage = $this->getJumpTo($this->catalog_editJumpTo, true);
		// Link to catalog edit
		return ampersand($this->generateFrontendUrl($arrPage, $strPrependParams . '/items/' . $objCatalog->$aliasField));
	}

	/**
	 * Generate a link and return it as string
	 * @param Database_Result $objCatalog
	 * @param string $strAliasField
	 * @param string $strTable
	 * @param boolean $blnWindow
	 * @param boolean $blnEdit optional link to the edit page? default is the reader page
	 * @param string $strLink optional custom link text
	 */
	protected function generateLink(Database_Result $objCatalog, $strAliasField, $strTable, $blnWindow, $blnEdit=false, $strLink='')
	{
		$linkUrl = (!$blnEdit ? $this->generateCatalogUrl($objCatalog, $strAliasField, $strTable) : $this->generateCatalogEditUrl($objCatalog, $strAliasField, $strTable));
		$strLink = strlen($strLink) ? $strLink : (!$blnEdit ? $GLOBALS['TL_LANG']['MSC']['viewCatalog'] : $GLOBALS['TL_LANG']['MSC']['editCatalog']);
		$strTitle = (!$blnEdit ? $GLOBALS['TL_LANG']['MSC']['viewCatalog'] : $GLOBALS['TL_LANG']['MSC']['editCatalog']);

		return sprintf('<a href="%s" title="%s"%s>%s</a>',
						$linkUrl,
						$strTitle,
						$blnWindow ? ' onclick="this.blur(); window.open(this.href); return false;"' : '',
						$strLink
						);
	}

	/**
	 * Format the catalog value according to its settings and return it as HTML string
	 * @param string $strId identifies the item in the list of items
	 * @param string $strFieldName
	 * @param mixed $value
	 * @param boolean $blnImageLink optional
	 * @param Database_Result $objCatalog optional
	 * @return string
	 */
	protected function formatValue($strId, $strFieldName, $value, $blnImageLink=true, $objCatalog =null)
	{
		$arrFormat = $this->parseValue($strId, $strFieldName, $value, $blnImageLink, $objCatalog);
		return $arrFormat['html'];
	}

	/**
	 * parse the catalog values and return information as an array
	 * @param string $strId identifies the item in the list of items
	 * @param string $strFieldName
	 * @param mixed $value
	 * @param boolean $blnImageLink optional
	 * @param Database_Result $objCatalog optional
	 * @return array
	 */
	protected function parseValue($strId, $strFieldName, $value, $blnImageLink =true, $objCatalog =null)
	{
		$raw = $value;
		$arrItems = deserialize($value, true);
		$arrValues = deserialize($value, true);
		$strHtml = $value;
		$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$strFieldName];
		// deleted field?
		if(!$fieldConf)
			return array();
		
		global $objPage;
		
		if(version_compare(VERSION, '2.10', '>=')
			&& $fieldConf['eval']['rte']
			&& $GLOBALS['TL_CONFIG']['useRTE'])
		{
			$this->import('String');
			// reformat the RTE output to the proper format
			if($objPage->outputFormat == 'xhtml')
			{
				$strHtml = $this->String->toXhtml($strHtml);
			}
			if($objPage->outputFormat == 'html5')
			{
				$strHtml = $this->String->toHtml5($strHtml);
			}
		}

		switch ($fieldConf['eval']['catalog']['type'])
		{
			case 'longtext':
				if (! ($fieldConf['eval']['allowHtml']
							 || $fieldConf['eval']['rte']))
				{
					if ($objPage->outputFormat == 'xhtml')
						$strHtml = nl2br_xhtml($strHtml);
	
					elseif ($objPage->outputFormat == 'html5')
						$strHtml = nl2br_html5($strHtml);
				}
				
				break;
				
			case 'select':
			case 'tags':
				if ($fieldConf['options'])
				{
					$arrItems = trimsplit(',',$raw);
					$selectedValues = array_intersect_key($fieldConf['options'], array_flip($arrItems));
					$arrValues = $selectedValues;
					$strHtml = implode(', ', $arrValues);
				}
				break;

			case 'file':
				$files = $this->parseFiles($strId, $strFieldName, $raw);
				$arrItems = $files['files'];
				$arrValues = $files['src'];
				$strHtml = implode('', $files['html']);
				break;

			case 'url':
				if (strlen($raw))
				{
					// E-mail addresses
					if (preg_match_all('/^(mailto:)?(\w+([_\.-]*\w+)*@\w+([_\.-]*\w+)*\.[a-z]{2,6})$/i', $value, $matches))
					{
						$this->import('String');
						$emailencode = $this->String->encodeEmail($matches[2][0]);
						$arrValues[0] = $emailencode;
						$strHtml = '<a href="mailto:' . $emailencode . '">' . $emailencode . '</a>';
					}
					elseif (preg_match_all('@^(https?://|ftp://)(\w+([_\.-]*\w+)*\.[a-z]{2,6})(/?)@i', $value, $matches))
					{
						$arrValues[0] = $raw;
						$website = $matches[2][0];
						$strHtml = '<a href="'.ampersand($raw).'"'.(preg_match('@^(https?://|ftp://)@i', $value) ? ' onclick="window.open(this.href); return false;"' : '').'>'.$website.'</a>';
					}
				}
				break;
			// allow custom fields
			default:
				if (count($arrHook = $this->parseValueHook($strId, $strFieldName, $value, $blnImageLink, $objCatalog, $fieldConf)))
				{
					$arrItems = $arrHook['items'];
					$arrValues = $arrHook['values'];
					$strHtml = $arrHook['html'];
				}
				break;
		}

		// special formatting
		$formatStr = $fieldConf['eval']['catalog']['formatStr'];
		if (strlen($formatStr))
		{
			$value = $arrValues[0];
			switch ($fieldConf['eval']['catalog']['formatFunction'])
			{
				case 'string':
						$value = sprintf($formatStr, $value);
						break;
				case 'number':
						$decimalPlaces = is_numeric($formatStr) ? intval($formatStr) : 0;
						$value = number_format($value, $decimalPlaces,
								$GLOBALS['TL_LANG']['MSC']['decimalSeparator'],
								$GLOBALS['TL_LANG']['MSC']['thousandsSeparator']);
						break;
				case 'date':
						if (strlen($raw) && $raw !== 0)
						{
							$date = new Date($raw);
							$value = $this->parseDate($formatStr, $date->tstamp);
						}
						else
						{
							$value = '';
						}
						break;
			}
			$arrValues[0] = $value;
			$strHtml = $value;
		}

		// add prefix and suffix format strings
		if (is_array($fieldConf['eval']['catalog']['formatPrePost']) && count($fieldConf['eval']['catalog']['formatPrePost'])>0)
		{
			$strHtml = $fieldConf['eval']['catalog']['formatPrePost'][0].$strHtml.$fieldConf['eval']['catalog']['formatPrePost'][1];
			// no $this->restoreBasicEntities() as this is done in the OutputTemplate
		}

		$return['items'] = $arrItems;
		$return['values'] = $arrValues;
		$return['html'] = $strHtml;

		return $return;
	}

	/**
	 * Invoke the 'parseValue' hook for the field
	 * @param string $strId identifies the item in the catalog output
	 * @param string $k
	 * @param mixed $value
	 * @param boolean $blnImageLink
	 * @param Database_Result $objCatalog can be null
	 * @param array $arrFieldConf
	 * @return array : empty || 'items' => array, 'values' => array, 'html' => string
	 */
	protected function parseValueHook($strId, $k, $value, $blnImageLink, $objCatalog, array $arrFieldConf)
	{
		$result = array();

		if(array_key_exists($arrFieldConf['eval']['catalog']['type'], $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes']))
		{
			// HOOK: try to format the fieldtype as it is a custom added one.
			$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$arrFieldConf['eval']['catalog']['type']];

			if(array_key_exists('parseValue', $fieldType)
			&& is_array($fieldType['parseValue']))
			{
				$html = array();
				$items = array();
				$values = array();
				foreach ($fieldType['parseValue'] as $callback)
				{
					$this->import($callback[0]);
					$arrHook = $this->$callback[0]->$callback[1]($strId, $k, $value, $blnImageLink, $objCatalog, $this, $arrFieldConf);
					$html[] = $arrHook['html'];
					if(is_array($arrHook['items']))
						$items = array_merge($arrHook['items'], $items);
					if(is_array($arrHook['values']))
						$values = array_merge($arrHook['values'], $values);
				}
				// take all hooks into account, the result will be better understood than
				// several hooks overwriting eachother's results
				$result = array (
					'html' => implode('', $html),
					'items' => $items,
					'values' => $values
				);
			}
		}
		return $result;
	}

	/**
	 * parse files into HTML and other information and return as an array
	 * @param string $strId some identification of the item in the current catalog
	 * @param string $k
	 * @param mixed $files either string or array
	 * @return array
	 */

	public function parseFiles($strId, $k, $files)
	{
		$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];

		$blnThumnailOverride = $this->catalog_thumbnails_override
								&& ($this instanceof ModuleCatalogList
									|| $this instanceof ModuleCatalogFeatured
									|| $this instanceof ModuleCatalogRelated
									|| $this instanceof ModuleCatalogReference);
		// setup standard linking
		$showLink = $fieldConf['eval']['catalog']['showLink'];
		// image override
		if ($blnThumnailOverride)
		{
			$showLink = $this->catalog_imagemain_field == $k ? $this->catalog_imagemain_fullsize :
					($this->catalog_imagegallery_field == $k ? $this->catalog_imagegallery_fullsize : ''); // override default
		}
		$sortBy = $blnThumnailOverride ? $this->sortBy : $fieldConf['eval']['catalog']['sortBy'];

		$files = deserialize($files, true);
		$countFiles = count($files);

		if (!is_array($files) || $countFiles < 1)
		{
			return array('files'=>array(),'src'=>array(),'html'=>array());
		}

		// required for parseMetaFile function (in FrontEnd)
		$this->multiSRC = $files;
		$this->arrAux = array();
		// TODO: we also have to clean the array of already processed files here as otherwise $arrAux will not get populated again.
		// We might find some better approach to this in the future rather than cleaning the cache arrays.
		$this->arrProcessed=array();

		$arrFiles = array();
		$arrSource = array();
		$arrValues = array();
		$auxDate = array();

		$counter = 0;
		$allowedDownload = trimsplit(',', strtolower($GLOBALS['TL_CONFIG']['allowedDownload']));
		if (strlen($fieldConf['eval']['extensions']))
		{
			$extensions = trimsplit(',', strtolower($fieldConf['eval']['extensions']));
			$allowedDownload = array_intersect($allowedDownload, $extensions);
		}

		foreach ($files as $file)
		{
			if (!file_exists(TL_ROOT . '/' . $file))
			{
				continue;
			}

			if (is_file(TL_ROOT . '/' . $file))
			{
				$objFile = new File($file);

				$showImage = $objFile->isGdImage && $fieldConf['eval']['catalog']['showImage'];
				if (!$showImage && in_array($objFile->extension, $allowedDownload) || $showImage)
				{
					$class = (($counter == 0) ? ' first' : '')
						. (($counter == ($countFiles -1 )) ? ' last' : '')
						. ((($counter % 2) == 0) ? ' even' : ' odd');

					$this->parseMetaFile(dirname($file), true);
					$strBasename = strlen($this->arrMeta[$objFile->basename][0]) ? $this->arrMeta[$objFile->basename][0] : specialchars($objFile->basename);
					$alt = (strlen($this->arrMeta[$objFile->basename][0]) ? $this->arrMeta[$objFile->basename][0] : ucfirst(str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename))));

					$auxDate[] = $objFile->mtime;

					// images
					if ($showImage)
					{
						$w = $fieldConf['eval']['catalog']['imageSize'][0] ? $fieldConf['eval']['catalog']['imageSize'][0] : '';
						$h = $fieldConf['eval']['catalog']['imageSize'][1] ? $fieldConf['eval']['catalog']['imageSize'][1] : '';
						$m = $fieldConf['eval']['catalog']['imageSize'][2] ? $fieldConf['eval']['catalog']['imageSize'][2] : '';
						if ($blnThumnailOverride)
						{
							$newsize =  deserialize($this->catalog_imagemain_field == $k ? $this->catalog_imagemain_size
								: ($this->catalog_imagegallery_field == $k ? $this->catalog_imagegallery_size : array()) );
							$w = ($newsize[0] ? $newsize[0] : '');
							$h = ($newsize[1] ? $newsize[1] : '');
							$m = ($newsize[2] ? $newsize[2] : '');
						}
						$src = $this->getImage($this->urlEncode($file), $w, $h, $m);
						$size = getimagesize(TL_ROOT . '/' . urldecode($src));
						$arrSource[$file] = array
						(
							'src'	=> $src,
							'alt'	=> $alt,
							'lb'	=> 'lb'.$strId,
							'w' 	=> $size[0],
							'h' 	=> $size[1],
							'wh'	=> $size[3],
							'caption' => (strlen($this->arrMeta[$objFile->basename][2]) ? $this->arrMeta[$objFile->basename][2] : ''),
							'metafile' => $this->arrMeta[$objFile->basename],
						);
						$tmpFile = '<img src="'.$src.'" alt="'.$alt.'" '.$size[3].' />';
						if ($showLink)
						{
							// we have to supply the catalog id here as we might have more than one catalog with a field with the same name
							// which will cause the lightbox to display the images for items with the same id in both.
							if(version_compare(VERSION, '2.11', '>='))
							{
								$tmpFile = '<a data-lightbox="lb' . $this->strTable . $strId . '" href="'.$file.'" title="'.$alt.'">'.$tmpFile.'</a>';
							} else {
								$tmpFile = '<a rel="lightbox[lb' . $this->strTable . $strId . ']" href="'.$file.'" title="'.$alt.'">'.$tmpFile.'</a>';
							}
						}
					}
					// files
					elseif ($showLink)
					{
						$text = $strBasename;
						$url = $this->Environment->request . (($GLOBALS['TL_CONFIG']['disableAlias'] || strpos($this->Environment->request, '?') !== false) ? '&amp;' : '?') . 'file=' . $this->urlEncode($file);
						$icon = 'system/themes/' . $this->getTheme() . '/images/' . $objFile->icon;
						$sizetext = '('.$this->getReadableSize($objFile->filesize, 1).')';
						$arrSource[$file] = array
						(
							'title' => $strBasename,
							'url' => $url,
							'alt'	=> $alt,
							'caption' => (strlen($this->arrMeta[$objFile->basename][2]) ? $this->arrMeta[$objFile->basename][2] : ''),
							'size' => $objFile->filesize,
							'sizetext' => $sizetext,
							'icon' => $icon,
							'metafile' => $this->arrMeta[$objFile->basename],
						);
						$iconfile = '<img src="'.$icon.'" alt="'.$alt.'" />';
						$tmpFile = $iconfile.' <a href="'.$url.'" title="'.$alt.'">'.$text.' '.$sizetext.'</a>';
					}

					$arrFiles[$file] = $file;
					$arrValues[$file] = '<span class="'.($showImage ? 'image' : 'file').$class.'">'.$tmpFile.'</span>';
					$counter ++;
				}
				continue;
			}
			else if (is_dir(TL_ROOT . '/' . $file))
			{
				// Folders
				$subfiles = scan(TL_ROOT . '/' . $file);
				$this->parseMetaFile($file);
				foreach ($subfiles as $subfile)
				{
					if (is_file(TL_ROOT . '/' . $file . '/' . $subfile))
					{
						$objFile = new File($file . '/' . $subfile);

						$showImage = $objFile->isGdImage && $fieldConf['eval']['catalog']['showImage'];
						if (!$showImage && in_array($objFile->extension, $allowedDownload) || $showImage)
						{
							$class = (($counter == 0) ? ' first' : '') . ((($counter % 2) == 0) ? ' even' : ' odd');

							$strBasename = strlen($this->arrMeta[$objFile->basename][0]) ? $this->arrMeta[$objFile->basename][0] : specialchars($objFile->basename);
							$alt = (strlen($this->arrMeta[$objFile->basename][0]) ? $this->arrMeta[$objFile->basename][0] : ucfirst(str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename))));

							$auxDate[] = $objFile->mtime;

							if ($showImage)
							{
								$w = $fieldConf['eval']['catalog']['imageSize'][0] ? $fieldConf['eval']['catalog']['imageSize'][0] : '';
								$h = $fieldConf['eval']['catalog']['imageSize'][1] ? $fieldConf['eval']['catalog']['imageSize'][1] : '';
								$m = $fieldConf['eval']['catalog']['imageSize'][2] ? $fieldConf['eval']['catalog']['imageSize'][2] : '';
								if ($blnThumnailOverride)
								{
									$newsize =  deserialize($this->catalog_imagemain_field == $k ? $this->catalog_imagemain_size
										: ($this->catalog_imagegallery_field == $k ? $this->catalog_imagegallery_size : array()) );
									$w = ($newsize[0] ? $newsize[0] : '');
									$h = ($newsize[1] ? $newsize[1] : '');
                                    $m = ($newsize[2] ? $newsize[2] : '');
								}
								$src = $this->getImage($this->urlEncode($file . '/' . $subfile), $w, $h, $m);
								$size = getimagesize(TL_ROOT . '/' . urldecode($src));

								$arrSource[$file . '/' . $subfile] = array
								(
									'src'	=> $src,
									'alt'	=> $alt,
									'lb'	=> 'lb'.$strId,
									'w' 	=> $size[0],
									'h' 	=> $size[1],
									'wh'	=> $size[3],
									'caption' => (strlen($this->arrMeta[$objFile->basename][2]) ? $this->arrMeta[$objFile->basename][2] : ''),
									'metafile' => $this->arrMeta[$objFile->basename],
								);

								$tmpFile = '<img src="'.$src.'" alt="'.$alt.'" '.$size[3].' />';

								if ($showLink)
								{
									// we have to supply the catalog id here as we might have more than one catalog with a field with the same name here.
									if(version_compare(VERSION, '2.11', '>='))
									{
										$tmpFile = '<a data-lightbox="lb' . $this->strTable . $strId . '" title="'.$alt.'" href="'.$file . '/' . $subfile.'">'.$tmpFile.'</a>';
									} else {
										$tmpFile = '<a rel="lightbox[lb' . $this->strTable . $strId . ']" title="'.$alt.'" href="'.$file . '/' . $subfile.'">'.$tmpFile.'</a>';
									}
								}

							}
							// files
							elseif ($showLink)
							{
								$text = $strBasename;
								$url = $this->Environment->request . (($GLOBALS['TL_CONFIG']['disableAlias'] || !$GLOBALS['TL_CONFIG']['rewriteURL']
&& count($_GET) || strlen($_GET['page'])) ? '&amp;' : '?'). 'file=' . $this->urlEncode($file . '/' . $subfile);
								$icon = 'system/themes/' . $this->getTheme() . '/images/' . $objFile->icon;
								$sizetext = '('.number_format(($objFile->filesize/1024), 1, $GLOBALS['TL_LANG']['MSC']['decimalSeparator'], $GLOBALS['TL_LANG']['MSC']['thousandsSeparator']).' kB)';

								$arrSource[$file . '/' . $subfile] = array
								(
									'title' => $strBasename,
									'url' => $url,
									'alt'	=> $alt,
									'caption' => (strlen($this->arrMeta[$objFile->basename][2]) ? $this->arrMeta[$objFile->basename][2] : ''),
									'size' => $objFile->filesize,
									'sizetext' => $sizetext,
									'icon' => $icon,
									'metafile' => $this->arrMeta[$objFile->basename],
								);
								$iconfile = '<img src="'.$icon.'" alt="'.$alt.'" />';
								$tmpFile = $iconfile.' <a href="'.$url.'" title="'.$alt.'">'.$text.' '.$sizetext.'</a>';
							}

							$arrFiles[$file . '/' . $subfile] = $file . '/' . $subfile;
							$arrValues[$file . '/' . $subfile] = '<span class="'.($showImage ? 'image' : 'file').$class.'">'.$tmpFile.'</span>';
							$counter ++;
						}
					}
				}
			}
		}

		// Sort array
		$files = array();
		$source = array();
		$values = array();

		switch ($sortBy)
		{
			default:
			case 'name_asc':
				uksort($arrFiles, 'basename_natcasecmp');
				break;

			case 'name_desc':
				uksort($arrFiles, 'basename_natcasercmp');
				break;

			case 'date_asc':
				array_multisort($arrFiles, SORT_NUMERIC, $auxDate, SORT_ASC);
				break;

			case 'date_desc':
				array_multisort($arrFiles, SORT_NUMERIC, $auxDate, SORT_DESC);
				break;

			case 'meta':
				foreach ($this->arrAux as $aux)
				{
					$k = array_search($aux, $arrFiles);
					if ($k !== false)
					{
						$files[] = $arrFiles[$k];
						$source[] = $arrSource[$k];
						$values[] = $arrValues[$k];
					}
				}
				break;

			case 'random':
				$keys = array_keys($arrFiles);
				shuffle($keys);
				foreach($keys as $key)
				{
					$files[$key] = $arrFiles[$key];
				}
				$arrFiles = $files;
				break;
		}
		if ($sortBy != 'meta')
		{
			// re-sort the values
			foreach($arrFiles as $k=>$v)
			{
				$files[] = $arrFiles[$k];
				$source[] = $arrSource[$k];
				$values[] = $arrValues[$k];
			}
		}

		$return['files']	= $files;
		$return['src'] 		= $source;
		$return['html']		= $values;

		return $return;
	}

	/**
	 * Replace Catalog InsertTags in a text
	 * @param string $strValue the text
	 * @param array $arrCatalog a catalog item
	 * @return string $strValue with replaced {{catalog::…}} tags
	 */
	public static function replaceCatalogTags($strValue, array $arrCatalog)
	{
		$strValue = trim($strValue);

		// search for catalog tags
		$tags = array();
		preg_match_all('/{{[^}]+}}/i', $strValue, $tags);

		// Replace tags of type {{catalog::fieldname}}
		foreach ($tags[0] as $tag)
		{
			$elements = explode('::', trim(str_replace(array('{{', '}}'), array('', ''), $tag)));

			// {{catalog::fieldname}}
			if (strtolower($elements[0]) == 'catalog')
			{
				$key = $elements[1];
				if (array_key_exists($key, $arrCatalog))
				{
					$strValue = str_replace($tag, str_replace("\n", "<br>", $arrCatalog[$key]), $strValue);
				}
			}
		}
		
		return $strValue;
	}

	/**
	 * Generate Front-end Url with only catalog ID as parameter
	 * @param int $field optional
	 * @param int $value optional
	 * @return string
	 */
	public function generateCatalogNavigationUrl($field=false, $value=false)
	{
		$jumpTo= $this->catalog_jumpTo?$this->catalog_jumpTo:$this->jumpTo;
		$pageRow=$this->getJumpTo($jumpTo, true);

		if (!$pageRow)
		{
			return ampersand($this->Environment->request, ENCODE_AMPERSANDS);
		}

		if($field !== false)
		{
			$strParams = $GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;' . $field . '=' . $value  : '/' . $field . '/' . $value;
		} else {
			$strParams = '';
		}

		// Return link to catalog reader page with item alias
		return ampersand($this->generateFrontendUrl($pageRow, $strParams));
	}

	/**
	 * Generates the catalog navigation
	 * @param int $id
	 * @param int $level
	 * @param boolean $blnTags optional
	 * @param string $value optional
	 * @return string HTML code for the navigation
	 */
	protected function renderCatalogNavigationItems($id, $level=1, $blnTags=false, $value='')
	{
		$aliasField = $this->getAliasField($this->strTable);

		$strWhere = $blnTags ? $this->strTable.'.id=tl_catalog_tag_rel.itemid AND fieldid='.$GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->catalog_navigation]['eval']['catalog']['fieldId'].' AND valueid='.$id.'' : $this->catalog_navigation."=?";
		// TODO: add the parsed filter URL here.
		if(!BE_USER_LOGGED_IN && $this->publishField)
		{
			$strWhere .=' AND '.$this->publishField.'=1';
		}
		// query database
		$objNodes = $this->Database->prepare('SELECT DISTINCT '.$this->strTable.'.*, tl_catalog_types.jumpTo AS parentJumpTo FROM '.$this->strTable.($blnTags?', tl_catalog_tag_rel':'').', tl_catalog_types WHERE tl_catalog_types.id='.$this->strTable.'.pid AND '.$strWhere)
										->execute($id);
		if ($objNodes->numRows < 1)
		{
			return '';
		}

		$items = array();

		// Determine the layout template
		if (!strlen($this->navigationTpl))
		{
			$this->navigationTpl = 'nav_default';
		}

		// Overwrite template
		$objTemplate = new FrontendTemplate($this->navigationTpl);

		$objTemplate->type = get_class($this);
		$objTemplate->level = 'level_' . $level++;

		// Get page object
		global $objPage;

		$showField = $this->catalog_show_field;

		// Browse items
		while($objNodes->next())
		{
			$href = $this->generateCatalogUrl($objNodes, $aliasField, $this->strTable, sprintf('/%s/%s', $this->catalog_navigation, $value?$value:$id));

			// Active field
			if ($this->Input->get('items') == $objNodes->id || $this->Input->get('items') == $objNodes->$aliasField)
			{
				$strClass =  trim('item' . (strlen($objJump->cssClass) ? ' ' . $objJump->cssClass : ''));

				$items[] = array
				(
					'isActive' => true,
					'subitems' => $subitems,
					'class' => (strlen($strClass) ? $strClass : ''),
					'title' => specialchars($objNodes->$showField),
					'link' => $objNodes->$showField,
					'href' => $href,
				);

				continue;
			}

			$strClass = trim('item' . (strlen($objJump->cssClass) ? ' ' . $objJump->cssClass : ''));

			$items[] = array
			(
				'isActive' => false,
				'subitems' => $subitems,
				'class' => (strlen($strClass) ? $strClass : ''),
				'title' => specialchars($objNodes->$showField),
				'link' => $objNodes->$showField,
				'href' => $href,
			);
		}

		// Add classes first and last
		if (count($items))
		{
			$last = count($items) - 1;
			$items[0]['class'] = trim($items[0]['class'] . ' first');
			$items[$last]['class'] = trim($items[$last]['class'] . ' last');
		}

		$objTemplate->items = $items;
		return count($items) ? $objTemplate->parse() : '';
	}

	/**
	 * used internally by internalRenderCatalogNavigation() to hold the trail of the currently selected item.
	 */
	protected $arrTrail=array();
	/**
	 * used internally by internalRenderCatalogNavigation() to hold the field used to navigate.
	 */
	protected $objNavField=NULL;

	/**
	 * Recursively compile the catalog navigation menu and return it as HTML string
	 * @param int
	 * @param int
	 * @return string
	 */
	protected function internalRenderCatalogNavigation($pid, $level, $strActive=NULL)
	{
		// Get internal page (from parent catalog)
		$arrJump = $this->getJumpTo($this->jumpTo, false);
		$ids = ($pid == 0) ? ($this->objNavField->limitItems && $this->objNavField->items ? $this->objNavField->items : array(0)) : array($pid);
		$strRoot = ((!$this->objNavField->blnChildren && $level == 1) ? 'id' : 'pid');
		if ($this->objNavField->treeView)
		{
			if($this->objNavField->type == 'tags')
			{
				$strItemCount = '(SELECT COUNT(t.id) AS count FROM tl_catalog_tag_rel AS t RIGHT JOIN ' . $this->strTable . ' AS c ON (t.itemid=c.id) WHERE t.valueid=o.id AND t.fieldid=' . $this->objNavField->id . (!BE_USER_LOGGED_IN && $this->publishField ? ' AND c.' . $this->publishField.'=1' : '') . ') AS itemCount';
			} else {
				$strItemCount = '(SELECT COUNT(t.id) AS count FROM ' . $this->strTable . ' AS t WHERE ' .
				(!BE_USER_LOGGED_IN && $this->publishField ? 't.'.$this->publishField.'=1 AND ' : '') .
				$this->catalog_navigation . ' IN (SELECT id FROM  ' . $this->objNavField->sourceTable . ' AS t WHERE pid=o.id) OR '.$this->catalog_navigation.'=o.id) AS itemCount';
			}
			$objNodes = $this->Database->execute('SELECT '.$strRoot.', id, ' . $this->objNavField->valueField .
											', (SELECT COUNT(*) FROM '. $this->objNavField->sourceTable .' i WHERE i.pid=o.id) AS childCount, ' .
											$this->objNavField->sourceColumn . ' AS name, ' .
											$strItemCount .
											' FROM '. $this->objNavField->sourceTable. ' o WHERE '.$strRoot.' IN ('.implode(',',$ids).') ORDER BY '. $this->objNavField->sort);
		}
		if (!$this->objNavField->treeView || ($objNodes->numRows == 0 && $level == 1))
		{
			$objNodes = $this->Database->execute('SELECT id, '.$this->objNavField->valueField.', 0 AS childCount, '. $this->objNavField->sourceColumn .' AS name, 0 AS itemCount FROM '. $this->objNavField->sourceTable .' ORDER BY '.$this->objNavField->sort);
		}
		// no entries for the given pid
		if (!$objNodes->numRows)
			return '';

		$valueField=$this->objNavField->valueField;

		$items = array();
		// Overwrite template
		$objTemplate = new FrontendTemplate($this->navigationTpl);
		$objTemplate->type = get_class($this);
		$objTemplate->level = 'level_' . $level++;
		// Browse field nodes

		while($objNodes->next())
		{
			$subitems = '';
			$strClass = '';
			// setup field and value
			$field = $this->catalog_navigation;
			$value = $objNodes->$valueField;
			$isTrail = in_array($objNodes->id, $this->arrTrail);
			$href = $this->generateCatalogNavigationUrl($field, $value, sprintf('/%s/%s', $field, $value));

 			if (!$this->showLevel || $this->showLevel >= $level || (!$this->hardLimit && $isTrail))
			{
				// if current field value is selected, display children
				if ($this->catalog_show_items && $strActive == $objNodes->$valueField)
				{
					$subitems .= $this->renderCatalogNavigationItems($objNodes->id, $level, ($this->objNavField->type == 'tags'), $value);
				}
				if (count($objNodes->childCount) && $this->objNavField->blnChildren)
				{
					$subitems .= $this->internalRenderCatalogNavigation($objNodes->id, $level, $strActive);
				}
			}

			$strClass .= trim((strlen($subitems) ? 'submenu' : '')
							. (strlen($arrJump['cssClass']) ? ' ' . $arrJump['cssClass'] : '')
							. ($isTrail ? ' trail' : '')
							. ' ' . (count($items)%2 ? 'odd' : 'even')
							);
			// Active field
			if ($strActive == $value)
			{
				$items[] = array
				(
					'isActive' => !strlen($this->Input->get('items')),
					'subitems' => $subitems,
					'subitemcount' => $objNodes->itemCount,
					'class' => (strlen($strClass) ? $strClass : ''),
					'pageTitle' => specialchars($arrJump['pageTitle']),
					'title' => specialchars($objNodes->name),
					'link' => $objNodes->name,
					'href' => $href,
					'alias' => $arrJump['alias'],
					'target' => (($arrJump['type'] == 'redirect' && $arrJump['target']) ? ' window.open(this.href); return false;' : ''),
					'description' => str_replace(array("\n", "\r"), array(' ' , ''), $arrJump['description']),
					'accesskey' => $arrJump['accesskey'],
					'tabindex' => $arrJump['tabindex'],
					'itemAlias' => $value
				);
				// move on to next childnode
				continue;
			}

			if(!$this->objNavField->treeView || ($objNodes->itemCount || $objNodes->childCount))
			{
				$items[] = array
				(
					'isActive' => false,
					'subitems' => $isTrail?$subitems:'',
					'subitemcount' => $objNodes->itemCount,
					'class' => (strlen($strClass) ? $strClass : ''),
					'pageTitle' => specialchars($arrJump['pageTitle']),
					'title' => specialchars($objNodes->name),
					'link' => specialchars($objNodes->name),
					'href' => $href,
					'alias' => $arrJump['alias'],
					'target' => (($arrJump['type'] == 'redirect' && $arrJump['target']) ? ' window.open(this.href); return false;' : ''),
					'description' => str_replace(array("\n", "\r"), array(' ' , ''), $arrJump['description']),
					'accesskey' => $arrJump['accesskey'],
					'tabindex' => $arrJump['tabindex'],
					'itemAlias' => $value
				);
			}
		}

		// Add classes first and last
		if (count($items))
		{
			$last = count($items) - 1;

			$items[0]['class'] = trim($items[0]['class'] . ' first');
			$items[$last]['class'] = trim($items[$last]['class'] . ' last');
		}
		$objTemplate->items = $items;
		return count($items) ? $objTemplate->parse() : '';
	}

	protected function determineNavRootFromReferer($strNavField, $skipRawReferer=false)
	{
		$HTTPReferer = (!$skipRawReferer) ? $this->Environment->httpReferer : $this->getReferer();

		// We check the real HTTP referer first, as we might have multiple tabs open in
		// this environment and therefore want to use the "real" referer.
		if((!$skipRawReferer) && preg_match('#[\/?&]'.$strNavField.'#', $HTTPReferer))
		{
			$strRequest = str_replace($this->Environment->base, '', $HTTPReferer);
			if(!$GLOBALS['TL_CONFIG']['disableAlias'])
			{
				$strRequest = preg_replace('/\?.*$/i', '', $strRequest);
				$strRequest = preg_replace('/' . preg_quote($GLOBALS['TL_CONFIG']['urlSuffix'], '/') . '$/i', '', $strRequest);
				$arrFragments = explode('/', $strRequest);
				// Skip index.php
				if (strtolower($arrFragments[0]) == 'index.php')
				{
					array_shift($arrFragments);
				}
			} else {
				// TODO: handle disabled aliases here.
				$arrFragments = array();
			}
			// skip page and search for our param.
			for($i=1;$i<count($arrFragments);$i+=2)
			{
				if($arrFragments[$i]==$strNavField)
				{
					return $arrFragments[$i+1];
				}
			}
		}
		if(!$skipRawReferer)
		{
			return $this->determineNavRootFromReferer($strNavField, true);
		}
	}

	/**
	 * Look up all needed stuff and then call the recursive function internalRenderCatalogNavigation
	 * @param integer $pid
	 * @return string
	 */
	protected function renderCatalogNavigation($pid)
	{
		$this->arrTrail=array();
		// get reference table and column
		$objFields = $this->Database->prepare('SELECT * FROM tl_catalog_fields WHERE pid=? AND colName=?')
											->limit(1)
											->execute($this->catalog, $this->catalog_navigation);
		if (!$objFields->numRows)
			return '';
		$this->objNavField = new stdClass();
		$this->objNavField->id = $objFields->id;
		$this->objNavField->sourceTable = $objFields->itemTable;
		$this->objNavField->sourceColumn = $objFields->itemTableValueCol;
		$this->objNavField->blnChildren = $objFields->childrenSelMode;
		$this->objNavField->limitItems = $objFields->limitItems;
		$this->objNavField->items = deserialize($objFields->items);
		$this->objNavField->valueField = $this->getAliasField($this->objNavField->sourceTable);
		$this->objNavField->type = $objFields->type;
		// check if this tree has a pid or a flat table
		$this->objNavField->treeView = $this->Database->fieldExists('pid', $this->objNavField->sourceTable);
		$this->objNavField->sort = $this->Database->fieldExists('sorting', $this->objNavField->sourceTable) ? 'sorting' : $this->objNavField->sourceColumn;
		// Determine the layout template
		if (!strlen($this->navigationTpl))
		{
			$this->navigationTpl = 'nav_default';
		}

		// root is given via URL
		if($this->Input->get($this->catalog_navigation))
		{
			$root=$this->Input->get($this->catalog_navigation);
		}
		// root not given via URL but we have an select field and an item is being requested.
		else if($this->Input->get('items') && ($this->objNavField->type == 'select'))
		{
			// SELECT fields
			$value=$this->Input->get('items');
			$strAlias = $this->strAliasField ? $this->strAliasField : (is_numeric($value) ? 'id' : '');
			if(strlen($strAlias))
			{
				$objItem = $this->Database->prepare('SELECT '.$this->objNavField->valueField.' AS alias FROM '. $this->objNavField->sourceTable. ' WHERE id=(SELECT '.$this->catalog_navigation.' FROM '.$this->strTable.' WHERE '.(!BE_USER_LOGGED_IN && $this->publishField ? $this->publishField.'=1 AND ' : ''). $strAlias . '=?)')
											->limit(1)
											->execute($value);
				if ($objItem->numRows)
				{
					$root=$objItem->alias;
				}
			}
		}
		// no root via URL but an item requested, let's try to get the root from the referer.
		else if($this->Input->get('items') && !$this->Input->get($this->catalog_navigation))
		{
			// check if in referer is something mentioned.
			if(preg_match('#[\/?&]'.$this->catalog_navigation.'#', $this->Environment->httpReferer))
			{
				$root = $this->determineNavRootFromReferer($this->catalog_navigation);
			}
		}
		if ($this->objNavField->treeView && $root)
		{
			// determine all parents
			$objRoot = $this->Database->prepare('SELECT id,pid FROM '. $this->objNavField->sourceTable. ' WHERE '.$this->objNavField->valueField.'=?')
												->limit(1)
												->execute($root);
			$parents=array($objRoot->id);
			// root found, we can now find the parents.
			while($objRoot->numRows && $objRoot->pid)
			{
				$parents[]=$objRoot->pid;
				$objRoot = $this->Database->prepare('SELECT pid FROM '. $this->objNavField->sourceTable. ' WHERE id='.$objRoot->pid)
													->limit(1)
													->execute();
			}
			foreach(array_reverse($parents) as $k=>$v)
				$this->arrTrail[$k+1]=$v;
		}
		if($this->levelOffset)
		{
			// start level specified but not in trail -> do not output
			if(!$this->arrTrail[$this->levelOffset])
				return '';
			$pid = $this->arrTrail[$this->levelOffset];
		}
		return $this->internalRenderCatalogNavigation($pid, 1, $root);
	}
		/**
	 * List and generate comment form
	 * @param Database_Result $objCatalog
	 * @return void
	 */
	public function processComments(Database_Result $objCatalog)
	{
		// Comments
		$objArchive = $this->Database->prepare('SELECT * FROM tl_catalog_types WHERE id=?')
									 ->limit(1)
									 ->execute($objCatalog->pid);

		if ($objArchive->numRows < 1
			|| !$objArchive->allowComments
			|| !in_array('comments', $this->Config->getActiveModules()))
		{
			$this->Template->allowComments = false;
			return;
		}

		$this->Template->allowComments = true;
		$this->import('Comments');
		$objConfig = new stdClass();
		$objConfig->perPage = $objArchive->perPage;
		$objConfig->order = $objArchive->sortOrder;
		$objConfig->template = $objArchive->template;
		$objConfig->requireLogin = $objArchive->requireLogin;
		$objConfig->disableCaptcha = $objArchive->disableCaptcha;
		$objConfig->bbcode = $objArchive->bbcode;
		$objConfig->moderate = $objArchive->moderate;

		// issue #1690 hide member details not working anymore.
		if($objArchive->hideMember && ($this->Input->post('FORM_SUBMIT')=='com_'. $this->strTable .'_'. $objCatalog->id))
		{
			// we have to trick the comments module into beliving that the user inserted his name and email as those are hardcoded mandatory.
			$this->import('FrontendUser', 'Member');
			$this->Input->setPost('name', trim($this->Member->firstname . ' ' . $this->Member->lastname));
			$this->Input->setPost('email', trim($this->Member->email));
		}

		// TODO: add notifies here.
		$arrNotifies=array($GLOBALS['TL_ADMIN_EMAIL']);
		$this->Comments->addCommentsToTemplate($this->Template, $objConfig, $this->strTable, $objCatalog->id, $arrNotifies);

		if($objArchive->disableWebsite)
		{
			$fields = $this->Template->fields;
			unset($fields['website']);
			$this->Template->fields=$fields;
		}
	}

	/**
	 * Finishes compiling by writing an error message to the template
	 * @param string $strMessage
	 */
	protected function compileError($strMessage)
	{
		global $objPage;
		$this->Template->error = $strMessage;

		// Do not index the page
		$objPage->noSearch = 1;
		$objPage->cache = 0;
	}

	/**
	 * Finishes compiling by returning the catalogInvalid error
	 * @return void
	 */
	protected function compileInvalidCatalog()
	{
		$result = $this->compileError($GLOBALS['TL_LANG']['MSC']['catalogInvalid']);
		// Send 404 header
		header('HTTP/1.0 404 Not Found');
		return $result;
	}

	/**
	 * Finishes compiling by returning the catalogItemInvalid error
	 * @return void
	 */
	protected function compileInvalidItem()
	{
		$result = $this->compileError($GLOBALS['TL_LANG']['ERR']['catalogItemInvalid']);
		// Send 404 header
		header('HTTP/1.0 404 Not Found');
		return $result;
	}

	/**
	 * Fetches information about the catalogType from the Database
	 * or returns null if it's invalid
	 * @param int $intId
	 * @return Database_Result for the catalogType
	 * || null if catalogType is not valid
	 */
	protected function getValidCatalogType($intId)
	{
		$objCatalogType = $this->Database->prepare('SELECT * FROM tl_catalog_types WHERE id=?')
										->execute($intId);
		// validity check
		if (!$objCatalogType->numRows || !strlen($objCatalogType->tableName))
		{
			return null;
		}
		else
		{
			return $objCatalogType;
		}
	}

	/**
	 * Writes general information into the template
	 * @return void
	 */
	protected function basicVarsToTemplate()
	{
		$this->Template->catalog = '';
		$this->Template->gobackDisable = $this->catalog_goback_disable;
		$this->Template->referer = $this->getReferer(ENCODE_AMPERSANDS);
		$this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
	}
	
	/**
	 * Fetches all items which should be displayed from the database
	 *
	 * @pre isset($this->objCatalogType)
	 * @param array $arrFields which fields show be fetched, additionally to $this->systemColumns
	 * @param string $strWhere optional part for the WHERE clause
	 * @param array $arrParams optional to pass to the statement
	 * @param string $strOrder optional part for the ORDER BY clause
	 * @param int $intLimit optional to how many items?
	 * @param int $intOffset optional start from which item?
	 * @param array $arrJoins all TABLE JOINs that shall also be applied as array of strings like: 'LEFT JOIN tl_something ON ({{table}}.id=tl_something.ref_id)') - the token {{table}} will get replaced automatically.
	 * @return Database_Result
	 */
	protected function fetchCatalogItems(array $arrFields, $strWhere ='',
																				array $arrParams =array(),
																				$strOrder ='', $intLimit =0,
																				$intOffset =0, array $arrJoins=array())
	{
		$table = $this->objCatalogType->tableName;
	  $arrFields = $this->processFieldSQL($arrFields, $table);
	  
	  // prepend columns to minimize the possibility of collisions when using JOINs
	  foreach ($this->systemColumns as $sysField)
	  {
	    $arrFields[] = sprintf('%s.%s AS %s',
	        $table, $sysField, $sysField);
	  }
	
	  if($this->objCatalogType->aliasField)
	    $arrFields[] = $this->objCatalogType->aliasField;
	  
	  $strJoins = '';
	  if($arrJoins)
	    $strJoins = str_replace('{{table}}', $table, implode(' ', $arrJoins));
	  
	  $strOrder = strlen($strOrder) ? " ORDER BY " . $strOrder : "";
	  $strWhereOrder = ($strWhere?" AND " . $strWhere:'') . $strOrder;
	  
	  // pid
	  $params = array($this->objCatalogType->id);
	  $params = array_merge($params, $arrParams);
	  	  
	  // Run Query
	  $objCatalogStmt = $this->Database->prepare(sprintf('SELECT %1$s,
	      (SELECT name FROM tl_catalog_types WHERE tl_catalog_types.id=%2$s.pid) AS catalog_name,
	      (SELECT jumpTo FROM tl_catalog_types WHERE tl_catalog_types.id=%2$s.pid) AS parentJumpTo
	      FROM %2$s %3$s WHERE pid=? %4$s',
	      implode(',', $arrFields),
	      $table,
	      $strJoins,
	      $strWhereOrder));
	  
	  // Limit result
	  if ($intLimit > 0)
	    $objCatalogStmt->limit($intLimit, $intOffset);
	  
	  return $objCatalogStmt->execute($params);
	}


	/**
	 * Fetches the requested item from the Database
	 * based on the id or the alias from the request
	 * If $arrFields is given, the field names are converted to SQL statements
	 * when applicable (calc fields)
	 * @param array $arrFields fields to get from DB
	 * @return Database_Result with all item's fields + 'catalog_name' + 'parentJumpTo'
	 */
	protected function fetchCatalogItemFromRequest(array $arrFields)
	{
		$items = $this->Input->get('items');
		$aliasField = 'id';
		
		if ((! is_numeric($items)
				&& strlen($this->strAliasField)))
			$aliasField = $this->strAliasField;
		
		$where = $aliasField . '=?';
		
		return $this->fetchCatalogItems($arrFields, $where, array($items));
	}
	
	/**
	 * Set the tableless property to true for widgets
	 * @post foreach (result as $widget) $widget->tableless == true
	 * @param array $arrWidgets (mixed $k => Widget $widget)
	 * @return array (mixed $k => Widget $widget)
	 */
	protected static function tablelessWidgets(array $arrWidgets) {
	  $result = array();
	
	  foreach ($arrWidgets as $k => $widget)
	  {
	    $widget->tableless = true;
	    $result[$k] = $widget;
	  }
	
	  return $result;
	}
}
?>