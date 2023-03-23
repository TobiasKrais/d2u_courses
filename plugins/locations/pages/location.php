<?php

use D2U_Courses\LocationCategory;

$func = rex_request('func', 'string');
$entry_id = (int) rex_request('entry_id', 'int');
$message = rex_get('message', 'string');

// Print comments
if ('' !== $message) {
    echo rex_view::success(rex_i18n::msg($message));
}

// save settings
if (1 === (int) filter_input(INPUT_POST, 'btn_save') || 1 === (int) filter_input(INPUT_POST, 'btn_apply')) {
    $form = rex_post('form', 'array', []);

    // Media fields and links need special treatment
    $input_media = rex_post('REX_INPUT_MEDIA', 'array', []);

    $location_id = $form['location_id'];
    $location = new D2U_Courses\Location($location_id);
    $location->name = $form['name'];
    $location->latitude = $form['latitude'];
    $location->longitude = $form['longitude'];
    $location->city = $form['city'];
    $location->zip_code = $form['zip_code'];
    $location->country_code = $form['country_code'];
    $location->street = $form['street'];
    $location->location_category = $form['location_category_id'] > 0 ? new D2U_Courses\LocationCategory($form['location_category_id']) : false;
    $location->kufer_location_id = $form['kufer_location_id'];
    $location->redaxo_users = $form['redaxo_users'] ?? [];
    $location->picture = $input_media[1];
    $location->site_plan = $input_media[2];

    // message output
    $message = 'form_save_error';
    if ($location->save()) {
        $message = 'form_saved';
    }

    // Redirect to make reload and thus double save impossible
    if (1 === (int) filter_input(INPUT_POST, 'btn_apply', FILTER_VALIDATE_INT) && $location->location_id > 0) {
        header('Location: '. rex_url::currentBackendPage(['entry_id' => $location->location_id, 'func' => 'edit', 'message' => $message], false));
    } else {
        header('Location: '. rex_url::currentBackendPage(['message' => $message], false));
    }
    exit;
}
// Delete
if (1 === (int) filter_input(INPUT_POST, 'btn_delete', FILTER_VALIDATE_INT) || 'delete' === $func) {
    $location_id = $entry_id;
    if (0 === $location_id) {
        $form = rex_post('form', 'array', []);
        $location_id = $form['location_id'];
    }
    $location = new D2U_Courses\Location($location_id);

    // Check if object is used
    $uses_courses = $location->getCourses(false);
    $used_in_settings = false;
    if ((int) rex_config::get('d2u_courses', 'kufer_sync_default_location_id', 0) === $location->location_id) {
        $used_in_settings = true;
    }

    if (0 === count($uses_courses) && false === $used_in_settings) {
        $location->delete();

        echo rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message);
    } else {
        $message = '<ul>';
        foreach ($uses_courses as $uses_course) {
            $message .= '<li><a href="index.php?page=d2u_courses/location/location&func=edit&entry_id='. $uses_course->course_id .'">'. $uses_course->name .'</a></li>';
        }
        if ($used_in_settings) {
            $message .= '<li><a href="index.php?page=d2u_courses/settings">'. rex_i18n::msg('d2u_helper_settings') .'</a></li>';
        }
        $message .= '</ul>';

        echo rex_view::error(rex_i18n::msg('d2u_helper_could_not_delete') . $message);
    }

    $func = '';
}

// Form
if ('edit' === $func || 'add' === $func) {
?>
	<form action="<?= rex_url::currentBackendPage() ?>" method="post">
		<div class="panel panel-edit">
			<header class="panel-heading"><div class="panel-title"><?= rex_i18n::msg('d2u_courses_location') ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[location_id]" value="<?= $entry_id ?>">
				<?php

                    $location = new D2U_Courses\Location($entry_id);
                    d2u_addon_backend_helper::form_input('d2u_helper_name', 'form[name]', $location->name, true, false);
                    d2u_addon_backend_helper::form_input('d2u_courses_location_street', 'form[street]', $location->street, true, false, 'text');
                    d2u_addon_backend_helper::form_input('d2u_courses_location', 'form[city]', $location->city, true, false, 'text');
                    d2u_addon_backend_helper::form_input('d2u_courses_location_zip_code', 'form[zip_code]', $location->zip_code, true, false, 'text');
                    d2u_addon_backend_helper::form_input('d2u_courses_location_country_code', 'form[country_code]', $location->country_code, true, false, 'text');

                    $d2u_helper = rex_addon::get('d2u_helper');
                    $api_key = '';
                    if ('' !== $d2u_helper->getConfig('maps_key', '')) {
                        $api_key = '?key='. $d2u_helper->getConfig('maps_key');

                ?>
				<script src="https://maps.googleapis.com/maps/api/js<?= $api_key ?>"></script>
				<script>
					function geocode() {
						if($("input[name='form[street]']").val() === "" || $("input[name='form[city]']").val() === "") {
							alert("<?= rex_i18n::msg('d2u_helper_geocode_fields') ?>");
							return;
						}

						// Geocode
						var geocoder = new google.maps.Geocoder();
						geocoder.geocode({'address': $("input[name='form[street]']").val() + ", " + $("input[name='form[zip_code]']").val() + " " + $("input[name='form[city]']").val()}, function(results, status) {
							if (status === google.maps.GeocoderStatus.OK) {
								$("input[name='form[latitude]']").val(results[0].geometry.location.lat);
								$("input[name='form[longitude]']").val(results[0].geometry.location.lng);
								// Show check geolocation button and set link to button
								$("#check_geocode").attr('href', "https://maps.google.com/?q=" + $("input[name='form[latitude]']").val() + "," + $("input[name='form[longitude]']").val() + "&z=17");
								$("#check_geocode").parent().show();
							}
							else {
								alert("<?= rex_i18n::msg('d2u_helper_geocode_failure') ?>");
							}
						});
					}
				</script>
				<?php
                        echo '<dl class="rex-form-group form-group" id="geocode">';
                        echo '<dt><label></label></dt>';
                        echo '<dd><input type="submit" value="'. rex_i18n::msg('d2u_helper_geocode') .'" onclick="geocode(); return false;" class="btn btn-save">'
                            . ' <div class="btn btn-abort"><a href="https://maps.google.com/?q='. $location->latitude .','. $location->longitude .'&z=17" id="check_geocode" target="_blank">'. rex_i18n::msg('d2u_helper_geocode_check') .'</a></div>'
                            . '</dd>';
                        echo '</dl>';
                        if (0 === (int) $location->latitude && 0 === (int) $location->longitude) {
                            echo '<script>jQuery(document).ready(function($) { $("#check_geocode").parent().hide(); });</script>';
                        }
                    }
                    d2u_addon_backend_helper::form_infotext('d2u_helper_geocode_hint', 'hint_geocoding');
                    d2u_addon_backend_helper::form_input('d2u_courses_location_latitude', 'form[latitude]', (string) $location->latitude, false, false, 'text');
                    d2u_addon_backend_helper::form_input('d2u_courses_location_longitude', 'form[longitude]', (string) $location->longitude, false, false, 'text');
                    d2u_addon_backend_helper::form_mediafield('d2u_helper_picture', '1', $location->picture, false);
                    d2u_addon_backend_helper::form_mediafield('d2u_courses_location_site_plan', '2', $location->site_plan, false);
                    $options_categories = [];
                    foreach (D2U_Courses\LocationCategory::getAll(false) as $location_category) {
                        $options_categories[$location_category->location_category_id] = $location_category->name;
                    }
                    d2u_addon_backend_helper::form_select('d2u_helper_category', 'form[location_category_id]', $options_categories, $location->location_category instanceof LocationCategory ? [$location->location_category->location_category_id] : [-1], 1, false, false);
                    $options_users = [];
                    $user_result = \rex_sql::factory();
                    $user_result->setQuery('SELECT login, name FROM '. rex::getTablePrefix() .'user ORDER BY name');
                    for ($i = 0; $i < $user_result->getRows(); ++$i) {
                        $options_users[(string) $user_result->getValue('login')] = (string) $user_result->getValue('name');
                        $user_result->next();
                    }
                    d2u_addon_backend_helper::form_select('d2u_courses_location_rexuser', 'form[redaxo_users][]', $options_users, $location->redaxo_users, 5, true, false);
                    if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
                        d2u_addon_backend_helper::form_input('d2u_courses_kufer_sync_location_id', 'form[kufer_location_id]', $location->kufer_location_id, false, false, 'number');
                    }
                ?>
			</div>
			<footer class="panel-footer">
				<div class="rex-form-panel-footer">
					<div class="btn-toolbar">
						<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="1"><?= rex_i18n::msg('form_save') ?></button>
						<button class="btn btn-apply" type="submit" name="btn_apply" value="1"><?= rex_i18n::msg('form_apply') ?></button>
						<button class="btn btn-abort" type="submit" name="btn_abort" formnovalidate="formnovalidate" value="1"><?= rex_i18n::msg('form_abort') ?></button>
						<?php
                            echo '<button class="btn btn-delete" type="submit" name="btn_delete" formnovalidate="formnovalidate" data-confirm="'. rex_i18n::msg('form_delete') .'?" value="1">'. rex_i18n::msg('form_delete') .'</button>';
                        ?>
					</div>
				</div>
			</footer>
		</div>
	</form>
	<br>
	<?php
        echo d2u_addon_backend_helper::getCSS();
        echo d2u_addon_backend_helper::getJS();
        echo d2u_addon_backend_helper::getJSOpenAll();
}

if ('' === $func) {
    $query = 'SELECT location_id, locations.name, categories.name AS category_name '
        .'FROM '. rex::getTablePrefix() .'d2u_courses_locations AS locations '
        .'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_location_categories as categories '
            .'ON locations.location_category_id = categories.location_category_id '
        .'ORDER BY name ASC';
    $list = rex_list::factory($query, 1000);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon fa-map-marker"></i>';
    $thIcon = '';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-module"></i></a>';

    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'entry_id' => '###location_id###']);

    $list->setColumnLabel('location_id', rex_i18n::msg('id'));
    $list->setColumnLayout('location_id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);

    $list->setColumnLabel('name', rex_i18n::msg('d2u_helper_name'));
    $list->setColumnParams('name', ['func' => 'edit', 'entry_id' => '###location_id###']);

    $list->setColumnLabel('category_name', rex_i18n::msg('d2u_helper_category'));

    $list->addColumn(rex_i18n::msg('module_functions'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'));
    $list->setColumnLayout(rex_i18n::msg('module_functions'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('module_functions'), ['func' => 'edit', 'entry_id' => '###location_id###']);

    $list->addColumn(rex_i18n::msg('delete_module'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete'));
    $list->setColumnLayout(rex_i18n::msg('delete_module'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('delete_module'), ['func' => 'delete', 'entry_id' => '###location_id###']);
    $list->addLinkAttribute(rex_i18n::msg('delete_module'), 'data-confirm', rex_i18n::msg('d2u_helper_confirm_delete'));

    $list->setNoRowsMessage(rex_i18n::msg('d2u_courses_location_no_locations_found'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('d2u_courses_locations'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}
