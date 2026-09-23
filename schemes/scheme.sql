CREATE DATABASE IF NOT EXISTS bdnovabank;
  

USE bdnovabank;

CREATE TABLE IF NOT EXISTS `User` (
  `idUser` INT NOT NULL AUTO_INCREMENT,
  `Alias` VARCHAR(45) NOT NULL,
  `Description` VARCHAR(255) NULL,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verification_token` VARCHAR(255) NULL,
  `PFP` BLOB NULL,
  `Password` VARCHAR(255) NOT NULL,
  `Balance` DECIMAL(65,2) NULL DEFAULT 1500,
  PRIMARY KEY (`idUser`)
);

ALTER TABLE `User`
  ADD UNIQUE KEY `uniq_alias` (`Alias`),
  ADD UNIQUE KEY `uniq_email` (`Description`);

CREATE TABLE IF NOT EXISTS `Stock` (
  `idStock` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(45) NULL,
  `LastPrice` DECIMAL(65,2) NULL,
  `Volume` DECIMAL(65,2) NULL,
  PRIMARY KEY (`idStock`)
);

CREATE TABLE IF NOT EXISTS `StockInvest` (
  `idInvest` INT NOT NULL AUTO_INCREMENT,
  `idStock` INT NOT NULL,
  `IdUser` INT NOT NULL,
  `Investement` DECIMAL(65,2) NOT NULL,
  PRIMARY KEY (`idInvest`),
  INDEX `FK_idStock_idx` (`idStock`),
  INDEX `FK_idUser_idx` (`IdUser`),
  CONSTRAINT `FK_idStock`
    FOREIGN KEY (`idStock`)
    REFERENCES `Stock` (`idStock`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_idUser`
    FOREIGN KEY (`IdUser`)
    REFERENCES `User` (`idUser`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);

CREATE TABLE IF NOT EXISTS `Crypto` (
  `idCrypto` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(45) NULL,
  `Description` VARCHAR(45) NULL,
  `marketCap` DECIMAL(65,2) NULL,
  `TotalSupply` INT NULL,
  `maxSupply` INT NULL,
  `CirculatingSupply` INT NULL,
  `Logo` BLOB NULL,
  `LaunchDate` DATE NULL,
  `Website` VARCHAR(45) NULL,
  `ProofType` VARCHAR(45) NULL,
  PRIMARY KEY (`idCrypto`)
);

CREATE TABLE IF NOT EXISTS `CryptoInvest` (
  `idInvest` INT NOT NULL AUTO_INCREMENT,
  `idCrypto` INT NOT NULL,
  `IdUser` INT NOT NULL,
  `Investement` DECIMAL(65,2) NOT NULL,
  PRIMARY KEY (`idInvest`),
  INDEX `FK_idCrypto_idx` (`idCrypto`),
  INDEX `FK_idUser2_idx` (`IdUser`),
  CONSTRAINT `FK_idCrypto`
    FOREIGN KEY (`idCrypto`)
    REFERENCES `Crypto` (`idCrypto`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_idUser0`
    FOREIGN KEY (`IdUser`)
    REFERENCES `User` (`idUser`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
);

