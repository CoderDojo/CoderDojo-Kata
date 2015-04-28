<?php

namespace SWL\MediaWiki\Hooks;

use Title;
use User;

/**
 * Called after the personal URLs have been set up, before they are shown.
 * https://secure.wikimedia.org/wikipedia/mediawiki/wiki/Manual:Hooks/PersonalUrls
 *
 * @ingroup SWL
 *
 * @licence GNU GPL v2+
 * @since 1.0
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class PersonalUrls {

	protected $personalUrls;
	protected $title;
	protected $user;

	/**
	 * @since 1.0
	 *
	 * @param array &$personalUrls
	 * @param Title &$title
	 * @param User $user
	 */
	public function __construct( array &$personalUrls, Title $title, User $user ) {
		$this->personalUrls =& $personalUrls;
		$this->title = $title;
		$this->user = $user;
	}

	/**
	 * @since 1.0
	 *
	 * @param array $configuration
	 */
	public function setConfiguration( array $configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function execute() {
		return $this->isEnabledForTopLink() && $this->isEnabledForUser() ? $this->addSwlTopLinkUrl(): true;
	}

	protected function isEnabledForTopLink() {
		return isset( $this->configuration['egSWLEnableTopLink'] ) && $this->configuration['egSWLEnableTopLink'];
	}

	protected function isEnabledForUser() {
		return $this->user->isLoggedIn() && $this->user->getOption( 'swl_watchlisttoplink' );
	}

	protected function addSwlTopLinkUrl() {

		$url = \SpecialPage::getTitleFor( 'SemanticWatchlist' )->getLinkUrl();

		$semanticWatchlist = array(
			'text' => wfMessage( 'prefs-swl' )->inLanguage( $this->title->getPageLanguage() )->text(),
			'href' => $url,
			'active' => ( $url == $this->title->getLinkUrl() )
		);

		$keys = array_keys( $this->personalUrls );

		array_splice(
			$this->personalUrls,
			$this->getWatchListLocation( $keys ),
			1,
			array( $this->getWatchListItem( $keys ), $semanticWatchlist )
		);

		return true;
	}

	protected function getWatchListLocation( $keys ) {
		return array_search( 'watchlist', $keys );
	}

	protected function getWatchListItem( $keys ) {
		return $this->personalUrls[ $keys [ $this->getWatchListLocation( $keys ) ] ];
	}

}
