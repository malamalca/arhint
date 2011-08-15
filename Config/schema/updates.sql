/* FROM VERSION 1.0 TO 2.0 */
ALTER TABLE  `projects` CHANGE  `no`  `slug` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `projects` ADD  `no` VARCHAR( 20 ) NULL DEFAULT NULL AFTER  `slug`;
ALTER TABLE  `projects` ADD  `descript` TEXT NULL DEFAULT NULL AFTER  `name`;

RENAME TABLE  `arhim_dev`.`counters` TO  `arhim_dev`.`invoices_counters` ;