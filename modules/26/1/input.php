<div class="row">
	<div class="col-xs-4">
		Starten mit:
	</div>
	<div class="col-xs-8">
		<?php
		$select_link = new rex_select(); 
		$select_link->setName('REX_INPUT_VALUE[1]'); 
		$select_link->setSize(1);
		$select_link->setAttribute('class', 'form-control');
		$select_link->setAttribute('id', 'selector');

		$select_link->addOption("Kurskategorien", "categories"); 
		if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			$select_link->addOption("Ortskategorien", "locations");
		}
		if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
			$select_link->addOption("Terminkategorien", "schedule_categories");
		}
		if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
			$select_link->addOption("Zielgruppen", "target_groups");
		}

		$select_link->setSelected("REX_VALUE[1]");

		echo $select_link->show();
		?>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[3]" value="true" <?php echo "REX_VALUE[3]" == 'true' ? ' checked="checked"' : ''; ?> style="float: right;" />
	</div>
	<div class="col-xs-8">
		Suchfeld für Volltextsuche der Veranstaltungen einblenden<br />
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[4]" value="true" <?php echo "REX_VALUE[4]" == 'true' ? ' checked="checked"' : ''; ?> style="float: right;" />
	</div>
	<div class="col-xs-8">
		Veranstaltungen in einer Kategorie nicht als Liste, sondern als Kacheln darstellen<br />
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<?php
if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
?>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[2]" value="true" <?php echo "REX_VALUE[2]" == 'true' ? ' checked="checked"' : ''; ?> style="float: right;" onChange="toggleMapDetails()"/>
	</div>
	<div class="col-xs-8">
		Karte anzeigen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">Art der Karte:</div>
	<div class="col-xs-8">
		<div class="rex-select-style">
			<?php 
				$map_types = ["osm" => "OpenStreetMap". (rex_addon::get('osmproxy')->isAvailable() ? "" : " (osmproxy Addon muss noch installiert werden)"),
					"google" => "Google Maps". (rex_config::get('d2u_helper', 'maps_key', '') != '' ? "" : " (in den Einstellung des D2U Helper Addons muss hierfür noch ein Google Maps API Key eingegeben werden)")];

				if(count($map_types) > 0) {
					print ' <select name="REX_INPUT_VALUE[7]" class="form-control">';
					foreach ($map_types as $map_type_id => $map_type_name) {
						echo '<option value="'. $map_type_id .'" ';

						if ("REX_VALUE[7]" == $map_type_id) {
							echo 'selected="selected" ';
						}
						echo '>'. $map_type_name .'</option>';
					}
					print '</select>';
				}
			?>
		</div>
	</div>
</div>
<script>
	function toggleMapDetails() {
		if ($("input[name='REX_INPUT_VALUE[2]']").is(':checked')) {
			$("select[name='REX_INPUT_VALUE[7]']").parent().parent().parent().slideDown();
		}
		else {
			$("select[name='REX_INPUT_VALUE[7]']").parent().parent().parent().slideUp();
		}
	}

	// Hide on document load
	$(document).ready(function() {
		toggleMapDetails();
	});
</script>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<?php
}
if(rex_addon::get('d2u_news')->isAvailable()) {
	$categories = \D2U_News\Category::getAll(rex_clang::getCurrentId(), TRUE);
	if (count($categories) > 0) {
?>
		<div class="row">
			<div class="col-xs-12 col-sm-4">Auf der Übersichtsseite News aus dem D2U News Addon anzeigen?</div>
			<div class="col-xs-12 col-sm-8">
				<div class="rex-select-style">
				<?php
					print '<select name="REX_INPUT_VALUE[5]" class="form-control">';
					print '<option value="0">Keine News anzeigen</option>';
					foreach ($categories as $category) {
						echo '<option value="'. $category->category_id .'" ';
						if ("REX_VALUE[5]" == $category->category_id) {
							echo 'selected="selected" ';
						}
						echo '>'. $category->name .'</option>';
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
	}
}
if(rex_addon::get('d2u_linkbox')->isAvailable()) {
	$categories = \D2U_Linkbox\Category::getAll(rex_clang::getCurrentId(), TRUE);
	if (count($categories) > 0) {
?>
		<div class="row">
			<div class="col-xs-12 col-sm-4">Auf der Übersichtsseite Linkboxen aus dem D2U Linkbox Addon anzeigen?</div>
			<div class="col-xs-12 col-sm-8">
				<div class="rex-select-style">
				<?php
					print '<select name="REX_INPUT_VALUE[6]" class="form-control">';
					print '<option value="0">Keine Linkboxen anzeigen</option>';
					foreach ($categories as $category) {
						echo '<option value="'. $category->category_id .'" ';
						if ("REX_VALUE[6]" == $category->category_id) {
							echo 'selected="selected" ';
						}
						echo '>'. $category->name .'</option>';
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
	}
}
?>
<div class="row">
	<div class="col-xs-12">
		<p>Kurse, Kategorien, Einstellung, usw. werden im <a href="index.php?page=d2u_courses">D2U Kurse Addon</a> verwaltet.</p>
	</div>
</div>