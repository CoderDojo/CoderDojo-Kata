<?php
/**
 * TemplateData extension.
 *
 * @file
 * @ingroup Extensions
 */

if ( version_compare( $wgVersion, '1.22wmf18', '<' ) ) {
	echo "Extension:TemplateData requires MediaWiki 1.22 or higher.\n";
	exit( 1 );
}

$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'TemplateData',
	'author' => array(
		'Timo Tijhof',
	),
	'version' => '0.1.1',
	'url' => 'https://www.mediawiki.org/wiki/Extension:TemplateData',
	'descriptionmsg' => 'templatedata-desc',
	'license-name' => 'GPLv2',
);

/* Setup */

$dir = __DIR__;

// Register files
$wgMessagesDirs['TemplateData'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['TemplateData'] = $dir . '/TemplateData.i18n.php';
$wgAutoloadClasses['TemplateDataHooks'] = $dir . '/TemplateData.hooks.php';
$wgAutoloadClasses['TemplateDataBlob'] = $dir . '/TemplateDataBlob.php';
$wgAutoloadClasses['ApiTemplateData'] = $dir . '/api/ApiTemplateData.php';

// Register hooks
$wgHooks['ParserFirstCallInit'][] = 'TemplateDataHooks::onParserFirstCallInit';
$wgHooks['PageContentSave'][] = 'TemplateDataHooks::onPageContentSave';
$wgHooks['UnitTestsList'][] = 'TemplateDataHooks::onUnitTestsList';
$wgHooks['ResourceLoaderTestModules'][] = 'TemplateDataHooks::onResourceLoaderTestModules';
$wgHooks['EditPage::showEditForm:initial'][] = 'TemplateDataHooks::onEditPage';

// Register APIs
$wgAPIModules['templatedata'] = 'ApiTemplateData';

// Register page properties
$wgPageProps['templatedata'] = 'Content of &lt;templatedata&gt; tag';

// Register modules
$wgResourceModules['ext.templateData'] = array(
	'styles' => 'resources/ext.templateData.css',
	'position' => 'top',
	'localBasePath' => $dir,
	'remoteExtPath' => 'TemplateData',
);

$wgResourceModules['ext.templateDataGenerator.editPage'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'TemplateData',
	'scripts' => array(
		'modules/ext.templateDataGenerator.editPage.js',
	),
	'dependencies' => array(
		'ext.templateDataGenerator.core',
	),
	'messages' => array(
		'templatedata-editbutton',
		'templatedata-helplink',
		'templatedata-errormsg-jsonbadformat',
	)
);

$wgResourceModules['ext.templateDataGenerator.core'] = array(
	'localBasePath' => $dir,
	'remoteExtPath' => 'TemplateData',
	'styles' => 'modules/ext.templateDataGenerator.css',
	'scripts' => array(
		'modules/ext.templateDataGenerator.core.js',
	),
	'dependencies' => array(
		'jquery.ui.dialog',
		'jquery.ui.button',
		'jquery.ui.sortable',
	),
	'messages' => array(
		'templatedata-modal-button-addparam',
		'templatedata-modal-button-apply',
		'templatedata-modal-button-cancel',
		'templatedata-modal-button-delparam',
		'templatedata-modal-button-importParams',
		'templatedata-modal-errormsg',
		'templatedata-modal-errormsg-import-noparams',
		'templatedata-modal-errormsg-import-paramsalreadyexist',
		'templatedata-modal-notice-import-numparams',
		'templatedata-modal-table-param-actions',
		'templatedata-modal-table-param-aliases',
		'templatedata-modal-table-param-default',
		'templatedata-modal-table-param-desc',
		'templatedata-modal-table-param-label',
		'templatedata-modal-table-param-name',
		'templatedata-modal-table-param-required',
		'templatedata-modal-table-param-suggested',
		'templatedata-modal-table-param-type',
		'templatedata-modal-table-param-type-boolean',
		'templatedata-modal-table-param-type-content',
		'templatedata-modal-table-param-type-wiki-file-name',
		'templatedata-modal-table-param-type-line',
		'templatedata-modal-table-param-type-number',
		'templatedata-modal-table-param-type-boolean',
		'templatedata-modal-table-param-type-date',
		'templatedata-modal-table-param-type-wiki-page-name',
		'templatedata-modal-table-param-type-string',
		'templatedata-modal-table-param-type-unbalanced-wikitext',
		'templatedata-modal-table-param-type-undefined',
		'templatedata-modal-table-param-type-wiki-user-name',
		'templatedata-modal-table-param-uneditablefield',
		'templatedata-modal-title',
		'templatedata-modal-title-templatedesc',
		'templatedata-modal-title-templateparams',
	)
);

/* Configuration */

// Set this to true to use the template documentation
// editor feature
$wgTemplateDataUseGUI = false;
