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


/**
 * Catalog Filter/List
 */

$GLOBALS['TL_LANG']['tl_module']['catalog'] = array('Catalog', 'Please select the catalog.');

$GLOBALS['TL_LANG']['tl_module']['catalog_filtertemplate'] = array('Filter layout', 'Please choose a filter layout. You can add custom filter layouts to folder <em>templates</em>. Filter template files start with <em>filter_</em> and require file extension <em>.tpl</em>.');

$GLOBALS['TL_LANG']['tl_module']['catalog_template'] = array('Catalog layout', 'Please choose a catalog layout. You can add custom catalog layouts to folder <em>templates</em>. Catalog template files start with <em>catalog_</em> and require file extension <em>.tpl</em>.');
$GLOBALS['TL_LANG']['tl_module']['catalog_layout'] = array('Catalog template', 'Please choose template for the module. You can add custom templates to folder <em>templates</em>. Catalog module template files start with <em>mod_catalog</em> and require file extension <em>.tpl</em>.');
/**
 * Catalog Filter
 */

$GLOBALS['TL_LANG']['tl_module']['catalog_jumpTo'] = array('Jump to page', 'Please select the page to which visitors will be redirected when clicking on a filter selection.');
$GLOBALS['TL_LANG']['tl_module']['catalog_filter_enable'] = array('Enables Filter By section ', 'Create filters by selecting the fields to be used as filters.');
$GLOBALS['TL_LANG']['tl_module']['catalog_filter_headline'] = array('Filter Headline', 'Enter the heading level you would like to appear above the filter menu.');
$GLOBALS['TL_LANG']['tl_module']['catalog_filters'] = array('Filter Fields', 'Please select type of field for each filter fields. Tree View checkbox creates a filter tree, where each child filter is dependant on the value of its parent.');
$GLOBALS['TL_LANG']['tl_module']['catalog_tags_multi'] = array('Tags multiple select allowed', 'Provides tags with multiple selection for its options, e.g. checkbox or multiple select dropdown.');
$GLOBALS['TL_LANG']['tl_module']['catalog_filter_hide'] = array('Reveal tree view sequentially', 'Reveal the next item in the tree view (as ordered in filter), only when the previous item has a selected value.');

$GLOBALS['TL_LANG']['tl_module']['catalog_range_enable'] = array('Enables Range section ', 'Select which fields are filtered with a mininum and maximum range box.');
$GLOBALS['TL_LANG']['tl_module']['catalog_range_headline'] = array('Range Headline', 'Enter the heading level you would like to appear above the range menu.');
$GLOBALS['TL_LANG']['tl_module']['catalog_range'] = array('Range Fields', 'Please select the ranged filter fields for this view.');


$GLOBALS['TL_LANG']['tl_module']['catalog_date_enable'] = array('Enables Date section ', 'Select which fields are used to create groups of date ranges.');
$GLOBALS['TL_LANG']['tl_module']['catalog_date_headline'] = array('Dates Headline', 'Enter the heading level you would like to appear above the date menu.');
$GLOBALS['TL_LANG']['tl_module']['catalog_dates'] = array('Date Fields', 'Please select the date filter fields for this view.');
$GLOBALS['TL_LANG']['tl_module']['catalog_date_ranges'] = array('Date Ranges', 'Please select the ranges to appear in the date selection.');


$GLOBALS['TL_LANG']['tl_module']['catalog_sort_enable'] = array('Enables Sort section ', 'Select which fields to sort by.');
$GLOBALS['TL_LANG']['tl_module']['catalog_sort_headline'] = array('Sort Headline', 'Enter the heading level you would like to appear above the sort menu.');
$GLOBALS['TL_LANG']['tl_module']['catalog_sort'] = array('Sort Fields', 'Please select the fields to display for sorting.');
$GLOBALS['TL_LANG']['tl_module']['catalog_sort_type'] = array('Sort form control type', 'Specify the type fo control to be used for the sort.');


$GLOBALS['TL_LANG']['tl_module']['catalog_search_enable'] = array('Enables Search Box ', 'Select which fields are searchable when a user enters text in the search box. Note only text, longtext field types are supported. ');
$GLOBALS['TL_LANG']['tl_module']['catalog_search_headline'] = array('Search Headline', 'Enter the heading level you would like to appear above the search box.');


/**
 * Catalog List
 */

$GLOBALS['TL_LANG']['tl_module']['catalog_visible'] = array('Visible Fields', 'Please select the catalog fields that should be visible. You can also reorder items using the up/down arrow buttons.');

$GLOBALS['TL_LANG']['tl_module']['catalog_link_override'] = array('Override default link', 'Select to remove the default link and specify individual linked fields.');
$GLOBALS['TL_LANG']['tl_module']['catalog_islink'] = array('Linked Fields', 'Please select the catalog fields that should be wrapped automatically with a hyperlink to the catalog reader page.');


$GLOBALS['TL_LANG']['tl_module']['catalog_query_mode'] = array('Query Mode', 'Select the query mode to combine filters, e.g. <em>ALL</em>   matches for all filters (a AND b), while <em>ANY</em> matches for any filter (a OR b).');
$GLOBALS['TL_LANG']['tl_module']['catalog_tags_mode'] = array('Tags Mode', 'Select the tags mode to combine tag options, e.g. <em>ALL</em>   matches for all tag options (a AND b), while <em>ANY</em> matches for any tag option (a OR b).');
$GLOBALS['TL_LANG']['tl_module']['catalog_search'] = array('Search Fields', 'Please select the  fields to use for searches.');

$GLOBALS['TL_LANG']['tl_module']['catalog_condition_enable'] = array('Enable Conditional List', 'Enable the conditional list mode, to prevent listing until the conditions below are first met.');
$GLOBALS['TL_LANG']['tl_module']['catalog_condition'] = array('Condition List Fields', 'Select the fields that must first appear in the filter before any items are displayed, e.g. Area, City - this will display an empty list until the user has selected both the Area AND City fields in the filter.');


$GLOBALS['TL_LANG']['tl_module']['catalog_thumbnails_override'] = array('Override Image sizes', 'Enables you to override the default catalog image sizes with new image size and fullscreen options.');

$GLOBALS['TL_LANG']['tl_module']['catalog_imagemain_field'] = array('Image field (single)', 'Select the main catalog image field to override using the image size and fullscreen options below.');
$GLOBALS['TL_LANG']['tl_module']['catalog_imagemain_size'] = array('Image width and height (single)', 'Please enter either the image width, the image height or both measures to resize the image. If you leave both fields blank, the original image size will be displayed.');
$GLOBALS['TL_LANG']['tl_module']['catalog_imagemain_fullsize'] = array('Fullsize view (single)', 'If you choose this option, the image can be viewed fullsize by clicking it.');

$GLOBALS['TL_LANG']['tl_module']['catalog_imagegallery_field'] = array('Image field (multiple)', 'Select the secondary (multiple) catalog image field to override using the image size and fullscreen options below.');
$GLOBALS['TL_LANG']['tl_module']['catalog_imagegallery_size'] = array('Image width and height (multiple)', 'Please enter either the image width, the image height or both measures to resize the image. If you leave both fields blank, the original image size will be displayed.');
$GLOBALS['TL_LANG']['tl_module']['catalog_imagegallery_fullsize'] = array('Fullsize view (multiple)', 'If you choose this option, the image can be viewed fullsize by clicking it.');

$GLOBALS['TL_LANG']['tl_module']['catalog_where']       = array('Condition', 'If you want to exclude or include certain records, you can enter a condition here (e.g. <em>published=1</em> or <em>type!=\'admin\'</em>).');
$GLOBALS['TL_LANG']['tl_module']['catalog_order']        = array('Order by', 'Please enter a comma seperated list of fields that will be used to order the results by default. Add <em>DESC</em> after the fieldname to sort descending (e.g. <em>name, date DESC</em>).');

$GLOBALS['TL_LANG']['tl_module']['catalog_edit_enable'] = array('Enable Editing', 'Enable editing of the item by providing a jump to a page with the Catalog Edit Module.');

/**
 * Catalog Featured
 */

$GLOBALS['TL_LANG']['tl_module']['catalog_random_disable'] = array('Custom ordering', 'This option disables the default random ordering, so you can provide your own order by clause.');

$GLOBALS['TL_LANG']['tl_module']['catalog_limit']    = array('Number of items', 'Please enter the maximum number of items. Enter 0 to show all items.');



/**
 * Catalog Related
 */

$GLOBALS['TL_LANG']['tl_module']['catalog_related'] = array('Related fields to match', 'Select the related fields that must match with the currently viewed item with catalog reader.');
$GLOBALS['TL_LANG']['tl_module']['catalog_related_tagcount'] = array('Amount of tags that must be in common', 'Please specify how many tags must be shared between any entry and this one. This is the minimum count of tags.');

 

/**
 * Catalog Reference
 */

$GLOBALS['TL_LANG']['tl_module']['catalog_match'] = array('Select the match field', 'Select the catalog field to match the value of the reference field in the reference catalog (below).');

$GLOBALS['TL_LANG']['tl_module']['catalog_selected'] = array('Selected reference catalog', 'Select the current catalog being viewed in the Catalog Reader module.');
$GLOBALS['TL_LANG']['tl_module']['catalog_reference'] = array('Select the reference field', 'Select the reference field to equal the match catalog field (above).');


/**
 * Catalog Navigation
 */

$GLOBALS['TL_LANG']['tl_module']['catalog_navigation'] = array('Navigation field', 'Select the select type field to be used for navigation tree.');
$GLOBALS['TL_LANG']['tl_module']['catalog_show_items'] = array('Show selected catalog items', 'Add the catalog items to the catalog navigation menu when selected.');
$GLOBALS['TL_LANG']['tl_module']['catalog_show_field'] = array('Select text field to show', 'Select the text field to show in the navigation menu (e.g. title, name).');



/**
 * Catalog Edit
 */

$GLOBALS['TL_LANG']['tl_module']['catalog_edit'] = array('Editable Fields', 'Please select the catalog fields that should be editable. You can also reorder items using the up/down arrow buttons.');
$GLOBALS['TL_LANG']['tl_module']['catalog_editJumpTo'] = array('Jump to page', 'Please select the page to which visitors will be redirected when clicking on the add or edit link.');
$GLOBALS['TL_LANG']['tl_module']['catalog_edit_use_default'] = array('Enable restriction to defaults', 'By checking this, you enable the possibility to restrict values to defaults, no matter if the fields defined in here are mentioned to be editable above, they will not be able to be saved with any other value as defined here (within this edit module).');
$GLOBALS['TL_LANG']['tl_module']['catalog_edit_default'] = array('Restricted fields', 'Select all the fields that shall be restricted (make sure to provide the proper default value below).');
$GLOBALS['TL_LANG']['tl_module']['catalog_edit_default_value'] = array('Restricted fields default values', 'These are the default values for the above defined fields.');

/**
 * Catalog Notify
 */

$GLOBALS['TL_LANG']['tl_module']['catalog_notify_fields'] = array('Form Fields', 'Create a quick list of form fields using short 1-line text fields for the user to complete.');
$GLOBALS['TL_LANG']['tl_module']['catalog_recipients'] = array('Recipients List', 'Create a list of recipients who will receive the catalog notification e-mail.');
$GLOBALS['TL_LANG']['tl_module']['catalog_subject'] = array('Subject', 'Enter the subject for the notification e-mail. Example: <em>Catalog Notification: {{catalog::title}} - Price: {{catalog::price}}</em>');
$GLOBALS['TL_LANG']['tl_module']['catalog_notify'] = array('Body text', 'Enter the body text for the notification e-mail. You can also include specific fields from the catalog item being viewed - <em>{{catalog::title}} {{catalog::description}}</em>');

$GLOBALS['TL_LANG']['tl_module']['catalogNotifyText'] = array('Catalog Notification: {{catalog::title}}', "A user has sent a notification for a catalog: ##catalog##.\n\nPlease click ##link## to view the catalog item.\n");


/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_module']['catalog_thumb_legend'] 	= 'Catalog Thumbnails';
$GLOBALS['TL_LANG']['tl_module']['catalog_edit_legend'] 	= 'Catalog Edit';
$GLOBALS['TL_LANG']['tl_module']['catalog_filter_legend']	= 'Catalog Filtering';
$GLOBALS['TL_LANG']['tl_module']['restrict_to_defaults_legend']	= 'Restrict to default values';


/**
 * Reference
 */
$GLOBALS['TL_LANG']['tl_module']['AND'] = 'ALL matches (AND)';
$GLOBALS['TL_LANG']['tl_module']['OR'] 	= 'ANY match (OR)';

$GLOBALS['TL_LANG']['tl_module']['filter']['tree']		= 'Tree View';

$GLOBALS['TL_LANG']['tl_module']['filter']['none']		= 'None';
$GLOBALS['TL_LANG']['tl_module']['filter']['list']		= 'Links List';
$GLOBALS['TL_LANG']['tl_module']['filter']['radio']		= 'Radio button';
$GLOBALS['TL_LANG']['tl_module']['filter']['select']	= 'Select drop-down';

$GLOBALS['TL_LANG']['tl_module']['daterange']['y'] = 'Last year';
$GLOBALS['TL_LANG']['tl_module']['daterange']['h'] = 'Last 6 months';
$GLOBALS['TL_LANG']['tl_module']['daterange']['m'] = 'Last month';
$GLOBALS['TL_LANG']['tl_module']['daterange']['w'] = 'Last week';
$GLOBALS['TL_LANG']['tl_module']['daterange']['d'] = 'Yesterday';
$GLOBALS['TL_LANG']['tl_module']['daterange']['t'] = 'Today';
$GLOBALS['TL_LANG']['tl_module']['daterange']['df'] = 'Tomorrow';
$GLOBALS['TL_LANG']['tl_module']['daterange']['wf'] = 'Next week';
$GLOBALS['TL_LANG']['tl_module']['daterange']['mf'] = 'Next month';
$GLOBALS['TL_LANG']['tl_module']['daterange']['hf'] = 'Next 6 months';
$GLOBALS['TL_LANG']['tl_module']['daterange']['yf'] = 'Next year';

$GLOBALS['TL_LANG']['tl_module']['catalog_filter_cond_from_lister'] = array('Use filter condition from lister on same page.', 'If there is at least one catalog lister module on the same page, the filter condition from those cataloglisters is applied to this filter aswell.');

?>