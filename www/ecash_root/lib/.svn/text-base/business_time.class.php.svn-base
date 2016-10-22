<?php
/**
 * Business_Time
 * Calculate Dates and Times based upon Weekends, Holidays and Operating Hours
 *
 * Created on Dec 28, 2006
 *
 * @category Date/Time
 *
 * @author Jason Belich <jason.belich@sellingsource.com>
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

class Business_Time 
{
	
	static private $pdc;
	
	private $start_time;
	private $end_time;
	private $break = array();
	private $tz;
	
	public function Set_Timezone($tz)
	{
		$this->tz = $tz;
	}
	
	public function Set_Open($mil_time)
	{	
		$this->start_time = $this->Time_Convert_Mil2Dec($mil_time);	
	}
	
	public function Set_Close($mil_time)
	{
		$this->end_time = $this->Time_Convert_Mil2Dec($mil_time); 		
	}
	
	public function Add_Break($mil_time, $duration_minutes)
	{
		$duration = number_format( ($duration_minutes / 60), 2);
		
		$start_time = $this->Time_Convert_Mil2Dec($mil_time);

		//TODO validate end time against closing time
		//TODO validate end time against midnight
		
		$end_time = number_format( ($start_time + $duration), 2);
		
		$this->break[(int) str_replace(".","",$start_time)] = array ('start' => $start_time, 'end' => $end_time);
		
		ksort($this->break);
		
	}
	
	static protected function getPDC()
	{
		require_once COMMON_LIB_DIR . "/pay_date_calc.3.php";
		
		if(!(self::$pdc instanceof Pay_Date_Calc_3 )) 
		{
			self::$pdc = new Pay_Date_Calc_3(Fetch_Holiday_List());
		}
		
		return self::$pdc;
		
	}
	
	public function Time_Convert_Dec2Mil($time)
	{
		if(!is_float($time) || $time > 24 || $time < 0) 
		{
			throw new InvalidArgumentException(" ({$time}) is not a properly formatted decimal time.");
		}
		
		$hours = floor($time);
		$min = round(60 * ($time - $hours));
		
		return  substr((string) "00" . (string) $hours, -2) . substr((string) "00" . (string) $min, -2);
		
	}
	
	public function Time_Convert_Mil2Dec($time)
	{
		if(!$this->isMilTime($time)) 
		{
			throw new InvalidArgumentException(" ({$time}) is not a properly formatted military time.");
		}
		
		$t = (float) preg_replace("/(\d{2})(\d{2})/", '$1.$2', substr((string) "0000" . (string) $time, -4));
		$t_h = intval($t);
		$t_m = $t - $t_h;
		$t_m = $t_m / 0.6;
		
		return $t_h + $t_m;
		
	}
	
	protected function isMilTime($time)
	{
		return (is_numeric($time) && strlen($time) <= 4 && $time < 2400 && $time > 0);
	}
	
	protected function isDateTime($date)
	{
		return preg_match("/[12]\d{3}\-[01]\d\-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d/",$date);
	}
	
	protected function isDate($date)
	{
		return preg_match("/[12]\d{3}\-[01]\d\-[0-3]\d/",$date);
	}
	
	protected function isTime($date)
	{
		return preg_match("/[0-2][0-3]:[0-5]\d:[0-5]\d/",$date);
	}
	
	protected function parseDate($date)
	{
		switch(TRUE)
		{
			case $this->isDateTime($date):
				list($tdate,$ttime) = explode(" ",$date);
				break;
		
			case $this->isDate($date):
				$tdate = $date;
				break;
				
			case $this->isTime($date):
				$ttime = $date;
		}
		
		if(isset($ttime)) 
		{
			$ttime = $this->Time_Convert_Mil2Dec(preg_replace("/^(\d{2}):(\d{2}):\d{2}/",'$1$2',$ttime));
		}
		
		return array("date" => $tdate , "time" => $ttime);
		
	}
	
	protected function checkDate(&$date)
	{
		if ($date == NULL) 
		{
			$date = date("Y-m-d");
		}
		
		if($this->isDateTime($date)) 
		{
			$a = $this->parseDate($date);
			$date = $a['date'];
		}
		
		if(!$this->isDate($date)) 
		{
			throw new InvalidArgumentException(" ({$date}) is not a properly formatted start date.");
		}
		
	}
	
	public function Get_Days_Forward($num, $from = NULL)
	{
		$this->checkDate($from);
		
		return self::getPDC()->Get_Business_Days_Forward($from, $num);
	
	}
	
	public function Get_Days_Backward($num, $from = NULL)
	{
		$this->checkDate($from);
			
		return self::getPDC()->Get_Business_Days_Backward($from, $num);
	
	}
	
	public function Get_End_Of_Day($num, $from = NULL)
	{
		$date = $this->Get_Days_Forward($num, $from);
		
		return $date . " " . preg_replace("/(\d{2})(\d{2})/", '$1:$2:00', $this->Time_Convert_Dec2Mil($this->end_time));
		
	}
	
	public function Get_Hours_Forward($num, $from = NULL)
	{
		$ret = $this->Get_Forward($this->Time_Convert_Mil2Dec($num),$from);
		
		return $ret['date'] . " " . preg_replace("/(\d{2})(\d{2})/", '$1:$2:00', $this->Time_Convert_Dec2Mil($ret['time']));
		
	}
	
	public function Get_Hours_Backward($num, $from = NULL)
	{
		$ret =  $this->Get_Backward($this->Time_Convert_Mil2Dec($num),$from);		
		
		return $ret['date'] . " " . preg_replace("/(\d{2})(\d{2})/", '$1:$2:00', $this->Time_Convert_Dec2Mil($ret['time']));
		
	}
	
	public function Get_Minutes_Forward($num, $from = NULL)
	{
		$ret =  $this->Get_Forward(($num / 60 ),$from);	
		
		return $ret['date'] . " " . preg_replace("/(\d{2})(\d{2})/", '$1:$2:00', $this->Time_Convert_Dec2Mil($ret['time']));
		
	}
	
	public function Get_Minutes_Backward($num, $from = NULL)
	{
		$ret =  $this->Get_Backward(($num / 60 ),$from);
		
		return $ret['date'] . " " . preg_replace("/(\d{2})(\d{2})/", '$1:$2:00', $this->Time_Convert_Dec2Mil($ret['time']));
		
	}
	
	//TODO include a sanity check for ends later than starts
	public function Get_Day_Length()
	{
		$t_len = $this->end_time - $this->start_time;
		
		foreach ($this->break as $break) 
		{
			
			$t_dur = $break['end'] - $break['start'];
			
			$t_len = $t_len - $t_dur;
			
		}
		
		return $t_len;
		
	}
	
	public function Get_Day_Length_Hours()
	{
		$this->Time_Convert_Dec2Mil($this->Get_Day_Length());
	}
	
	public function Get_Day_Length_Minutes()
	{
		return round($this->Get_Day_Length() * 60);
	}
	
	public function Get_Forward($dec_hours, $from = NULL)
	{
		if ($from == NULL) 
		{
			$old_tz = ini_get("date.timezone");
			ini_set("date.timezone",$this->tz);
			$from  = date("Y-m-d H:i:s");
			ini_set("date.timezone",$old_tz);
		}
		
		$day_length = $this->Get_Day_Length();
		$full_days = intval($dec_hours / $day_length);
		$dec_hours = $dec_hours - ($full_days * $day_length);

		$start = $this->parseDate($from);
		$start_date = ($start['date']) ? $start['date'] : date("Y-m-d");

		$cursor = ($start['time']) ? $start['time'] : $this->start_time;	
		
		do {		
			foreach ($this->break as $a) 
			{
				if ( $cursor <= $a['start'] && ($cursor + $dec_hours) >= $a['start']) 
				{
					$cursor_delta = $a['start'] - $cursor;
					$cursor = $a['end'];
					$dec_hours = $dec_hours - $cursor_delta;
				}
			}
			
			if (($cursor + $dec_hours) > $this->end_time) 
			{
				$full_days++;
				$dec_hours = $cursor + $dec_hours - $this->end_time;

				$cursor = $this->start_time;
				
			} 
			else 
			{
				$cursor = $cursor + $dec_hours;
				$dec_hours = 0;
			}
			
		} while ($dec_hours);

		$ret['date'] = self::getPDC()->Get_Business_Days_Forward($start_date, $full_days);
		$ret['time'] = $cursor; // return final time in decimal format
		
		return $ret;
		
	}
	
	public function Get_Backward($dec_hours, $from = NULL)
	{
		if ($from == NULL) 
		{
			$old_tz = ini_get("date.timezone");
			ini_set("date.timezone",$this->tz);
			$from  = date("Y-m-d H:i:s");
			ini_set("date.timezone",$old_tz);
		}
		
		$day_length = $this->Get_Day_Length();
		$full_days = floor($dec_hours / $day_length);
		
		$start = $this->parseDate($from);
		$start_date = ($start['date']) ? $start['date'] : date("Y-m-d");
		$start_time = ($start['time']) ? $start['time'] : $this->end_time;	
		
		if ($start_time < $this->start_time) 
		{
			$full_days++;

		// if the time param of $from is between start and end, add time delta to $dec_hours and set $from to start of day
		} 
		else 
		{
			$delta = $this->end_time - $start_time;
			$dec_hours = $dec_hours + $delta;
		}
		
		if ($dec_hours > $day_length && $full_days > 0 ) 
		{
			$dec_hours = $dec_hours % $day_length;
		}
		
		$cursor = $this->end_time - $dec_hours;
		
		// pad the expiration time with break periods
		$tbk = $this->break;
		krsort($tbk);
		$tbk = array_values($tbk);
		
		foreach ($tbk as $a) 
		{
			if ($cursor < $a['end']) 
			{
				$cursor = $cursor - ($break['end'] - $break['start']);
			} 
			else 
			{
				break;
			}
		}	
		
		$ret['date'] = self::getPDC()->Get_Business_Days_Backward($start_date, $full_days);
		$ret['time'] = $cursor; // return final time in decimal format
		
		return $ret;
		
		
	}
	
}
 

class Company_Time extends Business_Time 
{
	
	private static $obj;
	
	static public function Factory()
	{
		require_once LIB_DIR . "/company_rules.class.php";
		
		$bt = new Company_Time;
		
		$bt->Set_Open(Company_Rules::Get_Config("company_start_time"));
		$bt->Set_Close(Company_Rules::Get_Config("company_close_time"));
		$bt->Set_Timezone(Company_Rules::Get_Config("time_zone"));
		
		$lunch = Company_Rules::Get_Config("company_lunch_time");
		$duration = Company_Rules::Get_Config("company_lunch_duration");
		if($lunch && $duration) 
		{
			$bt->Add_Break($lunch, $duration);
		}	

		return $bt;
		
	}
	
	static public function Singleton()
	{
		if(!(self::$obj instanceof Business_Time)) 
		{
			self::$obj = self::Factory();
		}
		
		return self::$obj;
		
	}
	
	static public function Interval_Min($datetime, $from = NULL)
	{
		if(!self::Singleton()->isDateTime($datetime)) 
		{
			throw new InvalidArgumentException(" ({$datetime}) is not a properly formatted datetime.");
		}
		
		if($from != NULL) 
		{
			if(!self::Singleton()->isDateTime($from)) 
			{
				throw new InvalidArgumentException(" ({$from}) is not a properly formatted datetime.");
			}
			
			$from = strtotime($from);
			
		} 
		else 
		{
			$from = time();
			
		}

		return intval ( ( strtotime($datetime) - $from ) / 60 );
	}
	
}
