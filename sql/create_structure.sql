-- phpMyAdmin SQL Dump
-- version 2.11.9.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 29, 2009 at 02:27 PM
-- Server version: 5.0.77
-- PHP Version: 5.2.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `udbview`
--

-- --------------------------------------------------------

--
-- Table structure for table `areatable`
--

DROP TABLE IF EXISTS `areatable`;
CREATE TABLE IF NOT EXISTS `areatable` (
  `id` smallint(6) unsigned NOT NULL,
  `map` smallint(6) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `chartitles`
--

DROP TABLE IF EXISTS `chartitles`;
CREATE TABLE IF NOT EXISTS `chartitles` (
  `id` smallint(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `chrclasses`
--

DROP TABLE IF EXISTS `chrclasses`;
CREATE TABLE IF NOT EXISTS `chrclasses` (
  `id` smallint(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `chrraces`
--

DROP TABLE IF EXISTS `chrraces`;
CREATE TABLE IF NOT EXISTS `chrraces` (
  `id` smallint(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `faction`
--

DROP TABLE IF EXISTS `faction`;
CREATE TABLE IF NOT EXISTS `faction` (
  `id` smallint(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `map`
--

DROP TABLE IF EXISTS `map`;
CREATE TABLE IF NOT EXISTS `map` (
  `id` smallint(6) unsigned NOT NULL,
  `type` tinyint(1) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `questinfo`
--

DROP TABLE IF EXISTS `questinfo`;
CREATE TABLE IF NOT EXISTS `questinfo` (
  `id` smallint(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `questsort`
--

DROP TABLE IF EXISTS `questsort`;
CREATE TABLE IF NOT EXISTS `questsort` (
  `id` smallint(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `skillline`
--

DROP TABLE IF EXISTS `skillline`;
CREATE TABLE IF NOT EXISTS `skillline` (
  `id` smallint(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `spell`
--

DROP TABLE IF EXISTS `spell`;
CREATE TABLE IF NOT EXISTS `spell` (
  `id` int(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
CREATE TABLE IF NOT EXISTS `status` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `quest_id` int(6) unsigned NOT NULL,
  `user` int(11) NOT NULL,
  `udbver` int(4) NOT NULL,
  `report` text NOT NULL,
  `status` tinyint(1) NOT NULL,
  `ts` int(10) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `name` varchar(30) NOT NULL,
  `password` varchar(32) NOT NULL,
  `lastlogin` int(10) NOT NULL,
  `power` tinyint(3) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
