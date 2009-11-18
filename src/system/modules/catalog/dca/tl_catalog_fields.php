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
		'__selector__' => array('type', 'insertBreak', 'sortingField', 'showImage', 'format', 'limitItems', 'customFiletree', 'editGroups', 'rte'),
		'default' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,uniqueItem;{format_legend:hide},formatPrePost,format;{feedit_legend},editGroups',
		'text' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,uniqueItem;{format_legend:hide},formatPrePost,format;{feedit_legend},editGroups',
		'alias' => '{title_legend},name,description,colName,type,aliasTitle;{display_legend},insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{feedit_legend},editGroups',
		'longtext' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak;{filter_legend:hide},searchableField;{advanced_legend:hide},mandatory,allowHtml,rte;{feedit_legend},editGroups',
		'number' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,minValue,maxValue;{format_legend:hide},formatPrePost,format;{feedit_legend},editGroups',
		'decimal' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,minValue,maxValue;{format_legend:hide},formatPrePost,format;{feedit_legend},editGroups',
		'date' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,defValue,includeTime;{format_legend:hide},formatPrePost,format;{feedit_legend},editGroups',
		'select' => 'name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField;{advanced_legend:hide},mandatory,includeBlankOption;{options_legend},itemTable,itemTableValueCol,itemSortCol,itemFilter,limitItems;{feedit_legend},editGroups',
		'tags' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},searchableField;{advanced_legend:hide},mandatory;{options_legend},itemTable,itemTableValueCol,itemSortCol,itemFilter,limitItems;{feedit_legend},editGroups',
		'checkbox' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField;{feedit_legend},editGroups',
		'url' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory;{feedit_legend},editGroups',
		'file' => '{title_legend},name,description,colName,type;{display_legend},parentCheckbox,insertBreak,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{advanced_legend:hide},mandatory,multiple,customFiletree;{format_legend},showImage,showLink;{feedit_legend},editGroups',
		'calc' => '{title_legend},name,description,colName,type,calcValue;{display_legend},parentCheckbox,insertBreak,width50,titleField;{filter_legend:hide},sortingField,filteredField,searchableField;{format_legend:hide},formatPrePost,format;{feedit_legend},editGroups',
		
	),

	// Subpalettes
	'subpalettes' => array
	(
		'insertBreak'			=> 'legendTitle,legendHide',
		'sortingField'		=> 'groupingMode',
		'showImage'				=> 'imageSize',
		'format'					=> 'formatFunction,formatStr',
		'limitItems'			=> 'items,childrenSelMode,parentFilter',
		'customFiletree'	=> 'uploadFolder,validFileTypes,filesOnly',
		'editGroups'		=> 'editGroups',
		'rte'					=> 'rte_editor',
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
			'options'                 => array('text', 'alias', 'longtext', 'number', 'decimal', 'date', 'checkbox', 'select', 'tags', 'url', 'file', 'calc'),
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
			'options_callback'        => array('tl_catalog_fields', 'getCheckboxSelectors'),
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
		
		'calcValue' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['calcValue'],
			'inputType'               => 'textarea',
			'eval'                    => array('decodeEntities'=>true, 'style'=>'height:80px;', 'mandatory'=>true),
			'save_callback'           => array
			(
				array('tl_catalog_fields', 'checkCalc')
			),
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
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50')
		),
						
		'formatPrePost' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['formatPrePost'],
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'allowHtml'=>true),
		),

		
		'rte' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['rte'],
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true)
		),
		'rte_editor' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['rte_editor'],
			'inputType'               => 'select',
			'default'				  => 'tinyMCE',
			'options_callback'        => array('tl_catalog_fields', 'getRichTextEditors'),
		),
		
		'allowHtml' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['allowHtml'],
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
			'default'               	=> 'treeAll',
			'options'                 => array('items', 'children', 'treeAll', 'treeChildrenOnly'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_catalog_fields']['childOptions'],
			'eval'                    => array('tl_class'=>'w50'),
		),

		'parentFilter' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['parentFilter'],
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_fields', 'getOptionSelectors'),
			'eval'                    => array('includeBlankOption' => true, 'tl_class'=>'w50'),
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
		'editGroups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_fields']['editGroups'],
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('title' => &$GLOBALS['TL_LANG']['tl_catalog_fields']['useridfield'], 'multiple'=>true , 'tl_class'=>'w50')
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
				'filteredField'		=> 'system/modules/catalog/html/extension.gif',
				'searchableField'	=> 'system/modules/catalog/html/labels.gif',
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

	public function getRichTextEditors()
	{
		$configs=array();
		foreach(array_diff(scandir(sprintf('%s/system/config', TL_ROOT)), Array( ".", ".." )) as $name)
		{
			if((strpos($name, 'tiny')===0) && (substr($name, -4, 4)=='.php'))
				$configs[]=substr($name, 0, -4);
		}
		return $configs;
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

	public function getCheckboxSelectors(DataContainer $dc)
	{
		return $this->getSelectors($dc, $GLOBALS['BE_MOD']['content']['catalog']['typesCheckboxSelectors']);
	}

	public function getOptionSelectors(DataContainer $dc)
	{
		return $this->getSelectors($dc, $GLOBALS['BE_MOD']['content']['catalog']['typesOptionSelectors']);
	}
	
	public function getSelectors(DataContainer $dc, $type=NULL)
	{
		if (!$type)
		{
			return array();
		}
	
		$objField = $this->Database->prepare("SELECT pid FROM tl_catalog_fields WHERE id=?")
				->limit(1)
				->execute($dc->id);
				
		if (!$objField->numRows)
		{
			return array();
		}
		
		$pid = $objField->pid;
		
		$objFields = $this->Database->prepare("SELECT name, colName FROM tl_catalog_fields WHERE pid=? AND id != ? AND type IN ('".join("','", $type)."')")
				->execute($pid, $dc->id);
		 
		$result = array();
		while ($objFields->next())
		{
			$result[$objFields->colName] = $objFields->name;
		}
		
		return $result;
	}



	public function checkCalc($varValue, DataContainer $dc)
	{
		$objTable = $this->Database->prepare("SELECT tableName FROM tl_catalog_types t WHERE t.id=(SELECT f.pid FROM tl_catalog_fields f where f.id=?)")
				->limit(1)
				->execute($dc->id);
				
		if (!$objTable->numRows)
		{
			return $varValue;
		}

		try
		{
			$objValue = $this->Database->prepare("SELECT ".$varValue." as calcValue FROM ".$objTable->tableName)
								   ->limit(1)
								   ->execute();
	
			if ($objValue->numRows)
			{
				$value = $objValue->calcValue;
			}
		}

		catch (Exception $e)
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['calcInvalid'], $e->getMessage()));
		}

		return $varValue;
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