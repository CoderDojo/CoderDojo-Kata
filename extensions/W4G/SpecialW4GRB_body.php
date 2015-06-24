<?php

/*********************************************************************
**
** This file is part of the W4G Rating Bar extension for MediaWiki
** Copyright (C)2011
**                - David Dernoncourt <www.patheticcockroach.com>
**                - Franck Dernoncourt <www.francky.me>
**
** Home Page : http://www.wiki4games.com/Wiki4Games:W4G Rating Bar
** Version: 2.1.1
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

class W4GRB extends UnlistedSpecialPage
{
	const ANONYMOUS_UID = 0;
	private $bar_id,$page_idnum,$uid;
	
	function __construct()
	{
		parent::__construct( 'W4GRB');
	}
 
	/**
	 * That's what is executed when the W4GRB special page is loaded
	 * (basically, it deals with a vote cast by a user)
	 * @param $par parser: parser object passed by MediaWiki
	 * @see SpecialPage::execute()
	 */
	function execute( $par )
	{
		global $wgRequest, $wgOut, $wgUser;
		global $wgW4GRB_Settings;

		$this->skin =& $wgUser->getSkin(); # that's useful for creating links more easily
		$this->setHeaders(); # not sure what's that for
		$wgOut->disable(); # for raw output
		header( 'Pragma: nocache' ); # no caching

		# Get minimum parameters, then see if a vote was cast or if we just want to read
		$this->bar_id = $wgRequest->getInt('bid') or die('No bar specified');
		$this->page_idnum = $wgRequest->getInt('pid') or die('No page specified');
		$this->uid = $wgUser->getID(); # cache user ID
		$vote_sent = $wgRequest->getInt('vote') or die($this->justShowVotes());

		# Fights XSS
		if(!preg_match("/^https?:\/\/".$_SERVER["HTTP_HOST"]."\//",$_SERVER["HTTP_REFERER"]))
			die('XSS attempt detected. Please make sure your browser isn&#39;t configured to hide referer information.');
		if(wfReadOnly()) die('Wiki in read only mode: voting disabled.'); # if we want to vote (previous line passed) but we're read-only, die now
		
		$ip = $_SERVER['REMOTE_ADDR']; # cache IP
		
		# Restrict vote to legit values
		if ($vote_sent > 100) $vote_sent=100;
		else if ($vote_sent < 0) $vote_sent=0;
		
		$dbslave = wfGetDB( DB_SLAVE );
		
		# Check if the page ID is valid
		$result = $dbslave->select('page', 'page_id,page_title',
				array('page_id' => $this->page_idnum),
				__METHOD__ ,
				array('LIMIT' => 1));
		if(!($row = $dbslave->fetchObject($result))) die('No page with this ID');
		$dbslave->freeResult($result);
		
		# If not logged in (and anonymous voting not enabled)
		# Or if not allowed to vote (NB: anonymous voting overrides this!)
		if( ($this->uid==self::ANONYMOUS_UID || !$wgUser->isAllowed('w4g_rb-canvote'))
				&& !$wgW4GRB_Settings['anonymous-voting-enabled'])
			die($this->justShowVotes());
		
		$dbmaster = wfGetDB( DB_MASTER );
		$dbmaster->ignoreErrors('off'); # we need this so that failed queries return false
		# Checks in the master if the user has already voted
		# (But don't do the search if casting an anonymous vote)
		$user_already_voted=false;
		if($this->uid!=self::ANONYMOUS_UID)
		{
			$result = $dbmaster->select('w4grb_votes', 'vote',
					array(	'uid' => $this->uid,
							'pid' => $this->page_idnum),
					__METHOD__ ,
					array('LIMIT' => 1));
			if($row = $dbmaster->fetchObject($result)) $user_already_voted=true;
			$dbmaster->freeResult($result);
		}
		# Checks in the master if the IP has already voted and:
		# - grab all matching uids
		# - get most recent time this IP voted
		$result = $dbmaster->select('w4grb_votes',
				array(	'uid','time'),
				array(	'ip' => $ip,
						'pid' => $this->page_idnum),
				__METHOD__ ,
				array('ORDER BY' => 'time DESC'));
		if($row = $dbmaster->fetchObject($result))
		{
			$ip_already_voted=true;
			$most_recent_vote_from_ip=$row->time;
			$uids_who_voted=array();
			do
			{
				$uids_who_voted[]=$row->uid;
			} while($row = $dbmaster->fetchObject($result));
			$dbmaster->freeResult($result);
		}
		else $ip_already_voted=false;
		
		# If the same user already voted, we update their vote and it's over
		if($user_already_voted)
		{
			$dbmaster->update('w4grb_votes',
				array(	'vote' => $vote_sent,
						'ip' => $ip,
						'time' => time()),
				array(	'uid' => $this->uid,
						'pid' => $this->page_idnum),
				__METHOD__ ) or die('Query vote failed!');
			$vote_cast=true;
		}
		# Else if the same IP already voted anonymously, we update that vote
		else if($ip_already_voted && in_array(self::ANONYMOUS_UID,$uids_who_voted))
		{
			# If current vote is still anonymous, just update it
			# Otherwise, logged-in user will steal the anonymous vote
			# (design: anonymous vote is impossible if an identified user
			# already used the IP to vote)
			$dbmaster->update('w4grb_votes',
				array(	'vote' => $vote_sent,
						'uid' => $this->uid,
						'ip' => $ip,
						'time' => time()),
				array(	'uid' => self::ANONYMOUS_UID,
						'pid' => $this->page_idnum),
				__METHOD__ ) or die('Query vote failed!');
		}
		# Now this case is if the same IP voted as ANOTHER logged-in user:
		else if($ip_already_voted)
		{
			# 1. Maybe this guy's trying to multivote! Let's check that with timestamps
			if(time()-$most_recent_vote_from_ip < $wgW4GRB_Settings['multivote-cooldown'])
				die (wfMsg('w4g_rb-error_ip_overused'));
			# 2. Maybe an anonymous is trying to vote from the same IP as a registered
			#		user => this is not allowed
			else if($this->uid==self::ANONYMOUS_UID)
				die (wfMsg('w4g_rb-error_ip_used_by_registered'));	
		}
		# If we arrive there, the vote is new and clean (no vote from the
		# same user at all, and not from same IP recently)
		# so we can insert a new line
		else
		{
			$dbmaster->insert('w4grb_votes',
				array(	'uid' => $this->uid,
						'pid' => $this->page_idnum,
						'vote' => $vote_sent,
						'ip' => $ip,
						'time' => time()),
				__METHOD__ ) or die('Query vote failed!');
		}
		
		# Calculate the average rating and number of votes
		$result=$dbmaster->select('w4grb_votes', 'AVG(vote) AS avg, COUNT(vote) AS n',
				array('pid' => $this->page_idnum),
				__METHOD__);
		if($row = $dbslave->fetchObject($result))
			{
			$average_rating = intval($row->avg);
			$num_votes = intval($row->n);
			}
		$dbmaster->freeResult($result);
		
		#  Place those in the average rating table (same procedure as votecasting: first check in slave, then try in master)
		$result = $dbslave->select('w4grb_avg', 'avg',
				array('pid' => $this->page_idnum),
				__METHOD__ ,
				array('LIMIT' => 1));
		if($row = $dbslave->fetchObject($result)) $already_averaged=true;
		else $already_averaged=false;
		$dbslave->freeResult($result);
		
		if ($already_averaged || !$dbmaster->insert('w4grb_avg',
			array(	'pid' => $this->page_idnum,
					'avg' => $average_rating,
					'n' => $num_votes),
			__METHOD__ ))
			{
			if (!$dbmaster->update('w4grb_avg',
				array(	'avg' => $average_rating,
						'n' => $num_votes),
				array(	'pid' => $this->page_idnum),
				__METHOD__ ))
			echo 'Query avg failed!';
			}
		
		
		# Display what we did
		echo wfMsg('w4g_rb-current_user_rating','<b>'.($average_rating/20) .'</b>',$num_votes).'<br/>';
		echo wfMsg('w4g_rb-you_voted').' <b><span id="w4g_rb_rating_value-'.$this->bar_id.'">'. ($vote_sent/20) .'</span></b>';

	}
	
	/**
	 * Displays current vote (by the user and on average)
	 */
	private function justShowVotes()
	{
	global $wgUser;
	global $wgW4GRB_Settings;
	$out = '';
		
	$dbslave = wfGetDB( DB_SLAVE );
	
	# Gets average rating
	$result = $dbslave->select('w4grb_avg', 'avg,n',
			array('pid' => $this->page_idnum),
			__METHOD__ ,
			array('LIMIT' => 1));
	if($row = $dbslave->fetchObject($result))
		{
		$average_rating = intval($row->avg);
		$num_votes = intval($row->n);
		$out .= wfMsg('w4g_rb-current_user_rating','<b>'. ($average_rating/20) .'</b>',$num_votes).'<br/>';
		}
	else $out .= wfMsg('w4g_rb-nobody_voted').'.<br/>';
	$dbslave->freeResult($result);
	
	# If not logged in and anonymous voting not enabled
	if($this->uid==self::ANONYMOUS_UID && !$wgW4GRB_Settings['anonymous-voting-enabled'])
	{
		$loginLink = $this->skin->link( SpecialPage::getTitleFor( 'Userlogin' ), wfMsg('w4g_rb-log_in'), array(), array());
		$signupLink = $this->skin->link( SpecialPage::getTitleFor( 'Userlogin' ), wfMsg('w4g_rb-register'), array(), array('type'=>'signup'));
		$out .= wfMsg('w4g_rb-error_must_login',$loginLink,$signupLink);
	}
	# Else if not allowed to vote (NB: anonymous voting overrides this!)
	else if (!$wgUser->isAllowed('w4g_rb-canvote') && !$wgW4GRB_Settings['anonymous-voting-enabled'])
	{
		$rightsLink = $this->skin->link( SpecialPage::getTitleFor( 'Listgrouprights' ), wfMsg('w4g_rb-voting_rights'), array(), array());
		$out .= wfMsg('w4g_rb-error_no_canvote',$rightsLink);
	}
	# If the user can vote, get their vote (if anonymous, get the vote from their IP)
	else
	{
		if($this->uid==self::ANONYMOUS_UID)
			$result = $dbslave->select('w4grb_votes', 'vote',
					array(	'pid' => $this->page_idnum,
							'uid' => self::ANONYMOUS_UID,
							'ip' => $_SERVER['REMOTE_ADDR']),
					__METHOD__ ,
					array('LIMIT' => 1));
		else $result = $dbslave->select('w4grb_votes', 'vote',
					array(	'pid' => $this->page_idnum,
							'uid' => $this->uid),
					__METHOD__ ,
					array('LIMIT' => 1));
		if($row = $dbslave->fetchObject($result))
			{
			$existing_vote = intval($row->vote);
			$out .= wfMsg('w4g_rb-you_voted').' <b><span id="w4g_rb_rating_value-'.$this->bar_id.'">'. ($existing_vote/20) .'</span></b>';
			}
		else  $out .= wfMsg('w4g_rb-you_didnt_vote').'.<br/>';
		$dbslave->freeResult($result);
	}
		
	# Display what we did
	return $out;
	}
}