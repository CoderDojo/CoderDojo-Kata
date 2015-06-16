<?php

class GoogleAnalyticsHooks {
	/**
	 * @param Skin $skin
	 * @param string $text
	 * @return bool
	 */
	public static function onSkinAfterBottomScripts( Skin $skin, &$text = '' ) {
		global $wgGoogleAnalyticsAccount, $wgGoogleAnalyticsAnonymizeIP, $wgGoogleAnalyticsOtherCode,
			   $wgGoogleAnalyticsIgnoreNsIDs, $wgGoogleAnalyticsIgnorePages, $wgGoogleAnalyticsIgnoreSpecials;

		if ( $skin->getUser()->isAllowed( 'noanalytics' ) ) {
			$text .= "<!-- Web analytics code inclusion is disabled for this user. -->\r\n";
			return true;
		}

		if ( count( array_filter( $wgGoogleAnalyticsIgnoreSpecials, function ( $v ) use ( $skin ) {
				return $skin->getTitle()->isSpecial( $v );
			} ) ) > 0
			|| in_array( $skin->getTitle()->getNamespace(), $wgGoogleAnalyticsIgnoreNsIDs, true )
			|| in_array( $skin->getTitle()->getPrefixedText(), $wgGoogleAnalyticsIgnorePages, true ) ) {
			$text .= "<!-- Web analytics code inclusion is disabled for this page. -->\r\n";
			return true;
		}

		$appended = false;

		if ( $wgGoogleAnalyticsAccount !== '' ) {
			$text .= <<<EOD
<script>
	 var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', "{$wgGoogleAnalyticsAccount}"]);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	$(document).ready(function(){
		$('.external').click(function(){
			var action = $(this).attr('href');
			_gaq.push(['_trackEvent', 'external_link', action]);
		});

		$('.internal').click(function(){
			var action = $(this).attr('href');
			_gaq.push(['_trackEvent', 'internal_link', action]);
		});
	});

</script>

EOD;
			$appended = true;
		}

		if ( $wgGoogleAnalyticsOtherCode !== '' ) {
			$text .= $wgGoogleAnalyticsOtherCode . "\r\n";
			$appended = true;
		}

		if ( !$appended ) {
			$text .= "<!-- No web analytics configured. -->\r\n";
		}

		return true;
	}

	public static function onUnitTestsList( array &$files ) {
		// @codeCoverageIgnoreStart
		$directoryIterator = new RecursiveDirectoryIterator( __DIR__ . '/tests/' );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		$ourFiles = array();
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$ourFiles[] = $fileInfo->getPathname();
			}
		}

		$files = array_merge( $files, $ourFiles );
		return true;
		// @codeCoverageIgnoreEnd
	}
}
