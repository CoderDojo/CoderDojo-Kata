<?php
/**
 * Code for automatic creation of categories.
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
 * Class for automatic creation of Babel category pages.
 */
class BabelAutoCreate {
	/**
	 * @var User
	 */
	protected static $user = false;

	/**
	 * Abort user creation if the username is that of the autocreation username.
	 * @param $user User
	 * @param $errorText
	 * @return bool
	 */
	public static function onAbortNewAccount( User $user, &$errorText ) {
		$url = wfMessage( 'babel-url' )->text();
		$errorText = wfMessage( 'babel-autocreate-abort', $url )->parse();
		$autoCreateUser = wfMessage( 'babel-autocreate-user' )->inContentLanguage()->plain();

		return $user->getName() !== $autoCreateUser;
	}

	/**
	 * Create category.
	 *
	 * @param $category String: Name of category to create.
	 * @param $code String: Code of language that the category is for.
	 * @param $level String: Level that the category is for.
	 */
	public static function create( $category, $code, $level = null ) {
		wfProfileIn( __METHOD__ );
		$category = strip_tags( $category );
		$title = Title::makeTitleSafe( NS_CATEGORY, $category );
		if ( $title === null || $title->exists() ) {
			wfProfileOut( __METHOD__ );

			return;
		}
		global $wgLanguageCode;
		$language = BabelLanguageCodes::getName( $code, $wgLanguageCode );
		$params = array( $language, $code );
		if ( $level === null ) {
			$text = wfMessage( 'babel-autocreate-text-main', $params )->inContentLanguage()->text();
		} else {
			array_unshift( $params, $level );
			$text = wfMessage( 'babel-autocreate-text-levels', $params )->inContentLanguage()->text();
		}

		$user = self::user();
		# Do not add a message if the username is invalid or if the account that adds it, is blocked
		if ( !$user || $user->isBlocked() ) {
			wfProfileOut( __METHOD__ );

			return;
		}

		if ( !$title->quickUserCan( 'create', $user ) ) {
			wfProfileOut( __METHOD__ );

			return; # The Babel AutoCreate account is not allowed to create the page
		}

		/* $article->doEdit will call $wgParser->parse.
		 * Calling Parser::parse recursively is baaaadd... (bug 29245)
		 * @todo FIXME: surely there is a better way?
		 */
		global $wgParser, $wgParserConf;
		$oldParser = $wgParser;
		$parserClass = $wgParserConf['class'];
		$wgParser = new $parserClass( $wgParserConf );

		$url = wfMessage( 'babel-url' )->inContentLanguage()->plain();
		$article = new WikiPage( $title );
		$article->doEdit(
			$text,
			wfMessage( 'babel-autocreate-reason', $url )->text(),
			EDIT_FORCE_BOT,
			false,
			$user
		);

		$wgParser = $oldParser;
		wfProfileOut( __METHOD__ );
	}

	/**
	 * Get user object.
	 *
	 * @return User object: User object for autocreate user.
	 */
	public static function user() {
		if ( !self::$user ) {
			$userName = wfMessage( 'babel-autocreate-user' )->inContentLanguage()->plain();
			self::$user = User::newFromName( $userName );
			if ( self::$user && !self::$user->isLoggedIn() ) {
				self::$user->addToDatabase();
			}
		}

		return self::$user;
	}
}
