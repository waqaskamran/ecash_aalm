<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_%%%camel_name%%% extends ECash_Models_WritableModel
	{
		public static function getBy(array $where_args, array $override_dbs = NULL)
		{
			$base = new self();
			$base->setOverrideDatabases($override_dbs);

			$query = "SELECT * FROM " . $base->getTableName() . " " . self::buildWhere($where_args) . " LIMIT 1";

			if (($row = $base->getDatabaseInstance(self::DB_INST_READ)->querySingleRow($query, $where_args)) !== FALSE)
			{
				$base->fromDbRow($row);
				return $base;
			}
			return NULL;
		}

		public function getColumns()
		{
			static $columns = array(
				%%%column_list%%%
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array(%%%primary_key%%%);
		}
		public function getAutoIncrement()
		{
			return '%%%auto_inc%%%';
		}
		public function getTableName()
		{
			return '%%%table_name%%%';
		}
	}
?>