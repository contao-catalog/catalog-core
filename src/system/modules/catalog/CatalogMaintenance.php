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
 * Class CatalogMaintenance
 *
 * @copyright	CyberSpectrum and others, see CONTRIBUTORS
 * @author		Christian Schiffler <c.schiffler@cyberspectrum.de> and others, see CONTRIBUTORS
 * @package		Controller
 */
class CatalogMaintenance extends Backend
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'be_catalogmaintenance';

	public function checkUpgrade()
	{
		if ($this->Input->get('key') != 'upgrade' && file_exists(TL_ROOT . '/' . $this->checkFolder) && is_dir(TL_ROOT . '/' . $this->checkFolder))
		{
			$this->redirect($this->addToUrl("key=upgrade"));
		}
	}

	/**
	 * Generate module
	 */
	protected function compile()
	{
		$this->loadLanguageFile('tl_maintenance');
		$this->Template = new BackendTemplate($this->strTemplate);

		$this->Template->cacheMessage = '';
		$this->Template->updateMessage = '';

		$this->fixTags();

		$this->Template->href = $this->getReferer(true);
		$this->Template->title = specialchars($GLOBALS['TL_LANG']['MSC']['backBT']);
		$this->Template->action = ampersand($this->Environment->request);
		$this->Template->selectAll = $GLOBALS['TL_LANG']['MSC']['selectAll'];
		$this->Template->button = $GLOBALS['TL_LANG']['MSC']['backBT'];
		return $this->Template->parse();
	}

	protected static $tagsPerCall=200;

	// Rebuild tag contents
	protected function fixTags()
	{
		// get all tag fields and update them via ajax.
		$time = time();

		// Add error message
		if ($_SESSION['REBUILD_CATALOGTAGS_ERROR'] != '')
		{
			$this->Template->tagsMessage = $_SESSION['REBUILD_CATALOGTAGS_ERROR'];
			$_SESSION['REBUILD_CATALOGTAGS_ERROR'] = '';
		}

		// Rebuild tag contents
		if ($this->Input->get('act') == 'tags')
		{
			$intField = $this->Input->get('field');
			$intStart = intval($this->Input->get('start'));
			$objTagField = $this->Database->prepare('SELECT f.*, (SELECT tableName FROM tl_catalog_types WHERE id=f.pid) AS tableName FROM tl_catalog_fields AS f WHERE type="tags" AND id=?')->execute($intField);
			if($objTagField->numRows)
			{
				$objEntries = $this->Database->prepare('SELECT id,'.$objTagField->colName.' FROM '.$objTagField->tableName.' ORDER BY id ASC')->limit(self::$tagsPerCall, $intStart)->execute();
				while($objEntries->next())
				{
					Catalog::setTags($objTagField->pid, $intField, $objEntries->id, explode(',', $objEntries->{$objTagField->colName}));
				}
			}
			echo file_get_contents(TL_ROOT . '/system/themes/' . $this->getTheme() . '/images/ok.gif');
			exit;
		}

		if ($this->Input->post('act') == 'tags')
		{
			// determine all selected tag fields.
			$arrFields = array();
			foreach($this->Input->post('fields') as $field)
			{
				$arrFields[] = intval($field);
			}
			$objTagFields = $this->Database->execute('SELECT f.*, (SELECT tableName FROM tl_catalog_types WHERE id=f.pid) AS tableName FROM tl_catalog_fields AS f WHERE type="tags" AND id IN ('.implode(',', $arrFields).')');
			$rand = rand();
			$strBuffer = '';
			while($objTagFields->next())
			{
				$strBuffer .= '<div id="tl_catalogtagsmaintenance"><h3 class="sub_headline">'.sprintf('%s (%s.%s)', $objTagFields->name, $objTagFields->tableName, $objTagFields->colName).'</h3>';
				$objRows = $this->Database->execute('SELECT COUNT(id) AS count FROM '.$objTagFields->tableName);
				if($objRows->next() && $objRows->count)
				{
					// Display rows
					$pages=(ceil($objRows->count / self::$tagsPerCall));
					for ($i=0; $i<$pages; $i++)
					{
						$strBuffer .= '<img src="' . $this->addToUrl('act=tags&field='.$objTagFields->id.'&start='.($i*self::$tagsPerCall)) . '#' . $rand . $i . '" alt="" width="16" height="16" /> ' . sprintf('%s-%s', ($i*self::$tagsPerCall), ($i+1<$pages?($i+1)*self::$tagsPerCall:$objRows->count));
					}
				}
				$strBuffer .= '</div>';
			}
			$this->Template = new BackendTemplate('be_catalogtags');

			$this->Template->content = $strBuffer;
			$this->Template->note = $GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsNote'];
			$this->Template->loading = $GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsLoading'];
			$this->Template->complete = $GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsComplete'];
			$this->Template->theme = $this->getTheme();
		} else {
			$arrData = array(
				'label'     => &$GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsRebuild'],
				'exclude'   => true,
				'inputType' => 'checkbox',
				'tableless' => true,
				'options'   => array(),
				'eval'      => array('multiple'=>true)
			);
			// determine all tag fields.
			$objTagFields = $this->Database->execute('SELECT id,pid,name,colName,(SELECT tableName FROM tl_catalog_types WHERE id=f.pid) AS tableName FROM tl_catalog_fields AS f WHERE type="tags"');
			while($objTagFields->next())
			{
				$arrData['options'][$objTagFields->id] = sprintf('%s (%s.%s)', $objTagFields->name, $objTagFields->tableName, $objTagFields->colName);
			}
			$objWidget = new CheckBox($this->prepareForWidget($arrData, 'fields', NULL, 'fields', 'tl_catalog_maintenance'));
			$this->Template->tagsContent = '
<div' . ($arrData['eval']['tl_class'] ? ' class="' . $arrData['eval']['tl_class'] . '"' : '') . '>' . $objWidget->parse() . (($GLOBALS['TL_CONFIG']['oldBeTheme'] || !$objWidget->hasErrors()) ? ((!$GLOBALS['TL_CONFIG']['showHelp'] || !strlen($GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsRebuild'][1]))?'':'
  <p class="tl_help' . (!$GLOBALS['TL_CONFIG']['oldBeTheme'] ? ' tl_tip' : '') . '">'.$GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsRebuild'][1].'</p>') : '') . '
</div>';

		}
		// Default variables
		$this->Template->tagsContinue = $GLOBALS['TL_LANG']['MSC']['continue'];
		$this->Template->tagsHeadline = $GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsUpdate'];
		$this->Template->tagsSubmit = $GLOBALS['TL_LANG']['tl_maintenance']['catalogTagsSubmit'];
	}

	protected function purgeInvalidFields($tableName)
	{
		// only in backend!
		if(TL_MODE != 'BE')
			return;
		$columns = $this->Database->listFields($tableName, true);

		// skip the indexes
		foreach($columns as $key => $column)
		{
			if($column['type'] == 'index')
			{
				unset($columns[$key]);
			}
		}

		$invalid=array();
		$valid=array_merge(array_keys($GLOBALS['TL_DCA'][$tableName]['fields']), $this->systemColumns);
		foreach($columns as $col)
		{
			if(!in_array($col['name'], $valid))
				$invalid[] = $col['name'];
		}
		if(count($invalid)>0)
		{
			// TODO: loop over the invalid array and drop the columns or rather redirect to some maintenance screen? see issue #59
			throw new Exception('INVALID COLUMNS DETECTED: '.implode(', ', $invalid));
		}
	}
}

?>