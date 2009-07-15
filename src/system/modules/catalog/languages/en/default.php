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
 * Default language file (en).
 *
 * PHP version 5
 * @copyright  Martin Komara, Thyon Design 2008
 * @author     Martin Komara, John Brand <john.brand@thyon.com> 
 * @package    Language 
 * @license    GPL 
 * @filesource
 */


/**
 * Miscellaneous
 */

$GLOBALS['TL_LANG']['ERR']['tableExists'] = 'Table `%s` already exists. Please choose different name.';
$GLOBALS['TL_LANG']['ERR']['tableDoesNotExist'] = 'Table `%s` does not exists.';
$GLOBALS['TL_LANG']['ERR']['columnExists'] = 'Column `%s` already exists. Please choose different name.';
$GLOBALS['TL_LANG']['ERR']['columnDoesNotExist'] = 'Column `%s` does not exist in table %s.';
$GLOBALS['TL_LANG']['ERR']['systemColumn'] = 'Name `%s` is reserved for system use. Please choose different name.';
$GLOBALS['TL_LANG']['ERR']['invalidColumnName'] = 'Invalid column name `%s`. Please use only letters, numbers and underscore.';
$GLOBALS['TL_LANG']['ERR']['invalidTableName'] = 'Invalid table name `%s`. Please use only letters, numbers and underscore.';

$GLOBALS['TL_LANG']['ERR']['aliasTitleMissing'] = 'Incorrect alias field configuration. Missing Title field parameter.';
$GLOBALS['TL_LANG']['ERR']['aliasDuplicate'] = 'Alias field `%s` already defined. Only one alias field is allowed per table.';

$GLOBALS['TL_LANG']['ERR']['limitMin'] = 'This value is smaller than the minimum value: %s';
$GLOBALS['TL_LANG']['ERR']['limitMax'] = 'This value is greater than the maximum value: %s';

//Select Options
$GLOBALS['TL_LANG']['MSC']['optionsTitle'] = 'Select %s';


/**
 * Filter Module
 */

$GLOBALS['TL_LANG']['MSC']['catalogSearch'] = 'Go';
$GLOBALS['TL_LANG']['MSC']['clearFilter'] = 'Clear all filters';
$GLOBALS['TL_LANG']['MSC']['clearAll'] = 'Clear %s'; // %s=field label
$GLOBALS['TL_LANG']['MSC']['selectNone'] = 'Select %s'; // %s=field label
$GLOBALS['TL_LANG']['MSC']['optionselected'] 	= '[%s]'; // %s=field label

$GLOBALS['TL_LANG']['MSC']['invalidFilter'] = 'Invalid filter type';


// Checkbox options
$GLOBALS['TL_LANG']['MSC']['true'] = 'Yes';
$GLOBALS['TL_LANG']['MSC']['false'] = 'No';


// Date options
$GLOBALS['TL_LANG']['MSC']['daterange']['y'] = 'Last year';
$GLOBALS['TL_LANG']['MSC']['daterange']['h'] = 'Last 6 months';
$GLOBALS['TL_LANG']['MSC']['daterange']['m'] = 'Last month';
$GLOBALS['TL_LANG']['MSC']['daterange']['w'] = 'Last week';
$GLOBALS['TL_LANG']['MSC']['daterange']['d'] = 'Yesterday';
$GLOBALS['TL_LANG']['MSC']['daterange']['t'] = 'Today';
$GLOBALS['TL_LANG']['MSC']['daterange']['df'] = 'Tomorrow';
$GLOBALS['TL_LANG']['MSC']['daterange']['wf'] = 'Next week';
$GLOBALS['TL_LANG']['MSC']['daterange']['mf'] = 'Next month';
$GLOBALS['TL_LANG']['MSC']['daterange']['hf'] = 'Next 6 months';
$GLOBALS['TL_LANG']['MSC']['daterange']['yf'] = 'Next year';

// Sort options
$GLOBALS['TL_LANG']['MSC']['unsorted'] 	= 'Select Order';
$GLOBALS['TL_LANG']['MSC']['lowhigh'] = '(Low to High)';
$GLOBALS['TL_LANG']['MSC']['highlow'] = '(High to Low)';
$GLOBALS['TL_LANG']['MSC']['AtoZ'] 		= '(A-Z)';
$GLOBALS['TL_LANG']['MSC']['ZtoA'] 		= '(Z-A)';
$GLOBALS['TL_LANG']['MSC']['truefalse'] = '(True-False)';
$GLOBALS['TL_LANG']['MSC']['falsetrue'] = '(False-True)';
$GLOBALS['TL_LANG']['MSC']['dateasc'] 	= '(Oldest First)';
$GLOBALS['TL_LANG']['MSC']['datedesc'] 	= '(Recent First)';


/**
 * List Module
 */

$GLOBALS['TL_LANG']['MSC']['viewCatalog']     = 'View the item details';
$GLOBALS['TL_LANG']['MSC']['editCatalog']     = 'Edit the item details';

/**
 * Notify Module
 */

$GLOBALS['TL_LANG']['MSC']['notifySubmit']	= 'Send Notification';
$GLOBALS['TL_LANG']['MSC']['notifyConfirm']	= 'Your notification has been sent.';

/**
 * Miscellaneous
 */

$GLOBALS['TL_LANG']['MSC']['catalogCondition']	= 'Please first select the following filter(s): %s';
$GLOBALS['TL_LANG']['MSC']['catalogInvalid'] 		= 'Invalid Catalog!';
$GLOBALS['TL_LANG']['MSC']['catalogNoFields'] 	= 'No Catalog fields defined!';
$GLOBALS['TL_LANG']['ERR']['catalogItemInvalid'] = 'Catalog Item not Found';



?>