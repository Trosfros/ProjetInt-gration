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
  `TransactionType` ENUM('deposit','withdrawal','purchase','sale','transfer_sent','transfer_received') NOT NULL,
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

CREATE TABLE IF NOT EXISTS `EducationLevelUnlock` (
  `idUser` INT NOT NULL,
  `idModule` INT NOT NULL,
  `UnlockedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idUser`, `idModule`),
  CONSTRAINT `FK_EducationLevelUnlock_User`
    FOREIGN KEY (`idUser`) REFERENCES `User` (`idUser`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_EducationLevelUnlock_Module`
    FOREIGN KEY (`idModule`) REFERENCES `EducationModule` (`idModule`)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `EducationLessonProgress` (
  `idUser` INT NOT NULL,
  `idModule` INT NOT NULL,
  `LessonNumber` SMALLINT UNSIGNED NOT NULL,
  `CompletedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idUser`, `idModule`, `LessonNumber`),
  CONSTRAINT `FK_EducationLessonProgress_User`
    FOREIGN KEY (`idUser`) REFERENCES `User` (`idUser`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_EducationLessonProgress_Module`
    FOREIGN KEY (`idModule`) REFERENCES `EducationModule` (`idModule`)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `EducationQuestionProgress` (
  `idUser` INT NOT NULL,
  `idQuestion` INT NOT NULL,
  `CompletedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idUser`, `idQuestion`),
  CONSTRAINT `FK_EducationQuestionProgress_User`
    FOREIGN KEY (`idUser`) REFERENCES `User` (`idUser`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_EducationQuestionProgress_Question`
    FOREIGN KEY (`idQuestion`) REFERENCES `EducationQuestion` (`idQuestion`)
    ON DELETE CASCADE ON UPDATE CASCADE
);

-- Parcours d’apprentissage classés par niveau
INSERT INTO `EducationModule` (`ModuleKey`, `Title`, `Badge`, `Summary`, `DisplayOrder`)
VALUES
  ('module-1', 'Les bases de l’investissement', 'Facile', 'Comprendre les actions, le rendement et les premiers repères pour investir.', 1),
  ('module-2', 'Risque et diversification', 'Intermédiaire', 'Identifier les risques et apprendre à répartir un portefeuille.', 2),
  ('module-3', 'Lire le marché et maîtriser ses ordres', 'Expert', 'Analyser les mouvements du marché et comprendre les effets des ordres.', 3)
ON DUPLICATE KEY UPDATE
  `Title` = VALUES(`Title`),
  `Badge` = VALUES(`Badge`),
  `Summary` = VALUES(`Summary`),
  `DisplayOrder` = VALUES(`DisplayOrder`);

INSERT INTO `EducationQuestion` (`idModule`, `Question`, `Explanation`, `OptionsJson`)
SELECT m.`idModule`, questions.`Question`, questions.`Explanation`, questions.`OptionsJson`
FROM (
  SELECT
    'module-1' AS `ModuleKey`,
    'Que représente réellement une action en bourse ?' AS `Question`,
    'Une action est un titre de propriété représentant une fraction du capital de l’entreprise.' AS `Explanation`,
    '[{"text":"Un prêt accordé à une entreprise avec intérêts garantis.","isCorrect":false},{"text":"Une fraction du capital et de la propriété d’une entreprise.","isCorrect":true},{"text":"Une garantie que le cours montera.","isCorrect":false}]' AS `OptionsJson`
  UNION ALL SELECT
    'module-1',
    'Pourquoi est-il conseillé de diversifier son portefeuille ?',
    'La diversification réduit l’impact d’une baisse propre à une entreprise ou à un secteur, sans supprimer le risque de marché.',
    '[{"text":"Pour éliminer tout risque de perte.","isCorrect":false},{"text":"Pour limiter l’impact d’une baisse isolée sur le portefeuille.","isCorrect":true},{"text":"Pour garantir un rendement fixe.","isCorrect":false}]'
  UNION ALL SELECT
    'module-1',
    'Que signifie le rendement d’un placement ?',
    'Le rendement mesure le gain ou la perte par rapport au montant investi, sur une période donnée.',
    '[{"text":"Le gain ou la perte rapporté au montant investi.","isCorrect":true},{"text":"Le montant toujours garanti à l’échéance.","isCorrect":false},{"text":"Le nombre d’actions détenues.","isCorrect":false}]'
  UNION ALL SELECT
    'module-2',
    'Que peut-on attendre d’un portefeuille diversifié ?',
    'La diversification répartit le risque spécifique, mais ne protège pas contre une baisse générale des marchés.',
    '[{"text":"Aucune baisse, quelles que soient les conditions du marché.","isCorrect":false},{"text":"Une réduction de l’impact d’un titre ou secteur en difficulté.","isCorrect":true},{"text":"Un rendement supérieur garanti.","isCorrect":false}]'
  UNION ALL SELECT
    'module-2',
    'Un placement promet un rendement élevé sans risque. Quel est le bon réflexe ?',
    'Tout placement comporte des risques. Une promesse de rendement élevé et garanti doit inciter à vérifier soigneusement les informations.',
    '[{"text":"Investir immédiatement avant que l’offre disparaisse.","isCorrect":false},{"text":"Vérifier les risques et se méfier de la promesse garantie.","isCorrect":true},{"text":"Emprunter davantage pour profiter de l’occasion.","isCorrect":false}]'
  UNION ALL SELECT
    'module-2',
    'Pourquoi définir son horizon de placement ?',
    'L’horizon aide à choisir une stratégie cohérente avec ses objectifs et sa capacité à supporter les fluctuations.',
    '[{"text":"Pour savoir quand les marchés vont monter.","isCorrect":false},{"text":"Pour aligner ses choix avec ses objectifs et sa tolérance aux variations.","isCorrect":true},{"text":"Pour supprimer les frais de transaction.","isCorrect":false}]'
  UNION ALL SELECT
    'module-3',
    'Que se passe-t-il lorsqu’un ordre au marché est exécuté ?',
    'Un ordre au marché cherche une exécution rapide au meilleur prix disponible, qui peut différer du dernier cours affiché.',
    '[{"text":"Le prix d’exécution est garanti identique au dernier cours affiché.","isCorrect":false},{"text":"L’ordre est exécuté au meilleur prix disponible, qui peut varier.","isCorrect":true},{"text":"L’ordre attend obligatoirement que le prix choisi soit atteint.","isCorrect":false}]'
  UNION ALL SELECT
    'module-3',
    'Quel est le rôle d’un ordre à cours limité ?',
    'Un ordre à cours limité fixe un prix maximum à l’achat ou minimum à la vente, sans garantir qu’il sera exécuté.',
    '[{"text":"Garantir une exécution immédiate.","isCorrect":false},{"text":"Fixer un prix limite sans garantir l’exécution.","isCorrect":true},{"text":"Empêcher toute perte sur le placement.","isCorrect":false}]'
  UNION ALL SELECT
    'module-3',
    'Une action baisse après une annonce économique. Que faut-il considérer avant d’agir ?',
    'Une variation de cours peut avoir plusieurs causes. Il est utile de revoir ses objectifs, son horizon et sa tolérance au risque avant de décider.',
    '[{"text":"Vendre automatiquement sans examiner la situation.","isCorrect":false},{"text":"Les causes possibles, ses objectifs et son horizon de placement.","isCorrect":true},{"text":"Acheter davantage car le rebond est garanti.","isCorrect":false}]'
) AS questions
INNER JOIN `EducationModule` m ON m.`ModuleKey` = questions.`ModuleKey`
WHERE NOT EXISTS (
  SELECT 1
  FROM `EducationQuestion` existing
  WHERE existing.`idModule` = m.`idModule`
    AND existing.`Question` = questions.`Question`
);