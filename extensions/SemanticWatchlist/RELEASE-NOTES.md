# Semantic Watchlist

## 1.0.0 (2014-01-31)

### New features

* Semantic Watchlist is now installable via Composer
* Added support with Semantic MediaWiki 2.x
* Added support with MediaWiki 1.22, 1.23 and 1.24
* Added support with PHP 5.5, PHP 5.6 and HHVM

### Bug fixes

* #5 Fixed call to a member function getCount() on a non-object
* #10 Migrated depreciated wfMsg* functions to wfMessage()
* #11 Fixed undefined variable `egSWLEnableSelfNotify`
* #11 Fixed uncaught ReferenceError `wgScriptPath` is not defined

### Internal enhancements

* #12 Enabled unit testing

## 0.2.2 (2013-12-10)

* Fix for Special:AdminLinks when using SMW 1.9+.

## Version 0.2.1 (2013-09-26)

* 'swladmins' group removed.

## Version 0.2 (2012-11-15)

* Special:WatchlistConditions UI improved.
* Custom text can be sent in emails.
* Custom text can be set using Special:WatchlistConditions.
* No email sent to a page's own editor, by default.
* Fixed deleting of groups, which was not working.

## Version 0.1 (2011-07-30)

Initial release with these features:

* Special:SemanticWatchlist showing changes to properties watched by the user.
* Per-user optional email notification per edit that changes properties.  
* Integration with user preferences to allow users to specify which watchlist
  groups they want to follow, and if they want to receive emails on changes.
* Special:WatchlistConditions as administration interface for watchlist groups.
* API module to query property changes grouped by edit for a single user.
* API modules to add, modify and delete the watchlist groups.
