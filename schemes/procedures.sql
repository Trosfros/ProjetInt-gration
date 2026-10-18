CREATE DATABASE IF NOT EXISTS bdnovabank;

USE bdnovabank;

DELIMITER //

CREATE PROCEDURE addUser(
    palias VARCHAR(45),
    pdescription VARCHAR(45),
    pPFP BLOB,
    ppassword VARCHAR(255)
)
BEGIN
    IF palias IS NOT NULL AND ppassword IS NOT NULL THEN
        INSERT INTO `User` (Alias, Description, PFP, Password)
        VALUES (palias, pdescription, pPFP, SHA2(ppassword, 256));
    END IF;
END //

CREATE PROCEDURE addStock(
    IN pname VARCHAR(45),
    IN plastprice DECIMAL(45),
    IN pChange DECIMAL(45)
)
BEGIN
    IF pname IS NOT NULL AND plastprice IS NOT NULL AND pChange IS NOT NULL THEN
        
        IF EXISTS (SELECT 1 FROM `Stock` WHERE Name = pname) THEN
           
            UPDATE `Stock`
            SET LastPrice = plastprice,
                `Change` = pChange
            WHERE Name = pname;
        ELSE
            
            INSERT INTO `Stock` (Name, LastPrice, `Change`)
            VALUES (pname, plastprice, pChange);
        END IF;
    END IF;
END //

CREATE PROCEDURE addCrypto(
    pname VARCHAR(45),
    pdescription VARCHAR(45),
    pmarketCap DECIMAL(65,2),
    ptotalSupply INT,
    pmaxSupply INT,
    pcirculatingSupply INT,
    plogo BLOB,
    plaunchDate DATE,
    pwebsite VARCHAR(45),
    pproofType VARCHAR(45)
)
BEGIN
    IF pname IS NOT NULL AND pdescription IS NOT NULL AND pmarketCap IS NOT NULL AND ptotalSupply IS NOT NULL AND pmaxSupply IS NOT NULL AND pcirculatingSupply IS NOT NULL AND plogo IS NOT NULL AND plaunchDate IS NOT NULL AND pwebsite IS NOT NULL AND pproofType IS NOT NULL THEN
        INSERT INTO `Crypto` (
            Name,
            Description,
            marketCap,
            TotalSupply,
            maxSupply,
            CirculatingSupply,
            Logo,
            LaunchDate,
            Website,
            ProofType
        )
        VALUES (
            pname,
            pdescription,
            pmarketCap,
            ptotalSupply,
            pmaxSupply,
            pcirculatingSupply,
            plogo,
            plaunchDate,
            pwebsite,
            pproofType
        );
    END IF;
END //

CREATE PROCEDURE BuyStockInvestment(
    IN pidUser INT,
    IN pidStock INT,
    IN pAmount DECIMAL(65,2)
)
BEGIN
    DECLARE vBalance DECIMAL(65,2);
    DECLARE vPrice DECIMAL(65,2);
    DECLARE vTotal DECIMAL(65,2);
    DECLARE vExisting DECIMAL(65,2);
    IF pidUser IS NULL OR pidStock IS NULL OR pAmount IS NULL OR pAmount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid investment parameters.';
    END IF;

    SELECT Balance
    INTO vBalance
    FROM `User`
    WHERE idUser = pidUser
    FOR UPDATE;

    SELECT LastPrice 
    INTO vPrice
    FROM `Stock`
    WHERE idStock = pidStock
    FOR UPDATE;

    IF vBalance IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User not found.';
    END IF;

    IF vPrice IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock not found or price is invalid.';
    END IF;

    SET vTotal = vPrice * pAmount;

    IF vBalance < vTotal THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Insufficient balance.';
    END IF;

    START TRANSACTION;

    UPDATE `User`
    SET Balance = Balance - vTotal
    WHERE idUser = pidUser;
    
 
    -- Does the user already hold a position in this stock?
    SELECT Investement INTO vExisting
    FROM `StockInvest`
    WHERE idUser = pidUser AND idStock = pidStock
    FOR UPDATE;
 
    IF vExisting IS NULL THEN
        INSERT INTO `StockInvest` (idStock, idUser, Investement)
        VALUES (pidStock, pidUser, vTotal);
    ELSE
        UPDATE `StockInvest`
        SET Investement = Investement + vTotal
        WHERE idUser = pidUser AND idStock = pidStock;
    END IF;
    

    COMMIT;
END //


CREATE PROCEDURE SellStockInvestment(
    IN pidUser INT,
    IN pidStock INT,
    IN pAmount DECIMAL(65,2)
)
BEGIN

    DECLARE vBalance DECIMAL(65,2);
    DECLARE vPrice DECIMAL(65,2);
    DECLARE vTotal DECIMAL(65,2);
    DECLARE vHeld DECIMAL(65,2);

    IF pidUser IS NULL OR pidStock IS NULL OR pAmount IS NULL OR pAmount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid investment parameters.';
    END IF;

    SELECT Balance
    INTO vBalance
    FROM `User`
    WHERE idUser = pidUser
    FOR UPDATE;

    SELECT LastPrice 
    INTO vPrice
    FROM `Stock`
    WHERE idStock = pidStock
    FOR UPDATE;

    IF vBalance IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User not found.';
    END IF;

    IF vPrice IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock not found or price is invalid.';
    END IF;

    SET vTotal = vPrice * pAmount;

    START TRANSACTION;
    -- added a small check that was missing
    SELECT Investement INTO vHeld
    FROM `StockInvest`
    WHERE idUser = pidUser AND idStock = pidStock
    FOR UPDATE;
 
    IF vHeld IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'You do not own any shares of this stock.';
    END IF;
    UPDATE `User`
    SET Balance = Balance + vTotal
    WHERE idUser = pidUser;

    Update StockInvest
    Set Investement = Investement - vTotal
    WHERE idUser = pidUser AND idStock = pidStock;
    DELETE FROM StockInvest 
    where idUser = pidUser AND idStock = pidStock AND Investement <= 0;

    COMMIT;
END //

CREATE PROCEDURE TOGGLE_STOCK_FAVORITE(
    IN pidUser INT,
    IN pidStock INT
)
BEGIN

    IF EXISTS (SELECT 1 FROM `StockFavorite` WHERE idStock = pidStock AND IdUser = pidUser) THEN
        DELETE FROM `StockFavorite` WHERE idStock = pidStock AND IdUser = pidUser;
    ELSE
        INSERT INTO `StockFavorite` (idStock, IdUser) VALUES (pidStock, pidUser);
    END IF;

END //


CREATE PROCEDURE updatePassword(
    IN pidUser INT,
    IN pNewPassword VARCHAR(255)
)
BEGIN
    DECLARE vUserCount INT;
    DECLARE pOldPassword VARCHAR(255);

    SELECT Password INTO pOldPassword
    FROM `User`
    WHERE idUser = pidUser;

    SELECT COUNT(*) INTO vUserCount
    FROM `User`
    WHERE idUser = pidUser
      AND Password = SHA2(pOldPassword, 256);

    IF vUserCount = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid user or old password.';
    END IF;

    UPDATE `User`
    SET Password = SHA2(pNewPassword, 256)
    WHERE idUser = pidUser;
END //


CREATE PROCEDURE GetUserStockInvestments(
    IN pidUser INT,
    IN plastPrice DECIMAL(65,2),
    IN pChange DECIMAL(65,2)
)
BEGIN
    SELECT 
        si.idInvest,
        si.IdUser,
        si.idStock,
        si.Investement AS AmountInvested,
        s.Name AS StockName,
        s.LastPrice,
        s.Change
    FROM `StockInvest` si
    INNER JOIN `Stock` s ON si.idStock = s.idStock
    WHERE si.IdUser = pidUser
      AND (plastPrice IS NULL OR s.LastPrice = plastPrice)
      AND (pChange IS NULL OR s.Change = pChange);
END //


CREATE PROCEDURE updateUserAvatar(
    IN pidUser INT,
    IN pidAvatar INT
)
BEGIN
    UPDATE `User` SET idAvatar = pidAvatar WHERE idUser = pidUser;
END //
 -- this refers to bank accounts not user accounts
CREATE PROCEDURE createAccount(
    in PuserId INT
)
Begin
    INSERT INTO Account (idUser,Money)
    VALUES (PuserId,0);
END //
DROP PROCEDURE deleteAccount;
CREATE PROCEDURE deleteAccount(
    in AccountId INT
)
BEGIN 
    DECLARE accountExists INT;
    DECLARE moneyInAcc DECIMAL(65,2); 
    SELECT COUNT(AccountId) into accountExists FROM Account where idAccount = AccountId;
   

    if accountExists = 0 THEN    
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Account not found.';
    END IF;
     SELECT Money into moneyInAcc FROM Account where AccountId = idAccount;
    IF moneyInAcc != 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'cannot delete account if there is still money in it, please remove it from the account';
    END IF;

    DELETE FROM Account
    WHERE idAccount = AccountId;
END //
Create Procedure AddMoneyToAccount(
    in AccountId INT, 
    in Ammount decimal(65,2)
)
BEGIN
    DECLARE accountExists INT;
    SELECT COUNT(AccountId) into accountExists FROM Account where idAccount = AccountId;
   
    if accountExists = 0 THEN    
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Account not found.';
    END IF;
  
    UPDATE Account
    SET Money = Money + Ammount
    where idAccount = AccountId;
END;
Create Procedure  RemoveMoneyFromAccount(
    in AccountId INT, 
    in Ammount decimal(65,2)
)
BEGIN
    DECLARE accountExists INT;
    DECLARE moneyInAcc DECIMAL(65,2); 
    SELECT COUNT(AccountId) into accountExists FROM Account where idAccount = AccountId;
   
    if accountExists = 0 THEN    
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Account not found.';
    END IF;
    SELECT Money into moneyInAcc FROM Account where AccountId = idAccount;
    if moneyInAcc < Ammount THEN
       SIGNAL SQLSTATE '45000'
       SET MESSAGE_TEXT = 'cannot remove more than what is in the account';
    END IF;   
    
    UPDATE Account
    SET Money = Money - Ammount
    where idAccount = AccountId;
END;

CREATE FUNCTION verifyUser(
    palias VARCHAR(45),
    ppassword VARCHAR(255)
)
RETURNS TINYINT
DETERMINISTIC
BEGIN
    DECLARE userCount INT;

    SELECT COUNT(*) INTO userCount
    FROM `User`
    WHERE Alias = palias
      AND Password = SHA2(ppassword, 256);

    RETURN userCount > 0;
END //


CREATE FUNCTION verifyUserEmail(
    IN pidUser INT,
    IN pToken VARCHAR(255)
)
RETURNS TINYINT
DETERMINISTIC
BEGIN
    DECLARE vMatch INT;
    SELECT COUNT(*) INTO vMatch FROM `User` WHERE idUser = pidUser AND email_verification_token = pToken;
    
    IF vMatch > 0 THEN
        UPDATE `User` SET email_verified = 1, email_verification_token = NULL WHERE idUser = pidUser;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jeton de vérification invalide.';
    END IF;
    RETURN vMatch > 0;
END //




CREATE PROCEDURE GetEducationModules(
    IN pidUser INT
)
BEGIN
    SELECT
        m.idModule,
        m.ModuleKey,
        m.ModuleNumber,
        m.Title,
        m.Badge,
        m.Summary,
        COALESCE(p.IsCompleted, 0) AS IsCompleted,
        COALESCE(p.BestScore, 0.00) AS BestScore,
        COALESCE(p.AttemptCount, 0) AS AttemptCount
    FROM `EducationModule` m
    LEFT JOIN `EducationProgress` p
      ON p.idModule = m.idModule AND p.idUser = pidUser
    WHERE m.IsPublished = 1
    ORDER BY m.DisplayOrder, m.idModule;
END //





CREATE PROCEDURE GetEducationModule(
    IN pidModule INT,
    IN pidUser INT
)
BEGIN
    SELECT idModule, ModuleKey, ModuleNumber, Title, Badge, Summary
    FROM `EducationModule`
    WHERE idModule = pidModule AND IsPublished = 1;

    SELECT idLesson, idModule, Title, Content, LessonOrder
    FROM `EducationLesson`
    WHERE idModule = pidModule
    ORDER BY LessonOrder, idLesson;

    SELECT q.idQuiz, q.idModule, q.Question, q.Explanation,
           o.idOption, o.OptionText, o.OptionOrder
    FROM `EducationQuiz` q
    INNER JOIN `EducationQuizOption` o ON o.idQuiz = q.idQuiz
    WHERE q.idModule = pidModule
    ORDER BY o.OptionOrder, o.idOption;

    SELECT idProgress, idUser, idModule, IsCompleted, BestScore,
           AttemptCount, StartedAt, CompletedAt, UpdatedAt
    FROM `EducationProgress`
    WHERE idUser = pidUser AND idModule = pidModule;
END //




CREATE PROCEDURE StartEducationModule(
    IN pidUser INT,
    IN pidModule INT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM `User` WHERE idUser = pidUser) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User not found.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM `EducationModule` WHERE idModule = pidModule AND IsPublished = 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Education module not found.';
    END IF;

    INSERT INTO `EducationProgress` (idUser, idModule, StartedAt)
    VALUES (pidUser, pidModule, CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE StartedAt = COALESCE(StartedAt, CURRENT_TIMESTAMP);
END //




CREATE PROCEDURE RecordEducationQuizAnswer(
    IN pidUser INT,
    IN pidQuiz INT,
    IN pidOption INT
)
BEGIN
    DECLARE vModule INT;
    DECLARE vCorrect TINYINT;
    DECLARE vAttemptCount INT DEFAULT 0;
    DECLARE vBestScore DECIMAL(5,2) DEFAULT 0.00;

    IF NOT EXISTS (SELECT 1 FROM `User` WHERE idUser = pidUser) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User not found.';
    END IF;

    SELECT q.idModule, o.IsCorrect
      INTO vModule, vCorrect
    FROM `EducationQuiz` q
    INNER JOIN `EducationQuizOption` o ON o.idQuiz = q.idQuiz
    WHERE q.idQuiz = pidQuiz AND o.idOption = pidOption;

    IF vModule IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quiz option not found.';
    END IF;

    INSERT INTO `EducationQuizAttempt` (idUser, idQuiz, idOption, IsCorrect)
    VALUES (pidUser, pidQuiz, pidOption, vCorrect);

    SELECT COALESCE(AttemptCount, 0), COALESCE(BestScore, 0.00)
      INTO vAttemptCount, vBestScore
    FROM `EducationProgress`
    WHERE idUser = pidUser AND idModule = vModule
    FOR UPDATE;

    SET vAttemptCount = vAttemptCount + 1;
    IF vCorrect = 1 AND vBestScore < 100.00 THEN
        SET vBestScore = 100.00;
    END IF;

    INSERT INTO `EducationProgress`
      (idUser, idModule, IsCompleted, BestScore, AttemptCount, StartedAt, CompletedAt)
    VALUES
      (pidUser, vModule, vCorrect, vBestScore, vAttemptCount, CURRENT_TIMESTAMP,
       CASE WHEN vCorrect = 1 THEN CURRENT_TIMESTAMP ELSE NULL END)
    ON DUPLICATE KEY UPDATE
      IsCompleted = GREATEST(IsCompleted, VALUES(IsCompleted)),
      BestScore = GREATEST(COALESCE(BestScore, 0.00), VALUES(BestScore)),
      AttemptCount = VALUES(AttemptCount),
      CompletedAt = CASE
        WHEN IsCompleted = 1 OR VALUES(IsCompleted) = 1 THEN COALESCE(CompletedAt, CURRENT_TIMESTAMP)
        ELSE CompletedAt
      END;
END //

DELIMITER ;
