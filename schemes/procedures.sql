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

DELIMITER ;