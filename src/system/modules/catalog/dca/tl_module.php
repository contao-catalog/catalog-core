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
 * Add palettes to tl_module
 */


$GLOBALS['TL_DCA']['tl_module']['palettes']['catalogfilter']    = '{title_legend},name,headline,type;{config_legend},catalog,catalog_jumpTo,catalog_filter_cond_from_lister;{catalog_filter_legend},catalog_filter_enable;{catalog_range_legend:hide},catalog_range_enable;{catalog_date_legend:hide},catalog_date_enable;{catalog_search_legend:hide},catalog_search_enable;{catalog_sort_legend:hide},catalog_sort_enable;{template_legend:hide},catalog_filtertemplate,catalog_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['cataloglist']  = '{title_legend},name,headline,type;{config_legend},catalog,jumpTo,catalog_visible,catalog_link_override,catalog_link_window;{catalog_filter_legend:hide},catalog_condition_enable,catalog_search,catalog_query_mode,catalog_tags_mode,catalog_where,perPage,catalog_list_use_limit,catalog_order;{catalog_thumb_legend:hide},catalog_thumbnails_override;{catalog_edit_legend:hide},catalog_edit_enable;{template_legend:hide},catalog_template,catalog_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['catalogreader']  = '{title_legend},name,headline,type;{config_legend},catalog,catalog_visible,catalog_goback_disable,catalog_comments_disable;{template_legend:hide},catalog_template,catalog_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['catalogfeatured']  = '{title_legend},name,headline,type;{config_legend},catalog,jumpTo,catalog_visible,catalog_link_override,catalog_link_window;{catalog_filter_legend:hide},catalog_query_mode,catalog_tags_mode,catalog_where,catalog_list_use_limit,catalog_random_disable;{catalog_thumb_legend:hide},catalog_thumbnails_override;{template_legend:hide},catalog_template,catalog_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['catalogrelated']  = '{title_legend},name,headline,type;{config_legend},catalog,jumpTo,catalog_visible,catalog_link_override;{catalog_filter_legend:hide},catalog_related,catalog_related_tagcount,catalog_where,catalog_limit,catalog_random_disable;{catalog_thumb_legend:hide},catalog_thumbnails_override;{template_legend:hide},catalog_template,catalog_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['catalogreference']  = '{title_legend},name,headline,type;{config_legend},catalog,catalog_selected,catalog_match,catalog_reference,jumpTo,catalog_visible,catalog_link_override;{catalog_filter_legend:hide},catalog_where,catalog_limit,catalog_random_disable;{catalog_thumb_legend:hide},catalog_thumbnails_override;{template_legend:hide},catalog_template,catalog_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['catalognavigation'] = '{title_legend},name,headline,type;{config_legend},catalog,catalog_jumpTo,jumpTo,catalog_navigation,levelOffset,showLevel,hardLimit;catalog_show_items;{template_legend:hide},catalog_layout,navigationTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['catalognotify'] = '{title_legend},name,headline,type;{config_legend},catalog,catalog_notify_fields,disableCaptcha;{catalog_notify_legend:hide},catalog_subject,catalog_recipients,catalog_notify;{template_legend:hide},catalog_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

// catalog edit AND modify list above ^^
$GLOBALS['TL_DCA']['tl_module']['palettes']['catalogedit']  = '{title_legend},name,headline,type;{config_legend},catalog,catalog_edit,jumpTo,disableCaptcha;{template_legend:hide},catalog_template,catalog_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{restrict_to_defaults_legend:hide},catalog_edit_use_default';


$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_filter_enable';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_search_enable';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_range_enable';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_date_enable';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_sort_enable';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_link_override';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_condition_enable';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_random_disable';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_thumbnails_override';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_edit_enable';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_show_items';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_edit_use_default';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'catalog_list_use_limit';

// Insert new Subpalettes after position 1
array_insert($GLOBALS['TL_DCA']['tl_module']['subpalettes'], 1, array
	(
		'catalog_filter_enable' => 'catalog_filter_headline,catalog_filters,catalog_filter_hide,catalog_tags_multi',
		'catalog_range_enable' => 'catalog_range_headline,catalog_range',
		'catalog_search_enable' => 'catalog_search_headline,catalog_search',
		'catalog_date_enable' => 'catalog_date_headline,catalog_dates,catalog_date_ranges',
		'catalog_sort_enable' => 'catalog_sort_headline,catalog_sort,catalog_sort_type',
		'catalog_link_override' => 'catalog_islink',
		'catalog_condition_enable' => 'catalog_condition',
		'catalog_thumbnails_override'	=> 'catalog_imagemain_field,catalog_imagemain_size,catalog_imagemain_fullsize,catalog_imagegallery_field,catalog_imagegallery_size,catalog_imagegallery_fullsize,sortBy',
		'catalog_random_disable' => 'catalog_order',
		'catalog_edit_enable' => 'catalog_editJumpTo',
		'catalog_show_items' => 'catalog_show_field',
		'catalog_edit_use_default' => 'catalog_edit_default,catalog_edit_default_value',
		'catalog_list_use_limit' => 'catalog_list_offset,catalog_limit'
	)
);

array_insert($GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'], 1, 
	array(array('tl_module_catalog', 'onLoadCallback'))
);

/**
 * Add fields to tl_module
 */

array_insert($GLOBALS['TL_DCA']['tl_module']['fields'] , 1, array
(

	'catalog' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog'],
		'exclude'                 => true,
		'inputType'               => 'radio',
		'foreignKey'              => 'tl_catalog_types.name',
		'eval'                    => array('mandatory'=> true, 'submitOnChange'=> true)
	),

	'catalog_jumpTo' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_jumpTo'],
		'exclude'                 => true,
		'inputType'               => 'pageTree',
		'explanation'							=> &$GLOBALS['TL_LANG']['tl_module']['catalog_jumpTo'][1],
		'eval'                    => array('fieldType'=>'radio', 'helpwizard'=>true),
	),

	'catalog_template' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_template'],
		'default'                 => 'catalog_full',
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog','getCatalogTemplates'),
		'eval'                    => array('tl_class'=>'w50')
	),

	'catalog_layout' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_layout'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog', 'getModuleTemplates'),
		'eval'                    => array('tl_class'=>'w50')
	),

	'catalog_filtertemplate' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_filtertemplate'],
		'default'                 => 'filter_default',
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog','getFilterTemplates'),
		'eval'                    => array('tl_class'=>'w50')
	),

	'catalog_filter_enable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_filter_enable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),

	'catalog_filter_headline' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_filter_headline'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'inputUnit',
		'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
		'eval'                    => array('maxlength'=>255)
	),

	'catalog_filters' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_filters'],
		'exclude'                 => true,
		'inputType'               => 'filterWizard',
		'options_callback'        => array('tl_module_catalog', 'getFilterFields'),
		'eval'                    => array('multiple'=> true, 
				'radio' 		=> array('none','list','radio','select'), 
				'checkbox' 	=> array('tree'),
				'labels'	=> &$GLOBALS['TL_LANG']['tl_module']['filter']
				),

	),

	'catalog_tags_multi' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_tags_multi'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
	),


	'catalog_filter_hide' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_filter_hide'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array()
	),

	'catalog_range_enable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_range_enable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),

	'catalog_range_headline' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_range_headline'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'inputUnit',
		'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
		'eval'                    => array('maxlength'=>255)
	),
	
	'catalog_range' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_range'],
		'exclude'                 => true,
		'inputType'               => 'checkboxWizard',
		'options_callback'        => array('tl_module_catalog', 'getFilterFields'),
		'eval'                    => array('multiple'=> true, 'mandatory'=> true)
	),


	'catalog_date_enable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_date_enable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),

	'catalog_date_headline' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_date_headline'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'inputUnit',
		'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
		'eval'                    => array('maxlength'=>255)
	),

	'catalog_dates' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_dates'],
		'exclude'                 => true,
		'inputType'               => 'filterWizard',
		'options_callback'        => array('tl_module_catalog', 'getDateFields'),
		'eval'                    => array('multiple'=> true, 
				'radio' 		=> array('none','list','radio','select'), 
				'labels'	=> &$GLOBALS['TL_LANG']['tl_module']['filter']
				),
	),

	'catalog_date_ranges' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_date_ranges'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'checkboxWizard',
		'options'                 => array('y', 'h', 'm', 'w', 'd', 't', 'df', 'wf', 'mf', 'hf', 'yf'),
		'reference'               => &$GLOBALS['TL_LANG']['tl_module']['daterange'],
		'eval'                    => array('multiple'=>true)
	),


	'catalog_sort_enable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_sort_enable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),

	'catalog_sort_headline' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_sort_headline'],
		'exclude'                 => true,
		'search'                  => true,
		'inputType'               => 'inputUnit',
		'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
		'eval'                    => array('maxlength'=>255)
	),
	
	'catalog_sort' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_sort'],
		'exclude'                 => true,
		'inputType'               => 'checkboxWizard',
		'options_callback'        => array('tl_module_catalog', 'getCatalogFields'),
		'eval'                    => array('multiple'=> true, 'mandatory'=> true)
	),
	
	'catalog_sort_type' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_sort_type'],
		'default'               	=> 'select',
		'exclude'                 => true,
		'inputType'               => 'select',
		'options'               	=> array('list', 'radio','select'),
		'reference'               => &$GLOBALS['TL_LANG']['tl_module']['settings'],
	),
	
	'catalog_search_enable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_search_enable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),

	'catalog_search_headline' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_search_headline'],
		'exclude'                 => true,
		'inputType'               => 'inputUnit',
		'options'                 => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
		'eval'                    => array('maxlength'=>255)
	),
	'catalog_search' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_search'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'options_callback'        => array('tl_module_catalog', 'getCatalogFields'),
		'eval'                    => array('multiple'=> true, 'mandatory'=> true)
	),


	'catalog_visible' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_visible'],
		'exclude'                 => true,
		'inputType'               => 'checkboxWizard',
		'options_callback'        => array('tl_module_catalog', 'getCatalogFields'),
		'eval'                    => array('multiple'=> true, 'mandatory'=> true)
	),
	'catalog_link_override' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_link_override'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),
	'catalog_islink' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_islink'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'options_callback'        => array('tl_module_catalog', 'getCatalogLinkFields'),
		'eval'                    => array('multiple'=> true)
	),
	'catalog_link_window' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_link_window'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
	),
	'catalog_goback_disable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_goback_disable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
	),
	'catalog_query_mode' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_query_mode'],
		'default'               	=> 'AND',
		'exclude'                 => true,
		'inputType'               => 'select',
		'options'               	=> array('AND', 'OR'),
		'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
		'eval'                    => array('tl_class'=>'w50')
	),
	'catalog_tags_mode' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_tags_mode'],
		'default'               	=> 'AND',
		'exclude'                 => true,
		'inputType'               => 'select',
		'options'               	=> array('AND', 'OR'),
		'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
	),
	'catalog_condition_enable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_condition_enable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),
	'catalog_condition' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_condition'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'options_callback'        => array('tl_module_catalog', 'getCatalogFields'),
		'eval'                    => array('multiple'=> true, 'mandatory'=> true)
	),


	'catalog_random_disable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_random_disable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),

	'catalog_limit' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_limit'],
		'exclude'                 => true,
		'inputType'               => 'text',
		'default'               	=> '1',
		'eval'                    => array('rgxp'=>'digit')
	),


	'catalog_thumbnails_override' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_thumbnails_override'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),


	'catalog_imagemain_field' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_imagemain_field'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog', 'getImageFields'),
		'eval'                    => array('includeBlankOption' => true, 'tl_class'=>'w50')
	),

	'catalog_imagemain_size' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_imagemain_size'],
		'exclude'                 => true,
		'inputType'               => 'imageSize',
		'options'                 => array('crop', 'proportional', 'box'),
		'reference'               => &$GLOBALS['TL_LANG']['MSC'],
		'eval'                    => array('rgxp'=>'digit', 'nospace'=>true)
	),

	'catalog_imagemain_fullsize' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_imagemain_fullsize'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
	),

	'catalog_imagegallery_field' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_imagegallery_field'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog', 'getImageFields'),
		'eval'                    => array('includeBlankOption' => true, 'tl_class'=>'w50')
	),

	'catalog_imagegallery_size' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_imagegallery_size'],
		'exclude'                 => true,
		'inputType'               => 'imageSize',
		'options'                 => array('crop', 'proportional', 'box'),
		'reference'               => &$GLOBALS['TL_LANG']['MSC'],
		'eval'                    => array('rgxp'=>'digit', 'nospace'=>true)
	),

	'catalog_imagegallery_fullsize' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_imagegallery_fullsize'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
	),

	'catalog_edit' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_edit'],
		'exclude'                 => true,
		'inputType'               => 'checkboxWizard',
		'options_callback'        => array('tl_module_catalog', 'getCatalogEditFields'),
		'eval'                    => array('multiple'=> true, 'mandatory'=> true)
	),

	'catalog_edit_enable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_edit_enable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'										=> array('submitOnChange'=> true),
	),

	'catalog_editJumpTo' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_editJumpTo'],
		'exclude'                 => true,
		'inputType'               => 'pageTree',
		'eval'                    => array('fieldType'=>'radio', 'helpwizard'=>true),
		'explanation'             => 'jumpTo'
	),

	'catalog_edit_use_default' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_edit_default'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'                    => array('submitOnChange'=> true)
	),
	'catalog_edit_default' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_edit_default'],
		'exclude'                 => true,
		'inputType'               => 'checkboxWizard',
		'options_callback'        => array('tl_module_catalog', 'getCatalogEditFields'),
		'eval'                    => array('multiple'=> true, 'mandatory'=> true)
	),
	'catalog_edit_default_value' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_edit_default_value'],
		'exclude'                 => true,
		'inputType'               => 'CatalogMultiWidget',
		'save_callback' => array(array('tl_module_catalog', 'onSaveColumns_catalog_edit_default_value')),
		'load_callback' => array(array('tl_module_catalog', 'onLoadColumns_catalog_edit_default_value')),
		'eval'                    => array('getsubfields_callback' => array('tl_module_catalog', 'catalog_edit_default_value_subfields'), 'doNotSaveEmpty' => true, 'tl_class' => 'clr')
	),

	'catalog_where' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_where'],
		'exclude'                 => true,
		'inputType'               => 'textarea',
		'eval'                    => array('decodeEntities'=>true, 'style'=>'height:80px;')
	),	

	'catalog_order' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_order'],
		'exclude'                 => true,
		'inputType'               => 'textarea',
		'eval'                    => array('decodeEntities'=>true, 'style'=>'height:80px;')
	),	

	'catalog_related' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_related'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'options_callback'        => array('tl_module_catalog', 'getCatalogFields'),
		'eval'                    => array('multiple'=> true, 'mandatory'=> true)
	),

	'catalog_related_tagcount' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_related_tagcount'],
		'exclude'                 => true,
		'inputType'               => 'text',
	),

	'catalog_navigation' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_navigation'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog', 'getCatalogOptionSelectFields'),
		'eval'                    => array('mandatory'=>true, 'includeBlankOption'=>true)
	),

	'catalog_show_items' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_show_items'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'                    => array('submitOnChange'=> true)
	),

	'catalog_show_field' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_show_field'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog', 'getCatalogTextFields'),
		'eval'                    => array('mandatory'=>true, 'includeBlankOption' => true)
	),

	'catalog_selected' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_selected'],
		'exclude'                 => true,
		'inputType'               => 'radio',
		'options_callback'        => array('tl_module_catalog', 'getCatalogSelectList'),
		'eval'                    => array('mandatory'=> true, 'submitOnChange'=> true)
	),
	'catalog_reference' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_reference'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog', 'getCatalogReferenceFields'),
		'eval'                    => array('mandatory'=> true, 'includeBlankOption'=>true)
	),
	'catalog_match' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_match'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_catalog', 'getCatalogMatchFields'),
		'eval'                    => array('mandatory'=> true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),


	'catalog_notify_fields' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_notify_fields'],
		'exclude'                 => true,
		'default'									=> array('Name', 'E-mail', 'Phone'),
		'inputType'               => 'listWizard',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>64)
	),


	'catalog_recipients' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_recipients'],
		'exclude'                 => true,
		'default'									=> array($GLOBALS['TL_CONFIG']['adminEmail']),
		'inputType'               => 'listWizard',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>64)
	),

	'catalog_subject' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_subject'],
		'exclude'                 => true,
		'inputType'               => 'text',
		'default'               	=> (is_array($GLOBALS['TL_LANG']['tl_module']['catalogNotifyText']) ? $GLOBALS['TL_LANG']['tl_module']['catalogNotifyText'][0] : $GLOBALS['TL_LANG']['tl_module']['catalogNotifyText']),
		'eval'                    => array('mandatory'=>true, 'maxlength'=>255)
	),

	'catalog_notify' => array
	(
		'label'         => &$GLOBALS['TL_LANG']['tl_module']['catalog_notify'],
		'default'       => (is_array($GLOBALS['TL_LANG']['tl_module']['catalogNotifyText']) ? $GLOBALS['TL_LANG']['tl_module']['catalogNotifyText'][1] : $GLOBALS['TL_LANG']['tl_module']['catalogNotifyText']),
		'exclude'       => true,
		'inputType'     => 'textarea',
		'eval'          => array('mandatory'=>true, 'style'=>'height:120px;', 'decodeEntities'=>true),
		'save_callback' => array
		(
			array('tl_module_catalog', 'getDefaultValue')
		)
	),
	'catalog_comments_disable' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_comments_disable'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
	),
	'catalog_filter_cond_from_lister' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_filter_cond_from_lister'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
	),
	'catalog_list_use_limit' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_list_use_limit'],
		'exclude'                 => true,
		'inputType'               => 'checkbox',
		'eval'                    => array('submitOnChange'=> true, 'tl_class' => 'clr'),
	),
	'catalog_list_offset' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['catalog_list_offset'],
		'exclude'                 => true,
		'inputType'               => 'text',
		'default'                 => '10',
		'eval'                    => array('rgxp' => 'decimal', 'tl_class'=>'w50'),
	),

	'sortBy' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['sortBy'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options'                 => array('name_asc', 'name_desc', 'date_asc', 'date_desc', 'meta', 'random'),
		'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
		'eval'                    => array('tl_class'=>'w50')
	),
	


)); 

/**
 * Class tl_module_catalog
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright	Martin Komara, Thyon Design, CyberSpectrum 2007-2009
 * @author		Martin Komara, 
 * 				John Brand <john.brand@thyon.com>,
 * 				Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package    Controller
 */
class tl_module_catalog extends Backend
{
	/**
	 * Get all filter fields and return them as array
	 * @return array
	 */
	public function getFilterFields(DataContainer $dc)
	{
		return $this->getCatalogFields($dc, $GLOBALS['BE_MOD']['content']['catalog']['typesFilterFields']);
	}

	/**
	 * Get only Select fields and return them as array
	 * @return array
	 */
	public function getCatalogTextFields(DataContainer $dc)
	{
		return $this->getCatalogFields($dc, array('text'));
	}



	/**
	 * Get only Select fields and return them as array
	 * @return array
	 */
	public function getCatalogOptionSelectFields(DataContainer $dc)
	{
		return $this->getCatalogFields($dc, array('select'));
	}


	/**
	 * Get all date fields and return them as array
	 * @return array
	 */
	public function getDateFields(DataContainer $dc)
	{
		return $this->getCatalogFields($dc, array('date'));
	}


	/**
	 * Get all image fields and return them as array
	 * @return array
	 */
	public function getImageFields(DataContainer $dc)
	{
		return $this->getCatalogFields($dc, array('file'), true);
	}


	/**
	 * Get all reference fields and return them as array
	 * @return array
	 */

	public function getCatalogMatchFields(DataContainer $dc)
	{
		$return = $this->getCatalogFields($dc, $GLOBALS['BE_MOD']['content']['catalog']['typesMatchFields']);
		return array_merge(array('id'=>'ID [id:internal]'), $return);
	}


	/**
	 * Get all editable fields and return them as array
	 * @return array
	 */
	public function getCatalogEditFields(DataContainer $dc)
	{
		return $this->getCatalogFields($dc, $GLOBALS['BE_MOD']['content']['catalog']['typesEditFields']); // TODO: add file support later
	}

	/**
	 * Get all linkable fields and return them as array
	 * @return array
	 */
	public function getCatalogLinkFields(DataContainer $dc)
	{
		return $this->getCatalogFields($dc, $GLOBALS['BE_MOD']['content']['catalog']['typesLinkFields']);	
	}



	/**
	 * Get all catalog fields and return them as array
	 * @return array
	 */
	public function getCatalogFields(DataContainer $dc, $arrTypes=false, $blnImage=false)
	{
		if(!$arrTypes)
			$arrTypes=$GLOBALS['BE_MOD']['content']['catalog']['typesCatalogFields'];
		$fields = array();
		$chkImage = $blnImage ? " AND c.showImage=1" : "";
		
		$objFields = $this->Database->prepare("SELECT c.* FROM tl_catalog_fields c, tl_module m WHERE c.pid=m.catalog AND m.id=? AND c.type IN ('" . implode("','", $arrTypes) . "')".$chkImage." ORDER BY c.sorting ASC")
							->execute($this->Input->get('id'));

		while ($objFields->next())
		{
			$value = strlen($objFields->name) ? $objFields->name.' ' : '';
			$value .= '['.$objFields->colName.':'.$objFields->type.']';
			$fields[$objFields->colName] = $value;
		}

		return $fields;

	}


	/**
	 * Get all catalog, except the current one and return them as array
	 * @return array
	 */
	public function getCatalogSelectList(DataContainer $dc)
	{
		$catalogs = array();
		$objCatalog = $this->Database->prepare("SELECT c.* FROM tl_catalog_types c, tl_module m WHERE m.id=? AND c.id!=m.catalog AND c.id!=m.catalog ORDER BY name ASC")
							->execute($this->Input->get('id'));
		while ($objCatalog->next())
		{
			$catalogs[$objCatalog->id] = $objCatalog->name;
		}

		return $catalogs;
	}

	/**
	 * Get all reference fields and return them as array
	 * @return array
	 */
	public function getCatalogReferenceFields(DataContainer $dc, $arrTypes=false)
	{
		if(!$arrTypes)
			$arrTypes=$GLOBALS['BE_MOD']['content']['catalog']['typesReferenceFields'];
		$fields = array();
		$objFields = $this->Database->prepare("SELECT c.* FROM tl_catalog_fields c, tl_module m WHERE c.pid=m.catalog_selected AND m.id=? AND c.type IN ('" . implode("','", $arrTypes) . "') ORDER BY c.sorting ASC")
							->execute($this->Input->get('id'));
		while ($objFields->next())
		{
			$value = strlen($objFields->name) ? $objFields->name.' ' : '';
			$value .= '['.$objFields->colName.':'.$objFields->type.']';
			$fields[$objFields->colName] = $value;
		}
		return array_merge(array('id'=>'ID [id:internal]'), $fields);
	}

	/**
	 * Load the default value if the text is empty
	 * @param string
	 * @param object
	 * @return string
	 */
	public function getDefaultValue($varValue, DataContainer $dc)
	{
		if (!strlen(trim($varValue)))
		{
			$varValue = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['default'];
		}
		return $varValue;
	}

	/**
	 * Check if the Approval fields match in quantity
	 * @param string
	 * @param object
	 * @return string
	 */
	public function checkApprovalCounts($varValue, DataContainer $dc)
	{
		if (count(deserialize($varValue, true)) != count($this->Input->post('catalog_approval_names')))
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['approvalMismatch']);
		}
				
		return $varValue;
	}

	public function getModuleTemplates(DataContainer $dc)
	{
		// fix issue #70 - template selector shall only show relevant templates.
		if (version_compare(VERSION.'.'.BUILD, '2.9.0', '>='))
		{
			return $this->getTemplateGroup('mod_' . $dc->activeRecord->type, $dc->activeRecord->pid);
		}
		else
		{
			// backwards compatibility only
			if (version_compare(VERSION.'.'.BUILD, '2.8.0', '>='))
				$type = $dc->activeRecord->type;
			else
			{
				// 2.7 and below do not have activeRecord.
				$objModule=$this->Database->prepare("SELECT type FROM tl_module m WHERE m.id=?")
									->execute($dc->id);
				$type = $objModule->type;
			}
			return $this->getTemplateGroup('mod_' . $type);
		}
	}

	public function getCatalogTemplates(DataContainer $dc)
	{
		// fix issue #70 - template selector shall only show relevant templates.
		if (version_compare(VERSION.'.'.BUILD, '2.9.0', '>='))
		{
			return $this->getTemplateGroup('catalog_', $dc->activeRecord->pid);
		}
		else
		{
			return $this->getTemplateGroup('catalog_');
		}
	}

	public function getFilterTemplates(DataContainer $dc)
	{
		// fix issue #70 - template selector shall only show relevant templates.
		if (version_compare(VERSION.'.'.BUILD, '2.9.0', '>='))
		{
			return $this->getTemplateGroup('filter_', $dc->activeRecord->pid);
		}
		else
		{
			return $this->getTemplateGroup('filter_');
		}
	}

	public function onLoadCallback(DataContainer $dc)
	{
		$result = $this->Database->prepare('SELECT m.* FROM tl_module m WHERE m.id=? AND m.catalog_edit_use_default=1')
								->limit(1)
								->execute($dc->id);
		if($result->type != 'catalogedit')
		{
			return;
		}
		$this->import('Catalog');
		$dca=$this->Catalog->getCatalogDca($result->catalog);
		$fields=deserialize($result->catalog_edit_default);
		$subfields=array();
		if(is_array($fields))
		{
			foreach($fields as $field)
			{
				$subfields[$field]=$dca['fields'][$field];
			}
		}
		$GLOBALS['TL_DCA']['tl_module']['fields']['catalog_edit_default_value']['subfields']=$subfields;
	}
	
	public function catalog_edit_default_value_subfields($arrOptions)
	{
		return $GLOBALS['TL_DCA']['tl_module']['fields']['catalog_edit_default_value']['subfields'];
	}

	public function onSaveColumns_catalog_edit_default_value($varValue, DataContainer $dc)
	{
		$result = $this->Database->prepare('SELECT m.* FROM tl_module m WHERE m.id=? AND m.catalog_edit_use_default=1')
								->limit(1)
								->execute($dc->id);
		$fields=deserialize($result->catalog_edit_default);
		$varValue=deserialize($varValue);
		if(is_array($fields))
		{
			foreach($fields as $field)
			{
				$varValue[$field]=(isset($varValue[$field]) ? $varValue[$field] : false);
			}
		}
		$varValue=serialize($varValue);
		return $varValue;
	}

	public function onLoadColumns_catalog_edit_default_value($varValue, DataContainer $dc)
	{
		$result = $this->Database->prepare('SELECT m.* FROM tl_module m WHERE m.id=? AND m.catalog_edit_use_default=1')
								->limit(1)
								->execute($dc->id);
		return $varValue;
	}
}

?>