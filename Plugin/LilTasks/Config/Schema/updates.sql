/* VERSION pre TO 1.0 */

ALTER TABLE  `tasks` CHANGE  `descript`  `title` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `tasks` ADD  `descript` TEXT NULL DEFAULT NULL AFTER  `title`;