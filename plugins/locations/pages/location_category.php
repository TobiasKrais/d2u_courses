<?php
$func = rex_request('func', 'string');
$entry_id = rex_request('entry_id', 'int', 0);
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

    $location_category = new TobiasKrais\D2UCourses\LocationCategory($form['location_category_id']);
    $location_category->zoom_level = $form['zoom_level'];
    $location_category->picture = $input_media[1];
    $location_category->name = $form['name'];

    // message output
    $message = 'form_save_error';
    if ($location_category->save()) {
        $message = 'form_saved';
    }

    // Redirect to make reload and thus double save impossible
    if (1 === (int) filter_input(INPUT_POST, 'btn_apply', FILTER_VALIDATE_INT) && $location_category->location_category_id > 0) {
        header('Location: '. rex_url::currentBackendPage(['entry_id' => $location_category->location_category_id, 'func' => 'edit', 'message' => $message], false));
    } else {
        header('Location: '. rex_url::currentBackendPage(['message' => $message], false));
    }
    exit;
}
// Delete
if (1 === (int) filter_input(INPUT_POST, 'btn_delete', FILTER_VALIDATE_INT) || 'delete' === $func) {
    $location_category_id = $entry_id;
    if (0 === $location_category_id) {
        $form = rex_post('form', 'array', []);
        $location_category_id = $form['location_category_id'];
    }
    $location_category = new TobiasKrais\D2UCourses\LocationCategory($location_category_id);

    // Check if object is used
    $uses_locations = $location_category->getLocations(false);

    if (0 === count($uses_locations)) {
        $location_category->delete();

        echo rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message);
    } else {
        $message = '<ul>';
        foreach ($uses_locations as $uses_location) {
            $message .= '<li><a href="index.php?page=d2u_courses/location/location&func=edit&entry_id='. $uses_location->location_id .'">'. $uses_location->name.'</a></li>';
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
			<header class="panel-heading"><div class="panel-title"><?= rex_i18n::msg('d2u_courses_location_category') ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[location_category_id]" value="<?= $entry_id ?>">
				<?php

                    $location_category = new TobiasKrais\D2UCourses\LocationCategory($entry_id);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_helper_name', 'form[name]', $location_category->name, true, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_location_zoomlevel', 'form[zoom_level]', $location_category->zoom_level, true, false, 'number');
                    \TobiasKrais\D2UHelper\BackendHelper::form_mediafield('d2u_helper_picture', '1', $location_category->picture, false);
                ?>
			</div>
			<footer class="panel-footer">
				<div class="rex-form-panel-footer">
					<div class="btn-toolbar">
						<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="1"><?= rex_i18n::msg('form_save') ?></button>
						<button class="btn btn-apply" type="submit" name="btn_apply" value="1"><?= rex_i18n::msg('form_apply') ?></button>
						<button class="btn btn-abort" type="submit" name="btn_abort" formnovalidate="formnovalidate" value="1"><?= rex_i18n::msg('form_abort') ?></button>
						<?= '<button class="btn btn-delete" type="submit" name="btn_delete" formnovalidate="formnovalidate" data-confirm="'. rex_i18n::msg('form_delete') .'?" value="1">'. rex_i18n::msg('form_delete') .'</button>';
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
    $query = 'SELECT location_category_id, name '
        . 'FROM '. rex::getTablePrefix() .'d2u_courses_location_categories '
        .'ORDER BY name ASC';
    $list = rex_list::factory($query, 1000);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon rex-icon-open-category"></i>';
    $thIcon = '';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-module"></i></a>';

    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'entry_id' => '###location_category_id###']);

    $list->setColumnLabel('location_category_id', rex_i18n::msg('id'));
    $list->setColumnLayout('location_category_id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);

    $list->setColumnLabel('name', rex_i18n::msg('d2u_helper_name'));
    $list->setColumnParams('name', ['func' => 'edit', 'entry_id' => '###location_category_id###']);

    $list->addColumn(rex_i18n::msg('module_functions'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'));
    $list->setColumnLayout(rex_i18n::msg('module_functions'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('module_functions'), ['func' => 'edit', 'entry_id' => '###location_category_id###']);

    $list->addColumn(rex_i18n::msg('delete_module'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete'));
    $list->setColumnLayout(rex_i18n::msg('delete_module'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('delete_module'), ['func' => 'delete', 'entry_id' => '###location_category_id###']);
    $list->addLinkAttribute(rex_i18n::msg('delete_module'), 'data-confirm', rex_i18n::msg('d2u_helper_confirm_delete'));

    $list->setNoRowsMessage(rex_i18n::msg('d2u_helper_no_categories_found'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('d2u_courses_location_categories'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}
