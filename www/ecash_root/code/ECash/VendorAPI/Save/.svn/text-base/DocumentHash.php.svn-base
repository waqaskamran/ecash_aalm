<?php

/**
 * Save handler to allow updating existing rows to expired
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class ECash_VendorAPI_Save_DocumentHash implements VendorAPI_Save_ITableHandler
{
	/**
	 * @var ECash_Factory
	 */
	protected $factory;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var DB_IConnection_1
	 */
	protected $db;

	/**
	 * @var ECash_VendorAPI_Driver
	 */
	protected $driver;

	/**
	 * @param ECash_Factory $factory
	 * @param ECash_VendorAPI_Driver $driver
	 * @param DB_IConnection_1 $db
	 */
	public function __construct(ECash_Factory $factory, ECash_VendorAPI_Driver $driver, DB_IConnection_1 $db)
	{
		$this->factory = $factory;
		$this->driver = $driver;
		$this->db = $db;
	}

	/**
	 * Saves the data to the batch
	 *
	 * @param array $data
	 * @param DB_Models_Batch_1 $batch
	 */
	public function saveTo(array $data, DB_Models_Batch_1 $batch)
	{
		$model = $this->factory->getModel('DocumentHash', $this->db);
		
		$filter = array(
			'document_list_id' => $data['document_list_id'],
			'application_id' => $data['application_id'],
			'company_id' => $data['company_id'],
		);
		
		$model->loadBy($filter);
		foreach ($data as $field=>$value)
		{
			$model->{$field} = $value;
		}

		$batch->save($model);
	}
}

?>
