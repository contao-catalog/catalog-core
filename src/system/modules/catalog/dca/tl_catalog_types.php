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
 * Table tl_catalog_types 
 */
			
$GLOBALS['TL_DCA']['tl_catalog_types'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_catalog_fields'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'onload_callback'             => array
			(
				array('tl_catalog_types', 'checkUpgrade'),
				array('tl_catalog_types', 'checkPermission'),
				array('tl_catalog_types', 'checkRemoveTable'),
				array('tl_catalog_types', 'generateFeed'),
			)
		),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('name'),
			'flag'                    => 1,
			'panelLayout'             => 'filter;search,limit'
		),

		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s',
			'label_callback'					=> array('tl_catalog_types','getRowLabel')
		),

		'global_operations' => array
		(            
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
		),

		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_types']['edit'],
				'href'                => 'table=tl_catalog_items',
				'icon'                => 'edit.gif',
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_types']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
				'button_callback'			=> array('tl_catalog_types', 'copyBtn')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_types']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'button_callback'			=> array('tl_catalog_types', 'deleteBtn'),
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'

			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_types']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			),
      'fields' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_types']['fields'],
				'href'                => 'table=tl_catalog_fields',
				'icon'                => 'tablewizard.gif',
        'button_callback'     => array('tl_catalog_types', 'fieldsButton')
			),
			'comments' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_types']['comments'],
				'href'                => 'key=comments',
				'icon'                => 'system/modules/catalog/html/comments.gif',
				'button_callback'     => array('tl_catalog_types', 'showComments')
			),
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'		=> array('addImage', 'import', 'searchable', 'allowComments', 'makeFeed'),
		'default'					=> '{title_legend},name,tableName,aliasField,jumpTo;{display_legend:hide},addImage,format;{comments_legend:hide},allowComments;{search_legend:hide},searchable;{import_legend:hide},import;{feed_legend:hide},makeFeed',
	),

	// Subpalettes
	'subpalettes' => array
	(
		'addImage'				=> 'singleSRC,size',
		'allowComments'		=> 'template,sortOrder,perPage,moderate,bbcode,requireLogin,disableCaptcha',
		'import'					=> 'importAdmin,importDelete',
		'searchable'			=> 'searchCondition,titleField',
		'makeFeed'				=> 'feedFormat,language,source,datesource,maxItems,feedBase,alias,description,feedTitle',
	),

	// Fields
	'fields' => array
	(
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['name'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50')
		),
        
		'tableName' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['tableName'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64, 'doNotCopy'=>true),
			'save_callback'           => array
			(
				array('Catalog', 'renameTable')
			)
		),
        
		'addImage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['addImage'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true)
		),
		'singleSRC' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['singleSRC'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'files'=>true, 'filesOnly'=>true, 'mandatory'=>true, 'extensions' => 'jpg,jpeg,gif,png,tif,tiff')
		),
		
		'size' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['size'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'digit', 'nospace'=>true)
		),

		'format' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['format'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('allowHtml'=>true)
		),

		'jumpTo' =>	array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['jumpTo'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'eval'                    => array('fieldType'=>'radio', 'helpwizard'=>true),
			'explanation'             => 'jumpTo',
			'doNotCopy'               => true,
		),
		'aliasField' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['aliasField'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_types', 'getAliasFields'),
			'eval'                    => array('mandatory' => false, 'includeBlankOption' => true),
			'doNotCopy'               => true,
		),

		'allowComments' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['allowComments'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true)
		),
		'template' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['template'],
			'default'                 => 'com_default',
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => $this->getTemplateGroup('com_'),
			'eval'                    => array('tl_class'=>'w50')
		),
		'sortOrder' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['sortOrder'],
			'default'                 => 'ascending',
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('ascending', 'descending'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_catalog_types'],
			'eval'                    => array('tl_class'=>'w50')
		),
		'perPage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['perPage'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'digit', 'tl_class'=>'w50')
		),
		'moderate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['moderate'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),
		'bbcode' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['bbcode'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),
		'requireLogin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['requireLogin'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),
		'disableCaptcha' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['disableCaptcha'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50')
		),

		'import' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['import'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'										=> array('submitOnChange'=>true),
			'doNotCopy'               => true,
		),
		
		'importAdmin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['importAdmin'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'doNotCopy'               => true,
			'eval'                    => array('tl_class'=>'w50')
		),
		
		'importDelete' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['importDelete'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'doNotCopy'               => true,
			'eval'                    => array('tl_class'=>'w50')
		),

		'searchable' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['searchable'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'										=> array('submitOnChange'=>true),
			'doNotCopy'               => true,
		),
		
		'searchCondition' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['searchCondition'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true),
			'doNotCopy'               => true,
			'eval'                    => array('tl_class'=>'w50')
		),
		
		'titleField' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['titleField'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_types', 'getTitleFields'),
			'eval'                    => array('mandatory' => false, 'includeBlankOption' => true),
			'doNotCopy'               => true,
		),

		
		'makeFeed' => array
		(
			'label'										=> &$GLOBALS['TL_LANG']['tl_catalog_types']['makeFeed'],
			'exclude'									=> true,
			'inputType'								=> 'checkbox',
			'eval'										=> array('submitOnChange'=>true),
			'doNotCopy'								=> true,
		),
		
		'feedFormat' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['feedFormat'],
			'default'                 => 'rss',
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'options'                 => array('rss'=>'RSS 2.0', 'atom'=>'Atom'),
			'eval'                    => array('tl_class'=>'w50')
		),
		
		'language' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['language'],
			'exclude'                 => true,
			'search'                  => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>32, 'tl_class'=>'w50')
		),
		
		'source' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['source'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_types', 'getRSSFields'),
			'eval'                    => array('mandatory' => false, 'includeBlankOption' => true),
			'doNotCopy'               => true,
		),
		
		
		'datesource' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['datesource'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_catalog_types', 'getDateFields'),
			'eval'                    => array('mandatory' => false, 'includeBlankOption' => true),
			'doNotCopy'               => true,
		),
		
		
		'maxItems' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['maxItems'],
			'default'                 => 25,
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'digit', 'tl_class'=>'w50')
		),
		
		'feedBase' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['feedBase'],
			'default'                 => $this->Environment->base,
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('trailingSlash'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50')
		),
		
		'alias' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['alias'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'alnum', 'unique'=>true, 'spaceToUnderscore'=>true, 'maxlength'=>128, 'tl_class'=>'w50')
		),
		'description' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['description'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px;', 'tl_class'=>'clr')
		),
		'feedTitle' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_types']['feedTitle'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px;', 'tl_class'=>'clr')
		),
	)
);

class tl_catalog_types extends Backend
{


	/**
	 * Check if old split Catalog, then call auto-upgrade
	 */
	public function checkUpgrade()
	{
		$checkFolder = TL_ROOT . '/system/modules/catalog_ex';

		if (file_exists($checkFolder) && is_dir($checkFolder))
		{
			$this->Import('CatalogUpgrade');
			$this->CatalogUpgrade->checkUpgrade();
		}
	}


	/**
	 * Check permissions to edit table tl_catalog_types
	 */
	public function checkPermission()
	{
		$this->import('BackendUser', 'User');


		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (!is_array($this->User->catalogs) || count($this->User->catalogs) < 1)
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->catalogs;
		}

		$GLOBALS['TL_DCA']['tl_catalog_types']['config']['closed'] = true;
		$GLOBALS['TL_DCA']['tl_catalog_types']['list']['sorting']['root'] = $root;

		// Check current action
		switch ($this->Input->get('act'))
		{
			case 'select':
				// Allow
				break;

			case 'edit':
			case 'show':
				if (!in_array($this->Input->get('id'), $root))
				{
					$this->log('Not enough permissions to '.$this->Input->get('act').' catalog type ID "'.$this->Input->get('id').'"', 'tl_catalog_types checkPermission', 5);
					$this->redirect('typolight/main.php?act=error');
				}
				break;

			case 'editAll':
				$session = $this->Session->getData();
				$session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $root);
				$this->Session->setData($session);
				break;

			default:
				if (strlen($this->Input->get('act')))
				{
					$this->log('Not enough permissions to '.$this->Input->get('act').' catalog types', 'tl_catalog_types checkPermission', 5);
					$this->redirect('typolight/main.php?act=error');
				}
				break;
		}
	}



  public function getRowLabel($row, $label, $dc)
  {
		// add image
		$image = '';
		if ($row['addImage'])
		{
			$size = deserialize($row['size']);
			$image = '<div class="image" style="padding-top:3px"><img src="'.$this->getImage($row['singleSRC'], $size[0], $size[1]).'" alt="'.htmlspecialchars($label).'" /></div> ';
 		}
  
		// count items
		$objCount = $this->Database->prepare("SELECT count(*) AS itemCount FROM ".$row['tableName'])
					->execute();
		$itemCount =  sprintf($GLOBALS['TL_LANG']['tl_catalog_types']['itemFormat'], $objCount->itemCount, ($objCount->itemCount == 1) ? sprintf($GLOBALS['TL_LANG']['tl_catalog_types']['itemSingle']) : sprintf($GLOBALS['TL_LANG']['tl_catalog_types']['itemPlural']));
		  
  	return '<span class="name">'.$label. $itemCount . '</span>'.$image;
  }

	/**
	 * Return the copy archive button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function copyBtn($row, $href, $label, $title, $icon, $attributes)
	{
		if (!$this->User->isAdmin)
		{
			return '';
		}

		return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}


	/**
	 * Return the delete archive button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function deleteBtn($row, $href, $label, $title, $icon, $attributes)
	{
		if (!$this->User->isAdmin)
		{
			return '';
		}

		return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}
	
	public function editItem($row, $href, $label, $title, $icon, $attributes)
	{
		return '<a href="'.$this->addToUrl('table=tl_catalog_items&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a>  ';
	}

	public function fieldsButton($row, $href, $label, $title, $icon, $attributes)
	{
		$this->import('BackendUser', 'User');
	
		if (!$this->User->isAdmin)
		{
			return '';
		}
	
		return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	} 


	public function checkRemoveTable(DataContainer $dc)
	{
		$act = $this->Input->get('act');
		if ($act == 'deleteAll' || $act == 'delete')
		{
			if ($act == 'delete')
			{
				$ids = array($dc->id);
			} 
			else
			{
				$session = $this->Session->getData();
				$ids = $session['CURRENT']['IDS']; 
			}
			
			$objType = $this->Database->execute(
					sprintf("SELECT tableName FROM tl_catalog_types WHERE id IN (%s)",
							implode(',', $ids)));
					
			while ($objType->next())
			{
				$tableName = $objType->tableName;
				
				if ($this->Database->tableExists($tableName))
				{
					$this->import('Catalog');
					$this->Catalog->dropTable($tableName);
				}
			}
		}
	}
   

	public function getAliasFields(DataContainer $dc)
	{

		$objFields = $this->Database->prepare("SELECT name, colName FROM tl_catalog_fields WHERE pid=? AND (type=? OR uniqueItem=?)")
				->execute($dc->id, 'alias', 1);

		 
		$result = array();
		while ($objFields->next())
		{
			$result[$objFields->colName] = $objFields->name;
		}
		
		return $result;
	}

	public function getTitleFields(DataContainer $dc)
	{

		$objFields = $this->Database->prepare("SELECT name, colName FROM tl_catalog_fields WHERE pid=? AND type=? AND titleField=?")
				->execute($dc->id, 'text', 1);
		 
		$result = array();
		while ($objFields->next())
		{
			$result[$objFields->colName] = $objFields->name;
		}
		
		return $result;
	}

	public function getRSSFields(DataContainer $dc)
	{
		$rssfieldtypes = join(',', $GLOBALS['BE_MOD']['content']['catalog']['typesRSSFields']);
		$objFields = $this->Database->prepare('SELECT name, colName FROM tl_catalog_fields WHERE pid=? AND (FIND_IN_SET(type,"' . $rssfieldtypes . '")>0)')
				->execute($dc->id);
		$result = array();
		while ($objFields->next())
		{
			$result[$objFields->colName] = $objFields->name;
		}
		return $result;
	}

	public function getDateFields(DataContainer $dc)
	{
		$objFields = $this->Database->prepare('SELECT name, colName FROM tl_catalog_fields WHERE pid=? AND type="date"')
				->execute($dc->id);
		$result = array();
		while ($objFields->next())
		{
			$result[$objFields->colName] = $objFields->name;
		}
		return $result;
	}

	/**
	 * Return the show comments button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function showComments($row, $href, $label, $title, $icon, $attributes)
	{
		if ($row['allowComments'])
		{

			$objArchive = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=?")
									  ->execute($row['id']);
			
			if ($objArchive->numRows && strlen($objArchive->tableName))
			{


				$objComments = $this->Database->prepare("SELECT id FROM tl_catalog_comments WHERE catid=?")
											  ->execute($row['id']);
	
				if ($objComments->numRows)
				{
					return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
				}
			}
		}

		return $this->generateImage(str_replace('.gif', '_.gif', $icon), $label);
	}

	/**
	 * Update the RSS-feed
	 */
	public function generateFeed()
	{
		$this->import('CatalogExt');
		$this->CatalogExt->generateFeed(CURRENT_ID);
	}
}

?>