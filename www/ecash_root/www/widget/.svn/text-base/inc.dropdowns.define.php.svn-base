<?php

require_once("dropdown.1.php");
require_once("dropdown.1.months.php");
require_once("dropdown.1.numeric.php");

define("DAY_MON", "MON");
define("DAY_TUE", "TUE");
define("DAY_WED", "WED");
define("DAY_THU", "THU");
define("DAY_FRI", "FRI");
define("DAY_SAT", "SAT");
define("DAY_SUN", "SUN");
define("FREQ_WEEKLY", "WEEKLY");
define("FREQ_BIWEEKLY", "BI_WEEKLY");
define("FREQ_TWICEMONTHLY", "TWICE_MONTHLY");
define("FREQ_MONTHLY", "MONTHLY");

$dayofweek = array(
	DAY_MON => "Monday"
	,DAY_TUE => "Tuesday"
	,DAY_WED => "Wednesday"
	,DAY_THU => "Thursday"
	,DAY_FRI => "Friday"
);

$PAYFREQ = array(
	FREQ_WEEKLY => "Every Week"
	,FREQ_BIWEEKLY => "Every Other Week"
	,FREQ_TWICEMONTHLY => "Twice Per Month"
	,FREQ_MONTHLY => "Once Per Month"
);

$how_often = new Dropdown(
	array(
		"name" => "paydate[frequency]"
		,"selected" => @$_GET["paydate"]["frequency"]
		,"unselected" => "(Select Pay Frequency)"
		,"attrs" => array(
			"id" => "how_often"
			,"onchange" => "_how_often(this.value)"
			,"tabindex" => "@@TABINDEX_INCOME_HOW_OFTEN@@"
		)
		,"keyvals" => $PAYFREQ
		
	)
);

$weekly_day = new Dropdown(
	array(
		"name" => "paydate[weekly_day]"
		,"selected" => @$_GET["paydate"]["weekly_day"]
		,"unselected" => ""
		#,"unselected" => "(Select Day)"
		,"attrs" => array (
		 	"tabindex" => "@@TABINDEX_INCOME_WEEKLY_DAY@@"
		 )
		,"keyvals" => $dayofweek
	)
);

$biweekly_week = new Dropdown(
	array(
		"name" => "paydate[bimonthly_week]"
		,"selected" => @$_GET["paydate"]["bimonthly_week"]
		,"unselected" => ""
		#,"unselected" => "(Select Weeks)"
		,"keyvals" => array(
			"thisweek" => "This week"
			,"lastweek" => "Last week"
		)
	)
);

$biweekly_day = new Dropdown(
	array(
		"name" => "paydate[biweekly_day]"
		,"selected" => @$_GET["paydate"]["biweekly_day"]
		,"unselected" =>"" 
		#,"unselected" =>"(Select Weekday)" 
		,"attrs" => array(
			"id" => "biweekly_day"
			,"onchange" => "_biweekly_day(this.value);clear_radio('biweekly_date')"
			,"tabindex" => "@@TABINDEX_INCOME_BIWEEKLY_DAY@@"
		)
		,"keyvals" => $dayofweek
	)
);

$biweekly_twice_day = new Dropdown(
	array(
		"name" => "paydate[biweekly_day]"
		,"selected" => @$_GET["paydate"]["biweekly_day"]
		,"unselected" =>"" 
		#,"unselected" =>"(Select Weekday)" 
		,"attrs" => array(
			"id" => "biweekly_twice_day"
			,"onchange" => "_biweekly_twice_day(this.value);clear_radio('biweekly_date')"
			,"tabindex" => "@@TABINDEX_INCOME_BIWEEKLY_TWICE_DAY@@"
		)
		,"keyvals" => $dayofweek
	)
);

$twicemonthly_date1 = new Dropdown_Numeric(
	array(
		"name" => "paydate[twicemonthly_date1]"
		,"selected" => @$_GET["paydate"]["twicemonthly_date1"]
		,"unselected" => "" 
		#,"unselected" => "(Select Day)" 
		,"nth" => true
		,"start" => 1
		,"stop" => 31
		,"incr" => 1
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_TM_1_DATE@@"
		)
	)
);

$twicemonthly_date2 = new Dropdown(
	array(
		"name" => "paydate[twicemonthly_date2]"
		,"unselected" => "" 
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_TM_2_DATE@@"
		)
		#,"unselected" => "(Select Day)" 
		,"selected" => @$_GET["paydate"]["twicemonthly_date2"]
		,"keyvals" => array(
			"1" => "1st"
			,"2" => "2nd"
			,"3" => "3rd"
			,"4" => "4th"
			,"5" => "5th"
			,"6" => "6th"
			,"7" => "7th"
			,"8" => "8th"
			,"9" => "9th"
			,"10" => "10th"
			,"11" => "11th"
			,"12" => "12th"
			,"13" => "13th"
			,"14" => "14th"
			,"15" => "15th"
			,"16" => "16th"
			,"17" => "17th"
			,"18" => "18th"
			,"19" => "19th"
			,"20" => "20th"
			,"21" => "21st"
			,"22" => "22nd"
			,"23" => "23rd"
			,"24" => "24th"
			,"25" => "25th"
			,"26" => "26th"
			,"27" => "27th"
			,"28" => "28th"
			,"29" => "29th"
			,"30" => "30th"
			,"31" => "31st"
			,"32" => "Last Day")
		)
);

$twicemonthly_week = new Dropdown(
	array(
		"name" => "paydate[twicemonthly_week]"
		,"selected" => @$_GET["paydate"]["twicemonthly_week"]
		,"unselected" => "" 
		#,"unselected" => "(Select Frequency)" 
		,"keyvals" => array(
			"1-3" => "first and third"
			,"2-4" => "second and fourth"
		)
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_TM_WEEK_VAL@@"
		)
	)
);

$twicemonthly_day = new Dropdown(
	array(
		"name" => "paydate[twicemonthly_day]"
		,"selected" => @$_GET["paydate"]["twicemonthly_day"]
		,"unselected" => ""
		#,"unselected" => "(Select Weekday)"
		,"keyvals" => $dayofweek
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_TM_WEEK_DAY@@"
		)
	)
);

$monthly_date = new Dropdown(
	array(
		"name" => "paydate[monthly_date]"
		,"selected" => @$_GET["paydate"]["monthly_date"]
		,"unselected" => "" 
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_MONTHLY_DATE@@"
		)		
		#,"unselected" => "(Day Of The Month)" 
		,"keyvals" => array(
			"1" => "1st"
			,"2" => "2nd"
			,"3" => "3rd"
			,"4" => "4th"
			,"5" => "5th"
			,"6" => "6th"
			,"7" => "7th"
			,"8" => "8th"
			,"9" => "9th"
			,"10" => "10th"
			,"11" => "11th"
			,"12" => "12th"
			,"13" => "13th"
			,"14" => "14th"
			,"15" => "15th"
			,"16" => "16th"
			,"17" => "17th"
			,"18" => "18th"
			,"19" => "19th"
			,"20" => "20th"
			,"21" => "21st"
			,"22" => "22nd"
			,"23" => "23rd"
			,"24" => "24th"
			,"25" => "25th"
			,"26" => "26th"
			,"27" => "27th"
			,"28" => "28th"
			,"29" => "29th"
			,"30" => "30th"
			,"31" => "31st"
			,"32" => "Last Day"
		)
	)
);

$monthly_week = new Dropdown(
	array(
		"name" => "paydate[monthly_week]"
		,"selected" => @$_GET["paydate"]["monthly_week"]
		,"unselected" => ""
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_MONTHLY_WEEK@@"
		)		
		#,"unselected" => false
		,"keyvals" => array(
			"1" => "first"
			,"2" => "second"
			,"3" => "third"
			,"4" => "fourth"
			,"5" => "last"
		)
	)
);

$monthly_day = new Dropdown(
	array(
		"name" => "paydate[monthly_day]"
		,"selected" => @$_GET["paydate"]["monthly_day"]
		,"unselected" => ""
		#,"unselected" => false
		,"keyvals" => $dayofweek
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_MONTHLY_DROP_DAY@@"
		)
	)
);

$monthly_after_day = new Dropdown(
	array(
		"name" => "paydate[monthly_after_day]"
		,"selected" => @$_GET["paydate"]["monthly_after_day"]
		,"unselected" => ""
		#,"unselected" => false
		,"keyvals" => $dayofweek
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_MONTHLY_DROP_DAY_SHOW@@"
		)		
	)
);

$monthly_after_date = new Dropdown_Numeric(
	array(
		"name" => "paydate[monthly_after_date]"
		,"selected" => @$_GET["paydate"]["monthly_after_date"]
		,"unselected" => ""
		#,"unselected" => false
		,"nth" => true
		,"start" => 1
		,"stop" => 31
		,"attrs" => array(
			"tabindex" => "@@TABINDEX_INCOME_MONTHLY_DROP_DATE_SHOW@@"
		)		
		
	)
);

?>
