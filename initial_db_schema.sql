SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

CREATE DATABASE `intercom_data` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
use intercom_data;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intercom_id` varchar(100) NOT NULL,
  `email` varchar(1000) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `team_id` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `intercom_id` (`intercom_id`),
  KEY `email` (`email`(50))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `conversations`;
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intercom_id` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `initiated_from_url` text DEFAULT NULL,
  `assigned_to_type` varchar(50) DEFAULT NULL,
  `assigned_to_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`intercom_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `conversation_attachments`;
CREATE TABLE `conversation_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `unique_filename_hash` char(64) NOT NULL,
  `attached_by_type` varchar(50) DEFAULT NULL,
  `attached_by_id` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `content` longblob DEFAULT NULL,
  `content_type` text DEFAULT NULL,
  `filesize` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `conversation_customers`;
CREATE TABLE `conversation_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `customer_type` varchar(50) NOT NULL,
  `customer_id` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `conversation_parts`;
CREATE TABLE `conversation_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `intercom_id` bigint(20) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `assigned_to_type` varchar(50) DEFAULT NULL,
  `assigned_to_id` varchar(100) DEFAULT NULL,
  `author_type` varchar(10) DEFAULT NULL,
  `author_id` varchar(100) DEFAULT NULL,
  `subject` longtext DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `intercom_id` (`intercom_id`),
  KEY `conversation_id` (`conversation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `conversation_part_attachments`;
CREATE TABLE `conversation_part_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_part_id` int(11) NOT NULL,
  `unique_filename_hash` char(64) NOT NULL,
  `attached_by_type` varchar(50) DEFAULT NULL,
  `attached_by_id` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `content` longblob DEFAULT NULL,
  `content_type` text DEFAULT NULL,
  `filesize` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_part_id` (`conversation_part_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `conversation_tags`;
CREATE TABLE `conversation_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `tag_intercom_id` int(11) NOT NULL,
  `name` text DEFAULT NULL,
  `applied_at` datetime DEFAULT NULL,
  `applied_by_type` varchar(50) NOT NULL,
  `applied_by_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `tag_intercom_id` (`tag_intercom_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `leads`;
CREATE TABLE `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intercom_id` varchar(100) NOT NULL,
  `intercom_user_id` varchar(100) NOT NULL,
  `email` varchar(1000) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `pseudonym` text DEFAULT NULL,
  `app_id` varchar(50) DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `intercom_id` (`intercom_id`),
  KEY `email` (`email`(50))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intercom_id` varchar(100) NOT NULL,
  `email` varchar(1000) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `pseudonym` text DEFAULT NULL,
  `app_id` varchar(50) DEFAULT NULL,
  `referrer` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `intercom_id` (`intercom_id`),
  KEY `email` (`email`(50))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
