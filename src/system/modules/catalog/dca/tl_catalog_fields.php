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
 * This is the data container array for table tl_catalog_fields.
 *
 * PHP version 5
 * @copyright  Martin Komara, Thyon Design, CyberSpectrum 2008, 2009
 * @author     Martin Komara, John Brand <john.brand@thyon.com>
 *             Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    Catalog 
 * @license    GPL 
 * @filesource
 */


/**
 * Table tl_catalog_fields 
 */
$GLOBALS['TL_DCA']['tl_catalog_fields'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_catalog_types',
		'enableVersioning'            => true,
		'onload_callback'             => array
		(
			array('tl_catalog_fields', 'loadCatalogFields')
		),
	),
	
	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'filter,limit', 
			'headerFields'            => array('name', 'tableName', 'tstamp', 'makeFeed'), 
			'flag'                    => 1,
			'child_record_callback'   => array('tl_catalog_fields', 'renderField') 
		),
		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			),

		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__' => array('type', 'insertBreak', 'sortingField', 'showImage', 'format', 'limitItems', 'customFiletree'),
		'default' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,uniqueItem;{format_legend:hide},format',
		'text' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,uniqueItem;{format_legend:hide},format',
		'alias' => '{title_legend},name,description,colName,type,aliasTitle;{display_legend},insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField',
		'longtext' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak;{filter_legend:hide},searchableField;{advanced_legend:hide},mandatory,rte',
		'number' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,minValue,maxValue;{format_legend:hide},format',
		'decimal' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,minValue,maxValue;{format_legend:hide},format',
		'date' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,includeTime;{format_legend:hide},format',
		'select' => 'name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50;{filter_legend:hide},sortingField,filteredField;{advanced_legend:hide},mandatory,includeBlankOption;{options_legend},itemTable,itemTableValueCol,itemSortCol,itemFilter,limitItems',
		'tags' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50;{filter_legend:hide},searchableField;{advanced_legend:hide},mandatory;{options_legend},itemTable,itemTableValueCol,itemSortCol,itemFilter,limitItems',
		'checkbox' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField',
		'url' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory',
		'file' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,multiple,customFiletree;{format_legend},showImage,showLink',
		
	),

	// Subpalettes
	'subpalettes' => array
	(
		'insertBreak'			=> 'legendTitle,legendHide',
		'sortingField'		=> 'groupingMode',
		'showImage'				=> 'imageSize',
		'format'					=> 'formatFunction,formatStr',
		'limitItems'			=> 'items,childrenSelMode',
		'customFiletree'	=> 'uploadFolder,validFileTypes,filesOnly',
	),

	// Fields
	'fields' => array
	(
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['name'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50')
		),
		
		'description' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['description'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50')
		),
		
		// AVOID: doNotCopy => true, as child records won't be copied when copy catalog
		'colName' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['colName'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50'),
			'save_callback'           => array
			(
				array('Catalog', 'renameColumn')
			)
		),
		
		'type' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['type'],
			'default'                 => 'text', 
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('text', 'alias', 'longtext', 'number', 'decimal', 'date', 'checkbox', 'select', 'tags', 'url', 'file'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions'],
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50'),
			'save_callback'           => array
			(
				//added by thyon
				array('tl_catalog_fields', 'checkAliasDuplicate'),
				array('Catalog', 'changeColumn')
			)
		),
		
		'aliasTitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['aliasTitle'],
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_fields', 'getTitleFields'),
			'eval'                    => array('mandatory'=> true),
		),

		'insertBreak' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['insertBreak'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true)
		),
		
		'legendTitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['legendTitle'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255)
		),

		'legendHide' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['legendHide'],
			'inputType'               => 'checkbox',
		),		

		'width50' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['width50'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
		),		

		'titleField' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['titleField'],
			'inputType'               => 'checkbox',
		),		

		'filteredField' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['filteredField'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
		),
		
		'searchableField' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['searchableField'],
			'inputType'               => 'checkbox',
		),
		
		'sortingField' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['sortingField'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true)
		),
		
		'groupingMode' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['groupingMode'],
			'inputType'               => 'select',
			'options'                 => range(0, 12),
			'reference'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['groupingModeOptions'],
			'eval'      							=> array('mandatory' => true, 'includeBlankOption' => true),
		),
		
		'parentCheckbox' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['parentCheckbox'],
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_fields', 'getSelectors'),
			'eval'                    => array('includeBlankOption' => true),
		),
		
		'mandatory' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['mandatory'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
		),

		'includeBlankOption' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['includeBlankOption'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
		),
		
		'defValue' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['defValue'],
			'inputType'               => 'text',
			'eval'                    => array('tl_class'=>'w50'),
		),
		
		'uniqueItem' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['uniqueItem'],
			'inputType'               => 'checkbox'
		),
		
		'minValue' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['minValue'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'rgxp' => 'digit', 'tl_class'=>'w50')
		),
		
		'maxValue' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['maxValue'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'rgxp' => 'digit', 'tl_class'=>'w50')
		),
		
		'format' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['format'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true)
		),
		
		'formatFunction' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['formatFunction'],
			'inputType'               => 'select',
			'options'                 => array('string', 'number', 'date'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['formatFunctionOptions'],
			'eval'                    => array('tl_class'=>'w50'),
		),
		
		'formatStr' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['formatStr'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255)
		),
		
		'rte' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['rte'],
			'inputType'               => 'checkbox'
		),
		
		'itemTable' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['itemTable'],
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_fields', 'getTables'),
			'eval'                    => array('submitOnChange'=>true)
		),
		
/*
		'itemTableIdCol' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['itemTableIdCol'],
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_fields', 'getTableKeys'),
			'eval'                    => array('submitOnChange'=>true)
		),
*/
		
		'itemTableValueCol' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['itemTableValueCol'],
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_fields', 'getTableFields'),
			'eval'                    => array('tl_class'=>'w50', 'submitOnChange'=>true)
		),

		'itemSortCol' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['itemSortCol'],
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_fields', 'getTableFields'),
			'eval'                    => array('includeBlankOption'=>true)
		),

		
		'limitItems' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['limitItems'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
		),
		
		'items' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['items'],
			'inputType'               => 'tableTree',
			'eval'                    => array('fieldType'=>'checkbox', 'children'=>true),
			'load_callback'           => array(
					array('tl_catalog_fields', 'onLoadItems')
			),
		),
		
		'childrenSelMode' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['childrenSelMode'],
			'inputType'               => 'select',
			'options'                 => array('items', 'children', 'treeAll', 'treeChildrenOnly'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['childOptions'],
			'default'               	=> 'treeAll',
		),

		'itemFilter' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['itemFilter'],
			'inputType'               => 'textarea',
			'eval'                    => array('decodeEntities'=>true, 'style'=>'height:80px;')
		),

		
		'includeTime' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['includeTime'],
			'inputType'               => 'checkbox'
		),
		
		'linkToDetails' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['linkToDetails'],
			'inputType'               => 'checkbox'
		),
		
		'multiple' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['multiple'],
			'inputType'               => 'checkbox',
		),
		
		'showLink' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['showLink'],
			'inputType'               => 'checkbox',
		),
		
		'showImage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['showImage'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true) 
		),
						
		'imageSize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['imageSize'],
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'digit', 'nospace'=>true),
		),
		
		'customFiletree' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['customFiletree'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true)
		),
		'uploadFolder' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['uploadFolder'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio')
		),
		'validFileTypes' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['validFileTypes'],
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50')
		),
		'filesOnly' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['filesOnly'],
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),

		
	)
);

class tl_catalog_fields extends Backend
{


	public function loadCatalogFields(DataContainer $dc)
	{
		$this->import('Catalog');

		$act = $this->Input->get('act');
		switch ($act)
		{
			case 'delete':
				$this->Catalog->deleteColumn(array($dc->id));
				break;
				
			case "deleteAll":
				$session = $this->Session->getData();
				$this->Catalog->deleteColumn($session['CURRENT']['IDS']);
				break;

			default:;
		}

	}
	


	/**
	 * Add the type of input field
	 * @param array
	 * @return string
	 */
	public function renderField($arrRow)
	{
		$titleField = $arrRow['titleField'] ? ' published' : '';

		$images = array
			(
				'filteredField'		=> 'system/modules/development/html/extension.gif',
				'searchableField'	=> 'system/modules/development/html/labels.gif',
				'sortingField'		=> 'tablewizard.gif',
				'groupingMode'		=> 'modPlus.gif',
				'mandatory'				=> 'protect.gif',
				'showImage'				=> 'iconJPG.gif',
				'uniqueItem'			=> 'page.gif',
				'parentCheckbox'	=> 'ok.gif',
				'width50'					=> 'wrap.gif',
			);

		$type=$GLOBALS['BE_MOD']['content']['catalog']['fieldTypes'][$arrRow['type']];
		$strType = $this->generateImage($type['typeimage'], $GLOBALS['TL_LANG']['tl_catalog_fields']['type'][0], 'title="'.$GLOBALS['TL_LANG']['tl_catalog_fields']['type'][0].': '.$arrRow['type'].'"') . ' ';

		$strImages = '';
		foreach($images as $field=>$image)
		{
			if ($arrRow[$field])
			{
				if ($field == 'groupingMode' && !$arrRow['sortingField'])
					continue;

				$strImages .= ' '. $this->generateImage($image, $GLOBALS['TL_LANG']['tl_catalog_fields'][$field][0], 'title="'.$GLOBALS['TL_LANG']['tl_catalog_fields'][$field][0].'"');
			
			}
		}



		$legendImage = $this->generateImage(($arrRow['legendHide'] ? 'palCollapsed.gif' : 'palOpen.gif'), $GLOBALS['TL_LANG']['tl_catalog_fields']['legendTitle'][0], 'title="'.$GLOBALS['TL_LANG']['tl_catalog_fields']['legendTitle'][0].'"');
		
		return 		
'<div class="field_heading cte_type'.$titleField.'"><strong>' . $arrRow['colName'] . '</strong> <em>['.$arrRow['type'].']</em></div>
<div class="field_type block">
	<div style="padding-top:3px; float:right;">'. $strImages.'</div>
	'.$strType.'<strong>' . $arrRow['name'] . '</strong> - '.$arrRow['description'].'<br />
	'.($arrRow['insertBreak'] ? '<span style="padding-left:20px;" class="legend" title="'.$GLOBALS['TL_LANG']['tl_catalog_fields']['legendTitle'][0].'">'.$legendImage.' '.$arrRow['legendTitle'] .'</span>' : '').'
</div>';

	}



	public function getTables()
	{
		return $this->Database->listTables();
	}
	
	public function getTableKeys(DataContainer $dc)
	{
		$objTable = $this->Database->prepare("SELECT itemTable FROM tl_catalog_fields WHERE id=?")
				->limit(1)
				->execute($dc->id);
		 
		if ($objTable->numRows > 0 && $this->Database->tableExists($objTable->itemTable))
		{
			$fields = $this->Database->listFields($objTable->itemTable);
			return array_map(create_function('$x', 'return $x["name"];'), 
					array_filter($fields, create_function('$x', 'return array_key_exists("index", $x) && $x["type"] == "int";')));
		}
	}
	
 
	public function getTableFields(DataContainer $dc)
	{
		$objTable = $this->Database->prepare("SELECT itemTable FROM tl_catalog_fields WHERE id=?")
				->limit(1)
				->execute($dc->id);
		 
		if ($objTable->numRows > 0 && $this->Database->tableExists($objTable->itemTable))
		{
			$fields = $this->Database->listFields($objTable->itemTable);
			return array_map(create_function('$x', 'return $x["name"];'), $fields);
		}
	}


	public function getItems(DataContainer $dc)
	{
		$objField = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE id=?")
				->limit(1)
				->execute($dc->id);
				
		if ($objField->numRows > 0)
		{
			$idCol = 'id';//$objField->itemTableIdCol;
			$valueCol = $objField->itemTableValueCol;
			$itemTable = $objField->itemTable;
			
			try
			{
				$objItems = $this->Database->execute("SELECT $idCol, $valueCol FROM $itemTable");
			}
			catch (Exception $e)
			{
				// return empty array - no items yet
				return array();
			}
			
			$result = array();
			while($objItems->next())
			{
				$result[$objItems->$idCol] = $objItems->$valueCol;
			}
			
			return $result;
		}
	}
	
	public function getSelectors(DataContainer $dc)
	{
		$objField = $this->Database->prepare("SELECT pid FROM tl_catalog_fields WHERE id=?")
				->limit(1)
				->execute($dc->id);
				
		if (!$objField->numRows)
		{
			return array();
		}
		
		$pid = $objField->pid;
		
		$objFields = $this->Database->prepare("SELECT name, colName FROM tl_catalog_fields WHERE pid=? AND id != ? AND type=?")
				->execute($pid, $dc->id, 'checkbox');
		 
		$result = array();
		while ($objFields->next())
		{
			$result[$objFields->colName] = $objFields->name;
		}
		
		return $result;
	}


	public function checkAliasDuplicate($varValue, DataContainer $dc)
	{
		$arrAlias = $this->getAliasFields($dc);
		if ($varValue == 'alias' && count($arrAlias))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasDuplicate'],join(', ', array_keys($arrAlias))));
		}
		return $varValue;
	}

	public function getAliasFields(DataContainer $dc)
	{
		$objField = $this->Database->prepare("SELECT pid FROM tl_catalog_fields WHERE id=?")
				->limit(1)
				->execute($dc->id);
			
		if (!$objField->numRows)
		{
			return array();
		}
		
		$pid = $objField->pid;

		$objFields = $this->Database->prepare("SELECT name, colName FROM tl_catalog_fields WHERE pid=? AND id!=? AND type=?")
				->execute($pid, $dc->id, 'alias');
		 
		$result = array();
		while ($objFields->next())
		{
			$result[$objFields->colName] = $objFields->name;
		}
		
		return $result;
	}

	public function getTitleFields(DataContainer $dc)
	{
		$objField = $this->Database->prepare("SELECT pid FROM tl_catalog_fields WHERE id=?")
				->limit(1)
				->execute($dc->id);
			
		if (!$objField->numRows)
		{
			return array();
		}
		
		$pid = $objField->pid;

		$objFields = $this->Database->prepare("SELECT name, colName FROM tl_catalog_fields WHERE pid=? AND id!=? AND type=? AND titleField=?")
				->execute($pid, $dc->id, 'text', 1);
		 
		$result = array();
		while ($objFields->next())
		{
			$result[$objFields->colName] = $objFields->name;
		}
		
		return $result;
	}
	
	public function onLoadItems($varValue, DataContainer $dc)
	{
		$objField = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE id=?")
				->limit(1)
				->execute($dc->id);
				
		if ($objField->numRows)
		{
			$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['tableColumn'] =
					$objField->itemTable.'.'.$objField->itemTableValueCol;
		}

		return $varValue;
	}
	
	
}


?>