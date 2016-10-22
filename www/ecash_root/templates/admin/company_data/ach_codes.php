<div id="module_body">
	<table width="100%" class="rules">
	<tr><th colspan="3" style="background: #B4DCAF">ACH Return Codes</th></tr>
	<tr>
		<td valign='top'>
	 		<div style="height:500px; width:100%; overflow:auto;">
			<table width="100%" border="1">
	 <tr><th>Return Code</th><th>Description</th><th>Is Fatal?</th></tr>
<? foreach($this->code_list as $ach_return_code): ?>
	 <tr><td><?= $ach_return_code->name_short ?></td>
		 <td style="text-align: left"><?= $ach_return_code->name ?></td>
		 <td><?= ucwords($ach_return_code->is_fatal) ?></td>
	 </tr>
<? endforeach; ?>
	 </td></tr>
			</table>
	 		</div>
		</td>
	</tr>
	</table>
</div>