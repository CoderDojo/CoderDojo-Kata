<?php
/**
 * txt2cdb: Converts the text file of ISO codes to a constant database.
 *
 * Usage: php txt2cdb.php
 */

$dir = __DIR__;
$IP = "$dir/../..";
if ( file_exists( "$dir/../../CorePath.php" ) ) {
	include "$dir/../../CorePath.php"; // Allow override
}

require_once "$IP/maintenance/commandLine.inc";

$names = "$dir/names.cdb";
$codes = "$dir/codes.cdb";
$fr = fopen( "$dir/codes.txt", 'r' );

try {
	$names = CdbWriter::open( $names );
	$codes = CdbWriter::open( $codes );

	while ( $line = fgets( $fr ) ) {
		// Format is code1 code2 "language name"
		$line = explode( ' ', $line, 3 );
		$iso1 = trim( $line[0] );
		$iso3 = trim( $line[1] );
		// Strip quotes
		$name = substr( trim( $line[2] ), 1, -1 );
		if ( $iso1 !== '-' ) {
			$codes->set( $iso1, $iso1 );
			if ( $iso3 !== '-' ) {
				$codes->set( $iso3, $iso1 );
			}
			$names->set( $iso1, $name );
			$names->set( $iso3, $name );
		} elseif ( $iso3 !== '-' ) {
			$codes->set( $iso3, $iso3 );
			$names->set( $iso3, $name );
		}
	}
} catch ( CdbException $e ) {
	throw new MWException( $e->getMessage() );
}

fclose( $fr );
