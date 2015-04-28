( function ( $, mw ) {
	/**
	 * TemplateDataGenerator generates the JSON string for templatedata
	 * or reads existing templatedata string and allows for it to be edited
	 * with a visual modal GUI.
	 *
	 * @author Moriel Schottlender
	 */
	'use strict';
	mw.libs.templateDataGenerator = ( function () {
		var globalSettings,
			/**
			 * Unique counter for new param names so they can
			 * be attached a unique and temporary paramId in the
			 * model before the name field is filled.
			 * @property {number}
			 */
			newParamIdCounter = 0,
			/**
			 * Cache and store the original templatedata json string
			 * in an object. Particularly useful to preserve any types
			 * or attributes that the editor might not handle yet.
			 * @property {Object}
			 */
			jsonOriginalObject,
			/**
			 * Full name of the current page, including subpages
			 * @property {string}
			 */
			fullPageName,
			/**
			 * The current page is sublevel; this is used when
			 * fetching the template code. If there is no template
			 * code in the current page, and the page is sub-level
			 * then the system will look for template code at the
			 * parent page.
			 * @property {boolean}
			 */
			isPageSubLevel,
			/**
			 * The cached jQuery promise to search for template source code
			 * from the API.
			 * @property {jQuery.Promise}
			 */
			templateCodePromise,
			/**
			 * The original wikitext that is in the edit page textbox.
			 * @property {string}
			 */
			originalTemplateDataWikitext,
			/**
			 * The parameters should be sorted. This is automatically
			 * set to true if the given templatedata json string already
			 * has a paramOrder set, and it turns true if the user
			 * actively changes the order of parameters in the table.
			 * Otherwise, paramOrder is not outputted
			 * @property {boolean}
			 */
			isSorted = false,
			/**
			 * The templatedata parameters model
			 * @property {Object}
			 */
			paramsDataModel,
			/**
			 * The templatedata meta model
			 * @property {Object}
			 */
			templateDataMetaModel,
			/**
			 * An array of the parameter names that are found
			 * in the template code
			 * @property {string[]}
			 */
			templateParamNames = [],
			/**
			 * A reference to the textarea in the template edit
			 * page
			 * @property {jQuery}
			 */
			$editTextArea,
			// Dialog DOM Elements
			/**
			 * An error label in the edit dialog
			 * @property {jQuery}
			 */
			$errorModalBox,
			/**
			 * The DOM element holding the editor dialog
			 * @property {jQuery}
			 */
			$modalBox,
			/**
			 * Define elements to attach to the main template edit
			 * area.
			 * @property {Object}
			 */
			editAreaElements = {
				/**
				 * Define the edit button for the main template edit
				 * area that will trigger the editor window.
				 * @property {jQuery}
				 */
				$editButton: $( '<button>' )
					.button()
					.addClass( 'tdg-editscreen-main-button' )
					.text( mw.msg( 'templatedata-editbutton' ) ),
				/**
				 * Define the error box for the main template edit
				 * area.
				 * @property {jQuery}
				 */
				$errorBox: $( '<div>' )
					.addClass( 'tdg-editscreen-error-msg' )
					.hide()
			},
			/**
			 * Detailing the available editable
			 * attributes for parameters and their DOM elements.
			 * @property {Object}
			 */
			editableAttributes = {},
			/**
			 * General settings for the templatedata Editor singleton
			 * @property {Object}
			 */
			defaultSettings = {
				/**
				 * Use an editor GUI. False would mean only the model
				 * is used without the GUI. This is mainly used for
				 * unit tests.
				 * @property {boolean}
				 */
				useGUI: true,
				/**
				 * By default, run the test of whether the page we are in
				 * is a subpage, and if so, retrieve template code from the
				 * original template. Making this false is mainly used for
				 * unit tests.
				 * @property {boolean}
				 */
				fetchCodeFromSource: true
			},
			/**
			 * A definition of deprecated parameter types and their
			 * current type equivalents.
			 * @property {Object}
			 */
			mapDeprecatedParamType = {
				'string/wiki-page-name': 'wiki-page-name',
				'string/wiki-file-name': 'wiki-file-name',
				'string/wiki-user-name': 'wiki-user-name'
			},
			/**
			 * A full list of valid parameter types to include
			 * in the type select box
			 * @property {Array}
			 */
			paramTypes = [
				'undefined',
				'string',
				'number',
				'boolean',
				'date',
				'wiki-page-name',
				'wiki-user-name',
				'wiki-file-name',
				'content',
				'unbalanced-wikitext',
				'line'
			],

		/* Model functions */

		/**
		 * Load the current templatedata from the tags in the page
		 * if they exist. If not, we will create a new templatedata blob.
		 *
		 * @param {string} wikitext Current page wikitext
		 * @returns {jQuery.Promise} Promise that is resolved when
		 *  the full information about templatedata, including
		 *  information about the template code is complete.
		 *  Rejected if the json string could not be parsed.
		 */
		modelLoadTemplateDataJson = function ( wikitext ) {
			var parts,
				deferred = $.Deferred();

			// Check if there are templateData tags in the current page
			wikitext = wikitext || originalTemplateDataWikitext;
			parts = wikitext.match(
				/<templatedata>([\s\S]*?)<\/templatedata>/i
			);
			templateDataMetaModel = {};
			paramsDataModel = {};

			// Check if <templatedata> exists
			if ( parts && parts[1] && $.trim( parts[1] ).length > 0 ) {
				// Parse the json string
				try {
					// Store the original
					jsonOriginalObject = $.parseJSON( $.trim( parts[1] ) );
				} catch ( err ) {
					// Bad syntax for JSON
					deferred.reject();
				}
				// Mark parameters that are in the template
				modelGetParametersFromTemplateCode().done( function ( params ) {
					templateParamNames = params;
					// Create the parameters model
					modelCleanAddMultipleParameters( jsonOriginalObject, templateParamNames );
					// Save the meta details of the templatedata
					if ( jsonOriginalObject.description ) {
						templateDataMetaModel.description = jsonOriginalObject.description;
					}
					if ( jsonOriginalObject.paramOrder ) {
						templateDataMetaModel.paramOrder = jsonOriginalObject.paramOrder;
						isSorted = true;
					} else {
						isSorted = false;
					}
					deferred.resolve( paramsDataModel );
				} );
			} else {
				deferred.resolve( {} );
			}

			return deferred.promise();
		},

		/**
		 * Add a new parameter to the model.
		 *
		 * @param {string} paramName Parameter name or id
		 * @param {Object} paramObj Parameter details object
		 * @param {string[]} [templateParams] Parameters that exist in the template code.
		 * @param {boolean} [doNotEmitEvent] Do not fire an event. This is useful when we
		 *  need to silently add parameters during initialization, like in the case
		 *  of adding parameters that appear in paramOrder but not in the templatedata
		 *  json string itself.
		 * @fires tdgEventModelAddParameter
		 */
		modelAddParam = function ( paramName, paramObj, templateParams, doNotEmitEvent ) {
			paramObj = paramObj || {};

			paramsDataModel[paramName] = paramObj;
			templateParams = templateParams || templateParamNames;

			if ( paramsDataModel[paramName].name === undefined ) {
				paramsDataModel[paramName].name = paramName;
			}

			if ( templateParams && $.inArray( paramName, templateParams ) !== -1 ) {
				paramsDataModel[paramName].inTemplate = true;
			}

			if ( !doNotEmitEvent ) {
				// Trigger add param event
				$( document ).trigger( 'tdgEventModelAddParameter', [ paramName, paramObj ] );
			}
		},

		/**
		 * Delete a parameter from the model
		 *
		 * @param {string} paramId Parameter name or id
		 * @fires tdgEventModelDeleteParameter
		 */
		modelDeleteParam = function ( paramId ) {
			if ( paramsDataModel[paramId] ) {
				delete paramsDataModel[paramId];
				// Trigger delete param event
				$( document ).trigger( 'tdgEventModelDeleteParameter', [ paramId ] );
			}
		},

		/**
		 * Clean up the original parameters that came from
		 * the json string and insert the parameters to the model.
		 * Trim parameters if necessary and collect the parameters
		 * that can be edited into the parameter model.
		 *
		 * @param {Object} [jsonObj] Object from the original templatedata json string
		 * @param {string[]} [templateParameters] Parameters that exist in the template code.
		 */
		modelCleanAddMultipleParameters = function ( jsonObj, templateParameters ) {
			var trimmedParam, paramId;

			jsonObj = $.isPlainObject( jsonObj ) ? $.extend( {}, jsonObj ) : {};

			// Check if there are parameters
			if ( jsonObj.params ) {
				for ( paramId in jsonObj.params ) {
					// Trim in the original
					trimmedParam = $.trim( paramId );
					if ( trimmedParam !== paramId ) {
						// Add first
						jsonObj.params[trimmedParam] = jsonObj.params[paramId];
						// Delete the old param
						delete jsonObj.params[paramId];
						paramId = trimmedParam;
					}

					// Add parameter to model
					modelAddParam( paramId, jsonObj.params[paramId], templateParameters );
				}
			}
		},

		/**
		 * Update the parameter attribute value.
		 *
		 * @param {string} paramId Parameter id or name
		 * @param {string} attr Attribute name
		 * @param {string|boolean|Array} to New value, depending
		 *  on the attribute
		 */
		modelUpdateParamAttribute = function ( paramId, attr, to ) {
			var from;

			// Sanity check: If either paramId or attr are undefined
			// drop out of the method
			if ( !paramId || !attr ) {
				return;
			}

			// If the parameter doesn't exist but there is a new
			// value, create it
			if ( paramsDataModel[paramId] === undefined && to ) {
				paramsDataModel[paramId] = {};
			}

			// Check the original value
			from = paramsDataModel[paramId][attr];

			if ( !to ) {
				// Remove the attribute
				delete paramsDataModel[paramId][attr];
				return;
			}

			// Only update if the value is changed
			// Stringify first, to make sure we compare objects
			// and arrays as well
			if ( JSON.stringify( from ) !== JSON.stringify( to ) ) {
				paramsDataModel[paramId][attr] = to;
			}
		},

		/**
		 * Clean the parameter model from internal tags so it's ready to be stringified
		 *
		 * @returns {Object} Cleaned up parameter object
		 */
		modelGetCleanModelForOutput = function () {
			var paramId,
				paramOutput = $.extend( true, {}, paramsDataModel );

			for ( paramId in paramOutput ) {
				// Delete temporary and internal attributes
				delete paramOutput[paramId].name;
				delete paramOutput[paramId].inTemplate;
				if ( paramOutput[paramId].type === 'undefined' ) {
					delete paramOutput[paramId].type;
				}
			}

			return paramOutput;
		},

		/**
		 * Make sure all parameter names are sync with their keys
		 */
		modelSyncParamNames = function () {
			var paramId;
			// Make sure 'name' is the same as the parameter key
			for ( paramId in paramsDataModel ) {
				if ( paramsDataModel[paramId].name && paramId !== paramsDataModel[paramId].name ) {
					paramsDataModel[paramsDataModel[paramId].name] = paramsDataModel[paramId];
					delete paramsDataModel[paramId];
				}
			}
		},

		/**
		 * Output the model and general data back to a json string.
		 *
		 * @returns {string} TemplateData json string
		 */
		modelOutputJsonString = function () {
			var outputJson;

			// Copy the original over to the output
			outputJson = $.extend( true, {}, jsonOriginalObject );

			if ( globalSettings.useGUI ) {
				// Make sure the parameter names are synchronized with their names
				viewSyncParamNameValues();
			}

			// Make sure param names are synchronized
			modelSyncParamNames();

			// Copy over general data
			if ( !isSorted ) {
				delete templateDataMetaModel.paramOrder;
			}
			$.extend( outputJson, templateDataMetaModel );

			// Replace parameters
			outputJson.params = modelGetCleanModelForOutput( paramsDataModel );

			return JSON.stringify( outputJson, null, '\t');
		},

		/* GUI functions */

		/**
		 * Respond to model import parameters.
		 *
		 * @param {jQuery.Event} event jQuery event
		 * @param {string[]} collectedParams Array of parameter names that were
		 *  added to the model
		 * @param {string[]} uncollectedParams Array of parameter names that were
		 *  ignored or skipped
		 */
		onModelImportParams = function ( event, collectedParams, uncollectedParams ) {
			var errorMessage = [];
			if ( collectedParams.length + uncollectedParams.length === 0 ) {
				errorMessage.push( mw.msg( 'templatedata-modal-errormsg-import-noparams' ) );
			} else {
				if ( collectedParams.length > 1 ) {
					errorMessage.push( mw.msg( 'templatedata-modal-notice-import-numparams', collectedParams.length ) );
				}

				if ( uncollectedParams.length > 1 ) {
					errorMessage.push( mw.msg( 'templatedata-modal-errormsg-import-paramsalreadyexist', uncollectedParams.join( ', ' ) ) );
				}
				// Reorder the table
				viewReorderParamTable();
			}
			if ( errorMessage.length ) {
				// Display error
				viewShowModalError( errorMessage.join( ' ' ) );
			}
		},

		/**
		 * Respond to adding a parameter in the model.
		 *
		 * @param {jQuery.Event} event jQuery event
		 * @param {string} paramName Parameter name or id
		 * @param {Object} [paramObj] Parameter details
		 * @param {boolean} [sortTable] Sort the table again;
		 *  show parameters that appear in the template first, then those that are new.
		 */
		onModelAddParam = function ( event, paramName, paramObj ) {
			var $element, editableAttribute, $tr,
				$tbody = $( '.tdg-parameters-table > tbody' );

			$tr = viewCreateParamDomRow( paramName, paramObj );

			// Process the attribute values
			for ( editableAttribute in editableAttributes ) {
				$element = $tr.find( '.tdg_attr_' + editableAttribute + ' ' + editableAttributes[editableAttribute].selector );
				viewProcessDOMAttrValue( $element, editableAttribute, paramObj[editableAttribute] );
			}

			$tbody.append( $tr );

			// Focus on 'name' field
			$tr.find( '.tdg_attr_name input' ).focus();
		},

		/**
		 * Respond to deleting a parameter in the model.
		 *
		 * @param {jQuery.Event} event jQuery event
		 * @param {string} paramName Parameter name or id
		 */
		onModelDeleteParam = function ( event, paramId ) {
			// Delete the DOM row from table:
			$( '#tdg_param_' + paramId ).remove();
		},

		/**
		 * Retrieve template parameters from the template code.
		 *
		 * Adapted from https://he.wikipedia.org/wiki/MediaWiki:Gadget-TemplateParamWizard.js
		 *
		 * @param {string} templateSource Source of the template.
		 * @returns {jQuery.Promise} A promise that resolves into an
		 *  array of parameters that appear in the template code
		 */
		modelExtractParametersFromTemplateCode = function ( templateSource ) {
			var matches,
			paramNames = [],
			paramExtractor = /{{3,}(.*?)[<|}]/mg;

			while ( ( matches = paramExtractor.exec( templateSource ) ) !== null ) {
				if ( $.inArray( matches[1], paramNames ) === -1 ) {
					paramNames.push( $.trim( matches[1] ) );
				}
			}

			return paramNames;
		},

		/**
		 * Retrieve parameters from the template code from source in this order:
		 *
		 * 1. Check if there's a template in the given 'wikitext' parameter. If not,
		 * 2. Check if there's a template in the current page. If not,
		 * 3. Check if the page is a subpage and go up a level to check for template code. If none found,
		 * 4. Repeat until we are in the root of the template
		 * 5. Save the name of the page where the template is taken from
		 *
		 * Cache the templateCodePromise so we don't have to do this all over again on each
		 * template code request.
		 *
		 * @param {string} [wikitext] Optional. Source of the template.
		 * @returns {jQuery.Promise} Promise resolving into template parameter array
		 */
		modelGetParametersFromTemplateCode = function ( wikitext ) {
			var parentPage, api,
				currPageContent = wikitext || originalTemplateDataWikitext;

			// TODO: Separate parameter names from aliases and attach the
			// aliases to their respective parameter names when extracting the
			// parameter list from the template code.
			if ( !templateCodePromise ) {
				if ( currPageContent ) {
					// Check first the given wikitext for template code
					templateParamNames = modelExtractParametersFromTemplateCode( currPageContent );
				}
				if ( templateParamNames.length > 0 ) {
					// Resolve with the full parameters array
					templateCodePromise = $.Deferred().resolve( templateParamNames );
				} else {
					templateCodePromise = $.Deferred();
					if ( isPageSubLevel ) {
						parentPage = fullPageName.substr( 0, fullPageName.indexOf( '/' ) );
						// Get the content of one level up
						api = new mw.Api();
						api.get( {
							action: 'query',
							prop: 'revisions',
							rvprop: 'content',
							indexpageids: '1',
							titles: parentPage
						} )
						.done( function ( resp ) {
							var pageContent = '';

							// Verify that we have a sane response from the API.
							// This is particularly important for unit tests, since the
							// requested page from the API is the Qunit module and has no content
							if (
								resp.query.pages[resp.query.pageids[0]].revisions &&
								resp.query.pages[resp.query.pageids[0]].revisions[0]
							) {
								pageContent = resp.query.pages[resp.query.pageids[0]].revisions[0]['*'];
							}
							templateParamNames = modelExtractParametersFromTemplateCode( pageContent );
							if ( templateParamNames.length === 0 ) {
								// Resolve an empty parameters array
								templateCodePromise.resolve( [] );
							} else {
								// Resolve the full parameers array
								templateCodePromise.resolve( templateParamNames );
							}
						} )
						.fail( function () {
							// Resolve an empty parameters array
							return templateCodePromise.resolve( [] );
						} );
					} else {
						// No template found. Resolve to empty array of parameters
						templateCodePromise.resolve( [] );
					}
				}
			}
			return templateCodePromise;
		},

		/**
		 * Checks the wikitext for template parameters and imports
		 * those that aren't yet in the templatedata list.
		 *
		 * @param {Array} [existingParamNames] An array of existing parameter
		 *  names to test for duplication.
		 * @return {Object} Collected and uncollected parameter names
		 */
		modelImportTemplateParams = function ( existingParamNames ) {
			var paramName, i,
				uncollectedParams = [],
				collectedParams = [];

			// Make sure parameter names are synchronized
			// with their key
			modelSyncParamNames();

			existingParamNames = existingParamNames || viewCollectParameterNamesFromEditor();

			modelGetParametersFromTemplateCode().done( function ( paramNames ) {
				for ( i = 0; i < paramNames.length; i++ ) {
					paramName = paramNames[i];
					// Make sure the parameter doesn't already exist in the model
					if ( $.inArray( paramName, existingParamNames ) < 0 ) {
						// Add to parameters model
						modelAddParam( paramName );

						// Add to param name array
						existingParamNames.push( paramName );

						// Add to collected parameter list
						collectedParams.push( paramName );
					} else {
						// Add to uncollected parameter list
						uncollectedParams.push( paramName );
					}
				}

				// Trigger import params event
				$( document ).trigger( 'tdgEventModelImportParameters', [ collectedParams, uncollectedParams ] );
			} );
		},

		/**
		 * Create the parameter table for the edit dialog.
		 *
		 * @returns {jQuery} Parameter table
		 */
		viewCreateParameterTable = function () {
			var $table, $tr, $tbody, paramId, editableAttribute, i,
				addedParams = [],
				paramOrder = isSorted && templateDataMetaModel.paramOrder;

			$table = $( '<table>' )
				.addClass( 'tdg-parameters-table' );

			// Create table header
			$tr = $( '<tr>' );
			// Add space for the reorder arrows
			$tr.append( '<th>' )
				.html( '&nbsp' )
				.addClass( 'tdg-table-head-reorder' );
			// Add the columns
			for ( editableAttribute in editableAttributes ) {
				$tr.append(
					$( '<th>' )
						.text( mw.msg( editableAttributes[editableAttribute].label ) )
						.addClass( 'tdg-table-head_' + editableAttribute )
				);
			}
			$table.append( $( '<thead>' ).append( $tr ) );

			$tbody = $( '<tbody>' );
			// If there is a 'paramOrder' attribute in templatedata code
			// add the parameters according to the order specified.
			if ( paramOrder && paramOrder.length > 0 ) {
				// This templatedata string already has paramOrder
				isSorted = true;
				// First go by the order
				for ( i = 0; i < paramOrder.length; i++ ) {
					// Check for duplicates
					if ( $.inArray( paramOrder[i], addedParams ) < 0 ) {
						// Sanity check: make sure the parameter exists
						if ( paramsDataModel[paramOrder[i]] ) {
							// Add param to table
							$tr = viewCreateParamDomRow( paramOrder[i], paramsDataModel[paramOrder[i]] );
							$tbody.append( $tr );

							// Add this param to the added ones to avoid duplicates
							addedParams.push( paramOrder[i] );
						}
					}
				}
			}

			for ( paramId in paramsDataModel ) {
				if ( $.inArray( paramId, addedParams ) < 0 ) {
					$tr = viewCreateParamDomRow( paramId, paramsDataModel[paramId] );
					$tbody.append( $tr );
					// Add this param to the added ones to avoid duplicates
					addedParams.push( paramId );
				}
			}

			$table.append( $tbody );

			$table.find( 'tbody' ).sortable( {
				update: onParamTableSort
			} );

			return $table;
		},

		/**
		 * Respond to user sorting the table
		 */
		onParamTableSort = function () {
			// User sorted manually, add a paramOrder to the templatedata
			isSorted = true;
		},

		/**
		 * Create the DOM row object of a parameter with editable
		 * fields and its values.
		 *
		 * @param {string} paramId Unique parameter id
		 * @param {Object} [pObj] Parameter attributes for a new
		 *  (previously nonexisting) parameter
		 * @returns {jQuery} Table row of parameter editable fields
		 */
		viewCreateParamDomRow = function ( paramId, pObj ) {
			var editableAttribute, i, attrType,
				$wrapper, $element, $tr;

			$tr = $( '<tr>' )
				.prop( 'id', 'tdg_param_' + paramId )
				.data( 'paramId', paramId );

			if ( $.inArray( paramId, templateParamNames ) < 0 ) {
				$tr.addClass( 'tdg-not-in-template' );
			}

			pObj = pObj || {};

			// Add the reorder arrows
			$tr.append(
				$( '<td>' )
					.addClass( 'tdg_attr_reoder' )
					// Add a reorder arrow icon
					.append(
						$( '<span>' )
							.addClass( 'ui-icon ui-icon-arrowthick-2-n-s' )
					)
			);

			for ( editableAttribute in editableAttributes ) {
				$element = editableAttributes[editableAttribute].$element.clone()
					.data( 'paramId', paramId )
					.addClass( 'param-type-input' );
				attrType = editableAttributes[editableAttribute].type;

				if ( attrType === 'checkbox' ) {
					// Checkbox
					$element.prop( 'id', 'tdg_param_' + editableAttribute + '_' + paramId );
					$wrapper = $( '<label>' )
						.attr( 'for', 'tdg_param_' + editableAttribute + '_' + paramId )
						.text( mw.msg( editableAttributes[editableAttribute].label ) )
						.prepend( $element );

				} else if ( editableAttribute === 'type' ) {
					// Build type select
					for ( i = 0; i < paramTypes.length; i++ ) {
						$element.append(
							$( '<option>' )
								.val( paramTypes[i] )
								.text( mw.msg( 'templatedata-modal-table-param-type-' + paramTypes[i] ) )
						);
					}
					$wrapper = $element;
				} else {
					$wrapper = $element;
				}

				// Fill in the value
				viewProcessDOMAttrValue( $element, editableAttribute, pObj[editableAttribute] );

				$tr.append(
					$( '<td>' )
						.addClass( 'tdg_attr_' + editableAttribute )
						.append( $wrapper )
				);
			}

			$tr.find( '.tdg-param-button-del' ).click( function () {
				var paramId = $( this ).data( 'paramId' );
				// delete from model
				modelDeleteParam( paramId );
			} );

			return $tr;
		},

		/**
		 * Set the value of the DOM element according to its type
		 * and the parameter object.
		 *
		 * @param {jQuery} $element The DOM element for the attribute
		 * @param {string} attr Attribute name
		 * @param {Object|string|undefined} rawValue The value in the original
		 *  json string
		 */
		viewProcessDOMAttrValue = function ( $element, attr, rawValue ) {
			// Only update value if there is an original value
			// to update from
			if ( rawValue === undefined ) {
				return;
			}

			// Only update the value if the original one
			// is not an object
			if ( !$.isPlainObject( rawValue ) ) {
				switch ( attr ) {
					case 'aliases':
						if ( rawValue.length > 0 ) {
							$element.val( rawValue.join( ', ' ) );
						}
						break;
					case 'type':
						// Select box
						// Check if the given type is a deprecated type
						// and if it is, translate it to the corresponding new type
						if ( mapDeprecatedParamType[rawValue] !== undefined ) {
							rawValue = mapDeprecatedParamType[rawValue];
						}
						$element.val( rawValue );
						break;
					case 'required':
					case 'suggested':
						// For checkboxes, set 'true' and 'on' to true
						// and all else to false
						rawValue = ( rawValue === 'on' || rawValue === true );
						// Checkbox
						$element.prop( 'checked', !!rawValue );
						break;
					// All other attributes that appear inside a regular input
					// or a textbox and do not require special treatment.
					// For example: 'name', 'label', 'description' and 'default'
					default:
						$element.val( rawValue );
						break;
				}
			} else {
				// The attribute is an object. Tag it and disable
				$element
					.data( 'tdg_uneditable', true )
					.prop( 'disabled', true )
					.val( mw.msg( 'templatedata-modal-table-param-uneditablefield' ) );
			}
		},

		/**
		 * Define the editor modal buttons. This is done in a separate
		 * method so the buttons could be internationalized.
		 *
		 * @param {string} applyCaption Caption for the apply button
		 * @param {string} cancelCaption Caption for the cancel button
		 * @returns {jQuery} Buttons
		 */
		viewI18nEditorButtons = function ( applyCaption, cancelCaption ) {
			var buttons = {};

			buttons[applyCaption] = function () {
				var tdOutput, finalOutput, parts;

				if ( globalSettings.useGUI && viewIsEditorFormValid() ) {
					// Update the model
					viewUpdateParamsModelFromEditor();
					tdOutput = modelOutputJsonString();

					parts = originalTemplateDataWikitext.match(
							/<templatedata>([\s\S]*?)<\/templatedata>/i
						);

					if ( parts && parts[1] ) {
						// <templatedata> exists. Replace it
						finalOutput = originalTemplateDataWikitext.replace(
							/(<templatedata>)([\s\S]*?)(<\/templatedata>)/i,
							'<templatedata>\n' + tdOutput + '\n</templatedata>'
						);
					} else {
						// Add the <templatedata>
						finalOutput = originalTemplateDataWikitext + '\n\n<templatedata>\n' +
							tdOutput +
							'\n</templatedata>\n';
					}

					$modalBox.trigger( 'TemplateDataGeneratorDone', [ finalOutput ] );
					$modalBox.dialog( 'close' );

					// Clean up
					destroyAllParameters();

					return finalOutput;
				}
			};

			buttons[cancelCaption] = function () {
				// Clean up
				destroyAllParameters();
				$modalBox.dialog( 'close' );
			};

			return buttons;
		},

		/**
		 * Make sure all parameter names are sync with their prospective
		 * input name values
		 */
		viewSyncParamNameValues = function () {
			var $paramRows = $modalBox.find( '.tdg-parameters-table > tbody > tr' );

			$paramRows.each( function ( i, row ) {
				var $tr = $( row ),
					paramId = $( row ).data( 'paramId' ),
					pname = $tr.find( '.tdg_attr_name input' ).val();

				if ( paramsDataModel[paramId] ) {
					paramsDataModel[paramId].name = pname;
				}
			} );
		},

		/**
		 * Go over the editor parameter table and collect the
		 * parameter name, collect them into an array.
		 *
		 * @returns {Array} Parameter names
		 */
		viewCollectParameterNamesFromEditor = function () {
			var nameArray = [],
				$paramRows = $modalBox.find( '.tdg-parameters-table > tbody > tr' );

			$paramRows.each( function ( i, row ) {
				var $tr = $( row ),
					pname = $tr.find( '.tdg_attr_name input' ).val();

				if ( $.trim( pname ) && $.inArray( pname, nameArray ) < 0 ) {
					nameArray.push( $.trim( pname ) );
				}
			} );

			return nameArray;
		},

		/**
		 * Reorder the parameter table according to the parameter data model
		 */
		viewReorderParamTable = function () {
			var $element, i, paramId, editableAttribute, paramName, $tr,
				paramKeys = {
					inTemplate: [],
					added: []
				},
				$tbody = $( '.tdg-parameters-table > tbody' ),
				sortFunc = function ( a, b ) {
					if ( paramsDataModel[a].name < paramsDataModel[b].name ) {
						return -1;
					} else if ( paramsDataModel[a].name > paramsDataModel[b].name ) {
						return 1;
					} else {
						return 0;
					}
				};

			// Store param keys in a arrays so it can be sorted
			for ( paramId in paramsDataModel ) {
				if ( paramsDataModel[paramId].inTemplate ) {
					paramKeys.inTemplate.push( paramId );
				} else {
					paramKeys.added.push( paramId );
				}
			}
			// Sort arrays by name
			paramKeys.inTemplate.sort( sortFunc );
			paramKeys.added.sort( sortFunc );

			// Redo the table
			$tbody.empty();
			for ( i = 0; i < paramKeys.inTemplate.length; i++ ) {
				paramName = paramKeys.inTemplate[i];
				$tr = viewCreateParamDomRow( paramName, paramsDataModel[paramName] );
				// Process the attribute values
				for ( editableAttribute in editableAttributes ) {
					$element = $tr.find( '.tdg_attr_' + editableAttribute + ' ' + editableAttributes[editableAttribute].selector );
					viewProcessDOMAttrValue( $element, editableAttribute, paramsDataModel[paramName][editableAttribute] );
				}

				$tbody
					.append( $tr );
			}

			for ( i = 0; i < paramKeys.added.length; i++ ) {
				paramName = paramKeys.added[i];
				$tr = viewCreateParamDomRow( paramName, paramsDataModel[paramName] );
				$tr.addClass( 'tdg-not-in-template' );
				// Process the attribute values
				for ( editableAttribute in editableAttributes ) {
					$element = $tr.find( '.tdg_attr_' + editableAttribute + ' ' + editableAttributes[editableAttribute].selector );
					viewProcessDOMAttrValue( $element, editableAttribute, paramsDataModel[paramName][editableAttribute] );
				}

				$tbody
					.append( $tr );
			}
		},

		/**
		 * Display an error message in the editor window.
		 *
		 * @param {string} msg Message code to display
		 */
		viewShowModalError = function ( msg ) {
			$errorModalBox
				.text( msg )
				.show();
		},

		/**
		 * Validate the editor fields before processing the form.
		 *
		 * @returns {boolean} Form is valid
		 */
		viewIsEditorFormValid = function () {
			var isValid = true,
				$paramRows = $modalBox.find( '.tdg-parameters-table > tbody > tr' );

			// Reset errors
			$( '.tdgerror' ).removeClass( 'tdgerror' );
			$errorModalBox.empty().hide();

			// Make sure param names are synchronized
			modelSyncParamNames();

			// Go over the editor table elements.
			// Look for:
			// - Empty name fields
			// - Duplicate name fields
			// - Illegal characters in name fields: pipe, equal, }}
			$paramRows.each( function ( i, row ) {
				var paramName,
					paramNameArray = [],
					$tr = $( row ),
					$nameField = $tr.find( '.tdg_attr_name input' );

				paramName = $.trim( $nameField.val() );

				// Check that not empty
				if ( paramName === '' ) {
					$nameField.addClass( 'tdgerror' );
					isValid = false;
					return true; // Next iteration
				}

				// Check for duplicates
				if ( $.inArray( paramName, paramNameArray ) > -1 ) {
					// Duplicate!
					$nameField.addClass( 'tdgerror' );
					isValid = false;
					return true; // Next iteration
				}
				// Add to param name array
				paramNameArray.push( paramName );

				// Check for illegal characters
				if ( paramName.match( /[\|=]|}}/ ) ) {
					$nameField.addClass( 'tdgerror' );
					isValid = false;
					return true; // Next iteration
				}
			} );

			if ( !isValid ) {
				viewShowModalError( mw.msg( 'templatedata-modal-errormsg', '|', '=', '}}' ) );
			}

			return isValid;
		},

		/**
		 * Build the editor window.
		 *
		 * @fires TemplateDataGeneratorDone
		 * @returns {jQuery} Editor window
		 */
		viewBuildEditWindow = function () {
			var $editor = $( '<div>' )
				.append( $( '<h3>' )
					.addClass( 'tdg-title' )
					.text( mw.msg( 'templatedata-modal-title-templatedesc' ) )
				)
				// Main description
				.append(
					$( '<textarea>' )
						.addClass( 'tdg-template-description' )
				)
				// Main error box
				.append( $errorModalBox
					.addClass( 'errorbox' )
					.hide()
				)
				.append( $( '<h3>' )
					.addClass( 'tdg-title' )
					.text( mw.msg( 'templatedata-modal-title-templateparams' ) )
				)
				// Import parameters button
				.append(
					$( '<button>' )
						.text( mw.msg( 'templatedata-modal-button-importParams' ) )
						.button()
						.addClass( 'tdg-addparam' )
						.click( function () {
							// Reset error message
							$errorModalBox.empty().hide();

							// Sync the parameter values with their view names
							// in case the user added parameters manually
							viewSyncParamNameValues();

							// Import parameters from the template code
							modelImportTemplateParams( viewCollectParameterNamesFromEditor() );
						} ) )
				// Parameters table
				.append( viewCreateParameterTable() )
				// Add new parameter button
				.append(
					$( '<button>' )
						.text( mw.msg( 'templatedata-modal-button-addparam' ) )
						.button()
						.addClass( 'tdg-addparam' )
						.click( function () {
							var newId = 'tdg_new_' + newParamIdCounter;
								newParamIdCounter++;
								modelAddParam( newId, { name: '' } );
					} )
				);

				// Set the initial value of the description
				$editor.find( '.tdg-template-description' ).val( templateDataMetaModel.description );

			return $editor;
		},

		/**
		 * Go over the editor table and update the parameters data model accordingly.
		 */
		viewUpdateParamsModelFromEditor = function () {
			var paramOrder = [],
				$descBox = $modalBox.find( '.tdg-template-description' ),
				$paramRows = $modalBox.find( '.tdg-parameters-table > tbody > tr' );

			// Save meta details
			templateDataMetaModel.description = $descBox.val();

			// Go over the parameter table
			$paramRows.each( function ( i, row ) {
				var editableAttribute, name, paramId, value, $input,
					$tr = $( row );

				paramId = $tr.data( 'paramId' );

				// Check if param doesn't yet exists
				if ( paramsDataModel[paramId] === undefined ) {
					// Create the new param
					paramsDataModel[paramId] = {};
				} else {
					name = $tr.find( '.tdg_attr_name input' ).val();
					// Check if there's a need to change param name
					if ( paramId !== name ) {
						// Set name properly
						paramsDataModel[name] = paramsDataModel[paramId];
						delete paramsDataModel[paramId];
						// Change paramId to new one
						paramId = name;
						$tr.data( 'paramId', paramId );
					}
				}

				// Save the paramOrder
				paramOrder.push( paramId );

				// Go over attributes and cells
				for ( editableAttribute in editableAttributes ) {
					// Skip the delete button and name
					if ( editableAttribute !== 'delbutton' && editableAttribute !== 'name' ) {
						// Look at the value
						value = null;
						switch ( editableAttribute ) {
							case 'required':
							case 'suggested':
								$input = $tr.find( '.tdg_attr_' + editableAttribute + ' input' );
								value = $input.prop( 'checked' );
								break;
							case 'description':
								$input = $tr.find( '.tdg_attr_' + editableAttribute + ' textarea' );
								value = $input.val();
								break;
							case 'aliases':
								$input = $tr.find( '.tdg_attr_' + editableAttribute + ' input' );
								if ( $.trim( $input.val() ) ) {
									value = $input.val().split( ',' );
								}
								break;
							case 'type':
								$input = $tr.find( '.tdg_attr_' + editableAttribute + ' select' );
								value = $input.val();
								break;
							default:
								$input = $tr.find( '.tdg_attr_' + editableAttribute + ' input' );
								value = $input.val();
								break;
						}

						if ( !$input.data( 'tdg_uneditable') ) {
							// Only update the parameter attribute if it is editable
							modelUpdateParamAttribute( paramId, editableAttribute, value );
						}
					}
				}
			} );

			// Save param order
			templateDataMetaModel.paramOrder = paramOrder;
		},

		/**
		 * Set up all the required DOM elements for the dialog.
		 */
		viewSetupDialogDomElements = function () {
			$errorModalBox = $( '<div>' )
				.addClass( 'tdg-errorbox' );

			editableAttributes = {
				name: {
					selector: 'input',
					type: 'string',
					label: 'templatedata-modal-table-param-name',
					$element: $( '<input>' )
				},
				aliases: {
					selector: 'input',
					type: 'array',
					label: 'templatedata-modal-table-param-aliases',
					$element: $( '<input>' )
				},
				label: {
					selector: 'input',
					type: 'string',
					label: 'templatedata-modal-table-param-label',
					$element: $( '<input>' )
				},
				description: {
					selector: 'textarea',
					type: 'multiline',
					label: 'templatedata-modal-table-param-desc',
					$element: $( '<textarea>' )
				},
				type: {
					selector: 'select',
					type: 'select',
					label: 'templatedata-modal-table-param-type',
					$element: $( '<select>' )
				},
				'default': {
					selector: 'input[type="checkbox"]',
					type: 'string',
					label: 'templatedata-modal-table-param-default',
					$element: $( '<input>' )
				},
				required: {
					selector: 'input[type="checkbox"]',
					type: 'checkbox',
					label: 'templatedata-modal-table-param-required',
					$element: $( '<input type="checkbox" />' )
				},
				suggested: {
					selector: 'input[type="checkbox"]',
					type: 'checkbox',
					label: 'templatedata-modal-table-param-suggested',
					$element: $( '<input type="checkbox" />' )
				},
				delbutton: {
					selector: 'button',
					type: 'button',
					label: 'templatedata-modal-table-param-actions',
					$element: $( '<button>' )
						.text( mw.msg( 'templatedata-modal-button-delparam' ) )
						.addClass( 'tdg-param-button-del buttonRed' )
						.button()
				}
			};

		},

		/**
		 * Load the editor screen.
		 * @param {string} wikitext Wikitext string
		 * @returns {jQuery|Object} Editor jQuery object or,
		 *  if the json parsing failed, an error object.
		 */
		viewLoadEditor = function ( wikitext ) {
			// Setup the dialog elements
			viewSetupDialogDomElements();

			// Reset editor
			$modalBox.empty();
			$modalBox.hide();
			editAreaElements.$errorBox.empty().hide();

			originalTemplateDataWikitext = wikitext;

			modelLoadTemplateDataJson().then(
				function () {
					// Fill in editor data
					$modalBox.append( viewBuildEditWindow() );

					// Open the dialog
					$modalBox.dialog( 'open' );
				},
				function () {
					// Error reading json
					editAreaElements.$errorBox
						.text( mw.msg( 'templatedata-errormsg-jsonbadformat' ) )
						.show();
				}
			);
		},

		destroyAllParameters = function () {
			isSorted = false;
			templateDataMetaModel = {};
			paramsDataModel = {};
			originalTemplateDataWikitext = '';
			jsonOriginalObject = {};
			templateParamNames = [];

			$modalBox.empty();
			$modalBox.hide();
			editAreaElements.$errorBox.empty().hide();
		};

		/* Public functions */

		return {
			/**
			 * Initialize the TemplateDataGenerator singleton.
			 * Attach necessary elements to the edit page and
			 * define the main variables.
			 *
			 * @param {jQuery} [$contentArea] The element to prepend
			 *  the edit button to in the edit page.
			 * @param {jQuery} [$textarea] The textarea containing
			 *  the templatedata information, or the one where
			 *  the templatedata should be outputted to.
			 * @param {Object} [config] Optional configuration options
			 */
			init: function ( $contentArea, $textarea, config ) {
				$editTextArea = $textarea;

				// Merge settings with default settings
				config = config || {};
				globalSettings = $.extend( defaultSettings, config );

				fullPageName = mw.config.get( 'wgPageName' );
				isPageSubLevel = globalSettings.fetchCodeFromSource && ( fullPageName.indexOf( '/' ) > -1 );

				paramsDataModel = {};
				templateDataMetaModel = {};

				// If GUI is used, define it
				if ( globalSettings.useGUI && $contentArea && $textarea ) {

					// Define editable area elements
					editAreaElements = {
						/**
						 * Define the edit button for the main template edit
						 * area that will trigger the editor window.
						 * @property {jQuery}
						 */
						$editButton: $( '<button>' )
							.button()
							.addClass( 'tdg-editscreen-main-button' )
							.text( mw.msg( 'templatedata-editbutton' ) ),
						/**
						 * Define a link item that's attached next to the edit
						 * button, leading to TemplateData official documentation.
						 * @property {jQuery}
						 */
						$helpLink: $( '<a>' )
							.addClass( 'tdg-editscreen-main-helplink' )
							.text( mw.msg( 'templatedata-helplink' ) )
							.attr( 'href', 'https://www.mediawiki.org/wiki/Extension:TemplateData' )
							.attr( 'target', '_blank' ),
						/**
						 * Define the error box for the main template edit
						 * area.
						 * @property {jQuery}
						 */
						$errorBox: $( '<div>' )
							.addClass( 'tdg-editscreen-error-msg' )
							.hide()
					};

					// Define the dialog
					$modalBox = $( '<div>' )
						.addClass( 'tdg-editscreen-modal-form' )
						.attr( 'id', 'modal-box' )
						.attr( 'title', mw.msg( 'templatedata-modal-title' ) )
						.hide()
						.dialog( {
							autoOpen: false,
							height: $( window ).height() * 0.8,
							width: $( window ).width() * 0.8,
							modal: true,
							buttons: viewI18nEditorButtons(
								mw.msg( 'templatedata-modal-button-apply' ),
								mw.msg( 'templatedata-modal-button-cancel' )
							),
							close: function () {
								$modalBox.empty();
							}
						} );

					// Define edit button action
					editAreaElements.$editButton.click( function () {
						viewLoadEditor( $editTextArea.val() );
					} );

					// Attach elements to edit screen
					$contentArea
						.prepend(
							editAreaElements.$editButton,
							editAreaElements.$helpLink,
							editAreaElements.$errorBox
						);

					// Attach Event to editor window
					$modalBox.on( 'TemplateDataGeneratorDone', function ( e, output ) {
						$editTextArea.val( output );
					} );

					// Model events
					$( document )
						.on( 'tdgEventModelAddParameter', onModelAddParam )
						.on( 'tdgEventModelDeleteParameter', onModelDeleteParam )
						.on( 'tdgEventModelImportParameters', onModelImportParams );
				}
			},
			/**
			 * Expose functions for unit tests
			 * @property {Object}
			 */
			tests: {
				modelAddParam: function ( paramName, paramObj ) {
					modelAddParam( paramName, paramObj );
				},
				modelDeleteParam: function ( paramId ) {
					modelDeleteParam( paramId );
				},
				modelUpdateParamAttribute: function ( paramId, attr, to ) {
					modelUpdateParamAttribute( paramId, attr, to );
				},
				loadTemplateDataJson: function ( wikitext ) {
					return modelLoadTemplateDataJson( wikitext );
				},
				modelOutputJsonString: function () {
					return modelOutputJsonString();
				},
				getTDMeta: function () {
					return templateDataMetaModel;
				}
			}
		};
	} )();
}( jQuery, mediaWiki ) );
