<?php

/**
 * Class to populate html forms with field values
 *
 * i want to satisfy two goals: to allow designers to develop html pages that contain
 * references to style sheets and other pages that are relative to the folder containing
 * those html pages; and to allow those pages to be read and displayed from cgi or php
 * scripts in different folders without having to manually modify the html pages.
 * there are only two ways to accomplish these goals:  require that all cgi or php scripts
 * that read and display html pages reside in the same folder as those pages; or, to modify
 * all links in those pages, at runtime, to make those links operational.  this can be
 * accomplished by modifying all link paths to be relative to the folder of the code or to
 * be relative to the document root.  in this class, i modify all links to make them relative
 * to document root.
 *
 * the steps used to convert local paths, used within an html file, to global paths relative
 * to document root that can be used anywhere, is as follows:
 * 1) determine the absolute path, relative to the document root, of the folder containing the html code.
 *    the only way to determine this to use the relative pathname of the html file passed as a parameter
 *    and the absolute path of the script, which is given by $_SERVER['SCRIPT_NAME'].
 * 2) replace all links in the html file, which are relative paths, with absolute paths.
 */

class Form
{
	protected $location;
	protected $contents;
	protected $fields;
	protected $field_count;
	protected $code_part;

/**
 * Method to create a form object
 * The form is read into memory, the field variables are counted,
 * and a list of all field variables is placed into the fields array.
 * $this-fields[i][0] = full pattern match
 * $this-fields[i][1] = first parenthesized subpattern match
 */

	function __construct ($location)
	{
		$this->location = $location;
		if (!file_exists($location)) 
		{
			print "<pre>".print_r(debug_backtrace(),true)."</pre>";
			die("file $location does not exist!");
		}
		$this->contents = file_get_contents($this->location)."<!-- -->";
		$this->field_count = preg_match_all('/%%%(.*?)%%%/',$this->contents,$this->fields,PREG_SET_ORDER);
#		print '<pre>$fields: '.print_r($this->fields,true)."</pre>\n";
		self::_relocate_links();
	}

/**
 * Method to insert field values
 * Field values are represented in the html form with the variable: %field_name%.
 * Each variable is replaced by a corresponding value from: $vars or $_REQUEST or $_SESSION, in that order.
 * Select, checkbox, and radio types are handled with a colon: %field_name:value%.
 * Variables of this type are replaced with "selected checked" because browsers seem to handle this well and
 * I don't have to know if this is a select option, or checkbox or radio.
 */

	public function Insert_Values ($vars=0)
	{
		if ( 0 == $this->field_count )
			return $this->contents;

		foreach ( $this->fields as $key => $field ) 
		{
			$pattern[] = "/{$field[0]}/";
			if (preg_match('/([^:]+):([^:]+)/',$field[1],$m))
			{
				if ( ($vars->{$m[1]} == $m[2])
				|| ($_REQUEST[$m[1]] == $m[2])
				|| ($_SESSION[$m[1]] == $m[2]) ) {
					$replace[] = 'selected checked';
				} 
				else 
				{
					$replace[] = '';
				}
			} 
			else 
			{
				if( ! empty($field[1]) )
				{
					$replace[] =
						  ( isset($vars->{$field[1]}) && !(is_array($vars->{$field[1]}) && empty($vars->{$field[1]}) ) ? $vars->{$field[1]}
						: ( isset($_REQUEST[$field[1]]) && !(is_array($_REQUEST[$field[1]]) && empty($_REQUEST[$field[1]]) )  ? $_REQUEST[$field[1]]
						: ( isset($_SESSION[$field[1]]) && !(is_array($_SESSION[$field[1]]) && empty($_SESSION[$field[1]]) ) ? $_SESSION[$field[1]]
						: '')));
				}
			}
		}
		#print '<pre>$pattern: '.print_r($pattern,true).'</pre>';
		#print '<pre>$replace: '.print_r($replace,true).'</pre>';
		return preg_replace($pattern,$replace,$this->contents);
	}

/**
 * Method to output a populated form
 */

	public function Display ($vars=0)
	{
		print $this->Insert_values($vars);
		return;
	}

/**
 * Method to output a populated form
 */

	public function As_String ($vars=0)
	{
		return $this->Insert_values($vars);
	}

/**
 * Method to dump a populated form.  Used for debug only.
 */

	public function Dump()
	{
		print '<pre>'
			."   location: $this->location\n"
			."field count: $this->field_count\n"
			.'     fields: '.print_r($this->fields,TRUE)."\n"
			.'</pre>';
	}


	/**
	 * build the pattern and replace arrays to relocate the links
	 * $view_part is an array of each part of the path to the view folder, relative to document root
	 * the pattern i want to develop is:
	 * /(<[^!>]* )(href|src|background)="([^#?.\/])
	 *  ---(1)---+-------(2)----------   --(3)--
	 *	1) skip comments; i don't want to pick up 'src=' in a javascript and js is usually commented out
	 *	2) the links i'm relocating;
	 *	3) skip any special refs as well as the links i just built
	 * also note that, by convention, the designers aren't supposed to use root notation:
	 * src=/main.html.  this will certainly screw up epointmarketing.
	 */

	private function _relocate_links()
	{
#		$code_path = dirname($_SERVER['SCRIPT_NAME']);
#		$code_part = preg_split('/\//',$code_path);
#		$view_path = dirname($location);
#		$view_part = preg_split('/\//',$view_path);
#		print "\n<pre>$code_path: ".print_r($code_part,true)."</pre>\n";
#		print "\n<pre>$view_path: ".print_r($view_part,true)."</pre>\n";

#		$view_path = make_full($code_part,$view_part);
#		$view_part = preg_split('/\//',$view_path);
		$view_part = preg_split('/\//',self::_make_full(preg_split('/\//',dirname($_SERVER['SCRIPT_NAME'])),preg_split('/\//',dirname($this->location))));
#		print "\n<pre>\$view_part: ".print_r($view_part,true)."</pre>\n";
		$n = count($view_part)-1;
		for ( $i=count($view_part)-1; $i>=0; $i-- ) 
		{
			$pattern[] = '/(<[^!>]+ )(href|src|background)="'.str_repeat('\.\.\/',$i).'/';
			$replace[] = '$1$2="'.implode('/',$view_part).'/';
			array_pop($view_part);
		}
		$replace = array_reverse($replace);
		# gah this is ugly. original pattern doesn't take into account if a link is absolute to another domain,
		# which it is for sites that require security, like flyfone and are redirected to epointmarketing because
		# we don't want to buy an ssl cert for flyfone.com... for some good reason i'm sure
		#$pattern[$n] = substr($pattern[$n],0,-1).'([^#?.\/])/';
		$pattern[$n] = substr($pattern[$n],0,-1).'((?!\w+:)[^#?.\/])/';
		$replace[$n] .= '$3';
#		print "\n<pre>\$pattern: ".print_r($pattern,true)."</pre>\n";
#		print "\n<pre>\$replace: ".print_r($replace,true)."</pre>\n";

		// make all links relative to root
		$this->contents = preg_replace($pattern,$replace,$this->contents);
#		print "\n<pre>\$this->contents:\n{$this->contents}</pre>\n";

		// it's possible that we're passed an html document with a syntax error: too many back refs
		// let's flag it here so we're not confused into thinking the bugs lies with Form.
		/*if ( preg_match('/\.\.\//',$this->contents) ) {
			print "\n<pre>html document contains too many back refs</pre>\n";
		}*/
		return;
	}


	/**
	 * path b is relative to path a, return b as a full path
	 * example:  a = code/foo/bar; b = ../my/dir; return = code/foo/my/dir
	 */

	private function _make_full($a,$b)
	{
	#	print "\n<pre>\$a:".print_r($a,true)."</pre>";
	#	print "\n<pre>\$b:".print_r($b,true)."</pre>";
		// test for a boundary condition; a is the root directory;
		if ( $a[0] == '' && $a[1] == '' )
			array_pop($a);
		$i = count($a) - 1;
		foreach ( $b as $value ) 
		{
			switch ( $value ) 
			{
			case '..':
				array_pop($a);
				break;
			case '.':
				break;
			default:
				array_push($a,$value);
			}
		}
	#	print "\n<pre>\$a:".implode('/',$a)."</pre>";
			return implode('/',$a);
	}

}
?>
