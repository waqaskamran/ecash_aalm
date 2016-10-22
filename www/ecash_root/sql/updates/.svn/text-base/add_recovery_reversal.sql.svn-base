INSERT INTO `event_type` VALUES (NOW(),NOW(),'active',3,0,'ext_recovery_reversal','2nd Tier Recovery Reversal');
SELECT @event_type_id := LAST_INSERT_ID();

INSERT INTO `transaction_type` VALUES (NOW(),NOW(),'active',3,0,'ext_recovery_reversal_princ','Second Tier Recovery Reversal (Principal)','adjustment','yes',0,'complete','business');
SELECT @transaction_type_id__prin := LAST_INSERT_ID();

INSERT INTO `transaction_type` VALUES (NOW(),NOW(),'active',3,0,'ext_recovery_reversal_fees','Second Tier Recovery Reversal (Fees)','adjustment','no',0,'complete','business');
SELECT @transaction_type_id__fees := LAST_INSERT_ID();

INSERT INTO `event_transaction` VALUES (NOW(),NOW(),'active',3,@event_type_id,@transaction_type_id__prin,NULL,NULL,NULL,NULL,NULL);

INSERT INTO `event_transaction` VALUES (NOW(),NOW(),'active',3,@event_type_id,@transaction_type_id__fees,NULL,NULL,NULL,NULL,NULL);
