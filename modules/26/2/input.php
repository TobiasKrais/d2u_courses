<div class="row">
	<div class="col-xs-12 col-sm-4">Teilnehmeralter erfragen?</div>
	<div class="col-xs-12 col-sm-8">
		<div class="rex-select-style">
		<?php
			print '<select name="REX_INPUT_VALUE[1]" class="form-control">';
			$options = [
				0 => "Weder nach Alter noch Geburtsdatum fragen",
				1 => "Nach Geburtsdatum fragen",
				2 => "Nach Alter fragen"
			];
			foreach ($options as $key => $value) {
				echo '<option value="'. $key.'" '. ("REX_VALUE[1]" == $key ? 'selected="selected" ' : '') .'>'. $value .'</option>';
			}
			print '</select>';
		?>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<?php
	if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
?>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[2]" value="true" <?php echo "REX_VALUE[2]" == 'true' ? ' checked="checked"' : ''; ?> style="float: right;" />
	</div>
	<div class="col-xs-8">
		Im Anmeldeprozess statistischen Angaben abfragen<br />
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<?php
	}
?>
<div class="row">
	<div class="col-xs-12">
		<p>Einstellung für den Warenkorb werden im <a href="index.php?page=d2u_courses/settings">D2U Kurse Addon > Einstellungen</a> verwaltet.</p>
	</div>
</div>
