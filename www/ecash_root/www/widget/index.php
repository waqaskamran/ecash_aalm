<!-- vim: set ts=8: -->
<style type="text/css">
	/*DIV { 
		margin:		0px;
		padding:	1px;
		border:		1px dashed Red;
		font-family:	Arial, Helvetica;
		font-size:	x-small;
	}*/
	DIV {
		border:		0px solid Red;
	}
	.center {
		text-align:	center;
	}
	.vc { /* vertically centered */
		vertical-align:	middle;
	}
	.b {
		font-weight:	bold;
	}
	.tbl {
		width:		auto;
		padding:	0px;
		margin:		0px;
		border:		0px;
	}
	.col {
		width:		50%;
	}
	.suboption {
		/*border:		1px solid #CCCCCC;*/
		margin:		5px;
		padding:	10px;
	}
	/*
	.tbl {
		display:	table;
		width:		100%;
	}
	.col {
		display:	table-cell;
		border:		0px dashed Green;
		padding:	3px;
	}
	.row {
		display:	table-row;
	}
	*/
	.r {
		text-align:	right;
	}
	.example {
		font-style:	italic;
	}
	.bgl {
		background-color:	#DDDDDD;
	}
	.bgd {
		background-color:	#CCCCCC;
	}
	.bl {
		display:	list-item;
	}
	.hide {
		display:	none;
	}
	.or {
		font-style:	italic;
	}
</style>
<?php

require_once("inc.dropdowns.define.php");
require_once("lib_mode.1.php");
require_once("diag.1.php");

if (Lib_Mode::Get_Mode() == MODE_LOCAL)
{
	#Diag::Enable();
}

# exists solely for debuggability
function _strtotime($date)
{
	$ts = strtotime($date);
	Diag::Out("$date = " . date("m/d/Y H:i:s", $ts), "_strtotime(date)");
	return $ts;
}

################ begin frontend calculations for biweekly date

define("WEEKS_BACK", 2); # generate paychecks for this many weeks

$weekdays = array(
	DAY_MON => 1,
	DAY_TUE => 2,
	DAY_WED => 3,
	DAY_THU => 4,
	DAY_FRI => 5,
	DAY_SAT => 6,
	DAY_SUN => 7
);
$weekday = $weekdays[strtoupper(date("D"))];

$biweekly_dates = array(
	DAY_MON => array(),
	DAY_TUE => array(),
	DAY_WED => array(),
	DAY_THU => array(),
	DAY_FRI => array()
);

$ts = _strtotime(date("m/d/Y") . " -$weekday days");

reset($weekdays);
while (list($day,$i) = each($weekdays))
{
	if ($i > $weekdays[DAY_FRI]) # skip weekends
		continue;
	$tmp_ts = _strtotime(date("m/d/Y", $ts) . " +$i days");
	$go_back = ($i > $weekday ? WEEKS_BACK : WEEKS_BACK - 1); # don't fully rewind if dates could be in current week
	$tmp_ts = _strtotime(date("m/d/Y", $tmp_ts) . " -$go_back weeks"); # go back far
	$c = 0;
	while ($c++ < WEEKS_BACK)
	{
		$biweekly_dates[$day][] = date("m/d/Y", $tmp_ts);
		$tmp_ts = _strtotime(date("m/d/Y", $tmp_ts) . " +1 weeks");
	}
}

Diag::Dump($biweekly_dates, "biweekly_dates");

################### end frontend (argh) calculations for biweekly date

# calculate who's invisible and who's not
$hide = array(
	FREQ_WEEKLY => (@$_GET["paydate"]["frequency"] != FREQ_WEEKLY ? "hide" : "")
	,FREQ_BIWEEKLY => (@$_GET["paydate"]["frequency"] != FREQ_BIWEEKLY ? "hide" : "")
		,"biweekly_MON" => (@$_GET["paydate"]["frequency"] == FREQ_BIWEEKLY && (@$_GET["paydate"]["biweekly_day"] == DAY_MON || @$_GET["paydate"]["biweekly_day"] == "")  ? "" : "hide")
		,"biweekly_TUE" => (@$_GET["paydate"]["frequency"] == FREQ_BIWEEKLY && @$_GET["paydate"]["biweekly_day"] == DAY_TUE ? "" : "hide")
		,"biweekly_WED" => (@$_GET["paydate"]["frequency"] == FREQ_BIWEEKLY && @$_GET["paydate"]["biweekly_day"] == DAY_WED ? "" : "hide")
		,"biweekly_THU" => (@$_GET["paydate"]["frequency"] == FREQ_BIWEEKLY && @$_GET["paydate"]["biweekly_day"] == DAY_THU ? "" : "hide")
		,"biweekly_FRI" => (@$_GET["paydate"]["frequency"] == FREQ_BIWEEKLY && @$_GET["paydate"]["biweekly_day"] == DAY_FRI ? "" : "hide")
	,FREQ_TWICEMONTHLY => (@$_GET["paydate"]["frequency"] != FREQ_TWICEMONTHLY ? "hide" : "")
		,"twicemonthly_date" => (@$_GET["paydate"]["frequency"] == FREQ_TWICEMONTHLY && @$_GET["paydate"]["twicemonthly_type"] == "date" ? "" : "hide")
		,"twicemonthly_week" => (@$_GET["paydate"]["frequency"] == FREQ_TWICEMONTHLY && @$_GET["paydate"]["twicemonthly_type"] == "week" ? "" : "hide")
		,"twicemonthly_biweekly" => ""
	,FREQ_MONTHLY => (@$_GET["paydate"]["frequency"] != FREQ_MONTHLY ? "hide" : "")
		,"monthly_date" => (@$_GET["paydate"]["frequency"] == FREQ_MONTHLY && @$_GET["paydate"]["monthly_type"] == "date" ? "" : "hide")
		,"monthly_day" => (@$_GET["paydate"]["frequency"] == FREQ_MONTHLY && @$_GET["paydate"]["monthly_type"] == "day" ? "" : "hide")
		,"monthly_after" => (@$_GET["paydate"]["frequency"] == FREQ_MONTHLY && @$_GET["paydate"]["monthly_type"] == "after" ? "" : "hide")
);

# figure out what's supposed to be checked
//echo "Array HIDE <pre>";var_dump($hide);
$checked = array(
	"twicemonthly_date" => ($hide["twicemonthly_date"] == "" ? "checked" : "")
	,"twicemonthly_week" => ($hide["twicemonthly_week"] == "" ? "checked" : "")
	,"twicemonthly_biweekly" => ($hide["twicemonthly_biweekly"] == "" ? "checked" : "")
	,"monthly_date" => ($hide["monthly_date"] == "" ? "checked" : "")
	,"monthly_day" => ($hide["monthly_day"] == "" ? "checked" : "")
	,"monthly_after" => ($hide["monthly_after"] == "" ? "checked" : "")
);

# Decide whether form elements should be disabled or not - This option created for react process

#Diag::Dump($hide, "hide");
#Diag::Dump($checked, "checked");

$current_month = date("F");
$current_year = date("Y");

?>

<script language="Javascript">

function _how_often(val)
{

	hide_div("paydate_monthly");
	hide_div("paydate_weekly");
	hide_div("paydate_biweekly");
	hide_div("paydate_twicemonthly");

	switch(val)
	{
	case "<?php echo FREQ_WEEKLY; ?>":
		show_div("paydate_weekly");
		break;
	case "<?php echo FREQ_BIWEEKLY; ?>":
		show_div("paydate_biweekly");
		break;
	case "<?php echo FREQ_TWICEMONTHLY; ?>":
		show_div("paydate_twicemonthly");
		// This sucks but you can't use arrays in Javascipt so the paydate[twicemonthly] radio boxes cannot be manipulated by JS
		// So, we have to assign the first element of paydate[twicemonthly] an id and then erase it if it is checked
		twice_day = document.getElementById("twicemonthly_type").checked;
		if(twice_day='biweekly')
		{
			document.getElementById("twicemonthly_type").checked = '';
			hide_div("twicemonthly_biweekly");
		}
		break;
	case "<?php echo FREQ_MONTHLY; ?>":
		show_div("paydate_monthly");
		break;
	}
}

function _biweekly_day(val)
{
	twice_day = document.getElementById("biweekly_twice_day");
	eow_day = document.getElementById("biweekly_day");
	twice_day.selectedIndex = eow_day.selectedIndex;
	hide_div("paydate_biweekly_MON");
	hide_div("paydate_biweekly_TUE");
	hide_div("paydate_biweekly_WED");
	hide_div("paydate_biweekly_THU");
	hide_div("paydate_biweekly_FRI");
	show_div("paydate_biweekly_" + val);
		hide_div("paydate_twice_biweekly_MON");
	hide_div("paydate_twice_biweekly_TUE");
	hide_div("paydate_twice_biweekly_WED");
	hide_div("paydate_twice_biweekly_THU");
	hide_div("paydate_twice_biweekly_FRI");
	show_div("paydate_twice_biweekly_" + val);
}

function _biweekly_twice_day(val)
{
	twice_day = document.getElementById("biweekly_twice_day");
	eow_day = document.getElementById("biweekly_day");
	eow_day.selectedIndex = twice_day.selectedIndex;
	hide_div("paydate_biweekly_MON");
	hide_div("paydate_biweekly_TUE");
	hide_div("paydate_biweekly_WED");
	hide_div("paydate_biweekly_THU");
	hide_div("paydate_biweekly_FRI");
	
	show_div("paydate_biweekly_" + val);
	hide_div("paydate_twice_biweekly_MON");
	hide_div("paydate_twice_biweekly_TUE");
	hide_div("paydate_twice_biweekly_WED");
	hide_div("paydate_twice_biweekly_THU");
	hide_div("paydate_twice_biweekly_FRI");
	show_div("paydate_twice_biweekly_" + val);
}

function show_div(id)
{
	div = document.getElementById(id);
	div.style.display = "block";
}

function hide_div(id)
{
	div = document.getElementById(id);
	if (div.style == null)
		alert(id + " is not a div!");
	div.style.display = "none";
}
function twicemonthly_biweekly_show()
{	// This value has to be set to every other week to process correctly, even though it is in twice per month
	document.getElementById("how_often").selectedIndex = 2;
	hide_div("twicemonthly_day");
	hide_div("twicemonthly_date");
	show_div("twicemonthly_biweekly");
}

function twicemonthly_date_show()
{
	document.getElementById("how_often").selectedIndex = 3;
	hide_div("twicemonthly_day");
	hide_div("twicemonthly_biweekly");
	show_div("twicemonthly_date");
}

function twicemonthly_day_show()
{
	document.getElementById("how_often").selectedIndex = 3;
	hide_div("twicemonthly_biweekly");
	hide_div("twicemonthly_date");
	show_div("twicemonthly_day");
}

function monthly_date_show()
{
	show_div("monthly_date");
	hide_div("monthly_day");
	hide_div("monthly_after");
}

function monthly_day_show()
{
	hide_div("monthly_date");
	show_div("monthly_day");
	hide_div("monthly_after");
}

function monthly_after_show()
{
	hide_div("monthly_date");
	hide_div("monthly_day");
	show_div("monthly_after");
}

</script>

<div class="tbl" style="border:0px solid Red">

	<div class="tbl">
		<table class="tbl">
		<tr>
			<!--<td width="50%" class="sh-align-right sh-bold sh-nobr">How often are you paid?</td> -->
			<td width="50%" class="sh-bold sh-nobr paydate-copy">How often are you paid?</td>
			<td width="50%" class="sh-align-left sh-nobr paydate-select"><?php $how_often->display(); ?></td>
		</tr>
		</table>
	</div>

	<div id="paydate_weekly" class="tbl <?php echo $hide[FREQ_WEEKLY]; ?>">
		<table class="tbl">
		<tr>
			<td width="50%" class="sh-align-right sh-bold sh-nobr">Which day?</td>
			<td class="sh-align-left sh-nobr"><?php $weekly_day->display(); ?></td>
		</tr>
		</table>
	</div>
	
	<!-- twicemonthly -->
	<div id="paydate_twicemonthly" style="width:100%" class="<?php echo $hide[FREQ_TWICEMONTHLY]; ?>">
		Please select and complete one option that describes your pay schedule:
		<ul style="list-style:none; padding-left:1em; padding-right:1em">
			<li>
				<div class="suboption sh-align-left">
					<input type="radio" name="paydate[twicemonthly_type]" id="twicemonthly_type" value="biweekly" onclick="twicemonthly_biweekly_show()" <?php if(!empty($paydate['twicemonthly_type']) && $paydate['twicemonthly_type']=="biweekly"){echo "checked";} ?> tabindex="@@TABINDEX_INCOME_BIWEEKLY@@" />
					<span class="b">I get paid <u><em>every two weeks</em></u> on the same day of the week.</span><br />
					<div id="twicemonthly_biweekly" class="<?php echo $hide["twicemonthly_biweekly"]; ?>">
						<table class="tbl" cellpadding="0" cellspacing="0">
						<tr>
							<td class="sh-align-right sh-bold sh-nobr">
								I get paid on:
							</td>
							<td width="50%" class="sh-align-left sh-nobr">
								<?php $biweekly_twice_day->display(); ?>
							</td>
						</tr>
						<tr>
							<td width="50%" class="sh-align-right sh-bold">
								My last paydate was:
								<br /><span class="sh-form-hint">(see your last paycheck for this date)</span>
							</td>
							<td width="50%" class="sh-align-left sh-nobr">
							<?php
								reset($biweekly_dates);
								while (list($day,$dates) = each ($biweekly_dates))
								{
							?>
								<div id="paydate_twice_biweekly_<?php echo $day; ?>" class="sh-align-left <?php echo $hide["biweekly_$day"]; ?>">
								<?php
									reset($dates);
									$ti=0;
									while (list($i,$date) = each($dates))
									{$ti++;
								?>
									<input type="radio" name="paydate[biweekly_date]" value="<?php echo $date; ?>" <?php echo (@$_GET["paydate"]["biweekly_date"] == $date ? "checked" : ""); ?> tabindex="@@TABINDEX_INCOME_BIWEEKLY_<? echo $ti ?>_DATE@@"/> <?php echo $date; ?><br />
								<?php
									}
								?>
								</div>
							<?php
								}
							?>
							</td>
						</tr>
						</table>	
					</div>
					<span class="example">Example: I get paid every other Wednesday.</span>
				</div>
			</li>
			<li>
			<div class="suboption sh-align-left">
				<input type="radio" name="paydate[twicemonthly_type]" value="date" onclick="twicemonthly_date_show()" <?php echo $checked["twicemonthly_date"]; ?> tabindex="@@TABINDEX_INCOME_TM_DATE@@"/>
				<span class="b">I am paid based on the <u><em>date</em></u></span><br />
				<div id="twicemonthly_date" class="<?php echo $hide["twicemonthly_date"]; ?>">
					I get paid on the
					<?php $twicemonthly_date1->display(); ?>
					and
					<?php $twicemonthly_date2->display(); ?>
					of every month
				</div>
				<span class="example">Example: I get paid on the 15th and last day of every month</span>
			</div>
			<li>
				<div class="sh-align-center center">or... </div>
			</li>
			<li>
			<div class="suboption sh-align-left">
				<input type="radio" name="paydate[twicemonthly_type]" value="week" onclick="twicemonthly_day_show()" <?php echo $checked["twicemonthly_week"]; ?> tabindex="@@TABINDEX_INCOME_TM_WEEK@@"/>
				<span class="b">I am paid on the same day of the week, but <u><em>only twice per month</em></u></span><br />
				<div id="twicemonthly_day" class="<?php echo $hide["twicemonthly_week"]; ?>">
					I get paid on the
					<?php $twicemonthly_week->display(); ?>
					<?php $twicemonthly_day->display(); ?>
				</div>
				<span class="example">Example: I get paid on the first and third Friday of every month</span>
			</div>
		</ul>
	</div>

	<!-- biweekly -->
	<div id="paydate_biweekly" class="<?php echo $hide[FREQ_BIWEEKLY]; ?>">
		<table class="tbl" cellpadding="0" cellspacing="0">
		<tr>
			<td class="sh-align-right sh-bold sh-nobr">
				I get paid on:
			</td>
			<td width="50%" class="sh-align-left sh-nobr">
				<?php $biweekly_day->display(); ?>
			</td>
		</tr>
		<tr>
			<td width="50%" class="sh-align-right sh-bold">
				My last paydate was:
				<br /><span class="sh-form-hint">(see your last paycheck for this date)</span>
			</td>
			<td width="50%" class="sh-align-left sh-nobr">
			<?php
				reset($biweekly_dates);
				while (list($day,$dates) = each ($biweekly_dates))
				{
			?>
				<div id="paydate_biweekly_<?php echo $day; ?>" class="sh-align-left <?php echo $hide["biweekly_$day"]; ?>">
				<?php
					reset($dates);
					$ti=0;
					while (list($i,$date) = each($dates))
					{$ti++;
				?>
					<input type="radio" name="paydate[biweekly_date]" value="<?php echo $date; ?>" <?php echo (@$_GET["paydate"]["biweekly_date"] == $date ? "checked" : ""); ?> tabindex="@@TABINDEX_INCOME_BIWEEKLY_<? echo $ti ?>_DATE@@" /> <?php echo $date; ?><br />
				<?php
					}
				?>
				</div>
			<?php
				}
			?>
			</td>
		</tr>
		</table>
	</div>
	
	<!-- monthly -->
	<div id="paydate_monthly" class="<?php echo $hide[FREQ_MONTHLY]; ?>">
		<div style="width:100%">
			Please select and complete one option that describes your pay schedule:
			<ul style="list-style:none; padding-left:1em; padding-right:1em">
				<li>
					<div class="sh-align-left sh-nobr suboption">
						<input type="radio" name="paydate[monthly_type]" value="date" onclick="monthly_date_show()" <?php echo $checked["monthly_date"]; ?> tabindex="@@TABINDEX_INCOME_MONTHLY@@" />
						<span class="b">I am paid on a specific <u><em>date</em></u></span><br />
						<div id="monthly_date" class="<?php echo $hide["monthly_date"]; ?>">
							I get paid on the
							<?php $monthly_date->display(); ?>
							of every month
						</div>
						<span class="example">Example: &quot;I get paid on the 1st of every month&quot;</span>
					</div>
				</li>
				<li>
					<div class="sh-align-center or">or... </div>
				</li>
				<li>
					<div class="sh-align-left sh-nobr suboption">
						<input type="radio" name="paydate[monthly_type]" value="day" onclick="monthly_day_show()" <?php echo $checked["monthly_day"]; ?> tabindex="@@TABINDEX_INCOME_MONTHLY_DAY@@"/>
						<span class="b">I am paid <u><em>on a certain day of a certain week</em></u></span><br />
						<div id="monthly_day" class="<?php echo $hide["monthly_day"]; ?>">
							I get paid on the
							<?php $monthly_week->display(); ?>
							<?php $monthly_day->display(); ?>
							of every month
						</div>
						<span class="example">Example: &quot;I get paid on the second Tuesday of every month&quot;</span>
					</div>
				</li>
				<li>
					<div class="sh-align-center or">or... </div>
				</li>
				<li>
					<div class="sh-align-left sh-nobr suboption">
						
						 <input type="radio" name="paydate[monthly_type]" value="after" onclick="monthly_after_show()" <?php echo $checked["monthly_after"]; ?> tabindex="@@TABINDEX_INCOME_MONTHLY_AFTER@@"/>
						<span class="b">I am paid <u><em>after a certain date</em></u></span><br />
						<div id="monthly_after" class="<?php echo $hide["monthly_after"]; ?>">
							I get paid on the first
							<?php $monthly_after_day->display(); ?>
							after the
							<?php $monthly_after_date->display(); ?>
							of the month
						</div>
						<span class="example">Example: &quot;I get paid on the first Monday after the 15th of every month&quot;</span>
					</div>
				</li>
			</ul>
		</div>
	</div>

</div>

