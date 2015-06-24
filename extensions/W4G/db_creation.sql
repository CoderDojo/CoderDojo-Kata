CREATE TABLE IF NOT EXISTS `wik_w4grb_votes` (
  `uid` int(11) unsigned NOT NULL,
  `pid` int(11) unsigned NOT NULL,
  `vote` tinyint(4) unsigned NOT NULL,
  `ip` varbinary(39) NOT NULL,
  `time` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`uid`,`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE IF NOT EXISTS `wik_w4grb_avg` (
  `pid` int(10) unsigned NOT NULL,
  `avg` float unsigned NOT NULL,
  `n` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

# update to database to support anonymous voting:
ALTER TABLE  `wik_w4grb_votes` DROP PRIMARY KEY ,
ADD PRIMARY KEY (  `uid` ,  `pid` ,  `ip` );