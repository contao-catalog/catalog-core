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
 * Class CatalogUpgrade 
 *
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package		Controller
 */
class CatalogUpgrade extends Backend
{


	private $checkFolder = 'system/modules/catalog_ext';


/**
 * Check if upgrade from Catalog Extension (catalog_ext) 
 */

	public function checkUpgrade()
	{

		if ($this->Input->get('key') != 'upgrade' && file_exists(TL_ROOT . '/' . $this->checkFolder) && is_dir(TL_ROOT . '/' . $this->checkFolder))
		{
			$this->redirect($this->addToUrl("key=upgrade"));
		}

	}
	

/**
 * Display Upgrade messsage from Catalog Extension (catalog_ex) 
 */

	public function upgrade()
	{

		if ($this->Input->get('key') != 'upgrade')
		{
			exit;
		}

		if (file_exists(TL_ROOT . '/' . $checkFolder) && is_dir(TL_ROOT . '/' . $checkFolder))
		{

			return '
<div id="tl_buttons">
</div>

<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['tl_catalog_types']['upgrade'][0].'</h2>'.$this->getMessages().'

<form action="'.ampersand($this->Environment->script, ENCODE_AMPERSANDS).'" id="tl_csv_import" class="tl_form" method="get">
<div class="tl_formbody_edit">
<input type="hidden" name="do" value="' . $this->Input->get('do') . '" />
<input type="hidden" name="table" value="' . $this->Input->get('table') . '" />
<input type="hidden" name="key" value="' . $this->Input->get('key') . '" />
<input type="hidden" name="id" value="' . $this->Input->get('id') . '" />
<input type="hidden" name="token" value="' . $strToken . '" />

<div id="tl_messages">
  <h2>'.$GLOBALS['TL_LANG']['tl_catalog_types']['upgrade'][0].'</h2>
  '.$GLOBALS['TL_LANG']['tl_catalog_types']['upgrade'][1].'
</div>

<ul class="tl_listing"><li class="tl_folder" onmouseover="Theme.hoverDiv(this, 1);" onmouseout="Theme.hoverDiv(this, 0);"><div class="tl_left"><img src="system/themes/default/images/folderO.gif" width="18" height="18" alt="" /> <strong>'.$this->checkFolder.'</strong></div> <div class="tl_right"></div><div style="clear:both;"></div></li>
</ul>

<br />
';

			
		}

	}	



	private function scanRecursive($folder, $arrFiles=null)
	{
		if (is_dir(TL_ROOT . '/' . $checkFolder . '/' . $folder))
		{
			$arrSubFiles = scan(TL_ROOT . '/' . $folder);
			foreach ($arrSubFiles as $subfile)
			{
				// incomplete function
				$arrFiles =+ $this->scanRecursive($file, $arrFiles);
			}
		}
	}



/**
 * Delete a file, or a folder and its contents (recursive algorithm)
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.3
 * @link        http://aidanlister.com/repos/v/function.rmdirr.php
 * @param       string   $dirname    Directory to delete
 * @return      bool     Returns TRUE on success, FALSE on failure
 */

	private function rmdirr($dirname)
	{
		// Sanity check
		if (!file_exists($dirname)) 
		{
			return false;
		}
	 
		// Simple delete for a file
		if (is_file($dirname) || is_link($dirname)) 
		{
			try
			{
				return unlink($dirname);
			}
			catch (Exception $ee)
			{
				return true;
			}
		}
	 
		// Loop through the folder
		$dir = dir($dirname);
		while (false !== $entry = $dir->read()) 
		{
			// Skip pointers
			if ($entry == '.' || $entry == '..') 
			{
				continue;
			}
	 
			// Recurse
			$this->rmdirr($dirname . '/' . $entry);
		}
	 
		// Clean up
		$dir->close();
		try
		{
			return rmdir($dirname);
		}
		catch (Exception $ee)
		{
			return true;
		}

	}

	
}

?>