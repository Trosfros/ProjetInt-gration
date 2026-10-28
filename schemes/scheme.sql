CREATE DATABASE IF NOT EXISTS bdnovabank;
  

USE bdnovabank;

CREATE TABLE IF NOT EXISTS `User` (
  `idUser` INT NOT NULL AUTO_INCREMENT,
  `Alias` VARCHAR(45) NOT NULL,
  `idAvatar` INT NULL,
  `Description` VARCHAR(255) NULL,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verification_token` VARCHAR(255) NULL,
  `PFP` BLOB NULL,
  `Password` VARCHAR(255) NOT NULL,
  `Balance` DECIMAL(65,2) NULL DEFAULT 1500,
  `RealizedPnl` decimal(65,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`idUser`),
  UNIQUE KEY `uniq_alias` (`Alias`),
  UNIQUE KEY `uniq_email` (`Description`)
);

SET @add_alias_index = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'User' AND index_name = 'uniq_alias') = 0,
  'ALTER TABLE `User` ADD UNIQUE KEY `uniq_alias` (`Alias`)',
  'SELECT 1'
);
PREPARE add_alias_index_stmt FROM @add_alias_index;
EXECUTE add_alias_index_stmt;
DEALLOCATE PREPARE add_alias_index_stmt;

SET @add_email_index = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE() AND table_name = 'User' AND index_name = 'uniq_email') = 0,
  'ALTER TABLE `User` ADD UNIQUE KEY `uniq_email` (`Description`)',
  'SELECT 1'
);
PREPARE add_email_index_stmt FROM @add_email_index;
EXECUTE add_email_index_stmt;
DEALLOCATE PREPARE add_email_index_stmt;

CREATE TABLE IF NOT EXISTS `Stock` (
  `idStock` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(45) NULL,
  `LastPrice` DECIMAL(65,2) NULL,
  `Change` DECIMAL(65,2) NULL,
  PRIMARY KEY (`idStock`)
);

CREATE TABLE IF NOT EXISTS `StockInvest` (
  `idInvest` INT NOT NULL AUTO_INCREMENT,
  `idStock` INT NOT NULL,
  `IdUser` INT NOT NULL,
  `Investement` DECIMAL(65,2) NOT NULL,
  `Shares` DECIMAL(65,8) NOT NULL DEFAULT 0,
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

SET @add_stock_shares_column = IF(
  (SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE() AND table_name = 'StockInvest' AND column_name = 'Shares') = 0,
  'ALTER TABLE `StockInvest` ADD COLUMN `Shares` DECIMAL(65,8) NOT NULL DEFAULT 0 AFTER `Investement`',
  'SELECT 1'
);
PREPARE add_stock_shares_column_stmt FROM @add_stock_shares_column;
EXECUTE add_stock_shares_column_stmt;
DEALLOCATE PREPARE add_stock_shares_column_stmt;

UPDATE `StockInvest` si
INNER JOIN `Stock` s ON s.idStock = si.idStock
SET si.Shares = ROUND(si.Investement / s.LastPrice, 8)
WHERE si.Shares = 0
  AND si.Investement > 0
  AND s.LastPrice > 0;

CREATE TABLE IF NOT EXISTS `StockFavorite` (
  `idFavorite` INT NOT NULL AUTO_INCREMENT,
  `idStock` INT NOT NULL,
  `IdUser` INT NOT NULL,
  PRIMARY KEY (`idFavorite`),
  INDEX `FK_idStock1_idx` (`idStock`),
  INDEX `FK_idUser1_idx` (`IdUser`),
  CONSTRAINT `FK_idStock1`
    FOREIGN KEY (`idStock`)
    REFERENCES `Stock` (`idStock`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_idUser1`
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

CREATE TABLE IF NOT EXISTS `Account` (
  `idAccount` INT NOT NULL AUTO_INCREMENT,
  `idUser` INT NOT NULL,
  `AccountNumber` VARCHAR(34) NOT NULL,
  `AccountType` ENUM('checking','savings','investment') NOT NULL DEFAULT 'checking',
  `Status` ENUM('active','frozen','closed') NOT NULL DEFAULT 'active',
  `Currency` CHAR(3) NOT NULL DEFAULT 'CAD',
  `Money` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `OverdraftLimit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `AccountName` VARCHAR(50) NOT NULL,
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ClosedAt` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`idAccount`),
  UNIQUE KEY `uq_account_number` (`AccountNumber`),
  CONSTRAINT `FK_idUser2`
    FOREIGN KEY (`idUser`)
    REFERENCES `User` (`idUser`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `chk_money_overdraft` CHECK (`Money` >= (-1 * `OverdraftLimit`))
);

ALTER TABLE `Account`
  MODIFY `Status` ENUM('actif','Gelée','Fermée','active','frozen','closed') NOT NULL DEFAULT 'active';

UPDATE `Account`
SET `Status` = CASE `Status`
  WHEN 'actif' THEN 'active'
  WHEN 'Gelée' THEN 'frozen'
  WHEN 'Fermée' THEN 'closed'
  ELSE `Status`
END;

ALTER TABLE `Account`
  MODIFY `Status` ENUM('active','frozen','closed') NOT NULL DEFAULT 'active';

CREATE TABLE IF NOT EXISTS `BankTransaction` (
  `idTransaction` INT NOT NULL AUTO_INCREMENT,
  `idUser` INT NOT NULL,
  `TransactionType` ENUM('deposit','withdrawal','purchase','sale') NOT NULL,
  `Description` VARCHAR(255) NOT NULL,
  `Amount` DECIMAL(15,2) NOT NULL,
  `Currency` CHAR(3) NOT NULL DEFAULT 'CAD',
  `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idTransaction`),
  INDEX `idx_bank_transaction_user_date` (`idUser`, `CreatedAt`),
  CONSTRAINT `FK_BankTransaction_User`
    FOREIGN KEY (`idUser`)
    REFERENCES `User` (`idUser`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

USE bdnovabank;

-- 1. Table des Modules (Catégories / Chapitres)
CREATE TABLE IF NOT EXISTS `EducationModule` (
  `idModule` INT NOT NULL AUTO_INCREMENT,
  `ModuleKey` VARCHAR(50) NOT NULL,
  `Title` VARCHAR(150) NOT NULL,
  `Badge` VARCHAR(50) NOT NULL DEFAULT 'Général',
  `Summary` VARCHAR(500) NULL,
  `DisplayOrder` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`idModule`),
  UNIQUE KEY `UQ_EducationModule_Key` (`ModuleKey`)
);

-- 2. Table unique des Questions / Quiz
CREATE TABLE IF NOT EXISTS `EducationQuestion` (
  `idQuestion` INT NOT NULL AUTO_INCREMENT,
  `idModule` INT NOT NULL,
  `Question` VARCHAR(500) NOT NULL,
  `Explanation` TEXT NULL,
  `OptionsJson` JSON NOT NULL, -- Contient les choix et quelle est la bonne réponse
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idQuestion`),
  CONSTRAINT `FK_EducationQuestion_Module`
    FOREIGN KEY (`idModule`)
    REFERENCES `EducationModule` (`idModule`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

-- Insertion d'un module par défaut
INSERT INTO `EducationModule` (`ModuleKey`, `Title`, `Badge`, `Summary`, `DisplayOrder`)
VALUES ('module-1', 'Les bases de la Bourse', 'Débutant', 'Apprenez les principes fondamentaux de l’investissement.', 1)
ON DUPLICATE KEY UPDATE `Title` = VALUES(`Title`);

-- Insertion de questions avec les choix au format JSON
INSERT INTO `EducationQuestion` (`idModule`, `Question`, `Explanation`, `OptionsJson`)
VALUES 
(
  1, 
  'Que représente réellement une action en bourse ?', 
  'Une action est un titre de propriété représentant une fraction du capital de l’entreprise.',
  '[
    {"text": "Un prêt accordé à une entreprise avec intérêts garantis.", "isCorrect": false},
    {"text": "Une fraction du capital et de la propriété d’une entreprise.", "isCorrect": true},
    {"text": "Une garantie que la valeur va doubler.", "isCorrect": false}
  ]'
),
(
  1, 
  'Pourquoi est-il conseillé de diversifier son portefeuille ?', 
  'La diversification permet de réduire l’impact si une entreprise ou un secteur chute.',
  '[
    {"text": "Pour éliminer tout risque sur les marchés.", "isCorrect": false},
    {"text": "Pour réduire l’impact d’une baisse sur une seule action.", "isCorrect": true},
    {"text": "Pour réduire les frais de transaction.", "isCorrect": false}
  ]'
);