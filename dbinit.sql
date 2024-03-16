CREATE DATABASE arfnet2;

CREATE TABLE `arfnet2`.`users` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `username` VARCHAR(31) NOT NULL ,
    `password` VARCHAR(255) NOT NULL ,
    `email` VARCHAR(127) NOT NULL ,
    `verifycode` VARCHAR(31) NOT NULL ,
    `status` ENUM('verified','unverified') NOT NULL DEFAULT 'unverified' ,
    `type` ENUM('client','helpdesk','accountant','admin') NOT NULL ,
    `regdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`id`)
);

CREATE TABLE `arfnet2`.`services` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `name` VARCHAR(255) NOT NULL ,
    `type` ENUM('free','standard','premium') NOT NULL ,
    `billing` DECIMAL NOT NULL ,
    `description` TEXT NOT NULL ,
    PRIMARY KEY (`id`)
);
