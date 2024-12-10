<?php

use D2U_Courses\Category;
use D2U_Courses\CustomerBooking;
use D2U_Courses\Location;
use D2U_Courses\LocationCategory;
use D2U_Courses\ScheduleCategory;

$func = rex_request('func', 'string');
$entry_id = rex_request('entry_id', 'int');
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
    $input_media_list = rex_post('REX_INPUT_MEDIALIST', 'array', []);
    $input_link = rex_post('REX_INPUT_LINK', 'array', []);

    $course_id = $form['course_id'];
    $course = new D2U_Courses\Course($course_id);
    $course->name = $form['name'];
    $course->teaser = $form['teaser'];
    $course->description = $form['description'];
    $course->details_course = $form['details_course'];
    $course->details_deadline = $form['details_deadline'];
    $course->details_age = $form['details_age'];
    $course->picture = $input_media[1];
    $course->price = (float) $form['price'];
    $course->price_discount = (float) $form['price_discount'];
    $course->price_notes = $form['price_notes'];
    $course->price_salery_level = array_key_exists('price_salery_level', $form) ? (string) $form['price_salery_level'] : '';
    $course->price_salery_level_details = [];
    foreach (explode(PHP_EOL, $form['price_salery_level_details']) as $price_salery_level_details_line) {
        $line = explode(':', $price_salery_level_details_line);
        if (2 === count($line)) {
            $course->price_salery_level_details[trim($line[0])] = trim($line[1]);
        }
    }
    $course->date_start = $form['date_start'];
    $course->date_end = $form['date_end'];
    $course->time = $form['time'];
    $course->category = $form['category_id'] > 0 ? new D2U_Courses\Category($form['category_id']) : false;
    $course->secondary_category_ids = $form['secondary_category_ids'] ?? [];
    $course->participants_max = (int) $form['participants_max'];
    $course->participants_min = (int) $form['participants_min'];
    $course->participants_number = (int) $form['participants_number'];
    $course->participants_wait_list = (int) $form['participants_wait_list'];
    $course->registration_possible = $form['registration_possible'];
    $course->online_status = array_key_exists('online_status', $form) ? 'online' : 'offline';
    $course->google_type = $form['google_type'];
    $course->url_external = $form['url_external'];
    $course->redaxo_article = (int) $input_link['1'];
    $course->instructor = $form['instructor'];
    $course->course_number = $form['course_number'];
    $downloads = preg_grep('/^\s*$/s', explode(',', $input_media_list['1']), PREG_GREP_INVERT);
    $course->downloads = is_array($downloads) ? $downloads : [];
    // Locations plugin
    if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
        $course->location = $form['location_id'] > 0 ? new D2U_Courses\Location($form['location_id']) : false;
        $course->room = $form['room'];
    }
    // Schedule categories plugin
    if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
        $course->schedule_category_ids = $form['schedule_category_ids'] ?? [];
    }
    // Target groupes plugin
    if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
        $course->target_group_ids = $form['target_group_ids'] ?? [];
    }

    // message output
    $message = 'form_save_error';
    if ($course->save()) {
        $message = 'form_saved';
    }

    // Redirect to make reload and thus double save impossible
    if (1 === (int) filter_input(INPUT_POST, 'btn_apply', FILTER_VALIDATE_INT) && $course->course_id > 0) {
        header('Location: '. rex_url::currentBackendPage(['entry_id' => $course->course_id, 'func' => 'edit', 'message' => $message], false));
    } else {
        header('Location: '. rex_url::currentBackendPage(['message' => $message], false));
    }
    exit;
}
// Delete
if (1 === (int) filter_input(INPUT_POST, 'btn_delete', FILTER_VALIDATE_INT) || 'delete' === $func) {
    $course_id = $entry_id;
    if (0 === $course_id) {
        $form = rex_post('form', 'array', []);
        $course_id = $form['course_id'];
    }
    $course = new D2U_Courses\Course($course_id);

    if ($course->delete()) {
        echo rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message);
    } else {
        echo rex_view::error(rex_i18n::msg('d2u_courses_course_could_not_delete') . $message);
    }

    $func = '';
}
// Change online status of machine
elseif ('changestatus' === $func) {
    $course = new D2U_Courses\Course($entry_id);
    $course->changeStatus();

    header('Location: '. rex_url::currentBackendPage());
    exit;
}
// Form
if ('edit' === $func || 'clone' === $func || 'add' === $func) {
    $course = new D2U_Courses\Course($entry_id);

    $readonly = false;
    if (rex::getUser() instanceof rex_user && !rex::getUser()->isAdmin() && !rex::getUser()->hasPerm('d2u_courses[courses_all]')
        && rex_plugin::get('d2u_courses', 'locations')->isAvailable() && $course->location instanceof Location && !in_array(rex::getUser()->getLogin(), $course->location->redaxo_users, true)) {
        $readonly = true;
    }
?>
	<form action="<?= rex_url::currentBackendPage() ?>" method="post">
		<div class="panel panel-edit">
			<header class="panel-heading"><div class="panel-title"><?= rex_i18n::msg('d2u_courses_course') ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[course_id]" value="<?= 'edit' === $func ? $entry_id : 0 ?>">
				<fieldset>
					<legend><?= rex_i18n::msg('d2u_courses_course_data') ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_helper_name', 'form[name]', $course->name, true, $readonly);
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_course_number', 'form[course_number]', $course->course_number, false, $readonly, 'text');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_instructor', 'form[instructor]', $course->instructor, false, $readonly, 'text');
                            \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_helper_online_status', 'form[online_status]', 'online', 'online' === $course->online_status, $readonly);
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_teaser', 'form[teaser]', $course->teaser, false, $readonly, 'text');
                            \TobiasKrais\D2UHelper\BackendHelper::form_textarea('d2u_courses_description', 'form[description]', $course->description, 5, false, $readonly, true);
                            \TobiasKrais\D2UHelper\BackendHelper::form_textarea('d2u_courses_details_course', 'form[details_course]', $course->details_course, 3, false, $readonly, true);
                            \TobiasKrais\D2UHelper\BackendHelper::form_textarea('d2u_courses_details_deadline', 'form[details_deadline]', $course->details_deadline, 3, false, $readonly, false);
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_details_age', 'form[details_age]', $course->details_age, false, $readonly, 'text');
                            \TobiasKrais\D2UHelper\BackendHelper::form_mediafield('d2u_helper_picture', '1', $course->picture, $readonly);
                            \TobiasKrais\D2UHelper\BackendHelper::form_medialistfield('d2u_courses_downloads', 1, $course->downloads, $readonly);
                            if (!rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() && 'KuferSQL' !== $course->import_type) {
                                \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_price_salery_level', 'form[price_salery_level]', 'true', $course->price_salery_level, $readonly);
                                $price_salery_level_details = '';
                                foreach ($course->price_salery_level_details as $key => $value) {
                                    $price_salery_level_details .= $key .': '. $value .PHP_EOL;
                                }
                                \TobiasKrais\D2UHelper\BackendHelper::form_textarea('d2u_courses_price_salery_level_details', 'form[price_salery_level_details]', $price_salery_level_details, 5, false, $readonly, false);
                                \TobiasKrais\D2UHelper\BackendHelper::form_infotext('d2u_courses_price_salery_level_details_description', 'd2u_courses_price_salery_level_details_description');
                            }
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_price', 'form[price]', (string) $course->price, false, $readonly, 'text');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_price_discount', 'form[price_discount]', (string) $course->price_discount, false, $readonly, 'text');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_price_notes', 'form[price_notes]', (string) $course->price_notes, false, $readonly, 'text');
                            $options_registration = [
                                'yes' => rex_i18n::msg('d2u_courses_yes'),
                                'yes_number' => rex_i18n::msg('d2u_courses_yes_number'),
                                'no' => rex_i18n::msg('d2u_courses_no'),
                                'booked' => rex_i18n::msg('d2u_courses_booked'),
                            ];
                            if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() && 'KuferSQL' === $course->import_type && '' !== $course->course_number) {
                                unset($options_registration['yes_number']);
                            }
                            \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_registration_possible', 'form[registration_possible]', $options_registration, [$course->registration_possible], 1, false, $readonly);
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_participants_max', 'form[participants_max]', $course->participants_max, false, $readonly, 'number');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_participants_min', 'form[participants_min]', $course->participants_min, false, $readonly, 'number');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_participants_number', 'form[participants_number]', $course->participants_number, false, $readonly, 'number');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_participants_wait_list', 'form[participants_wait_list]', $course->participants_wait_list, false, $readonly, 'number');
                            if (rex_plugin::get('d2u_courses', 'customer_bookings')->isAvailable() && 'KuferSQL' !== $course->import_type) {
                                ?>
                                <script>
                                    function participants_changer() {
                                        if ($("select[name='form[registration_possible]']").val() === "yes") {
                                            $("input[name='form[participants_number]']").parent().parent().fadeOut();
                                            $("input[name='form[participants_wait_list]']").parent().parent().fadeOut();
                                        }
                                        else {
                                            $("input[name='form[participants_number]']").parent().parent().fadeIn();
                                            $("input[name='form[participants_wait_list]']").parent().parent().fadeIn();
                                        }
                                    }
        
                                    // Hide on document load
                                    $(document).ready(function() {
                                        participants_changer();
                                    });
        
                                    // Hide on selection change
                                    $("select[name='form[registration_possible]']").on('change', function(e) {
                                        participants_changer();
                                    });
                                </script>
                                <?php
                            }
            
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_date_start', 'form[date_start]', $course->date_start, false, $readonly, 'date');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_date_end', 'form[date_end]', $course->date_end, false, $readonly, 'date');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_time', 'form[time]', $course->time, false, $readonly, 'text');
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_url_external', 'form[url_external]', $course->url_external, false, $readonly, 'text');
                            \TobiasKrais\D2UHelper\BackendHelper::form_linkfield('d2u_courses_redaxo_article', '1', $course->redaxo_article, (int) rex_config::get('d2u_helper', 'default_lang', rex_clang::getStartId()), $readonly);
                        ?>
						<script>
							function container_changer() {
								if ($("input[name='form[price_salery_level]']").is(':checked')) {
									$("textarea[name='form[price_salery_level_details]']").parent().parent().fadeIn();
									$("#d2u_courses_price_salery_level_details_description").fadeIn();
									$("input[name='form[price]']").parent().parent().fadeOut();
									$("input[name='form[price_discount]']").parent().parent().fadeOut();
								}
								else {
									$("textarea[name='form[price_salery_level_details]']").parent().parent().fadeOut();
									$("#d2u_courses_price_salery_level_details_description").fadeOut();
									$("input[name='form[price]']").parent().parent().fadeIn();
									$("input[name='form[price_discount]']").parent().parent().fadeIn();
								}
							}

							// Hide on document load
							$(document).ready(function() {
								container_changer();
							});

							// Hide on selection change
							$("input[name='form[price_salery_level]']").on('change', function(e) {
								container_changer();
							});
						</script>
					</div>
				</fieldset>
				<fieldset>
					<legend><?= rex_i18n::msg('d2u_helper_categories') ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
                            $options_categories = [];
                            foreach (D2U_Courses\Category::getAllNotParents() as $category) {
                                $options_categories[$category->category_id] = ($category->parent_category instanceof Category ? ($category->parent_category->parent_category instanceof Category ? ($category->parent_category->parent_category->parent_category instanceof Category ? $category->parent_category->parent_category->parent_category->name .' → ' : ''). $category->parent_category->parent_category->name .' → ' : ''). $category->parent_category->name .' → ' : ''). $category->name;
                            }
                            \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_category_primary', 'form[category_id]', $options_categories, $course->category instanceof Category ? [$course->category->category_id] : [], 1, false, $readonly);
                            \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_category_secondary', 'form[secondary_category_ids][]', $options_categories, $course->secondary_category_ids, 10, true, $readonly);
                            $options_google_type = [
                                '' => rex_i18n::msg('d2u_courses_google_type_none'),
                                'course' => rex_i18n::msg('d2u_courses_google_type_course'),
                                'event' => rex_i18n::msg('d2u_courses_google_type_event'),
                            ];
                            \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_google_type', 'form[google_type]', $options_google_type, [$course->google_type], 1, false, $readonly);
                            if (!rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
                                \TobiasKrais\D2UHelper\BackendHelper::form_infotext('d2u_courses_google_type_event_hint', 'google_type_event_hint');
                            }
                            // Schedule categories plugin
                            if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
                                $options_schedule_categories = [];
                                foreach (D2U_Courses\ScheduleCategory::getAllNotParents() as $schedule_category) {
                                    $options_schedule_categories[$schedule_category->schedule_category_id] = ($schedule_category->parent_schedule_category instanceof ScheduleCategory ? $schedule_category->parent_schedule_category->name .' → ' : ''). $schedule_category->name;
                                }
                                \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_schedule_categories', 'form[schedule_category_ids][]', $options_schedule_categories, $course->schedule_category_ids, 10, true, $readonly);
                            }
                            // Target groups plugin
                            if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
                                $options_target_groups = [];
                                foreach (D2U_Courses\TargetGroup::getAll() as $target_groups) {
                                    $options_target_groups[$target_groups->target_group_id] = $target_groups->name;
                                }
                                \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_target_groups', 'form[target_group_ids][]', $options_target_groups, $course->target_group_ids, 5, true, $readonly);
                            }
                            // Locations plugin
                            if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
                                $options_locations = [];
                                foreach (D2U_Courses\Location::getAll() as $location) {
                                    // Add location only if user has the right to edit courses for location
                                    if (rex::getUser() instanceof rex_user && (rex::getUser()->isAdmin() || rex::getUser()->hasPerm('d2u_courses[courses_all]') || in_array(rex::getUser()->getLogin(), $location->redaxo_users, true))) {
                                        $options_locations[$location->location_id] = ($location->location_category instanceof LocationCategory ? $location->location_category->name .' → ' : ''). $location->name;
                                    }
                                }
                                asort($options_locations);
                                \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_location', 'form[location_id]', $options_locations, $course->location instanceof Location ? [$course->location->location_id] : [], 1, false, $readonly);
                                \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_location_room', 'form[room]', $course->room, false, $readonly, 'text');
                            }
                        ?>
					</div>
				</fieldset>
			</div>
			<footer class="panel-footer">
				<div class="rex-form-panel-footer">
					<div class="btn-toolbar">
						<?php
                            if (!$readonly) {
                        ?>
						<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="1"><?= rex_i18n::msg('form_save') ?></button>
						<button class="btn btn-apply" type="submit" name="btn_apply" value="1"><?= rex_i18n::msg('form_apply') ?></button>
						<?php
                            }
                        ?>
						<button class="btn btn-abort" type="submit" name="btn_abort" formnovalidate="formnovalidate" value="1"><?= rex_i18n::msg('form_abort') ?></button>
						<?php
                            if (!$readonly) {
                                $has_bookings = false;
                                if (rex_plugin::get('d2u_courses', 'customer_bookings')->isAvailable()) {
                                    $bookings = CustomerBooking::getAllForCourse($course->course_id);
                                    if (count($bookings) > 0) {
                                        $has_bookings = true;
                                    }
                                }
                                echo '<button class="btn btn-delete" type="submit" name="btn_delete" formnovalidate="formnovalidate" data-confirm="'. rex_i18n::msg($has_bookings ? 'd2u_courses_customer_bookings_delete': 'd2u_helper_confirm_delete') .'" value="1">'
                                    . rex_i18n::msg('form_delete') . ($has_bookings ? ' (<i class="rex-icon fa-leanpub"></i>+<i class="rex-icon fa-user"></i>)' : '') .'</button>';
                            }
                        ?>
					</div>
				</div>
			</footer>
		</div>
	</form>
	<br>
	<?php
        echo \TobiasKrais\D2UHelper\BackendHelper::getCSS();
        echo \TobiasKrais\D2UHelper\BackendHelper::getJS();
        echo \TobiasKrais\D2UHelper\BackendHelper::getJSOpenAll();
}

if ('' === $func) {
    $query = 'SELECT courses.course_id, courses.name, course_number, online_status, CONCAT_WS(" → ", categories_great_grand_parents.name, categories_grand_parents.name, categories_parents.name, categories.name) AS cat_name '
        . 'FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses '
        . 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories '
            . 'ON courses.category_id = categories.category_id '
        . 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories_parents '
            . 'ON categories.parent_category_id = categories_parents.category_id '
        . 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories_grand_parents '
            . 'ON categories_parents.parent_category_id = categories_grand_parents.category_id '
        . 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories_great_grand_parents '
            . 'ON categories_grand_parents.parent_category_id = categories_great_grand_parents.category_id ';
    if (rex::getUser() instanceof rex_user && !rex::getUser()->isAdmin() && !rex::getUser()->hasPerm('d2u_courses[courses_all]') && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
        $query .= 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations '
                . 'ON courses.location_id = locations.location_id '
            .'WHERE redaxo_users LIKE "%'. rex::getUser()->getLogin() .'%" ';
    }

    $list = rex_list::factory(query:$query, rowsPerPage:1000, defaultSort:['name' => 'ASC']);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon fa-leanpub"></i>';
    $thIcon = '';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-module"></i></a>';

    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'entry_id' => '###course_id###']);

    $list->setColumnLabel('course_id', rex_i18n::msg('id'));
    $list->setColumnLayout('course_id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);
    $list->setColumnSortable('course_id');

    $list->setColumnLabel('name', rex_i18n::msg('d2u_helper_name'));
    $list->setColumnParams('name', ['func' => 'edit', 'entry_id' => '###course_id###']);
    $list->setColumnSortable('name');

    $list->setColumnLabel('course_number', rex_i18n::msg('d2u_courses_course_number'));
    $list->setColumnSortable('course_number');

    $list->setColumnLabel('cat_name', rex_i18n::msg('d2u_helper_categories'));
    $list->setColumnSortable('cat_name');

    $list->addColumn(rex_i18n::msg('module_functions'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'));
    $list->setColumnLayout(rex_i18n::msg('module_functions'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('module_functions'), ['func' => 'edit', 'entry_id' => '###course_id###']);

    $list->removeColumn('online_status');
    $list->addColumn(rex_i18n::msg('status_online'), '<a class="rex-###online_status###" href="' . rex_url::currentBackendPage(['func' => 'changestatus']) . '&entry_id=###course_id###"><i class="rex-icon rex-icon-###online_status###"></i> ###online_status###</a>');
    $list->setColumnLayout(rex_i18n::msg('status_online'), ['', '<td class="rex-table-action">###VALUE###</td>']);

    $list->addColumn(rex_i18n::msg('d2u_helper_clone'), '<i class="rex-icon fa-copy"></i> ' . rex_i18n::msg('d2u_helper_clone'));
    $list->setColumnLayout(rex_i18n::msg('d2u_helper_clone'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('d2u_helper_clone'), ['func' => 'clone', 'entry_id' => '###course_id###']);

    $list->addColumn(rex_i18n::msg('delete'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete'));
    $list->setColumnLayout(rex_i18n::msg('delete'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    if (rex_plugin::get('d2u_courses', 'customer_bookings')->isAvailable()) {
        $list->setColumnFormat(rex_i18n::msg('delete'), 'custom', static function ($params) {
            $list_params = $params['list'];
            $course_id = $list_params->getValue('course_id');
            $bookings = CustomerBooking::getAllForCourse($course_id);
            return '<a href="index.php?page='. rex_be_controller::getCurrentPage() .'&amp;func=delete&amp;entry_id='. $course_id .'" '
                .'data-confirm="'. rex_i18n::msg(count($bookings) > 0 ? 'd2u_courses_customer_bookings_delete': 'd2u_helper_confirm_delete')
                .'" class="rex-link-expanded">'
                .'<i class="rex-icon rex-icon-delete"></i> '
                . rex_i18n::msg('delete') 
                . (count($bookings) > 0 ? ' (<i class="rex-icon fa-leanpub"></i>+<i class="rex-icon fa-user"></i>)' : '')
                .'</a>';
        });
    }
    else {
        $list->setColumnParams(rex_i18n::msg('delete'), ['func' => 'delete', 'entry_id' => '###course_id###']);
        $list->addLinkAttribute(rex_i18n::msg('delete'), 'data-confirm', rex_i18n::msg('d2u_helper_confirm_delete'));
    }

    $list->setNoRowsMessage(rex_i18n::msg('d2u_courses_no_courses_found'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('d2u_courses_courses'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}