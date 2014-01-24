<?php
defined( '_JEXEC' ) or die();

?>

<div class="col100">
	<fieldset class="adminform">
		<table class="admintable" width = "100%" >
			<tr>
				<td  class="key"><?php echo _JSHOP_IDEALBASIC_MERCHANT_ID;?></td>
				<td>
					<input type = "text" class = "inputbox" name = "pm_params[merchant_id]" size="45" value = "<?php echo $pluginConfig['merchant_id'];?>" />
					<?php echo " " . JHTML::tooltip(_JSHOP_IDEALBASIC_MERCHANT_ID_DESC);?>
				</td>
			</tr>

			<tr>
				<td  class="key"><?php echo _JSHOP_IDEALBASIC_SUB_ID;?></td>
				<td>
					<input type = "text" class = "inputbox" name = "pm_params[sub_id]" size="45" value = "<?php echo $pluginConfig['sub_id'];?>" />
					<?php echo " " .JHTML::tooltip( _JSHOP_IDEALBASIC_SUB_ID_DESC);?>
				</td>
			</tr>

			<tr>
				<td  class="key"><?php echo _JSHOP_IDEALBASIC_SECRET_KEY;?></td>
				<td>
					<input type = "text" class = "inputbox" name = "pm_params[secret_key]" size="45" value = "<?php echo $pluginConfig['secret_key'];?>" />
					<?php echo " " .JHTML::tooltip( _JSHOP_IDEALBASIC_SECRET_KEY_DESC);?>
				</td>
			</tr>

			<tr>
				<td  class="key"><?php echo _JSHOP_TESTMODE;?></td>
				<td>
				<?php
					echo JHTML::_('select.booleanlist', 'pm_params[test_mode]', 'class = "inputbox" size = "1"', $pluginConfig['test_mode']);
					echo " " . JHTML::tooltip(_JSHOP_IDEALBASIC_TEST_MODE_DESC);
				?>
				</td>
			</tr>

			<tr>
				<td class="key"><?php echo _JSHOP_TRANSACTION_END; ?></td>
				<td>
				<?php
					echo JHTML::_('select.genericlist',$order_status, 'pm_params[transaction_end_status]', 'class = "inputbox" size = "1"', 'status_id', 'name', $pluginConfig['transaction_end_status']);
					echo " " . JHTML::tooltip(_JSHOP_IDEALBASIC_TRANSACTION_END_DESC);
				?>
				</td>
			</tr>

			<tr>
				<td class="key"><?php echo _JSHOP_TRANSACTION_FAILED;?></td>
				<td>
				<?php
					echo JHTML::_('select.genericlist',$order_status, 'pm_params[transaction_failed_status]', 'class = "inputbox" size = "1"', 'status_id', 'name', $pluginConfig['transaction_failed_status']);
					echo " " . JHTML::tooltip(_JSHOP_IDEALBASIC_TRANSACTION_FAILED_DESC);
				?>
				</td>
			</tr>

		</table>
	</fieldset>
</div>

<div class="clr"></div>
