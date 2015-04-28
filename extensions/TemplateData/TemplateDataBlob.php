<?php
/**
 * @file
 * @ingroup Extensions
 */

/**
 * Represents the information about a template,
 * coming from the JSON blob in the <templatedata> tags
 * on wiki pages.
 *
 * @class
 */
class TemplateDataBlob {
	// Size of MySQL 'blob' field; page_props table where the data is stored uses one.
	const MAX_LENGTH = 65535;

	/**
	 * @var stdClass
	 */
	private $data;

	/**
	 * @var Status: Cache of TemplateDataBlob::parse
	 */
	private $status;

	/**
	 * Parse and validate passed JSON and create a TemplateDataBlob object.
	 * Accepts and handles user-provided data.
	 *
	 * @param string $json
	 * @throws MWException
	 * @return TemplateDataBlob
	 */
	public static function newFromJSON( $json ) {
		$tdb = new self( json_decode( $json ) );

		$status = $tdb->parse();

		if ( !$status->isOK() ) {
			// If data is invalid, replace with the minimal valid blob.
			// This is to make sure that, if something forgets to check the status first,
			// we don't end up with invalid data in the database.
			$tdb->data = new stdClass();
			$tdb->data->description = null;
			$tdb->data->params = new stdClass();
			$tdb->data->sets = array();
		}
		$tdb->status = $status;
		return $tdb;
	}

	/**
	 * Parse and validate passed JSON (possibly gzip-compressed) and create a TemplateDataBlob object.
	 *
	 * @param string $json
	 * @return TemplateDataBlob
	 */
	public static function newFromDatabase( $json ) {
		// Handle GZIP compression. \037\213 is the header for GZIP files.
		if ( substr( $json, 0, 2 ) === "\037\213" ) {
			$json = gzdecode( $json );
		}
		return self::newFromJSON( $json );
	}

	/**
	 * Parse the data, normalise it and validate it.
	 *
	 * See spec.templatedata.json for the expected format of the JSON object.
	 * @return Status
	 */
	private function parse() {
		$data = $this->data;

		static $rootKeys = array(
			'description',
			'params',
			'paramOrder',
			'sets',
		);

		static $paramKeys = array(
			'label',
			'required',
			'suggested',
			'description',
			'deprecated',
			'aliases',
			'default',
			'inherits',
			'type',
		);

		static $types = array(
			'content',
			'line',
			'number',
			'boolean',
			'string',
			'date',
			'unbalanced-wikitext',
			'unknown',
			'wiki-page-name',
			'wiki-user-name',
			'wiki-file-name',
		);

		static $typeCompatMap = array(
			'string/line' => 'line',
			'string/wiki-page-name' => 'wiki-page-name',
			'string/wiki-user-name' => 'wiki-user-name',
			'string/wiki-file-name' => 'wiki-file-name',
		);

		if ( $data === null ) {
			return Status::newFatal( 'templatedata-invalid-parse' );
		}

		if ( !is_object( $data ) ) {
			return Status::newFatal( 'templatedata-invalid-type', 'templatedata', 'object' );
		}

		foreach ( $data as $key => $value ) {
			if ( !in_array( $key, $rootKeys ) ) {
				return Status::newFatal( 'templatedata-invalid-unknown', $key );
			}
		}

		// Root.description
		if ( isset( $data->description ) ) {
			if ( !is_object( $data->description ) && !is_string( $data->description ) ) {
				return Status::newFatal( 'templatedata-invalid-type', 'description', 'string|object' );
			}
			$data->description = self::normaliseInterfaceText( $data->description );
		} else {
			$data->description = null;
		}

		// Root.params
		if ( !isset( $data->params ) ) {
			return Status::newFatal( 'templatedata-invalid-missing', 'params', 'object' );
		}

		if ( !is_object( $data->params ) ) {
			return Status::newFatal( 'templatedata-invalid-type', 'params', 'object' );
		}

		// Deep clone
		// We need this to determine whether a property was originally set
		// to decide whether 'inherits' will add it or not.
		$unnormalizedParams = unserialize( serialize( $data->params ) );
		$paramNames = array();

		foreach ( $data->params as $paramName => $paramObj ) {
			if ( !is_object( $paramObj ) ) {
				return Status::newFatal(
					'templatedata-invalid-type',
					"params.{$paramName}",
					'object'
				);
			}

			foreach ( $paramObj as $key => $value ) {
				if ( !in_array( $key, $paramKeys ) ) {
					return Status::newFatal(
						'templatedata-invalid-unknown',
						"params.{$paramName}.{$key}"
					);
				}
			}

			// Param.label
			if ( isset( $paramObj->label ) ) {
				if ( !is_object( $paramObj->label ) && !is_string( $paramObj->label ) ) {
					// TODO: Also validate that the keys are valid lang codes and the values strings.
					return Status::newFatal(
						'templatedata-invalid-type',
						"params.{$paramName}.label",
						'string|object'
					);
				}
				$paramObj->label = self::normaliseInterfaceText( $paramObj->label );
			} else {
				$paramObj->label = null;
			}

			// Param.required
			if ( isset( $paramObj->required ) ) {
				if ( !is_bool( $paramObj->required ) ) {
					return Status::newFatal(
						'templatedata-invalid-type',
						"params.{$paramName}.required",
						'boolean'
					);
				}
			} else {
				$paramObj->required = false;
			}

			// Param.suggested
			if ( isset( $paramObj->suggested ) ) {
				if ( !is_bool( $paramObj->suggested ) ) {
					return Status::newFatal(
						'templatedata-invalid-type',
						"params.{$paramName}.suggested",
						'boolean'
					);
				}
			} else {
				$paramObj->suggested = false;
			}

			// Param.description
			if ( isset( $paramObj->description ) ) {
				if ( !is_object( $paramObj->description ) && !is_string( $paramObj->description ) ) {
					// TODO: Also validate that the keys are valid lang codes and the values strings.
					return Status::newFatal(
						'templatedata-invalid-type',
						"params.{$paramName}.description",
						'string|object'
					);
				}
				$paramObj->description = self::normaliseInterfaceText( $paramObj->description );
			} else {
				$paramObj->description = null;
			}

			// Param.deprecated
			if ( isset( $paramObj->deprecated ) ) {
				if ( !is_bool( $paramObj->deprecated ) && !is_string( $paramObj->deprecated ) ) {
					return Status::newFatal(
						'templatedata-invalid-type',
						"params.{$paramName}.deprecated",
						'boolean|string'
					);
				}
			} else {
				$paramObj->deprecated = false;
			}

			// Param.aliases
			if ( isset( $paramObj->aliases ) ) {
				if ( !is_array( $paramObj->aliases ) ) {
					// TODO: Validate the array values.
					return Status::newFatal(
						'templatedata-invalid-type',
						"params.{$paramName}.aliases",
						'array'
					);
				}
			} else {
				$paramObj->aliases = array();
			}

			// Param.default
			if ( isset( $paramObj->default ) ) {
				if ( !is_string( $paramObj->default ) ) {
					return Status::newFatal(
						'templatedata-invalid-type',
						"params.{$paramName}.default",
						'string'
					);
				}
			} else {
				$paramObj->default = '';
			}

			// Param.type
			if ( isset( $paramObj->type ) ) {
				if ( !is_string( $paramObj->type ) ) {
					return Status::newFatal(
						'templatedata-invalid-type',
						"params.{$paramName}.type",
						'string'
					);
				}

				// Map deprecated types to newer versions
				if ( isset( $typeCompatMap[ $paramObj->type ] ) ) {
					$paramObj->type = $typeCompatMap[ $paramObj->type ];
				}

				if ( !in_array( $paramObj->type, $types ) ) {
					return Status::newFatal(
						'templatedata-invalid-value',
						'params.' . $paramName . '.type'
					);
				}
			} else {
				$paramObj->type = 'unknown';
			}

			$paramNames[] = $paramName;
		}

		// Param.inherits
		// Done afterwards to avoid code duplication
		foreach ( $data->params as $paramName => $paramObj ) {
			if ( isset( $paramObj->inherits ) ) {
				if ( !isset( $data->params->{ $paramObj->inherits } ) ) {
						return Status::newFatal(
							'templatedata-invalid-missing',
							"params.{$paramObj->inherits}"
						);
				}
				$parentParamObj = $data->params->{ $paramObj->inherits };
				foreach ( $parentParamObj as $key => $value ) {
					if ( !in_array( $key, $paramKeys ) ) {
						return Status::newFatal( 'templatedata-invalid-unknown', $key );
					}
					if ( !isset( $unnormalizedParams->$paramName->$key ) ) {
						$paramObj->$key = is_object( $parentParamObj->$key ) ?
							clone $parentParamObj->$key :
							$parentParamObj->$key;
					}
				}
				unset( $paramObj->inherits );
			}
		}

		// Root.paramOrder
		if ( isset( $data->paramOrder ) ) {
			if ( !is_array( $data->paramOrder ) ) {
				return Status::newFatal( 'templatedata-invalid-type', 'paramOrder', 'array' );
			}

			if ( !count( $data->paramOrder ) ) {
				return Status::newFatal( 'templatedata-invalid-empty-array', "paramOrder" );
			}

			if ( count( $data->paramOrder ) < count( $paramNames ) ) {
				$i = count( $data->paramOrder );
				return Status::newFatal( 'templatedata-invalid-missing', "paramOrder[$i]" );
			}

			// Validate each of the values corresponds to a parameter and that there are no
			// duplicates
			$seen = array();
			foreach ( $data->paramOrder as $i => $param ) {
				if ( !isset( $data->params->$param ) ) {
					return Status::newFatal( 'templatedata-invalid-value', "paramOrder[$i]" );
				}
				if ( isset( $seen[$param] ) ) {
					return Status::newFatal(
						'templatedata-invalid-duplicate-value',
						"paramOrder[$i]",
						"paramOrder[{$seen[$param]}]",
						$param
					);
				}
				$seen[$param] = $i;
			}

		} elseif ( count( $paramNames ) ) {
			$data->paramOrder = $paramNames;
		}

		// Root.sets
		if ( isset( $data->sets ) ) {
			if ( !is_array( $data->sets ) ) {
				return Status::newFatal( 'templatedata-invalid-type', 'sets', 'array' );
			}
		} else {
			$data->sets = array();
		}

		foreach ( $data->sets as $setNr => $setObj ) {
			if ( !is_object( $setObj ) ) {
				return Status::newFatal( 'templatedata-invalid-value', "sets.{$setNr}" );
			}

			if ( !isset( $setObj->label ) ) {
				return Status::newFatal(
					'templatedata-invalid-missing',
					"sets.{$setNr}.label",
					'string|object'
				);
			}

			if ( !is_object( $setObj->label ) && !is_string( $setObj->label ) ) {
				// TODO: Also validate that the keys are valid lang codes and the values strings.
				return Status::newFatal(
					'templatedata-invalid-type',
					"sets.{$setNr}.label",
					'string|object'
				);
			}

			$setObj->label = self::normaliseInterfaceText( $setObj->label );

			if ( !isset( $setObj->params ) ) {
				return Status::newFatal( 'templatedata-invalid-missing', "sets.{$setNr}.params", 'array' );
			}

			if ( !is_array( $setObj->params ) ) {
				return Status::newFatal( 'templatedata-invalid-type', "sets.{$setNr}.params", 'array' );
			}

			if ( !count( $setObj->params ) ) {
				return Status::newFatal( 'templatedata-invalid-empty-array', "sets.{$setNr}.params" );
			}

			foreach ( $setObj->params as $i => $param ) {
				if ( !isset( $data->params->$param ) ) {
					return Status::newFatal( 'templatedata-invalid-value', "sets.{$setNr}.params[$i]" );
				}
			}
		}

		$length = strlen( $this->getJSONForDatabase() );
		if ( $length > self::MAX_LENGTH ) {
			return Status::newFatal( 'templatedata-invalid-length', $length, self::MAX_LENGTH );
		}

		return Status::newGood();
	}

	/**
	 * Normalise a InterfaceText field in the TemplateData blob.
	 * @return stdClass|string $text
	 */
	protected static function normaliseInterfaceText( $text ) {
		if ( is_string( $text ) ) {
			global $wgContLang;
			$ret = new stdClass();
			$ret->{ $wgContLang->getCode() } = $text;
			return $ret;
		}
		return $text;
	}

	/**
	 * Get a single localized string from an InterfaceText object.
	 *
	 * Uses the preferred language passed to this function, or one of its fallbacks,
	 * or the site content language, or its fallbacks.
	 *
	 * @param stdClass $text An InterfaceText object
	 * @param string $langCode Preferred language
	 * @return null|string Text value from the InterfaceText object or null if no suitable
	 *  match was found
	 */
	protected static function getInterfaceTextInLanguage( stdClass $text, $langCode ) {
		if ( isset( $text->$langCode ) ) {
			return $text->$langCode;
		}

		list( $userlangs, $sitelangs ) = Language::getFallbacksIncludingSiteLanguage( $langCode );

		foreach ( $userlangs as $lang ) {
			if ( isset( $text->$lang ) ) {
				return $text->$lang;
			}
		}

		foreach ( $sitelangs as $lang ) {
			if ( isset( $text->$lang ) ) {
				return $text->$lang;
			}
		}

		// If none of the languages are found fallback to null. Alternatively we could fallback to
		// reset( $text ) which will return whatever key there is, but we should't give the user a
		// "random" language with no context (e.g. could be RTL/Hebrew for an LTR/Japanese user).
		return null;
	}

	/**
	 * @return Status
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return object
	 */
	public function getData() {
		// TODO: Returned by reference. Data is a private member. Use clone instead?
		return $this->data;
	}

	/**
	 * Get data with all InterfaceText objects resolved to a single string to the
	 * appropriate language.
	 *
	 * @param string $langCode Preferred language
	 * @return object
	 */
	public function getDataInLanguage( $langCode ) {
		// Deep clone, also need to clone ->params and all interfacetext objects
		// within param properties.
		$data = unserialize( serialize( $this->data ) );

		// Root.description
		if ( $data->description !== null ) {
			$data->description = self::getInterfaceTextInLanguage( $data->description, $langCode );
		}

		foreach ( $data->params as $paramObj ) {
			// Param.label
			if ( $paramObj->label !== null ) {
				$paramObj->label = self::getInterfaceTextInLanguage( $paramObj->label, $langCode );
			}

			// Param.description
			if ( $paramObj->description !== null ) {
				$paramObj->description = self::getInterfaceTextInLanguage( $paramObj->description, $langCode );
			}
		}

		foreach ( $data->sets as $setObj ) {
			$label = self::getInterfaceTextInLanguage( $setObj->label, $langCode );
			if ( $label === null ) {
				// Contrary to other InterfaceTexts, set label is not optional. If we're here it
				// means the template data from the wiki doesn't contain either the user language,
				// site language or any of its fallbacks. Wikis should fix data that is in this
				// condition (TODO: Disallow during saving?). For now, fallback to whatever we can
				// get that does exist in the text object.
				$arr = (array)$setObj->label;
				$label = reset( $arr );
			}

			$setObj->label = $label;
		}

		return $data;
	}

	/**
	 * @return string JSON
	 */
	public function getJSON() {
		return json_encode( $this->data );
	}

	/**
	 * @return string JSON, gzip-compressed
	 */
	public function getJSONForDatabase() {
		return gzencode( $this->getJSON() );
	}

	public function getHtml( Language $lang ) {
		$data = $this->getDataInLanguage( $lang->getCode() );
		$html =
			Html::openElement( 'div', array( 'class' => 'mw-templatedata-doc-wrap' ) )
			. Html::element(
				'p',
				array(
					'class' => array(
						'mw-templatedata-doc-desc',
						'mw-templatedata-doc-muted' => $data->description === null,
					)
				),
				$data->description !== null ?
					$data->description :
					wfMessage( 'templatedata-doc-desc-empty' )->inLanguage( $lang )->text()
			)
			. '<table class="wikitable mw-templatedata-doc-params">'
			. Html::element(
				'caption',
				array(),
				wfMessage( 'templatedata-doc-params' )->inLanguage( $lang )->text()
			)
			. '<thead><tr>'
			. Html::element(
				'th',
				array( 'colspan' => 2 ),
				wfMessage( 'templatedata-doc-param-name' )->inLanguage( $lang )->text()
			)
			. Html::element(
				'th',
				array(),
				wfMessage( 'templatedata-doc-param-desc' )->inLanguage( $lang )->text()
			)
			. Html::element(
				'th',
				array(),
				wfMessage( 'templatedata-doc-param-type' )->inLanguage( $lang )->text()
			)
			. Html::element(
				'th',
				array(),
				wfMessage( 'templatedata-doc-param-default' )->inLanguage( $lang )->text()
			)
			. Html::element(
				'th',
				array(),
				wfMessage( 'templatedata-doc-param-status' )->inLanguage( $lang )->text()
			)
			. '</tr></thead>'
			. '<tbody>';

		foreach ( $data->params as $paramName => $paramObj ) {
			$description = '';
			$default = '';

			$aliases = '';
			if ( count( $paramObj->aliases ) ) {
				foreach ( $paramObj->aliases as $alias ) {
					$aliases .= Html::element( 'code', array(
						'class' => 'mw-templatedata-doc-param-alias'
					), $alias );
				}
			}

			if ( $paramObj->deprecated ) {
				$status = 'templatedata-doc-param-status-deprecated';
			} elseif ( $paramObj->required ) {
				$status = 'templatedata-doc-param-status-required';
			} elseif ( $paramObj->suggested ) {
				$status = 'templatedata-doc-param-status-suggested';
			} else {
				$status = 'templatedata-doc-param-status-optional';
			}

			$html .= '<tr>'
			// Label
			. Html::element( 'th', array(),
				$paramObj->label !== null ?
					$paramObj->label :
					ucfirst( $paramName )
			)
			// Parameters and aliases
			. Html::rawElement( 'td', array( 'class' => 'mw-templatedata-doc-param-name' ),
				Html::element( 'code', array(), $paramName ) . $aliases
			)
			// Description
			. Html::element( 'td', array(
					'class' => array(
						'mw-templatedata-doc-muted' => (
							$paramObj->description === null && $paramObj->deprecated === false
						)
					)
				),
				$paramObj->description !== null ?
					$paramObj->description :
					wfMessage( 'templatedata-doc-param-desc-empty' )->inLanguage( $lang )->text()
				)
			// Type
			. Html::rawElement( 'td', array(
					'class' => array(
						'mw-templatedata-doc-param-type',
						'mw-templatedata-doc-muted' => $paramObj->type === 'unknown'
					)
				),
				Html::element( 'code', array(), $paramObj->type )
			)
			// Default
			. Html::element( 'td', array(
					'class' => array(
						'mw-templatedata-doc-muted' => $paramObj->default === ''
					)
				),
				$paramObj->default !== '' ?
					$paramObj->default :
					wfMessage( 'templatedata-doc-param-default-empty' )->inLanguage( $lang )->text()
			)
			// Status
			. Html::element( 'td', array(), wfMessage( $status )->inLanguage( $lang )->text() )
			. '</tr>';
		}
		$html .= '</tbody></table>'
			. Html::closeElement( 'div' );

		return $html;
	}

	private function __construct( $data = null ) {
		$this->data = $data;
	}

}
