USE nnstats;
DROP TABLE IF EXISTS `stats`;
CREATE TABLE `stats` (
	`id` MEDIUMINT NOT NULL AUTO_INCREMENT,
	`categoryID` INT DEFAULT 0,
	`categoryname` VARCHAR(255) NOT NULL DEFAULT '',
	`updatedate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`total` INT DEFAULT 0,
	`queue` INT DEFAULT 0,
	PRIMARY KEY (`id`)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
