<form  action='/phphatesmath.php' method="post">

<input type="text" name="principal" value="<?=$_REQUEST['principal']?>" > - Principal
<br>
<input type="text" name='rate' value="<?=$_REQUEST['rate']?>" >% - Interest Rate
<br>
<input type="text" name="days" value="<?=$_REQUEST['days']?>"> - Days
<br>
<select name="rounding_type">
<option value="none">Down
<option value="default">Default
<option value="up">Up
<option value="banker">Bankers'
</select>
<br>
<input type="submit" name="submit" value="submit">


</form>
<?
	if ($_REQUEST['submit']) 
	{
		$interest = calculateDailyInterest($_REQUEST['rounding_type'],$_REQUEST['principal'],$_REQUEST['rate'],$_REQUEST['days']);
	?>
		
	<?
	}
?>


<?
	

	function calculateDailyInterest($type, $amount, $percent, $days, $failure_date=NULL)
	{ 
		
		$svc_charge_percentage = $percent;
		// Determine daily rate

		//There's a ton of echos here to help debug the pieces of interest calculation
		echo "<hr><br> Fixed service charge";
			$daily_rate = ( $svc_charge_percentage / 100);
			echo "<br> Daily rate is - $daily_rate";
			$service_charge = $daily_rate * $amount;
			echo "<br> Service Charge is - $service_charge";
			$interest = roundInterest($service_charge,$type,2);
			echo "<br> Interest is - ".$interest;
		echo "<hr><br> Daily Service Charge";
			$daily_rate = (( $svc_charge_percentage / 100) / 7);
			echo "<br> Daily rate is - $daily_rate";
			$service_charge = (($daily_rate * $amount) * $days);
			echo "<br> Service Charge is - $service_charge";
			$interest = roundInterest($service_charge,$type,2);
			echo "<br> Interest is - ".$interest;
	}
	
	function roundInterest($charge, $round_type = 'default', $decimal_place = 2)
	{
		$interest = 0;
		if ($charge==0) 
		{echo "<hr><br>NUMBER IS ZERO!";
			return number_format(0,2);
		}
		switch ($round_type)
		{
			//Truncates the value/ Rounds down
			case 'none':
			echo "<br><b>ROUNDING DOWN</b><br>";
				$interest = roundDown($charge,$decimal_place);
			break;
			
			//Bankers' rounding 
			case 'banker':
			echo "<br><b>BANKERS' ROUNDING</b><br>";
				$interest = bankersRound($charge,$decimal_place);
			break;	
			
			//Always rounds the value up
			case 'up':
			echo "<br><b>ROUNDING UP</b><br>";
				$interest = roundUp($charge,$decimal_place);
			break;

			//Uses standard rounding
			case 'default':
			echo "<br><b>DEFAULT ROUNDING</b><br>";
			default:
				$interest = round($charge,$decimal_place);	
				
			break;
		}
		
		return number_format($interest,2);
	}
	/**
	 * roundDown
	 * Always rounds the digit down/truncates the value
	 *
	 * @param float $charge the unrounded interest charge
	 * @param int $decimal_place the decimal precision to round to
	 * @return float the rounded interest charge
	 */
	function roundDown($charge, $decimal_place)
	{
		//There's a few different ways to round down that I've tried, this spits out the results for all of them.	
		echo "<br>Subtract .5 from the last place and then round = ".number_format(($charge-5*pow(10,-$decimal_place-1)),2);
		echo "<br>floor rounding = ".number_format(floor($charge * pow(10,$decimal_place))/pow(10,$decimal_place),$decimal_place);
		echo "<br>String truncating = ".number_format(roundDownBC($charge,$decimal_place),2);
		return number_format(($charge-5*pow(10,-$decimal_place-1)),2);
	}
	
	/**
	 * roundUp
	 * Always rounds the digit up, disregarding standard rounding rules.
	 *
	 * @param float $charge the unrounded interest charge
	 * @param int $decimal_place the decimal precision to round to
	 * @return float the rounded interest charge
	 */
	function roundUp($charge, $decimal_place)
	{
		return number_format(($charge+(5*pow(10,-$decimal_place-1))),$decimal_place);	
	}
	
	/**
	 * bankersRound
	 * Performs bankers' rounding on Interest charge.
	 * Bankers rounding is identical to the common method of rounding except when the digit(s) 
	 * following the rounding digit start with a five and have no non-zero digits after it. The new algorithm is:
     * -- Decide which is the last digit to keep.
     * -- Increase it by 1 if the next digit is 6 or more, or a 5 followed by one or more non-zero digits.
     *  -- Leave it the same if the next digit is 4 or less
	 *  --Otherwise, all that follows the last digit is a 5 and possibly trailing zeroes; 
     * 		then change the last digit to the nearest even digit. 
     * 		That is, increase the rounded digit if it is currently odd; leave it if it is already even.
	 *
	 * @param float $charge the raw, unrounded interest charge
	 * @param int $decimal_place the amount of decimals places you want to use when rounding
	 * @return float the Bankers' rounded interest charge.
	 */
	function bankersRound ($charge,$decimal_place)
	{
		$format_str = "%01." . ($decimal_place + 1) . "f";
	    $money_str = sprintf($format_str, roundUp($charge, ($decimal_place + 1))); 
	    $last_pos = strlen($money_str)-1;   
	    if ($decimal_place == 0)
	    {
	    	$second_last_pos = strlen($money_str)-3; 
	    }
	    else 
	    {
	    	$second_last_pos = strlen($money_str)-2;                     
	    }
	    
	    if ($money_str[$last_pos] === "5")
	    {
	    	$money_str[$last_pos] = ((int)$money_str[$second_last_pos] & 1) ? "9" : "0"; 
	    }
	    return round($money_str, $decimal_place); 
	}
	
	function roundDownBC($x, $p) 
	{

	    $x = trim($x);
	    $data = explode(".",$x);
		//don't round the value and return the orignal to the desired
	    //precision or less.
	    return $data[0] . "." . substr($data[1],0,$p);

   	}//end roundbc.
	
	
	
		?>