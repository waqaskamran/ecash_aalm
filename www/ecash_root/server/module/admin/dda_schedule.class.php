<?php

require_once(COMMON_LIB_DIR."applog.1.php");
require_once(SQL_LIB_DIR."scheduling.func.php");
require_once(COMMON_LIB_DIR."pay_date_calc.3.php");

class dda_validation_exception extends Exception {
}

class dda_schedule extends dda
{
    public function get_resource_name()
    {
        $return = "Edit Scheduled Transactions";

        if(isset($_SESSION['dda_schedule']) && isset($_SESSION['dda_schedule']['id']))
        {
            $return .= ": #".$_SESSION['dda_schedule']['id'];
	    $return .= " of Application " . $_SESSION['dda_schedule']['amounts'][0]->application_id; //mantis:7102
        }

        return($return);
    }

    private function calculate_date_effective($date_event_yyyymmdd, $event_type_id)
    {
        $event_types = $this->get_event_types();
        $event_types = $event_types[1];

        if(!isset($event_types[$event_type_id]))
        {
            throw(new Exception("Unknown event_type_id"));
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
                $log = new Applog(APPLOG_SUBDIRECTORY.'/repairs', APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT);
				$holidays = Fetch_Holiday_List();
				$pdc = new Pay_Date_Calc_3($holidays);
                $nbd = $pdc->Get_Next_Business_Day($date_event_yyyymmdd);
                return($nbd);
            default:
                throw(new Exception("Unknown tracking number"));
        }
    }

    private function process_event_create()
    {
        $return = "";

        if(!isset($this->request->create))
        {
            return($return);
        }

        $history = array();
        $history['action'] = 'create';
        $history['request'] = $this->request;
        $history['agent_id'] = $this->server->agent_id;
        $history['event_schedule_id'] = 0;

        $server = $this->server;
        $request = $this->request;

        $db = ECash::getMasterDb();
        $db->query("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
        $db->beginTransaction();

        try
        {
            if  (   !isset($this->request->undo)
                ||  !$this->request->undo
                ||  !isset($_SESSION['dda_schedule']['created'])
                )
            {
                $c_id = $server->company_id;
                $app_id = $request->application_id;
                $et_id = $request->event_type_id;
                $ctd = 'dda: '.$request->configuration_trace_data;
                $de = $request->date_event;

                if(0 >= $app_id)
                {
                    throw new dda_validation_exception("<div style='text-align: center; background-color: #FF8888;'>ERROR! You must enter an application id</span></div>");
                }
                if('' == substr($ctd,5))
                {
                    throw new dda_validation_exception("<div style='text-align: center; background-color: #FF8888;'>ERROR! You must enter a note</span></div>");
                }
                if(($ap < 0 && $anp > 0) || ($ap > 0 && $anp < 0))
                {
                    throw new dda_validation_exception("<div style='text-align: center; background-color: #FF8888;'>ERROR! Principal and non-principal must be the same direction</span></div>");
                }
                if(strtotime(date('Y-m-d 00:00:00')) > strtotime($de))
                {
                    throw new dda_validation_exception("<div style='text-align: center; background-color: #FF8888;'>ERROR! Date must be in the future</span></div>");
                }

                $def = $this->calculate_date_effective($de,$et_id);

                $new_id = Record_Schedule_Entry($app_id, $et_id, '', '', $de, $def, ctd, 'manual', 'dda');
                $history['event_schedule_id'] = $new_id;
                
                Record_Event_Amount($app_id, $new_id, 'principal', $request->p_amount);
                Record_Event_Amount($app_id, $new_id, 'service_charge', $request->sc_amount);
                Record_Event_Amount($app_id, $new_id, 'fee', $request->f_amount);
              	

                $query = "
                    DELETE FROM event_schedule WHERE event_schedule_id = $new_id
                    ";
                $history['undo_query'][] = $query;
                
                $query = "
                    DELETE FROM event_amount WHERE event_schedule_id = $new_id
                    ";
                $history['undo_query'][] = $query;
                
                $this->save_history($history);

                $db->commit();

                $_SESSION['dda_schedule']['id'] = $new_id;
                $_SESSION['dda_schedule']['created'] = $history;
            }
            else
            {
                $query = "
                    SELECT  *
                    FROM    event_schedule
                    JOIN 	event_amount USING (event_schedule_id)
                    WHERE   event_schedule_id = '".intval($_SESSION['dda_schedule']['id'])."'
                    ";
                $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
                $history['before_query'] = $query;
                $history['before_data'] = $before;

                $queries = $_SESSION['dda_schedule']['created']['undo_query'];
                $rows = 0;
                foreach ((array)$queries as $query) 
                {
	                $result = $db->query($query);
	                $rows += $result->rowCount();
                }
                $history['after_query'] = $queries;
                if(0 == ($history['affected_rows'] = $rows))
                {
                    throw(new Exception("No rows removed"));
                }

                $history['undo_query'] = $_SESSION['dda_schedule']['created']['after_query'];
                $history['undo'] = TRUE;

                $this->save_history($history);

                $db->commit();

                unset($_SESSION['dda_schedule']['created']);
                unset($_SESSION['dda_schedule']['id']);
            }
        }
        catch(dda_validation_exception $e) 
		{
			$db->rollBack();
			return $e->getMessage();
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
            $return .=  "<form>";
            $return .=      "<input type='hidden' name='dda_resource' value='schedule'>";
            $return .=      "<div style='text-align: center; background-color: #88FF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes saved<br>";
            $return .=          "<input type='hidden' name='create' value='1'>";
            $return .=          "<input type='hidden' name='undo' value='1'>";
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

	unset($_SESSION['dda_schedule']['id']); //mantis:4002	

        return($return);
    }

    private function process_event_edit()
    {
        $return = "";

        if(!isset($this->request->edit))
        {
            return($return);
        }

        $history = array();
        $history['action'] = 'edit';
        $history['request'] = $this->request;
        $history['agent_id'] = $this->server->agent_id;
        $history['event_schedule_id'] = $_SESSION['dda_schedule']['id'];

        $server = $this->server;
        $request = $this->request;

        $db = ECash::getMasterDb();
        $db->exec("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
        $db->beginTransaction();

        try
        {
            if  (   !isset($this->request->undo)
                ||  !$this->request->undo
                ||  !isset($_SESSION['dda_schedule']['edited'])
                )
            {
                $et_id = $request->event_type_id;
                $ctd = 'dda: '.$request->configuration_trace_data;
                $ap = $request->amount_principal;
                $anp = $request->amount_non_principal;
                $de = $request->date_event;

                if('' == substr($ctd,5))
                {
                    throw new dda_validation_exception("<div style='text-align: center; background-color: #FF8888;'>ERROR! You must enter a note</span></div>");
                }
                if(($ap < 0 && $anp > 0) || ($ap > 0 && $anp < 0))
                {
                    throw new dda_validation_exception("<div style='text-align: center; background-color: #FF8888;'>ERROR! Principal and non-principal must be the same direction</span></div>");
                }
                if(strtotime(date('Y-m-d 00:00:00')) > strtotime($de))
                {
                    throw new dda_validation_exception("<div style='text-align: center; background-color: #FF8888;'>ERROR! Date must be in the future</span></div>");
                }

                $def = $this->calculate_date_effective($de,$et_id);

                $query = "
                    SELECT  *
                    FROM    event_schedule
                    WHERE   event_schedule_id = '".intval($_SESSION['dda_schedule']['id'])."'
                    ";

                $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
                
                $history['before_query'] = $query;
                $history['before_data'] = $before;
                //$application_id = $before->application_id;
		$application_id = $before['application_id']; //mantis:7102
                
                $amounts = Fetch_Schedule_Amounts($_SESSION['dda_schedule']['id']);

                $pairs = array();
                foreach($before as $col => $val)
                {
                	if (in_array($col, array('amount_principal', 'amount_non_principal'))) continue;
                    $pairs[] = "`$col` = ".$db->quote($val)."";
                }
                $pairs = join(", ",$pairs);
                $query = "
                    UPDATE  event_schedule
                    SET     $pairs
                    WHERE   event_schedule_id = ".$db->quote($_SESSION['dda_schedule']['id'])."
                    ";
                $history['undo_query'][] = $query;
                $history['undo_query'][] = "
                    DELETE FROM event_amount WHERE event_schedule_id = ".$db->quote($_SESSION['dda_schedule']['id']);

                foreach ($amounts as $ea) {
	                $pairs = array();
	                foreach((array)$ea as $col => $val)
	                {
	                	if (in_array($col, array('event_amount_type', 'event_amount_id'))) continue;
	                    $pairs[] = "`$col` = ".$db->quote($val)."";
	                }
	                $pairs = join(", ",$pairs);
	                
                	$history['undo_query'][] = "
	                    INSERT  event_amount
	                    SET     $pairs
                    ";
                }

                $source_map = Get_Source_Map();
                $query = "
                    UPDATE  event_schedule
                    SET     event_type_id = ".$db->quote($et_id)."
                        ,   configuration_trace_data = ".$db->quote($ctd)."
                        ,   date_event = ".$db->quote($de)."
                        ,   date_effective = " . $db->quote($def) . "
                        ,	source_id = ".$db->quote($source_map['dda'])."
                    WHERE   event_schedule_id = ".$db->quote($_SESSION['dda_schedule']['id'])."
                    ";
                $result = $db->query($query);
                $history['after_query'] = $query;
                Remove_Schedule_Amounts($_SESSION['dda_schedule']['id']);
                Record_Event_Amount($application_id, $_SESSION['dda_schedule']['id'], 'principal', $request->p_amount);
                Record_Event_Amount($application_id, $_SESSION['dda_schedule']['id'], 'service_charge', $request->sc_amount);
                Record_Event_Amount($application_id, $_SESSION['dda_schedule']['id'], 'fee', $request->f_amount);
                
                if (isset($request->p_reattempt)) foreach ($request->p_reattempt as $reattempt) 
				{
                	Record_Event_Amount($application_id, $_SESSION['dda_schedule']['id'], 'principal', $reattempt['amount'], $reattempt['count']);
				}

                if (isset($request->sc_reattempt)) foreach ($request->sc_reattempt as $reattempt) 
				{
                	Record_Event_Amount($application_id, $_SESSION['dda_schedule']['id'], 'service_charge', $reattempt['amount'], $reattempt['count']);
				}

                if (isset($request->f_reattempt)) foreach ($request->f_reattempt as $reattempt) 
				{
                	Record_Event_Amount($application_id, $_SESSION['dda_schedule']['id'], 'fee', $reattempt['amount'], $reattempt['count']);
				}

                $this->save_history($history);

                $db->commit();

                $_SESSION['dda_schedule']['edited'] = $history;
            }
            else
            {
                $query = "
                    SELECT  *
                    FROM    event_schedule
                    WHERE   event_schedule_id = '".intval($_SESSION['dda_schedule']['id'])."'
                    ";

                $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
                $history['before_query'] = $query;
                $history['before_data'] = $before;

		if(isset($_SESSION['dda_schedule']['id'])) //mantis:4002
		{
			$pairs = array();
                	foreach($before as $col => $val)
                	{
                    		$pairs[] = "`$col` = ".$db->quote($val)."";
                	}
                	$pairs = join(", ",$pairs);
		}


                $query = "
                    UPDATE  event_schedule
                    SET     $pairs
                    WHERE   event_schedule_id = ".$db->quote($_SESSION['dda_schedule']['id'])."
                    ";
                $history['undo_query'] = $query;
                $history['undo'] = TRUE;

                $queries = $_SESSION['dda_schedule']['edited']['undo_query'];
                $rows = 0;

                foreach ((array)$queries as $query) 
                {
	                $result = $db->query($query);
	                $rows += $db->rowCount();
                }
                $history['after_query'] = $query;
                if(0 == ($history['affected_rows'] = $rows))
                {
                    throw(new Exception("No rows deleted"));
                }

                $this->save_history($history);

                $db->commit();

                $_SESSION['dda_schedule']['edited'] = $history;
            }
        }
        catch(dda_validation_exception $e) 
		{
			$db->rollBack();
			return $e->getMessage();
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
            $return .=  "<form>";
            $return .=      "<input type='hidden' name='dda_resource' value='schedule'>";
            $return .=      "<div style='text-align: center; background-color: #88FF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes saved<br>";
            $return .=          "<input type='hidden' name='edit' value='1'>";
            $return .=          "<input type='hidden' name='undo' value='1'>";
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

	unset($_SESSION['dda_schedule']['id']); //mantis:4002

        return($return);
    }

    private function process_event_delete()
    {
        $return = "";

        if(!isset($this->request->delete))
        {
            return($return);
        }

        $history = array();
        $history['action'] = 'delete';
        $history['request'] = $this->request;
        $history['event_schedule_id'] = $_SESSION['dda_schedule']['id'];
        $history['agent_id'] = $this->server->agent_id;

        $db = ECash::getMasterDb();
        $db->exec("SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ");
        $db->beginTransaction();

        try
        {
            if  (   !isset($this->request->undo)
                ||  !$this->request->undo
                ||  !isset($_SESSION['dda_schedule']['edited'])
                )
            {
                $query = "
                    SELECT  *
                    FROM    event_schedule
                    WHERE   event_schedule_id = '".intval($_SESSION['dda_schedule']['id'])."'
                    ";
                $before = $db->querySingleRow($query, NULL, PDO::FETCH_ASSOC);
                $history['before_query'] = $query;
                $history['before_data'] = $before;

                $query = "
                    DELETE FROM event_schedule
                    WHERE       event_schedule_id = '".intval($_SESSION['dda_schedule']['id'])."'
                    ";
                $after = $db->query($query);
                $history['after_query'] = $query;
                if(0 == ($history['affected_rows'] = $after->rowCount()))
                {
                    throw(new Exception("No rows deleted or restored"));
                }

                $query = "
                    DELETE FROM event_amount
                    WHERE       event_schedule_id = '".intval($_SESSION['dda_schedule']['id'])."'
                    ";
                $st = $db->query($query);
                
                if(0 == ($history['affected_rows'] = $st->rowCount()))
                {
                    throw(new Exception("No rows deleted"));
                }
                $query = "
                    INSERT INTO event_schedule SET
                    ";
                foreach($before as $col => $val)
                {
                    $before[$col] = "`$col` = ".$db->quote($val)."";
                }
                $query .= join(", ", $before);
                $history['undo_query'] = $query;

                $this->save_history($history);


                $db->commit();
                $_SESSION['dda_schedule']['deleted'] = $history;
                unset($_SESSION['dda_schedule']['id']);
            }
            else
            {
                $query = $_SESSION['dda_schedule']['deleted']['undo_query'];
                $before = $db->query($query);
                $history['after_query'] = $query;
                if(0 == ($history['affected_rows'] = $before->rowCount()))
                {
                    throw(new Exception("No rows added"));
                }

                $history['undo_query'] = $_SESSION['dda_schedule']['deleted']['after_query'];
                $history['undo'] = TRUE;

                $this->save_history($history);

                $db->commit();

                $_SESSION['dda_schedule']['id'] = $_SESSION['dda_schedule']['deleted']['event_schedule_id'];
                unset($_SESSION['dda_schedule']['deleted']);
            }
            $_SESSION['dda_schedule']['amounts'] = array();
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
            $return .=      "<div style='text-align: center; background-color: #88FF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes saved<br>";
            $return .=      "</div>";
        }
        else
        {
            $return .=  "<div style='text-align: center; background-color: #FFFF88; font-weight: bold; padding: 15px;'>";
            $return .=      "Changes reversed";
            $return .=  "</div>";
        }

        return($return);
    }

    private function process_changes()
    {
        return  (   $this->process_event_create()
                .   $this->process_event_edit()
                .   $this->process_event_delete()
                );
    }

    private function get_event_types()
    {
        $return = array();
        $return[0] = "--- INVALID ---";

        // $tracking[$return key] = 0-INVALID 1-SAME_DAY
        // 2-NEXT_BUSINESS_DAY
        $tracking = array();

        $db = ECash::getMasterDb();

        $query = "
            SELECT      `et1`.`event_type_id`
                ,       `et1`.`name`
                ,       `tt1`.`clearing_type`
            FROM        `event_type`        `et1`
            JOIN        `event_transaction` `et2` ON ( `et1`.`event_type_id` = `et2`.`event_type_id` )
            JOIN        `transaction_type`  `tt1` ON ( `et2`.`transaction_type_id` = `tt1`.`transaction_type_id` )
            WHERE       `et1`.`company_id` = ".$db->quote($this->server->company_id)."
            ORDER BY    `et1`.`event_type_id` ASC
                ,       `tt1`.`transaction_type_id`
            ";
        $result = $db->query($query);

        while($row = $result->fetch(PDO::FETCH_ASSOC))
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

    private function search_for_id()
    {
        $return = "";

        $db = ECash::getMasterDb();

        if(!isset($_SESSION['dda_schedule']['id']))
        {
            return("");
        }

        $query = "
            SELECT  `es1`.`event_type_id`
                ,   `es1`.`configuration_trace_data`
                ,   `es1`.`amount_principal`
                ,   `es1`.`amount_non_principal`
                ,   `es1`.`date_event`
            FROM    `event_schedule` `es1`
            WHERE   `es1`.`event_schedule_id` = ".$db->quote($_SESSION['dda_schedule']['id'])."
                AND `es1`.`company_id` = ".$db->quote($this->server->company_id)."
                AND `es1`.`event_status` = 'scheduled'
            ";
        $result = $db->query($query);
        $result = $result->fetch(PDO::FETCH_ASSOC);
        
        $_SESSION['dda_schedule']['amounts'] = array();
        if(FALSE === $result)
        {
            unset($_SESSION['dda_schedule']['id']);
	        $_SESSION['dda_schedule']['amounts'] = array();
            return("<div style='text-align: center; background-color: #FF8888;'>No such id found</div>");
        }

        $_SESSION['dda_schedule']['amounts'] = Fetch_Schedule_Amounts($_SESSION['dda_schedule']['id']);
        if('dda: ' != substr($result['configuration_trace_data'],0,5))
        {
            $result['configuration_trace_data'] = '';
        }
        else
        {
            $result['configuration_trace_data'] = substr($result['configuration_trace_data'],5);
        }

        foreach($result as $key => $value)
        {
            $_SESSION['dda_schedule'][$key] = $value;
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
    	if (isset($_SESSION['dda_schedule']['amounts'])) 
		{
	    	foreach ($_SESSION['dda_schedule']['amounts'] as $event_amount) 
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
    
    private function show_entry_form()
    {
        $return = "";

        $event_types = $this->get_event_types();
        $event_types = $event_types[0];

        $return .= $this->search_for_id();

        if(!isset($_SESSION['dda_schedule']['id']))
        {
            $return .=  "<form style='border: 1px solid #000000;'>";
            $return .=      "<input type='hidden' name='dda_resource' value='schedule'>";
            $return .=      "<input type='hidden' name='create' value='1'>";
            $return .=      "<div style='text-align: center; font-weight: bold; text-decoration: underline;'>";
            $return .=          "Create New Entry";
            $return .=      "</div>";
            $return .=      "<fieldset>";
            $return .=          "<dt>";
            $return .=              "Application Id";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              "<input type='text' name='application_id' value=''>";
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Event Type";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_html_form_select('event_type_id',$event_types,0);
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Note";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(required)";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              "<input type='text' name='configuration_trace_data' value=''>";
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Event Amounts";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(NOTE: POSITIVE values GIVE the person money)";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(NOTE: NEGATIVE values TAKE the person's money)";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_event_amount_grid();
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Event Date";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(This is labeled &quot;Action Date&quot; on the payments screen)";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(This is the date the transaction is scheduled to be SENT OUT)";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(Format: YYYY-MM-DD)";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              "<input type='text' name='date_event' value=''>";
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "<input type='submit' value='Create New Event'>";
            $return .=          "</dt>";
            $return .=      "</fieldset>";
            $return .=  "</form>";
        }
        else
        {
            $return .=  "<form id='edit_form' style='border: 1px solid #000000;'>";
            $return .=      "<input type='hidden' name='dda_resource' value='schedule'>";
            $return .=      "<input id='hidden_action' type='hidden' name='edit' value='1'>";
            $return .=      "<div style='text-align: center; font-weight: bold; text-decoration: underline;'>";
            $return .=          "Edit Entry";
            $return .=      "</div>";
            $return .=      "<fieldset>";
            $return .=          "<dt>";
            $return .=              "Event Type";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_html_form_select('event_type_id',$event_types,$_SESSION['dda_schedule']['event_type_id']);
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Note";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(required)";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_html_form_input('configuration_trace_data',$_SESSION['dda_schedule']['configuration_trace_data']);
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Event Amounts";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(NOTE: POSITIVE values GIVE the person money)";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(NOTE: NEGATIVE values TAKE the person's money)";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_event_amount_grid();
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "Event Date";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(This is labeled &quot;Action Date&quot; on the payments screen)";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(This is the date the transaction is scheduled to be SENT OUT)";
            $return .=              "<br>&nbsp;&nbsp;&nbsp;&nbsp;";
            $return .=              "(Format: YYYY-MM-DD)";
            $return .=          "</dt>";
            $return .=          "<dd>";
            $return .=              $this->build_html_form_input('date_event',$_SESSION['dda_schedule']['date_event']);
            $return .=          "</dd>";
            $return .=          "<dt>";
            $return .=              "<input type='submit' value='Save Changes'>";
            $return .=              "<input type='button' value='Delete This Record' onClick=\"if(confirm('Are you sure? The record cannot be restored.')) { document.getElementById('hidden_action').name = 'delete'; document.getElementById('edit_form').submit(); } \">";
            $return .=          "</dt>";
            $return .=      "</fieldset>";
            $return .=  "</form>";
        }

        return($return);
    }

    private function select_id()
    {
        $return = "";

        if(isset($this->request->id))
        {
            if($this->request->id)
            {
                $_SESSION['dda_schedule']['id'] = $this->request->id;
            }
            else
            {
                unset($_SESSION['dda_schedule']['id']);
            }
        }

        $return .=  "<form style='border: 1px solid #000000;'>";
        $return .=      "<input type='hidden' name='dda_resource' value='schedule'>";
        $return .=      $this->build_html_form_input( "id", (isset($_SESSION['dda_schedule']['id'])) ? strval($_SESSION['dda_schedule']['id']) : null );
        $return .=      "<input type='submit' value='Search for Event Schedule Id'>";
        $return .=  "</form>";

        $return .= $this->process_changes();
        $return .= $this->show_entry_form();

        return($return);
    }

    public function main()
    {
        if(!isset($this->request->undo))
        {
            unset($_SESSION['dda_schedule']['created']);
            unset($_SESSION['dda_schedule']['edited']);
            unset($_SESSION['dda_schedule']['deleted']);
        }

        $result = $this->select_id();
        $return = new stdClass();
        $return->header = "";
        $return->display = $this->build_dda_table($result);
        ECash::getTransport()->Set_Data($return);
    }
}

?>
