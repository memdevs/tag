/*

MySQL definition for tags

*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `tagdefinition`
-- ----------------------------
DROP TABLE IF EXISTS `tagdefinition`;
CREATE TABLE `tagdefinition` (
  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(30) NOT NULL,
  `tag_type` enum('i','t','j','c','n') CHARACTER SET latin1 NOT NULL DEFAULT 'n' COMMENT 'i=int; t=text; j=json; c=counter; n=no value',
  `tag_is_unique` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `idx_tag_name` (`tag_name`)
) DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tagdefinition
-- ----------------------------
INSERT INTO `tagdefinition` VALUES ('1', 'integer tag', 'i', '0', null, null);
INSERT INTO `tagdefinition` VALUES ('2', 'null value tag', 'n', '1', null, null);
INSERT INTO `tagdefinition` VALUES ('3', 'json tag', 'j', '1', null, null);
INSERT INTO `tagdefinition` VALUES ('4', 'counter tag', 'c', '1', null, null);
INSERT INTO `tagdefinition` VALUES ('5', 'text tag - unique', 't', '1', null, null);
INSERT INTO `tagdefinition` VALUES ('6', 'text tag - multiple', 't', '0', null, null);


-- ----------------------------
-- Table structure for `tagentry`
-- ----------------------------
DROP TABLE IF EXISTS `tagentry`;
CREATE TABLE `tagentry` (
  `tagentry_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `external_id` int(10) unsigned NOT NULL,
  `tag_id` int(11) unsigned NOT NULL,
  `tag_value` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`tagentry_id`),
  KEY `idx_tag_id` (`tag_id`)
) DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tagentry
-- ----------------------------
