<?php
/**
 * Additional input types for [http://www.mediawiki.org/wiki/Extension:SemanticForms Semantic Forms].
 *
 * @defgroup SFI Semantic Forms Inputs
 *
 * @author Stephan Gambke
 * @author Yaron Koren
 * @author Jeroen de Dauw
 * @author Sanyam Goyal
 * @author Yury Katkov
 */

/**
 * The main file of the Semantic Forms Inputs extension
 *
 * @file
 * @ingroup SFI
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of a MediaWiki extension, it is not a valid entry point.' );
}

if ( version_compare( $wgVersion, '1.17', 'lt' ) ) {
	die( '<b>Error:</b> This version of <a href="https://www.mediawiki.org/wiki/Extension:Semantic_Forms_Inputs">Semantic Forms Inputs</a> is only compatible with MediaWiki 1.17 or above. You need to upgrade MediaWiki first.' );
}

if ( !defined( 'SF_VERSION' ) ) {
	die( '<b>Error:</b> <a href="https://www.mediawiki.org/wiki/Extension:Semantic_Forms_Inputs">Semantic Forms Inputs</a> is a Semantic Forms extension. You need to install <a href="https://www.mediawiki.org/wiki/Extension:Semantic_Forms">Semantic Forms</a> first.' );
}

if ( version_compare( SF_VERSION, '2.4.2', 'lt' ) ) {
	die( '<b>Error:</b> This version of <a href="https://www.mediawiki.org/wiki/Extension:Semantic_Forms_Inputs">Semantic Forms Inputs</a> is only compatible with Semantic Forms 2.4.1 or above. You need to upgrade <a href="https://www.mediawiki.org/wiki/Extension:Semantic_Forms">Semantic Forms</a> first.' );
}

define( 'SFI_VERSION', '0.9.0 alpha' );

// create and initialize settings
$sfigSettings = new SFISettings();

// register extension
$wgExtensionCredits[ 'semantic' ][] = array(
	'path' => __FILE__,
	'name' => 'Semantic Forms Inputs',
	'author' => array( '[http://www.mediawiki.org/wiki/User:F.trott Stephan Gambke]', '...' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Semantic_Forms_Inputs',
	'descriptionmsg' => 'semanticformsinputs-desc',
	'version' => SFI_VERSION,
	'license-name' => 'GPL-2.0+'
);

$dir = dirname( __FILE__ );

// load user settings
require_once( $dir . '/includes/SFI_Settings.php' );

$wgMessagesDirs['SemanticFormsInputs'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['SemanticFormsInputs'] = $dir . '/SemanticFormsInputs.i18n.php';
$wgHooks['ParserFirstCallInit'][] = 'wfSFISetup';

$wgAutoloadClasses['SFIUtils'] = $dir . '/includes/SFI_Utils.php';
$wgAutoloadClasses['SFIDatePicker'] = $dir . '/includes/SFI_DatePicker.php';
$wgAutoloadClasses['SFITimePicker'] = $dir . '/includes/SFI_TimePicker.php';
$wgAutoloadClasses['SFIDateTimePicker'] = $dir . '/includes/SFI_DateTimePicker.php';
$wgAutoloadClasses['SFIMenuSelect'] = $dir . '/includes/SFI_MenuSelect.php';
$wgAutoloadClasses['SFIRegExp'] = $dir . '/includes/SFI_RegExp.php';
$wgAutoloadClasses['SFITwoListBoxes'] = $dir . '/includes/SFI_TwoListBoxes.php';
$wgAutoloadClasses['SFIDateCheck'] = $dir . '/includes/SFI_DateCheck.php';

$wgResourceModules['ext.semanticformsinputs.datepicker'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'SemanticFormsInputs',
	'scripts' => 'libs/datepicker.js',
	'dependencies' => array(
		'jquery.ui.datepicker',
		'ext.semanticforms.main'
	),
);

$wgResourceModules['ext.semanticformsinputs.timepicker'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'SemanticFormsInputs',
	'scripts' => 'libs/timepicker.js',
	'styles' => 'skins/SFI_Timepicker.css',
	'dependencies' => array(
		'ext.semanticforms.main'
	),
);

$wgResourceModules['ext.semanticformsinputs.datetimepicker'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'SemanticFormsInputs',
	'scripts' => 'libs/datetimepicker.js',
	'dependencies' => array(
		'ext.semanticformsinputs.timepicker',
		'ext.semanticformsinputs.datepicker'
	),
);

$wgResourceModules['ext.semanticformsinputs.menuselect'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'SemanticFormsInputs',
	'scripts' => 'libs/menuselect.js',
	'styles' => 'skins/SFI_Menuselect.css',
	'dependencies' => array(
		'ext.semanticforms.main'
	),
);

$wgResourceModules['ext.semanticformsinputs.regexp'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'SemanticFormsInputs',
	'scripts' => 'libs/regexp.js',
	'dependencies' => array(
		'ext.semanticforms.main'
	),
);

$wgResourceModules['ext.semanticformsinputs.twolistboxes'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'SemanticFormsInputs',
	'scripts' => array(
		'libs/jquery.quicksearch.js',
		'libs/jquery.multi-select.js',
		'libs/twolistboxes.js'
	),
	'styles' => 'skins/SFI_TwoListBoxes.css',
	'dependencies' => 'ext.semanticforms.main'
);


$wgResourceModules[ 'ext.semanticformsinputs.datecheck' ] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'SemanticFormsInputs',
	'scripts' => array(
		'libs/jquery.form-validator.js',
		'libs/datecheck.js',
	),
	'styles' => 'skins/SFI_DateCheck.css',
	'dependencies' => array(
		'ext.semanticforms.main'
	),
);

/**
 * Class to encapsulate all settings
 */
class SFISettings {
	// general settings
	public $scriptPath;

	// settings for input type datepicker
	public $datePickerFirstDate;
	public $datePickerLastDate;
	public $datePickerDateFormat;
	public $datePickerWeekStart;
	public $datePickerShowWeekNumbers;
	public $datePickerDisableInputField;
	public $datePickerShowResetButton;
	public $datePickerDisabledDaysOfWeek;
	public $datePickerHighlightedDaysOfWeek;
	public $datePickerDisabledDates;
	public $datePickerHighlightedDates;
	public $datePickerMonthNames;
	public $datePickerDayNames;
}

/**
 * Registers the input types with Semantic Forms.
 */
function wfSFISetup() {
	global $sfgFormPrinter, $wgVersion;

	$sfgFormPrinter->registerInputType( 'SFIDatePicker' );
	$sfgFormPrinter->registerInputType( 'SFITimePicker' );
	$sfgFormPrinter->registerInputType( 'SFIDateTimePicker' );
	$sfgFormPrinter->registerInputType( 'SFIMenuSelect' );
	$sfgFormPrinter->registerInputType( 'SFIRegExp' );
	$sfgFormPrinter->registerInputType( 'SFITwoListBoxes' );
	$sfgFormPrinter->registerInputType( 'SFIDateCheck' );

	return true;
}
