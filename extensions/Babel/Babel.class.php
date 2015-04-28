<?php
/**
 * Contains main code.
 *
 * @file
 * @author Robert Leverington
 * @author Robin Pepermans
 * @author Niklas Laxström
 * @author Brian Wolff
 * @author Purodha Blissenbach
 * @author Sam Reed
 * @author Siebrand Mazeland
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * Main class for the Babel extension.
 */
class Babel {
	/**
	 * @var Title
	 */
	protected static $title;

	/**
	 * Render the Babel tower.
	 *
	 * @param $parser Parser.
	 * @return string: Babel tower.
	 */
	public static function Render( $parser ) {
		wfProfileIn( __METHOD__ );
		global $wgBabelUseUserLanguage;
		$parameters = func_get_args();
		array_shift( $parameters );
		self::$title = $parser->getTitle();

		self::mTemplateLinkBatch( $parameters );

		$parser->getOutput()->addModuleStyles( 'ext.babel' );

		$content = '';
		$templateParameters = array(); // collects name=value parameters to be passed to wiki templates.
		$createCategories = !$parser->mOptions->mIsPreview;
		foreach ( $parameters as $name ) {
			if ( strpos( $name, '=' ) !== false ) {
				$templateParameters[] = $name;
				continue;
			}
			$components = self::mParseParameter( $name );
			$template = wfMessage( 'babel-template', $name )->inContentLanguage()->text();
			if ( $name === '' ) {
				continue;
			} elseif ( $components !== false ) {
				// Valid parameter syntax (with lowercase language code), babel box
				$content .= self::mGenerateBox( $components['code'], $components['level'] );
				$content .= self::mGenerateCategories(
					$components['code'],
					$components['level'],
					$createCategories
				);
			} elseif ( self::mPageExists( $template ) ) {
				// Check for an existing template
				$templateParameters[0] = $template;
				$template = implode( '|', $templateParameters );
				$content .= self::mGenerateNotaBox( $parser->replaceVariables( "{{{$template}}}" ) );
			} elseif ( self::mValidTitle( $template ) ) {
				// Non-existing page, so try again as a babel box,
				// with converting the code to lowercase
				$components2 = self::mParseParameter( $name, /* code to lowercase */
					true );
				if ( $components2 !== false ) {
					$content .= self::mGenerateBox( $components2['code'], $components2['level'] );
					$content .= self::mGenerateCategories(
						$components2['code'],
						$components2['level'],
						$createCategories
					);
				} else {
					// Non-existent page and invalid parameter syntax, red link.
					$content .= self::mGenerateNotaBox( '[[' . $template . ']]' );
				}
			} else {
				// Invalid title, output raw.
				$content .= self::mGenerateNotaBox( $template );
			}
		}

		if ( $wgBabelUseUserLanguage ) {
			$uiLang = $parser->getOptions()->getUserLangObj();
		} else {
			$uiLang = self::$title->getPageLanguage();
		}

		$top = wfMessage( 'babel', self::$title->getDBkey() )->inLanguage( $uiLang );

		if ( $top->isDisabled() ) {
			$top = '';
		} else {
			$top = $top->text();
			$url = wfMessage( 'babel-url' )->inContentLanguage();
			if ( !$url->isDisabled() ) {
				$top = '[[' . $url->text() . '|' . $top . ']]';
			}
			$top = '! class="mw-babel-header" | ' . $top;
		}
		$footer = wfMessage( 'babel-footer', self::$title->getDBkey() )->inLanguage( $uiLang );

		$url = wfMessage( 'babel-footer-url' )->inContentLanguage();
		$showfooter = '';
		if ( !$footer->isDisabled() && !$url->isDisabled() ) {
			$showfooter = '! class="mw-babel-footer" | [[' .
				$url->text() . '|' . $footer->text() . ']]';
		}
		$spacing = Babel::mCssAttrib( 'border-spacing', 'babel-box-cellspacing', true );
		$padding = Babel::mCssAttrib( 'padding', 'babel-box-cellpadding', true );

		if ( $spacing === '' ) {
			$style = ( $padding === '' ) ? '' : ( 'style="' . $padding . '"' );
		} else {
			$style = ( $padding === '' ) ?
				'style="' . $spacing . '"' :
				'style="' . $padding . ' ' . $spacing . '"';
		}

		$tower = <<<EOT
{|$style class="mw-babel-wrapper"
$top
|-
| $content
|-
$showfooter
|}
EOT;
		wfProfileOut( __METHOD__ );

		return $tower;
	}

	/**
	 * Performs a link batch on a series of templates.
	 *
	 * @param $parameters Array: Templates to perform the link batch on.
	 */
	protected static function mTemplateLinkBatch( $parameters ) {
		wfProfileIn( __METHOD__ );
		$titles = array();
		foreach ( $parameters as $name ) {
			$title = Title::newFromText( wfMessage( 'babel-template', $name )->inContentLanguage()->text() );
			if ( is_object( $title ) ) {
				$titles[] = $title;
			}
		}

		$batch = new LinkBatch( $titles );
		$batch->setCaller( __METHOD__ );
		$batch->execute();
		wfProfileOut( __METHOD__ );
	}

	/**
	 * Identify whether or not a page exists.
	 *
	 * @param $name String: Name of the page to check.
	 * @return Boolean: Indication of whether the page exists.
	 */
	protected static function mPageExists( $name ) {
		$titleObj = Title::newFromText( $name );

		return ( is_object( $titleObj ) && $titleObj->exists() );
	}

	/**
	 * Identify whether or not the passed string would make a valid page name.
	 *
	 * @param $name string: Name of page to check.
	 * @return Boolean: Indication of whether or not the title is valid.
	 */
	protected static function mValidTitle( $name ) {
		$titleObj = Title::newFromText( $name );

		return is_object( $titleObj );
	}

	/**
	 * Parse a parameter, getting a language code and level.
	 *
	 * @param $parameter String: Parameter.
	 * @param $strtolower Boolean: Whether to convert the language code to lowercase
	 * @return Array: { 'code' => xx, 'level' => xx }
	 */
	protected static function mParseParameter( $parameter, $strtolower = false ) {
		wfProfileIn( __METHOD__ );
		global $wgBabelDefaultLevel, $wgBabelCategoryNames;
		$return = array();

		$babelcode = $strtolower ? strtolower( $parameter ) : $parameter;
		// Try treating the paramter as a language code (for default level).
		$code = BabelLanguageCodes::getCode( $babelcode );
		if ( $code !== false ) {
			$return['code'] = $code;
			$return['level'] = $wgBabelDefaultLevel;
			wfProfileOut( __METHOD__ );

			return $return;
		}
		// Try splitting the paramter in to language and level, split on last hyphen.
		$lastSplit = strrpos( $parameter, '-' );
		if ( $lastSplit === false ) {
			wfProfileOut( __METHOD__ );

			return false;
		}
		$code = substr( $parameter, 0, $lastSplit );
		$level = substr( $parameter, $lastSplit + 1 );

		$babelcode = $strtolower ? strtolower( $code ) : $code;
		// Validate code.
		$return['code'] = BabelLanguageCodes::getCode( $babelcode );
		if ( $return['code'] === false ) {
			wfProfileOut( __METHOD__ );

			return false;
		}
		// Validate level.
		$level = strtoupper( $level );
		if ( !isset( $wgBabelCategoryNames[$level] ) ) {
			wfProfileOut( __METHOD__ );

			return false;
		}
		$return['level'] = $level;
		wfProfileOut( __METHOD__ );

		return $return;
	}

	/**
	 * Generate an inner item which is not a babel box.
	 *
	 * @param $content String: what's inside the box, in wikitext format.
	 * @return String: A single non-babel box, in wikitext format.
	 */
	protected static function mGenerateNotaBox( $content ) {
		$dir_head = self::$title->getPageLanguage()->getDir();
		$notabox = <<<EOT
<div class="mw-babel-notabox" dir="$dir_head">$content</div>
EOT;

		return $notabox;
	}

	/**
	 * Generate a babel box for the given language and level.
	 *
	 * @param $code String: Language code to use.
	 * @param $level String or Integer: Level of ability to use.
	 * @return String: A single babel box, in wikitext format.
	 */
	protected static function mGenerateBox( $code, $level ) {
		wfProfileIn( __METHOD__ );
		$lang = wfBCP47( $code );
		$portal = wfMessage( 'babel-portal', $code )->inContentLanguage()->plain();
		if ( $portal !== '' ) {
			$portal = "[[$portal|$lang]]";
		} else {
			$portal = $lang;
		}
		$header = "$portal<span class=\"mw-babel-box-level-$level\">-$level</span>";

		$code = strtolower( $code );
		$name = BabelLanguageCodes::getName( $code );
		$code = BabelLanguageCodes::getCode( $code );
		$text = self::mGetText( $name, $code, $level );

		$dir_current = Language::factory( $code )->getDir();

		$spacing = Babel::mCssAttrib( 'border-spacing', 'babel-cellspacing', true );
		$padding = Babel::mCssAttrib( 'padding', 'babel-cellpadding', true );

		if ( $spacing === '' ) {
			$style = ( $padding === '' ) ? '' : ( 'style="' . $padding . '"' );
		} else {
			$style = ( $padding === '' ) ?
				'style="' . $spacing . '"' :
				'style="' . $padding . ' ' . $spacing . '"';
		}

		$dir_head = self::$title->getPageLanguage()->getDir();

		$box = <<<EOT
<div class="mw-babel-box mw-babel-box-$level" dir="$dir_head">
{|$style
! dir="$dir_head" | $header
| dir="$dir_current" lang="$lang" | $text
|}
</div>
EOT;
		wfProfileOut( __METHOD__ );

		return $box;
	}

	/**
	 * Get the text to display in the language box for specific language and
	 * level.
	 *
	 * @param $name string
	 * @param $language String: Language code of language to use.
	 * @param $level String: Level to use.
	 * @return String: Text for display, in wikitext format.
	 */
	protected static function mGetText( $name, $language, $level ) {
		wfProfileIn( __METHOD__ );
		global $wgBabelMainCategory, $wgBabelCategoryNames;

		if ( $wgBabelCategoryNames[$level] === false ) {
			$categoryLevel = self::$title->getFullText();
		} else {
			$categoryLevel = ':Category:' .
				self::mReplaceCategoryVariables( $wgBabelCategoryNames[$level], $language );
		}

		if ( $wgBabelMainCategory === false ) {
			$categoryMain = self::$title->getFullText();
		} else {
			$categoryMain = ':Category:' .
				self::mReplaceCategoryVariables( $wgBabelMainCategory, $language );
		}

		// Give grep a chance to find the usages:
		// babel-0-n, babel-1-n, babel-2-n, babel-3-n, babel-4-n, babel-5-n, babel-N-n
		$text = wfMessage( "babel-$level-n",
			$categoryLevel, $categoryMain, '', self::$title->getDBkey()
		)->inLanguage( $language )->text();

		$fallbackLanguage = Language::getFallbackfor( $language );
		$fallback = wfMessage( "babel-$level-n",
			$categoryLevel, $categoryMain, '', self::$title->getDBkey()
		)->inLanguage( $fallbackLanguage ? $fallbackLanguage : $language )->text();

		// Give grep a chance to find the usages:
		// babel-0, babel-1, babel-2, babel-3, babel-4, babel-5, babel-N
		if ( $text == $fallback ) {
			$text = wfMessage( "babel-$level",
				$categoryLevel, $categoryMain, $name, self::$title->getDBkey()
			)->inLanguage( $language )->text();
		}

		wfProfileOut( __METHOD__ );

		return $text;
	}

	/**
	 * Generate categories for the given language and level.
	 *
	 * @param $code String: Language code to use.
	 * @param $level String or Integer: Level of ability to use.
	 * @param $createCategories Boolean: If true, creates non existing categories;
	 * otherwise, doesn't create them.
	 * @return String: Wikitext to add categories.
	 */
	protected static function mGenerateCategories( $code, $level, $createCategories = true ) {
		wfProfileIn( __METHOD__ );
		global $wgBabelMainCategory, $wgBabelCategoryNames;

		$r = '';

		# Add main category
		if ( $wgBabelMainCategory !== false ) {
			$category = self::mReplaceCategoryVariables( $wgBabelMainCategory, $code );
			$r .= "[[Category:$category|$level]]";
			if ( $createCategories ) {
				BabelAutoCreate::create( $category, $code );
			}
		}

		# Add level category
		if ( $wgBabelCategoryNames[$level] !== false ) {
			$category = self::mReplaceCategoryVariables( $wgBabelCategoryNames[$level], $code );
			$r .= "[[Category:$category]]";
			if ( $createCategories ) {
				BabelAutoCreate::create( $category, $code, $level );
			}
		}

		wfProfileOut( __METHOD__ );

		return $r;
	}

	/**
	 * Replace the placeholder variables from the category names configurtion
	 * array with actual values.
	 *
	 * @param $category String: Category name (containing variables).
	 * @param $code String: Language code of category.
	 * @return String: Category name with variables replaced.
	 */
	protected static function mReplaceCategoryVariables( $category, $code ) {
		global $wgLanguageCode;
		$category = strtr( $category, array(
			'%code%' => $code,
			'%wikiname%' => BabelLanguageCodes::getName( $code, $wgLanguageCode ),
			'%nativename%' => BabelLanguageCodes::getName( $code )
		) );

		return $category;
	}

	/**
	 * Determine a CSS attribute, such as "border-spacing", from a localizeable message.
	 *
	 * @param $name String: name of CSS attribute.
	 * @param $key String: Message key of attribute value.
	 * @param $assumeNumbersArePixels Boolean: if true, treat numbers values as pixels;
	 * otherwise, keep values as is (default: false).
	 * @todo Move this function to a more appropriate place, likely outside the class.
	 * @return Message|string
	 */
	protected static function mCssAttrib( $name, $key, $assumeNumbersArePixels = false ) {
		$value = wfMessage( $key )->inContentLanguage();
		if ( $value->isDisabled() ) {
			$value = '';
		} else {
			$value = htmlentities( $value->text(), ENT_COMPAT, 'UTF-8' );
			if ( $assumeNumbersArePixels && is_numeric( $value ) && $value !== "0" ) {
				//Compatibility: previous babel-box-cellpadding and
				//babel-box-cellspacing entries were in HTML, not CSS
				//and so used numbers without unity as pixels.
				$value .= 'px';
			}
			$value = ' ' . $name . ': ' . $value . ';';
		}

		return $value;
	}

	/**
	 * Determine an HTML attribute, such as "cellspacing" or "title", from a localizeable message.
	 *
	 * @param $name String: name of HTML attribute.
	 * @param $key String: Message key of attribute value.
	 * TODO: move this function to a more appropriate place, likely outside the class.
	 *       or consider to deprecate it as it's not used anymore.
	 * @return Message|string
	 */
	protected static function mHtmlAttrib( $name, $key ) {
		$value = wfMessage( $key )->inContentLanguage();
		if ( $value->isDisabled() ) {
			$value = '';
		} else {
			$value = ' ' . $name . '="' . htmlentities( $value->text(), ENT_COMPAT, 'UTF-8' ) .
				'"'; // must get rid of > and " inside value
		}

		return $value;
	}

	/**
	 * Gets the list of languages a user has set up with Babel
	 *
	 * TODO Can be done much smarter, e.g. by saving the languages in the DB and getting them there
	 * TODO There could be an API module that returns the result of this function
	 *
	 * @param User $user
	 * @param string $level minimal level as given in $wgBabelCategoryNames
	 * @return string[] List of language codes
	 *
	 * @since Version 1.9.0
	 */
	public static function getUserLanguages( User $user, $level = null ) {
		// Right now the function only returns something if the user is categorized appropriately
		// (as defined by the $wgBabelMainCategory setting). If categorization is off, this function
		// will return an empty array.
		// If Babel would save the languages of the user in a Database table, this workaround using
		// the categories would not be needed.
		global $wgBabelMainCategory;
		// If Babel is not configured as required, return nothing.
		// Note also that "Set to false to disable main category".
		if ( $wgBabelMainCategory === false ) {
			return array();
		}

		// The string we construct here will be a pony, it will not be a valid category
		$babelCategoryTitle = Title::makeTitle( NS_CATEGORY, $wgBabelMainCategory );
		// Quote everything to avoid unexpected matches due to parenthesis form
		// It is not necessary to quote any additional chars except the special chars for the regex
		// and perhaps the limiting char, but that should not be respected as anything other than
		// edge delimiter.
		$babelCategoryString = preg_quote( $babelCategoryTitle->getPrefixedDBkey(), '/' );
		// Look for the %code% inside the string and put a group match in the same place
		// This will only work if the previous works so the string isn't misinterpreted as a regular
		// expression itself
		$codeRegex = '/^' . preg_replace( '/%code%/', '(.+?)(-([0-5N]))?', $babelCategoryString ) . '$/';

		$categories = array_keys( $user->getUserPage()->getParentCategories() );

		// We sort on proficiency level
		$result = array();
		foreach ( $categories as $category ) {
			// Only process categories that matches, $match will be created if necessary
			$res = preg_match( $codeRegex, $category, $match );
			if ( $res ) {
				// lowercase the first char, but stay away from the others in case of region codes
				$code = BabelLanguageCodes::getCode( lcfirst( $match[1] ) );
				if ( $code !== false ) {
					$result[$code] = isset( $match[3] ) ? $match[3] : 'N';
				}
			}
		}

		if ( isset( $level ) ) {
			$level = (string)$level;
			// filter down the set, note that this uses a text sort!
			$result = array_filter(
				$result,
				function ( $value ) use ( $level ) {
					return ( strcmp( $value, $level ) >= 0 );
				}
			);
			// sort and retain keys
			uasort(
				$result,
				function ( $a, $b ) {
					return -strcmp( $a, $b );
				}
			);
		}

		return array_keys( $result );
	}
}
