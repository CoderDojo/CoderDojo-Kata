/**
 * TemplateData Generator GUI Unit Tests
 */

( function ( $, mw ) {
	'use strict';

	QUnit.module( 'ext.templateData', QUnit.newMwEnvironment() );

	var originalWikitext = 'Some text here that is not templatedata information.' +
		'<templatedata>' +
		'{' +
		'	"description": "Label unsigned comments in a conversation.",' +
		'	"params": {' +
		'		"user": {' +
		'			"label": "Username",' +
		'			"type": "wiki-user-name",' +
		'			"required": true,' +
		'			"description": "User name of person who forgot to sign their comment.",' +
		'			"aliases": ["1"]' +
		'		},' +
		'		"date": {' +
		'			"label": "Date",' +
		'			"description": {' +
		'				"en": "Timestamp of when the comment was posted, in YYYY-MM-DD format."' +
		'			},' +
		'			"aliases": ["2"],' +
		'			"suggested": true' +
		'		},' +
		'		"year": {' +
		'			"label": "Year",' +
		'			"type": "number"' +
		'		},' +
		'		"month": {' +
		'			"label": "Month",' +
		'			"inherits": "year"' +
		'		},' +
		'		"day": {' +
		'			"label": "Day",' +
		'			"inherits": "year"' +
		'		},' +
		'		"comment": {' +
		'			"required": false' +
		'		}' +
		'	},' +
		'	"sets": [' +
		'		{' +
		'			"label": "Date",' +
		'			"params": ["year", "month", "day"]' +
		'		}' +
		'	]' +
		'}' +
		'</templatedata>' +
		'Trailing text at the end.',
	finalJsonStringOnly = '{\n' +
		'	"description": "Label unsigned comments in a conversation.",\n' +
		'	"params": {\n' +
		'		"user": {\n' +
		'			"label": "New user label",\n' +
		'			"type": "wiki-user-name",\n' +
		'			"description": "User name of person who forgot to sign their comment.",\n' +
		'			"aliases": [\n' +
		'				"1"\n' +
		'			]\n' +
		'		},\n' +
		'		"date": {\n' +
		'			"label": "Date",\n' +
		'			"description": {\n' +
		'				"en": "Timestamp of when the comment was posted, in YYYY-MM-DD format."\n' +
		'			},\n' +
		'			"aliases": [\n' +
		'				"2"\n' +
		'			],\n' +
		'			"suggested": true\n' +
		'		},\n' +
		'		"year": {\n' +
		'			"label": "Year",\n' +
		'			"type": "number"\n' +
		'		},\n' +
		'		"month": {\n' +
		'			"label": "Month",\n' +
		'			"inherits": "year"\n' +
		'		},\n' +
		'		"comment": {\n' +
		'			"required": false\n' +
		'		},\n' +
		'		"someNewParameter": {\n' +
		'			"required": true\n' +
		'		}\n' +
		'	},\n' +
		'	"sets": [\n' +
		'		{\n' +
		'			"label": "Date",\n' +
		'			"params": [\n' +
		'				"year",\n' +
		'				"month",\n' +
		'				"day"\n' +
		'			]\n' +
		'		}\n' +
		'	]\n' +
		'}',
	tdManualParamsObject = {
		'user': {
			// The parameter data model adds 'name' attribute
			'name': 'user',
			'label': 'Username',
			'type': 'wiki-user-name',
			'required': true,
			'description': 'User name of person who forgot to sign their comment.',
			'aliases': ['1']
		},
		'date': {
			'name': 'date',
			'label': 'Date',
			'description': {
				'en': 'Timestamp of when the comment was posted, in YYYY-MM-DD format.'
			},
			'aliases': ['2'],
			'suggested': true
		},
		'year': {
			'name': 'year',
			'label': 'Year',
			'type': 'number'
		},
		'month': {
			'name': 'month',
			'label': 'Month',
			'inherits': 'year'
		},
		'day': {
			'name': 'day',
			'label': 'Day',
			'inherits': 'year'
		},
		'comment': {
			'name': 'comment',
			'required': false
		}
	};

	/** Parameter data model tests **/
	QUnit.asyncTest( 'TemplateData Parameter Model', 6, function ( assert ) {
		var tdgTests;

		mw.libs.templateDataGenerator.init( null, null, { 'useGUI': false, 'fetchCodeFromSource': false } );
		tdgTests = mw.libs.templateDataGenerator.tests;
		// Load data into model

		tdgTests.loadTemplateDataJson( originalWikitext ).done( function ( pmodel ) {
			// Tests
			assert.deepEqual(
				tdManualParamsObject,
				pmodel,
				'Loading parameters data model'
			);

			// Make sure description sticks
			assert.equal(
				tdgTests.getTDMeta().description,
				'Label unsigned comments in a conversation.',
				'Template description.'
			);

			// Change attributes
			tdManualParamsObject.user.label = 'New user label';
			delete tdManualParamsObject.user.required;

			tdgTests.modelUpdateParamAttribute( 'user', 'label', 'New user label' );
			tdgTests.modelUpdateParamAttribute( 'user', 'required', false );

			assert.deepEqual(
				tdManualParamsObject,
				pmodel,
				'Changing parameter attributes'
			);

			// Add parameter
			tdManualParamsObject.newparam = { 'name': 'someNewParameter', 'required': true };
			tdgTests.modelAddParam( 'newparam', { 'name': 'someNewParameter', 'required': true } );

			assert.deepEqual(
				tdManualParamsObject,
				pmodel,
				'Adding a new parameter'
			);

			// Delete a parameter
			delete tdManualParamsObject.day;
			tdgTests.modelDeleteParam( 'day' );

			assert.deepEqual(
				tdManualParamsObject,
				pmodel,
				'Deleting a parameter'
			);

			// Outputting final templatedata string
			assert.equal(
				finalJsonStringOnly,
				tdgTests.modelOutputJsonString(),
				'Outputting templatedata json string from model'
			);

		} )
		.always( function () {
			QUnit.start();
		} );

	} );

}( jQuery, mediaWiki ) );
