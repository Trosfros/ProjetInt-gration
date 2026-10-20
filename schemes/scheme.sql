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
  PRIMARY KEY (`idUser`)
);

ALTER TABLE `User`
  ADD UNIQUE KEY `uniq_alias` (`Alias`),
  ADD UNIQUE KEY `uniq_email` (`Description`);

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
  `Status` ENUM('actif','Gelée','Fermée') NOT NULL DEFAULT 'active',
  `Currency` CHAR(3) NOT NULL DEFAULT 'CAD',
  `Money` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `OverdraftLimit` DECIMAL(15,2) NOT NULL DEFAULT 0,
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
  CHECK (`Money` >= -`OverdraftLimit`)
);
CREATE TABLE IF NOT EXISTS `EducationModule` (
  `idModule` INT NOT NULL AUTO_INCREMENT,
  `ModuleKey` VARCHAR(50) NOT NULL,
  `ModuleNumber` SMALLINT UNSIGNED NOT NULL,
  `Title` VARCHAR(150) NOT NULL,
  `Badge` VARCHAR(50) NOT NULL,
  `Summary` VARCHAR(500) NOT NULL,
  `DisplayOrder` SMALLINT UNSIGNED NOT NULL,
  `IsPublished` TINYINT(1) NOT NULL DEFAULT 1,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idModule`),
  UNIQUE KEY `UQ_EducationModule_Key` (`ModuleKey`),
  UNIQUE KEY `UQ_EducationModule_Number` (`ModuleNumber`),
  KEY `IX_EducationModule_Order` (`DisplayOrder`, `IsPublished`)
);


CREATE TABLE IF NOT EXISTS `EducationLesson` (
  `idLesson` INT NOT NULL AUTO_INCREMENT,
  `idModule` INT NOT NULL,
  `Title` VARCHAR(150) NOT NULL,
  `Content` TEXT NOT NULL,
  `LessonOrder` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`idLesson`),
  UNIQUE KEY `UQ_EducationLesson_Order` (`idModule`, `LessonOrder`),
  CONSTRAINT `FK_EducationLesson_Module`
    FOREIGN KEY (`idModule`)
    REFERENCES `EducationModule` (`idModule`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);


CREATE TABLE IF NOT EXISTS `EducationQuiz` (
  `idQuiz` INT NOT NULL AUTO_INCREMENT,
  `idModule` INT NOT NULL,
  `Question` VARCHAR(500) NOT NULL,
  `Explanation` TEXT NOT NULL,
  PRIMARY KEY (`idQuiz`),
  UNIQUE KEY `UQ_EducationQuiz_Module` (`idModule`),
  CONSTRAINT `FK_EducationQuiz_Module`
    FOREIGN KEY (`idModule`)
    REFERENCES `EducationModule` (`idModule`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);



CREATE TABLE IF NOT EXISTS `EducationQuizOption` (
  `idOption` INT NOT NULL AUTO_INCREMENT,
  `idQuiz` INT NOT NULL,
  `OptionText` VARCHAR(500) NOT NULL,
  `IsCorrect` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idOption`),
  UNIQUE KEY `UQ_EducationQuizOption_Order` (`idQuiz`, `OptionOrder`),
  CONSTRAINT `FK_EducationQuizOption_Quiz`
    FOREIGN KEY (`idQuiz`)
    REFERENCES `EducationQuiz` (`idQuiz`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `EducationProgress` (
  `idProgress` INT NOT NULL AUTO_INCREMENT,
  `idUser` INT NOT NULL,
  `idModule` INT NOT NULL,
  `IsCompleted` TINYINT(1) NOT NULL DEFAULT 0,
  `BestScore` DECIMAL(5,2) NULL,
  `AttemptCount` INT UNSIGNED NOT NULL DEFAULT 0,
  `StartedAt` DATETIME NULL,
  `CompletedAt` DATETIME NULL,
  `UpdatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idProgress`),
  UNIQUE KEY `UQ_EducationProgress_UserModule` (`idUser`, `idModule`),
  KEY `IX_EducationProgress_Module` (`idModule`),
  CONSTRAINT `FK_EducationProgress_User`
    FOREIGN KEY (`idUser`)
    REFERENCES `User` (`idUser`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_EducationProgress_Module`
    FOREIGN KEY (`idModule`)
    REFERENCES `EducationModule` (`idModule`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `EducationQuizAttempt` (
  `idAttempt` BIGINT NOT NULL AUTO_INCREMENT,
  `idUser` INT NOT NULL,
  `idQuiz` INT NOT NULL,
  `idOption` INT NOT NULL,
  `IsCorrect` TINYINT(1) NOT NULL,
  `AnsweredAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idAttempt`),
  KEY `IX_EducationQuizAttempt_UserQuiz` (`idUser`, `idQuiz`, `AnsweredAt`),
  CONSTRAINT `FK_EducationQuizAttempt_User`
    FOREIGN KEY (`idUser`)
    REFERENCES `User` (`idUser`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_EducationQuizAttempt_Quiz`
    FOREIGN KEY (`idQuiz`)
    REFERENCES `EducationQuiz` (`idQuiz`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_EducationQuizAttempt_Option`
    FOREIGN KEY (`idOption`)
    REFERENCES `EducationQuizOption` (`idOption`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

INSERT INTO `EducationModule`
  (`ModuleKey`, `ModuleNumber`, `Title`, `Badge`, `Summary`, `DisplayOrder`)
VALUES
  ('module-1', 1, 'Les bases de l’investissement', 'Débutant',
   'Comprendre le fonctionnement des actions, le capital et l’horizon d’investissement.', 1),
  ('module-2', 2, 'Risque et diversification', 'Essentiel',
   'Réduire la volatilité en répartissant son capital sur plusieurs secteurs d’activité.', 2),
  ('module-3', 3, 'Lire une cotation & passer un ordre', 'Pratique',
   'Maîtriser les tickers, le carnet d’ordres et la passation dans le simulateur.', 3)
ON DUPLICATE KEY UPDATE
  `Title` = VALUES(`Title`),
  `Badge` = VALUES(`Badge`),
  `Summary` = VALUES(`Summary`),
  `DisplayOrder` = VALUES(`DisplayOrder`);

INSERT INTO `EducationLesson` (`idModule`, `Title`, `Content`, `LessonOrder`)
SELECT m.idModule, 'Qu’est-ce qu’une action ?',
  'Une action représente une part de propriété d’une société. Quand vous en achetez une, vous devenez actionnaire et profitez potentiellement de la hausse de sa valeur ou de dividendes.', 1
FROM `EducationModule` m WHERE m.ModuleKey = 'module-1'
ON DUPLICATE KEY UPDATE `Title` = VALUES(`Title`), `Content` = VALUES(`Content`);
INSERT INTO `EducationLesson` (`idModule`, `Title`, `Content`, `LessonOrder`)
SELECT m.idModule, 'L’horizon de placement',
  'En bourse, le court terme est volatile. Définir un horizon (moyen ou long terme) évite de paniquer lors des corrections de marché et permet de lisser les variations.', 2
FROM `EducationModule` m WHERE m.ModuleKey = 'module-1'
ON DUPLICATE KEY UPDATE `Title` = VALUES(`Title`), `Content` = VALUES(`Content`);

INSERT INTO `EducationLesson` (`idModule`, `Title`, `Content`, `LessonOrder`)
SELECT m.idModule, 'Le principe de diversification',
  'Ne mettez pas tous vos œufs dans le même panier. Si vous investissez seulement dans la tech et que le secteur chute, tout votre portefeuille encaisse la baisse.', 1
FROM `EducationModule` m WHERE m.ModuleKey = 'module-2'
ON DUPLICATE KEY UPDATE `Title` = VALUES(`Title`), `Content` = VALUES(`Content`);
INSERT INTO `EducationLesson` (`idModule`, `Title`, `Content`, `LessonOrder`)
SELECT m.idModule, 'Le ratio risque / rendement',
  'Un potentiel de rendement élevé implique toujours un risque de perte supérieur. Il n’existe aucun investissement très rentable sans risque.', 2
FROM `EducationModule` m WHERE m.ModuleKey = 'module-2'
ON DUPLICATE KEY UPDATE `Title` = VALUES(`Title`), `Content` = VALUES(`Content`);

INSERT INTO `EducationLesson` (`idModule`, `Title`, `Content`, `LessonOrder`)
SELECT m.idModule, 'Le Ticker (Symbole)',
  'Chaque titre a un symbole boursier unique (ex. : AAPL pour Apple, MSFT pour Microsoft, NVDA pour Nvidia). C’est ce code que vous recherchez sur les marchés.', 1
FROM `EducationModule` m WHERE m.ModuleKey = 'module-3'
ON DUPLICATE KEY UPDATE `Title` = VALUES(`Title`), `Content` = VALUES(`Content`);
INSERT INTO `EducationLesson` (`idModule`, `Title`, `Content`, `LessonOrder`)
SELECT m.idModule, 'Achat vs Vente',
  'Un ordre d’achat consomme votre solde virtuel pour ajouter des titres. Un ordre de vente liquide vos positions et crédite à nouveau votre solde disponible.', 2
FROM `EducationModule` m WHERE m.ModuleKey = 'module-3'
ON DUPLICATE KEY UPDATE `Title` = VALUES(`Title`), `Content` = VALUES(`Content`);

INSERT INTO `EducationQuiz` (`idModule`, `Question`, `Explanation`)
SELECT m.idModule, 'Que représente réellement une action en bourse ?',
  'Une action est un titre de propriété représentant une fraction du capital de l’entreprise.'
FROM `EducationModule` m WHERE m.ModuleKey = 'module-1'
ON DUPLICATE KEY UPDATE `Question` = VALUES(`Question`), `Explanation` = VALUES(`Explanation`);
INSERT INTO `EducationQuiz` (`idModule`, `Question`, `Explanation`)
SELECT m.idModule, 'Pourquoi diversifie-t-on un portefeuille boursier ?',
  'La diversification atténue les risques spécifiques à une entreprise, bien qu’elle ne supprime jamais le risque global du marché.'
FROM `EducationModule` m WHERE m.ModuleKey = 'module-2'
ON DUPLICATE KEY UPDATE `Question` = VALUES(`Question`), `Explanation` = VALUES(`Explanation`);
INSERT INTO `EducationQuiz` (`idModule`, `Question`, `Explanation`)
SELECT m.idModule, 'Si vous achetez 5 actions à 150 $ et les revendez à 180 $, quel est votre gain ?',
  'Gain par action = 180 $ - 150 $ = 30 $. Pour 5 actions : 30 $ x 5 = 150 $ de gain brut.'
FROM `EducationModule` m WHERE m.ModuleKey = 'module-3'
ON DUPLICATE KEY UPDATE `Question` = VALUES(`Question`), `Explanation` = VALUES(`Explanation`);

INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, 'Un prêt accordé à une entreprise avec intérêts garantis.', 0, 1
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-1'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);
INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, 'Une fraction du capital et de la propriété d’une entreprise.', 1, 2
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-1'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);
INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, 'Une promesse que le cours montera obligatoirement.', 0, 3
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-1'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);

INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, 'Pour éliminer 100 % du risque de marché.', 0, 1
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-2'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);
INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, 'Pour réduire l’impact de la chute d’un titre ou d’un secteur isolé.', 1, 2
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-2'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);
INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, 'Pour payer moins de taxes sur les transactions.', 0, 3
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-2'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);

INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, '30 $ de profit au total.', 0, 1
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-3'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);
INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, '150 $ de profit au total (30 $ x 5).', 1, 2
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-3'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);
INSERT INTO `EducationQuizOption` (`idQuiz`, `OptionText`, `IsCorrect`, `OptionOrder`)
SELECT q.idQuiz, '900 $ de profit au total.', 0, 3
FROM `EducationQuiz` q INNER JOIN `EducationModule` m ON m.idModule = q.idModule
WHERE m.ModuleKey = 'module-3'
ON DUPLICATE KEY UPDATE `OptionText` = VALUES(`OptionText`), `IsCorrect` = VALUES(`IsCorrect`);