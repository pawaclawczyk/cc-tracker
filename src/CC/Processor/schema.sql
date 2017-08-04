CREATE TABLE raw_requests (
	`id` INTEGER NOT NULL AUTO_INCREMENT,
	`content` TEXT NOT NULL,
	`country` VARCHAR(128) NOT NULL,
	`os` VARCHAR(128) NOT NULL,
	`browser` VARCHAR(128) NOT NULL,
	PRIMARY KEY(id)
);
