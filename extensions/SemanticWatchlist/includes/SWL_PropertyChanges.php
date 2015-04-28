<?php

/**
 * A collection of semantic properties and changes changes made to them.
 * This class is based on SMWSemanticData and can be seen as a simplified
 * version with SWLPropertyChange objects, each holding 2 SMWDataItem objects,
 * instead of SMWDataItem objects.
 * 
 * @since 0.1
 * 
 * @file SWL_PropertyChange.php
 * @ingroup SemanticWatchlist
 * 
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SWLPropertyChanges implements Iterator {

	private $pos = 0;
	private $currentRow = null;	
	
	/**
	 * Cache for the localized version of the namespace prefix "Property:".
	 *
	 * @var string
	 */
	static private $propertyPrefix = '';	
	
	/**
	 * Array mapping property keys (string) to arrays of SWLPropertyChange.
	 * 
	 * @var array of SWLPropertyChange
	 */
	private $changes = array();
	
	/**
	 * Array mapping property keys (string) to SMWDIProperty objects.
	 *
	 * @var array of SMWDIProperty
	 */
	private $properties = array();
	
	/**
	 * Indicates if there are changes in the list.
	 * 
	 * @var boolean
	 */
	private $hasChanges = false;
	
	/**
	 * Get the array of all properties that have changes.
	 *
	 * @return array of SMWDIProperty
	 */
	public function getProperties() {
		return $this->properties;
	}
	
	/**
	 * Returns if the list contains any changes.
	 * This info is cached, so the call is cheaper then doing a count.
	 * 
	 * @return boolean
	 */
	public function hasChanges() {
		return $this->hasChanges;
	}
	
	/**
	 * Get the array of all stored values for some property.
	 *
	 * @param $property SMWDIProperty
	 * 
	 * @return array of SWLPropertyChange
	 */
	public function getPropertyChanges( SMWDIProperty $property ) {
		if ( array_key_exists( $property->getKey(), $this->changes ) ) {
			return $this->changes[$property->getKey()];
		} else {
			return array();
		}  
	}
	
	/**
	 * Store a value for a property identified by its SMWDataItem object.
	 *
	 * @note There is no check whether the type of the given data item
	 * agrees with the type of the property. Since property types can
	 * change, all parts of SMW are prepared to handle mismatched data item
	 * types anyway.
	 *
	 * @param SMWDIProperty $property
	 * @param SWLPropertyChange $change
	 */
	public function addPropertyObjectChange( SMWDIProperty $property, SWLPropertyChange $change ) {
		if ( $property->isInverse() ) { // inverse properties cannot be used for annotation
			return;
		}

		if ( !array_key_exists( $property->getKey(), $this->changes ) ) {
			$this->changes[$property->getKey()] = array();
			$this->properties[$property->getKey()] = $property;
		}

		$this->changes[$property->getKey()][] = $change;
		
		$this->hasChanges = true;
	}

	/**
	 * Store a value for a given property identified by its text label
	 * (without namespace prefix).
	 *
	 * @param string $propertyName
	 * @param SWLPropertyChange $change
	 */
	public function addPropertyChange( $propertyName, SWLPropertyChange $change ) {
		$propertyKey = smwfNormalTitleDBKey( $propertyName );

		if ( array_key_exists( $propertyKey, $this->properties ) ) {
			$property = $this->properties[$propertyKey];
		} else {
			if ( self::$propertyPrefix == '' ) {
				global $wgContLang;
				self::$propertyPrefix = $wgContLang->getNsText( SMW_NS_PROPERTY ) . ':';
			} // explicitly use prefix to cope with things like [[Property:User:Stupid::somevalue]]

			$propertyDV = SMWPropertyValue::makeUserProperty( self::$propertyPrefix . $propertyName );

			if ( !$propertyDV->isValid() ) { // error, maybe illegal title text
				return;
			}
			
			$property = $propertyDV->getDataItem();
		}

		$this->addPropertyObjectChange( $property, $change );
	}
	
	/**
	 * Removes all changes for a certian property.
	 * 
	 * @param SMWDIProperty $property
	 */
	public function removeChangesForProperty( SMWDIProperty $property ) {
		if ( array_key_exists( $property->getKey(), $this->changes ) ) {
			unset( $this->changes[$property->getKey()] );
			unset( $this->properties[$property->getKey()] );
		}
	}
	
	function rewind() {
		$this->pos = 0;
		$this->currentRow = null;
	}

	function current() {
		if ( is_null( $this->currentRow ) ) {
			$this->next();
		}
		return $this->currentRow;
	}

	function key() {
		return $this->pos;
	}

	function next() {
		$this->pos++;
		$this->currentRow = array_key_exists( $this->pos, $this->changes ) ? $this->changes[$this->pos] : false;
		return $this->currentRow;
	}

	function valid() {
		return $this->current() !== false;
	}
	
}