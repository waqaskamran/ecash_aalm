<?php

class Render_Transactions_Table
{
	protected function Get_Amount_Columns($event, $amounts = null)
	{
		if (!isset($amounts))
		{
			$amounts = array("due" => 0.0,
					 "debit" => 0.0,
					 "credit" => 0.0);
		}

		$event->principal = !empty($event->principal) ? $event->principal : 0;
		$event->service_charge = !empty($event->service_charge) ? $event->service_charge : 0;
		$event->fee = !empty($event->fee) ? $event->fee : 0;
		$event->irrecoverable = !empty($event->irrecoverable) ? $event->irrecoverable : 0;

		$event->principal = !empty($event->principal) ? $event->principal : 0;
		$total = $event->principal + $event->service_charge + $event->fee;
		if ($total > 0.0) $amounts['credit'] += $total;
		else $amounts['debit'] -= $total;

		return $amounts;
	}

	protected function Format_Status($event) {
		if (($event->status == 'complete')
		    && (($event->principal_amount > 0) ||
			($event->fee_amount > 0))) {
			return "Applied";
		}

		return(ucwords($event->status));
	}

	protected function Format_Description($event) {
		if (($event->origin_id != null) &&
		    ($event->origin_id != $event->event_schedule_id)) {
			return ($event->event_name . " (Reatt)");
		} 
		else 
		{
			return $event->event_name;
		}

	}

	protected function Format_Date($event, $effective=true)
	{
		if ($event->status == 'scheduled' && $effective)
		{
			return date("m/d/Y", strtotime($event->date_event));
		}
		else
		{
			return date("m/d/Y", strtotime($event->date_effective));
		}
	}

	public function format_schedule($is_transactional_data_read_only, $schedule, $schedule_status = null, $business_rules = null, $current_balance = 0)
	{
		/**
		 * We need to sort the schedule differently based on the service charge model.  The difference 
		 * is when we accrue interest.  For fixed, we accrue interest after the loan disbursement or the
		 * payment.  For daily we wait to accrue it until the payment is due.  Because of this, we have to
		 * order the interest assessment and the interest payment differently otherwise it will look like
		 * things are happening out of order and it will confuse both QA and the customer.
		 * 
		 * This should probably become a Factory requested object in the near future.  If any extra "hacking" is 
		 * required, then it should be rewritten.  [BR]
		 */
		require_once("code/ECash/ScheduleSort.php");
		if(strtolower($business_rules['service_charge']['svc_charge_type']) == 'daily')
		{
			usort($schedule, array("ECash_ScheduleSort", "sortDailyInterest"));
		}
		else
		{
			usort($schedule, array("ECash_ScheduleSort", "sortFixedInterest"));
		}
		
		/**
		 * This could possibly be moved up to where we check for the service charge type, but 
		 * I'm afraid of the repurcussions.
		 */
		$allow_shift_schedule = (isset($business_rules['loan_type_model']) && $business_rules['loan_type_model'] === 'Fixed') ? TRUE : FALSE;

		$status = $schedule_status;
		$total = $current_balance;
		$assocs = array();
		$hidetext = " style=\"visibility: hidden;\"";
		$rowspan_replacements = array();
		$rowspan = 1;
		$i = 0;
		$first_date = null;
		$first_event = null;
		$shift_set = false;
		$found_completed_items = false;
		$disable_date_adjustment = false;
		if(!empty($schedule->total))
			$total = $schedule->total;
		
		if(!empty($schedule_status) && !empty($business_rules))
		{
			if($schedule_status->has_scheduled_reattempts === TRUE
				&& 	isset($business_rules['service_charge']['svc_charge_type']) 
				&& $business_rules['service_charge']['svc_charge_type'] === 'Daily')
			{
				$disable_date_adjustment = true;
			}
		}
		
		foreach ($schedule as $idx => $event)
		{
			if ($event->type == 'converted_sc_event')
			{
				continue;
			}
			elseif (in_array($event->type, array('h_fatal_cashline_return', 'h_nfatal_cashline_return')) &&
					$event->status == 'failed' && empty($event->origin_id))
			{
				continue;
			}
			
			$ht = "";
			$fmt = date("m/d/Y", strtotime($event->date_event));
			$status_fmt = $this->Format_Status($event);
			$amounts = $this->Get_Amount_Columns($event);

			if ($event->status == 'complete') $found_completed_items = true;

			// Update the total
			if (($status_fmt != 'Failed') && (preg_match('/refund_3rd_party/',$event->type) == 0))
			{
				$total -= $amounts['debit'];
				$total += $amounts['credit'];
				$total = round($total, 2);
			}
			
			if ($status_fmt == 'Scheduled')
			{
				if ($first_date == null)
				{
					$first_date = date("m/d/Y", strtotime($event->date_effective));
					$first_event = $fmt;
				}

				if ($first_date != date("m/d/Y", strtotime($event->date_event)) &&
					$first_event != $fmt)
				{
					$ht = $hidetext;
				}

				$date1 = "<td class=\"date\">";
				$effective = strtotime($event->date_effective);
				if (($first_date == date("m/d/Y", $effective))
					&& $effective > time()
				    && (($amounts['debit'] > 0.00) || (isset($event->clearing_type) && $event->clearing_type == 'ach'))
				    && (!$shift_set)
				    && (! $disable_date_adjustment)
				    && ($found_completed_items))
				{
					//Disabling shift schedule because if you want to adjust next payment, we have adjust next payment
					//and if it's a payment arrangement we have edit payment arrangements, getting this to work right with
					//interest down the schedule would take rewriting crap we have already written. [richardb][GF:3655]	
					if($allow_shift_schedule && !$is_transactional_data_read_only)
					{
						$date1 .= "<select id=\"schedule_shift_date\" name=\"schedule_shift_date\" onChange=\"CheckShiftDate(this);\">\n";
						foreach ($status->shift_date_range as $d)
						{
							$d_fmt = date("m/d/Y", strtotime($d));
							$date1 .= "<option ".(($d_fmt==$fmt)?"selected":"")." value=\"{$d}\">";
							$date1 .= $d_fmt;
							$date1 .= "</option>\n";
						}
						$date1 .= "</select>\n";
						$shift_set = true;
					}
					else
					{
						$date1 .= $fmt;
					}
				}
				else
				{
					$date1 .= $fmt;
				}
				$date1 .= "</td>\n";
			}
			else
			{
				$date1 = "<td class=\"date\">{$fmt}</td>\n";
			}
			
			if (($amounts['debit'] > 0.00) &&
			    (($event->date_event != $event->date_effective) || ($event->status == 'pending')))
			{
				$date2 = "<td>".date("m/d/Y", strtotime($event->date_effective))."</td>\n";
			}
			else
			{
				$date2 = "<td>&nbsp;</td>\n";
			}
			$event->status_fmt = $status_fmt;
			$event->date1 = $date1;
			$event->date2 = $date2;
			$event->total = sprintf("%.2f", $total);
			$event->debit = (($amounts['debit'] > 0.00) ? sprintf("%.2f", $amounts['debit']) : "");
			$event->credit = (($amounts['credit'] + $event->irrecoverable > 0.00) ? sprintf("%.2f", $amounts['credit'] + $event->irrecoverable) : "");
			$event->ht = $ht;
		}
	//	$schedule->disable_date_adjustment = $disable_date_adjustment;
	//	$schedule->found_completed_items = $found_completed_items;
		return $schedule;
	}
	
	public function Build_Schedule($schedule, $mode = null)
	{
		$str = '';
		foreach ($schedule as $idx => $event)
		{
			if(empty($event->date_modified_display))
			{
				$event->date_modified_display = date("m/d/y",strtotime((empty($event->date_modified) ? date("m/d/y") : $event->date_modified)));
			}
			if(empty($event->date_effective_display))
			{
				$event->date_effective_display = date("m/d/y",strtotime($event->date_effective));
			}
			$tool_tip_str = 'Principal: ' . (empty($event->principal) ? '0.00' : sprintf("%.2f",$event->principal));
			$tool_tip_str .= '<br>Interest: ' . (empty($event->service_charge)? '0.00' : sprintf("%.2f",$event->service_charge));
			$tool_tip_str .= '<br>Fee: ' . (empty($event->fee) ? '0.00' : sprintf("%.2f",$event->fee)); 
			$tool_tip_str .= '<br>Irrecoverable: ' . (empty($event->irrecoverable) ? '0.00' : sprintf("%.2f",$event->irrecoverable));
			$tool_tip_str .= '<br>Comment: ' . $event->comment;
			$tool_tip_str .= '<br> Date Modified: ' . $event->date_modified_display;
			$tool_tip_str .= '<br> Date Effective: ' . $event->date_effective_display;
			
			$str .= "<tr class=\"transactions_".$event->status."\" {$event->ht} >\n";
			$str .= $event->date1;
			$desc_fmt = $this->Format_Description($event);
			if (!empty($mode) && preg_match('/conversion|account_mgmt|internal|customer_service/', $mode) == 1)
			{
				if (preg_match('/paydown|payout|cancel/',$event->type)) $action = "details&edit=no&";
				else $action = "details&edit=yes&";


				$link = "";

				if ($event->status == 'scheduled')
				{
					$action .= "esid={$event->event_schedule_id}";
					$link .= "<a href=\"#\" onClick=\"OpenTransactionPopup('{$action}',";
				}
				else
				{
					$action .= "trid={$event->transaction_register_id}";
					$link .= "<a href=\"#\" onClick=\"OpenCompletedTransactionPopup('{$action}',";
				}

				$link .= "'Transaction Details','{$mode}');\">{$desc_fmt}</a>";
			}
			else
			{
				$link = $desc_fmt;
			}
			
			$str .= "<td onmouseover=\"return overlib('{$tool_tip_str}', RIGHT, BELOW);\" onmouseout=\"return nd();\">{$link}</td>\n  ";
			$str .= "<td>{$event->status_fmt}</td>\n  ";
			$str .= "<td>". $event->debit ."</td>\n  ";
			$str .= "<td>". $event->credit ."</td>\n  ";
			$str .= "<td>". $event->total ."</td>\n";
			$str .= $event->date2;
			$re = "/payment|full_balance|service_chg|loan_disbursement|refund|refund_3rd_party/";
			if ((isset($event->status_fmt) && (($event->status_fmt == 'Pending') || ($event->status_fmt == 'Scheduled')))
			    && (preg_match($re, $event->type) == 0) && !empty($event->pending_end))
			{
				$end_date = date("m/d/Y", strtotime($event->pending_end));
				$start_date = (($event->status_fmt == 'Scheduled')) ? $event->date_event : date("Y-m-d");
				$days_left = round((strtotime($event->pending_end) - time()) / 86400, 0);

				$add_str = ($days_left == 1) ? "day left" : "days left";
				$str .= "<td onmouseover=\"return overlib('{$days_left} {$add_str}', RIGHT, ABOVE);\" onmouseout=\"return nd();\">{$end_date}</td>\n";
			}
			else
			{
				$str .= "<td>&nbsp;</td>\n";
			}
			$str .= "</tr>\n";
			
		}
		return $str;
	}
	
}
?>
