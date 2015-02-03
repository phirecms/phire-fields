--
-- Fields Module MySQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

--
-- Table structure for table `field_groups`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]field_groups` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `name` varchar(255),
  `order` int(16),
  `dynamic` int(1),
  PRIMARY KEY (`id`),
  INDEX `field_group_name` (`name`),
  INDEX `field_group_order` (`order`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10001 ;

-- --------------------------------------------------------

--
-- Table structure for table `fields`
--

DROP TABLE IF EXISTS `[{prefix}]fields`;
CREATE TABLE IF NOT EXISTS `[{prefix}]fields` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `group_id` int(16),
  `type` varchar(255),
  `name` varchar(255),
  `label` varchar(255),
  `values` text,
  `default_values` text,
  `attributes` varchar(255),
  `validators` varchar(255),
  `encrypt` int(1) NOT NULL,
  `order` int(16) NOT NULL,
  `required` int(1) NOT NULL,
  `placement` varchar(255),
  `editor` varchar(255),
  `models` text,
  PRIMARY KEY (`id`),
  INDEX `field_group_id` (`group_id`),
  INDEX `field_type` (`type`),
  INDEX `field_name` (`name`),
  CONSTRAINT `fk_group_id` FOREIGN KEY (`group_id`) REFERENCES `[{prefix}]field_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11001 ;

-- --------------------------------------------------------

--
-- Table structure for table `field_values`
--

DROP TABLE IF EXISTS `[{prefix}]field_values`;
CREATE TABLE IF NOT EXISTS `[{prefix}]field_values` (
  `field_id` int(16) NOT NULL,
  `model_id` int(16) NOT NULL,
  `value` mediumtext,
  `timestamp` int(16),
  `history` mediumtext,
  INDEX `field_id` (`field_id`),
  INDEX `model_id` (`model_id`),
  UNIQUE (`field_id`, `model_id`),
  CONSTRAINT `fk_field_id` FOREIGN KEY (`field_id`) REFERENCES `[{prefix}]fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
