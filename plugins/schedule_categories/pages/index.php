<?php

use D2U_Courses\ScheduleCategory;

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

    $schedule_category_id = $form['schedule_category_id'];
    $schedule_category = new D2U_Courses\ScheduleCategory($schedule_category_id);
    $schedule_category->name = $form['name'];
    $schedule_category->picture = $input_media[1];
    $schedule_category->priority = $form['priority'];
    $schedule_category->parent_schedule_category = $form['parent_schedule_category_id'] > 0 ? new D2U_Courses\ScheduleCategory($form['parent_schedule_category_id']) : false;
    if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
        $kufer_categories = preg_grep('/^\s*$/s', explode(PHP_EOL, $form['kufer_categories']), PREG_GREP_INVERT);
        $schedule_category->kufer_categories = is_array($kufer_categories) ? array_map('trim', $kufer_categories) : [];
    }

    // message output
    $message = 'form_save_error';
    if ($schedule_category->save()) {
        $message = 'form_saved';
    }

    // Redirect to make reload and thus double save impossible
    if (1 === (int) filter_input(INPUT_POST, 'btn_apply', FILTER_VALIDATE_INT) && $schedule_category->schedule_category_id > 0) {
        header('Location: '. rex_url::currentBackendPage(['entry_id' => $schedule_category->schedule_category_id, 'func' => 'edit', 'message' => $message], false));
    } else {
        header('Location: '. rex_url::currentBackendPage(['message' => $message], false));
    }
    exit;
}
// Delete
if (1 === (int) filter_input(INPUT_POST, 'btn_delete', FILTER_VALIDATE_INT) || 'delete' === $func) {
    $schedule_category_id = $entry_id;
    if (0 === $schedule_category_id) {
        $form = rex_post('form', 'array', []);
        $schedule_category_id = $form['schedule_category_id'];
    }
    $schedule_category = new D2U_Courses\ScheduleCategory($schedule_category_id);

    // Check if object is used
    $uses_courses = $schedule_category->getCourses();
    $uses_categories = $schedule_category->getChildren();

    if (0 === count($uses_courses) && 0 === count($uses_categories)) {
        $schedule_category->delete();
        echo rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message);
    } else {
        $message = '<ul>';
        foreach ($uses_courses as $uses_course) {
            $message .= '<li>'. rex_i18n::msg('d2u_courses_course') .': <a href="index.php?page=d2u_courses/course&func=edit&entry_id='. $uses_course->course_id .'">'. $uses_course->name.'</a></li>';
        }
        foreach ($uses_categories as $uses_category) {
            $message .= '<li>'. rex_i18n::msg('d2u_helper_category') .': <a href="index.php?page=d2u_courses/schedule_categories&func=edit&entry_id='. $uses_category->schedule_category_id .'">'. $uses_category->name.'</a></li>';
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
			<header class="panel-heading"><div class="panel-title"><?= rex_i18n::msg('d2u_helper_category') ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[schedule_category_id]" value="<?= $entry_id ?>">
				<?php

                    $schedule_category = new D2U_Courses\ScheduleCategory($entry_id);
                    d2u_addon_backend_helper::form_input('d2u_helper_name', 'form[name]', $schedule_category->name, true, false);
                    d2u_addon_backend_helper::form_mediafield('d2u_helper_picture', '1', $schedule_category->picture, false);
                    d2u_addon_backend_helper::form_input('header_priority', 'form[priority]', $schedule_category->priority, true, false, 'number');
                    $options_parents = [-1 => rex_i18n::msg('d2u_courses_categories_parent_category_none')];
                    foreach (D2U_Courses\ScheduleCategory::getAllParents() as $parent) {
                        if ($parent instanceof ScheduleCategory) {
                            $options_parents[$parent->schedule_category_id] = $parent->name;
                        }
                    }
                    d2u_addon_backend_helper::form_select('d2u_courses_categories_parent_category', 'form[parent_schedule_category_id]', $options_parents, $schedule_category->parent_schedule_category instanceof ScheduleCategory ? [$schedule_category->parent_schedule_category->schedule_category_id] : [-1], 1, false, false);
                    if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
                        d2u_addon_backend_helper::form_textarea('d2u_courses_kufer_categories', 'form[kufer_categories]', implode(PHP_EOL, $schedule_category->kufer_categories), 5, false, false, false);
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
    $query = 'SELECT category.schedule_category_id, CONCAT_WS(" → ", parents.name, category.name) AS full_name, category.priority '
        . 'FROM '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS category '
        . 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS parents '
            . 'ON category.parent_schedule_category_id = parents.schedule_category_id ';
    if ('priority' === rex_config::get('d2u_courses', 'default_category_sort', 'name')) {
        $query .= 'ORDER BY priority ASC';
    } else {
        $query .= 'ORDER BY full_name ASC';
    }
    $list = rex_list::factory($query, 1000);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon fa-calendar"></i>';
    $thIcon = '';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-module"></i></a>';

    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'entry_id' => '###schedule_category_id###']);

    $list->setColumnLabel('schedule_category_id', rex_i18n::msg('id'));
    $list->setColumnLayout('schedule_category_id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);

    $list->setColumnLabel('full_name', rex_i18n::msg('d2u_helper_name'));
    $list->setColumnParams('full_name', ['func' => 'edit', 'entry_id' => '###schedule_category_id###']);

    $list->setColumnLabel('priority', rex_i18n::msg('header_priority'));

    $list->addColumn(rex_i18n::msg('module_functions'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'));
    $list->setColumnLayout(rex_i18n::msg('module_functions'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('module_functions'), ['func' => 'edit', 'entry_id' => '###schedule_category_id###']);

    $list->addColumn(rex_i18n::msg('delete_module'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete'));
    $list->setColumnLayout(rex_i18n::msg('delete_module'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('delete_module'), ['func' => 'delete', 'entry_id' => '###schedule_category_id###']);
    $list->addLinkAttribute(rex_i18n::msg('delete_module'), 'data-confirm', rex_i18n::msg('d2u_helper_confirm_delete'));

    $list->setNoRowsMessage(rex_i18n::msg('d2u_helper_no_categories_found'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('d2u_helper_category'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}
