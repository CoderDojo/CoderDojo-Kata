<?php

namespace SWL\MediaWiki\Hooks;

use SWLGroup;
use User;
use Language;
use MWNamespace;

/**
 * Adds the preferences relevant to Semantic Watchlist
 * https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
 *
 * @ingroup SWL
 *
 * @licence GNU GPL v2+
 * @since 1.0
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class GetPreferences {

	protected $user;
	protected $language;
	protected $preferences;
	protected $configuration;

	/**
	 * @since 1.0
	 *
	 * @param User $user
	 * @param Language $language
	 * @param array &$preferences
	 */
	public function __construct( User $user, Language $language, array &$preferences ) {
		$this->user = $user;
		$this->language = $language;
		$this->preferences =& $preferences;
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

		$groups = $this->getAllSwlGroups();

		if ( $this->configuration['egSWLEnableEmailNotify'] ) {
			$this->preferences['swl_email'] = $this->addEmailNotificationPreference();
		}

		if ( $this->configuration['egSWLEnableTopLink'] ) {
			$this->preferences['swl_watchlisttoplink'] = $this->addTopLinkPreference();
		}

		foreach ( $groups as /* SWLGroup */ $group ) {
			$this->handleGroup( $group );
		}

		return true;
	}

	private function handleGroup( SWLGroup $group ) {
		$properties = $group->getProperties();

		if ( empty( $properties ) ) {
			return;
		}

		switch ( true ) {
			case count( $group->getCategories() ) > 0 :
				$type = 'category';
				$name = $group->getCategories();
				$name = $name[0];
				break;
			case count( $group->getNamespaces() ) > 0 :
				$type = 'namespace';
				$name = $group->getNamespaces();
				$name = $name[0] == 0 ? wfMessage( 'main' )->text() : MWNamespace::getCanonicalName( $name[0] );
				break;
			case count( $group->getConcepts() ) > 0 :
				$type = 'concept';
				$name = $group->getConcepts();
				$name = $name[0];
				break;
			default:
				return;
		}

		foreach ( $properties as &$property ) {
			$property = "''$property''";
		}

		$this->preferences['swl_watchgroup_' . $group->getId()] = $this->addGoupPreference(
			$type,
			$group->getName(),
			$name,
			$properties
		);
	}

	protected function getAllSwlGroups() {
		return \SWLGroups::getAll();
	}

	protected function addEmailNotificationPreference() {
		return array(
			'type' => 'toggle',
			'label-message' => 'swl-prefs-emailnofity',
			'section' => 'swl/swlglobal',
		);
	}

	protected function addTopLinkPreference() {
		return array(
			'type' => 'toggle',
			'label-message' => 'swl-prefs-watchlisttoplink',
			'section' => 'swl/swlglobal',
		);
	}

	/**
	 * @search swl-prefs-category-label, swl-prefs-namespace-label,
	 * swl-prefs-concept-label
	 */
	protected function addGoupPreference( $type, $group, $name, $properties ) {
		return  array(
			'type' => 'toggle',
			'label' => wfMessage(
				"swl-prefs-$type-label",
				$group,
				count( $properties ),
				$this->language->listToText( $properties ),
				$name
			)->inLanguage( $this->language )->text(),
			'section' => 'swl/swlgroup',
		);
	}

}
