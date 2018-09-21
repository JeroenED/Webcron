-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 12, 2018 at 12:23 PM
-- Server version: 10.1.34-MariaDB-0ubuntu0.18.04.1
-- PHP Version: 5.6.37-1+ubuntu18.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `jeroened_webcron`
--

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `conf` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `label` text NOT NULL,
  `description` text NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`conf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`conf`, `category`, `type`, `label`, `description`, `value`) VALUES
('dbclean.delay', 'Database Cleanup', 'number(0)', 'Cleanup Delay', 'How many days until the database cleanup is triggered', '7'),
('dbclean.expireruns', 'Database Cleanup', 'number(0)', 'Retention', 'How many days does the database keep the runs', '30'),
('dbclean.enabled', 'Database Cleanup', 'text', 'Enabled', 'Database cleanup enabled? (true: yes; false: no)', 'false'),
('dbclean.lastrun', 'Database Cleanup', 'hidden', 'Last run', 'Last run of database cleanup', UNIX_TIMESTAMP()),
('jobs.reboottime', 'Jobs', 'number(0,30)', 'Reboot delay', 'The amount of delay in minutes between scheduling a reboot and the actual reboot', '5');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `jobID` int(11) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `name` text NOT NULL,
  `url` text NOT NULL,
  `host` varchar(50) NOT NULL DEFAULT 'localhost',
  `delay` int(11) NOT NULL,
  `nextrun` int(11) NOT NULL,
  `expected` int(11) NOT NULL DEFAULT '200',
  PRIMARY KEY (`jobID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `runs`
--

DROP TABLE IF EXISTS `runs`;
CREATE TABLE IF NOT EXISTS `runs` (
  `runID` bigint(20) NOT NULL AUTO_INCREMENT,
  `job` int(11) NOT NULL,
  `statuscode` char(3) NOT NULL,
  `result` longtext NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`runID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `password` char(60) NOT NULL,
  `email` varchar(100) NOT NULL,
  `autologin` text NOT NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
