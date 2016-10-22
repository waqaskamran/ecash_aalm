<?php
    set_time_limit(0);
    /**
    * This seems to be the basic IDV+CRA+TLT+ETC. response
    * @author Andrew Minerd <andrew.minerd@sellingsource.com>
    */
    class ECash_Clarity_Responses_Perf extends Clarity_UW_Response implements Clarity_UW_IPerformanceResponse
    {
        const LOAN_AMOUNT_INCREASE = 'INCREASE';
    
        public function isValid()
        {
            return $this->getDecision() == 'Y';
        }
    
        public function getDecision()
        {
            // we translate the CL result 'Approve' & 'Deny' to dataX 'Y' and 'N'
            if ($this->findNode('/xml-response/clear-products-request/action') == 'Approve') return 'Y';
            else return 'N';
        }
    
        public function getScore()
        {
            return $this->findNode('/xml-response/clear-subprime-idfraud/clear-subprime-idfraud-score');
        }
    
        public function getDecisionBuckets()
        {
            return $this->getGlobalDecisionBuckets();
        }
    
        public function getReportingBuckets()
        {
            return $this->getGlobalReportingBuckets();
        }
    
        public function isIDVFailure()
        {
            $result = $this->findNode('/xml-response/clear-subprime-idfraud/action');
            return $result == 'Deny';
        }
    
        public function getLoanAmountDecision()
        {
            $decision = $this->findNode('/xml-response/clear-payday-tradeline/supplier-payday-tradeline/summary-matrix-payday-tradelines/number-open-lines/');
            if(is_numeric($decision) && $decision == 0)
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }
    
        /**
         * Used to determine whether or not the loan should be auto funded
         */
        public function getAutoFundDecision()
        {
            return $this->isValid();
        }
    
        public function update_bureau_xml_fields($db, $application_id, $bureau_inquiry_id = 0, $bureau_inquiry_failed_id = 0) {
            $record_data = $this->getReportingBuckets();
            $record_data['AutoFund'] = 'N';
            if($this->getLoanAmountDecision()) {
                    $record_data['AutoFund'] = 'Y';
            } elseif($this->getAutoFundDecision()) {
                    $record_data['AutoFund'] = 'Auto';
            }

            $row_fields = array_keys($record_data);
            foreach ($row_fields as $field) {
                    $sql = "INSERT INTO bureau_xml_fields (`application_id`, `bureau_inquiry_id`, ".
                           "`bureau_inquiry_failed_id`, `fieldname`, `value`) VALUES (".$application_id.", ".
                           $bureau_inquiry_id.", ".$bureau_inquiry_failed_id.", '".$field."', '".$record_data[$field]."')";
                    $statement =  $db->prepare($sql);
                    $statement->execute();
            }
        }
    }

?>


