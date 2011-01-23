-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the TYPOlight *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************


-- --------------------------------------------------------

-- 
-- Table `tl_catalog_types`
-- 

CREATE TABLE `tl_catalog_types` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `tableName` varchar(64) NOT NULL default '',

  `format` text NULL,
  `addImage` char(1) NOT NULL default '',
  `singleSRC` varchar(255) NOT NULL default '',
  `size` varchar(255) NOT NULL default '',

  `jumpTo` smallint(5) unsigned NOT NULL default '0',
  `aliasField` varchar(64) NOT NULL default '',
  `publishField` varchar(64) NOT NULL default '',
  `titleField` varchar(64) NOT NULL default '',
  `descriptionField` varchar(64) NOT NULL default '',
  `keywordsField` varchar(64) NOT NULL default '',
  `allowManualSort` char(1) NOT NULL default '',

  `import` char(1) NOT NULL default '',
  `importAdmin` char(1) NOT NULL default '',
  `importDelete` char(1) NOT NULL default '',

  `allowComments` char(1) NOT NULL default '',
  `template` varchar(32) NOT NULL default '',
  `perPage` smallint(5) unsigned NOT NULL default '0',
  `sortOrder` varchar(32) NOT NULL default '',
  `moderate` char(1) NOT NULL default '',
  `bbcode` char(1) NOT NULL default '',
  `disableCaptcha` char(1) NOT NULL default '',
  `requireLogin` char(1) NOT NULL default '',
  `hideMember` char(1) NOT NULL default '',
  `disableWebsite` char(1) NOT NULL default '',

  `searchable` char(1) NOT NULL default '',
  `searchCondition` varchar(255) NOT NULL default '',
  `titleField` varchar(64) NOT NULL default '',

  `makeFeed` char(1) NOT NULL default '',
  `feedFormat` varchar(32) NOT NULL default '',
  `language` varchar(32) NOT NULL default '',
  `source` varchar(32) NOT NULL default '',
  `datesource` varchar(32) NOT NULL default '',
  `maxItems` smallint(5) unsigned NOT NULL default '0',
  `feedBase` varchar(255) NOT NULL default '',
  `alias` varbinary(128) NOT NULL default '',
  `feedTitle` varchar(255) NOT NULL default '',
  `description` text NULL,
  
  PRIMARY KEY  (`id`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_catalog_fields`
-- 

CREATE TABLE `tl_catalog_fields` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `sorting` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `colName` varchar(64) NOT NULL default '',
  `type` varchar(64) NOT NULL default '',
  `titleField` char(1) NOT NULL default '',
  `aliasTitle` varchar(255) NOT NULL default '',
  `filteredField` char(1) NOT NULL default '',
  `insertBreak` char(1) NOT NULL default '',
  `legendTitle` varchar(255) NOT NULL default '',
  `legendHide` char(1) NOT NULL default '',
  `width50` char(1) NOT NULL default '',
  `sortingField` char(1) NOT NULL default '',
  `groupingMode` int(10) NOT NULL default '0',
  `searchableField` char(1) NOT NULL default '',
  `parentCheckbox` varchar(255) NOT NULL default '',
  `mandatory` char(1) NOT NULL default '',
  `includeBlankOption` char(1) NOT NULL default '',
  `parentFilter` varchar(255) NOT NULL default '',
  `calcValue` text NULL,
  `defValue` varchar(255) NOT NULL default '',
  `minValue` int(10) NULL default NULL,
  `maxValue` int(10) NULL default NULL,
  `format` char(1) NOT NULL default '',
  `formatFunction` varchar(6) NOT NULL default '',
  `formatStr` varchar(255) NOT NULL default '',
  `formatPrePost` varchar(255) NOT NULL default '',
  `uniqueItem` char(1) NOT NULL default '',
  `rte` char(1) NOT NULL default '',
  `rte_editor` varchar(255) NOT NULL default 'tinyMCE',
  `allowHtml` char(1) NOT NULL default '',
  `textHeight` int(10) unsigned NOT NULL default '0',
  `itemTable` varchar(255) NOT NULL default '',
  `itemTableValueCol` varchar(255) NOT NULL default '',
  `itemSortCol` varchar(255) NOT NULL default '',
  `limitItems` char(1) NOT NULL default '',
  `items` text NULL,
  `childrenSelMode` varchar(64) NOT NULL default '',
  `treeMinLevel` int(10) NULL default NULL,
  `treeMaxLevel` int(10) NULL default NULL,
  `itemFilter` text NULL,
  `includeTime` char(1) NOT NULL default '',
  `multiple` char(1) NOT NULL default '',
  `sortBy` varchar(32) NOT NULL default '',
  `showLink` char(1) NOT NULL default '',
  `showImage` char(1) NOT NULL default '',
  `imageSize` varchar(255) NOT NULL default '',
  `customFiletree` char(1) NOT NULL default '',
  `uploadFolder` varchar(255) NOT NULL default '',
  `validFileTypes` varchar(255) NOT NULL default '',
  `filesOnly` char(1) NOT NULL default '',
  `editGroups` blob NULL,

  PRIMARY KEY  (`id`),
  KEY `pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_catalog_comments`
-- 

CREATE TABLE `tl_catalog_comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `catid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  `email` varchar(128) NOT NULL default '',
  `website` varchar(128) NOT NULL default '',
  `comment` text NULL,
  `ip` varchar(15) NOT NULL default '',
  `date` int(10) unsigned NOT NULL default '0',
  `published` char(1) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `pid` (`pid`),
  KEY `catid` (`catid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_module`
-- 

CREATE TABLE `tl_module` (
  `catalog` int(10) unsigned NOT NULL default '0',
  `catalog_template` varchar(64) NOT NULL default '',
  `catalog_layout` varchar(64) NOT NULL default '',

  `catalog_jumpTo` smallint(5) unsigned NOT NULL default '0',

  `catalog_filtertemplate` varchar(64) NOT NULL default '',

  `catalog_filter_enable` char(1) NOT NULL default '',
  `catalog_filter_headline` varchar(255) NOT NULL default '',
  `catalog_filters` blob NULL,
  `catalog_tags_multi` char(1) NOT NULL default '',
  `catalog_filter_hide` char(1) NOT NULL default '',

  `catalog_search_enable` char(1) NOT NULL default '',
  `catalog_search_headline` varchar(255) NOT NULL default '',
  `catalog_search` blob NULL,

  `catalog_range_enable` char(1) NOT NULL default '',
  `catalog_range_headline` varchar(255) NOT NULL default '',
  `catalog_range` blob NULL,

  `catalog_date_enable` char(1) NOT NULL default '',
  `catalog_date_headline` varchar(255) NOT NULL default '',
  `catalog_dates` blob NULL,
  `catalog_date_ranges` blob NULL,

  `catalog_sort_enable` char(1) NOT NULL default '',
  `catalog_sort_headline` varchar(255) NOT NULL default '',
  `catalog_sort` blob NULL,
  `catalog_sort_type` varchar(15) NOT NULL default '',

  `catalog_visible` blob NULL,
  `catalog_comments_disable` char(1) NOT NULL default '',

  `catalog_query_mode` varchar(5) NOT NULL default '',
  `catalog_tags_mode` varchar(5) NOT NULL default '',

  `catalog_link_override` char(1) NOT NULL default '',
  `catalog_islink` blob NULL,
  `catalog_link_window` char(1) NOT NULL default '',
  `catalog_goback_disable` char(1) NOT NULL default '',

  `catalog_condition_enable` char(1) NOT NULL default '',
  `catalog_condition` blob NULL,

  `catalog_thumbnails_override` char(1) NOT NULL default '',
  `catalog_imagemain_field` varchar(64) NOT NULL default '',
  `catalog_imagemain_size` varchar(255) NOT NULL default '',
  `catalog_imagemain_fullsize` char(1) NOT NULL default '',
  `catalog_imagegallery_field` varchar(64) NOT NULL default '',
  `catalog_imagegallery_size` varchar(255) NOT NULL default '',
  `catalog_imagegallery_fullsize` char(1) NOT NULL default '',
  `sortBy` varchar(32) NOT NULL default '',

  `catalog_limit` varchar(32) NOT NULL default '',
  `catalog_random_disable` char(1) NOT NULL default '',

-- new catalog conditions
  `catalog_where` text NULL,
  `catalog_order` text NULL,

-- catalog related
  `catalog_related` blob NULL,
  `catalog_related_tagcount` smallint(3) unsigned NOT NULL default '0',

-- catalog reference
  `catalog_selected` int(10) unsigned NOT NULL default '0',
  `catalog_match` varchar(64) NOT NULL default '',
  `catalog_reference` varchar(64) NOT NULL default '',

-- catalog navigation
  `catalog_navigation` varchar(64) NOT NULL default '',
  `catalog_show_items` char(1) NOT NULL default '',
  `catalog_show_field` varchar(64) NOT NULL default '',

-- catalog edit
  `catalog_edit_enable` char(1) NOT NULL default '',
  `catalog_editJumpTo` smallint(5) unsigned NOT NULL default '0',
  `catalog_edit` blob NULL,
  `catalog_edit_use_default` char(1) NOT NULL default '',
  `catalog_edit_default` blob NULL,
  `catalog_edit_default_value` blob NULL,

-- catalog notify
  `catalog_notify_fields` text NULL,
  `catalog_recipients` text NULL,
  `catalog_recipient_fields` text NULL,
  `catalog_subject` varchar(255) NOT NULL default '',
  `catalog_notify` text NULL,

-- catalog filter
  `catalog_filter_cond_from_lister` char(1) NOT NULL default '0',

-- LIMIT n,m for listings
   `catalog_list_use_limit` char(1) NOT NULL default '',
   `catalog_list_offset` smallint(5) NOT NULL default '0',

) ENGINE=MyISAM DEFAULT CHARSET=utf8;



-- --------------------------------------------------------

-- 
-- Table `tl_user_group`
-- 
-- added by thyon

CREATE TABLE `tl_user_group` (
  `catalogs` blob NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_user`
-- 
-- added by thyon

CREATE TABLE `tl_user` (
  `catalogs` blob NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

-- 
-- Table `tl_layout`
-- 
-- added by c.schiffler for rss feeds

CREATE TABLE `tl_layout` (
  `catalogfeeds` blob NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

