Parsoid implements a bidirectional wikitext parser and interpreter. It
converts and interprets wikitext into an annotated HTML DOM, which can then be
edited with HTML editor tools such as the Visual Editor (see
http://www.mediawiki.org/wiki/VisualEditor). It also provides the conversion
of a (possibly modified) HTML DOM back to wikitext.

For more information about this project, check out the wiki:

	* http://www.mediawiki.org/wiki/Parsoid
    
If you are looking for the main Parsoid service, it can be found elsewhere.

Current development happens at
git clone https://gerrit.wikimedia.org/r/p/mediawiki/services/parsoid
or (with gerrit auth)
git clone ssh://USERNAME@gerrit.wikimedia.org:29418/mediawiki/services/parsoid

The deployment / packaging repository is at
git clone https://gerrit.wikimedia.org/r/p/mediawiki/services/parsoid/deploy
or (with gerrit auth)
git clone ssh://USERNAME@gerrit.wikimedia.org:29418/mediawiki/services/parsoid/deploy
It includes node_modules and the first repository as a submodule, so you
should do 'git submodule init; git submodule update' to fetch that too.

For more background, see https://www.mediawiki.org/wiki/Parsoid/Setup and
https://www.mediawiki.org/wiki/Parsoid/Packaging.
