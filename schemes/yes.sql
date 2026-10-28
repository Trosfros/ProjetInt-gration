delimiter //
CREATE PROCEDURE AddMoneyToAccount(
    IN pidUser INT,
    IN AccountId INT,
    IN Ammount DECIMAL(15,2)
)
BEGIN
    DECLARE vStatus VARCHAR(10);

    IF Ammount IS NULL OR Ammount <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Amount must be positive.';
    END IF;

    START TRANSACTION;

    SELECT Status INTO vStatus
    FROM `Account`
    WHERE idAccount = AccountId
    FOR UPDATE;

    IF vStatus IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Account not found.';
    END IF;

    IF vStatus != 'actif' THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Account is not active.';
    END IF;

    UPDATE `Account`
    SET Money = Money + Ammount
    WHERE idAccount = AccountId;

    UPDATE User 
    SET Balance = Balance - Ammount
    WHERE idUser = pidUser; 
    
    COMMIT;
END //

CREATE PROCEDURE RemoveMoneyFromAccount(
    IN pidUser INT,
    IN AccountId INT,
    IN Ammount DECIMAL(15,2)
)
BEGIN
    DECLARE vMoney DECIMAL(15,2);
    DECLARE vOverdraftLimit DECIMAL(15,2);
    DECLARE vStatus VARCHAR(10);

    IF Ammount IS NULL OR Ammount <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Amount must be positive.';
    END IF;

    START TRANSACTION;

    SELECT Money, OverdraftLimit, Status INTO vMoney, vOverdraftLimit, vStatus
    FROM `Account`
    WHERE idAccount = AccountId
    FOR UPDATE;

    IF vStatus IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Account not found.';
    END IF;

    IF vStatus != 'actif' THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Account is not active.';
    END IF;

    IF vMoney - Ammount < -vOverdraftLimit THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot remove more than what is available in the account.';
    END IF;

    UPDATE `Account`
    SET Money = Money - Ammount
    WHERE idAccount = AccountId;

    UPDATE User 
    SET Balance = Balance + Ammount
    WHERE idUser = pidUser; 
    
    COMMIT;
END //