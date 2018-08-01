<?php
$func = rex_request('func', 'string');
$entry_id = rex_request('entry_id', 'int');
$message = rex_get('message', 'string');

// Print comments
if($message != "") {
	print rex_view::success(rex_i18n::msg($message));
}

// save settings
if (filter_input(INPUT_POST, "btn_save") == 1 || filter_input(INPUT_POST, "btn_apply") == 1) {
	$form = (array) rex_post('form', 'array', []);

	// Media fields and links need special treatment
	$input_media = (array) rex_post('REX_INPUT_MEDIA', 'array', []);

	$location_id = $form['location_id'];
	$location = new D2U_Courses\Location($location_id);
	$location->name = $form['name'];
	$location->latitude = $form['latitude'];
	$location->longitude = $form['longitude'];
	$location->city = $form['city'];
	$location->zip_code = $form['zip_code'];
	$location->street = $form['street'];
	$location->location_category = $form['location_category_id'] > 0 ? new D2U_Courses\LocationCategory($form['location_category_id']) : FALSE;
	$location->redaxo_users = isset($form['redaxo_users']) ? $form['redaxo_users'] : [];
	$location->picture = $input_media[1];
	$location->site_plan = $input_media[2];
	
	// message output
	$message = 'form_save_error';
	if($location->save()) {
		$message = 'form_saved';
	}

	// Redirect to make reload and thus double save impossible
	if(filter_input(INPUT_POST, "btn_apply") == 1 && $location !== FALSE) {
		header("Location: ". rex_url::currentBackendPage(["entry_id"=>$location->location_id, "func"=>'edit', "message"=>$message], FALSE));
	}
	else {
		header("Location: ". rex_url::currentBackendPage(["message"=>$message], FALSE));
	}
	exit;
}
// Delete
else if(filter_input(INPUT_POST, "btn_delete") == 1 || $func == 'delete') {
	$location_id = $entry_id;
	if($location_id == 0) {
		$form = (array) rex_post('form', 'array', []);
		$location_id = $form['location_id'];
	}
	$location = new D2U_Courses\Location($location_id);

	// Check if object is used
	$uses_courses = $location->getCourses(FALSE);
	$used_in_settings = FALSE;
	if(rex_config::get('d2u_courses', 'kufer_sync_default_location_id', 0) == $location->location_id) {
		$used_in_settings = TRUE;
	}

	if(count($uses_courses) == 0 && $used_in_settings == FALSE) {
		$location->delete();
		print rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message);
	}
	else {
		$message = '<ul>';
		foreach($uses_courses as $uses_location) {
			$message .= '<li><a href="index.php?page=d2u_courses/location/location&func=edit&entry_id='. $uses_location->location_id .'">'. $uses_location->name .'</a></li>';
		}
		if($used_in_settings) {
			$message .= '<li><a href="index.php?page=d2u_courses/settings">'. rex_i18n::msg('d2u_helper_settings') .'</a></li>';
		}
		$message .= '</ul>';

		print rex_view::error(rex_i18n::msg('d2u_helper_could_not_delete') . $message);
	}
	
	$func = '';
}

// Form
if ($func == 'edit' || $func == 'add') {
	$readonly = FALSE;
?>
	<form action="<?php print rex_url::currentBackendPage(); ?>" method="post">
		<div class="panel panel-edit">
			<header class="panel-heading"><div class="panel-title"><?php print rex_i18n::msg('d2u_courses_location'); ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[location_id]" value="<?php echo $entry_id; ?>">
				<?php

					$location = new D2U_Courses\Location($entry_id);
					d2u_addon_backend_helper::form_input('d2u_helper_name', "form[name]", $location->name, TRUE, $readonly);
					d2u_addon_backend_helper::form_input('d2u_courses_location_street', 'form[street]', $location->street, TRUE, $readonly, 'text');
					d2u_addon_backend_helper::form_input('d2u_courses_location', 'form[city]', $location->city, TRUE, $readonly, 'text');
					d2u_addon_backend_helper::form_input('d2u_courses_location_zip_code', 'form[zip_code]', $location->zip_code, TRUE, $readonly, 'text');
					d2u_addon_backend_helper::form_input('d2u_courses_location_latitude', 'form[latitude]', $location->latitude, FALSE, $readonly, 'text');
					d2u_addon_backend_helper::form_input('d2u_courses_location_longitude', 'form[longitude]', $location->longitude, FALSE, $readonly, 'text');
					d2u_addon_backend_helper::form_mediafield('d2u_helper_picture', '1', $location->picture, $readonly);
					d2u_addon_backend_helper::form_mediafield('d2u_courses_location_site_plan', '2', $location->site_plan, $readonly);
					$options_categories = [];
					foreach(D2U_Courses\LocationCategory::getAll(FALSE) as $location_category) {
						$options_categories[$location_category->location_category_id] = $location_category->name;
					}
					d2u_addon_backend_helper::form_select('d2u_helper_category', 'form[location_category_id]', $options_categories, ($location->location_category === FALSE ? [-1] : [$location->location_category->location_category_id]), 1, FALSE, $readonly);
					$options_users = [];
					$user_result = \rex_sql::factory();
					$user_result->setQuery('SELECT login, name FROM '. rex::getTablePrefix() .'user');
					for($i = 0; $i < $user_result->getRows(); $i++) {
						$options_users[$user_result->getValue('login')] = $user_result->getValue('name');
						$user_result->next();
					}
					d2u_addon_backend_helper::form_select('d2u_courses_location_rexuser', 'form[redaxo_users][]', $options_users, $location->redaxo_users, 5, TRUE, $readonly);
				?>
			</div>
			<footer class="panel-footer">
				<div class="rex-form-panel-footer">
					<div class="btn-toolbar">
						<?php
							if(!$readonly) {
						?>
						<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="1"><?php echo rex_i18n::msg('form_save'); ?></button>
						<button class="btn btn-apply" type="submit" name="btn_apply" value="1"><?php echo rex_i18n::msg('form_apply'); ?></button>
						<?php
							}
						?>
						<button class="btn btn-abort" type="submit" name="btn_abort" formnovalidate="formnovalidate" value="1"><?php echo rex_i18n::msg('form_abort'); ?></button>
						<?php
							if(!$readonly) {
								print '<button class="btn btn-delete" type="submit" name="btn_delete" formnovalidate="formnovalidate" data-confirm="'. rex_i18n::msg('form_delete') .'?" value="1">'. rex_i18n::msg('form_delete') .'</button>';
							}
						?>
					</div>
				</div>
			</footer>
		</div>
	</form>
	<br>
	<?php
		print d2u_addon_backend_helper::getCSS();
		print d2u_addon_backend_helper::getJS();
		print d2u_addon_backend_helper::getJSOpenAll();
}

if ($func == '') {
	$query = 'SELECT location_id, locations.name, categories.name AS category_name '
		.'FROM '. rex::getTablePrefix() .'d2u_courses_locations AS locations '
		.'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_location_categories as categories '
			.'ON locations.location_category_id = categories.location_category_id '
		.'ORDER BY name ASC';
    $list = rex_list::factory($query);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon fa-map-marker"></i>';
 	$thIcon = "";
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
    $fragment->setVar('title', rex_i18n::msg('d2u_courses_location_categories'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}