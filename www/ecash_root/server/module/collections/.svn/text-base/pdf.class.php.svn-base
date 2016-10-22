<?php
  /**
   * PDF Document Class
   * Used to create a generic PDF document using PDFlib
   * This particular version was written to implement the ez-pdf functions
   * @author Brian Ronald
   * @todo Remove deprecated methods
   */

class PDF_Document
{
	private $filename;		// Optional filename to write the pdf to disk

	private $pdf;			// The actual PDF Document handle

	private $font;			// Font pointer
	private $font_name;		// Name of current font
	private $font_size;		// Size of font

	private $page_size_x;
	private $page_size_y;

	private $current_x;
	private $current_y;
							
	public function __construct($resource_file = null)
	{
		if(! $this->pdf = pdf_new())
			throw new Exception( "Can't create PDF.  Is PDFLib installed?" );

		// Read in License Keys for PDFLib if they exist
		if(defined('PDFLIB_LICENSE_FILE') && file_exists(PDFLIB_LICENSE_FILE))
		{
			$qc_licenses = file(PDFLIB_LICENSE_FILE);
			foreach($qc_licenses as $license)
			{
				if(preg_match("/^(\w{7})-(\d{6})-(\d{6})-(\w{6})/", $license))
				{
					pdf_set_parameter($this->pdf, "license", rtrim($license));
				}
			}
		}
		
		// If a filename is specified, a file will automatically
		// be written.
		pdf_open_file($this->pdf, "");

		// the PDF Resource file is a .upr file that PDFlib uses
		// to map Font Names to their corresponding files
		if( ! empty($resource_file) )
		{
			pdf_set_parameter($this->pdf, "resourcefile", $resource_file);
		}
		
	}

	public function __destruct()
	{
		pdf_delete($this->pdf);
	}
	
	// Start a new page
	public function New_Page($x, $y)
	{
		pdf_begin_page($this->pdf, $x, $y);
		$this->page_size_x = $x;
		$this->page_size_y = $y;

		pdf_set_text_pos($this->pdf, $x, $y);
		
	}

	public function Get_Page_Size_X ()
	{
		return($this->page_size_x);
	}

	public function Get_Page_Size_Y ()
	{
		return($this->page_size_y);
	}

	// Ends the current page
	public function End_Page()
	{
		pdf_end_page($this->pdf);
	}

	// Used to describe the document
	public function Set_Info ($key, $val)
	{
		pdf_set_info($this->pdf, $key, $val);
	}

	// Sets parameters for PDFLib.  Used for setting colors, underlines,
	// etc.
	public function Set_Parameter ($key, $val)
	{	
		pdf_set_parameter($this->pdf, $key, $val);	
	}

	// Gets current parameter settings from PDFLib.
	public function Get_Parameter ($key)
	{	
		$parm = pdf_get_parameter($this->pdf, $key, 0);
		return($parm);
	}

	// spits out the raw PDF data
	public function Get_Buffer()
	{
		$buf = pdf_get_buffer($this->pdf);
		return $buf;
	}

	// These next two functions actually get the text-position for x & y which
	// are different than the graphic x & y positions.  We also set the classes'
	// internal pointers while we're at it.
	public function Get_X()
	{
		$this->current_x = pdf_get_value($this->pdf, "textx", 0);
		return ($this->current_x);
	}
	
	public function Get_Y()
	{
		$this->current_y = pdf_get_value($this->pdf, "texty", 0);
		return ($this->current_y);
	}
	
	// Display's a line of text.  If X and Y are specified it will 
	// relocate the cursor to X & Y to print, else it will continue
	// from the current pointer.
	public function Show ($text, $x, $y)
	{	
		// Had to remove the ctype calls since gentoo is retarded and specifically disables the
		// by default enabled ctype functions...  aint that smart?
		if( preg_match("/^\d*$/", $x) && preg_match("/\d*$/", $y) )
		//if((ctype_digit((string) $x)) && (ctype_digit((string) $y)))
		{	
			pdf_show_xy($this->pdf, $text, $x, $y );
		}
		else
		{
			pdf_show($this->pdf, $text);
		}

		// Get the current x & y positions after we've written to the page
		$this->Get_X();  $this->Get_Y();
	}

	// Close the PDF document.  If a filename was specified previously,
	// data will be written out to that file.  Otherwise, use the
	// Get_Buffer method to get the PDF data for output to the screen
	// or to store into a database field.
	public function Close ()
	{
		pdf_close($this->pdf);
	}

	public function Find_Font($font_name, $encoding, $embed = NULL )
	{
		// If they don't specify non-zero for embed, we want
		// to use the font right away
		if(($embed == NULL) || !(is_int($embed)))
		{
			$embed = 0;
		}
		
		// Set a font handle to our font
		$this->font = pdf_findfont($this->pdf, $font_name, $encoding, $embed);
		$this->font_name = $font_name;	
	}

	public function Set_Font($point_size)
	{
		pdf_setfont($this->pdf, $this->font, $point_size);
		$this->font_size = $point_size;
	}

	/* ez-pdf-like Functions */
	
	// Draw a line from x,y to x,y optionally specifying the width
	// Note: After specifying the width, any subsequent lines that
	// are drawn will use this width.
	public function line ($x, $y, $x2, $y2, $width = NULL)
	{
		if ($width)
		{
			pdf_setlinewidth($this->pdf, $width);
		}
		pdf_moveto($this->pdf, $x, $y);
		pdf_lineto($this->pdf, $x2, $y2);
		pdf_stroke($this->pdf);
	}

	// Not exactly how ez-pdf works, but this method simplifies
	// the selection of an embedded font
	public function selectFont ($font_name, $font_size, $embed = NULL )
	{
		// If they don't specify non-zero for embed, we want
		// to use the font right away
		if(($embed == NULL) || !(is_int($embed)))
		{
			$embed = 0;
		}
			$this->Find_Font($font_name, "auto", $embed);
			$this->Set_Font($font_size);
	}

	// This function is used to move the text cursor to a new
	// Y coordinate.
	public function ezSetY ($y)
	{
		pdf_set_text_pos($this->pdf, $this->current_x, $y);
		$this->current_y = $y;
	}

	// This is supposed to mimic the ezpdf function, but I added the font
	// and removed the formatting <b> <i> <u> text functions.  Any software
	// that used ezpdf and wants to use this class instead will need to be modified.
	//
	// Also, this class only supports basic text options for justifying left or
	// right with the value being the gap from the respective side.
	public function ezText ($string, $font_name, $font_size, $options)
	{
		$this->current_x = 0;

		if(is_array($options))
		{
			if(isset($options['left']))
			{
				$this->current_x = $this->current_x + $options['left'];
			}
			elseif (isset($options['right']))
			{			
				$this->current_x = $this->page_size_x - (pdf_stringwidth($this->pdf, $string, $this->font, $this->font_size) + $options['right']);		
			}
			// Check to see if the font is embedded or not
			if(isset($options['embed']))
			{
				$embed = $options['embed'];
			}
			else
			{
				$embed = 0;
			}
		}

		$this->selectFont($font_name, $font_size, $embed);
		
		// Advances to the next line automatically
		$this->ezSetY($this->current_y - $this->font_size);
		$this->Show($string, $this->current_x, $this->current_y);
		
		return($this->current_y);

	}
	
}


?>
