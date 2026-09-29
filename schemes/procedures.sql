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
    IN plastprice VARCHAR(45),
    IN pChange VARCHAR(45)
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

    INSERT INTO `StockInvest` (idStock, IdUser, Investement)
    VALUES (pidStock, pidUser, vTotal);

    COMMIT;
END;


CREATE PROCEDURE SellStockInvestment(
    IN pidUser INT,
    IN pidStock INT,
    IN pAmount DECIMAL(65,2)
)
Begin

    DECLARE vBalance DECIMAL(65,2);
    DECLARE vPrice DECIMAL(65,2);
    DECLARE vTotal DECIMAL(65,2);

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

    UPDATE `User`
    SET Balance = Balance + vTotal
    WHERE idUser = pidUser;

    INSERT INTO `StockInvest` (idStock, IdUser, Investement)
    VALUES (pidStock, pidUser, -vTotal);

    COMMIT;
end

CREATE PROCEDURE TOGGLE_STOCK_FAVORITE(
    IN pidUser INT,
    IN pidStock INT
)
begin

    IF EXISTS (SELECT 1 FROM `StockFavorite` WHERE idStock = pidStock AND IdUser = pidUser) THEN
        DELETE FROM `StockFavorite` WHERE idStock = pidStock AND IdUser = pidUser;
    ELSE
        INSERT INTO `StockFavorite` (idStock, IdUser) VALUES (pidStock, pidUser);
    END IF;

end //


CREATE PROCEDURE updatePassword(
    IN pidUser INT,
    IN pNewPassword VARCHAR(255)
)
BEGIN
    DECLARE vUserCount INT;
    DECLARE pOldPassword VARCHAR(255);

    Select Password INTO pOldPassword
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


CREATE PROCEDURE updateUserAvatar(
    IN pidUser INT,
    IN pidAvatar INT
)
BEGIN
    UPDATE `User` SET idAvatar = pidAvatar WHERE idUser = pidUser;
END //








DELIMITER ; 






