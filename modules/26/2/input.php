<div class="row">
	<div class="col-xs-12 col-sm-4">Teilnehmeralter erfragen?</div>
	<div class="col-xs-12 col-sm-8">
		<div class="rex-select-style">
		<?php
            echo '<select name="REX_INPUT_VALUE[1]" class="form-control" onChange="toggleDetailsView()">';
            $options = [
                0 => 'Weder nach Alter noch Geburtsdatum fragen',
                1 => 'Nach Geburtsdatum fragen',
                2 => 'Nach Alter fragen',
            ];
            foreach ($options as $key => $value) {
                echo '<option value="'. $key.'" '. ((int) 'REX_VALUE[1]' === $key ? 'selected="selected" ' : '') .'>'. $value .'</option>';
            }
            echo '</select>';
        ?>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row" id="all_categories">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[3]" value="true" <?= 'REX_VALUE[3]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" onChange="toggleDetailsView()"/>
	</div>
	<div class="col-xs-8">
		Altersabfrage der Teilnehmer auf ausgewählte Hauptkategorien begrenzen?<br />
	</div>
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row" id="categories">
	<div class="col-xs-12 col-sm-4">Kategorien mit Altersabfrage auswählen</div>
	<div class="col-xs-12 col-sm-8">
		<?php
            echo '<select name="REX_INPUT_VALUE[4][]" class="form-control selectpicker" multiple="multiple">';
            foreach (\TobiasKrais\D2UCourses\Category::getAllParents() as $root_category) {
                echo '<option value="'. $root_category->category_id .'" '. (in_array($root_category->category_id, array_map('intval', is_array(rex_var::toArray('REX_VALUE[4]')) ? rex_var::toArray('REX_VALUE[4]') : []), true) ? 'selected="selected" ' : '') .'>'. $root_category->name .'</option>';
            }
            echo '</select>';
        ?>
	</div>
	<div class="col-xs-12">&nbsp;</div>
</div>
<script>
	function toggleDetailsView() {
		if($("select[name='REX_INPUT_VALUE[1]']").val() > 0) {
			$("#all_categories").fadeIn();
			if($("input[name='REX_INPUT_VALUE[3]']").is(':checked')) {
				$("#categories").fadeIn();
			}
			else {
				$("#categories").hide();
			}
		}
		else {
			$("#all_categories").hide();
			$("#categories").hide();
		}
	}

	$(document).ready(function() {
		toggleDetailsView();
	});
</script>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[6]" value="true" <?= 'REX_VALUE[6]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" />
	</div>
	<div class="col-xs-8">
		Im Anmeldeprozess nach einer Notfallnummer der Teilnehmer fragen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[7]" value="true" <?= 'REX_VALUE[7]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" />
	</div>
	<div class="col-xs-8">
		Im Anmeldeprozess nach Rentenversicherungsnummer der Teilnehmer fragen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[8]" value="true" <?= 'REX_VALUE[8]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" />
	</div>
	<div class="col-xs-8">
		Im Anmeldeprozess nach Nationalität der Teilnehmer fragen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[9]" value="true" <?= 'REX_VALUE[9]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" />
	</div>
	<div class="col-xs-8">
		Im Anmeldeprozess nach Muttersprache der Teilnehmer fragen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[5]" value="true" <?= 'REX_VALUE[5]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" />
	</div>
	<div class="col-xs-8">
		Im Anmeldeprozess <b>nicht</b> nach dem Geschlecht der Teilnehmer fragen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<?php
    if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
?>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[2]" value="true" <?= 'REX_VALUE[2]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" />
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
		<p>Einstellung für den Warenkorb werden im <a href="index.php?page=d2u_courses/settings">D2U Veranstaltungen Addon > Einstellungen</a> verwaltet.</p>
	</div>
</div>