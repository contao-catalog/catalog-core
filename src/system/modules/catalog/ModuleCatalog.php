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

	protected	$arrTree;	

	protected	$cacheJumpTo;	

	public function generate()
	{
		if (!strlen($this->catalog))
		{
			return '';
		}

		// get DCA
		$objCatalog = $this->Database->prepare("SELECT tableName FROM tl_catalog_types WHERE id=?")
				->limit(1)
				->execute($this->catalog);
		
		if ($objCatalog->numRows > 0 && $objCatalog->tableName)
		{
			$this->strTable = $objCatalog->tableName;

			// dynamically load dca for catalog operations
			$this->Import('Catalog');
			$GLOBALS['TL_DCA'][$objCatalog->tableName] = $this->Catalog->getCatalogDca($this->catalog);
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
					$objDownload = $this->Database->prepare("SELECT id FROM ".$this->strTable." WHERE LOCATE(?,".$k.")>0 OR LOCATE(?,".$k.")>0")
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



	protected function getCatalogFields($arrTypes=false)
	{
		if(!$arrTypes)
			$arrTypes=$GLOBALS['BE_MOD']['content']['catalog']['typesCatalogFields'];
		$fields = array();
		$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=? AND type IN ('" . join("','", $arrTypes) . "') ORDER BY sorting")
							->execute($this->catalog);

		while ($objFields->next())
		{
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
   
		// Setup Fields
		$fields = $this->getCatalogFields();

		if (!strlen($this->catalog_tags_mode))
		{
			$this->catalog_tags_mode = "AND";
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
									$tags = split(',', $v);
									$newtags = array();
									foreach($tags as $tag)
									{
										$newtags[] = $arrAlias[$tag];
									}
									$v = join(',', $newtags);
									break;
									
								case 'select':
									$v = $arrAlias[$v]; 
									break;
									
								default:;
							}
						}
						
						$current[$field] = $v;


/*
						// convert alias values to IDs
						$arrAlias = array_flip($this->getAliasOptionList($fieldConf));
						
						switch ($fieldConf['eval']['catalog']['type'])
						{
							case 'select':

								//$tmpValue = $this->convertAliasToId($field, array($this->Input->post($field)));
								//$current[$field] = $tmpValue[0];
								break;

							case 'tags':
								$tags = split(',', $this->Input->post($field));
								$tags = $this->convertAliasToId($field, $tags);
								$current[$field] = join(',', $tags);
								break;

							default:
								$current[$field] = $this->Input->post($field);
						}

						$current[$field] = $this->Input->post($field);
*/


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
								$procedure['search'][$field] = '('.join(' OR ',$tmpDate).')';
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
							$procedure['search'][$field] = "(".$field." IN (SELECT id FROM ".$itemTable." WHERE ".$valueCol." LIKE ?".($fieldConf['options']? " AND id IN (".join(',',array_keys($fieldConf['options'])).")":"")."))";
							$values['search'][$field] = '%'.$this->Input->get($this->strSearch).'%';
							break;
								
					case 'tags' :

							list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
							$tagQuery = $this->Database->prepare("SELECT id FROM ".$itemTable." WHERE ".$valueCol." LIKE ?".($fieldConf['options']? " AND id IN (".join(',',array_keys($fieldConf['options'])).")":""))
									->execute('%'.$this->Input->get($this->strSearch).'%');

							// search only if search string in tag namelist
							if ($tagQuery->numRows)
							{
								$tmpTags = '';
								while($tagQuery->next())
								{
									$tmpTags[] = "FIND_IN_SET(?,".$field.")";
									$values['search'][$field][] = $tagQuery->id;
								}
								$procedure['search'][$field] = '('.join(' + ',$tmpTags).' > 0)';
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
				$rangeValues = trimsplit('__', $this->Input->get($field));
				$rangeOptions[$field]['label'] = $fieldConf['label'][0];
				$rangeOptions[$field]['min'] = 	$rangeValues[0];
				$rangeOptions[$field]['max'] = $rangeValues[1];
				
				$procedure['where'][] = '('.$field.' BETWEEN ? AND ?)';
				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'number':
						$values['where'][] = intval($rangeValues[0]);
						$values['where'][] = intval($rangeValues[1]);
						break;

					case 'decimal':
						$values['where'][] = floatval($rangeValues[0]);
						$values['where'][] = floatval($rangeValues[1]);
						break;

					case 'date':
						$values['where'][] = strtotime($rangeValues[0]);
						$values['where'][] = strtotime($rangeValues[1]);
						break;

					default:
						$values['where'][] = $rangeValues[0];
						$values['where'][] = $rangeValues[1];
				}
				
				$current[$field] = $this->Input->get($field);

			}
			
			//GET filter values
			elseif (strlen($this->Input->get($field)))
			{

				switch ($fieldConf['eval']['catalog']['type'])
				{
					case 'tags':					
						$tags = split(',', $current[$field]);

						$tmpTags = array();
						foreach ($tags as $tag)
						{
							$tmpTags[] = "FIND_IN_SET(?,".$field.")";
							$values['tags'][] = $tag;
						}
						$procedure['tags'][] = '('.join(($this->catalog_tags_mode == 'AND' ? ' * ' : ' + '),$tmpTags).' > 0)';

						if ($blnTree && in_array($field, $arrTree)) 
						{
							$procedure['tree'][] = '('.join(($this->catalog_tags_mode == 'AND' ? ' * ' : ' + '),$tmpTags).' > 0)';
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
					case 'checkbox':

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

	private function getAliasOptionList($fieldConf)
	{
		// get alias column
		list($itemTable, $valueCol) = explode('.', $fieldConf['eval']['catalog']['foreignKey']);
		$aliasField = $this->getAliasField($itemTable);
		
		// get existing alias values of options in DB
		$objList = $this->Database->prepare("SELECT id,".$aliasField." FROM ".$itemTable . 
						($fieldConf['options'] ? " WHERE id IN (".join(',',array_keys($fieldConf['options'])).")":""))
				->execute();
		
		$return = array();
		while ($objList->next())
		{
			// check if this is still ok to use id if alias is empty
			$return[$objList->id] = strlen($objList->$aliasField) ? $objList->$aliasField : $objList->id;
		}
		
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
							$tags = split(',', $v);
							$newtags = array();
							foreach($tags as $tag)
							{
								$newtags[] = $arrAlias[$tag];
							}
							$v = join(',', $newtags);
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
			'query' => (is_array($query) ? join(' AND ', $query) : ''),
			'params' => (is_array($params) ? $params : ''),
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
		if ($this->catalog_filter_enable && count($arrFilters) && strlen($arrFilters[0]))
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

				$fieldConf = &$GLOBALS['TL_DCA'][$this->strTable]['fields'][$field];
				$options = array();
				
				switch ($fieldConf['eval']['catalog']['type'])
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

						// get existing options in DB
						$objFilter = $this->Database->prepare("SELECT DISTINCT(".$field.") FROM ".$this->strTable . ($query['query'] ? " WHERE ".$query['query'] : '') )
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
									if ($selected)
									{
										$addOption['selected'] = true;
									}
									array_push($options, $addOption);

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

						// Changed by c.schiffler seemed like a bug to me as it threw an Exception.
						// $tmpTags = '';
						$tmpTags = array();
						foreach ($fieldConf['options'] as $id=>$option)
						{
								$tmpTags[] = "SUM(FIND_IN_SET(".$id.",".$field.")) AS ".$field.$id;
						}
						$objFilter = $this->Database->prepare("SELECT ".join(', ',$tmpTags)." FROM ".$this->strTable. ($query['query'] ? " WHERE ". $query['query'] : ''))
								->execute($query['params']);
						if ($objFilter->numRows)
						{
							$row = $objFilter->row();

							foreach ($fieldConf['options'] as $id=>$option)
							{
								if ($row[$field.$id])
								{
									$selected = in_array($id, split(',',$current[$field]));
									$newcurrent = $current;
									$newids = strlen($current[$field]) ? split(',', $current[$field]) : array();
									$newids = array_unique(!$selected ? array_merge($newids, array($id)) : array_diff($newids, array($id)));
									$newcurrent[$field] = ($this->catalog_tags_multi ? join(',',$newids) : $id);
									$this->clearTree($field, $newcurrent, $tree);
									$url = $this->generateFilterUrl($newcurrent, true, $blnLast);
									
									$blnList = ($selected && $input=='list');

									$addOption = array();
									$addOption['value'] = $url;
									$addOption['label'] = $blnList ? sprintf($GLOBALS['TL_LANG']['MSC']['optionselected'], $option) : $option;
									$addOption['id'] = $id;
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
										$label = join(',',deserialize($row[$field],true));
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
					
					default:;

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
								'rgxp' 			=> 'date',
								'datepicker' => $this->getDatePickerString(),
							);
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
		if ($this->catalog_date_enable && count($arrDates) && strlen($arrDates[0]))
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
		if ($this->catalog_sort_enable && count($arrSort) && strlen($arrSort[0]))
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

		return $settings; 
	}



	private function parseWidget(&$widget)
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
		<li class="option%s%s">%s%s%s</li>',
						(' list_'.$class),
						($selected ? ' active' : ''),
						(!$selected ? sprintf('<a href="%s" title="%s">', $option['value'], $option['label']) : '<p class="active">'),
						$option['label'],
						(!$selected ? '</a>' : '</p>')
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
		
					$arrFields[] = sprintf('<input type="text" name="%s[]" id="ctrl_%s" class="text%s" value="%s"%s />',
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
				
			default:;
		}		
		return $labelSuffix;
	}



	private function getJumpTo($catalogJump, $blnJumpTo=true)
	{
		global $objPage;

		if ($this->cacheJumpTo['id'] == $catalogJump)
		{
			return;
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
			$this->cacheJumpTo = array (
				'id' 		=> $pageRow['id'],
				'page'	=> $pageRow
			);
		}
	}



	private function generateFilterUrl($arrGet = array(), $blnRoot=false, $blnJumpTo=true)
	{
		$this->getJumpTo($this->catalog_jumpTo, $blnJumpTo);

		$strParams = '';

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
						$tags = split(',', $v);
						$newtags = array();
						foreach($tags as $tag)
						{
							$newtags[] = $arrAlias[$tag];
						}
						$v = join(',', $newtags);
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

		return ($blnRoot ? $this->Environment->base : '') . $this->generateFrontEndUrl($this->cacheJumpTo['page'], $strParams);		
	}



	/**
	 * Parse one or more items and return them as array
	 * @param object
	 * @param boolean
	 * @return array
	 */
	protected function parseCatalog(Database_Result $objCatalog, $blnLink=true, $template='catalog_full', $visible=null, $blnImageLink=false)
	{
		$objTemplate = new FrontendTemplate($template);

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
//				$objArchive = $this->Database->prepare("SELECT aliasField FROM tl_catalog_types where name=?")
//										 ->limit(1)
//										 ->execute($objCatalog->catalog_name);
				// We have to use pid here as Catalog names are not unique - Does this imply any problems in any module?
				$objArchive = $this->Database->prepare("SELECT aliasField FROM tl_catalog_types where id=?")
										 ->limit(1)
										 ->execute($objCatalog->pid);

	
				$aliasField = $objArchive->numRows ? $objArchive->aliasField : 'alias';
			}
			
			$class = (($i == 0) ? ' first' : '') . ((($i + 1) == $objCatalog->numRows) ? ' last' : '') . ((($i % 2) == 0) ? ' even' : ' odd');
			$arrCatalog[$i]['class'] = $class;

			if ($blnLink) 
			{
				$arrCatalog[$i]['link'] = $this->generateLink($objCatalog, $aliasField, $this->strTable);
				$arrCatalog[$i]['url'] = $this->generateCatalogUrl($objCatalog, $aliasField, $this->strTable);
			}


			$arrData = $objCatalog->row();
			// check if editing of this record is disabled for frontend.
			$editingallowedByFields=true;
			foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'] as $field)
			{
				// HOOK: additional permission checks if this field allows editing of this record (for the current user).
				$fieldType = $GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$field['eval']['catalog']['type']];
				if(is_array($fieldType) && array_key_exists('checkPermissionFEEdit', $fieldType) && is_array($fieldType['checkPermissionFERecordEdit']))
				{
					foreach ($fieldType['checkPermissionFERecordEdit'] as $callback)
					{
						$this->import($callback[0]);
						// TODO: Do we need more parameters here?
						if(!($this->$callback[0]->$callback[1]($this->strTable, $arrData)))
						{
							$editingallowedByFields=false;
							break;
						}
					}
				}
			}

			if ($this->catalog_edit_enable && $editingallowedByFields)
			{
				$arrCatalog[$i]['linkEdit'] = $this->generateLink($objCatalog, $aliasField, $this->strTable, true);
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
					$arrValues['html'] = $this->generateLink($objCatalog, $aliasField, $this->strTable, false, $arrValues['html']);
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
							$objRef = $this->Database->prepare("SELECT * FROM ".$refTable." WHERE id IN (".trim($v).") ORDER BY ".$sortOrder)
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
											$objRef->parentLink = $this->generateLink($objRef, $objJump->aliasField, $objJump->tableName);
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

					default:;									
				}

			}
			
			$i++;
		}

		$objTemplate->entries = $arrCatalog;

		return $objTemplate->parse();
	}


	private function generateCatalogUrl(Database_Result $objCatalog, $aliasField='alias', $strTable)
	{

		if (!strlen($aliasField))
		{
			$aliasField = 'alias';
		}

		$useJump = ($this instanceof ModuleCatalogList || $this instanceof ModuleCatalogFeatured || $this instanceof ModuleCatalogRelated || $this instanceof ModuleCatalogReference || $this instanceof ModuleCatalogNavigation); 

		$jumpTo = ($useJump && $this->jumpTo) ? $this->jumpTo : $objCatalog->parentJumpTo;
		$this->getJumpTo($jumpTo, true);

		return ampersand($this->generateFrontendUrl($this->cacheJumpTo['page'], '/items/' . (($this->Database->fieldExists($aliasField, $strTable) && strlen($objCatalog->$aliasField) && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $objCatalog->$aliasField : $objCatalog->id)));
		
	}

	private function generateCatalogEditUrl(Database_Result $objCatalog, $aliasField='alias', $strTable)
	{

		if (!strlen($aliasField))
		{
			$aliasField = 'alias';
		}

		$this->getJumpTo($this->catalog_editJumpTo, true);

		// Link to catalog edit
		return ampersand($this->generateFrontendUrl($this->cacheJumpTo['page'], '/items/' . (($this->Database->fieldExists($aliasField, $strTable) && strlen($objCatalog->$aliasField) && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $objCatalog->$aliasField : $objCatalog->id)));
		
	}


	/**
	 * Generate a link and return it as string
	 * @param string
	 * @param object
	 * @param boolean
	 * @return string
	 */
	private function generateLink(Database_Result $objCatalog, $aliasField, $strTable, $blnEdit=false, $strLink='')
	{
		$linkUrl = (!$blnEdit ? $this->generateCatalogUrl($objCatalog, $aliasField, $strTable) : $this->generateCatalogEditUrl($objCatalog, $aliasField, $strTable));
		$strLink = strlen($strLink) ? $strLink : (!$blnEdit ? $GLOBALS['TL_LANG']['MSC']['viewCatalog'] : $GLOBALS['TL_LANG']['MSC']['editCatalog']);
		$strTitle = (!$blnEdit ? $GLOBALS['TL_LANG']['MSC']['viewCatalog'] : $GLOBALS['TL_LANG']['MSC']['editCatalog']);

		return sprintf('<a href="%s" title="%s">%s</a>',
						$linkUrl,
						$strTitle,
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
					$strHtml = join(', ', $arrValues);
				}
				break;

			case 'file':
				$files = $this->parseFiles($id, $k, $raw);
				$arrItems = $files['files'];
				$arrValues = $files['src'];
				$strHtml = join('', $files['html']);
				break;


			case 'url':
				if (strlen($raw))
				{
					// E-mail addresses
					//	if (preg_match('/^[a-zA-Z0-9\.\+\/\?#%:,;\{\}\[\]@&=~_-]*$/', $varInput))
					if (preg_match_all('/^(mailto:)?(\w+([_\.-]*\w+)*@\w+([_\.-]*\w+)*\.[a-z]{2,6})$/i', $value, $matches))
					{
						$this->import('String');
						$emailencode = $this->String->encodeEmail($matches[2][0]);
						$arrValues[0] = $emailencode;
						$strHtml = '<a href="mailto:' . $emailencode . '">' . $emailencode . '</a>';
					}
					else 
					{
						$arrValues[0] = $raw;
						$strHtml = '<a href="'.$raw.'"'.(preg_match('@^(https?://|ftp://)@i', $value) ? ' onclick="window.open(this.href); return false;"' : '').'>'.$raw.'</a>';
					}
				}
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
		
			// Changed by c.schiffler to allow custom fields.
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
							$ret=$this->$callback[0]->$callback[1]($id, $k, $value, $blnImageLink, $objCatalog, $this);
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
						
/*
				case 'money':
						$value = money_format($formatStr, $value);
						break;
*/
						
				case 'date':
						$date = new Date($raw);
						$value = $this->parseDate((strlen($formatStr) ? $formatStr : $GLOBALS['TL_CONFIG']['dateFormat']), $date->tstamp);
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
		
		$files = deserialize($files,true);
		if (!is_array($files) || count($files) < 1)
		{
			continue;
		}	

		// required for parseMetaFile function (in FrontEnd)
		$this->multiSRC = $files;					

		$arrFiles = array();
		$arrSource = array();
		$arrValues = array();

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
						$src = $this->getImage($this->urlEncode($file), $w, $h);
						$size = getimagesize(TL_ROOT . '/' . $src);
						$arrSource[] = array
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
						$url = $this->Environment->request . (($GLOBALS['TL_CONFIG']['disableAlias'] || !$GLOBALS['TL_CONFIG']['rewriteURL']
&& count($_GET) || strlen($_GET['page'])) ? '&amp;' : '?'). 'file=' . $this->urlEncode($file);
						$icon = 'system/themes/' . $this->getTheme() . '/images/' . $objFile->icon;
						$sizetext = '('.number_format(($objFile->filesize/1024), 1, $GLOBALS['TL_LANG']['MSC']['decimalSeparator'], $GLOBALS['TL_LANG']['MSC']['thousandsSeparator']).' kB)';
						$arrSource[] = array
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

					$arrFiles[] = $file;
					$arrValues[] = '<span class="'.($showImage ? 'image' : 'file').$class.'">'.$tmpFile.'</span>';
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
								$src = $this->getImage($this->urlEncode($file . '/' . $subfile), $w, $h);
								$size = getimagesize(TL_ROOT . '/' . $src);
								$arrSource[] = array
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
								$arrSource[] = array
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
							
							$arrFiles[] = $file . '/' . $subfile;
							$arrValues[] = '<span class="'.($showImage ? 'image' : 'file').$class.'">'.$tmpFile.'</span>';
							$counter ++;
						}
					}
				}
			}
		}

		$return['files']	= $arrFiles;
		$return['src'] 		= $arrSource;
		$return['html']		= $arrValues;

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
	
		global $objPage;

		// bugfix c.schiffler, we must handle a jumpTo if it is present.
		if($this->jumpTo)
		{
			$this->getJumpTo($this->jumpTo, false);
			// Get internal page (from parent catalog)
			$objJump = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
									  ->limit(1)
									  ->execute($this->jumpTo);
			// TODO: shall we fallback to the current page then?
			if ($objJump->numRows < 1)
			{
				return '';
			}
			$pageRow = $objJump->fetchAssoc();
		} else
			$pageRow = $objPage->row();
		// end bugfix c.schiffler, we must handle a jumpTo if it is present.


		if (!count($pageRow))
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


	protected function renderCatalogItems($id, $level=1, $blnTags=false)
	{

		$aliasField = $this->getAliasField($this->strTable);

		$strWhere = $blnTags ? "FIND_IN_SET(?,".$this->catalog_navigation.")" : $this->catalog_navigation."=?";

		// query database
		$objNodes = $this->Database->prepare("SELECT * FROM ".$this->strTable." WHERE ".$strWhere)
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
			$href = $this->generateCatalogUrl($objNodes, $aliasField, $this->strTable);
			
			// Active field
			if ($this->Input->get('items') == $objNodes->id || $this->Input->get('items') == $objNodes->$aliasField)
			{
				$strClass =  trim('item' . (strlen($objJump->cssClass) ? ' ' . $objJump->cssClass : ''));

				$items[] = array
				(
					'isActive' => true,
					'subitems' => $subitems,
					'class' => (strlen($strClass) ? $strClass : ''),
					'pageTitle' => specialchars($objJump->pageTitle),
					'title' => specialchars($objNodes->$showField),
					'link' => $objNodes->$showField,
					'href' => $href,
					'alias' => $objJump->alias,
					'target' => (($objJump->type == 'redirect' && $objJump->target) ? ' window.open(this.href); return false;' : ''),
					'description' => str_replace(array("\n", "\r"), array(' ' , ''), $objJump->description),
					'accesskey' => $objJump->accesskey,
					'tabindex' => $objJump->tabindex
				);

				continue;
			}

			$strClass = trim('item' . (strlen($objJump->cssClass) ? ' ' . $objJump->cssClass : ''));

			$items[] = array
			(
				'isActive' => false,
				'subitems' => $subitems,
				'class' => (strlen($strClass) ? $strClass : ''),
				'pageTitle' => specialchars($objJump->pageTitle),
				'title' => specialchars($objNodes->$showField),
				'link' => $objNodes->$showField,
				'href' => $href,
				'alias' => $objJump->alias,
				'target' => (($objJump->type == 'redirect' && $objJump->target) ? ' window.open(this.href); return false;' : ''),
				'description' => str_replace(array("\n", "\r"), array(' ' , ''), $objJump->description),
				'accesskey' => $objJump->accesskey,
				'tabindex' => $objJump->tabindex
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
	 * Recursively compile the catalog navigation menu and return it as HTML string
	 * @param integer
	 * @param integer
	 * @return string
	 */
	protected function renderCatalogNavigation($pid, $level=1)
	{

		$this->getJumpTo($this->jumpTo, false);


		// Get internal page (from parent catalog)
		$objJump = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
							 	  ->limit(1)
								  ->execute($this->jumpTo);

		if ($objJump->numRows < 1)
		{
			return '';
		}

		$arrJump = $objJump->fetchAssoc();

	
		// get reference table and column		
		$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=? AND colName=?")
											->limit(1)
											->execute($this->catalog, $this->catalog_navigation);
		
		if (!$objFields->numRows)
		{
			return '';
		}

		$sourceTable = $objFields->itemTable;
		$sourceColumn = $objFields->itemTableValueCol;
		$blnChildren = $objFields->childrenSelMode;

		$ids = ($pid == 0) ? ($objFields->limitItems && strlen($objFields->items) ? deserialize($objFields->items) : array(0)) : array($pid);
		$strRoot = ((!$blnChildren && $level == 1) ? 'id' : 'pid');

		$valueField = $this->getAliasField($sourceTable);
								
		// check if this tree has a pid or a flat table
		$treeView = $this->Database->fieldExists('pid', $sourceTable);
		$sort = $this->Database->fieldExists('sorting', $sourceTable) ? 'sorting' : $sourceColumn;
		if ($treeView)
		{
			$objNodes = $this->Database->prepare("SELECT id, ".$valueField.", (SELECT COUNT(*) FROM ". $sourceTable ." i WHERE i.pid=o.id) AS childCount, " . $sourceColumn . " AS name FROM ". $sourceTable. " o WHERE ".$strRoot." IN (".join(',',$ids).") ORDER BY ". $sort)
									 ->execute();
		}
		
		if (!$treeView || ($objNodes->numRows == 0 && $level == 1))  // 0 => 1 ??
		{
			$objNodes = $this->Database->execute("SELECT id, ".$valueField.", 0 AS childCount, ". $sourceColumn ." AS name FROM ". $sourceTable ." ORDER BY ".$sort);
		}

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

		// Browse field nodes
		while($objNodes->next())
		{
			$subitems = '';

			// if catalog reader, select item
			// if current field value is selected, display children

// !showlevel and hardLimit not working if set: Start=1 and Stop=2
			
 			if (!$this->showLevel || $this->showLevel >= $level || (!$this->hardLimit && ($this->Input->get($this->catalog_navigation) == $objNodes->$valueField)))
			{
				// check order
				if ($this->catalog_show_items && $this->Input->get($this->catalog_navigation) == $objNodes->$valueField)
				{
					$subitems .= $this->renderCatalogItems($objNodes->id, $level, ($objFields->type == 'tags'));
				}
				if (count($objNodes->childCount) && $objFields->childrenSelMode) 
				{
					$subitems .= $this->renderCatalogNavigation($objNodes->id, $level);
				}
			}

			// setup field and value
			$field = $objFields->colName;
			$value = $objNodes->$valueField;
				
			$href = $this->generateCatalogNavigationUrl($field, $value);

			// Active field
			if ($this->Input->get($this->catalog_navigation) == $objNodes->$valueField)
			{
				$strClass = trim((strlen($subitems) ? 'submenu' : '') . (strlen($objJump->cssClass) ? ' ' . $objJump->cssClass : ''));

				$items[] = array
				(
					'isActive' => true,
					'subitems' => $subitems,
					'class' => (strlen($strClass) ? $strClass : ''),
					'pageTitle' => specialchars($objJump->pageTitle),
					'title' => specialchars($objNodes->name),
					'link' => $objNodes->name,
					'href' => $href,
					'alias' => $objJump->alias,
					'target' => (($objJump->type == 'redirect' && $objJump->target) ? ' window.open(this.href); return false;' : ''),
					'description' => str_replace(array("\n", "\r"), array(' ' , ''), $objJump->description),
					'accesskey' => $objJump->accesskey,
					'tabindex' => $objJump->tabindex
				);
				continue;
			}

// !fix trail

			$strClass = trim((strlen($subitems) ? 'submenu' : '') . (strlen($objJump->cssClass) ? ' ' . $objJump->cssClass : '') . (in_array($objJump->id, $objPage->trail) ? ' trail' : ''));
			// contributed patch by m.reimann@patchwork-webdesign.de attached to issue #72
			// check's if there are actually items for this navigation entry.
			$idArray = $this->Database->prepare("SELECT concat(pid,',',group_concat(id)) AS tree FROM  " . $sourceTable . " AS t where pid=? group by pid")
										->execute($objNodes->id)->next();
			$objCount = $this->Database->prepare("SELECT id FROM" . $this->strTable . " AS t where " . $this->arrData['catalog_navigation'] . "  in (" . ($idArray->tree ? implode(',', array($objNodes->id, $idArray->tree)) : $objNodes->id) . ")")
										->execute()->numRows;
			if($objCount)
			{
			// end of contributed patch by m.reimann@patchwork-webdesign.de attached to issue #72
				$items[] = array
				(
					'isActive' => false,
					'subitems' => $subitems,
					'class' => (strlen($strClass) ? $strClass : ''),
					'pageTitle' => specialchars($objJump->pageTitle),
					'title' => specialchars($objNodes->name),
					'link' => $objNodes->name,
					'href' => $href,
					'alias' => $objJump->alias,
					'target' => (($objJump->type == 'redirect' && $objJump->target) ? ' window.open(this.href); return false;' : ''),
					'description' => str_replace(array("\n", "\r"), array(' ' , ''), $objJump->description),
					'accesskey' => $objJump->accesskey,
					'tabindex' => $objJump->tabindex
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
	
	/**
	 * List and generate comment form
	 * @param object
	 */
	public function processComments(Database_Result $objCatalog)
	{
		// Comments
		$objArchive = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=?")
									 ->limit(1)
									 ->execute($objCatalog->pid);

		if (/*$objCatalog->noComments ||*/ $objArchive->numRows < 1 || !$objArchive->allowComments)
		{
			$this->Template->allowComments = false;
			return;
		}

		$limit = null;
		$arrComments = array();

		// Pagination
		if ($objArchive->perPage > 0)
		{
			$page = $this->Input->get('com_page') ? $this->Input->get('com_page') : 1;
			$limit = $objArchive->perPage;
			$offset = ($page - 1) * $objArchive->perPage;

			// Get total number of comments
			$objTotal = $this->Database->prepare("SELECT COUNT(*) AS count FROM tl_catalog_comments WHERE pid=? AND catid=?" . (!BE_USER_LOGGED_IN ? " AND published=?" : ""))
									   ->execute($objCatalog->id, $objCatalog->pid, 1);

			// Add pagination menu
			$objPagination = new PaginationCustom($objTotal->count, $objArchive->perPage, 7, 'com_page');
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		// Get all published comments
		$objCommentsStmt = $this->Database->prepare("SELECT * FROM tl_catalog_comments WHERE pid=? AND catid=?" . (!BE_USER_LOGGED_IN ? " AND published=?" : "") . " ORDER BY date" . (($objArchive->sortOrder == 'descending') ? " DESC" : ""));

		if ($limit)
		{
			$objCommentsStmt->limit($limit, $offset);
		}

		$objComments = $objCommentsStmt->execute($objCatalog->id, $objCatalog->pid, 1);

		if ($objComments->numRows)
		{
			$count = 0;
			$objTemplate = new FrontendTemplate($objArchive->template);

			while ($objComments->next())
			{
				$objTemplate->name = $objComments->name;
				$objTemplate->email = $objComments->email;
				$objTemplate->website = $objComments->website;
				$objTemplate->comment = trim($objComments->comment);
				$objTemplate->datim = date($GLOBALS['TL_CONFIG']['datimFormat'], $objComments->date);
				$objTemplate->date = date($GLOBALS['TL_CONFIG']['dateFormat'], $objComments->date);
				$objTemplate->class = (($count++ % 2) == 0) ? ' even' : ' odd';
				$objTemplate->by = $GLOBALS['TL_LANG']['MSC']['comment_by'];
				$objTemplate->id = 'c' . $objComments->id;
				$objTemplate->ip = $objComments->ip;
				$objTemplate->timestamp = $objComments->date;

				$arrComments[] = $objTemplate->parse();
			}
		}

		$this->Template->allowComments = true;
		$this->Template->comments = $arrComments;
		$this->Template->addComment = $GLOBALS['TL_LANG']['MSC']['addComment'];
		$this->Template->name = $GLOBALS['TL_LANG']['MSC']['com_name'];
		$this->Template->email = $GLOBALS['TL_LANG']['MSC']['com_email'];
		$this->Template->website = $GLOBALS['TL_LANG']['MSC']['com_website'];

		// Access control
		if ($objArchive->requireLogin && !BE_USER_LOGGED_IN && !FE_USER_LOGGED_IN)
		{
			$this->Template->protected = true;
			return;
		}


		// check if member logged in
		$this->import('FrontendUser', 'User');

		$blnHide = (FE_USER_LOGGED_IN && $objArchive->hideMember);

		// Form fields
		$arrFields = array
		(
			'name' => array
			(
				'name' => 'name',
				'label' =>  $blnHide ? '' : $GLOBALS['TL_LANG']['MSC']['com_name'],
				'value' => FE_USER_LOGGED_IN ? ($this->User->firstname . ' ' . $this->User->lastname) : '',
				'inputType' => ($blnHide) ? 'hidden' : 'text',
				'eval' => array('mandatory'=> !$blnHide, 'maxlength'=>64)
			),
			'email' => array
			(
				'name' => 'email',
				'label' => ($blnHide) ? '' : $GLOBALS['TL_LANG']['MSC']['com_email'],
				'value' => $this->User->email,
				'inputType' => ($blnHide) ? 'hidden' : 'text',
				'eval' => array('rgxp'=>'email', 'mandatory'=>!$blnHide, 'maxlength'=>128)
			),
		);
		
		
		if (!$objArchive->disableWebsite)
		{
			$arrFields['website'] = array
				(
					'name' => 'website',
					'label' => $GLOBALS['TL_LANG']['MSC']['com_website'],
					'inputType' => 'text',
					'eval' => array('rgxp'=>'url', 'maxlength'=>128, 'decodeEntities'=>true)
				);
		}

		// Captcha
		if (!$objArchive->disableCaptcha)
		{
			$arrFields['captcha'] = array
			(
				'name' => 'captcha',
				'inputType' => 'captcha',
				'eval' => array('mandatory'=>true)
			);
		}

		// Comment field
		$arrFields['comment'] = array
		(
			'name' => 'comment',
			'inputType' => 'textarea',
			'eval' => array('rows'=>4, 'cols'=>40, 'allowHtml'=>true)
		);

		$doNotSubmit = false;
		$arrWidgets = array();

		// Initialize widgets
		foreach ($arrFields as $arrField)
		{
			$strClass = $GLOBALS['TL_FFL'][$arrField['inputType']];

			// Continue if the class is not defined
			if (!$this->classFileExists($strClass))
			{
				continue;
			}

			$arrField['eval']['required'] = $arrField['eval']['mandatory'];
			$objWidget = new $strClass($this->prepareForWidget($arrField, $arrField['name'], $arrField['value']));

			// Validate widget
			if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_comment')
			{
				$objWidget->validate();

				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}
			}

			$arrWidgets[] = $objWidget;
		}

		$this->Template->fields = $arrWidgets;
		$this->Template->submit = $GLOBALS['TL_LANG']['MSC']['com_submit'];
		$this->Template->action = ampersand($this->Environment->request);

		// Add comment
		if ($this->Input->post('FORM_SUBMIT') == 'tl_catalog_comment' && !$doNotSubmit)
		{
			$this->addComment($objCatalog, $objArchive);
			$this->reload();
		}
	}


	/**
	 * Replace bbcode and add the comment to the database
	 * 
	 * Supports the following tags:
	 * 
	 * - [b][/b] bold
	 * - [i][/i] italic
	 * - [u][/u] underline
	 * - [img][/img]
	 * - [code][/code]
	 * - [color=#ff0000][/color]
	 * - [quote][/quote]
	 * - [quote=tim][/quote]
	 * - [url][/url]
	 * - [url=http://][/url]
	 * - [email][/email]
	 * - [email=name@domain.com][/email]
	 * @param object
	 * @param object
	 */
	private function addComment(Database_Result $objCatalog, Database_Result $objArchive)
	{
		$strWebsite = $this->Input->post('website');

		// Add http:// to website
		if (strlen($strWebsite) && !preg_match('@^https?://|ftp://|mailto:@i', $strWebsite))
		{
			$strWebsite = 'http://' . $strWebsite;
		}

		$strComment = trim($this->Input->post('comment', DECODE_ENTITIES));

		// Replace bbcode
		if ($objArchive->bbcode)
		{
			$arrSearch = array
			(
				'[b]', '[/b]',
				'[i]', '[/i]',
				'[u]', '[/u]',
				'[code]', '[/code]',
				'[/color]',
				'[quote]', '[/quote]'
			);

			$arrReplace = array
			(
				'<strong>', '</strong>',
				'<em>', '</em>',
				'<span style="text-decoration:underline;">', '</span>',
				'<div class="code"><p>' . $GLOBALS['TL_LANG']['MSC']['com_code'] . '</p><pre>', '</pre></div>',
				'</span>',
				'<div class="quote">', '</div>'
			);

			$strComment = str_replace($arrSearch, $arrReplace, $strComment);

			$strComment = preg_replace('/\[color=([^\]]+)\]/i', '<span style="color:$1;">', $strComment);
			$strComment = preg_replace('/\[quote=([^\]]+)\]/i', '<div class="quote"><p>' . sprintf($GLOBALS['TL_LANG']['MSC']['com_quote'], '$1') . '</p>', $strComment);
			$strComment = preg_replace('/\[img\]([^\[]+)\[\/img\]/i', '<img src="$1" alt="" />', $strComment);

			$strComment = preg_replace('/\[url\]([^\[]+)\[\/url\]/i', '<a href="$1">$1</a>', $strComment);
			$strComment = preg_replace('/\[url=([^\]]+)\]([^\[]+)\[\/url\]/i', '<a href="$1">$2</a>', $strComment);

			$strComment = preg_replace('/\[email\]([^\[]+)\[\/email\]/i', '<a href="mailto:$1">$1</a>', $strComment);
			$strComment = preg_replace('/\[email=([^\]]+)\]([^\[]+)\[\/email\]/i', '<a href="mailto:$1">$2</a>', $strComment);

			$strComment = preg_replace(array('@</div>(\n)*@', '@\r@'), array("</div>\n", ''), $strComment);
		}

		// Encode e-mail addresses
		if (strpos($strComment, 'mailto:') !== false)
		{
			$this->import('String');
			$strComment = $this->String->encodeEmail($strComment);
		}

		$time = time();

		// Prevent cross-site request forgeries
		$strComment = preg_replace('/(href|src|on[a-z]+)="[^"]*(typolight\/main\.php|javascript|vbscri?pt|script|alert|document|cookie|window)[^"]*"+/i', '$1="#"', $strComment);

		// Prepare record
		$arrSet = array
		(
			'pid' => $objCatalog->id,
			'catid' => $objCatalog->pid,
			'tstamp' => $time,
			'name' => $this->Input->post('name'),
			'email' => $this->Input->post('email', true),
			'website' => $strWebsite,
			'comment' => nl2br_pre($strComment),
			'ip' => $this->Environment->ip,
			'date' => $time,
			'published' => 1
		);

		// Moderate
		if ($objArchive->moderate)
		{
			$arrSet['published'] = '';
		}

		$insert = $this->Database->prepare("INSERT INTO tl_catalog_comments %s")->set($arrSet)->execute();

		// Inform admin
		$objEmail = new Email();

		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['com_catalog_subject'], $objArchive->name, $this->Environment->host);

		// Add First Catalog Title Field
		$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=? AND titleField=? AND titleField=? ORDER BY sorting")
								  ->execute($this->Input->get('id'), 1, 'text');

		$titleField = strlen($objArchive->titleField) ? $objArchive->titleField : 
				($objFields->numRows ? $objFields->colName : 'id');

		// Add comment details
		$objEmail->text = sprintf($GLOBALS['TL_LANG']['MSC']['com_catalog_message'],
									$objArchive->name, 
									$objCatalog->$titleField,
								  $arrSet['name'] . ' (' . $arrSet['email'] . ')',
								  strip_tags($arrSet['comment']),
								  $this->Environment->base . $this->Environment->request,
								  $this->Environment->base . 'typolight/main.php?do=catalog&key=comments&act=edit&id=' . $insert->insertId);

		$objEmail->sendTo($GLOBALS['TL_ADMIN_EMAIL']);

	}


}

?>