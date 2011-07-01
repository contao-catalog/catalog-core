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
 * Class ModuleCatalog
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
 *
 */
abstract class ModuleCatalog extends Module
{


	/**
	 * Tablename String
	 * @var string
	 */
	protected	$strTable;

	/**
	 * Name of the alias field
	 * @var string
	 */
	protected	$strAliasField;

	/**
	 * Search String
	 * @var string
	 */
	protected	$strSearch 	= 'search';

	/**
	 * Sort String
	 * @var string
	 */
	protected	$strSort 		= 'sort';

	/**
	 * OrderBy String
	 * @var string
	 */
	protected	$strOrderBy	= 'orderby';


	protected $systemColumns = array('id', 'pid', 'sorting', 'tstamp');


	protected	$arrTree;	

	protected	$cacheJumpTo;	

	public function generate()
	{
		if (!strlen($this->catalog))
		{
			return '';
		}

		// get DCA
		$objCatalog = $this->Database->prepare('SELECT * FROM tl_catalog_types WHERE id=?')
				->limit(1)
				->execute($this->catalog);
		
		if ($objCatalog->numRows > 0 && $objCatalog->tableName)
		{
			$this->strTable = $objCatalog->tableName;
			$this->strAliasField=$objCatalog->aliasField;
			$this->publishField=$objCatalog->publishField;

			// dynamically load dca for catalog operations
			$this->Import('Catalog');
			if(!$GLOBALS['TL_DCA'][$objCatalog->tableName]['Cataloggenerated'])
			{
				// load default language
				$GLOBALS['TL_LANG'][$objType->tableName] = is_array($GLOBALS['TL_LANG'][$objType->tableName])
													 ? Catalog::array_replace_recursive($GLOBALS['TL_LANG']['tl_catalog_items'], $GLOBALS['TL_LANG'][$objType->tableName])
													 : $GLOBALS['TL_LANG']['tl_catalog_items'];
				// load dca
				$GLOBALS['TL_DCA'][$objCatalog->tableName] = 
					is_array($GLOBALS['TL_DCA'][$objCatalog->tableName])
						? Catalog::array_replace_recursive($this->Catalog->getCatalogDca($this->catalog), $GLOBALS['TL_DCA'][$objCatalog->tableName])
						: $this->Catalog->getCatalogDca($this->catalog);
				$GLOBALS['TL_DCA'][$objCatalog->tableName]['Cataloggenerated'] = true;
			}
		}

		// Send file to the browser
		$blnDownload = ($this instanceof ModuleCatalogList || $this instanceof ModuleCatalogFeatured || $this instanceof ModuleCatalogRelated || $this instanceof ModuleCatalogReference || $this instanceof ModuleCatalogReader); 
		if ($blnDownload && strlen($this->Input->get('file')) && $this->catalog_visible)
		{
			foreach ($this->catalog_visible as $k)
			{
				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];
				if ($fieldConf['eval']['catalog']['type'] == 'file' && !$fieldConf['eval']['catalog']['showImage'])
				{
					// check file in Catalog
					$objDownload = $this->Database->prepare('SELECT id FROM '.$this->strTable.' WHERE '.(!BE_USER_LOGGED_IN && $this->publishField ? $this->publishField.'=1 AND ' : '').'(LOCATE(?,'.$k.')>0 OR LOCATE(?,'.$k.')>0)')
							->limit(1)
							->execute($this->Input->get('file'), dirname($this->Input->get('file')));
					
					if ($objDownload->numRows)
					{
						$this->sendFileToBrowser($this->Input->get('file'));
					}
				}
			}
		}

		return parent::generate();
	}

	protected function getModulesForThisPage()
	{
		if($this->cachePageModules)
			return $this->cachePageModules;
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

	protected function getCatalogFields($arrTypes=false)
	{
		if(!$arrTypes)
			$arrTypes=$GLOBALS['BE_MOD']['content']['catalog']['typesCatalogFields'];
		$fields = array();
		$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=? ORDER BY sorting")
							->execute($this->catalog);

		while ($objFields->next())
		{
			if(!in_array($objFields->type, $arrTypes))
				continue;
			$fields[$objFields->colName] = array 
			(
				'label' => $objFields->name,
				'type' => $objFields->type,
			);

		}
		return $fields;
	}

	protected function getTree()
	{
		if ($this->type != 'catalogfilter' || !$this->catalog_filter_enable)
		{
			return array();
		}
		$tree = array();
		if($this->catalog_filters) {
			$arrFilters = deserialize($this->catalog_filters, true);
			foreach ($arrFilters as $key=>$fieldconfig)
			{
				list($field, $config) = each($fieldconfig);
				if ($config['checkbox'] == 'tree')
				{
					$tree[] = $field;
				}
			}
		}
		return $tree;
	}


	protected function parseFilterUrl($searchFields=NULL)
	{
		$arrTree = $this->getTree();
		$blnTree = (count($arrTree)>0);

		$current = $this->convertAliasInput();
		$searchFields = deserialize($searchFields);

		// Setup Fields
		$fields = $this->getCatalogFields();

		if (!strlen($this->catalog_tags_mode))
		{
			$this->catalog_tags_mode = 'AND';
		}

		// Process POST redirect() settings
		$doPost = false;
		if ($this->Input->post('FORM_SUBMIT') == $this->strTable)
		{
			// search string POST
			if (array_key_exists($this->strSearch, $_POST))
			{
				$doPost = true;

				if ($this->Input->post($this->strSearch))
				{
					$current[$this->strSearch] = $this->Input->post($this->strSearch);
				} 
				else
				{
					unset($current[$this->strSearch]);
				} 
			}

			// filters POST
			foreach ($fields as $field=>$data)
			{
				// check if this is a filter
				if (array_key_exists($field, $_POST))
				{
					$doPost = true;
					$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
					// check if array posted (range and dates)
					if (is_array($this->Input->post($field)))
					{
						$range = $this->Input->post($field);
						$min = strlen($fieldConf['eval']['catalog']['minValue']) ? 
									max(min($range), $fieldConf['eval']['catalog']['minValue']) : min($range);
						$max = strlen($fieldConf['eval']['catalog']['maxValue']) ? 
									min(max($range), $fieldConf['eval']['catalog']['maxValue']) : max($range);
						if (strlen($max) && strlen($min))
						{
							$current[$field] = $min.'__'.$max;
						}
						if (in_array($fieldConf['eval']['catalog']['type'],array('number', 'decimal', 'date')))
						{
							$min='';
							$max='';
							if($fieldConf['eval']['catalog']['type'] == 'date')
							{
								if($range[0] && !is_numeric($range[0]))
									$range[0] = strtotime($range[0]);
								if($range[1] && !is_numeric($range[1]))
									$range[1] = strtotime($range[1]);
							}
							if (strlen($range[0]))							
								$min=$range[0];
							if (strlen($range[1]))
								$max=$range[1];
							if (strlen($max) || strlen($min))
							{
								$current[$field] = $min.'__'.$max;
							}
						}
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
									
								default:;
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

		// return if no filter parameters in URL
		if (!is_array($current) && !count($current))
		{
			return array();
		}

		// Setup Variables
		$baseurl = $this->generateFilterUrl();
		$procedure = array();
		$values = array();


		$procedure['search'] = null;
		$values['search'] = null;

		foreach ($fields as $field=>$data)
		{
			$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];

			// GET search value
			if ($this->Input->get($this->strSearch) && strlen($fieldConf['eval']['catalog']['type']))
			{
				if (is_array($searchFields) && !in_array($field, $searchFields))
				{
					continue;
				}

				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'text':
					case 'longtext':
							// explode the search by spaces and add the result to the query.
							// this allows us to search for multiple words which do not have to be in the same order as searched by.
							// Drawback is, we now can not search for exact phrases anymore (which is less required than searching for
							// multiple words IMO).
							// TODO: make this configable so users can decide which search algorithm to use.
							$words=explode(' ', $this->Input->get($this->strSearch));
							$proc=array();
							$vals=array();
							if(count($words))
							{
								$values['search'][$field]=array();
								foreach($words as $word)
								{
									$proc[] = '('.$field.' LIKE ?)';
									$values['search'][$field][] =  '%'.$word.'%';
								}
								$procedure['search'][$field] = '('.implode(' AND ', $proc).')';
							}
							break;
					case 'number':
					case 'decimal':
							$procedure['search'][$field] = '('.$field.' LIKE ?)';
							$values['search'][$field] = '%'.$this->Input->get($this->strSearch).'%';
							break;

					case 'file':
					case 'url':
							$procedure['search'][$field] = '('.$field.' LIKE ?)';
//							$values['search'][$field] = '%'.urldecode($this->Input->get($this->strSearch)).'%';
							$values['search'][$field] = '%'.($this->Input->get($this->strSearch)).'%';
							break;

					case 'date':
							// add month only search
							if (!is_numeric($this->Input->get($this->strSearch)))
							{
								$procedure['search'][$field] = "CAST(MONTHNAME(FROM_UNIXTIME(".$field.")) AS CHAR) LIKE ?";
								$values['search'][$field] = '%'.$this->Input->get($this->strSearch).'%';
							}
							// add numeric day, month, year search
							else
							{
								foreach (array('YEAR','MONTH','DAY') as $function) 
								{
									$tmpDate[] = "CAST(".$function."(FROM_UNIXTIME(".$field.")) AS CHAR) LIKE ?";
									$values['search'][$field][] = '%'.$this->Input->get($this->strSearch).'%';
								}
								$procedure['search'][$field] = '('.implode(' OR ',$tmpDate).')';
							}
							
							break;

					case 'checkbox' :
							// search only if true
							if (substr_count(strtolower($fieldConf['label']['0']),strtolower($this->Input->get($this->strSearch))))
							{
								$procedure['search'][$field] = '('.$field.'=?)';
								$values['search'][$field] = '1';
							}
							break;

					case 'select' :
							list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
							$procedure['search'][$field] = '('.$field.' IN (SELECT id FROM '.$itemTable.' WHERE '.$valueCol.' LIKE ?'.($fieldConf['options']? ' AND id IN ('.implode(',',array_keys($fieldConf['options'])).')':'').'))';
							$values['search'][$field] = '%'.$this->Input->get($this->strSearch).'%';
							break;
								
					case 'tags' :

							list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
							// perform search by using a subselect over the tables.
							$tagQuery = $this->Database->prepare(sprintf('SELECT DISTINCT(itemid) as id FROM tl_catalog_tag_rel WHERE fieldid=%s AND valueid IN (SELECT id FROM %s WHERE %s LIKE ? %s)',
																	$fieldConf['eval']['catalog']['fieldId'],
																	$itemTable,
																	$valueCol,
																	($fieldConf['options']? ' AND id IN ('.implode(',',array_keys($fieldConf['options'])).')':'')
																	))
									->execute('%'.$this->Input->get($this->strSearch).'%');
							if ($tagQuery->numRows)
							{
								$procedure['search'][$field] = 'id IN('.implode(',', $tagQuery->fetchEach('id')).')';
							}
							break;

					default:;
						// HOOK: Might be a custom field type, check if that one has registered a hook.
						$fieldType=$GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldConf['eval']['catalog']['type']];
						if(array_key_exists('generateFilter', $fieldType) && is_array($fieldType['generateFilter']))
						{
							foreach ($fieldType['generateFilter'] as $callback)
							{
								$this->import($callback[0]);
								$tmp=$this->$callback[0]->$callback[1]($field, $fieldConf, $this->Input->get($this->strSearch));
								$procedure['search'][$field] = $tmp['procedure'];
								if(is_array($tmp['search']))
								{
									if(isset($values['search'][$field]))
										$values['search'][$field]=array_merge($values['search'][$field], $tmp['search']);
									else
										$values['search'][$field]=$tmp['search'];
								}
								else
									$values['search'][$field]=$tmp['search'];
							}
						}
				}
			} // of search

			// GET range values
			if (substr_count($this->Input->get($field),'__'))
			{
				$rangeValues = trimsplit('__', $this->Input->get($field), 2);
				$rangeOptions[$field]['label'] = $fieldConf['label'][0];
				$rangeOptions[$field]['min'] = 	$rangeValues[0];
				$rangeOptions[$field]['max'] = $rangeValues[1];
				$minValue =	$rangeValues[0];
				$maxValue = $rangeValues[1];
				//$procedure['where'][] = '('.$field.' BETWEEN ? AND ?)';
				$strSqlWhereClause = '('.$field.' BETWEEN ? AND ?)';
				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'number':
						$rangeValues[0] = intval($rangeValues[0]);
						$rangeValues[1] = intval($rangeValues[1]);
						break;
					case 'decimal':
						$rangeValues[0] = floatval($rangeValues[0]);
						$rangeValues[1] = floatval($rangeValues[1]);
						break;
					case 'date':
						$rangeValues[0] = strtotime($rangeValues[0]);
						$rangeValues[1] = strtotime($rangeValues[1]);
						break;
					default:
				}
				if ($minValue!='')
					$values['where'][] = $rangeValues[0];
				else
					$strSqlWhereClause = '('.$field.' < ?)';
				if ($maxValue!='')
					$values['where'][] = $rangeValues[1];
				else
					$strSqlWhereClause = '('.$field.' > ?)';
				$procedure['where'][] = $strSqlWhereClause;
				$current[$field] = $this->Input->get($field);
			}
			//GET filter values
			elseif (strlen($this->Input->get($field)))
			{
				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'tags':
						list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
						// TODO: add support for string values here and get rid of the convertAliasInput call on the beginning.
						foreach(explode(',', $current[$field]) as $tag)
							$tags[] = (int)$tag;
						if($this->catalog_tags_mode == 'AND')
						{
							$sql = sprintf('SELECT itemid FROM tl_catalog_tag_rel WHERE fieldid=%s AND valueid=%s', 
											 $fieldConf['eval']['catalog']['fieldId'], 
											 $tag);
							foreach($tags as $tag)
								$sql = sprintf('SELECT itemid FROM tl_catalog_tag_rel WHERE fieldid=%s AND valueid=%s AND itemid IN(%s)', 
												 $fieldConf['eval']['catalog']['fieldId'], 
												 $tag,
												 $sql);
							$sql = sprintf('id IN (SELECT DISTINCT(itemid) FROM tl_catalog_tag_rel WHERE fieldid=%s AND itemid IN (%s) AND valueid IN (%s))',
											$fieldConf['eval']['catalog']['fieldId'], 
											$sql,
											implode(',', array_intersect($tags, ($fieldConf['options']?array_keys($fieldConf['options']):array())))
											);
							$tagQuery = $sql;
						} else {
							// perform search by using a subselect over the tables.
							$tagQuery = 'id IN(SELECT DISTINCT(itemid) as id FROM tl_catalog_tag_rel WHERE fieldid='.$fieldConf['eval']['catalog']['fieldId'].' AND valueid IN ('.implode(',', array_intersect($tags, ($fieldConf['options']?array_keys($fieldConf['options']):array()))).'))';
						}
						$procedure['tags'][] = $tagQuery;
						if ($blnTree && in_array($field, $arrTree))
						{
							$procedure['tree'][] = $tagQuery;
						}
						break;
					case 'checkbox':
						$procedure['where'][] = $field."=?";
						$values['where'][] = ($this->Input->get($field) == 'true' ? 1 : 0);
						//$current[$field] = $this->Input->get($field);
						if ($blnTree && in_array($field, $arrTree)) 
						{
							$procedure['tree'][$field] = $field."=?";
							$values['tree'][$field] = ($this->Input->get($field) == 'true' ? 1 : 0);
						}
						break;
					case 'text':
					case 'longtext':
					case 'number':
					case 'decimal':
					case 'select':
						$value = $current[$field];
						$procedure['where'][] = $field."=?";
						$values['where'][] = $value;
						if ($blnTree && in_array($field, $arrTree)) 
						{
							$procedure['tree'][$field] = $field."=?";
							$values['tree'][$field] = $value;
						}
						break;
					case 'date':
						$procedure['where'][] = $field."=?";
						$values['where'][] = strtotime($this->Input->get($field));
						//$current[$field] = $this->Input->get($field);

						if ($blnTree && in_array($field, $arrTree)) 
						{
							$procedure['tree'][$field] = $field."=?";
							$values['tree'][$field] = strtotime($this->Input->get($field));
						}
						break;
					case 'file':
					case 'url':
						$procedure['where'][] = $field."=?";
						$values['where'][] = urldecode($this->Input->get($field));
						$current[$field] = $this->Input->get($field);

						if ($blnTree && in_array($field, $arrTree)) 
						{
							$procedure['tree'][$field] = $field."=?";
							$values['tree'][$field] = urldecode($this->Input->get($field));
						}
						break;
				}
			} // filter

			// GET sort values
			if ($this->Input->get($this->strOrderBy) == $field && in_array($this->Input->get($this->strSort), array('asc','desc')))
			{
				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'select':
						list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
						$procedure['orderby'] = '(SELECT '.$valueCol.' from '.$itemTable.' WHERE id='.$field.') '.$this->Input->get($this->strSort);
						break;
					default:
						$procedure['orderby'] = $field.' '.$this->Input->get($this->strSort);
				}
				$current[$this->strOrderBy] = $this->Input->get($this->strOrderBy);
				$current[$this->strSort] = $this->Input->get($this->strSort);
			} //sort

		} // foreach $filter
		

		$settings = array 
			(
				'current' 	=> $current,
				'procedure' => $procedure,
				'values' 		=> $values,
			);
		// HOOK: allow other extensions to manipulate the filter settings before passing it to the template
		if(is_array($GLOBALS['TL_HOOKS']['filterCatalog']))
		{
			foreach ($GLOBALS['TL_HOOKS']['filterCatalog'] as $callback)
			{
				$this->import($callback[0]);
				$settings = $this->$callback[0]->$callback[1]($settings);
			}
		}
		return $settings;
	}

	/**
	 * Retrieve Alias field from table, checks if catalog
	 * @param string
	 * @return string
	 */

	public function getAliasField($sourceTable)
	{
		// check alias field
		$objAlias = $this->Database->prepare("SELECT aliasField FROM tl_catalog_types WHERE tableName=?")
										->execute($sourceTable);
		$aliasField = ($objAlias->numRows && strlen($objAlias->aliasField)) ? $objAlias->aliasField : 'alias';

		return ($this->Database->fieldExists($aliasField, $sourceTable) && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $aliasField : 'id';
	}

	/**
	 * Retrieve alias field for current catalog field configuration
	 * @param array
	 * @return string
	 */

	private function getAliasFieldConf($fieldConf)
	{
		if (!$fieldConf['eval']['catalog']['foreignKey'])
		{
			return 'id';
		}
		
		// get alias column
		list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);

		return $this->getAliasField($itemTable);
	}


	/**
	 * Retrieve alias values with id as index
	 * @param array
	 * @return array
	 */

	private function getAliasOptionList(&$fieldConf)
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
	 * @return array
	 */

	protected function convertAliasInput()
	{

		$return = array();

		// convert $_GET filter parameters in Url
		foreach ($_GET as $k=>$v)
		{
			// exclude page parameter
			if (!in_array($k, array('page')))
			{
				$_GET[$k] = str_replace($GLOBALS['TL_CONFIG']['catalog']['safeReplace'], $GLOBALS['TL_CONFIG']['catalog']['safeCheck'], $v);
				
				// use TL safe function
				$v = $this->Input->get($k);

				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];
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
							
						default:;
					}
				}
				
				$return[$k] = $v; 
			}
		}
		
		return $return;
	}


	protected function lastInTree($field, $current, $tree)
	{
		return (!count($tree) || in_array($field, $tree) && array_search($field, $tree)==(count($tree)-1));
	}


	protected function hideTree($field, $current, $tree)
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

	protected function buildTreeQuery($field, $filterurl, $tree)
	{
		if (count($tree) && is_array($filterurl['procedure']['tree']))
		{
			$pos = array_search($field, $tree);
			for ($i=0;$i<$pos;$i++)
			{
				if (strlen($filterurl['procedure']['tree'][$tree[$i]]))
					$query[] = $filterurl['procedure']['tree'][$tree[$i]];
				if (strlen($filterurl['values']['tree'][$tree[$i]]))
					$params[] = $filterurl['values']['tree'][$tree[$i]];
			}
		}
		return array (
			'query' => (is_array($query) ? implode(' AND ', $query) : ''),
			'params' => (is_array($params) ? $params : array()),
		);
	}


	protected function clearTree($field, &$newcurrent, $tree)
	{
		if (in_array($field, $tree))
		{
			$pos = (array_search($field, $tree)+1);
			for ($i=$pos;$i<=count($tree);$i++)
			{
				unset($newcurrent[$tree[$i]]);
			}
		}
	}

	protected function makeAllLabel($input, $label, $multi=false)
	{
		if ($input=='select' && !$multi)
		{
			return sprintf($GLOBALS['TL_LANG']['MSC']['selectNone'], $label);
		}
		return sprintf($GLOBALS['TL_LANG']['MSC']['clearAll'], $label);
	}


	protected function generateFilter()
	{
		$filterurl = $this->parseFilterUrl();		
		$current 	= $filterurl['current'];

		$arrFilters = deserialize($this->catalog_filters, true);
		if ($this->catalog_filter_enable && count($arrFilters))
		{
			// Get Tree View
			$tree = $this->getTree();

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
				$blnLast = $this->lastInTree($field, $current, $tree);

				$query = $this->buildTreeQuery($field, $filterurl, $tree);
				if(!BE_USER_LOGGED_IN && $this->publishField)
				{
					if(strlen($query['query']))
					{
						$query['query'].=' AND '.$this->publishField.'=1 ';
					} else {
						$query['query']=$this->publishField.'=1 ';
					}
				}

				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];

				$fieldType = $fieldConf['eval']['catalog']['type'];
				// HOOK: let custom fields mimic another fieldtype to generate a filter
				if(array_key_exists($fieldType, $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes']))
				{
					$fieldTypeArr=$GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType];
					if(array_key_exists('generateFilterWidget', $fieldTypeArr) && is_array($fieldTypeArr['generateFilterWidget']))
					{
						foreach ($fieldTypeArr['generateFilterWidget'] as $callback)
						{
							$this->import($callback[0]);
							$tmp=$this->$callback[0]->$callback[1]($fieldType, $field, $config, $fieldConf, $filterurl, $query, $tree);
							if($tmp)
							{
								$fieldType = $tmp;
							}
						}
					}
				}

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
							$selected = $current[$field] == $newcurrent[$field];
							$this->clearTree($field, $newcurrent, $tree);
							$url = $this->generateFilterUrl($newcurrent, true, $blnLast);
							
							$addOption = array();
							$addOption['value'] = $url;
							$addOption['label'] = $label;
							$addOption['id'] = ($key == 'none' ? '' : $key);
							if ($selected)
							{
								$addOption['selected'] = true;
							}
							array_push($options, $addOption);

						}
						$widget = array
						(
							'name'			=> $field,
							'id'				=> 'filter_field_'.$field,
							'label'			=> $fieldConf['label'][0],
							'options'		=> serialize($options),
							'value' 		=> htmlentities($this->generateFilterUrl($current, true, $blnLast)),
							'tableless'	=> true,
							'inputType' => $input,
						);

						// parse Widget
						$settings['filter'][] =  $this->parseWidget($widget, false);
						$widgets['filter'][] = $widget;

						break;


					case 'select':
						if(($this instanceof ModuleCatalogFilter) && $this->catalog_filter_cond_from_lister)
						{
							$ids=$this->getModulesForThisPage();
							$objModules = $this->Database->prepare('SELECT * FROM tl_module WHERE id IN (' . implode(', ', $ids) . ') AND type=\'cataloglist\' AND catalog='.$this->catalog)
									->execute();
							while($objModules->next())
							{
								$objModules->catalog_search=deserialize($objModules->catalog_search);
								$moduleFilterUrl = $this->parseFilterUrl($objModules->catalog_search);
								if (is_array($objModules->catalog_search) && strlen($objModules->catalog_search[0]) && is_array($moduleFilterUrl['procedure']['search']))
								{
									// reset arrays
									$searchProcedure = array();
									$searchValues = array();
									foreach($objModules->catalog_search as $searchfield)
									{
										if (($searchfield != $field)
											&& array_key_exists($searchfield, $moduleFilterUrl['current'])
											&& array_key_exists($searchfield, $moduleFilterUrl['procedure']['search']))
										{
											$searchProcedure[] = $moduleFilterUrl['procedure']['search'][$searchfield];
											if (is_array($moduleFilterUrl['values']['search'][$searchfield]))
											{
												foreach($moduleFilterUrl['values']['search'][$searchfield] as $item)
												{
													$searchValues[] = $item;
												}
											}
											else
											{
												$searchValues[] = $moduleFilterUrl['values']['search'][$searchfield];
											}
										}
									}
									if(count($searchProcedure))
									{
										$moduleFilterUrl['procedure']['where'][] = ' ('.implode(' OR ', $searchProcedure).')';
										$moduleFilterUrl['values']['where'] = is_array($moduleFilterUrl['values']['where']) ? (array_merge($moduleFilterUrl['values']['where'],$searchValues)) : $searchValues;
									}
								}
								if(is_array($moduleFilterUrl['procedure']['where']))
								{
									foreach($moduleFilterUrl['procedure']['where'] as $key=>$value)
									{
										if(strpos($value, $field) !== false)
										{
											unset($moduleFilterUrl['procedure']['where'][$key]);
											unset($moduleFilterUrl['values']['where'][$key]);
										}
									}
								}
								if(is_array($moduleFilterUrl['procedure']['tags']))
								{
									foreach($moduleFilterUrl['procedure']['tags'] as $key=>$value)
									{
										if(strpos($value, $field) !== false)
										{
											unset($moduleFilterUrl['procedure']['tags'][$key]);
											unset($moduleFilterUrl['values']['tags'][$key]);
										}
									}
								}
								if (is_array($moduleFilterUrl['values']['where'])) {
									$query['params'] = array_merge($query['params'], $moduleFilterUrl['values']['where']);
								}
						
								if (is_array($moduleFilterUrl['values']['tags'])) {
									$query['params'] = array_merge($query['params'], $moduleFilterUrl['values']['tags']);
								}

								if($objModules->catalog_where)
								{
									$strCondition = $this->replaceInsertTags($objModules->catalog_where);
									if(strlen($strCondition))
										$query['query'] .= (strlen($query['query'])?' AND ':'').$strCondition;
								}
								if(count($moduleFilterUrl['procedure']['where']))
									$query['query'] .=(strlen($query['query'])?' AND ':'').implode(' '.$objModules->catalog_query_mode.' ', $moduleFilterUrl['procedure']['where']);
								if(count($moduleFilterUrl['procedure']['tags']))
									$query['query'] .=(strlen($query['query'])?' AND ':'').implode(' '.$objModules->catalog_tags_mode.' ', $moduleFilterUrl['procedure']['tags']);
							}
						}
						// get existing options in DB
						$objFilter = $this->Database->prepare('SELECT DISTINCT('.$field.') FROM '.$this->strTable . ($query['query'] ? ' WHERE '.$query['query'] : '') )
								->execute($query['params']);

						if ($objFilter->numRows) 
						{
							$selected = !strlen($current[$field]);
							$newcurrent = $current;
							unset($newcurrent[$field]);
							$this->clearTree($field, $newcurrent, $tree);
							$url = $this->generateFilterUrl($newcurrent, true, $blnLast);
							
							$addOption = array();
							$addOption['value'] = $url;
							$addOption['label'] = $this->makeAllLabel($input, $fieldConf['label'][0]);
							$addOption['id'] = '';
							if ($selected)
							{
								$addOption['selected'] = true;
							}
							array_push($options, $addOption);
							
							// get all rows
							$rows = $objFilter->fetchEach($field);

							if($fieldConf['options'])
							{
								$tmpTags = array();
								foreach ($fieldConf['options'] as $id=>$option)
								{
									$tmpTags[] = 'SUM(FIND_IN_SET('.$id.','.$field.')) AS '.$field.$id;
								}
								$objResultCount = $this->Database->prepare('SELECT '.implode(', ',$tmpTags).' FROM '.$this->strTable. ($query['query'] ? ' WHERE '. $query['query'] : ''))
																->execute($query['params']);
								$arrResultCount = $objResultCount->row();

								foreach ($fieldConf['options'] as $id=>$option)
								{
									if (in_array($id, $rows))
									{
										$selected = ($current[$field] == $id);
										$newcurrent = $current;
										$newcurrent[$field] = $id;
										$this->clearTree($field, $newcurrent, $tree);
										$url = $this->generateFilterUrl($newcurrent, true, $blnLast);
										$addOption = array();
										$addOption['value'] = $url;
										$addOption['label'] = $option;
										$addOption['id'] = $id;
										$addOption['alias'] = $id;
										$addOption['resultcount'] = $arrResultCount[$field.$id];
										if ($selected)
										{
											$addOption['selected'] = true;
										}
										array_push($options, $addOption);
									}
								}
							}

							$widget = array
							(
								'name'			=> $field,
								'id'				=> 'filter_field_'.$field,
								'label'			=> $fieldConf['label'][0],
								'value' 		=> $this->generateFilterUrl($current, true, $blnLast),
								'options'		=> serialize($options),
								'tableless'	=> true,
								'inputType' => $input,
							);
	
							// parse Widget
							$settings['filter'][] =  $this->parseWidget($widget, false);
							$widgets['filter'][] = $widget;

						}
						break;
	
					case 'tags' :
						$query['query'] = '';
						$query['params'] = is_array($filterurl['values']['where'])? $filterurl['values']['where'] : array();
						if (is_array($filterurl['values']['tags']))
							$query['params'] = array_merge($query['params'], $filterurl['values']['tags']);
						if(count($filterurl['procedure']['where']))
							$query['query'] .=(strlen($query['query'])?' AND ':'').implode(' AND ', $filterurl['procedure']['where']);
						// TODO: we have a problem here if more than one tag field exists - we simply ignore the other one in here.
//						if(count($filterurl['procedure']['tags']))
//							$query['query'] .=(strlen($query['query'])?' AND ':'').implode(' AND ', $filterurl['procedure']['tags']);

						// clear option
						$selected = !strlen($current[$field]);
						$newcurrent = $current;
						unset($newcurrent[$field]);
						$this->clearTree($field, $newcurrent, $tree);
						$url = $this->generateFilterUrl($newcurrent, true, $blnLast);

						$addOption = array();
						$addOption['value'] = $url;
						$addOption['label'] = $this->makeAllLabel($input, $fieldConf['label'][0], $this->catalog_tags_multi);
						$addOption['id'] = $id;
						if ($selected)
						{
							$addOption['selected'] = true;
						}
						array_push($options, $addOption);

						$tmpTags = array();
						// get ids of matches according to all other filters.
						if($query['query'])
						{
							$objFilter = $this->Database->prepare('SELECT id FROM '.$this->strTable.' WHERE '.$query['query'])
														->execute($query['params']);
							$itemIds = implode(',',$objFilter->fetchEach('id'));
						}
						foreach ($fieldConf['options'] as $id=>$option)
						{
							$tmpTags[] = '(SELECT COUNT(itemid) FROM tl_catalog_tag_rel WHERE valueid='.$id.' AND fieldid='.$fieldConf['eval']['catalog']['fieldId'].($query['query'] && $itemIds?' AND itemid IN('.$itemIds.')':'').') AS '.$field.$id;
						}
						if(count($tmpTags)==0)
							$tmpTags = array($field);
						$objFilter = $this->Database->prepare('SELECT '.implode(', ',$tmpTags))
													->execute($query['params']);
						if ($objFilter->numRows)
						{
							$row = $objFilter->row();

							foreach ($fieldConf['options'] as $id=>$option)
							{
								if ($row[$field.$id])
								{
									$selected = in_array($id, explode(',',$current[$field]));
									$newcurrent = $current;
									$newids = strlen($current[$field]) ? explode(',', $current[$field]) : array();
									$newids = array_unique(!$selected ? array_merge($newids, array($id)) : array_diff($newids, array($id)));
									$newcurrent[$field] = ($this->catalog_tags_multi ? implode(',',$newids) : $id);
									$this->clearTree($field, $newcurrent, $tree);
									$url = $this->generateFilterUrl($newcurrent, true, $blnLast);

									$blnList = ($selected && $input=='list');

									$addOption = array();
									$addOption['value'] = $url;
									$addOption['label'] = $blnList ? sprintf($GLOBALS['TL_LANG']['MSC']['optionselected'], $option) : $option;
									$addOption['id'] = $id;
									$addOption['resultcount'] = $row[$field.$id];
									$addOption['selected'] = $selected;
									array_push($options, $addOption);
								}
							}
						}

						$widget = array
						(
							'name'			=> $field,
							'id'				=> 'filter_field_'.$field,
							'label'			=> $fieldConf['label'][0],
							'value' 		=> $this->generateFilterUrl($current, true, $blnLast),
							'options'		=> serialize($options),
							'tableless'	=> true,
							'inputType' => ($this->catalog_tags_multi && $input=='radio' ? 'checkbox' : $input),
						);


						if ($this->catalog_tags_multi && $input=='select') 
						{
							$widget = array_merge($widget, array('multiple'=>true));
						}
						
						$settings['filter'][] = $this->parseWidget($widget, false);
						$widgets['filter'][] = $widget;

						break;
	
					case 'text':
					case 'file':
					case 'url':
					case 'number':
					case 'decimal':
					case 'date':
						$query['query'] = '';
						// TODO: this is an evil hack - we DEFINATELY have to rewrite this lookup mechanism.
						if(count($filterurl['procedure']['where']))
						foreach($filterurl['procedure']['where'] as $k=>$v)
						{
							if(strpos($v, $field) !== false)
							{
								unset($filterurl['procedure']['where'][$k]);
								unset($filterurl['values']['where'][$k]);
							}
						}
						
						$query['params'] = is_array($filterurl['values']['where'])? $filterurl['values']['where'] : array();
						if (is_array($filterurl['values']['tags']))
							$query['params'] = array_merge($query['params'], $filterurl['values']['tags']);
						if(count($filterurl['procedure']['where']))
							$query['query'] .=(strlen($query['query'])?' AND ':'').implode(' AND ', $filterurl['procedure']['where']);
						if(count($filterurl['procedure']['tags']))
							$query['query'] .=(strlen($query['query'])?' AND ':'').implode(' AND ', $filterurl['procedure']['tags']);
						// get existing options in DB
						$options = array();
						$objFilter = $this->Database->prepare("SELECT DISTINCT ".$field." FROM ".$this->strTable . ($query['query'] ? " WHERE ".$query['query'] : '') . " ORDER BY ".$field)
								->execute($query['params']);

						if ($objFilter->numRows)
						{
							// setup ALL option
							$selected = !strlen($current[$field]);
							$newcurrent = $current;
							unset($newcurrent[$field]);	
							$this->clearTree($field, $newcurrent, $tree);
							$url = $this->generateFilterUrl($newcurrent, true, $blnLast);

							$addOption = array();
							$addOption['value'] = $url;
							$addOption['label'] = $this->makeAllLabel($input, $fieldConf['label'][0]);
							$addOption['id'] = '';
							if ($selected)
							{
								$addOption['selected'] = true;
							}
							array_push($options, $addOption);

							while ($objFilter->next())
							{
								$row = $objFilter->row();
								if (!strlen(trim($row[$field])))
								{
									continue;
								}
								$label = $this->formatValue(0, $field, $row[$field], false);
								switch ($fieldConf['eval']['catalog']['type'])
								{
									case 'url':
										$label = $row[$field];
										$row[$field] = urlencode(urlencode($row[$field]));
										break;

									case 'file':
										$label = implode(',',deserialize($row[$field],true));
										$row[$field] = urlencode(urlencode($row[$field]));
										break;

									case 'date':
										$row[$field] = $label;
										break;

									default:;

								}

								$newcurrent = $current;
								$newcurrent[$field] = htmlspecialchars($row[$field]);
								$selected = $current[$field] == $newcurrent[$field];
								$this->clearTree($field, $newcurrent, $tree);
								$url = $this->generateFilterUrl($newcurrent, true, $blnLast);

								$addOption = array();
								$addOption['value'] = $url;
								$addOption['label'] = $label;
								$addOption['id'] = $row[$field];
								if ($selected)
								{
									$addOption['selected'] = true;
								}
								array_push($options, $addOption);

							}
							
							$widget = array
							(
								'name'			=> $field,
								'id'				=> 'filter_field_'.$field,
								'label'			=> $fieldConf['label'][0],
								'options'		=> serialize($options),
								'value' 		=> htmlentities($this->generateFilterUrl($current, true, $blnLast)),
								'tableless'	=> true,
								'inputType'	=> $input,
							);

							// parse Widget
							$settings['filter'][] =  $this->parseWidget($widget, false);
							$widgets['filter'][] = $widget;
							
						}
						
						break;

					case 'longtext':
						$options[] = array 
						(
							'label' => &$GLOBALS['TL_LANG']['MSC']['invalidFilter'],
							'value' => '',
						);
						$widget = array
						(
							'name'			=> $field,
							'id'				=> 'filter_field_'.$field,
							'label'			=> $fieldConf['label'][0],
							'options'		=> serialize($options),
							'value' 		=> '',
							'tableless'	=> true,
							'inputType'	=> 'list',
						);
						// parse Widget
						$settings['filter'][] =  $this->parseWidget($widget, true);
						$widgets['filter'][] = $widget;

						break;
					
					default:
						// HOOK: let custom fields generate a filter widget
						if($fieldType && array_key_exists($fieldType, $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes']))
						{
							$fieldType=$GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldType];
							if(array_key_exists('generateFilterForField', $fieldType) && is_array($fieldType['generateFilterForField']))
							{
								$callback = $fieldType['generateFilterForField'];
								$tmp=$this->$callback[0]->$callback[1]($fieldType, $field, $config, $fieldConf, $filterurl, $query, $tree);
								if($tmp)
								{
									$settings['filter'][] = $tmp['settings'];
									$widgets['filter'][] = $tmp['widget'];
								}
							}
						}
				}
			}
		}
		$arrRange = deserialize($this->catalog_range,true);
		if ($this->catalog_range_enable && count($arrRange) && strlen($arrRange[0]))
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
					
						$rangeValues = substr_count($this->Input->get($field),'__') ? trimsplit('__', $this->Input->get($field)) : array('','');
						$current[$field] = $this->Input->get($field);

						$widget = array
						(
							'name'			=> $field,
							'id'				=> 'filter_range_'.$field,
							'label'			=> ($i==0 ? $fieldConf['label'][0]:''),
							'inputType' => 'range',
							'value' 		=> serialize($rangeValues),
							'multiple'	=> true,
							'size'			=> 2,
							'tableless' => true,
							'addSubmit' => true,
							'slabel' 		=> $GLOBALS['TL_LANG']['MSC']['catalogSearch'],
						);


						if ($fieldConf['eval']['catalog']['type'] == 'date')
						{
							$date = array
							(
								'maxlength' => 10,
								'rgxp' 			=> 'date'
							);
							// date picker was changed in 2.10
							if (version_compare(VERSION.'.'.BUILD, '2.10.0', '>='))
								$date['datepicker'] = true;
							else
								$date['datepicker'] = $this->getDatePickerString();
							$widget = array_merge($widget, $date);
						}

						$settings['range'][] = $this->parseWidget($widget, true);
						$widgets['range'][] = $widget;

						break;
	
					default :;
				}
			}
		}
		

		// Setup date values
		$arrDates = deserialize($this->catalog_dates,true);

		$arrRanges = deserialize($this->catalog_date_ranges,true);
		if ($this->catalog_date_enable && count($arrDates))
		{
			foreach ($arrDates as $fieldconfig)
			{
				list($field, $config) = each($fieldconfig);
				$input = $config['radio'];

				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
				$options = array();

				$selected = !strlen($current[$field]);
				$newcurrent = $current;
				unset($newcurrent[$field]);
				$url = $this->generateFilterUrl($newcurrent, true);

				$addOption = array();
				$addOption['value'] = $url;
				$addOption['label'] = $this->makeAllLabel($input, $fieldConf['label'][0]);
				$addOption['id'] = '';
				if ($selected)
				{
					$addOption['selected'] = true;
				}
				array_push($options, $addOption);

				foreach ($arrRanges as $id=>$range) 
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
					$newcurrent = $current;
					$newcurrent[$field] = ($strPast ? $strPast . '__' .$strYesterday : '') . $strToday . ($strFuture ? $strTomorrow . '__' . $strFuture : '');
					$selected = ($current[$field] == $newcurrent[$field]);
					$url = $this->generateFilterUrl($newcurrent, true);

					$addOption = array();
					$addOption['value'] = $url;
					$addOption['label'] = &$GLOBALS['TL_LANG']['MSC']['daterange'][$range];
					$addOption['id'] = $range;
					if ($selected)
					{
						$addOption['selected'] = true;
					}
					array_push($options, $addOption);

				}
				$widget = array
				(
					'name' => $field,
					'id' => 'filter_date_'.$field,
					'label' => $fieldConf['label'][0],
					'value' =>  $this->generateFilterUrl($current, true),
					'tableless' => true,
					'options' => serialize($options),
					'inputType' => $input,
				);
				
				$settings['date'][] = $this->parseWidget($widget, false);			
				$widgets['date'][] = $widget;
			}
		}



		$arrSort = deserialize($this->catalog_sort,true);
		if ($this->catalog_sort_enable && count($arrSort))
		{
			// Setup sort values
			$options = array();
			if (count($arrSort) && $this->catalog_sort_type!='list')
			{
				$selected = !strlen($current[$field]);
				$newcurrent = $current;
				unset($newcurrent[$this->strOrderBy]);
				unset($newcurrent[$this->strSort]);
				$url = $this->generateFilterUrl($newcurrent, true);

				$addOption = array();
				$addOption['value'] = $url;
				$addOption['label'] = $GLOBALS['TL_LANG']['MSC']['unsorted'];
				$addOption['id'] = '';
				if ($selected)
				{
					$addOption['selected'] = true;
				}
				array_push($options, $addOption);

			}
			foreach ($arrSort as $id=>$field) 
			{
				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
				$newcurrent = $current;
				$newcurrent[$this->strOrderBy] = $field;
				foreach (array('asc','desc') as $order)
				{
					$newcurrent[$this->strSort] = $order;
					$selected = ($current[$this->strSort] == $newcurrent[$this->strSort] && $current[$this->strOrderBy] == $newcurrent[$this->strOrderBy]);
					$url = $this->generateFilterUrl($newcurrent, true);

					$addOption = array();
					$addOption['value'] = $url;
					$addOption['label'] = $fieldConf['label'][0] . ' ' . $this->selectSortLabel($fieldConf['eval']['catalog']['type'], ($order=='asc'));
					$addOption['id'] = $field.'__'.$order;
					if ($selected)
					{
						$addOption['selected'] = true;
					}
					array_push($options, $addOption);

				}
			}
			$widget = array
			(
				'name' 			=> $this->strSort,
				'id' 				=> 'filter_'.$this->strSort,
				'value' 		=>  $this->generateFilterUrl($current, true),
				'tableless'	=> true,
				'options' 	=> serialize($options),
				'inputType' => $this->catalog_sort_type,
			);
			
			$settings['sort'] = $this->parseWidget($widget, false);			
			$widgets['sort'] = $widget;
		}


		if ($this->catalog_search_enable)
		{

			$widget = array
			(
				'name' => $this->strSearch,
				'id' => 'filter_'.$this->strSearch,
				'inputType' => 'text',
				'value' =>  $this->Input->get($this->strSearch),
				'tableless' => true,
				'addSubmit' => true,
				'slabel' => $GLOBALS['TL_LANG']['MSC']['catalogSearch'],
			);
			$settings['search'] =  $this->parseWidget($widget);
			$widgets['search'] = $widget;
		}


		$settings['url'] 		=	$this->generateFilterUrl();
		$settings['action'] =	$this->generateFilterUrl($current);
		$settings['widgets'] = $widgets;

		if(is_array($GLOBALS['TL_HOOKS']['generateFilterCatalog']))
		{
			foreach ($GLOBALS['TL_HOOKS']['generateFilterCatalog'] as $callback)
			{
				$this->import($callback[0]);
				$settings = $this->$callback[0]->$callback[1]($this,$settings);
			}
		}
		return $settings; 
	}



	public function parseWidget(&$widget)
	{
		$this->addWidgetAttributes($widget);

		$class = $widget['inputType'];
		$options = deserialize($widget['options']);
		$return = '';

		$label = $widget['label'] ? sprintf(
'<h3><label for="%s">%s</label></h3>
',
							'ctrl_'.$widget['id'], 
							$widget['label'])
							: '';

	
		switch ($widget['inputType'])
		{
			case 'list':		
				$return = sprintf(
'%s<div id="%s" class="list_container">
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
				for ($i=0; $i<2; $i++)
				{
					$datepicker = ($widget['datepicker'] ? '
					<script type="text/javascript"><!--//--><![CDATA[//><!--
					window.addEvent(\'domready\', function() { ' . sprintf($widget['datepicker'], 'ctrl_' . $widget['id'] .'_'.$i) . ' });
					//--><!]]></script>'
					: '');
					
					// adding a <label for=""> to the inputs
					$strLabel = ($i == 0) ? $GLOBALS['TL_LANG']['MSC']['rangeFrom'] : $GLOBALS['TL_LANG']['MSC']['rangeTo'];
		
					$arrFields[] = sprintf('%s<input type="text" name="%s[]" id="ctrl_%s" class="text%s" value="%s"%s />',
							(strlen($strLabel)) ? '<label for="ctrl_' . $widget['id'].'_'.$i . '">' . $strLabel . '</label>' : '',
							$widget['name'],
							$widget['id'].'_'.$i,
							(strlen($widget['class']) ? ' ' . $widget['class'] : ''),
							specialchars($widget['value'][$i]),
							$widget['attributes'])
							. $datepicker . ($i==1 ? $this->addSubmit($widget) : '');
								
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
//						$widget['id'].'_'.$i, // the id again
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
//						implode(($widget['separator'] ? $widget['separator'] : '<br />'), $arrOptions));
						// c.schiffler - removed the separator as if none specified, we do not want one. <br /> is evil IMO.
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
	
		
		
			default:;
		}		
		return '<div class="widget'.(strlen($widget['id']) ? ' ' . $widget['id'] : '').'">
'.$return.'
</div>
';
	}



	protected function addSubmit($widget)
	{
		return (strlen($widget['slabel']) ? sprintf(' <input type="submit" id="ctrl_%s_submit" class="submit" value="%s" />',
						$widget['id'],
						specialchars($widget['slabel']))
						: '');
	}


	private function addWidgetAttributes(&$widget)
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
			default:;
		}
		if (is_array($types)) 
		{
			$widget = array_merge($widget, $types);
		}
	}
	


	private function checkArray($arrInput) 
	{
		return is_array($arrInput) ? $arrInput : array();
	}



	protected function selectSortLabel($inputType, $blnAsc=true)
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



	public function generateFilterUrl($arrGet = array(), $blnRoot=false, $blnJumpTo=true)
	{
		$arrPage=$this->getJumpTo($this->catalog_jumpTo, $blnJumpTo);
		$strParams = '';
		if (is_array($arrGet) && ($arrGet))
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
					$v = str_replace($GLOBALS['TL_CONFIG']['catalog']['safeCheck'], $GLOBALS['TL_CONFIG']['catalog']['safeReplace'], $v);
	
					$strParams .= $GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;' . $k . '=' . $v  : '/' . $k . '/' . $v;
				}
			}
		}
		return ($blnRoot ? $this->Environment->base : '') . $this->generateFrontEndUrl($arrPage, $strParams);		
	}

	/**
	 * Translate SQL if needed (needed for calculated fields)
	 * @param array
	 * @return array
	 */

	protected function processFieldSQL($arrVisible)
	{
		$arrConverted = array();

		// iterate all catalog fields
		$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields f WHERE f.pid=(SELECT c.id FROM tl_catalog_types c WHERE c.tableName=?)")
								   ->execute($this->strTable);

		$arrFields = array();
		if ($objFields->numRows)
		{
			while ($objFields->next())
			{
				$row = $objFields->row();			
				$arrFields[$row['colName']] = $row;
			}

			foreach ($arrVisible as $id=>$field)
			{
				if (array_key_exists($field, $arrFields))
				{
					switch ($arrFields[$field]['type'])
					{
						case 'calc':
							// set query value to forumla
							$value = '('.$arrFields[$field]['calcValue'].') AS '.$field; //.'_calc';
							$arrConverted[$id] = $value;
							break;

						default:
							$arrConverted[$id] = $field;
					}

				}

				// HOOK: allow third party extension developers to prepare the SQL data
				if(is_array($GLOBALS['TL_HOOKS']['processFieldSQL']) && count($GLOBALS['TL_HOOKS']['processFieldSQL']))
				{
					foreach($GLOBALS['TL_HOOKS']['processFieldSQL'] as $callback)
					{
						$this->import($callback[0]);
						$this->$callback[0]->$callback[1]($this->catalog, $id, $field, $arrFields, $arrConverted, $this->strTable);
					}
				}	
			}	
		}

		return $arrConverted;
	}

	/**
	 * Generate one or more items and return them as array
	 * @param object
	 * @param boolean
	 * @return array
	 */
	protected function generateCatalog(Database_Result $objCatalog, $blnLink=true, $visible=null, $blnImageLink=false)
	{
		$i=0;
		$arrCatalog = array();

		$objCatalog->reset();		
		while ($objCatalog->next())
		{
			$arrCatalog[$i]['id'] = $objCatalog->id;
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
			// check if editing of this record is disabled for frontend.
			$editingallowedByFields=true;
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $fieldname=>$field)
			{
				if(is_array($visible) && !in_array($fieldname, $visible))
					continue;
				// HOOK: additional permission checks if this field allows editing of this record (for the current user).
				$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$field['eval']['catalog']['type']];
				if(is_array($fieldType) && array_key_exists('checkPermissionFERecordEdit', $fieldType) && is_array($fieldType['checkPermissionFERecordEdit']))
				{
					foreach ($fieldType['checkPermissionFERecordEdit'] as $callback)
					{
						$this->import($callback[0]);
						// TODO: Do we need more parameters here?
						if(!($this->$callback[0]->$callback[1]($this->strTable, $fieldname, $arrData)))
						{
							$editingallowedByFields=false;
							break;
						}
					}
				}
			}

			if ($this->catalog_edit_enable && $editingallowedByFields)
			{
				$arrCatalog[$i]['linkEdit'] = $this->generateLink($objCatalog, $aliasField, $this->strTable, $this->catalog_link_window, true);
				$arrCatalog[$i]['urlEdit'] = $this->generateCatalogEditUrl($objCatalog, $aliasField, $this->strTable);
			}

			if (is_array($visible))
			{
				foreach($visible as $field)
				{
					$tmpData[$field] = $arrData[$field];
				}
				$arrData = $tmpData;
			}
			

			foreach ($arrData as $k=>$v)
			{
			
				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];
				
				$blnParentCheckbox = $fieldConf['eval']['catalog']['parentCheckbox'] && !$arrData[$fieldConf['eval']['catalog']['parentCheckbox']];
				
				if (in_array($k, array('id','pid','sorting','tstamp')) || $fieldConf['inputType'] == 'password' || $blnParentCheckbox)
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
			if(is_array($GLOBALS['TL_HOOKS']['generateCatalogItem']))
			{
				foreach ($GLOBALS['TL_HOOKS']['generateCatalogItem'] as $callback)
				{
					$this->import($callback[0]);
					$arrCatalog[$i] = $this->$callback[0]->$callback[1]($arrCatalog[$i], $arrData, $this);
				}
			}
			$i++;
		}
		return $arrCatalog;
	}

	/**
	 * Parse one or more items and pipe them through the given template
	 * @param object
	 * @param boolean
	 * @return array
	 */
	protected function parseCatalog(Database_Result $objCatalog, $blnLink=true, $template='catalog_full', $visible=null, $blnImageLink=false)
	{
		$objTemplate = new FrontendTemplate($template);
		$arrCatalog = $this->generateCatalog($objCatalog, $blnLink, $visible, $blnImageLink);

		// HOOK: allow other extensions to manipulate the items before passing it to the template
		if(is_array($GLOBALS['TL_HOOKS']['parseCatalog']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseCatalog'] as $callback)
			{
				$this->import($callback[0]);
				$arrCatalog = $this->$callback[0]->$callback[1]($arrCatalog, $objTemplate, $this);
			}
		}
		$objTemplate->entries         = $arrCatalog;
		$objTemplate->moduleTemplate  = $this->Template;

		return $objTemplate->parse();
	}


	protected function generateCatalogUrl(Database_Result $objCatalog, $aliasField='alias', $strTable, $strPrependParams='')
	{

		if (!strlen($aliasField))
		{
			$aliasField = 'alias';
		}

		$useJump = ($this instanceof ModuleCatalogList || $this instanceof ModuleCatalogFeatured || $this instanceof ModuleCatalogRelated || $this instanceof ModuleCatalogReference || $this instanceof ModuleCatalogNavigation); 

		$jumpTo = ($useJump && $this->jumpTo) ? $this->jumpTo : $objCatalog->parentJumpTo;
		$arrPage=$this->getJumpTo($jumpTo, true);
		return ampersand($this->generateFrontendUrl($arrPage, $strPrependParams . '/items/' . (($this->Database->fieldExists($aliasField, $strTable) && strlen($objCatalog->$aliasField) && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $objCatalog->$aliasField : $objCatalog->id)));
		
	}

	private function generateCatalogEditUrl(Database_Result $objCatalog, $aliasField='alias', $strTable)
	{

		if (!strlen($aliasField))
		{
			$aliasField = 'alias';
		}

		$arrPage=$this->getJumpTo($this->catalog_editJumpTo, true);

		// Link to catalog edit
		return ampersand($this->generateFrontendUrl($arrPage, '/items/' . (($this->Database->fieldExists($aliasField, $strTable) && strlen($objCatalog->$aliasField) && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $objCatalog->$aliasField : $objCatalog->id)));
		
	}


	/**
	 * Generate a link and return it as string
	 * @param string
	 * @param object
	 * @param boolean
	 * @return string
	 */
	protected function generateLink(Database_Result $objCatalog, $aliasField, $strTable, $blnWindow, $blnEdit=false, $strLink='')
	{
		$linkUrl = (!$blnEdit ? $this->generateCatalogUrl($objCatalog, $aliasField, $strTable) : $this->generateCatalogEditUrl($objCatalog, $aliasField, $strTable));
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
	 * @param integer
	 * @param string
	 * @param variable
	 * @param boolean
	 * @return string
	 */

	protected function formatValue($id, $k, $value, $blnImageLink=true, $objCatalog=array())
	{
		$arrFormat = $this->parseValue($id, $k, $value, $blnImageLink, $objCatalog);
		return $arrFormat['html'];
	}
	

 	/**
	 * parse the catalog values and return information as an array
	 * @param integer
	 * @param string
	 * @param variable
	 * @param boolean
	 * @return array
	 */
  
	protected function parseValue($id, $k, $value, $blnImageLink=true, $objCatalog=array())
	{
		$raw = $value;
		$arrItems = deserialize($value, true);
		$arrValues = deserialize($value, true);
		$strHtml = $value;
		
		$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];

		switch ($fieldConf['eval']['catalog']['type'])
		{
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
				$files = $this->parseFiles($id, $k, $raw);
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
		
			// allow custom fields.
			default:
				if(array_key_exists($fieldConf['eval']['catalog']['type'], $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes']))
				{
					// HOOK: try to format the fieldtype as it is a custom added one.
					$fieldType=$GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$fieldConf['eval']['catalog']['type']];
					if(array_key_exists('parseValue', $fieldType) && is_array($fieldType['parseValue']))
					{
						foreach ($fieldType['parseValue'] as $callback)
						{
							$this->import($callback[0]);
							$ret=$this->$callback[0]->$callback[1]($id, $k, $value, $blnImageLink, $objCatalog, $this, $fieldConf);
							$arrItems = $ret['items'];
							$arrValues = $ret['values'];
							$strHtml = $ret['html'];
						}
					}
				}
		}		

		// special formatting 
		$formatStr = $fieldConf['eval']['catalog']['formatStr'];
		if (strlen($formatStr))
		{
			//$formatStr = htmlspecialchars_decode($fieldConf['eval']['catalog']['formatStr']);
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
							$value = $this->parseDate((strlen($formatStr) ? $formatStr : $GLOBALS['TL_CONFIG']['dateFormat']), $date->tstamp);
						}
						else
						{
							$value = '';
						}
						break;
				default:;
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
	 * parse files into HTML and other information and return as an array
	 * @param integer
	 * @param string
	 * @param variable
	 * @return array
	 */

	public function parseFiles($id, $k, $files)
	{	
		$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$k];

		$blnThumnailOverride = $this->catalog_thumbnails_override && ($this instanceof ModuleCatalogList || $this instanceof ModuleCatalogFeatured || $this instanceof ModuleCatalogRelated || $this instanceof ModuleCatalogReference);

		// setup standard linking
		$showLink = $fieldConf['eval']['catalog']['showLink'];
		
		// image override 
		if ($blnThumnailOverride)
		{
			$showLink = $this->catalog_imagemain_field == $k ? $this->catalog_imagemain_fullsize : 
					($this->catalog_imagegallery_field == $k ? $this->catalog_imagegallery_fullsize : ''); // override default
		}

		$sortBy = $blnThumnailOverride ? $this->sortBy : $fieldConf['eval']['catalog']['sortBy'];
		
		$files = deserialize($files,true);
		if (!is_array($files) || count($files) < 1)
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
					$class = (($counter == 0) ? ' first' : '') . ((($counter % 2) == 0) ? ' even' : ' odd');

					$this->parseMetaFile(dirname($file), true);
					$strBasename = strlen($this->arrMeta[$objFile->basename][0]) ? $this->arrMeta[$objFile->basename][0] : specialchars($objFile->basename);
					$alt = (strlen($this->arrMeta[$objFile->basename][0]) ? $this->arrMeta[$objFile->basename][0] : ucfirst(str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename))));

					$auxDate[] = $objFile->mtime;
					
					// images
					if ($showImage)
					{
						$w = $fieldConf['eval']['catalog']['imageSize'][0] ? $fieldConf['eval']['catalog']['imageSize'][0] : '';
						$h = $fieldConf['eval']['catalog']['imageSize'][1] ? $fieldConf['eval']['catalog']['imageSize'][1] : '';
						if ($blnThumnailOverride)
						{
							$newsize =  deserialize($this->catalog_imagemain_field == $k ? $this->catalog_imagemain_size 
								: ($this->catalog_imagegallery_field == $k ? $this->catalog_imagegallery_size : array()) );
							$w = ($newsize[0] ? $newsize[0] : '');
							$h = ($newsize[1] ? $newsize[1] : '');
						}
						$src = $this->getImage($this->urlEncode($file), $w, $h, $fieldConf['eval']['catalog']['imageSize'][2]);
						$size = getimagesize(TL_ROOT . '/' . $src);
						$arrSource[$file] = array
						(
							'src'	=> $src,
							'alt'	=> $alt,
							'lb'	=> 'lb'.$id,
							'w' 	=> $size[0],
							'h' 	=> $size[1],
							'wh'	=> $size[3],
							'caption' => (strlen($this->arrMeta[$objFile->basename][2]) ? $this->arrMeta[$objFile->basename][2] : ''),
							'metafile' => $this->arrMeta[$objFile->basename],
						);
						$tmpFile = '<img src="'.$src.'" alt="'.$alt.'" '.$size[3].' />';
						if ($showLink)	
						{
							// $tmpFile = '<a rel="lightbox[lb'.$id.']" href="'.$file.'" title="'.$alt.'">'.$tmpFile.'</a>';
							// we have to supply the catalog id here as we might have more than one catalog with a field with the same name 
							// which will cause the lightbox to display the images for items with the same id in both.
							$tmpFile = '<a rel="lightbox[lb' . $this->strTable . $id . ']" href="'.$file.'" title="'.$alt.'">'.$tmpFile.'</a>';
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
								if ($blnThumnailOverride)
								{
									$newsize =  deserialize($this->catalog_imagemain_field == $k ? $this->catalog_imagemain_size 
										: ($this->catalog_imagegallery_field == $k ? $this->catalog_imagegallery_size : array()) );
									$w = ($newsize[0] ? $newsize[0] : '');
									$h = ($newsize[1] ? $newsize[1] : '');
								}
								$src = $this->getImage($this->urlEncode($file . '/' . $subfile), $w, $h, $fieldConf['eval']['catalog']['imageSize'][2]);
								$size = getimagesize(TL_ROOT . '/' . $src);

								$arrSource[$file . '/' . $subfile] = array
								(
									'src'	=> $src,
									'alt'	=> $alt,
									'lb'	=> 'lb'.$id,
									'w' 	=> $size[0],
									'h' 	=> $size[1],
									'wh'	=> $size[3],
									'caption' => (strlen($this->arrMeta[$objFile->basename][2]) ? $this->arrMeta[$objFile->basename][2] : ''),
									'metafile' => $this->arrMeta[$objFile->basename],
								);

								$tmpFile = '<img src="'.$src.'" alt="'.$alt.'" '.$size[3].' />';
								if ($showLink)	
								{
									// $tmpFile = '<a rel="lightbox[lb'.$id.']" title="'.$alt.'" href="'.$file . '/' . $subfile.'">'.$tmpFile.'</a>';
									// we have to supply the catalog id here as we might have more than one catalog with a field with the same name here.
									$tmpFile = '<a rel="lightbox[lb' . $this->strTable . $id . ']" title="'.$alt.'" href="'.$file . '/' . $subfile.'">'.$tmpFile.'</a>';

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
	 * Replace Catalog InsertTags including TL InsertTags
	 * @param string
	 * @param array
	 * @return string
	 */

	protected function replaceCatalogTags($strValue, $arrCatalog)
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
		
		// Replace standard insert tags
		if (strlen($strValue))
		{
			$strValue = $this->replaceInsertTags($strValue);
		}

		return $strValue;
	} 



	/**
	 * Generate Front-end Url with only catalog ID as parameter
	 * @param integer
	 * @param integer
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

		if($field)
		{
			$strParams = $GLOBALS['TL_CONFIG']['disableAlias'] ? '&amp;' . $field . '=' . $value  : '/' . $field . '/' . $value;
		} else {
			$strParams = '';
		}

		// Return link to catalog reader page with item alias
		return ampersand($this->generateFrontendUrl($pageRow, $strParams));
		
	}


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
//					'pageTitle' => specialchars($objJump->pageTitle),
					'title' => specialchars($objNodes->$showField),
					'link' => $objNodes->$showField,
					'href' => $href,
//					'alias' => $objJump->alias,
//					'target' => (($objJump->type == 'redirect' && $objJump->target) ? ' window.open(this.href); return false;' : ''),
//					'description' => str_replace(array("\n", "\r"), array(' ' , ''), $objJump->description),
//					'accesskey' => $objJump->accesskey,
//					'tabindex' => $objJump->tabindex
				);

				continue;
			}

			$strClass = trim('item' . (strlen($objJump->cssClass) ? ' ' . $objJump->cssClass : ''));

			$items[] = array
			(
				'isActive' => false,
				'subitems' => $subitems,
				'class' => (strlen($strClass) ? $strClass : ''),
//				'pageTitle' => specialchars($objJump->pageTitle),
				'title' => specialchars($objNodes->$showField),
				'link' => $objNodes->$showField,
				'href' => $href,
//				'alias' => $objJump->alias,
//				'target' => (($objJump->type == 'redirect' && $objJump->target) ? ' window.open(this.href); return false;' : ''),
//				'description' => str_replace(array("\n", "\r"), array(' ' , ''), $objJump->description),
//				'accesskey' => $objJump->accesskey,
//				'tabindex' => $objJump->tabindex
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


	protected $arrTrail=array();
	protected $objNavField=NULL;

	/**
	 * Recursively compile the catalog navigation menu and return it as HTML string
	 * @param integer
	 * @param integer
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
			$objNodes = $this->Database->execute('SELECT id, '.$this->objNavField->valueField.', 0 AS childCount, '. $this->objNavField->sourceColumn .' AS name FROM '. $this->objNavField->sourceTable .', 0 AS itemCount ORDER BY '.$this->objNavField->sort);
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
	 * @param integer
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
	 * @param object
	 */
	public function processComments(Database_Result $objCatalog)
	{
		// Comments
		$objArchive = $this->Database->prepare('SELECT * FROM tl_catalog_types WHERE id=?')
									 ->limit(1)
									 ->execute($objCatalog->pid);

		if ($objArchive->numRows < 1 || !$objArchive->allowComments || !in_array('comments', $this->Config->getActiveModules()))
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
}

?>