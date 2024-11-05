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

        $select_link->addOption('Kurskategorien', 'categories');
        if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
            $select_link->addOption('Ortskategorien', 'locations');
        }
        if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
            $select_link->addOption('Terminkategorien', 'schedule_categories');
        }
        if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
            $select_link->addOption('Zielgruppen', 'target_groups');
        }

        $select_link->setSelected('REX_VALUE[1]');

        $select_link->show();
        ?>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[3]" value="true" <?= 'REX_VALUE[3]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" />
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
		<input type="checkbox" name="REX_INPUT_VALUE[4]" value="true" <?= 'REX_VALUE[4]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" />
	</div>
	<div class="col-xs-8">
		Veranstaltungen in einer Kategorie nicht als Liste, sondern als Kacheln darstellen<br />
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<?php
if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
?>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[2]" value="true" <?= 'REX_VALUE[2]' === 'true' ? ' checked="checked"' : '' /** @phpstan-ignore-line */ ?> class="form-control d2u_helper_toggle" onChange="toggleMapDetails()"/>
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
                $map_types = [];
                if (rex_addon::get('geolocation')->isAvailable()) {
                    $map_types['geolocation'] = 'Geolocation Addon: Standardkarte';
					$mapsets = [];
					if(rex_version::compare('2.0.0', rex_addon::get('geolocation')->getVersion(), '<=')) {
						// Geolocation 2.x
						$mapsets = \FriendsOfRedaxo\Geolocation\Mapset::query()
							->orderBy('title')
							->findValues('title', 'id');
					}
					else {
						// Geolocation 1.x
						$mapsets = \Geolocation\mapset::query() /** @phpstan-ignore-line */
							->orderBy('title')
							->findValues('title', 'id');
					}
                    foreach ($mapsets as $id => $name) {
                        $map_types[$id] = 'Geolocation Addon: '. $name;
                    }
                } elseif (rex_addon::get('osmproxy')->isAvailable()) {
                    $map_types['osm'] = 'OSM Proxy Addon OpenStreetMap Karte';
                }
                $map_types['google'] = 'Google Maps'. ('' !== rex_config::get('d2u_helper', 'maps_key', '') ? '' : ' (in den Einstellung des D2U Helper Addons muss hierfür noch ein Google Maps API Key eingegeben werden)');

				echo '<select name="REX_INPUT_VALUE[7]" class="form-control">';
				foreach ($map_types as $map_type_id => $map_type_name) {
					echo '<option value="'. $map_type_id .'"';

					if ('REX_VALUE[7]' === (string) $map_type_id) {
						echo ' selected="selected" ';
					}
					echo '>'. $map_type_name .'</option>';
				}
				echo '</select>';
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
if (rex_addon::get('d2u_news')->isAvailable()) {
    $categories = \D2U_News\Category::getAll(rex_clang::getCurrentId());
    if (count($categories) > 0) {
?>
		<div class="row">
			<div class="col-xs-12 col-sm-4">Auf der Übersichtsseite News aus dem D2U News Addon anzeigen?</div>
			<div class="col-xs-12 col-sm-8">
				<div class="rex-select-style">
				<?php
                    echo '<select name="REX_INPUT_VALUE[5]" class="form-control">';
                    echo '<option value="0">Keine News anzeigen</option>';
                    foreach ($categories as $category) {
                        echo '<option value="'. $category->category_id .'" ';
                        if ((int) 'REX_VALUE[5]' === $category->category_id) {
                            echo 'selected="selected" ';
                        }
                        echo '>'. $category->name .'</option>';
                    }
                    echo '</select>';
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
if (rex_addon::get('d2u_linkbox')->isAvailable()) {
    $categories = \TobiasKrais\D2ULinkbox\Category::getAll(rex_clang::getCurrentId(), true);
    if (count($categories) > 0) {
?>
		<div class="row">
			<div class="col-xs-12 col-sm-4">Auf der Übersichtsseite Linkboxen aus dem D2U Linkbox Addon anzeigen?</div>
			<div class="col-xs-12 col-sm-8">
				<div class="rex-select-style">
				<?php
                    echo '<select name="REX_INPUT_VALUE[6]" class="form-control">';
                    echo '<option value="0">Keine Linkboxen anzeigen</option>';
                    foreach ($categories as $category) {
                        echo '<option value="'. $category->category_id .'" ';
                        if ((int) 'REX_VALUE[6]' === $category->category_id) {
                            echo 'selected="selected" ';
                        }
                        echo '>'. $category->name .'</option>';
                    }
                    echo '</select>';
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
	<div class="col-xs-4">Anzahl Linkboxen / Zeile</div>
	<div class="col-xs-8">
		<?php
            echo '<select name="REX_INPUT_VALUE[8]" class="form-control">';
            echo '<option value="3" '. (3 === (int) 'REX_VALUE[8]' ? 'selected="selected" ' : '') .'>3</option>'; /** @phpstan-ignore-line */
            echo '<option value="4" '. (4 === (int) 'REX_VALUE[8]' ? 'selected="selected" ' : '') .'>4</option>'; /** @phpstan-ignore-line */
            echo '</select>';
        ?>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-12">
		<p>Kurse, Kategorien, Einstellung, usw. werden im <a href="index.php?page=d2u_courses">D2U Veranstaltungen Addon</a> verwaltet.</p>
	</div>
</div>