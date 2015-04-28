<?php

/*********************************************************************
**
** This file is part of the W4G Rating Bar extension for MediaWiki
** Copyright (C)2010
**                - David Dernoncourt <www.patheticcockroach.com>
**                - Franck Dernoncourt <www.francky.me>
**
** Home Page: http://www.wiki4games.com/Wiki4Games:W4G Rating Bar
** Version: 2.1.0
**
** This program is licensed under the Creative Commons
** Attribution-Noncommercial-No Derivative Works 3.0 Unported license
** <http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode>
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
**
*********************************************************************/

/**
* Edit those accordingly to your previous Rating Bar v1.1 settings
**/
$W4GRBmigrationDBhost					= 'localhost';	// Copy value from $ratingbar_dbhost
$W4GRBmigrationDBuser					= 'testwiki';	// Copy value from $ratingbar_dbuser
$W4GRBmigrationDBpass					= '';		// Copy value from $ratingbar_dbpass
$W4GRBmigrationDBname					= 'testwiki'; // Copy value from $ratingbar_dbname
$W4GRBmigrationTablePrefix				= 'wg_';		// Copy value from $table_prefix
$W4GRBmigrationTableBase				= 'ratingbar';	// Copy value from $ratingbar_tablename
$remove_anonymous_votes                 = true;			// Whether or not you want to remove anonymous votes if you set this to false, you'll want to make sure the primary key of the w4grb_votes table is (uid,pid,ip)
$W4GRBmigrationFollow					= 100;			// Every $W4GRBmigrationFollow lines inserted you'll get a progress-o-meter message

$W4GRBmigrationTableName=$W4GRBmigrationTablePrefix.$W4GRBmigrationTableBase;
$wgSpecialPages['W4GRBMigrate'] = 'W4GRBMigrate';
class W4GRBMigrate extends UnlistedSpecialPage
{
	function __construct()
	{
		parent::__construct( 'W4GRBMigrate');
	}
 
	function execute( $par )
	{
		global $wgRequest, $wgOut, $wgUser, $wgDBprefix;
		global $W4GRBmigrationDBhost, $W4GRBmigrationDBuser, $W4GRBmigrationDBpass, $W4GRBmigrationDBname, $W4GRBmigrationTableName, $remove_anonymous_votes, $W4GRBmigrationFollow;
		error_reporting(E_ALL); 
		ini_set("display_errors", 'on');
		
		if( wfReadOnly() )
		{
			$wgOut->readOnlyPage();
			return;
		}

		$this->skin =& $wgUser->getSkin(); # that's useful for creating links more easily
		$this->setHeaders(); # not sure what that's for
		$wgOut->disable(); # for raw output
		header( 'Pragma: nocache' ); # no caching
		
		$db_old = mysql_connect($W4GRBmigrationDBhost, $W4GRBmigrationDBuser, $W4GRBmigrationDBpass) or die("Failed to connected to the database");
		$query='SELECT * FROM `'.$W4GRBmigrationTableName.'`;';
		$db_old_result = mysql_query($query) or die('Failed to select table '.$W4GRBmigrationTableName.' (query: '.$query.mysql_error());
		$page_obj=new W4GRBPage();
 		
		$dbmaster = wfGetDB( DB_MASTER ); # exceptionnally we'll run all on master
		$dbmaster->ignoreErrors('off'); # we need this so that failed queries return false
		
		echo 'Database connection successful, starting vote import...<br/>';
		$i=0;
		while($db_old_row=mysql_fetch_array($db_old_result))
			{
			$i++; # line counter
			# Read a line
			$uid=$db_old_row['user_id'];
			$vote_sent=$db_old_row['rating'];
			
			if(!$page_obj->setFullPageName($db_old_row['page_id'])) # Check if the page name is valid
				{
				echo 'Error: no page with textual ID '.$db_old_row['page_id'].' => skipping line '.$i.'.<br/>';
				continue;
				}
			$pid=$page_obj->getPID();
			$time=$db_old_row['time'];
			$ip=$db_old_row['ip'];
			
			# Check if the vote wasn't anonymous
			if($uid==0 && $remove_anonymous_votes)
				{
				echo 'Anonymous vote on page '.$pid.' skipped.<br/>';
				continue;
				}

			if(!$dbmaster->insert('w4grb_votes',
			array(	'uid' => $uid,
					'pid' => $pid,
					'vote' => $vote_sent,
					'ip' => $ip,
					'time' => $time),
			__METHOD__ ))
				echo 'Error: couldn&#39;t insert line '.$i.' (this is probably multiple anonymous votes on a same page from a same IP)<br/>';
			#else echo $dbmaster->lastQuery().'=>OK<br/>';
			if(round($i/$W4GRBmigrationFollow)*$W4GRBmigrationFollow==$i) echo $i.' lines processed.<br/>';
			}
		
		echo 'All votes imported (a total of '.$i.' lines were read). Generating average ratings table...<br/>';

		$result=$dbmaster->select(
				$wgDBprefix.'w4grb_votes AS w4grb_votes, '.$wgDBprefix.'page AS page',
				'AVG(w4grb_votes.vote) AS avg, COUNT(*) AS n, page.page_id AS pid',
				'w4grb_votes.pid=page.page_id',
				__METHOD__,
				array('GROUP BY' => 'page.page_id')
				);
		#echo $dbmaster->lastQuery().'=>OK?<br/>';
		$i=0;
		while($row = $dbmaster->fetchObject($result))
			{
			$i++;
			if(!$dbmaster->insert('w4grb_avg',
			array(	'pid' => $row->pid,
					'avg' => $row->avg,
					'n' => $row->n),
			__METHOD__ ))
				echo 'Error: couldn&#39;t insert average rating for page '.$row->pid.'<br/>';
			if(round($i/$W4GRBmigrationFollow)*$W4GRBmigrationFollow==$i) echo $i.' pages processed.<br/>';
			}
		echo 'All average ratings have been imported (a total of '.$i.' pages were treated).<br/>';
		echo 'That&#39;s it, all done!';
	}
}