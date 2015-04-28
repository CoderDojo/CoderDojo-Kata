<?php
/**
 * Class holding functions for displaying widgets.
 */

class WidgetRenderer {

	// A randomly-generated string, used to prevent malicious users from
	// spoofing the output of #widget in order to have arbitrary
	// JavaScript show up in the page's output.
	static $mRandomString;

	public static function initRandomString() {
		// Set the random string, used in both encoding and decoding.
		self::$mRandomString = substr( base64_encode( rand() ), 0, 7 );
	}

	public static function renderWidget( &$parser, $widgetName ) {
		global $IP, $wgWidgetsCompileDir;

		$smarty = new Smarty;
		$smarty->left_delimiter = '<!--{';
		$smarty->right_delimiter = '}-->';
		$smarty->compile_dir = $wgWidgetsCompileDir;

		// registering custom Smarty plugins
		$smarty->addPluginsDir( "$IP/extensions/Widgets/smarty_plugins/" );

		$smarty->enableSecurity();
		// These settings were for Smarty v2 - they don't seem to
		// have an equivalent in Smarty v3.
		/*
		$smarty->security_settings = array(
			'IF_FUNCS' => array(
					'is_array',
					'isset',
					'array',
					'list',
					'count',
					'sizeof',
					'in_array',
					'true',
					'false',
					'null'
					),
			'MODIFIER_FUNCS' => array( 'validate' )
		);
		*/

		// Register the Widgets extension functions.
		$smarty->registerResource(
			'wiki',
			array(
				array( 'WidgetRenderer', 'wiki_get_template' ),
				array( 'WidgetRenderer', 'wiki_get_timestamp' ),
				array( 'WidgetRenderer', 'wiki_get_secure' ),
				array( 'WidgetRenderer', 'wiki_get_trusted' )
			)
		);

		$params = func_get_args();
		// The first and second params are the parser and the widget
		// name - we already have both.
		array_shift( $params );
		array_shift( $params );

		$params_tree = array();

		foreach ( $params as $param ) {
			$pair = explode( '=', $param, 2 );

			if ( count( $pair ) == 2 ) {
				$key = trim( $pair[0] );
				$val = trim( $pair[1] );
			} else {
				$key = $param;
				$val = true;
			}

			if ( $val == 'false' ) {
				$val = false;
			}

			/* If the name of the parameter has object notation

				a.b.c.d

			   then we assign stuff to hash of hashes, not scalar

			*/
			$keys = explode( '.', $key );

			// $subtree will be moved from top to the bottom and
			// at the end will point to the last level.
			$subtree =& $params_tree;

			// Go through all the keys but the last one.
			$last_key = array_pop( $keys );

			foreach ( $keys as $subkey ) {
				// If next level of subtree doesn't exist yet,
				// create an empty one.
				if ( !array_key_exists( $subkey, $subtree ) ) {
					$subtree[$subkey] = array();
				}

				// move to the lower level
				$subtree =& $subtree[$subkey];
			}

			// last portion of the key points to itself
			if ( isset( $subtree[$last_key] ) ) {
				// If this is already an array, push into it;
				// otherwise, convert into an array first.
				if ( !is_array( $subtree[$last_key] ) ) {
					$subtree[$last_key] = array( $subtree[$last_key] );
				}
				$subtree[$last_key][] = $val;
			} else {
				// doesn't exist yet, just setting a value
				$subtree[$last_key] = $val;
			}
		}

		$smarty->assign( $params_tree );

		try {
			$output = $smarty->fetch( "wiki:$widgetName" );
		} catch ( Exception $e ) {
			return '<div class=\"error\">' . wfMessage( 'widgets-error', htmlentities( $widgetName ) )->text() . '</div>';
		}

		// Hide the widget from the parser.
		$output = 'ENCODED_CONTENT ' . self::$mRandomString . base64_encode($output) . ' END_ENCODED_CONTENT';
		return array( $output, 'noparse' => true, 'isHTML' => true );
	}

	public static function processEncodedWidgetOutput( &$out, &$text ) {
		// Find all hidden content and restore to normal
		$text = preg_replace_callback(
			'/ENCODED_CONTENT ' . self::$mRandomString . '([0-9a-zA-Z\/+]+=*)* END_ENCODED_CONTENT/',
			function( $matches ) {
				return base64_decode( $matches[1]);
			},
			$text
		);

		return true;
	}

	// the following four functions are all registered with Smarty
	public static function wiki_get_template( $widgetName, &$widgetCode, $smarty_obj ) {
		global $wgWidgetsUseFlaggedRevs;
	
		$widgetTitle = Title::newFromText( $widgetName, NS_WIDGET );

		if ( $widgetTitle && $widgetTitle->exists() ) {
			if ( $wgWidgetsUseFlaggedRevs ) {
				$flaggedWidgetArticle = FlaggedArticle::getTitleInstance( $widgetTitle );
				$flaggedWidgetArticleRevision = $flaggedWidgetArticle->getStableRev();

				if ( $flaggedWidgetArticleRevision ) {
					$widgetCode = $flaggedWidgetArticleRevision->getRevText();
				} else {
					$widgetCode = '';
				}
			} else {
				$widgetArticle = new Article( $widgetTitle, 0 );
				$widgetCode = $widgetArticle->getContent();
			}

			// Remove <noinclude> sections and <includeonly> tags from form definition
			$widgetCode = StringUtils::delimiterReplace( '<noinclude>', '</noinclude>', '', $widgetCode );
			$widgetCode = strtr( $widgetCode, array( '<includeonly>' => '', '</includeonly>' => '' ) );

			return true;
		} else {
			return false;
		}
	}

	public static function wiki_get_timestamp( $widgetName, &$widgetTimestamp, $smarty_obj ) {
		$widgetTitle = Title::newFromText( $widgetName, NS_WIDGET );

		if ( $widgetTitle && $widgetTitle->exists() ) {
			$widgetArticle = new Article( $widgetTitle, 0 );
			$widgetTimestamp = $widgetArticle->getTouched();
			return true;
		} else {
			return false;
		}
	}

	public static function wiki_get_secure( $tpl_name, &$smarty_obj ) {
		// assume all templates are secure
		return true;
	}

	public static function wiki_get_trusted( $tpl_name, &$smarty_obj ) {
		// not used for templates
	}

}
