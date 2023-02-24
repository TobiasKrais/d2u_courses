<?php
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

    $target_group_id = $form['target_group_id'];
    $target_group = new D2U_Courses\TargetGroup($target_group_id);
    $target_group->name = $form['name'];
    $target_group->picture = $input_media[1];
    $target_group->priority = $form['priority'];
    if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
        $target_group->kufer_target_group_name = $form['kufer_target_group_name'];
        $target_group->kufer_categories = array_map('trim', preg_grep('/^\s*$/s', explode(PHP_EOL, $form['kufer_categories']), PREG_GREP_INVERT));
    }

    // message output
    $message = 'form_save_error';
    if ($target_group->save()) {
        $message = 'form_saved';
    }

    // Redirect to make reload and thus double save impossible
    if (1 === (int) filter_input(INPUT_POST, 'btn_apply', FILTER_VALIDATE_INT) && false !== $target_group) {
        header('Location: '. rex_url::currentBackendPage(['entry_id' => $target_group->target_group_id, 'func' => 'edit', 'message' => $message], false));
    } else {
        header('Location: '. rex_url::currentBackendPage(['message' => $message], false));
    }
    exit;
}
// Delete
if (1 === (int) filter_input(INPUT_POST, 'btn_delete', FILTER_VALIDATE_INT) || 'delete' === $func) {
    $target_group_id = $entry_id;
    if (0 === $target_group_id) {
        $form = rex_post('form', 'array', []);
        $target_group_id = $form['target_group_id'];
    }
    $target_group = new D2U_Courses\TargetGroup($target_group_id);

    // Check if object is used
    $uses_courses = $target_group->getCourses();

    if (0 === count($uses_courses)) {
        $target_group->delete();
        echo rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message);
    } else {
        $message = '<ul>';
        foreach ($uses_courses as $uses_course) {
            $message .= '<li>'. rex_i18n::msg('d2u_courses_course') .': <a href="index.php?page=d2u_courses/course&func=edit&entry_id='. $uses_course->course_id .'">'. $uses_course->name.'</a></li>';
        }
        $message .= '</ul>';

        echo rex_view::error(rex_i18n::msg('d2u_helper_could_not_delete') . $message);
    }

    $func = '';
}

// Form
if ('edit' === $func || 'add' === $func) {
    $readonly = false;
?>
	<form action="<?= rex_url::currentBackendPage() ?>" method="post">
		<div class="panel panel-edit">
			<header class="panel-heading"><div class="panel-title"><?= rex_i18n::msg('d2u_helper_category') ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[target_group_id]" value="<?= $entry_id ?>">
				<?php

                    $target_group = new D2U_Courses\TargetGroup($entry_id);
                    d2u_addon_backend_helper::form_input('d2u_helper_name', 'form[name]', $target_group->name, true, $readonly);
                    d2u_addon_backend_helper::form_mediafield('d2u_helper_picture', '1', $target_group->picture, $readonly);
                    d2u_addon_backend_helper::form_input('header_priority', 'form[priority]', $target_group->priority, true, $readonly, 'number');
                    if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
                        d2u_addon_backend_helper::form_input('d2u_courses_kufer_categories_target_group_name', 'form[kufer_target_group_name]', $target_group->kufer_target_group_name, false, $readonly);
                        d2u_addon_backend_helper::form_textarea('d2u_courses_kufer_categories', 'form[kufer_categories]', implode(PHP_EOL, $target_group->kufer_categories), 5, false, $readonly, false);
                    }
                ?>
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
                                echo '<button class="btn btn-delete" type="submit" name="btn_delete" formnovalidate="formnovalidate" data-confirm="'. rex_i18n::msg('form_delete') .'?" value="1">'. rex_i18n::msg('form_delete') .'</button>';
                            }
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
    $query = 'SELECT target_group_id, name, priority '
        . 'FROM '. rex::getTablePrefix() .'d2u_courses_target_groups ';
    if ('priority' == rex_config::get('d2u_courses', 'default_category_sort', 'name')) {
        $query .= 'ORDER BY priority ASC';
    } else {
        $query .= 'ORDER BY name ASC';
    }
    $list = rex_list::factory($query, 1000);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon fa-bullseye"></i>';
    $thIcon = '';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-module"></i></a>';

    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'entry_id' => '###target_group_id###']);

    $list->setColumnLabel('target_group_id', rex_i18n::msg('id'));
    $list->setColumnLayout('target_group_id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);

    $list->setColumnLabel('name', rex_i18n::msg('d2u_helper_name'));
    $list->setColumnParams('name', ['func' => 'edit', 'entry_id' => '###target_group_id###']);

    $list->setColumnLabel('priority', rex_i18n::msg('header_priority'));

    $list->addColumn(rex_i18n::msg('module_functions'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'));
    $list->setColumnLayout(rex_i18n::msg('module_functions'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('module_functions'), ['func' => 'edit', 'entry_id' => '###target_group_id###']);

    $list->addColumn(rex_i18n::msg('delete_module'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete'));
    $list->setColumnLayout(rex_i18n::msg('delete_module'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('delete_module'), ['func' => 'delete', 'entry_id' => '###target_group_id###']);
    $list->addLinkAttribute(rex_i18n::msg('delete_module'), 'data-confirm', rex_i18n::msg('d2u_helper_confirm_delete'));

    $list->setNoRowsMessage(rex_i18n::msg('d2u_courses_target_groups_no_target_groups_found'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('d2u_helper_category'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}
