<?php
class ECash_ACHBatch_ValidatorManager
{
	/**
	 * Messages to be displayed
	 *
	 * @var array
	 */
	protected $messages;


	/**
	 * Array of tests to be run 
	 *
	 * @var array
	 */
	protected $tests;

	/**
	 * Initilizes the tests to be run
	 */
	public function __construct()
	{
		$this->tests = array();
		$this->messages = array();
		$this->loadTests(ECASH_CODE_DIR . 'ECash/ACHBatch/ValidatorTest/', "ECash_ACHBatch_ValidatorTest_");
	}	

	/**
	 * Loads all test from tests directory
	 *
	 * @parm string $dir
	 * @parm string $prefix
	 */
	protected function loadTests($dir, $prefix)
	{
			$files = scandir($dir);
			$tests = array();
			foreach($files as $file) {
				if(substr($file,0,1) == '.' || substr($file,-4) != '.php' || !is_file($dir . $file)) continue;
				$file = substr($file,0,-4);
				if( !($key = array_search($file, $tests))) {
					require($dir . $file . '.php');
					$class_name = $prefix . $file;
					try{	
						$class = new $class_name();
						if($class instanceof ECash_ACHBatch_IValidator)
						{
							$tests[] = $class;
						}
						else
						{
							ECash::getlog('ach')->write("validator test $class_name is not instance of ECash_ACHBatch_Validator");
						}
					}
					catch(Execption $e)
					{
						ECash::getlog('ach')->write("validator test $class_name failed to load");
					}
				
				} else {
	
				}
			}
			$this->tests = $tests;	
	}
	/**
	* Rules Validation tests to transactions
	*
	* @param array $transactions
	*/
	public function Validate(array $transactions)
	{
		foreach($this->tests as $test)
		{
			if(!$test->Validate($transactions))
				$this->messages = array_merge($this->messages, $test->getMessageArray());		
		}
	
	}

	/**
	* returns the message array
	*
	* @return array $message
	*/	
	public function getMessageArray()
	{
		return $this->messages;
	}
}



?>
