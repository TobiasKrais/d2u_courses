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
	$input_media_list = (array) rex_post('REX_INPUT_MEDIALIST', 'array', []);
	$input_link = (array) rex_post('REX_INPUT_LINK', 'array', []);

	$course_id = $form['course_id'];
	$course = new D2U_Courses\Course($course_id);
	$course->name = $form['name'];
	$course->teaser = $form['teaser'];
	$course->description = $form['description'];
	$course->details_course = $form['details_course'];
	$course->details_deadline = $form['details_deadline'];
	$course->details_age = $form['details_age'];
	$course->picture = $input_media[1];
	$course->price = $form['price'];
	$course->price_discount = $form['price_discount'];
	$course->date_start = $form['date_start'];
	$course->date_end = $form['date_end'];
	$course->time = $form['time'];
	$course->category = $form['category_id'] > 0 ? new D2U_Courses\Category($form['category_id']) : FALSE;
	$course->secondary_category_ids = isset($form['secondary_category_ids']) ? $form['secondary_category_ids'] : [];
	$course->participants_number = $form['participants_number'];
	$course->participants_max = $form['participants_max'];
	$course->participants_min = $form['participants_min'];
	$course->participants_wait_list = $form['participants_wait_list'];
	$course->registration_possible = $form['registration_possible'];
	$course->online_status = array_key_exists('online_status', $form) ? "online" : "offline";
	$course->url_external = $form['url_external'];
	$course->redaxo_article = $input_link['1'];
	$course->instructor = $form['instructor'];
	$course->course_number = $form['course_number'];
	$course->downloads = preg_grep('/^\s*$/s', explode(",", $input_media_list['1']), PREG_GREP_INVERT);
	// Locations plugin
	if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
		$course->location = $form['location_id'] > 0 ? new D2U_Courses\Location($form['location_id']) : FALSE;
		$course->room = $form['room'];
	}
	// Schedule categories plugin
	if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
		$course->schedule_category_ids = isset($form['schedule_category_ids']) ? $form['schedule_category_ids'] : [];
	}
	// Target groupes plugin
	if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
		$course->target_group_ids = isset($form['target_group_ids']) ? $form['target_group_ids'] : [];
	}
	
	// message output
	$message = 'form_save_error';
	if($course->save()) {
		\d2u_addon_backend_helper::update_searchit_url_index();
		$message = 'form_saved';
	}
	
	// Redirect to make reload and thus double save impossible
	if(filter_input(INPUT_POST, "btn_apply") == 1 && $course !== FALSE) {
		header("Location: ". rex_url::currentBackendPage(["entry_id"=>$course->course_id, "func"=>'edit', "message"=>$message], FALSE));
	}
	else {
		header("Location: ". rex_url::currentBackendPage(["message"=>$message], FALSE));
	}
	exit;
}
// Delete
else if(filter_input(INPUT_POST, "btn_delete") == 1 || $func == 'delete') {
	$course_id = $entry_id;
	if($course_id == 0) {
		$form = (array) rex_post('form', 'array', []);
		$course_id = $form['course_id'];
	}
	$course = new D2U_Courses\Course($course_id);

	if($course->delete()) {
		\d2u_addon_backend_helper::update_searchit_url_index();
		print rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message);
	}
	else {
		print rex_view::error(rex_i18n::msg('d2u_courses_course_could_not_delete') . $message);
	}
	
	$func = '';
}
// Change online status of machine
else if($func == 'changestatus') {
	$course = new D2U_Courses\Course($entry_id);
	$course->changeStatus();

	\d2u_addon_backend_helper::update_searchit_url_index();
	
	header("Location: ". rex_url::currentBackendPage());
	exit;
}
// Form
if ($func == 'edit' || $func == 'clone' || $func == 'add') {
	$course = new D2U_Courses\Course($entry_id);

	$readonly = FALSE;
	if(!rex::getUser()->isAdmin() && !rex::getUser()->hasPerm('d2u_courses[courses_all]')
		&& rex_plugin::get('d2u_courses', 'locations')->isAvailable() && $course->location !== FALSE && !in_array(rex::getUser()->getLogin(), $course->location->redaxo_users)) {
		$readonly = TRUE;
	}
?>
	<form action="<?php print rex_url::currentBackendPage(); ?>" method="post">
		<div class="panel panel-edit">
			<header class="panel-heading"><div class="panel-title"><?php print rex_i18n::msg('d2u_courses_course'); ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[course_id]" value="<?php echo ($func == 'edit' ? $entry_id : 0); ?>">
				<fieldset>
					<legend><?php echo rex_i18n::msg('d2u_courses_course_data'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_input('d2u_helper_name', "form[name]", $course->name, TRUE, $readonly);
							d2u_addon_backend_helper::form_input('d2u_courses_course_number', 'form[course_number]', $course->course_number, FALSE, $readonly, 'text');
							d2u_addon_backend_helper::form_input('d2u_courses_instructor', 'form[instructor]', $course->instructor, FALSE, $readonly, 'text');
							d2u_addon_backend_helper::form_checkbox('d2u_helper_online_status', 'form[online_status]', 'online', $course->online_status == "online", $readonly);
							d2u_addon_backend_helper::form_input('d2u_courses_teaser', "form[teaser]", $course->teaser, FALSE, $readonly, FALSE);
							d2u_addon_backend_helper::form_textarea('d2u_courses_description', "form[description]", $course->description, 5, FALSE, $readonly, TRUE);
							d2u_addon_backend_helper::form_textarea('d2u_courses_details_course', "form[details_course]", $course->details_course, 3, FALSE, $readonly, FALSE);
							d2u_addon_backend_helper::form_textarea('d2u_courses_details_deadline', "form[details_deadline]", $course->details_deadline, 3, FALSE, $readonly, FALSE);
							d2u_addon_backend_helper::form_input('d2u_courses_details_age', 'form[details_age]', $course->details_age, FALSE, $readonly, 'text');
							d2u_addon_backend_helper::form_mediafield('d2u_helper_picture', '1', $course->picture, $readonly);
							d2u_addon_backend_helper::form_medialistfield('d2u_courses_downloads', '1', $course->downloads, $readonly);
							d2u_addon_backend_helper::form_input('d2u_courses_price', 'form[price]', $course->price, FALSE, $readonly, 'text');
							d2u_addon_backend_helper::form_input('d2u_courses_price_discount', 'form[price_discount]', $course->price_discount, FALSE, $readonly, 'text');
							d2u_addon_backend_helper::form_input('d2u_courses_date_start', 'form[date_start]', $course->date_start, FALSE, $readonly, 'date');
							d2u_addon_backend_helper::form_input('d2u_courses_date_end', 'form[date_end]', $course->date_end, FALSE, $readonly, 'date');
							d2u_addon_backend_helper::form_input('d2u_courses_time', 'form[time]', $course->time, FALSE, $readonly, 'text');
							d2u_addon_backend_helper::form_input('d2u_courses_participants_number', 'form[participants_number]', $course->participants_number, FALSE, $readonly, 'number');
							d2u_addon_backend_helper::form_input('d2u_courses_participants_max', 'form[participants_max]', $course->participants_max, FALSE, $readonly, 'number');
							d2u_addon_backend_helper::form_input('d2u_courses_participants_min', 'form[participants_min]', $course->participants_min, FALSE, $readonly, 'number');
							d2u_addon_backend_helper::form_input('d2u_courses_participants_wait_list', 'form[participants_wait_list]', $course->participants_wait_list, FALSE, $readonly, 'number');
							$options_registration = [
								"yes" => rex_i18n::msg('d2u_courses_yes'),
								"yes_number" => rex_i18n::msg('d2u_courses_yes_number'),
								"no" => rex_i18n::msg('d2u_courses_no'),
								"booked" => rex_i18n::msg('d2u_courses_booked'),
							];
							if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() && $course->import_type == "KuferSQL" && $course->course_number != "") {
								unset($options_registration["yes_number"]);
							}
							d2u_addon_backend_helper::form_select('d2u_courses_registration_possible', 'form[registration_possible]', $options_registration, [$course->registration_possible], 1, FALSE, $readonly);
							d2u_addon_backend_helper::form_input('d2u_courses_url_external', 'form[url_external]', $course->url_external, FALSE, $readonly, 'text');
							d2u_addon_backend_helper::form_linkfield('d2u_courses_redaxo_article', '1', $course->redaxo_article, rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()), $readonly);
						?>
					</div>
				</fieldset>
				<fieldset>
					<legend><?php echo rex_i18n::msg('d2u_helper_categories'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							$options_categories = [];
							foreach(D2U_Courses\Category::getAllNotParents() as $category) {
								$options_categories[$category->category_id] = ($category->parent_category ? ($category->parent_category->parent_category ? $category->parent_category->parent_category->name ." → " : "" ). $category->parent_category->name ." → " : "" ). $category->name;
							}
							d2u_addon_backend_helper::form_select('d2u_courses_category_primary', 'form[category_id]', $options_categories, ($course->category ? [$course->category->category_id] : []), 1, FALSE, $readonly);
							d2u_addon_backend_helper::form_select('d2u_courses_category_secondary', 'form[secondary_category_ids][]', $options_categories, $course->secondary_category_ids, 10, TRUE, $readonly);
							// Schedule categories plugin
							if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
								$options_schedule_categories = [];
								foreach(D2U_Courses\ScheduleCategory::getAllNotParents() as $schedule_category) {
									$options_schedule_categories[$schedule_category->schedule_category_id] = ($schedule_category->parent_schedule_category !== FALSE ? $schedule_category->parent_schedule_category->name ." → " : "" ). $schedule_category->name;
								}
								d2u_addon_backend_helper::form_select('d2u_courses_schedule_categories', 'form[schedule_category_ids][]', $options_schedule_categories, $course->schedule_category_ids, 10, TRUE, $readonly);
							}
							// Target groups plugin
							if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
								$options_target_groups = [];
								foreach(D2U_Courses\TargetGroup::getAll() as $target_groups) {
									$options_target_groups[$target_groups->target_group_id] = $target_groups->name;
								}
								d2u_addon_backend_helper::form_select('d2u_courses_target_groups', 'form[target_group_ids][]', $options_target_groups, $course->target_group_ids, 5, TRUE, $readonly);
							}
							// Locations plugin
							if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
								$options_locations = [];
								foreach(D2U_Courses\Location::getAll() as $location) {
									// Add location only if user has the right to edit courses for location
									if(rex::getUser()->isAdmin() || rex::getUser()->hasPerm('d2u_courses[courses_all]') || in_array(rex::getUser()->getLogin(), $location->redaxo_users)) {
										$options_locations[$location->location_id] = ($location->location_category !== FALSE ? $location->location_category->name ." → " : "" ). $location->name;
									}
								}
								asort($options_locations);
								d2u_addon_backend_helper::form_select('d2u_courses_location', 'form[location_id]', $options_locations, ($course->location === FALSE ? [] : [$course->location->location_id]), 1, FALSE, $readonly);
								d2u_addon_backend_helper::form_input('d2u_courses_location_room', 'form[room]', $course->room, FALSE, $readonly, 'text');
							}
						?>
					</div>
				</fieldset>
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
	$query = 'SELECT courses.course_id, courses.name, course_number, online_status, categories.name AS cat_name '
		. 'FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses '
		. 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories '
			. 'ON courses.category_id = categories.category_id ';
	if(!rex::getUser()->isAdmin() && !rex::getUser()->hasPerm('d2u_courses[courses_all]') && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
		$query .= 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations '
				. 'ON courses.location_id = locations.location_id '
			.'WHERE redaxo_users LIKE "%'. rex::getUser()->getLogin() .'%" ';
	}
	$query .= 'ORDER BY name ASC';

    $list = rex_list::factory($query, 1000);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon fa-leanpub"></i>';
 	$thIcon = "";
	$thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-module"></i></a>';

	$list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'entry_id' => '###course_id###']);

    $list->setColumnLabel('course_id', rex_i18n::msg('id'));
    $list->setColumnLayout('course_id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);

    $list->setColumnLabel('name', rex_i18n::msg('d2u_helper_name'));
    $list->setColumnParams('name', ['func' => 'edit', 'entry_id' => '###course_id###']);

    $list->setColumnLabel('course_number', rex_i18n::msg('d2u_courses_course_number'));

	$list->setColumnLabel('cat_name', rex_i18n::msg('d2u_helper_categories'));

	$list->addColumn(rex_i18n::msg('module_functions'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'));
    $list->setColumnLayout(rex_i18n::msg('module_functions'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('module_functions'), ['func' => 'edit', 'entry_id' => '###course_id###']);

	$list->removeColumn('online_status');
	$list->addColumn(rex_i18n::msg('status_online'), '<a class="rex-###online_status###" href="' . rex_url::currentBackendPage(['func' => 'changestatus']) . '&entry_id=###course_id###"><i class="rex-icon rex-icon-###online_status###"></i> ###online_status###</a>');
	$list->setColumnLayout(rex_i18n::msg('status_online'), ['', '<td class="rex-table-action">###VALUE###</td>']);

	$list->addColumn(rex_i18n::msg('d2u_helper_clone'), '<i class="rex-icon fa-copy"></i> ' . rex_i18n::msg('d2u_helper_clone'));
	$list->setColumnLayout(rex_i18n::msg('d2u_helper_clone'), ['', '<td class="rex-table-action">###VALUE###</td>']);
	$list->setColumnParams(rex_i18n::msg('d2u_helper_clone'), ['func' => 'clone', 'entry_id' => '###course_id###']);

	$list->addColumn(rex_i18n::msg('delete_module'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete'));
	$list->setColumnLayout(rex_i18n::msg('delete_module'), ['', '<td class="rex-table-action">###VALUE###</td>']);
	$list->setColumnParams(rex_i18n::msg('delete_module'), ['func' => 'delete', 'entry_id' => '###course_id###']);
	$list->addLinkAttribute(rex_i18n::msg('delete_module'), 'data-confirm', rex_i18n::msg('d2u_helper_confirm_delete'));

    $list->setNoRowsMessage(rex_i18n::msg('d2u_courses_no_courses_found'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('d2u_courses_courses'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}