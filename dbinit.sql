CREATE DATABASE arfnet2;

CREATE TABLE `arfnet2`.`users` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `username` VARCHAR(31) NOT NULL ,
    `password` VARCHAR(255) NOT NULL ,
    `email` VARCHAR(127) NOT NULL ,
    `verifycode` VARCHAR(31) NOT NULL ,
    `type` ENUM('client','helpdesk','accountant','admin') NOT NULL ,
    `regdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
    PRIMARY KEY (`id`)
);
