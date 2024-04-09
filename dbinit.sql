CREATE DATABASE arfnet2;

CREATE TABLE `arfnet2`.`users` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `username` VARCHAR(31) NOT NULL ,
    `password` VARCHAR(255) NOT NULL ,
    `email` VARCHAR(127) NOT NULL ,
    `verifycode` VARCHAR(31) NOT NULL ,
    `type` ENUM('client','helpdesk','accountant','admin') NOT NULL ,
    `regdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `status` ENUM('verified','unverified') NOT NULL DEFAULT 'unverified' ,
    PRIMARY KEY (`id`)
);

CREATE TABLE `arfnet2`.`services` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `name` VARCHAR(255) NOT NULL ,
    `type` ENUM('free','standard','premium') NOT NULL ,
    `billing` VARCHAR(255) NOT NULL ,
    `description` TEXT NOT NULL ,
    PRIMARY KEY (`id`)
);

CREATE TABLE `arfnet2`.`orders` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `service` INT NOT NULL ,
    `name` VARCHAR(255) NOT NULL ,
    `client` INT NOT NULL ,
    `billing` VARCHAR(255) NOT NULL ,
    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `status` ENUM('setting up','active','inactive') NOT NULL DEFAULT 'setting up' ,
    `comments` TEXT NOT NULL ,
    PRIMARY KEY (`id`)
);

CREATE TABLE `arfnet2`.`tickets` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `order` INT NOT NULL ,
    `subject` VARCHAR(255) NOT NULL ,
    `body` TEXT NOT NULL ,
    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `status` ENUM('open','closed') NOT NULL DEFAULT 'open' ,
    `closecomment` TEXT NOT NULL DEFAULT '',
    `asignee` INT NOT NULL ,
    PRIMARY KEY (`id`)
);

CREATE TABLE `arfnet2`.`invoices` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `client` INT NOT NULL ,
    `desc` VARCHAR(255) NOT NULL ,
    `amount` DECIMAL(10, 4) NOT NULL ,
    `pdf` MEDIUMBLOB NOT NULL ,
    `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    `proof` MEDIUMBLOB DEFAULT NULL ,
    `status` ENUM('paid','unpaid') NOT NULL DEFAULT 'unpaid' ,
    PRIMARY KEY (`id`)
);
