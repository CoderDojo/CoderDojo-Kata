<?php

/*********************************************************************
**
** This file is part of the W4G Rating Bar extension for MediaWiki
** Copyright (C)2011
**                - David Dernoncourt <www.patheticcockroach.com>
**                - Franck Dernoncourt <www.francky.me>
**
** Home Page : http://www.wiki4games.com/Wiki4Games:W4G Rating Bar
**
** This program is licensed under the Creative Commons
** Attribution-Noncommercial-No Derivative Works 3.0 Unported license
** <http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode>
**
** The attribution part of the license prohibits any unauthorized editing of any line related to
** $wgExtensionCredits['parserhook'][] and $wgExtensionCredits['specialpage'][]
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
**
*********************************************************************/

class W4GRBPage
{
	# always set when creating the object
	private $page_id; # page numeric ID as in page.page_id
	private $page_name; #page name as in page.page_title
	private $NS_id; # page namespace numeric ID as in page.page_namespace
	private $NS_name; # page namespace name
	private $fullpagename; # full page name (like Namespace:Title)
	private $valid; # true if the page exists, false otherwise
	
	# not always set (need to ask specifically since it's not always needed and means an extra SQL query)
	# will be false if not set (beware, when set they CAN be zero, so check the boolean with ===)
	private $avg_rating; # average rating
	private $n_voters; # number of voters
	
	public function __construct()
	{
		$this->reset();
	}
	
	/** sets all values to what they must be at start */
	private function reset()
	{
		$this->valid = false;
		$this->avg_rating = false;
		$this->n_voters = false;
		$this->NS_name = '';
	}
	
	/** "load averages": sets $avg_rating and $n_voters */
	private function loadAVG()
	{
		$dbslave = wfGetDB( DB_SLAVE );
		
		$result = $dbslave->select('w4grb_avg', 'avg,n',
				array('pid' => $this->page_id),
				__METHOD__);
		if($row = $dbslave->fetchObject($result))
			{
			$this->avg_rating = intval($row->avg);
			$this->n_voters = intval($row->n);
			}
		else
			{
			$this->avg_rating = '--';
			$this->n_voters = 0;
			}
		$dbslave->freeResult($result);
		unset($dbslave);
	}
	
	/**
	* This is an improved version of MWNamespace::getCanonicalIndex which is able to deal properly with the Project: and Project talk: namespaces
	**/
	private function computeCanonicalIndex($name)
	{
		global $wgMetaNamespace, $wgMetaNamespaceTalk, $wgSitename;
		$localMetaNamespace = ($wgMetaNamespace===false)? $wgSitename : $wgMetaNamespace; // project namespace
		$localMetaNamespaceTalk = ($wgMetaNamespaceTalk===false)? $localMetaNamespace.'_talk' : $wgMetaNamespaceTalk; // project talk NS
		$result=MWNamespace::getCanonicalIndex($name);
		if(is_null($result))
			{
			if($name==strtolower($localMetaNamespace)) $result=NS_PROJECT;
			else if($name==strtolower($localMetaNamespaceTalk)) $result=NS_PROJECT_TALK;
			}
		return $result;
	}
	/**
	* This is an improved version of MWNamespace::getCanonicalName which is able to deal properly with the Project: and Project talk: namespaces
	* @param $index int: numerical ID to match to a namespace
	* @return the name of the namespace (string)
	**/
	private function computeCanonicalName($index)
	{
		global $wgMetaNamespace, $wgMetaNamespaceTalk, $wgSitename;
		$localMetaNamespace = ($wgMetaNamespace===false)? $wgSitename : $wgMetaNamespace; // project namespace
		$localMetaNamespaceTalk = ($wgMetaNamespaceTalk===false)? $localMetaNamespace.'_talk' : $wgMetaNamespaceTalk; // project talk NS
		$result=MWNamespace::getCanonicalName($index);
		if($index==NS_PROJECT) $result=$localMetaNamespace;
		else if($index==NS_PROJECT_TALK) $result=$localMetaNamespaceTalk;
		return $result;
	}
	
	/**
	* Sets the object to the page passed to the function.
	* @param $pid int: must be a page numerical ID
	* @return true if ID does exist, false otherwise
	**/
	public function setPID($pid)
	{
		$this->reset();
		$this->page_id = intval($pid);
		
		$dbslave = wfGetDB( DB_SLAVE );
		$result = $dbslave->select('page', 'page_title,page_namespace',
					array('page_id' => $this->page_id),
					__METHOD__);
		if($row = $dbslave->fetchObject($result))
			{
			$this->page_name=$row->page_title;
			$this->NS_id=$row->page_namespace;
			$this->NS_name=$this->computeCanonicalName($this->NS_id);
			$this->fullpagename=$this->NS_name. (($this->NS_name!='')? ':':'') .$this->page_name;
			$this->valid=true;
			}
		$dbslave->freeResult($result);
		unset($dbslave);
		return $this->valid;
	}
	
	/**
	* Sets the object to the page passed to the function.
	* @param $fullname string: must be a full page name, like the one output by {{FULLPAGENAME}} (e.g.Namespace:Title)
	* @return true if ID does exist, false otherwise
	**/
	public function setFullPageName($fullname)
	{
		global $wgW4GRB_Settings;
		$this->reset();
		$this->fullpagename = $fullname;
		if($wgW4GRB_Settings['fix-spaces']) $this->fullpagename = str_replace(' ','_',$this->fullpagename);
		
		# Much gracias to includes/Namespace.php
		$this->NS_id = NS_MAIN;
		if(strpos($this->fullpagename,':') && !is_null($this->computeCanonicalIndex(strtolower(substr($this->fullpagename, 0, strpos($this->fullpagename,':'))))))
			{
			$this->NS_name = substr($this->fullpagename, 0, strpos($this->fullpagename,':'));
			$this->NS_id = $this->computeCanonicalIndex(strtolower($this->NS_name));
			}
		if($this->NS_id!=NS_MAIN)
			$this->page_name = substr($this->fullpagename, strpos($this->fullpagename,':') + 1);
		else $this->page_name = $this->fullpagename;
		
		$dbslave = wfGetDB( DB_SLAVE );
		$result = $dbslave->select('page', 'page_id',
					array(	'page_title' => $this->page_name,
							'page_namespace' => $this->NS_id),
					__METHOD__);
		if($row = $dbslave->fetchObject($result))
			{
			$this->page_id=intval($row->page_id);
			$this->valid=true;
			}
		$dbslave->freeResult($result);
		#return $dbslave->lastQuery();  # used to find out that mysql_real_escape_string seems to be done automatically
		unset($dbslave);
		return $this->valid;
	}
	
	/** Returns true if the object is a proper, existing page. False otherwise. **/
	public function exist()
	{
		return $this->valid;
	}
	
	/*
	* Getters
	**/
	public function getFullPageName()
	{
		if($this->valid) return $this->fullpagename;
		else return false;
	}
	
	public function getPID()
	{
		if($this->valid) return $this->page_id;
		else return false;
	}
	
	public function getPName()
	{
		if($this->valid) return $this->page_name;
		else return false;
	}
	
	public function getNsID()
	{
		if($this->valid) return $this->NS_id;
		else return false;
	}
	
	public function getNsName()
	{
		if($this->valid) return $this->NS_name;
		else return false;
	}
	
	public function getAVG()
	{
		if($this->avg_rating===false) $this->loadAVG();
		return $this->avg_rating;
	}
	
	public function getNVotes()
	{
		if($this->n_voters===false) $this->loadAVG();
		return $this->n_voters;
	}
	
	/**
	* Just used when debugging
	**/
	public function debug()
	{
	return substr($this->fullpagename, 0, strpos($this->fullpagename,':'))
		.MWNamespace::getCanonicalIndex(strtolower(substr($this->fullpagename, 0, strpos($this->fullpagename,':'))))
		.MWNamespace::getCanonicalName($this->NS_id)
		.$this->NS_name;
	}
}