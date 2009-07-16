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
 * Table tl_catalog_comments
 */
$GLOBALS['TL_DCA']['tl_catalog_comments'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => '',
		'doNotCopyRecords'            => true,
		'enableVersioning'            => true,
		'closed'                      => true,
		'onload_callback' => array
		(
			array('tl_catalog_comments', 'checkPermission')
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 2,
			'fields'                  => array('date DESC'),
			'flag'                    => 8,
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s',
			'label_callback'          => array('tl_catalog_comments', 'listComments')
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
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_comments']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_comments']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_catalog_comments']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => 'name,email,website;comment;published'
	),

	// Fields
	'fields' => array
	(
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_comments']['name'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>64)
		),
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_comments']['email'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>128, 'rgxp'=>'email')
		),
		'website' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_comments']['website'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>128, 'rgxp'=>'url', 'decodeEntities'=>true)
		),
		'comment' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_comments']['comment'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE')
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_comments']['published'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true)
		),
		'date' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_catalog_comments']['date'],
			'sorting'                 => true,
			'filter'                  => true,
			'flag'                    => 8
		),
		'pid' => array
		(
			'label'                   => array('PID', 'Parent ID'),
			'filter'                  => true,
			'sorting'                 => true
		)
	)
);


/**
 * Class tl_catalog_comments
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Controller
 */
class tl_catalog_comments extends Backend
{

	/**
	 * Database result
	 * @var array
	 */
	protected $arrData = null;


	/**
	 * Check permissions to edit table tl_catalog_comments
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

		// Check current action
		switch ($this->Input->get('act'))
		{
			case 'select':
				// Allow
				break;

			case 'edit':
			case 'show':
			case 'delete':
				if (!in_array($this->Input->get('id'), $root))
				{
					$this->log('Not enough permissions to '.$this->Input->get('act').' catalog comment ID "'.$this->Input->get('id').'"', 'tl_catalog_comments checkPermission', 5);
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
					$this->log('Not enough permissions to '.$this->Input->get('act').' catalog comments', 'tl_catalog_comments checkPermission', 5);
					$this->redirect('typolight/main.php?act=error');
				}
				elseif (!in_array($this->Input->get('id'), $root))
				{
					$this->log('Not enough permissions to access catalog ID "'.$this->Input->get('id').'"', 'tl_catalog_comments checkPermission', 5);
					$this->redirect('typolight/main.php?act=error');
				}
				break;
		}
	}


	/**
	 * List a particular record
	 * @param array
	 * @return string
	 */
	public function listComments($arrRow)
	{

		if (is_null($this->arrData))
		{
			$this->import('String');

			$objArchive = $this->Database->prepare("SELECT * FROM tl_catalog_types WHERE id=?")
									  ->execute($this->Input->get('id'));

			if ($objArchive->numRows && strlen($objArchive->tableName))
			{

				$objFields = $this->Database->prepare("SELECT * FROM tl_catalog_fields WHERE pid=? AND titleField=? AND type=? ORDER BY sorting")
										  ->execute($this->Input->get('id'), 1, 'text');

				$titleField = strlen($objArchive->titleField) ? $objArchive->titleField : 
						($objFields->numRows ? $objFields->name : 'id');

				$objData = $this->Database->prepare("SELECT c.id". ($titleField != 'id' ? ", c.".$titleField : '') ." FROM ". $objArchive->tableName ." c, tl_catalog_types a WHERE a.id=c.pid AND a.id=?")
										  ->execute($this->Input->get('id'));
	
				while ($objData->next())
				{
					$this->arrData[$objData->id] = $objData->$titleField;
				}
			}
		}

		$key = $arrRow['published'] ? 'published' : 'unpublished';
		return '
<div class="comment_wrap">
<div class="cte_type ' . $key . '"><strong><a href="mailto:' . $arrRow['email'] . '" title="' . specialchars($arrRow['email']) . '">' . $arrRow['name'] . '</a></strong>' . (strlen($arrRow['website']) ? ' (<a href="' . $arrRow['website'] . '" title="' . specialchars($arrRow['website']) . '" onclick="window.open(this.href); return false;">' . $GLOBALS['TL_LANG']['MSC']['com_website'] . '</a>)' : '') . ' - ' . date($GLOBALS['TL_CONFIG']['datimFormat'], $arrRow['date']) . ' - IP ' . $arrRow['ip'] . '<br />' . $this->arrData[$arrRow['pid']] . ' (PID: ' . $arrRow['pid'] . ')</div>
<div class="limit_height mark_links' . (!$GLOBALS['TL_CONFIG']['doNotCollapse'] ? ' h52' : '') . ' block">
' . $arrRow['comment'] . '
</div>
</div>' . "\n    ";
	}
}

?>