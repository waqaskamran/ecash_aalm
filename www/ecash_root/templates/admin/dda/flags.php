<div id="module_body">
	 <table width='100%' border='0' style='background: #EEEEEE;'>
	 <tr class='valign_top'><th style='background-color: #FBCD57;'>Direct Data Administration: Edit Flags</th></tr>
	 <tr class='valign_top'><td style='text-align: left;'>
	 	<form method="post">Find Application Id
	 		<input type='text' name='application_id' value='<?= empty($this->application_id) ? '' : $this->application_id ?>' />
			<input type='hidden' name='page' value='DDA_Flags' />
			<input type='hidden' name='module' value='admin' />
	 		<input type='submit' name='search' value='Search' />
	 	</form>
	 </td></tr>
	 <? if(isset($this->app_not_found)): ?>
	 <tr class='valign_top'><td style='text-align: left;'>
		  Application not found for ID "<?= $this->application_id ?>"
	 </td></tr>	 
	 <? elseif(isset($this->flag_list)):
	?>
	<form method="post">
	<input type='hidden' name='application_id' value='<?= $this->application_id ?>' />
	<input type='hidden' name='page' value='DDA_Flags' />
	<input type='hidden' name='module' value='admin' />
	<?
		$flags = $this->app_flags->getAll();
		foreach($this->flag_list as $flag):
	?>
	 <tr class='valign_top'><td style='text-align: left;'>
			<input type="checkbox" name="flags[]" value="<?= $flag->name_short ?>" <?= isset($flags[$flag->name_short]) ? 'checked="checked"' : ''?> /><?= $flag->name; ?>
	 </td></tr>	
	<? endforeach; ?>
	 <tr class='valign_top'><td style='text-align: center;'><input type="submit" name='save' value="Save" /></td></tr>
	 <?	endif; ?>
	 </table>
	<div style="clear: both;"></div>
</div>