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
 * Fields
 */
$GLOBALS['TL_LANG']['tl_catalog_fields']['name'] = array('Label', 'Field label is used to describe the field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['description'] = array('Description', 'Field description. Shown under input field to provide help or description of the field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['colName'] = array('Column name', 'Name of the column in the database table.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['type'] = array('Type', 'Type of field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['titleField'] = array('Visible in back-end list view', 'This enables the field to be  displayed in the back-end list view (also required for custom display string).');
$GLOBALS['TL_LANG']['tl_catalog_fields']['aliasTitle'] = array('Alias title field', 'Please select the title field to be used for auto-generation of the alias.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['filteredField'] = array('Enable back-end filter', 'This adds a back-end filter drop-down in the header panel layout, with a list of all the field values.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['insertBreak'] = array('Start legend group', 'Starts a new legend group for better visual grouping.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['legendTitle'] = array('Legend title', 'Enter the legend title.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['legendHide'] = array('Hide legend by default', 'Select to hide this legend section by default.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['width50'] = array('Enable half width', 'Enables this as a half-width field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['searchableField'] = array('Enable back-end search', 'This adds the current field to the back-end search drop-down in the header panel layout.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['sortingField'] = array('Enable sort drop-down', 'This adds the current field to the the back-end sort drop-down in the header panel layout.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['groupingMode'] = array('Sorting mode', 'If you want to sort items in TL backend according to this field, select one of grouping modes.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['parentCheckbox'] = array('Controlling checkbox', 'Please select a checkbox. Current field is hidden in edit view until the selected checkbox is checked.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['mandatory'] = array('Mandatory', 'Whether the user is required to fill in the field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['includeBlankOption'] = array('Include blank option', 'Include a blank option in the drop-down list.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['parentFilter'] = array('Parent filter', 'Select the parent control that provides additional root(s) for options filtering.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['treeMinLevel'] = array('Start level', 'Enter a value greater than 0 to only allow seletion of items from a certain sublevel.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['treeMaxLevel'] = array('Stop level', 'Enter a value greater than 0 to limit the nesting level of the selectable tree.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['uniqueItem'] = array('Unique', 'Whether this field is unique within the table.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['defValue'] = array('Default value', 'Please enter the default value for the field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['calcValue'] = array('Calculation formula', 'Enter the SQL calculation for the field, e.g. (price*1.15)*qty.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['minValue'] = array('Minimum', 'Specifies the minimal value user can input.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['maxValue'] = array('Maximum', 'Specifies the maximal value user can input.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['rte'] = array('Rich text', 'If selected, rich text editor is displayed.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['rte_editor'] = array('tinyMCE editor Template', 'Please select the tinyMCE template to use for rich text editing.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['allowHtml'] = array('Allow html', 'If selected, html tags won\'t be stripped.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['textHeight'] = array('Textarea height', 'Specify the textarea height in pixels, 0 for default.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['itemTable'] = array('Options source table', 'Please select a table where options are stored.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['itemTableIdCol'] = array('Table id column', 'Please select an ID column of the table.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['itemTableValueCol'] = array('Option value column', 'Please select a column storing description of an option.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['itemSortCol'] = array('Option sort column', 'Please select a column to sort the options when displayed.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['limitItems'] = array('Customize the options selection', 'Allows you to set custom parameters for the selection of options for the field. By default, all items will be shown as collapsed.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['items'] = array('Select root items', 'Please select one or more root (parent) points for the field options (all items below are selectable by user).');
$GLOBALS['TL_LANG']['tl_catalog_fields']['childrenSelMode'] = array('Select children mode', 'Select how to display items in the back-end. Collapsed items will display as a select or checkbox field, otherwise as a table tree.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['itemFilter'] = array('Filter items', 'Exclude or include certain records here, e.g. published=1 or type!=\'admin\'.');

$GLOBALS['TL_LANG']['tl_catalog_fields']['includeTime'] = array('Include time', 'If selected, user is able to enter date and time.');

$GLOBALS['TL_LANG']['tl_catalog_fields']['formatPrePost'] = array('Prefix and suffix strings', 'Enter strings, e.g. currency, to be displayed before and after this field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['format'] = array('Enable additional format function', 'Enables the value to be formatted by one of the special functions: <em>String</em>, <em>Number</em> and <em>Date</em>.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['formatFunction'] = array('Format function', 'Select which formatting function will be used.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['formatStr'] = array('Format string or parameter', '<strong>Text</strong>:  [<a href="http://php.net/sprintf" onclick="window.open(this.href)">sprintf</a>] format, <strong>Date</strong>: [<a href="http://php.net/date" title="Click to open link" onclick="window.open(this.href)">date</a>] format, <strong>Number</strong>: decimals, as TL configured separators are used for number formats.');

$GLOBALS['TL_LANG']['tl_catalog_fields']['showLink'] = array('Create link as file download or image lightbox', 'Wraps the item in a link that will show the fullscreen image or download the file.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['showImage'] = array('Enable as image field with thumbnail', 'If selected, a thumbnail will be created for image files.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['imageSize'] = array('Image width and height', 'Please enter either the image width, the image height or both measures to resize the image. If you leave both fields blank, the original image size will be displayed.');

$GLOBALS['TL_LANG']['tl_catalog_fields']['multiple'] = array('Multiple selection', 'If selected, user will be able to select more than one item.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['sortBy'] = array('Order by', 'Please choose the sort order.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['customFiletree'] = array('Customize the file Ttree', 'Allows you to set custom options for the Filetree.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['uploadFolder'] = array('Set file root folder', 'Selects the root point from which the user will select this file field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['validFileTypes'] = array('Valid file types', 'Please enter a comma separated list of extensions of valid file types for this field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['filesOnly'] = array('Allow files only', 'Select this option to restrict the file browser to files only (folders not selectable).');
$GLOBALS['TL_LANG']['tl_catalog_fields']['editGroups'] = array('Frontend editing groups', 'If defined only selected groups are allowed to edit this field.');

/**
 * Reference
 */
$GLOBALS['TL_LANG']['tl_catalog_fields']['groupingModeOptions'] = array(
    'Sort in parent-child view',
    'Group by initial letter and sort ascending',
    'Group by initial letter and sort descending',
    'Group by initial two letters and sort ascending',
    'Group by initial two letters and sort descending',
    'Group by day and sort ascending',
    'Group by day and sort descending',
    'Group by month and sort ascending',
    'Group by month and sort descending',
    'Group by year and sort ascending',
    'Group by year and sort descending',
    'Group and sort ascending',
    'Group and sort descending',
);

$GLOBALS['TL_LANG']['tl_catalog_fields']['childOptions']['items'] = 'Show selected items collapsed';
$GLOBALS['TL_LANG']['tl_catalog_fields']['childOptions']['children'] = 'Show children of selected items collapsed';
$GLOBALS['TL_LANG']['tl_catalog_fields']['childOptions']['treeAll'] = 'Tree with all items selectable';
$GLOBALS['TL_LANG']['tl_catalog_fields']['childOptions']['treeChildrenOnly'] = 'Tree with only children selectable';


$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['text']			= 'Text';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['alias'] 		= 'Alias';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['longtext']	= 'Long text';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['number']		= 'Number';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['decimal']	= 'Decimal';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['date']			= 'Date';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['select']		= 'Select';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['tags']			= 'Tags';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['checkbox']	= 'Checkbox';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['url']			= 'Url';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['file']			= 'File';
$GLOBALS['TL_LANG']['tl_catalog_fields']['typeOptions']['calc']			= 'Calculate';


$GLOBALS['TL_LANG']['tl_catalog_fields']['formatFunctionOptions']['string']	= 'String';
$GLOBALS['TL_LANG']['tl_catalog_fields']['formatFunctionOptions']['number']	= 'Number';
$GLOBALS['TL_LANG']['tl_catalog_fields']['formatFunctionOptions']['date']		= 'Date';
//$GLOBALS['TL_LANG']['tl_catalog_fields']['formatFunctionOptions']['money']	= 'Money';

$GLOBALS['TL_LANG']['tl_catalog_fields']['name_asc']  = 'File name (ascending)';
$GLOBALS['TL_LANG']['tl_catalog_fields']['name_desc'] = 'File name (descending)';
$GLOBALS['TL_LANG']['tl_catalog_fields']['date_asc']  = 'Date (ascending)';
$GLOBALS['TL_LANG']['tl_catalog_fields']['date_desc'] = 'Date (descending)';
$GLOBALS['TL_LANG']['tl_catalog_fields']['meta']      = 'Meta file (meta.txt)';
$GLOBALS['TL_LANG']['tl_catalog_fields']['random']    = 'Random order';


/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_catalog_fields']['title_legend']		= 'Field configuration';
$GLOBALS['TL_LANG']['tl_catalog_fields']['display_legend']	= 'Display settings';
$GLOBALS['TL_LANG']['tl_catalog_fields']['filter_legend']		= 'Back-end filter settings';
$GLOBALS['TL_LANG']['tl_catalog_fields']['legend_legend']		= 'Legend settings';
$GLOBALS['TL_LANG']['tl_catalog_fields']['advanced_legend']	= 'Advanced settings';
$GLOBALS['TL_LANG']['tl_catalog_fields']['options_legend']	= 'Options settings';
$GLOBALS['TL_LANG']['tl_catalog_fields']['format_legend']		= 'Format settings';
$GLOBALS['TL_LANG']['tl_catalog_fields']['feedit_legend']		= 'Frontend editing';


/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_catalog_fields']['new']    = array('New field', 'Create new field.');
$GLOBALS['TL_LANG']['tl_catalog_fields']['edit']   = array('Edit field', 'Edit field ID %s');
$GLOBALS['TL_LANG']['tl_catalog_fields']['copy']   = array('Copy field', 'Copy field ID %s');
$GLOBALS['TL_LANG']['tl_catalog_fields']['cut']   = array('Move field', 'Move field ID %s');
$GLOBALS['TL_LANG']['tl_catalog_fields']['delete'] = array('Delete field', 'Delete field ID %s');
$GLOBALS['TL_LANG']['tl_catalog_fields']['show']   = array('Field details', 'Show details of field ID %s');
$GLOBALS['TL_LANG']['tl_catalog_fields']['editheader'] = array('Edit item type', 'Edit the item type');


?>