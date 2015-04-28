<?php

/**
 * Interface to modify the semantic watchlist groups.
 *
 * @since 0.1
 *
 * @file SpecialWatchlistConditions.php
 * @ingroup SemanticWatchlist
 *
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SpecialWatchlistConditions extends SpecialPage {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'WatchlistConditions', 'semanticwatchgroups' );
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @since 0.1
	 */
	public function getDescription() {
		return $this->msg( 'special-' . strtolower( $this->getName() ) )->text();
	}

	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
	 *
	 * @since 0.1
	 */
	public function setHeaders() {
		global $wgOut;
		$wgOut->setArticleRelated( false );
		$wgOut->setRobotPolicy( 'noindex,nofollow' );
		$wgOut->setPageTitle( $this->getDescription() );
	}

	/**
	 * Main method.
	 *
	 * @since 0.1
	 *
	 * @param string $arg
	 */
	public function execute( $arg ) {
		global $wgOut, $wgUser;

		$this->setHeaders();
		$this->outputHeader();

		// If the user is authorized, display the page, if not, show an error.
		if ( !$this->userCanExecute( $wgUser ) ) {
			$this->displayRestrictionError();
			return;
		}

		$wgOut->addHTML(
			'<strong><p class="saveMessage" style=" display:none;width: 100px; alight:center;text-align:center; background: #f9f9aa; border: 1px solid #dd9">' . $this->msg( 'swl-group-saved' )->text() . '</p></strong>'
		);

		$groupsHtml = array();

		foreach ( SWLGroups::getAll() as $group ) {
			$groupsHtml[] = $this->getGroupHtml( $group );
		}
		$wgOut->addHTML(
			'<div id="swl-groups">' .
				implode( '', $groupsHtml ) .
			'</div>'
		);

		$wgOut->addHTML( "<p>" . Html::element(
			'input',
			array(
				'type' => 'button',
				'value' => $this->msg( 'swl-group-add-group' )->text(),
				'id' => 'swl-add-group-button'
			)
		) . "</p>\n" );

		$wgOut->addHTML( "<p>" . Html::element(
			'input',
			array(
				'type' => 'button',
				'value' => $this->msg( 'swl-group-save' )->text(),
				'id' => 'swl-save-all'
			)
		) . "</p>\n" );

		$wgOut->addModules( 'ext.swl.watchlistconditions' );
	}

	/**
	 * Creates and returns the HTML for a single watchlist group.
	 *
	 * @since 0.1
	 *
	 * @param SWLGroup $group
	 *
	 * @return string
	 */
	protected function getGroupHtml( SWLGroup $group ) {
		$namespaces = $group->getNamespaces();

		foreach ( $namespaces as &$ns ) {
			$ns = $ns == 0 ? 'Main' : MWNamespace::getCanonicalName( $ns );
		}

		return Html::rawElement(
			'fieldset',
			array(
				'id' => 'swl_group_' . $group->getId(),
				'groupid' => $group->getId(),
				'class' => 'swl_group',
				'groupname' => $group->getName(),
				'categories' => implode( '|', $group->getCategories() ),
				'namespaces' => implode( '|', $namespaces ),
				'properties' => implode( '|', $group->getProperties() ),
				'concepts' => implode( '|', $group->getConcepts() ),
				'customTexts' => implode( '|', $group->getSerializedCustomTexts() ),
			),
			Html::element(
				'legend',
				array(),
				$this->msg( 'swl-group-legend' )->text()
			)
		);
	}

}
