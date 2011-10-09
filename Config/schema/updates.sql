/* FROM VERSION 1.0 TO 2.0 */
ALTER TABLE  `projects` CHANGE  `no`  `slug` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE  `projects` ADD  `no` VARCHAR( 20 ) NULL DEFAULT NULL AFTER  `slug`;
ALTER TABLE  `projects` ADD  `descript` TEXT NULL DEFAULT NULL AFTER  `name`;

RENAME TABLE  `counters` TO  `invoices_counters` ;

/* FROM VERSION 2.0 TO 2.0.1 */
CREATE TABLE  `payments_accounts` (
`id` CHAR( 36 ) NOT NULL ,
`title` VARCHAR( 200 ) NULL DEFAULT NULL ,
`created` DATETIME NULL DEFAULT NULL ,
`modified` DATETIME NULL DEFAULT NULL ,
PRIMARY KEY (  `id` )
);
INSERT INTO  `payments_accounts` (
	`id` ,
	`title` ,
	`created` ,
	`modified`
	)
	VALUES (
	'7c1798f2-f0b3-11e0-a211-b8ac6f7cbae5' ,  'Company account', NULL , NULL
);
INSERT INTO `payments_accounts` (
	`id` ,
	`title` ,
	`created` ,
	`modified`
	)
	VALUES (
	'909cb44c-f0b3-11e0-a211-b8ac6f7cbae5',  'Personal account', NULL , NULL
);
ALTER TABLE  `payments` ADD  `account_id` CHAR( 36 ) NULL DEFAULT NULL AFTER  `id`;
UPDATE  `payments` SET  `account_id` =  '7c1798f2-f0b3-11e0-a211-b8ac6f7cbae5' WHERE  `source` =  'c';
UPDATE  `payments` SET  `account_id` =  '909cb44c-f0b3-11e0-a211-b8ac6f7cbae5' WHERE  `source` =  'p';
ALTER TABLE  `payments` DROP  `source`;

ALTER TABLE  `invoices` DROP  `expense`;
ALTER TABLE  `invoices` DROP  `era`;
ALTER TABLE  `invoices` DROP  `expense_id`;
ALTER TABLE  `invoices` DROP  `kind`;

ALTER TABLE  `invoices` ADD  `invoices_attachment_count` INT NOT NULL DEFAULT  '0' AFTER  `project_id`;