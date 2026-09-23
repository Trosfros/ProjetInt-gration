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
    pname VARCHAR(45),
    plastprice VARCHAR(45),
    pvolume VARCHAR(45)
)
BEGIN
    IF pname IS NOT NULL AND plastprice IS NOT NULL AND pvolume IS NOT NULL THEN
        INSERT INTO `Stock` (Name, LastPrice, Volume)
        VALUES (pname, plastprice, pvolume);
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


CREATE PROCEDURE addStockInvestment(
    pidStock INT,
    pidUser INT,
    pinvestement DECIMAL(65,2)
)
BEGIN
    IF pidStock IS NOT NULL AND pidUser IS NOT NULL AND pinvestement IS NOT NULL AND pinvestement > 0 THEN
        INSERT INTO `StockInvest` (idStock, IdUser, Investement)
        VALUES (pidStock, pidUser, pinvestement);
    END IF;
END //



CREATE PROCEDURE addCryptoInvestment(
    pidCrypto INT,
    pidUser INT,
    pinvestement DECIMAL(65,2)
)
BEGIN
    IF pidCrypto IS NOT NULL AND pidUser IS NOT NULL AND pinvestement IS NOT NULL AND pinvestement > 0 THEN
        INSERT INTO `CryptoInvest` (idCrypto, IdUser, Investement)
        VALUES (pidCrypto, pidUser, pinvestement);
    END IF;
END //



CREATE FUNCTION verifyUser(
    palias VARCHAR(45),
    ppassword VARCHAR(255)
)
RETURNS TINYINT
BEGIN
    DECLARE userCount INT;

    SELECT COUNT(*) INTO userCount
    FROM `User`
    WHERE Alias = palias
      AND Password = SHA2(ppassword, 256);

    RETURN userCount > 0;
END //
CREATE 
DELIMITER ; 

