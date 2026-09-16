
-- Schema mydb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `bdnovabank` DEFAULT CHARACTER SET utf8 ;
USE `bdnovabank` ;

-- -----------------------------------------------------
-- Table `bdnovabank`.`User`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bdnovabank`.`User` (
  `idUser` INT NOT NULL AUTO_INCREMENT,
  `Alias` VARCHAR(45) NOT NULL,
  `Description` VARCHAR(45) NULL,
  `PFP` BLOB NULL,
  `Password` VARBINARY(48) NULL,
  `Balance` DECIMAL(65,2) NULL DEFAULT 1500,
  PRIMARY KEY (`idUser`, `Alias`))



-- -----------------------------------------------------
-- Table `bdnovabank`.`Stock`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bdnovabank`.`Stock` (
  `idStock` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(45) NULL,
  `LastPrice` VARCHAR(45) NULL,
  `Volume` VARCHAR(45) NULL,
  PRIMARY KEY (`idStock`))



-- -----------------------------------------------------
-- Table `bdnovabank`.`StockInvest`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bdnovabank`.`StockInvest` (
  `idInvest` INT NOT NULL AUTO_INCREMENT,
  `idStock` INT NOT NULL,
  `IdUser` INT NOT NULL,
  `Investement` DECIMAL(65,2) NOT NULL,
  PRIMARY KEY (`idInvest`, `idStock`, `IdUser`),
  INDEX `FK_idStock_idx` (`idStock` ASC) VISIBLE,
  INDEX `FK_idUser_idx` (`IdUser` ASC) VISIBLE,
  CONSTRAINT `FK_idStock`
    FOREIGN KEY (`idStock`)
    REFERENCES `bdnovabank`.`Stock` (`idStock`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_idUser`
    FOREIGN KEY (`IdUser`)
    REFERENCES `bdnovabank`.`User` (`idUser`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)



-- -----------------------------------------------------
-- Table `bdnovabank`.`Crypto`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bdnovabank`.`Crypto` (
  `idCrypto` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(45) NULL,
  `Description` VARCHAR(45) NULL,
  `marketCap` DECIMAL NULL,
  `TotalSupply` INT NULL,
  `maxSupply` INT NULL,
  `CirculatingSupply` INT NULL,
  `Logo` BLOB NULL,
  `LaunchDate` DATE NULL,
  `Website` VARCHAR(45) NULL COMMENT 'For the record website is the crypto providers site and is not always given same for the logo',
  `ProofType` VARCHAR(45) NULL COMMENT 'The type of the proof of work it can be proof of work, proof of stake, zero knowledge proof (risky should be listed as so on site), proof of capacity, Merkle proof is a compact hashchain \nmay be problematic',
  PRIMARY KEY (`idCrypto`))



-- -----------------------------------------------------
-- Table `bdnovabank`.`CryptoInvest`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bdnovabank`.`CryptoInvest` (
  `idInvest` INT NOT NULL AUTO_INCREMENT,
  `idCrypto` INT NOT NULL,
  `IdUser` INT NOT NULL,
  `Investement` DECIMAL(65,2) NOT NULL,
  PRIMARY KEY (`idInvest`, `idCrypto`, `IdUser`),
  INDEX `FK_idUser_idx` (`IdUser` ASC) VISIBLE,
  INDEX `FK_idCrypto_idx` (`idCrypto` ASC) VISIBLE,
  CONSTRAINT `FK_idCrypto`
    FOREIGN KEY (`idCrypto`)
    REFERENCES `bdnovabank`.`Crypto` (`idCrypto`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_idUser0`
    FOREIGN KEY (`IdUser`)
    REFERENCES `bdnovabank`.`User` (`idUser`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)

DELIMITER //

PROCEDURE bdnovabank.AddUser(
  Name VARCHAR(45),
  Description VARCHAR(45),
  PFP BLOB,
  Password VARBINARY(48),
  Balance DECIMAL(65,2)



//