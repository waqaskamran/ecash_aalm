<?php

require_once('crypt.1.php');
require_once('crypt.3.php');

/* This class is so I can format stuff, and do some checks and whatnot */
class Payment_Card
{
	public $card_number;
	public $cardholder_name;
	public $card_street;
	public $card_zip;
	public $card_month;
	public $card_year;
	static public $key   = NULL;
	static public $crypt = NULL;
	
	public static function getKey()
	{
		if (self::$key == NULL)
		{
			// Set the key
			self::$key = ECash::getConfig()->PAYMENT_CARD_KEY;
			if (strlen(self::$key) != 32)
				throw new Exception('Key size needs to be 32 bits in the key file');

			if (self::$crypt == NULL)
			{
				self::$crypt = new Crypt_3();
			}

		}
	}
	
	public static function encrypt($data)
	{
		if (self::$key == NULL)
			self::getKey();

		return self::$crypt->encrypt($data);
	}

	public static function decrypt($data)
	{
		if (self::$key == NULL)
			self::getKey();

		return self::$crypt->decrypt($data);
	}

	// Name shorts match what's in the database, if you edit it there, you must edit it here
	// also.
	public static function Get_Card_Type_By_Card_Number($card_number)
	{
		// First we want to detect the card type
		// We want to grab the CC, grab the date, application ID, comment ID
		// MasterCard
		if (($mc = preg_match('/^(5[1-5][0-9]{2}[- ]?[0-9]{4}[- ]?[0-9]{4}[- ]?[0-9]{4})$/', $card_number)) == 1)
			return 'MCD';

		// Visa
		if ((($vs = preg_match('/^(4[0-9]{3}[- ]?[0-9]{4}[- ]?[0-9]{4}[- ]?[0-9]{4})$/', $card_number)) == 1) ||
			(($vs = preg_match('/^(4[0-9]{3}[- ]?[0-9]{4}[- ]?[0-9]{4}[- ]?[0-9]{1})$/', $card_number)) == 1))
			return 'VIS';

		// Discover
		if (($dc = preg_match('/^(6011[- ]?[0-9]{4}[- ]?[0-9]{4}[- ]?[0-9]{4})$/', $card_number)) == 1)
			return 'DIS';

		// Amex
		if (($amx1 = preg_match('/^(37[0-9]{2}[- ]?[0-9]{4}[- ]?[0-9]{2}[- ]?[0-9]{4}[- ]?[0-9])$/', $card_number)) == 1)
			return 'AMX';

		if (($amx2 = preg_match('/^(34[0-9]{2}[- ]?[0-9]{4}[- ]?[0-9]{2}[- ]?[0-9]{4}[- ]?[0-9])$/', $card_number)) == 1)
			return 'AMX';

		// Diner's Club
		if (($dnc1 = preg_match('/^(30[0-5][0-9][- ]?[0-9]{4}[- ]?[0-9]{2}[- ]?[0-9]{4})$/', $card_number)) == 1)
			return 'DIN';
		
		if (($dnc2 = preg_match('/^(3[6-8][0-9]{2}[- ]?[0-9]{4}[- ]?[0-9]{2}[- ]?[0-9]{4})$/', $card_number)) == 1)
			return 'DIN';

		// Other
		return 'OTR';
	}

	public static function Format_Payment_Card($card_number, $use_mask = FALSE)
	{
		$card_type = self::Get_Card_Type_By_Card_Number($card_number);

		// If we can't determine the type, just display the stars
		if ($card_type === 'OTR')
		{
			if ($use_mask !== FALSE)
			{
				return substr($card_number, 0, 4) . '-' . ((strlen($card_number) > 8) ? str_repeat('*', strlen($card_number) - 8) : ""). '-' . substr($card_number, -4); 
			}
			else
			{
				return $card_number;
			}
		}
	
		switch ($card_type)
		{
			case 'MCD':
				if ($use_mask !== TRUE)
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-' . substr($card_number, 4, 4) . '-' . substr($card_number, 8, 4) . '-' . substr($card_number, 12, 4);
				}
				else
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-****-****-' . substr($card_number, 12, 4);
				}
				break;
			case 'VIS':
				if ($use_mask !== TRUE)
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-' . substr($card_number, 4, 4) . '-' . substr($card_number, 8, 4) . '-' . substr($card_number, 12, 4);
				}
				else
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-****-****-' . substr($card_number, 12, 4);
				}
				break;
			case 'DIS':
				if ($use_mask !== TRUE)
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-' . substr($card_number, 4, 4) . '-' . substr($card_number, 8, 4) . '-' . substr($card_number, 12, 4);
				}
				else
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-****-****-' . substr($card_number, 12, 4);
				}

				break;
			case 'AMX':
				if ($use_mask !== TRUE)
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-' . substr($card_number, 4, 6) . '-' . substr($card_number, 10, 5);
				}
				else
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-******-*' . substr($card_number, 11, 4);
				}
				break;
			case 'DIN':
				if ($use_mask !== TRUE)
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-' . substr($card_number, 4, 6) . '-' . substr($card_number, 10, 4);
				}
				else
				{
					$formatted_card_number = substr($card_number, 0, 4) . '-******-' . substr($card_number, 10, 4);
				}
				break;
		}	
		
		return $formatted_card_number;
	}
		
}


?>
