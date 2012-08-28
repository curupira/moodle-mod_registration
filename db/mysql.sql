#
# Table structure for table `registration`
#

CREATE TABLE IF NOT EXISTS `prefix_registration` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `course` bigint(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `intro` text NOT NULL,
  `number` mediumint(5) unsigned NOT NULL DEFAULT '0',
  `room` varchar(30) NOT NULL DEFAULT '',
  `timedue` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timeavailable` bigint(10) unsigned NOT NULL DEFAULT '0',
  `grade` bigint(10) NOT NULL DEFAULT '0',
  `timemodified` bigint(10) unsigned NOT NULL DEFAULT '0',
  `allowqueue` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `course` (`course`)
) COMMENT='Defines registrations';

# --------------------------------------------------------

#
# Table structure for table `registration_submissions`
#

CREATE TABLE IF NOT EXISTS `prefix_registration_submissions` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `registration` bigint(10) unsigned NOT NULL DEFAULT '0',
  `userid` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timecreated` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timemodified` bigint(10) unsigned NOT NULL DEFAULT '0',
  `grade` bigint(11) NOT NULL DEFAULT '0',
  `teacher` bigint(10) unsigned NOT NULL DEFAULT '0',
  `timemarked` bigint(10) unsigned NOT NULL DEFAULT '0',
  `mailed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `registration` (`registration`),
  KEY `userid` (`userid`),
  KEY `mailed` (`mailed`),
  KEY `timemarked` (`timemarked`)
) COMMENT='Info about submitted registrations';

# --------------------------------------------------------


INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'view', 'registration', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'add', 'registration', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'update', 'registration', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'view submission', 'registration', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('registration', 'upload', 'registration', 'name');

