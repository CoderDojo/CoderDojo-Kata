<?php

class CCFilters {

	/**
	 * @param array $conds
	 * @param array $tables
	 * @param array $join_conds
	 * @param FormOptions $opts
	 * @return bool
	 */
	public static function user( &$conds, &$tables, &$join_conds, FormOptions $opts ) {
		global $wgRequest;
		$opts->add( 'users', '' );
		$users = $wgRequest->getVal( 'users' );
		if ( $users === null ) {
			return true;
		}

		$idfilters = array();
		$userArr = explode( '|', $users );
		foreach ( $userArr as $u ) {
			$id = User::idFromName( $u );
			if ( $id !== null ) {
				$idfilters[] = $id;
			}
		}
		if ( count( $idfilters ) ) {
			$dbr = wfGetDB( DB_SLAVE );
			$conds[] = 'rc_user IN (' . $dbr->makeList( $idfilters ) . ')';
			$opts->setValue( 'users', $users );
		}

		return true;
	}

	/**
	 * @param $items array
	 * @param $opts FormOptions
	 * @return bool
	 */
	public static function userForm( &$items, FormOptions $opts ) {
		$opts->consumeValue( 'users' );
		global $wgRequest;

		$default = $wgRequest->getVal( 'users', '' );
		$items['users'] = Xml::inputLabelSep( wfMessage( 'cleanchanges-users' )->text(), 'users',
			'mw-users', 40, $default  );
		return true;
	}

	/**
	 * @param array $conds
	 * @param array $tables
	 * @param array $join_conds
	 * @param FormOptions $opts
	 * @return bool
	 */
	public static function trailer( &$conds, &$tables, &$join_conds, FormOptions $opts ) {
		global $wgRequest;
		$opts->add( 'trailer', '' );
		$trailer = $wgRequest->getVal( 'trailer' );
		if ( $trailer === null ) return true;

		$dbr = wfGetDB( DB_SLAVE );
		$conds[] = 'rc_title ' . $dbr->buildLike( $dbr->anyString(), $trailer );
		$opts->setValue( 'trailer', $trailer );

		return true;
	}

	/**
	 * @param array $items
	 * @param FormOptions $opts
	 * @return bool
	 */
	public static function trailerForm( &$items, FormOptions $opts ) {
		$opts->consumeValue( 'trailer' );

		global $wgRequest;
		$default = $wgRequest->getVal( 'trailer', '' );
		/**
		 * @var Language $wgLang
		 */
		global $wgLang;
		if ( is_callable( array( 'LanguageNames', 'getNames' ) ) ) {
			$languages = LanguageNames::getNames( $wgLang->getCode(),
				LanguageNames::FALLBACK_NORMAL,
				LanguageNames::LIST_MW
			);
		} else {
			$languages = Language::getLanguageNames( false );
		}
		ksort( $languages );
		$options = Xml::option( wfMessage( 'cleanchanges-language-na' )->text(), '', $default === '' );
		foreach ( $languages as $code => $name ) {
			$selected = ( "/$code" === $default );
			$options .= Xml::option( "$code - $name", "/$code", $selected ) . "\n";
		}
		$str =
		Xml::openElement( 'select', array(
			'name' => 'trailer',
			'class' => 'mw-language-selector',
			'id' => 'sp-rc-language',
		) ) .
		$options .
		Xml::closeElement( 'select' );

		$items['tailer'] = array( wfMessage( 'cleanchanges-language' )->escaped(), $str );
		return true;
	}
}
