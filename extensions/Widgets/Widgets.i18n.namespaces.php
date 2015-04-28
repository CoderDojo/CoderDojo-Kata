<?php

$namespaceNames = array();

// For wikis without Widgets installed.
if ( !defined( 'NS_WIDGET' ) ) {
	define( 'NS_WIDGET', 274 );
	define( 'NS_WIDGET_TALK', 275 );
}

$namespaceNames['en'] = array(
	NS_WIDGET       => 'Widget',
	NS_WIDGET_TALK  => 'Widget_talk',
);

$namespaceNames['de'] = array(
	NS_WIDGET_TALK  => 'Widget_Diskussion',
);
