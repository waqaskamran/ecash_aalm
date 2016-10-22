<?php

require_once(COMMON_LIB_DIR."pay_date_calc.3.php");

/*

That which must be matched

    event_schedule.event_status == 'registered'
    transaction_register.ach_id IS NULL
    transaction_register.ecld_id IS NULL
    transaction_register.transaction_status == 'complete'

That which must be set on changes

    event_schedule.date_modified = NOW()
    event_schedule.context = 'manual'
    transaction_register.date_modified = NOW()
    transaction_register.amount = SUM(event_schedule.amount_*)
    transaction_register.date_effective = event_schedule.date_effective
    transaction_ledger.transaction_type_id = transaction_register.transaction_type_id
    transaction_ledger.amount = transaction_register.amount
    transaction_ledger.date_posted = transaction_register.date_effective

That which the user may edit

    event_schedule.event_type_id
    event_schedule.configuration_trace_data
    event_schedule.amount_principal
    event_schedule.amount_non_principal
    event_schedule.date_event
    event_schedule.date_effective
    transaction_register.transaction_type_id

*/

require_once(COMMON_LIB_DIR."applog.1.php");
require_once(SQL_LIB_DIR."scheduling.func.php");
require_once(COMMON_LIB_DIR."pay_date_calc.3.php");

class dda_adjustments extends dda
{
    public function get_resource_name()
    {
        $return = "Edit account adjustments";

        return($return);
    }

    private function get_event_types($company_id)
    {
        $return = array();
        $return[0] = "--- INVALID ---";

        // $tracking[$return key] = 0-INVALID 1-SAME_DAY
        // 2-NEXT_BUSINESS_DAY
        $tracking = array();

        $db = ECash::getMasterDb();

		// GF #9718: when changing references to $db->quote, it'd be prudent to remove the old quotes. [benb]

        $query = "
            SELECT      `et1`.`event_type_id`
                ,       `et1`.`name`
                ,       `tt1`.`clearing_type`
            FROM        `event_type`        `et1`
            JOIN        `event_transaction` `et2` ON ( `et1`.`event_type_id` = `et2`.`event_type_id` )
            JOIN        `transaction_type`  `tt1` ON ( `et2`.`transaction_type_id` = `tt1`.`transaction_type_id` )
            WHERE       `et1`.`company_id` = ".$db->quote($company_id)."
            ORDER BY    `et1`.`event_type_id` ASC
                ,       `tt1`.`transaction_type_id`
            ";
        $result = $db->query($query);

        while ($row = $result->fetch(PDO::FETCH_ASSOC))
        {
            switch($row['clearing_type'])
            {
                case 'ach':
                case 'quickcheck':
                case 'external':
                    $mode = 2; // Effective next business day
                    break;
                case 'accrued charge':
                case 'adjustment':
                    $mode = 1; // Effective same day
                    break;
                default:
                    $mode = 0; // INVALID
            }

            if(empty($tracking[$row['event_type_id']]))
            {
                $tracking[$row['event_type_id']] = $mode;
            }
            elseif($mode != $tracking[$row['event_type_id']])
            {
                $tracking[$row['event_type_id']] = 0;
            }

            if(0 == $tracking[$row['event_type_id']])
            {
                unset($return[$row['event_type_id']]);
            }
            else
            {
                $return[$row['event_type_id']] = $row['name'];
            }
        }

        return(array($return,$tracking));
    }

    private function calculate_date_effective($date_event_yyyymmdd, $event_type_id, $company_id)
    {
        $event_types = $this->get_event_types($company_id);
        $event_types = $event_types[1];

        if(!isset($event_types[$event_type_id]))
        {
            throw(new Exception("Unknown event_type_id (perhaps from wrong company?)"));
        }
        if(!$date_event_yyyymmdd || FALSE === strtotime($date_event_yyyymmdd))
        {
            throw(new Exception("Invalid date passed"));
        }
        switch($event_types[$event_type_id])
        {
            case 1:
                return($date_event_yyyymmdd);
            case 2:
                $db = ECash::getMasterDb();
                $log = new Applog(APPLOG_SUBDIRECTORY.'/repairs', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT);
				$holidays = Fetch_Holiday_List();
				$pdc = new Pay_Date_Calc_3($holidays);
                $nbd = $pdc->Get_Next_Business_Day($date_event_yyyymmdd);
                return($nbd);
            default:
                throw(new Exception("Unknown tracking number"));
        }
    }

    private function get_transaction_types($company_id)
    {
        $return = array();

        $db = ECash::getMasterDb();

        $query = "
            SELECT      tt1.transaction_type_id     AS      transaction_type_id
                ,       tt1.name                    AS      transaction_name
                ,       et2.event_type_id           AS      event_type_id
            FROM        transaction_type            AS      tt1
                ,       event_transaction           AS      et1
                ,       event_type                  AS      et2
            WHERE       tt1.clearing_type           NOT IN  ('ach','quickcheck')
                AND     tt1.transaction_type_id     =       et1.transaction_type_id
                AND     et1.event_type_id           =       et2.event_type_id
				AND		tt1.company_id				=		{$company_id}
            ORDER BY    tt1.name                    ASC
            ";
        
        $result = $db->query($query);
        while ($row = $result->fetch(PDO::FETCH_ASSOC))
        {
            $return['ttid_to_name'][$row['transaction_type_id']] = $row['transaction_name'];
            $return['ttid_to_etid'][$row['transaction_type_id']] = $row['event_type_id'];
        }

        return($return);
    }

    private function show_search_form()
    {
        $return = "";

        $return .=  "<form id='search_form' style='border: 1px solid #000000;' method='post'>";
        $return .=      "<fieldset>";
        $return .=          "<dt>";
        $return .=              "Event Schedule Id";
        $return .=          "</dt>";
        $return .=          "<dd>";
        $return .=              $this->build_html_form_input('event_schedule_id',isset($this->request->event_schedule_id) ? $this->request->event_schedule_id : null);
        $return .=          "</dd>";
        $return .=          "<dt>";
        $return .=              "<input type='submit' value='Search'>";
        $return .=          "</dt>";
        $return .=      "</fieldset>";
        $return .=  "</form>";

        return($return);
    }

    private function get_before_update(&$history, $esid, $trid, $tlid)
    {
        $db = ECash::getMasterDb();

        $query = "
            SELECT  *
            FROM    event_schedule
            WHERE   event_schedule_id = '".intval($esid)."'
            ";

        $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
        
        $history['before_query']['event_schedule'] = $query;
        $history['before_data']['event_schedule'] = $before;
        $pairs = array();
        foreach($before as $col => $val)
        {
            $pairs[] = "`$col` = " . $db->quote($val);
        }
        $pairs = join(", ",$pairs);
        $query = "
            UPDATE  event_schedule
            SET     $pairs
            WHERE   event_schedule_id = ".intval($esid)."
            ";
        $history['undo_query']['event_schedule'] = $query;
        $history['undo_query']['event_amount'] = array("DELETE FROM event_amount WHERE  event_schedule_id = '".intval($esid)."'");
        
        $amounts = Fetch_Schedule_Amounts($esid);

        foreach ($amounts as $before) 
		{
	        $pairs = array();
	        foreach($before as $col => $val)
	        {
	           	if (in_array($col, array('event_amount_type', 'event_amount_id'))) continue;
	            $pairs[] = "`$col` = " . $db->quote($val);
	        }
	        $pairs = join(", ",$pairs);
	        $query = "
	            INSERT INTO event_amount
	            SET         $pairs
	            ";
	        $history['undo_query']['event_amount'][] = $query;
        }

        $query = "
            SELECT  *
            FROM    transaction_register
            WHERE   transaction_register_id = '".intval($trid)."'
            ";
        $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
        $history['before_query']['transaction_register'] = $query;
        $history['before_data']['transaction_register'] = $before;
        $pairs = array();
        foreach($before as $col => $val)
        {
            $pairs[] = "`$col` = " . $db->quote($val);
        }
        $pairs = join(", ",$pairs);
        $query = "
            UPDATE  transaction_register
            SET     $pairs
            WHERE   transaction_register_id = ".intval($trid)."
            ";
        $history['undo_query']['transaction_register'] = $query;

        $query = "
            SELECT  *
            FROM    transaction_ledger
            WHERE   transaction_ledger_id = '".intval($tlid)."'
            ";
        $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
        $history['before_query']['transaction_ledger'] = $query;
        $history['before_data']['transaction_ledger'] = $before;
        
        $pairs = array();
        foreach($before as $col => $val)
        {
            $pairs[] = "`$col` = " . $db->quote($val);
        }
        $pairs = join(", ",$pairs);
        $query = "
            UPDATE  transaction_ledger
            SET     $pairs
            WHERE   transaction_ledger_id = ".intval($tlid)."
            ";
        $history['undo_query']['transaction_ledger'] = $query;
    }

    private function get_before_delete(&$history, $esid, $trid, $tlid)
    {
        $db = ECash::getMasterDb();

        $query = "
            SELECT  *
            FROM    event_schedule
            WHERE   event_schedule_id = '".intval($esid)."'
            ";
        
        $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
        
        $history['before_query']['event_schedule'] = $query;
        $history['before_data']['event_schedule'] = $before;
        $pairs = array();
        foreach($before as $col => $val)
        {
           	if (in_array($col, array('amount_principal', 'amount_non_principal'))) continue;
            $pairs[] = "`$col` = " . $db->quote($val);
        }
        $pairs = join(", ",$pairs);
        $query = "
            INSERT INTO event_schedule
            SET         $pairs
            ";
        $history['undo_query']['event_schedule'] = $query;
		
        $history['undo_query']['event_amount'] = array("DELETE FROM event_amount WHERE  event_schedule_id = '".intval($esid)."'");
        $amounts = Fetch_Schedule_Amounts($esid);
        foreach ($amounts as $before) 
		{
	        $pairs = array();
	        foreach($before as $col => $val)
	        {
	           	if (in_array($col, array('event_amount_type', 'event_amount_id'))) continue;
	            $pairs[] = "`$col` = " . $db->quote($val);
	        }
	        $pairs = join(", ",$pairs);
	        $query = "
	            INSERT INTO event_amount
	            SET         $pairs
	            ";
	        $history['undo_query']['event_amount'][] = $query;
        }

        $query = "
            SELECT  *
            FROM    transaction_register
            WHERE   transaction_register_id = '".intval($trid)."'
            ";
        $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
        $history['before_query']['transaction_register'] = $query;
        $history['before_data']['transaction_register'] = $before;
        $pairs = array();
        foreach($before as $col => $val)
        {
            $pairs[] = "`$col` = " . $db->quote($val);
        }
        $pairs = join(", ",$pairs);
        $query = "
            INSERT INTO transaction_register
            SET         $pairs
            ";
        $history['undo_query']['transaction_register'] = $query;

        $query = "
            SELECT  *
            FROM    transaction_ledger
            WHERE   transaction_ledger_id = '".intval($tlid)."'
            ";
        $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
        $history['before_query']['transaction_ledger'] = $query;
        $history['before_data']['transaction_ledger'] = $before;
        $pairs = array();
        foreach($before as $col => $val)
        {
            $pairs[] = "`$col` = " . $db->quote($val);
        }
        $pairs = join(", ",$pairs);
        $query = "
            INSERT INTO transaction_ledger
            SET         $pairs
            ";
        $history['undo_query']['transaction_ledger'] = $query;
    }

    private function show_edit_results()
    {
        $return = "";

        if(!isset($this->request->edit) || !$this->request->edit)
        {
            return($return);
        }

        $now = date('Y-m-d H:i:s');

        $history = array();
        $history['action'] = 'edit';
        $history['request'] = $this->request;
        $history['agent_id'] = $this->server->agent_id;
        $history['now'] = $now;

        $server = $this->server;
        $request = $this->request;
		$company_id = $this->request->company_id;

        $transaction_types = $this->get_transaction_types($company_id);		

        $db = ECash::getMasterDb();
        $db->query("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
        $db->query("BEGIN");

        try
        {
            if  (   !isset($this->request->undo)
                ||  !$this->request->undo
                ||  !isset($_SESSION['dda_adjustments']['edited'])
                )
            {
                $esid = $request->event_schedule_id;
                $trid = $request->transaction_register_id;
                $tlid = $request->transaction_ledger_id;
                $ttid = $request->transaction_type_id;
                $etid = $transaction_types['ttid_to_etid'][$ttid];
                $ctd = 'dda: '.$request->configuration_trace_data;
                
		//$ap = $request->amount_principal;
                //$anp = $request->amount_non_principal;
		//mantis:8673
		$ap = $request->p_amount;
                $anp = $request->sc_amount;

                $de = $request->date_event;
                $application_id = $request->application_id;

                if('' == substr($ctd,5))
                {
                    return("<div style='text-align: center; background-color: #FF8888;'>ERROR! You must enter a note</span></div>");
                }
                if(($ap < 0 && $anp > 0) || ($ap > 0 && $anp < 0))
                {
                    return("<div style='text-align: center; background-color: #FF8888;'>ERROR! Principal and non-principal must be the same direction</span></div>");
                }
                if(strtotime(date('Y-m-d 00:00:00')) <= strtotime($de))
                {
                    return("<div style='text-align: center; background-color: #FF8888;'>ERROR! Date must be in the future</span></div>");
                }

                $def = $this->calculate_date_effective($de,$etid, $company_id);
                $this->get_before_update($history, $esid, $trid, $tlid);
                
                $source_map = Get_Source_Map();

                Remove_Schedule_Amounts($esid);
		
		//mantis:8673 - passed $trid
                Record_Event_Amount($application_id, $esid, 'principal', $request->p_amount, 0, $trid);
                Record_Event_Amount($application_id, $esid, 'service_charge', $request->sc_amount, 0, $trid);
                Record_Event_Amount($application_id, $esid, 'fee', $request->f_amount, 0, $trid);
                
                if (isset($request->p_reattempt)) foreach ($request->p_reattempt as $reattempt) 
				{
                	Record_Event_Amount($application_id, $esid, 'principal', $reattempt['amount'], $reattempt['count'], $trid);
				}

                if (isset($request->sc_reattempt)) foreach ($request->sc_reattempt as $reattempt) 
				{
                	Record_Event_Amount($application_id, $esid, 'service_charge', $reattempt['amount'], $reattempt['count'], $trid);
				}

                if (isset($request->f_reattempt)) foreach ($request->f_reattempt as $reattempt) 
				{
                	Record_Event_Amount($application_id, $esid, 'fee', $reattempt['amount'], $reattempt['count'], $trid);
				}

				$query = "
                    UPDATE  event_schedule
                    SET     date_modified = " . $db->quote($now) . "
                        ,   context = 'manual'
                        ,   event_type_id = $etid
                        ,   configuration_trace_data = " . $db->quote($ctd) . "
                        ,   amount_principal = " . $db->quote($ap) . "
                        ,   amount_non_principal = " . $db->quote($anp) . "
                        ,   date_event = " . $db->quote($de) . "
                        ,   date_effective = " . $db->quote($def) . "
                        ,	source_id = " . $db->quote($source_map['dda']) . "
                    WHERE   event_schedule_id = ".intval($esid)."
                    ";
                $result = $db->query($query);
                $history['after_query']['event_schedule'] = $query;
                $history['affected_rows']['event_schedule'] = $result->rowCount();

                $query = "
                    UPDATE  transaction_register
                    SET     date_modified = " . $db->quote($now) . "
                        ,   amount = ".doubleval($ap+$anp)."
                        ,   date_effective = " . $db->quote($def) . "
                        ,   transaction_type_id = " . $db->quote($ttid) . "
                        ,	source_id = " . $db->quote($source_map['dda']) . "
                    WHERE   transaction_register_id = ".intval($trid)."
                    ";
                $result = $db->query($query);
                $history['after_query']['transaction_register'] = $query;
                $history['affected_rows']['transaction_register'] = $result->rowCount();

                $query = "
                    UPDATE  transaction_ledger
                    SET     date_posted = " . $db->quote($def) . "
                        ,   amount = ".doubleval($ap+$anp)."
                        ,   transaction_type_id = " . $db->quote($ttid) . "
                        ,	source_id = " . $db->quote($source_map['dda']) . "
                    WHERE   transaction_ledger_id = ".intval($tlid)."
                    ";
                $result = $db->query($query);
                $history['after_query']['transaction_register'] = $query;
                $history['affected_rows']['transaction_register'] = $result->rowCount();

                $this->save_history($history);

                $db->query("COMMIT");

                $_SESSION['dda_adjustments']['edited'] = $history;
            }
            else
            {
                $esid = $request->event_schedule_id;
                $trid = $request->transaction_register_id;
                $tlid = $request->transaction_ledger_id;

                $this->get_before_update($history, $esid, $trid, $tlid);

                $history['undo'] = TRUE;

                foreach($_SESSION['dda_adjustments']['edited']['undo_query'] as $name => $queries)
                {
                	foreach ((array)$queries as $query) 
                	{
	                    $result = $db->query($query);
                	}
                    $history['after_query'][$name] = $queries;
                }

                $this->save_history($history);

                $db->query("COMMIT");

                $_SESSION['dda_adjustments']['edited'] = $history;
            }
        }
        catch(Exception $e)
        {
            try
            {
                $db->rollBack();
            }
            catch(Exception $e2)
            {
            }

            return("<div style='text-align: center; background-color: #FF8888;'>ERROR! Please tell an administrator:<br><span style='text-align: left;'><pre>".$e->getMessage()."</pre></span></div>");
        }

        $return = "";
        if(!isset($this->request->undo) || !$this->request->undo)
        {
            $return .=  "<form method='post'>\n";
            $return .=      "<div style='text-align: center; background-color: #88FF88; font-weight: bold; padding: 15px;'>\n";
            $return .=      "Changes saved<br>\n";
            $return .=          "<input type='hidden' name='edit'                       value='1'>\n";
            $return .=          "<input type='hidden' name='undo'                       value='1'>\n";
            $return .=          "<input type='hidden' name='event_schedule_id'          value='$esid'>\n";
            $return .=          "<input type='hidden' name='transaction_register_id'    value='$trid'>\n";
            $return .=          "<input type='hidden' name='transaction_ledger_id'      value='$tlid'>\n";
            $return .=          "<input type='hidden' name='company_id'                 value='$company_id'>\n";
            $return .=          "<input type='submit' value='Undo'>\n";
            $return .=      "</div>\n";
            $return .=  "</form>\n";
        }
        else
        {
            $return .=  "<div style='text-align: center; background-color: #FFFF88; font-weight: bold; padding: 15px;'>\n";
            $return .=      "Changes reversed\n";
            $return .=  "</div>\n";
        }

        return($return);
    }

    private function show_delete_results()
    {
        $return = "";

        if(!isset($this->request->delete) || !$this->request->delete)
        {
            return($return);
        }

        $history = array();
        $history['action'] = 'delete';
        $history['request'] = $this->request;
        $history['agent_id'] = $this->server->agent_id;

        $server = $this->server;
        $request = $this->request;

        $db = ECash::getMasterDb();
        $db->query("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
        $db->query("BEGIN");

        try
        {
            if  (   !isset($this->request->undo)
                ||  !$this->request->undo
                ||  !isset($_SESSION['dda_adjustments']['deleted'])
                )
            {
                $esid = $request->event_schedule_id;
                $trid = $request->transaction_register_id;
                $tlid = $request->transaction_ledger_id;

                $this->get_before_delete($history, $esid, $trid, $tlid);

                $query = "
                    DELETE FROM event_amount
                    WHERE       event_schedule_id = ".intval($esid)."
                    ";
                $result = $db->query($query);
                $history['after_query']['event_amount'] = $query;
                $history['affected_rows']['event_amount'] = $result->rowCount();

                $query = "
                    DELETE FROM event_schedule
                    WHERE       event_schedule_id = ".intval($esid)."
                    ";
                $result = $db->query($query);
                
                $history['after_query']['event_schedule'] = $query;
                $history['affected_rows']['event_schedule'] = $result->rowCount();

                $query = "
                    DELETE FROM transaction_register
                    WHERE       transaction_register_id = ".intval($trid)."
                    ";
                $result = $db->query($query);
                $history['after_query']['transaction_register'] = $query;
                $history['affected_rows']['transaction_register'] = $result->rowCount();

                $query = "
                    DELETE FROM transaction_ledger
                    WHERE       transaction_ledger_id = ".intval($tlid)."
                    ";
                $result = $db->query($query);
                $history['after_query']['transaction_register'] = $query;
                $history['affected_rows']['transaction_register'] = $result->rowCount();

                $this->save_history($history);

                $db->commit();

                $_SESSION['dda_adjustments']['deleted'] = $history;
            }
            else
            {
                $history['undo'] = TRUE;

                foreach($_SESSION['dda_adjustments']['deleted']['undo_query'] as $name => $queries)
                {

                	foreach ((array)$queries as $query) 
                	{
	                    $result = $db->query($query);
                	}
                    $history['after_query'][$name] = $queries;
                }

                $this->save_history($history);

                $db->rollBack();

                $_SESSION['dda_adjustments']['deleted'] = $history;
            }
        }
        catch(Exception $e)
        {
            try
            {
                $db->rollBack();
            }
            catch(Exception $e2)
            {
            }

            return("<div style='text-align: center; background-color: #FF8888;'>ERROR! Please tell an administrator:<br><span style='text-align: left;'><pre>".$e->getMessage()."</pre></span></div>");
        }

        $return = "";
        if(!isset($this->request->undo) || !$this->request->undo)
        {
            $return .=  "<form method='post'>";
            $return .=      "<div style='text-align: center; background-color: #88FF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes saved";
            $return .=          "<input type='hidden' name='delete'                     value='1'>";
            $return .=          "<input type='hidden' name='undo'                       value='1'>";
            $return .=          "<input type='hidden' name='event_schedule_id'          value='$esid'>";
            $return .=          "<input type='hidden' name='transaction_register_id'    value='$trid'>";
            $return .=          "<input type='hidden' name='transaction_ledger_id'      value='$tlid'>";
            $return .=          "<input type='submit' value='Undo'>";
            $return .=      "</div>";
            $return .=  "</form>";
        }
        else
        {
            $return .=  "<div style='text-align: center; background-color: #FFFF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes reversed";
            $return .=  "</div>";
        }

        return($return);
    }

    
    private function build_reatt_rows(Array $reatt_arr) {
    	$return = '';
    	
    	foreach ($reatt_arr as $index => $reatt) 
		{
    		$count = $reatt['count'];
    		$amount = $reatt['amount'];
    		$type = $reatt['type'];
    		
    		$return .= <<<END_HTML
					<tr>
						<td style="text-align: left"><strong>{$type}</strong></td>
						<td style="text-align: left"><input name="p_reatt[{$index}][amount]" value="{$amount}" size="5" style="text-align: right;"></td>
						<td>{$count}<input name="p_reatt[{$index}][count]" value="{$amount}" type="hidden" ></td>
					</tr>
END_HTML;
    	}
    	
    	return $return;
    }

    private function build_event_amount_grid() {
    	$return = '';
    	
    	$p_amount = 0;
    	$sc_amount = 0;
    	$f_amount = 0;
    	$p_reatt = array();
    	$sc_reatt = array();
    	$f_reatt = array();
    	if (isset($_SESSION['dda_adjustments']['amounts'])) 
		{
	    	foreach ($_SESSION['dda_adjustments']['amounts'] as $event_amount) 
			{
	    		switch ($event_amount->event_amount_type) 
				{
	    			case 'principal':
	    				if ($event_amount->num_reattempt > 0) 
						{
	    					$p_reatt[] = array('count' => $event_amount->num_reattempt, 'amount' => $event_amount->amount, 'type' => 'Principal');
	    				} 
						else 
						{
	    					$p_amount += $event_amount->amount;
	    				}
	    				break;
	    			case 'service_charge':
	    				if ($event_amount->num_reattempt > 0) 
						{
	    					$sc_reatt[] = array('count' => $event_amount->num_reattempt, 'amount' => $event_amount->amount, 'type' => 'Interest');
	    				} 
						else 
						{
	    					$sc_amount += $event_amount->amount;
	    				}
	    				break;
	    			case 'fee':
	    				if ($event_amount->num_reattempt > 0) 
						{
	    					$f_reatt[] = array('count' => $event_amount->num_reattempt, 'amount' => $event_amount->amount, 'type' => 'Fee');
	    				} 
						else 
						{
	    					$f_amount += $event_amount->amount;
	    				}
	    				break;
	    		}
	    	}
    	}
    	
    	$return = <<<END_HTML
			<table style="border: 1px solid black;" cellspacing="0">
				<thead>
					<tr>
						<th style="text-align: left; width: 150px;">Amount Type</th>
						<th style="text-align: left; width: 75px;">Amount</th>
						<th style="text-align: left; width: 75px;"># Reattempt</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="text-align: left"><strong>Principal</strong></td>
						<td style="text-align: left"><input name="p_amount" value="{$p_amount}" size="5" style="text-align: right;"></td>
						<td>0</td>
					</tr>
					<tr>
						<td style="text-align: left"><strong>Interest</strong></td>
						<td style="text-align: left"><input name="sc_amount" value="{$sc_amount}" size="5" style="text-align: right;"></td>
						<td>0</td>
					</tr>
					<tr>
						<td style="text-align: left"><strong>Fee</strong></td>
						<td style="text-align: left"><input name="f_amount" value="{$f_amount}" size="5" style="text-align: right;"></td>
						<td>0</td>
					</tr>
					{$this->build_reatt_rows($p_reatt)}
					{$this->build_reatt_rows($sc_reatt)}
					{$this->build_reatt_rows($f_reatt)}
				</tbody>
			</table>
END_HTML;
		return $return;
    }
    
    private function show_search_results()
    {
        $return = "";

        if(!isset($this->request->event_schedule_id))
        {
            return($return);
        }
        
        $db = ECash::getMasterDb();
        
        $_SESSION['dda_adjustments']['amounts'] = Fetch_Schedule_Amounts($this->request->event_schedule_id);

        $query = "
            SELECT      es1.event_schedule_id
                ,       tr1.transaction_register_id
                ,       tl1.transaction_ledger_id
                ,       es1.event_type_id
                ,       es1.configuration_trace_data
                ,       es1.amount_principal
                ,       es1.amount_non_principal
                ,       es1.date_event
                ,       tr1.transaction_type_id
                ,		es1.application_id
				,		es1.company_id
            FROM        event_schedule es1
                ,       transaction_register tr1
                ,       transaction_ledger tl1
            WHERE       es1.event_schedule_id = tr1.event_schedule_id
                AND     tr1.transaction_register_id = tl1.transaction_register_id
                AND     es1.event_status = 'registered'
                AND     (tr1.ach_id IS NULL OR tr1.ach_id = 0)
                AND     tr1.transaction_status = 'complete'
                AND     es1.event_schedule_id = " . $db->quote($this->request->event_schedule_id) . "
            ";
        $result = $db->query($query);
        if(1 !== $result->rowCount())
        {
            $return .= "<div style='text-align: center; background-color: #FF8888;'>No such record found or record not editable</div>";

            return($return);
        }

        $result = $result->fetch(PDO::FETCH_ASSOC);

        $transaction_types = $this->get_transaction_types($result['company_id']);
		
        if('dda: ' != substr($result['configuration_trace_data'],0,5))
        {
            $result['configuration_trace_data'] = '';
        }
        else
        {
            $result['configuration_trace_data'] = substr($result['configuration_trace_data'],5);
        }

        $return .=  "<form id='edit_form' style='border: 1px solid #000000;' method='post'>\n";
        $return .=      "<input id='hidden_action' type='hidden' name='edit' value='1'>\n";
        $return .=      "<input type='hidden' name='event_schedule_id' value='".htmlentities($result['event_schedule_id'])."'>\n";
        $return .=      "<input type='hidden' name='application_id' value='".htmlentities($result['application_id'])."'>\n";
        $return .=      "<input type='hidden' name='company_id' value='".htmlentities($result['company_id'])."'>\n";
        $return .=      "<input type='hidden' name='transaction_register_id' value='".htmlentities($result['transaction_register_id'])."'>\n";
        $return .=      "<input type='hidden' name='transaction_ledger_id' value='".htmlentities($result['transaction_ledger_id'])."'>\n";
        $return .=      "<fieldset>\n";
        $return .=          "<dt>\n";
        $return .=              "Transaction Type\n";
        $return .=          "</dt>\n";
        $return .=          "<dd>\n";
        $return .=              $this->build_html_form_select('transaction_type_id',$transaction_types['ttid_to_name'],$result['transaction_type_id']);
        $return .=          "</dd>\n";
        $return .=          "<dt>\n";
        $return .=              "Notes\n";
        $return .=          "</dt>\n";
        $return .=          "<dd>\n";
        $return .=              $this->build_html_form_input('configuration_trace_data',htmlentities($result['configuration_trace_data']));
        $return .=          "</dd>\n";
        $return .=          "<dt>\n";
        $return .=              "Event Amounts\n";
        $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;\n";
        $return .=              "(NOTE: POSITIVE values GIVE the person money)\n";
        $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;\n";
        $return .=              "(NOTE: NEGATIVE values TAKE the person's money)\n";
        $return .=          "</dt>\n";
        $return .=          "<dd>\n";
        $return .=              $this->build_event_amount_grid();
        $return .=          "</dd>\n";
        $return .=          "<dt>\n";
        $return .=              "Date Event\n";
        $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;\n";
        $return .=              "(This is labeled &quot;Action Date&quot; on the payments screen)\n";
        $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;\n";
        $return .=              "(This is the date the transaction is scheduled to be SENT OUT)\n";
        $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;\n";
        $return .=              "(Format: YYYY-MM-DD)\n";
        $return .=          "</dt>\n";
        $return .=          "<dd>\n";
        $return .=              $this->build_html_form_input('date_event',htmlentities($result['date_event']));
        $return .=          "</dd>\n";
        $return .=          "<dt>\n";
        $return .=              "<input type='submit' value='Edit'>\n";
        $return .=              "<input type='button' value='Delete' onClick=\"if(confirm('Are you sure?')) { document.getElementById('hidden_action').name = 'delete'; document.getElementById('edit_form').submit(); } \">\n";
        $return .=          "</dt>\n";
        $return .=      "</fieldset>\n";
        $return .=  "</form>\n";

        return($return);
    }

    private function entry_point()
    {
        if(!isset($this->request->undo))
        {
            unset($_SESSION['dda_adjustments']);
        }

        $return = "";
        $return .= $this->show_search_form();
        $return .= $this->show_edit_results();
        $return .= $this->show_delete_results();
        $return .= $this->show_search_results();

        return($return);
    }

    public function main()
    {
        $result = $this->entry_point();
        $return = new stdClass();
        $return->header = "";
        $return->display = $this->build_dda_table($result);
        ECash::getTransport()->Set_Data($return);
    }
}

?>
