<?php

/**
 * @package Reporting
 * @category Display
 */
class Transaction_Summary_Report extends Report_Parent
{
	public function __construct(ECash_Transport $transport, $module_name)
	{
		$this->report_title       = "Transaction Summary Report";
		$this->column_names       = array( 'application_status'  => 'Transaction',
		                                   'count'          => 'Count',
		                                   'pct'          => 'PCT',
		                                   'description'	=> 'Description');
	
		$this->column_format	  = array( 'pct'		=>		self::FORMAT_PERCENT);
		$this->column_width		  = array('description'		=>	250);
	/*$this->totals             = array( 'company' => array( 
		                                                       'count'        => Report_Parent::$TOTAL_AS_SUM),
		                                   'grand'   => array( 'rows','count'        => Report_Parent::$TOTAL_AS_SUM));*/
		$this->totals_conditions  = null;
		$this->date_dropdown      = Report_Parent::$DATE_DROPDOWN_RANGE;
		$this->loan_type          = true;
		$this->company_list		  = false;
		$this->download_file_name = null;
		$this->ajax_reporting 	  = true;
		parent::__construct($transport, $module_name);
	}

	
	
		protected function Get_Form_Options_HTML(stdClass &$substitutions)
	{

		$substitutions->report_mode = $_REQUEST["mode"];
		$substitutions->start_date_title  = "
		<script>
		function selectHand(cal, date) {
			cal.sel.value = date;
			var arrdate = cal.sel.value.split('/');
			var el = document.getElementById(cal.frmTarget + 'month');
			el.value = arrdate[0];
			var el = document.getElementById(cal.frmTarget + 'day');
			el.value = arrdate[1];
			var el = document.getElementById(cal.frmTarget + 'year');
			el.value = arrdate[2];
			cal.callCloseHandler();
		}

		function ReportCalendar(target, x, y)
		{
			var el = document.getElementById(target + 'display');
			if (calendar != null)
			{
				calendar.onSelected = selectHand;
				calendar.hide();
				calendar.parseDate(el.value);
			}
			else
			{
				var cal = new Calendar(true, serverdate, selectHand, closeHandler);
				calendar = cal;
				cal.setRange(1900, 2070);
				calendar.create();
				calendar.parseDate(el.value);
			}

			calendar.frmTarget = target;
			calendar.sel = el;
			//calendar.pt_dropdown = pt_dropdown;

			// Don't show *at* the element, b/c the position might be jacked.
			// Show at the cursor location
			calendar.showAt(x, y);

			// Need this to hide the calendar
			Calendar.addEvent(document, 'mousedown', checkCalendar);

			return false;
		}

		</script>
		";

		switch ($this->date_dropdown)
		{
			case self::$DATE_DROPDOWN_SPECIFIC:
				$extra_attribute = 'onClick="ReportCalendar(\'specific_date_\', event.clientX, event.clientY)"';
				$substitutions->start_date_title .= '<span>Date :</span>';
				$substitutions->start_date       = '<span>' . $this->Date_Calander( "specific_date_", "specific", $extra_attribute )
					. ' (<a style="text-decoration: underline;" href="#" ' . $extra_attribute . '>select</a>)</span>';
				$substitutions->end_date_title   = "";
				$substitutions->end_date         = "";
				break;

			case self::$DATE_DROPDOWN_RANGE:
				$extra_attribute = 'onClick="ReportCalendar(\'start_date_\', event.clientX, event.clientY)"';
				$substitutions->start_date_title .= '<span>Start Date :</span>';
				$substitutions->start_date       = '<span style="white-space: nowrap;">' . $this->Date_Calander( "start_date_", "start", $extra_attribute )
					. ' (<a style="text-decoration: underline;" href="#" ' . $extra_attribute . '>select</a>)</span>';

				$extra_attribute = 'onClick="ReportCalendar(\'end_date_\', event.clientX, event.clientY)"';
				$substitutions->end_date_title   = '<span>End Date :</span>';
				$substitutions->end_date         = '<span style="white-space: nowrap;">' . $this->Date_Calander( "end_date_", "end", $extra_attribute )
					. ' (<a style="text-decoration: underline;" href="#" ' . $extra_attribute . '>select</a>)</span>';
				break;

			case self::$DATE_DROPDOWN_NONE:
			default:
				$substitutions->start_date_title = "";
				$substitutions->end_date_title   = "";
				$substitutions->start_date       = "";
				$substitutions->end_date         = "";
				break;
		}

		if( $this->loan_type === true )
		{
			$substitutions->loan_type_select_list  = '<span>Card Type : </span><span><select name="loan_type" size="1" style="width:auto;;"></span>';
			$selected = ($this->search_criteria['loan_type'] === 'all') ? 'selected' : ''; 
			
			$substitutions->loan_type_select_list .= "<option value=\"all\" $selected>All</option>";

			foreach($this->loan_type_list as $loan_type)
			{
				$selected = ($this->search_criteria['loan_type'] === $loan_type['name_short']) ? 'selected' : ''; 
				
				$substitutions->loan_type_select_list .= "<option value=\"{$loan_type['name_short']}\" $selected>{$loan_type['name']}</option>";
			}
			
			$substitutions->loan_type_select_list .= '</select>';
		}

		if( $this->payment_arrange_type === true )
		{
			$substitutions->achtype_select_list  = '<span>Date Search : </span><span><select name="payment_arrange_type" size="1" style="width:100px;"></span>';
			switch( $this->search_criteria['payment_arrange_type'] )
			{
				case 'date_created':
					$substitutions->achtype_select_list .= '<option value="date_created" selected>Created Date</option>';
					$substitutions->achtype_select_list .= '<option value="date_effective">Payment Date</option>';
					break;
				case 'date_effective':
					$substitutions->achtype_select_list .= '<option value="date_created">Created Date</option>';
					$substitutions->achtype_select_list .= '<option value="date_effective" selected>Payment Date</option>';
					break;
				default:
					$substitutions->achtype_select_list .= '<option value="date_created">Created Date</option>';
					$substitutions->achtype_select_list .= '<option value="date_effective">Payment Date</option>';
					break;
			}
			$substitutions->achtype_select_list .= '</select>';
		}

		if( $this->react_type === true)
		{
			$substitutions->react_type_select_list  = '<span>Show: </span><span><select name="react_type" size="1" style="width:95px;"></span>';
			switch( $this->search_criteria['react_type'] )
			{
				case 'yes':
					$substitutions->react_type_select_list .= '<option value="all">All Loans</option>';
					$substitutions->react_type_select_list .= '<option value="yes" selected>Only Reacts</option>';
					$substitutions->react_type_select_list .= '<option value="no">Only New Loans</option>';
					break;
				case 'no':
					$substitutions->react_type_select_list .= '<option value="all">All Loans</option>';
					$substitutions->react_type_select_list .= '<option value="yes">Only Reacts</option>';
					$substitutions->react_type_select_list .= '<option value="no" selected>Only New Loans</option>';
					break;
				case 'all':
					$substitutions->react_type_select_list .= '<option value="all" selected>All Loans</option>';
					$substitutions->react_type_select_list .= '<option value="yes">Only Reacts</option>';
					$substitutions->react_type_select_list .= '<option value="no">Only New Loans</option>';
					break;
				default:
					$substitutions->react_type_select_list .= '<option value="all" selected>All Loans</option>';
					$substitutions->react_type_select_list .= '<option value="yes">Only Reacts</option>';
					$substitutions->react_type_select_list .= '<option value="no">Only New Loans</option>';
					break;
			}
			$substitutions->react_type_select_list .= '</select>';
		}

		if( $this->chargeback_report === true)
		{
			$substitutions->reptype_select_list = '<span>Show: </span><span><select name="chargeback_type" size="1" style="width:150px;"></span>';
			switch( $this->search_criteria['chargeback_type'] )
			{
				case 'all':
					$substitutions->reptype_select_list .= '<option value="all" selected>All</option>';
					$substitutions->reptype_select_list .= '<option value="chargeback">Chargebacks</option>';
					$substitutions->reptype_select_list .= '<option value="chargeback_reversal">Chargeback Reversals</option>';
					break;
				case 'chargeback':
					$substitutions->reptype_select_list .= '<option value="all">All</option>';
					$substitutions->reptype_select_list .= '<option value="chargeback" selected>Chargebacks</option>';
					$substitutions->reptype_select_list .= '<option value="chargeback_reversal">Chargeback Reversals</option>';
					break;
				case 'chargeback_reversal':
					$substitutions->reptype_select_list .= '<option value="all">All</option>';
					$substitutions->reptype_select_list .= '<option value="chargeback">Chargebacks</option>';
					$substitutions->reptype_select_list .= '<option value="chargeback_reversal" selected>Chargeback Reversals</option>';
					break;
				default:
					$substitutions->reptype_select_list .= '<option value="all">All</option>';
					$substitutions->reptype_select_list .= '<option value="chargeback">Chargebacks</option>';
					$substitutions->reptype_select_list .= '<option value="chargeback_reversal">Chargeback Reversals</option>';
					break;
			}
			$substitutions->reptype_select_list .= '</select>';
		}

		if( $this->nsf_type === true)
		{
			$substitutions->achtype_select_list  = '<span>ACH Type: </span><span><select name="achtype" size="1" style="width:95px;"></span>';
			switch( $this->search_criteria['achtype'] )
			{
				case 'debit':
					$substitutions->achtype_select_list .= '<option value="debit" selected>Debit Report</option>';
					$substitutions->achtype_select_list .= '<option value="credit">Credit Report</option>';
					break;
				case 'credit':
					$substitutions->achtype_select_list .= '<option value="debit">Debit Report</option>';
					$substitutions->achtype_select_list .= '<option value="credit" selected>Credit Report</option>';
					break;
				default:
					$substitutions->achtype_select_list .= '<option value="debit">Debit Report</option>';
					$substitutions->achtype_select_list .= '<option value="credit">Credit Report</option>';
					break;
			}
			$substitutions->achtype_select_list .= '</select>';

			$substitutions->reptype_select_list  = '<span>Show: </span><span><select name="reptype" size="1" style="width:225px;"></span>';
			switch( $this->search_criteria['reptype'] )
			{
				case 'nsfper':
					$substitutions->reptype_select_list .= '<option value="nsfper" selected>Reported/Non Reported</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_newloan">Reported/Non Reported (New Loans)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_react">Reported/Non Reported (Reacts)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfperstattypek">Reported/Non Reported by Status type</option>';
					break;
				case 'nsfpercusttypek_newloan':
					$substitutions->reptype_select_list .= '<option value="nsfper">Reported/Non Reported</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_newloan" selected>Reported/Non Reported (New Loans)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_react">Reported/Non Reported (Reacts)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfperstattypek">Reported/Non Reported by Status type</option>';
					break;
				case 'nsfpercusttypek_react':
					$substitutions->reptype_select_list .= '<option value="nsfper">Reported/Non Reported</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_newloan">Reported/Non Reported (New Loans)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_react" selected>Reported/Non Reported (Reacts)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfperstattypek">Reported/Non Reported by Status type</option>';
					break;
				case 'nsfperstattypek':
					$substitutions->reptype_select_list .= '<option value="nsfper">Reported/Non Reported</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_newloan">Reported/Non Reported (New Loans)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_react">Reported/Non Reported (Reacts)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfperstattypek" selected>Reported/Non Reported by Status type</option>';
					break;
				default:
					$substitutions->reptype_select_list .= '<option value="nsfper">Reported/Non Reported</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_newloan">Reported/Non Reported (New Loans)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfpercusttypek_react">Reported/Non Reported (Reacts)</option>';
					$substitutions->reptype_select_list .= '<option value="nsfperstattypek">Reported/Non Reported by Status type</option>';
					break;
			}
			$substitutions->reptype_select_list .= '</select>';
		}

		if( TRUE === $this->agent_list )
		{
			$substitutions->agent_select_list  .= '
				<script language=javascript>
				function SelectAllList(CONTROL){
					for(var i = 0;i < CONTROL.length;i++){
						CONTROL.options[i].selected = true;
					}
				}

				function DeselectAllList(CONTROL){
					for(var i = 0;i < CONTROL.length;i++){
						CONTROL.options[i].selected = false;
					}
				}
				</script>
			';

			$substitutions->agent_select_list  .= '<table cellpadding=0 cellspacing=0><tr><td nowrap>Agent <br>(select multiple)</td><td>';
			$substitutions->agent_select_list  .= '<select id="agent_selector" name="agent_id[]" multiple size="4">';

			$selected = array();
			if(!is_array($this->search_criteria['agent_id']))
			{
				$this->search_criteria['agent_id'] = array();
			}
			foreach($this->search_criteria['agent_id'] as $id)
			{
				$selected[$id] = TRUE;
			}
			if(0 == count($selected))
			{
				$selected[0] = TRUE;
			}

			foreach($this->Get_Agent_List() as $agent_id => $agent_display_name)
			{
				$substitutions->agent_select_list .= ""
					. "<option value=\""
					. htmlentities($agent_id)
					. "\""
					. (isset($selected[$agent_id]) ? " selected=\"selected\"" : "")
					. ">"
					. htmlentities($agent_display_name)
					. "</option>"
					;
			}

			$substitutions->agent_select_list .= "</select>";
			$substitutions->agent_select_list .= '</td><td align=center>';
			$substitutions->agent_select_list .= '<input type=button name="All" value="All" onClick="SelectAllList(this.form.agent_selector)"><br>';
			$substitutions->agent_select_list .= '<input type=button name="None" value="None" OnClick="DeselectAllList(this.form.agent_selector)">';
			$substitutions->agent_select_list .= '</td></tr></table>';
		}

		if( $this->loan_status === true )
		{
			$temp = 'Loan Status : <select name="loan_status_type" size="1" style="width:90px;">';

			asort($this->search_criteria['status_ids']);
			foreach( $this->search_criteria['status_ids'] as $id => $hard_coded_long_name )
			{
				if( isset($this->search_criteria['loan_status_type']) &&
					$this->search_criteria['loan_status_type'] == $id )
					$temp .= "<option value=\"{$id}\" selected>{$hard_coded_long_name}</option>";
				else
					$temp .= "<option value=\"{$id}\">{$hard_coded_long_name}</option>";
			}

			$temp .= '</select>';

			$substitutions->loan_status_select_list = $temp;
		}

		//var_dump($this);
		$substitutions->company_select_list = $this->Company_Dropdown();

		if($this->report_title == "Follow Up Report")
		{
			$all     = "";
			$under   = "";
			$verify  = "";
			$collect = "";
			switch( $this->search_criteria['follow_up_queue'] )
			{
				case 'all':
					$all = "SELECTED";
					break;
				case 'underwriting':
					$under = "SELECTED";
					break;
				case 'verification':
					$verify = "SELECTED";
					break;
				case 'collections':
					$collect = "SELECTED";
					break;
			}
			$substitutions->follow_up_queue_list = <<<EOS
Queue : <select name="follow_up_queue" size="1" style="width:90px;">
<option value="all" {$all}>All</option>
<option value="underwriting" {$under}>Underwriting</option>
<option value="verification" {$verify}>Verification</option>
<option value="collections" {$collect}>Collections</option>
</select>
EOS;
		}


		if($this->report_title == 'Manual Payment Report')
		{
			$all     		= "";
			$credit   		= "";
			$moneygram  	= "";
			$moneyorder 	= "";
			$westernunion 	= "";
			$tier2 			= "";

			switch( $this->search_criteria['payment_type'] )
			{
				case 'all':
					$all = "SELECTED";
					break;
				case 'credit':
					$credit = "SELECTED";
					break;
				case 'moneygram':
					$moneygram = "SELECTED";
					break;
				case 'moneyorder':
					$moneyorder = "SELECTED";
					break;
				case 'westernunion':
					$westernunion = "SELECTED";
					break;
				case 'tier2':
					$tier2 = "SELECTED";
					break;
				case 'debt_consolidation':
					$debt_consolidation = "SELECTED";
					break;
			}
			$substitutions->payment_type_list = <<<EOS
Payment Type : <select name="payment_type" size="1" style="width:90px;">
<option value="all" {$all}>All</option>
<option value="credit" {$credit}>Credit Card</option>
<option value="moneygram" {$moneygram}>Moneygram</option>
<option value="moneyorder" {$moneyorder}>Money Order</option>
<option value="westernunion" {$westernunion}>Western Union</option>
<option value="tier2" {$tier2}>Tier 2 Recovery</option>
<option value="debt_consolidation" {$debt_consolidation}>Debt Consolidation</option>
</select>
EOS;
		}

		if($this->report_title == "Status History Report")
		{
			$confirmed = "";
			$confirmed_followup = "";
			$approved = "";
			$approved_followup = "";
			$pre_fund = "";
			$active = "";
			$funding_failed = "";
			$servicing_hold = "";
			$past_due = "";
			$cashline = "";
			$second_tier_pending = "";
			$second_tier_sent = "";
			$made_arrangements = "";
			$arrangements_failed = "";
			$arrangements_hold = "";
			$bankruptcy_notified = "";
			$bankruptcy_verified = "";
			$collections_contact = "";
			$collections_dequeued = "";
			$qc_ready = "";
			$qc_sent = "";
			$collections_new = "";
			$collections_followup = "";
			$qc_return = "";

			// HACK..
			if($this->search_criteria['status_type'] != NULL)
			{
				$evaled = '$' . $this->search_criteria['status_type'] . ' = "SELECTED";';
				eval($evaled);
			}

			$list  = "Status : <select name='status_type' size='1' style='width:140px;'>\n";
			$list .= "<option value='active' $active>Active</option>\n";
			$list .= "<option value='approved' $approved>Approved</option>\n";
			$list .= "<option value='approved_followup' $approved_followup>Approved Followup</option>\n";
			$list .= "<option value='arrangements_failed' $arrangements_failed>Arrangements Failed</option>\n";
			$list .= "<option value='arrangements_hold' $arrangements_hold>Arrangements Hold</option>\n";
			$list .= "<option value='bankruptcy_notified' $bankruptcy_notified>Bankruptcy Notified</option>\n";
			$list .= "<option value='bankruptcy_verified' $bankruptcy_verified>Bankruptcy Verified</option>\n";
			// CLK only has Cashline IDs
			if(in_array(ECash::getTransport()->company,array("ufc","pcl","ca","d1","ucl","ufc_a")))
			{
				$list .= "<option value='cashline' $cashline>Cashline</option>\n";
			}
			$list .= "<option value='collections_contact' $collections_contact>Collections Contact</option>\n";
			$list .= "<option value='collections_dequeued' $collections_contact>Collections (Dequeued)</option>\n";
			$list .= "<option value='collections_followup' $collections_followup>Collections Followup</option>\n";
			$list .= "<option value='collections_new' $collections_new>Collections New</option>\n";
			$list .= "<option value='confirmed' $confirmed>Confirmed</option>\n";
			$list .= "<option value='confirmed_followup' $confirmed_followup>Confirmed Followup</option>\n";
			$list .= "<option value='funding_failed' $funding_failed>Funding Failed</option>\n";
			$list .= "<option value='made_arrangements' $made_arrangements>Made Arrangements</option>\n";
			$list .= "<option value='past_due' $past_due>Past Due</option>\n";
			$list .= "<option value='pre_fund'$pre_fund>Pre-Fund</option>\n";
			$list .= "<option value='qc_ready' $qc_ready>QC Ready</option>\n";
			$list .= "<option value='qc_sent' $qc_sent>QC Sent</option>\n";
			$list .= "<option value='qc_returned' $qc_returned>QC Returned</option>\n";
			$list .= "<option value='servicing_hold' $servicing_hold>Servicing Hold</option>\n";
			$list .= "<option value='second_tier_pending' $second_tier_pending>Second Tier (Pending)</option>\n";
			$list .= "<option value='second_tier_sent' $second_tier_sent>Second Tier (Sent)</option>\n";
			$list .= "</select>\n";

			$substitutions->status_type_list = $list;
		}

		if($this->queue_dropdown)
		{
			//require_once(SQL_LIB_DIR."/queues.lib.php");
			$selected = $this->search_criteria['queue_name'];

			$list = "Queue : <select name='queue_name' size='1' style='width: 140px;'>";
			foreach(ECash::getConfig()->QUEUE_CONFIG->getAutoQueues() as $queue_name)
			{
				$is_selected = ($selected == $queue_name ? "selected=\"selected\"" : "");
				$queue_name = htmlentities($queue_name);
				$list .= "<option value='$queue_name' $is_selected>$queue_name</option>";
			}
			$list .= "</select>";

			$substitutions->queue_name_list = $list;
		}

		if($this->report_title == "Status Overview Report" || $this->report_title == "Status Group Overview Report" )
		{
			$list = "Status : <select name='status' size='1' style='width:140px;'>\n";
			if($this->report_title == "Status Overview Report")
			{
				foreach($_SESSION['statuses'] as $status )
				{
					$selected = "";
					if(($this->search_criteria['status'] != NULL) && ($this->search_criteria['status'] == $status['id']))
					{
						$selected = "SELECTED";
					}

					if(($status['name'] == 'Approved') || ($status['name'] == 'Collections Contact') || ($status['name'] == 'Confirmed'))
					{
						$name = "{$status['name']} ({$status['name_short']})";
					}
					else
					{
						$name = "{$status['name']}";
					}

					$list .= "<option value='{$status['id']}' $selected>$name</option>\n";
				}
			}
			else if($this->report_title == "Status Group Overview Report")
			{
				$groups_status = array(	"collections" => "Collections",
							"customers" => "Customers",
							"customers" => "Customers",
							"underwriting" => "Underwriting",
							"verification" => "Verification",
							"prospects" => "Prospects",
							"inactive" => "Inactive"
							);
				ksort($groups_status);
				foreach($groups_status as $key_status => $disp_status)
				{
			        $selected = "";

					if(($this->search_criteria['status'] != NULL) && ($this->search_criteria['status'] == $key_status))
					{
	    				$selected = "SELECTED";
					}

					$list .= "<option value='{$key_status}' $selected>$disp_status</option>\n";
				}
			}

			$list .= "</select>\n";
			$substitutions->status_name_list = $list;

			$pos_selected = "";
			$neg_selected = "";
			$zero_selected = "";

			if(($this->search_criteria['balance_type'] != NULL) && ($this->search_criteria['balance_type'] == 'positive'))
			{
				$pos_selected = "SELECTED";
			}
			else if ($this->search_criteria['balance_type'] == 'negative')
			{
				$neg_selected = "SELECTED";
			}
			else if ($this->search_criteria['balance_type'] == 'zero')
			{
				$zero_selected = "SELECTED";
			}

			$list = "Balance Type : <select name='balance_type' size='1' style='width:100px;'>\n";
			$list .= "<option value='positive' $pos_selected>Positive</option>\n";
			$list .= "<option value='negative' $neg_selected>Negative</option>\n";
			$list .= "<option value='zero' $zero_selected>Zero Balance</option>\n";
			$list .= "</select>\n";

			$substitutions->balance_type_list = $list;
		}

		if($this->report_title == "Status History Report")
		{
			$confirmed = "";
			$confirmed_followup = "";
			$approved = "";
			$approved_followup = "";
			$pre_fund = "";
			$active = "";
			$funding_failed = "";
			$servicing_hold = "";
			$past_due = "";
			$cashline = "";
			$second_tier_pending = "";
			$second_tier_sent = "";
			$made_arrangements = "";
			$arrangements_failed = "";
			$arrangements_hold = "";
			$bankruptcy_notified = "";
			$bankruptcy_verified = "";
			$collections_contact = "";
			$qc_ready = "";
			$qc_sent = "";
			$collections_new = "";
			$collections_followup = "";

			// HACK..
			if($this->search_criteria['status_type'] != NULL)
			{
				$evaled = '$' . $this->search_criteria['status_type'] . ' = "SELECTED";';
				eval($evaled);
			}

			$list  = "Status : <select name='status_type' size='1' style='width:140px;'>\n";
			$list .= "<option value='active' $active>Active</option>\n";
			$list .= "<option value='approved' $approved>Approved</option>\n";
			$list .= "<option value='approved_followup' $approved_followup>Approved Followup</option>\n";
			$list .= "<option value='arrangements_failed' $arrangements_failed>Arrangements Failed</option>\n";
			$list .= "<option value='arrangements_hold' $arrangements_hold>Arrangements Hold</option>\n";
			$list .= "<option value='bankruptcy_notified' $bankruptcy_notified>Bankruptcy Notified</option>\n";
			$list .= "<option value='bankruptcy_verified' $bankruptcy_verified>Bankruptcy Verified</option>\n";
			// CLK only has Cashline IDs
			if(in_array(ECash::getTransport()->company,array("ufc","pcl","ca","d1","ucl","ufc_a")))
			{
				$list .= "<option value='cashline' $cashline>Cashline</option>\n";
			}
			$list .= "<option value='collections_contact' $collections_contact>Collections Contact</option>\n";
			$list .= "<option value='collections_followup' $collections_followup>Collections Followup</option>\n";
			$list .= "<option value='collections_new' $collections_new>Collections New</option>\n";
			$list .= "<option value='confirmed' $confirmed>Confirmed</option>\n";
			$list .= "<option value='confirmed_followup' $confirmed_followup>Confirmed Followup</option>\n";
			$list .= "<option value='funding_failed' $funding_failed>Funding Failed</option>\n";
			$list .= "<option value='made_arrangements' $made_arrangements>Made Arrangements</option>\n";
			$list .= "<option value='past_due' $past_due>Past Due</option>\n";
			$list .= "<option value='pre_fund'$pre_fund>Pre-Fund</option>\n";
			$list .= "<option value='qc_ready' $qc_ready>QC Ready</option>\n";
			$list .= "<option value='qc_sent' $qc_sent>QC Sent</option>\n";
			$list .= "<option value='qc_returned' $qc_returned>QC Returned</option>\n";
			$list .= "<option value='servicing_hold' $servicing_hold>Servicing Hold</option>\n";
			$list .= "<option value='second_tier_pending' $second_tier_pending>Second Tier (Pending)</option>\n";
			$list .= "<option value='second_tier_sent' $second_tier_sent>Second Tier (Sent)</option>\n";
			$list .= "</select>\n";

			$substitutions->status_type_list = $list;
		}

		if($this->report_title == "Status Overview Report" || $this->report_title == "Status Group Overview Report" )
		{
			$list = "Status : <select name='status' size='1' style='width:178px;'>\n";

			if($this->report_title == "Status Overview Report")
			{
				foreach($_SESSION['statuses'] as $status )
				{
					$selected = "";
					if(($this->search_criteria['status'] != NULL) && ($this->search_criteria['status'] == $status['id']))
					{
						$selected = "SELECTED";
					}

					if(($status['name'] == 'Approved') || ($status['name'] == 'Collections Contact') || ($status['name'] == 'Confirmed'))
					{
						$name = "{$status['name']} ({$status['name_short']})";
					}
					else
					{
						$name = "{$status['name']}";
					}

					$list .= "<option value='{$status['id']}' $selected>$name</option>\n";
				}
			}
			else if ($this->report_title == "Status Group Overview Report")
			{
				$groups_status = array(	"collections" => "Collections",
							"customers" => "Customers",
							"underwriting" => "Underwriting",
							"verification" => "Verification",
							"prospects" => "Prospects",
							"inactive" => "Inactive"
							);
				ksort($groups_status);
				foreach($groups_status as $key_status => $disp_status)
				{
			        $selected = "";

					if(($this->search_criteria['status'] != NULL) && ($this->search_criteria['status'] == $key_status))
					{
	    				$selected = "SELECTED";
					}

					$list .= "<option value='{$key_status}' $selected>$disp_status</option>\n";
				}
			}
			$list .= "</select>\n";
			$substitutions->status_name_list = $list;

			$pos_selected = "";
			$neg_selected = "";
			$zero_selected = "";

			if(($this->search_criteria['balance_type'] != NULL) && ($this->search_criteria['balance_type'] == 'positive'))
			{
				$pos_selected = "SELECTED";
			}
			else if ($this->search_criteria['balance_type'] == 'negative')
			{
				$neg_selected = "SELECTED";
			}
			else if ($this->search_criteria['balance_type'] == 'zero')
			{
				$zero_selected = "SELECTED";
			}

			$list = "Balance Type : <select name='balance_type' size='1' style='width:100px;'>\n";
			$list .= "<option value='positive' $pos_selected>Positive</option>\n";
			$list .= "<option value='negative' $neg_selected>Negative</option>\n";
			$list .= "<option value='zero' $zero_selected>Zero Balance</option>\n";
			$list .= "</select>\n";

			$substitutions->balance_type_list = $list;
		}
	}
	
	
	
}

?>
