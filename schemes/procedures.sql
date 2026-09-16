
DELIMITER //
-- does password encryption and adds user to db
Create PROCEDURE bdnovabank.AddUser(
  palias VARCHAR(45),
  pdescription VARCHAR(45),
  pPFP BLOB,
  ppassword VARCHAR(24))
BEGIN
DECLARE encryptedPass VARBINARY(48);
  IF (palias IS NOT NULL AND ppassword IS NOT NULL) THEN 
      SELECT Hashbyte('SHA2-256', ppassword) into encryptedPass;

      INSERT INTO bdnovabank.User (Alias, Description, PFP, Password) VALUES (palias, pdescription, pPFP, encryptedPass);
  END IF;
END;
//

CREATE Procedure bdnovabank.AddStock(
  pname VARCHAR(45),
  plastprice VARCHAR(45),
  pvolume VARCHAR(45))  
Begin 
  IF (pname IS NOT NULL AND plastprice IS NOT NULL AND pvolume IS NOT NULL) THEN 
    INSERT INTO bdnovabank.Stock (Name, LastPrice, Volume) VALUES (pname, plastprice, pvolume);
  END IF;
END;
//

CREATE FUNCTION bdnovabank.verifyUser(
  palias VARCHAR(45),
  ppassword VARCHAR(24))
RETURNS BOOLEAN
BEGIN
  DECLARE encryptedPass VARBINARY(48), userCount INT;
  
  IF (palias IS NOT NULL AND ppassword IS NOT NULL) THEN 
      SELECT Hashbyte('SHA2-256', ppassword) into encryptedPass;

      SELECT COUNT(*) INTO userCount FROM bdnovabank.User WHERE Alias = palias AND Password = encryptedPass;
      
      IF (userCount > 0) THEN
        RETURN TRUE;
      ELSE
        RETURN FALSE;
      END IF;

  END IF;
END; 
//

DELIMITER ;