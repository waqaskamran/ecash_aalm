<div id="module_body" style="padding: 5px; text-align: left; color: black; font-family: Verdana, Arial, Sans-seif; font-size: 9pt;">
<style type="text/css">

	h2
	{
		text-align: left;
		margin: 2px;
	}

	h3
	{
		text-align: left;
		margin: 2px;
	}
	
	div.tableDefinition
	{
		border-style: solid; 
		border-width: 1px;
		padding: 4px;
		padding-right: 10px;
		padding-left: 10px;
		padding-bottom: 10px;
		margin: 2px;
		margin-bottom: 6px;
		width: 750px;
		text-align: left;
		background: white;
	}

	table.columnTable td
	{ 
		border-style: solid; 
		border-width: 1px;
		padding: 2px;
		padding-right: 16px;
		font-size: 8pt;
		text-align: left;
	}
	
	th.columnHeader
	{
		background: black;
		color: white;
		text-align: left;
		font-size: 9pt;
	}
	
	td.columnName
	{
		width: 250px;
		background: #E1E4F2;
		text-align: left;
	}
	
	td.columDescription
	{
		width: 460px;
		background: #F0F0F0;
		text-align: left;
	}
	
</style>

<h1>ECash Data Dictionary</h1>

<?php

/**
 * #34853 - Displays the eCash Data Dictionary
 */
$db = ECash::getSlaveDB();
$last_table = NULL;
$table_list = array();
$last_update = NULL;

$query = "
	SELECT	table_name,
			column_name,
			description,
			(	SELECT	MAX(UNIX_TIMESTAMP(date_modified))
				FROM table_descriptions
			) as last_updated
	FROM table_descriptions
	WHERE description <> ''
	GROUP BY table_name, column_name
	ORDER BY table_name, column_name ";

$result = $db->query($query);

while ($row = $result->fetch(PDO::FETCH_OBJ))
{
	if(empty($last_update))
	{
		$last_update = $row->last_updated;
	}

	if($row->table_name != $last_table)
	{
		$table_list[$row->table_name] = new ECash_DataDictionary_Table($row->table_name);
		$last_table = $row->table_name;
	}
	
	if($row->column_name == '__table__')
	{
		$table_list[$row->table_name]->setTableDescription($row->description);	
	}
	else
	{
		$table_list[$row->table_name]->addColumn($row->column_name, $row->description);
	}
}
if(! empty($last_update))
{
	echo "<span>Last Updated: " . date('m/d/Y h:i:s a e', $last_update) ."</span>\n";
}
else
{
	echo "<span>No Data Available!</span>\n";
}

// Foreach the table list and print each description
foreach($table_list as $table)
{
	echo $table;
}

class ECash_DataDictionary_Table
{
	private $table_name;
	private $column_data;
	private $table_description;
	
	public function __construct($table_name)
	{
		$this->table_name = $table_name;
		$this->column_data = array();
	}

	public function setTableDescription($description)
	{
		$this->table_description = $description;
	}
	
	public function addColumn($column_name, $description)
	{
		$this->column_data[$column_name] = $description;
	}
	
	public function getTableName()
	{
		return $this->table_name;
	}
	
	public function __toString()
	{
		if(empty($this->column_data))
			return '';

		$string = "
	<div class=\"tableDefinition\">
		<h2 class=\"tableName\">{$this->table_name}</h1>
		<h3 class=\"tableDescription\">{$this->table_description}</h2>
		<table class=\"columnTable\">
		<tr>
			<th class=\"columnHeader\">Column Name</th>
			<th class=\"columnHeader\">Column Description</th>
		</tr>\n";
	
		foreach($this->column_data as $column => $description)
		{
			$string .= "		<tr>\n";
			$string .= "			<td class=\"columnName\">{$column}</td>\n";
			$string .= "			<td class=\"columDescription\">{$description}</td>\n";
			$string .= "		</tr>\n";
		}

		$string .= "		</table>
	</div>\n";
		
		return $string;
	}
}

?>

</div>