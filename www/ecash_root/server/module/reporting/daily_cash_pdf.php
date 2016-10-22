<?php
/**
 * @package reporting
 *
 * @copyright Copyright &copy; 2006 The Selling Source, Inc.
 *
 * @version $Revision$
 */

/**
 * Generates the pdf of the daily cash report.
 *
 * <b>Revision History:</b>
 * <ul>
 *     <li><b>2007-10-23 - mlively</b><br>
 *         The vertical margin has been changed from 36 to 27 (3/8 of an inch)
 *     </li>
 * </ul>
 */
class Daily_Cash_Report_PDF {
	const HORZ_MARGIN = 36;
	const VERT_MARGIN = 21;
	
	const PAGE_HEIGHT = 792;
	const PAGE_WIDTH = 612;
	
	private $pdf;
	private $fonts = array();
	private $formats = array();
	private $data = array();
	
	private $company_name;
	private $date;
	
	public function __construct($company_name, $date, $data) {
		$this->company_name = $company_name;
		$this->date = $date;
		$this->data = $data;
	}
	
	public function Create_PDF($filename) {
		$this->Initialize_PDF($filename);
		$this->Write_Header();
		$this->Write_Balance_Table();
		$this->Write_Future_Items_Table();
		$this->Write_Monthly_Table();
		$this->Close_PDF();
	}
	
	private function Initialize_PDF($filename) {
		$this->pdf = new PDFLib();
		

		// Read in License Keys for PDFLib if they exist
		if(defined('PDFLIB_LICENSE_FILE') && file_exists(PDFLIB_LICENSE_FILE)) {
			$qc_licenses = file(PDFLIB_LICENSE_FILE);
			foreach($qc_licenses as $license) {
				if(preg_match("/^(\w{7})-(\d{6})-(\d{6})-(\w{6})/", $license)) {
					$this->pdf->set_parameter("license", rtrim($license));
				}
			}
		}
		if ($this->pdf->begin_document($filename, "") == 0) {
			throw new Exception("Error creating PDF: {$this->pdf->get_errmsg()}");
		}
		
		$this->Setup_Fonts();
		$this->Setup_Formats();
		
		$this->pdf->set_info("Creator", "ECash");
		$this->pdf->set_info("Title", "Daily Cash Report");
		$this->pdf->begin_page_ext(self::PAGE_WIDTH , self::PAGE_HEIGHT , "");
		$this->pdf->setfont($this->fonts['Helvetica'], 9.0);
	}
	
	private function Close_PDF() {
		$this->pdf->end_page_ext("");
		$this->pdf->end_document("");
	}
	
	private function Setup_Fonts() {
		$this->fonts['Helvetica'] = $this->pdf->load_font("Helvetica", "iso8859-1", "");
	}
	
	private function Setup_Formats() {
		$this->formats['BOLD_FONT'] = "fontname=Helvetica-Bold fontsize=7 encoding=iso8859-1 leftindent=3";
		$this->formats['NORMAL_FONT'] = "fontname=Helvetica fontsize=7 encoding=iso8859-1 leftindent=3";
		$this->formats['UNDERLINE'] = "underline=true";
		$this->formats['ITALICS'] = "italicangle=-10";
		$this->formats['CENTER'] = "alignment=center";
		$this->formats['LEFT'] = "alignment=left";
		$this->formats['RIGHT'] = "alignment=right rightindent=3";
	}
	
	private function Write_Header() {
		$this->Text_TL($this->company_name, self::HORZ_MARGIN, self::VERT_MARGIN, 100, 9, "{$this->formats['BOLD_FONT']}");
		$this->Text_TL($this->date, self::PAGE_WIDTH - self::HORZ_MARGIN - 100, self::VERT_MARGIN, 100, 9, "{$this->formats['BOLD_FONT']} {$this->formats['RIGHT']}");
	}

	/**
	 * Writes the balance table to the pdf.
	 *
	 * <b>Revision History:</b>
	 * <ul>
	 *     <li><b>2007-10-23 - mlively</b><br>
	 *         The card loan amounts have been added.
	 *     </li>
	 * </ul>
	 */
	private function Write_Balance_Table() {
		$top = 12 + self::VERT_MARGIN;
		$row_height = 8.5;
		$number_of_rows = 19;
		$labels_width = 80;
		$labels_left_1 = self::HORZ_MARGIN;
		$labels_left_2 = $labels_left_1 + $labels_width;
		$labels_left_3 = $labels_left_2 + $labels_width;
		
		$table_left = self::HORZ_MARGIN + 432;
		$table_column_width = 54;
		$table_left_column2 = $table_left + $table_column_width;
		
		$this->pdf->setlinewidth(0.40);
		$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
		
		/**
		 * Build the table first
		 * $this->pdf->rect($x, $y, $w, $h);
		 */
		
		//Define the height positions of elements in the table, so we can go adding and removing elements all willy nilly
		$label_heights = array(
		
			'BEGINNING CHECKING BALANCE'	=> 2,
			'DEPOSITS'					 	=> 4,
			'TOTAL RECEIPTS'				=> 11,
			'DISBURSEMENTS'					=> 12,
			'TOTAL DISBURSED'				=> 16,
			'ENDING CHECKING BALANCE'		=> 18,
			'OPERATING ACCOUNT'				=> 2,
			'RETURNS'						=> 2,
			'CREDIT CARD PAYMENTS'			=> 5,
			'CHARGEBACKS'					=> 6,
			//'WESTERN UNION PAYMENTS'		=> 7,
			'MONEY ORDER'					=> 7,
			//'QUICK CHECK DEPOSIT'			=> 9,
			//'MONEYGRAM'						=> 10,
			//'CRSI RECOVERY'					=> 11,
			//'RECOVERY'						=> 12,
			//'FINAL COLLECTIONS'				=> 13,
			'DEBIT RETURNS'					=> 8,
			'CREDIT RETURNS'				=> 9,
			'LOANS'							=> 12,
		//	'CARD LOANS'					=> 19,
			'DEBIT RETURNS DISBURSE'					=> 13,
			'CREDIT RETURNS DISBURSE'				=> 14,
			'INTERCEPT_RESERVE'				=> 2,
			'TOTAL DEBITED'					=> 4,
			
			);
		
		
		
		/**
		 * fill table header
		 */
		$this->Rect_TL($table_left, $top, $table_column_width * 2, $row_height * $label_heights['BEGINNING CHECKING BALANCE']);
		$this->pdf->fill();
		
		/**
		 * fill highlighted rows
		 */
		$this->pdf->setcolor('fill', 'rgb', 1, 1, 176/255, 0);
		$this->Rect_TL($table_left_column2, $top + ($row_height * $label_heights['TOTAL RECEIPTS']), $table_column_width, $row_height);
		$this->Rect_TL($table_left_column2, $top + ($row_height * $label_heights['DEBIT RETURNS DISBURSE']), $table_column_width, $row_height);
		$this->Rect_TL($table_left_column2, $top + ($row_height * $label_heights['CREDIT RETURNS DISBURSE']), $table_column_width, $row_height);
		$this->Rect_TL($table_left, $top + ($row_height * $label_heights['TOTAL DISBURSED']), $table_column_width * 2, $row_height);
		$this->Rect_TL($table_left_column2, $top + ($row_height * $label_heights['ENDING CHECKING BALANCE']), $table_column_width, $row_height);
		$this->pdf->fill();
		
		/**
		 * fill the cute little background at the bottom.
		 */
//		$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
//		$this->Rect_TL($table_left, $top + ($row_height * 24) - 1, $table_column_width * 2, 1);
//		$this->pdf->fill();
		
		/**
		 * Add strokes to the table
		 */
		$this->Rect_TL($table_left, $top, $table_column_width * 2, $row_height * $number_of_rows);
		
		$this->Line_TL($table_left + $table_column_width, $top,
					   $table_left + $table_column_width, $top + ($row_height * $number_of_rows));
		
		$this->Line_TL($table_left, $top + ($row_height * $label_heights['BEGINNING CHECKING BALANCE']),
					   $table_left + ($table_column_width * 2), $top + ($row_height * $label_heights['BEGINNING CHECKING BALANCE']));
		
		$this->Line_TL($table_left, $top + ($row_height * $label_heights['TOTAL DISBURSED']),
					   $table_left + ($table_column_width * 2), $top + ($row_height * $label_heights['TOTAL DISBURSED']));
		
		$this->Line_TL($table_left, $top + ($row_height * $label_heights['ENDING CHECKING BALANCE']),
					   $table_left + ($table_column_width * 2), $top + ($row_height * $label_heights['ENDING CHECKING BALANCE']));
					   
		$this->Line_TL($table_left, $top + ($row_height * $label_heights['ENDING CHECKING BALANCE']) - 1,
					   $table_left + ($table_column_width * 2), $top + ($row_height * $label_heights['ENDING CHECKING BALANCE']) - 1);
		$this->pdf->stroke();
		
		/**
		 * Build Label Backgrounds
		 */
		$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
		$this->Rect_TL($labels_left_1, $top + ($row_height * $label_heights['BEGINNING CHECKING BALANCE']), $labels_width * 3, $row_height);
		$this->Rect_TL($labels_left_1, $top + ($row_height * $label_heights['DEPOSITS']), $labels_width, $row_height);
		$this->Rect_TL($labels_left_2, $top + ($row_height * $label_heights['TOTAL RECEIPTS']), $labels_width * 2, $row_height);
		$this->Rect_TL($labels_left_1, $top + ($row_height * $label_heights['DISBURSEMENTS']), $labels_width, $row_height);
		$this->Rect_TL($labels_left_2, $top + ($row_height * $label_heights['TOTAL DISBURSED']), $labels_width * 2, $row_height);
		$this->Rect_TL($labels_left_1, $top + ($row_height * $label_heights['ENDING CHECKING BALANCE']), $labels_width * 2, $row_height);
		$this->pdf->fill();
		
		/**
		 * Build Labels
		 */
		$this->Text_TL("BEGINNING CHECKING BALANCE", 
			$labels_left_1, $top + ($row_height * $label_heights['BEGINNING CHECKING BALANCE']) - 1, 
			$labels_width * 3, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['UNDERLINE']} {$this->formats['ITALICS']}");

		$this->Text_TL("DEPOSITS", 
			$labels_left_1, $top + ($row_height * $label_heights['DEPOSITS']) - 1, 
			$labels_width, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['UNDERLINE']} {$this->formats['ITALICS']}");

		$this->Text_TL("TOTAL RECEIPTS", 
			$labels_left_2, $top + ($row_height * $label_heights['TOTAL RECEIPTS']) - 1,
			$labels_width * 2, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['UNDERLINE']} {$this->formats['ITALICS']}");

		$this->Text_TL("DISBURSEMENTS", 
			$labels_left_1, $top + ($row_height * $label_heights['DISBURSEMENTS']) - 1,
			$labels_width, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['UNDERLINE']} {$this->formats['ITALICS']}");

		$this->Text_TL("TOTAL DISBURSED", 
			$labels_left_2, $top + ($row_height * $label_heights['TOTAL DISBURSED']) - 1,
			$labels_width * 2, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['UNDERLINE']} {$this->formats['ITALICS']}");

		$this->Text_TL("ENDING CHECKING BALANCE", 
			$labels_left_1, $top + ($row_height * $label_heights['ENDING CHECKING BALANCE']) - 1,
			$labels_width * 3, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['UNDERLINE']} {$this->formats['ITALICS']}");

		$this->Text_TL("OPERATING ACCOUNT", 
			$table_left, $top, 
			$table_column_width, $row_height * $label_heights['OPERATING ACCOUNT'],
			"{$this->formats['BOLD_FONT']} {$this->formats['CENTER']} {$this->formats['ITALICS']}");

		$this->Text_TL("RETURNS<avoidbreak=false>",
			$table_left_column2, $top, 
			$table_column_width, $row_height * $label_heights['RETURNS'],
			"{$this->formats['BOLD_FONT']} {$this->formats['CENTER']} {$this->formats['ITALICS']} adjustmethod=spread");
		//Let's use  variable and increment/decrement to define the locations of the
		
		$this->Text_TL("DEPOSITS", 
			$labels_left_2, $top + ($row_height * $label_heights['DEPOSITS']), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		$this->Text_TL("CREDIT CARD PAYMENTS", 
			$labels_left_2, $top + ($row_height * $label_heights['CREDIT CARD PAYMENTS']), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		$this->Text_TL("CHARGEBACKS", 
			$labels_left_2, $top + ($row_height * $label_heights['CHARGEBACKS']), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		/*$this->Text_TL("WESTERN UNION PAYMENTS", 
			$labels_left_2, $top + ($row_height * 7), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");
*/
		$this->Text_TL("MONEY ORDERS", 
			$labels_left_2, $top + ($row_height * $label_heights['MONEY ORDER']), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

/*		$this->Text_TL("QUICK CHECK DEPOSIT", 
			$labels_left_2, $top + ($row_height * 9), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		$this->Text_TL("MONEYGRAM", 
			$labels_left_2, $top + ($row_height * 10), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		$this->Text_TL("CRSI RECOVERY", 
			$labels_left_2, $top + ($row_height * 11), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		$this->Text_TL("RECOVERY", 
			$labels_left_2, $top + ($row_height * 12), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");
*/
		/*$this->Text_TL("FINAL COLLECTIONS", 
			$labels_left_2, $top + ($row_height * 13), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");
*/
		$this->Text_TL("DEBIT RETURNS", 
			$labels_left_2, $top + ($row_height * $label_heights['DEBIT RETURNS']), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		$this->Text_TL("CREDIT RETURNS",
			$labels_left_2, $top + ($row_height * $label_heights['CREDIT RETURNS']),
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		$this->Text_TL("LOANS", 
			$labels_left_2, $top + ($row_height * $label_heights['LOANS']), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

/*		$this->Text_TL("CARD LOANS",
			$labels_left_2, $top + ($row_height * 19),
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");
*/
		$this->Text_TL("DEBIT RETURNS",
			$labels_left_2, $top + ($row_height * $label_heights['DEBIT RETURNS DISBURSE']), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");

		$this->Text_TL("CREDIT RETURNS",
			$labels_left_2, $top + ($row_height * $label_heights['CREDIT RETURNS DISBURSE']), 
			$labels_width * 2, $row_height,
			"{$this->formats['NORMAL_FONT']}");
			
		/**
		 * Set Values
		 */
		$this->Text_TL(number_format($this->data['intercept_reserve'], 2),
			$table_left_column2, $top + ($row_height * $label_heights['INTERCEPT_RESERVE']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");

		$this->Text_TL(number_format($this->data['period']['total debited']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['TOTAL DEBITED']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['period']['credit card payments']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['CREDIT CARD PAYMENTS']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");

		$this->Text_TL(number_format($this->data['period']['chargebacks']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['CHARGEBACKS']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");

/*		$this->Text_TL(number_format($this->data['period']['western union deposit']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['WESTERN UNION DEPOSIT']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");*/
		
		$this->Text_TL(number_format($this->data['period']['money order deposit']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['MONEY ORDER']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		/*$this->Text_TL(number_format($this->data['period']['quick check deposit']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['QUICK CHECK DEPOSIT']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['period']['moneygram deposit']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['MONEYGRAM DEPOSIT']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");*/
		
/*		$this->Text_TL(number_format($this->data['period']['crsi recovery']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['CRSI RECOVERY']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");*/
		
	/*	$this->Text_TL(number_format($this->data['period']['recovery']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['RECOVERY']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");*/
		
/*		$this->Text_TL(number_format($this->data['period']['final collections']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['FINAL COLLECTIONS']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");*/
		
		$this->Text_TL(number_format($this->data['period']['debit returns']['span'], 2),
			$table_left_column2, $top + ($row_height * $label_heights['DEBIT RETURNS']),
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['period']['credit returns']['span'], 2),
			$table_left_column2, $top + ($row_height * $label_heights['CREDIT RETURNS']),
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['period']['debit returns']['span'] + $this->data['period']['credit returns']['span'], 2),
			$table_left_column2, $top + ($row_height * $label_heights['TOTAL RECEIPTS']),
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['period']['loan disbursement']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['DISBURSEMENTS']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
/*		$this->Text_TL(number_format($this->data['card_loan_disbursement'], 2),
			$table_left, $top + ($row_height * 19),
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
*/
		$this->Text_TL(number_format($this->data['period']['debit returns']['span'], 2),
			$table_left_column2, $top + ($row_height * $label_heights['DEBIT RETURNS DISBURSE']),
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");

		$this->Text_TL(number_format($this->data['period']['credit returns']['span'], 2),
			$table_left_column2, $top + ($row_height * $label_heights['CREDIT RETURNS DISBURSE']),
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");

		$this->Text_TL(number_format($this->data['period']['loan disbursement']['span'], 2),
			$table_left, $top + ($row_height * $label_heights['TOTAL DISBURSED']), 
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['period']['debit returns']['span'] + $this->data['period']['credit returns']['span'], 2),
			$table_left_column2, $top + ($row_height * $label_heights['TOTAL DISBURSED']),
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['intercept_reserve'], 2),
			$table_left_column2, $top + ($row_height * $label_heights['ENDING CHECKING BALANCE']) - 1,
			$table_column_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
	}
	
	private function Write_Future_Items_Table() {
		$top = 227 + self::VERT_MARGIN;
		$left = self::HORZ_MARGIN;
		$label_indent = 40;
		$label_width = 160;
		$table_left = $left + $label_width;
		$cell_width = 55;
		$row_height = 8.5;
		
		/**
		 * Build label backgrounds
		 */
		$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
		$this->Rect_TL($left, $top + $row_height, $label_width / 2, $row_height);
		$this->Rect_TL($left, $top + ($row_height * 2), $label_width, $row_height * 4);
		$this->Rect_TL($table_left, $top + ($row_height * 2), $cell_width * 5, $row_height * 2);
		$this->Rect_TL($table_left + $cell_width, $top + ($row_height * 5), $cell_width * 4, $row_height);
		$this->pdf->fill();
		
		/**
		 * Build the lighter backgrounds
		 */
		$this->pdf->setcolor('fill', 'rgb', 1, 1, 176/255, 0);
		$this->Rect_TL($table_left, $top + ($row_height * 4), $cell_width, $row_height);
		$this->pdf->fill();
		
		/**
		 * Add the strokes
		 */
		$this->Rect_TL($table_left, $top + ($row_height * 2), $cell_width * 5, $row_height * 4);
		
		$this->Line_TL($table_left, $top + ($row_height * 4),
					   $table_left + ($cell_width * 5), $top + ($row_height * 4));
		
		$this->Line_TL($table_left, $top + ($row_height * 5),
					   $table_left + ($cell_width * 5), $top + ($row_height * 5));
		
		$this->Line_TL($table_left + $cell_width, $top + ($row_height * 2),
					   $table_left + $cell_width, $top + ($row_height * 6));
		
		$this->Line_TL($table_left + ($cell_width * 2), $top + ($row_height * 2),
					   $table_left + ($cell_width * 2), $top + ($row_height * 6));
		
		$this->Line_TL($table_left + ($cell_width * 3), $top + ($row_height * 2),
					   $table_left + ($cell_width * 3), $top + ($row_height * 6));
		
		$this->Line_TL($table_left + ($cell_width * 4), $top + ($row_height * 2),
					   $table_left + ($cell_width * 4), $top + ($row_height * 6));
		
		$this->pdf->stroke();
		
		/**
		 * Add the labels
		 */
		$this->Text_TL("TOTAL",
			$table_left, $top + ($row_height * 2), 
			$cell_width, $row_height * 2,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['CENTER']}");
		
		$this->Text_TL("WEEKLY",
			$table_left + $cell_width, $top + ($row_height * 2), 
			$cell_width, $row_height * 2,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['CENTER']}");
		
		$this->Text_TL("BI-WEEKLY",
			$table_left + ($cell_width * 2), $top + ($row_height * 2), 
			$cell_width, $row_height * 2,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['CENTER']}");
		
		$this->Text_TL("SEMI-\nMONTHLY",
			$table_left + ($cell_width * 3), $top + ($row_height * 2), 
			$cell_width, $row_height * 2,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['CENTER']}");
		
		$this->Text_TL("MONTHLY",
			$table_left + ($cell_width * 4), $top + ($row_height * 2), 
			$cell_width, $row_height * 2,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['CENTER']}");
		
		$this->Text_TL("FUTURE ITEMS",
			$left, $top + $row_height - 1, 
			$label_width / 2, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['UNDERLINE']} {$this->formats['ITALICS']}");
		
		$this->Text_TL("CUSTOMERS",
			$left, $top + ($row_height * 2), 
			$label_width, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['LEFT']}");
		
		$this->Text_TL("ACTIVE",
			$left + $label_indent, $top + ($row_height * 4), 
			$label_width - $label_indent, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['LEFT']}");
		
		$this->Text_TL("% OF ACTIVE",
			$left + $label_indent, $top + ($row_height * 5), 
			$label_width - $label_indent, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['LEFT']}");
		
		$current_row = 6;
		$totals = array(
			'weekly' => 0,
			'biweekly' => 0,
			'semimonthly' => 0,
			'monthly' => 0,
		);
		foreach ($this->data['future'] as $label => $values) {
			$totals['weekly'] += $values['weekly'];
			$totals['bi_weekly'] += $values['bi_weekly'];
			$totals['twice_monthly'] += $values['twice_monthly'];
			$totals['monthly'] += $values['monthly'];
			$totals['totals'] += array_sum($values);
			
			if ($label == 'active') continue;
			
			$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
			$this->Rect_TL($left, $top + ($row_height * $current_row), $label_width, $row_height);
			$this->pdf->fill();
			
			$this->pdf->setcolor('fill', 'rgb', 1, 1, 176/255, 0);
			$this->Rect_TL($table_left, $top + ($row_height * $current_row), $cell_width, $row_height);
			$this->pdf->fill();
			
			$this->Rect_TL($table_left, $top + ($row_height * $current_row), $cell_width, $row_height);
			$this->Rect_TL($table_left + ($cell_width * 1), $top + ($row_height * $current_row), $cell_width, $row_height);
			$this->Rect_TL($table_left + ($cell_width * 2), $top + ($row_height * $current_row), $cell_width, $row_height);
			$this->Rect_TL($table_left + ($cell_width * 3), $top + ($row_height * $current_row), $cell_width, $row_height);
			$this->Rect_TL($table_left + ($cell_width * 4), $top + ($row_height * $current_row), $cell_width, $row_height);
			$this->pdf->stroke();
			
			$this->Text_TL(strtoupper($label),
				$left + $label_indent, $top + ($row_height * $current_row), 
				$label_width - $label_indent, $row_height,
				"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['LEFT']}");
			
			$this->Text_TL(number_format($values['weekly'] + $values['bi_weekly'] + $values['twice_monthly'] + $values['monthly'], 0),
				$table_left, $top + ($row_height * $current_row), 
				$cell_width, $row_height,
				"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
			
			$this->Text_TL(number_format($values['weekly'], 0),
				$table_left + ($cell_width * 1), $top + ($row_height * $current_row), 
				$cell_width, $row_height,
				"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
			
			$this->Text_TL(number_format($values['bi_weekly'], 0),
				$table_left + ($cell_width * 2), $top + ($row_height * $current_row), 
				$cell_width, $row_height,
				"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
			
			$this->Text_TL(number_format($values['twice_monthly'], 0),
				$table_left + ($cell_width * 3), $top + ($row_height * $current_row), 
				$cell_width, $row_height,
				"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
			
			$this->Text_TL(number_format($values['monthly'], 0),
				$table_left + ($cell_width * 4), $top + ($row_height * $current_row), 
				$cell_width, $row_height,
				"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
			
			$current_row++;
		}
		
		/**
		 * Active Values
		 */
		$this->Text_TL(number_format($this->data['future']['active']['weekly'] + $this->data['future']['active']['bi_weekly'] + $this->data['future']['active']['twice_monthly'] + $this->data['future']['active']['monthly'], 0),
			$table_left, $top + ($row_height * 4), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($this->data['future']['active']['weekly'], 0),
			$table_left + ($cell_width * 1), $top + ($row_height * 4), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($this->data['future']['active']['bi_weekly'], 0),
			$table_left + ($cell_width * 2), $top + ($row_height * 4), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($this->data['future']['active']['twice_monthly'], 0),
			$table_left + ($cell_width * 3), $top + ($row_height * 4), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($this->data['future']['active']['monthly'], 0),
			$table_left + ($cell_width * 4), $top + ($row_height * 4), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		/**
		 * Percentages
		 */
		
		
		
		$total_active = $this->data['future']['active']['weekly'] + $this->data['future']['active']['bi_weekly'] + $this->data['future']['active']['twice_monthly'] + $this->data['future']['active']['monthly'];
		
		$weekly_active = $total_active ? $this->search_results['future']['active']['weekly'] / 
							$total_active : 0;
		
		$biweekly_active = $total_active ? $this->search_results['future']['active']['bi_weekly'] / 
							$total_active : 0;

		$twice_monthly_active = $total_active ? $this->search_results['future']['active']['twice_monthly'] / 
							$total_active : 0;
							
		$monthly_active = $total_active ? $this->search_results['future']['active']['monthly'] / 
							$total_active : 0;
							
		
		
		
		$this->Text_TL(number_format($weekly_active * 100, 1).'%',
			$table_left + ($cell_width * 1), $top + ($row_height * 5), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($biweekly_active * 100, 1).'%',
			$table_left + ($cell_width * 2), $top + ($row_height * 5), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($twice_monthly_active * 100, 1).'%',
			$table_left + ($cell_width * 3), $top + ($row_height * 5), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($monthly_active * 100, 1).'%',
			$table_left + ($cell_width * 4), $top + ($row_height * 5), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		/**
		 * Draw Totals
		 */
		$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
		$this->Rect_TL($left, $top + ($row_height * $current_row), $label_width, $row_height);
		$this->pdf->fill();
		
		$this->pdf->setcolor('fill', 'rgb', 1, 1, 176/255, 0);
		$this->Rect_TL($table_left, $top + ($row_height * $current_row), $cell_width, $row_height);
		$this->pdf->fill();
		
		$this->Rect_TL($table_left, $top + ($row_height * $current_row), $cell_width, $row_height);
		$this->Rect_TL($table_left + ($cell_width * 1), $top + ($row_height * $current_row), $cell_width, $row_height);
		$this->Rect_TL($table_left + ($cell_width * 2), $top + ($row_height * $current_row), $cell_width, $row_height);
		$this->Rect_TL($table_left + ($cell_width * 3), $top + ($row_height * $current_row), $cell_width, $row_height);
		$this->Rect_TL($table_left + ($cell_width * 4), $top + ($row_height * $current_row), $cell_width, $row_height);
		$this->pdf->stroke();
		
		$this->Text_TL("TOTAL",
			$left + ($label_indent * 2), $top + ($row_height * $current_row), 
			$label_width - ($label_indent * 2), $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['LEFT']}");
		
		$this->Text_TL(number_format($totals['weekly'] + $totals['bi_weekly'] + $totals['twice_monthly'] + $totals['monthly'], 0),
			$table_left, $top + ($row_height * $current_row), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($totals['weekly'], 0),
			$table_left + ($cell_width * 1), $top + ($row_height * $current_row), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($totals['bi_weekly'], 0),
			$table_left + ($cell_width * 2), $top + ($row_height * $current_row), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($totals['twice_monthly'], 0),
			$table_left + ($cell_width * 3), $top + ($row_height * $current_row), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
		
		$this->Text_TL(number_format($totals['monthly'], 0),
			$table_left + ($cell_width * 4), $top + ($row_height * $current_row), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");
	}
	
	/**
	 * Writes the monthly table to the pdf.
	 *
	 * <b>Revision History:</b>
	 * <ul>
	 *     <li><b>2007-10-23 - mlively</b>
	 *         Card reactivations counts have been added to the monthly table. 
	 *     </li>
	 * </ul>
	 */
	private function Write_Monthly_Table() {
		$top = 445 + self::VERT_MARGIN;
		$left = self::HORZ_MARGIN;
		$label_width = 160;
		$table_left = $left + $label_width;
		$cell_width = 55;
		$row_height = 8.5;
		
		/**
		 * Build label backgrounds
		 */
		$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
		$this->Rect_TL($left, $top, $label_width + ($cell_width * 1), $row_height);
		$this->pdf->fill();
		
		/**
		 * Add the strokes
		 */
		$this->Rect_TL($table_left, $top, $cell_width, $row_height);
	//	$this->Rect_TL($table_left + ($cell_width * 1), $top, $cell_width, $row_height);
	//	$this->Rect_TL($table_left + ($cell_width * 2), $top, $cell_width, $row_height);
		$this->pdf->stroke();
		
		/**
		 * Add the labels
		 */
		$this->Text_TL("CURRENT PERIOD",
			$left, $top, 
			$label_width, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['LEFT']}");
		
		$this->Text_TL("PERIOD",
			$table_left, $top, 
			$cell_width, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['CENTER']}");
		
		/*$this->Text_TL("WEEK",
			$table_left + ($cell_width * 1), $top, 
			$cell_width, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['CENTER']}");
		
		$this->Text_TL("MONTH",
			$table_left + ($cell_width * 2), $top, 
			$cell_width, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['CENTER']}");*/
			
		$current_row = 1;
		$totals = array(
			'weekly' => 0,
			'biweekly' => 0,
			'semimonthly' => 0,
			'monthly' => 0,
		);
		foreach ($this->data['period'] as $label => $values) {
			/**
			 * Special processing
			 */
			switch ($label) {
				case 'nsf$':
					continue 2;
				case 'debit returns':
//					$this->pdf->setcolor('fill', 'rgb', 1, 1, 176/255, 0);
//					$this->Rect_TL($table_left + ($cell_width * 1), $top + ($row_height * $current_row), $cell_width, $row_height);
//					$this->pdf->fill();
					
//					$this->Rect_TL($table_left + ($cell_width * 1), $top + ($row_height * $current_row), $cell_width, $row_height);
//					$this->pdf->stroke();
					/*$percentage = $values['month']? number_format($values['month'] / $this->data['monthly']['total debited']['month'] * 100, 1):0;
					$this->Text_TL($percentage.'%',
						$table_left + ($cell_width * 3), $top + ($row_height * $current_row), 
						$cell_width, $row_height,
						"{$this->formats['NORMAL_FONT']} {$this->formats['CENTER']}");*/
					
					break;
				case 'net cash collected':
					$this->pdf->setcolor('fill', 'rgb', 1, 1, 176/255, 0);
					$this->Rect_TL($table_left, $top + ($row_height * $current_row), $cell_width, $row_height);
					//$this->Rect_TL($table_left + ($cell_width * 1), $top + ($row_height * $current_row), $cell_width, $row_height);
					//$this->Rect_TL($table_left + ($cell_width * 2), $top + ($row_height * $current_row), $cell_width, $row_height);
					$this->pdf->fill();
					
					//$this->Text_TL(number_format($this->data['period']['total debited']['span'] - $this->data['period']['debit returns']['span'], 2),
					$this->Text_TL(number_format($this->data['period']['total debited']['span'] - $this->data['period']['nsf$']['span'], 2),
						$table_left, $top + ($row_height * $current_row), 
						$cell_width, $row_height,
						"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
						
					/*$this->Text_TL(number_format($this->data['monthly']['total debited']['week'] - $this->data['monthly']['debit returns']['week'], 2),
						$table_left + ($cell_width * 1), $top + ($row_height * $current_row), 
						$cell_width, $row_height,
						"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
					$this->Text_TL(number_format($this->data['monthly']['total debited']['month'] - $this->data['monthly']['debit returns']['month'], 2),
						$table_left + ($cell_width * 2), $top + ($row_height * $current_row), 
						$cell_width, $row_height,
						"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");*/
					break;
				//FORBIDDEN ROWS!
				case 'moneygram deposit':
				//case 'credit card payments':
					continue 2;
				break;
				
			}
			$format_decimals = 0;
			switch ($label) {
				case 'new customers':
				case 'card reactivations':
				case 'reactivated customers':
				case 'refunded customers':
				case 'resend customers':
				case 'cancelled customers':
				case 'paid out customers (ach)':
				case 'paid out customers (non-ach)':
					$format_decimals = 0;
					break;
				default:
					$format_decimals = 2;
					break;
			}
			
			$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
			$this->Rect_TL($left, $top + ($row_height * $current_row), $label_width, $row_height);
			$this->pdf->fill();
			
			$this->Rect_TL($table_left, $top + ($row_height * $current_row), $cell_width, $row_height);
//			$this->Rect_TL($table_left + ($cell_width * 1), $top + ($row_height * $current_row), $cell_width, $row_height);
//			$this->Rect_TL($table_left + ($cell_width * 2), $top + ($row_height * $current_row), $cell_width, $row_height);
			$this->pdf->stroke();
			
			$this->Text_TL(strtoupper($label),
				$left + $label_indent, $top + ($row_height * $current_row), 
				$label_width - $label_indent, $row_height,
				"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['RIGHT']}");
			
			if ($label != 'net cash collected') {
				$this->Text_TL(number_format($values['span'], $format_decimals),
					$table_left, $top + ($row_height * $current_row), 
					$cell_width, $row_height,
					"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
				
				/*$this->Text_TL(number_format($values['week'], $format_decimals),
					$table_left + ($cell_width * 1), $top + ($row_height * $current_row), 
					$cell_width, $row_height,
					"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
				
				$this->Text_TL(number_format($values['month'], $format_decimals),
					$table_left + ($cell_width * 2), $top + ($row_height * $current_row), 
					$cell_width, $row_height,
					"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");*/
			}
			
			$current_row++;
		}
		
		/**
		 * Advances in Collection
		 */
		$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
		$this->Rect_TL($left, $top + ($row_height * $current_row), $label_width, $row_height);
		$this->pdf->fill();
		
		$this->Rect_TL($table_left, $top + ($row_height * $current_row), $cell_width, $row_height);
		$this->pdf->stroke();
		
		$this->Text_TL("ADVANCES IN COLLECTION",
			$left, $top + ($row_height * $current_row), 
			$label_width, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['advances_collections'], 2),
			$table_left, $top + ($row_height * $current_row), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
		/**
		 * Advances in Active
		 */
		$this->pdf->setcolor('fill', 'gray', 0.75, 0, 0, 0);
		$this->Rect_TL($table_left + ($cell_width * 4), $top + ($row_height * $current_row), $cell_width * 2, $row_height);
		$this->pdf->fill();
		
		$this->Rect_TL($table_left + ($cell_width * 6), $top + ($row_height * $current_row), $cell_width, $row_height);
		$this->pdf->stroke();
		
		$this->Text_TL("ACTIVE ADVANCES OUT",
			$table_left + ($cell_width * 4), $top + ($row_height * $current_row), 
			$cell_width * 2, $row_height,
			"{$this->formats['BOLD_FONT']} {$this->formats['ITALICS']} {$this->formats['RIGHT']}");
		
		$this->Text_TL(number_format($this->data['advances_active'], 2),
			$table_left + ($cell_width * 6), $top + ($row_height * $current_row), 
			$cell_width, $row_height,
			"{$this->formats['NORMAL_FONT']} {$this->formats['RIGHT']}");
		
	}
	
	private function Rect_TL($x, $y, $w, $h) {
		$this->pdf->rect($x, (self::PAGE_HEIGHT - $y) - $h, $w, $h);
	}
	
	private function Line_TL($f_x, $f_y, $t_x, $t_y) {
		$this->pdf->moveto($f_x, self::PAGE_HEIGHT - $f_y);
		$this->pdf->lineto($t_x, self::PAGE_HEIGHT  - $t_y);
	}
	
	private function Text_TL($text, $x, $y, $w, $h, $formatting) {
		$tf = $this->pdf->create_textflow($text, $formatting);
		$this->pdf->fit_textflow($tf, $x, self::PAGE_HEIGHT - $y - $h, $x + $w, self::PAGE_HEIGHT - $y, "");
	}
}

?>
