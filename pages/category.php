<?php
$func = rex_request('func', 'string');
$entry_id = rex_request('entry_id', 'int');
$message = rex_get('message', 'string');

// Print comments
if($message !== '') {
	print rex_view::success(rex_i18n::msg($message));
}

// save settings
if (intval(filter_input(INPUT_POST, "btn_save")) === 1 || intval(filter_input(INPUT_POST, "btn_apply")) === 1) {
	$form = rex_post('form', 'array', []);

	// Media fields and links need special treatment
	$input_media = rex_post('REX_INPUT_MEDIA', 'array', []);

	$category_id = $form['category_id'];
	$category = new D2U_Courses\Category($category_id);
	$category->color = $form['color'];
	$category->picture = $input_media[1];
	$category->parent_category = $form['parent_category_id'] > 0 ? new D2U_Courses\Category($form['parent_category_id']) : false;
	$category->priority = $form['priority'];
	if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
		$category->kufer_categories = array_map('trim', preg_grep('/^\s*$/s', explode(PHP_EOL, $form['kufer_categories']), PREG_GREP_INVERT));
		$category->google_type = $form['google_type'];
	}
	$category->name = $form['name'];
	$category->description = $form['description'];
	
	// message output
	$message = 'form_save_error';
	if($category->save()) {
		$message = 'form_saved';
	}
	
	// Redirect to make reload and thus double save impossible
	if(intval(filter_input(INPUT_POST, "btn_apply", FILTER_VALIDATE_INT)) === 1 &&$category !== false) {
		header("Location: ". rex_url::currentBackendPage(["entry_id"=>$category->category_id, "func"=>'edit', "message"=>$message], false));
	}
	else {
		header("Location: ". rex_url::currentBackendPage(["message"=>$message], false));
	}
	exit;
}
// Delete
else if(intval(filter_input(INPUT_POST, "btn_delete", FILTER_VALIDATE_INT)) === 1 || $func === 'delete') {
	$category_id = $entry_id;
	if($category_id === 0) {
		$form = rex_post('form', 'array', []);
		$category_id = $form['category_id'];
	}
	$category = new D2U_Courses\Category($category_id);

	// Check if object is used
	$uses_courses = $category->getCourses();
	$uses_categories = $category->getChildren();
	$used_in_settings = false;
	if(rex_config::get('d2u_courses', 'kufer_sync_default_category_id', 0) == $category->category_id) {
		$used_in_settings = true;
	}

	if(count($uses_courses) == 0 && count($uses_categories) == 0 && $used_in_settings == false) {
		$category->delete();
		print rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message);
	}
	else {
		$message = '<ul>';
		foreach($uses_courses as $uses_course) {
			$message .= '<li>'. rex_i18n::msg('d2u_courses_course') .': <a href="index.php?page=d2u_courses/course&func=edit&entry_id='. $uses_course->course_id .'">'. $uses_course->name.'</a></li>';
		}
		foreach($uses_categories as $uses_category) {
			$message .= '<li>'. rex_i18n::msg('d2u_helper_category') .': <a href="index.php?page=d2u_courses/category&func=edit&entry_id='. $uses_category->category_id .'">'. $uses_category->name.'</a></li>';
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
if ($func === 'edit' || $func === 'clone' || $func === 'add') {
	$readonly = false;
?>
	<form action="<?php print rex_url::currentBackendPage(); ?>" method="post">
		<div class="panel panel-edit">
			<header class="panel-heading"><div class="panel-title"><?php print rex_i18n::msg('d2u_helper_categories'); ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[category_id]" value="<?php echo ($func === 'edit' ? $entry_id : 0); ?>">
				<?php

					$category = new D2U_Courses\Category($entry_id);
					d2u_addon_backend_helper::form_input('d2u_helper_name', "form[name]", $category->name, true, $readonly);
					d2u_addon_backend_helper::form_textarea('d2u_courses_description', "form[description]", $category->description, 5, false, $readonly, true);
					d2u_addon_backend_helper::form_input('d2u_courses_categories_color', 'form[color]', $category->color, true, $readonly, 'color');
					d2u_addon_backend_helper::form_mediafield('d2u_helper_picture', '1', $category->picture, $readonly);
					$options_parents = [-1 => rex_i18n::msg('d2u_courses_categories_parent_category_none')];
					foreach(D2U_Courses\Category::getAllParents() as $parent) {
                        if($parent->category_id != $category->category_id) {
                            $options_parents[$parent->category_id] = $parent->name;
							foreach($parent->getChildren() as $child) {
								if($child->category_id != $category->category_id) {
									$options_parents[$child->category_id] = $parent->name ." → ". $child->name;
									foreach($child->getChildren() as $grand_child) {
										if($grand_child->category_id != $category->category_id) {
											$options_parents[$grand_child->category_id] = $parent->name ." → ". $child->name ." → ". $grand_child->name;
										}
									}
								}
							}
                        }
					}
					d2u_addon_backend_helper::form_select('d2u_courses_categories_parent_category', 'form[parent_category_id]', $options_parents, ($category->parent_category === false ? [-1] : [$category->parent_category->category_id]), 1, false, $readonly);
					d2u_addon_backend_helper::form_input('header_priority', 'form[priority]', $category->priority, true, $readonly, 'number');
					if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
						d2u_addon_backend_helper::form_textarea('d2u_courses_kufer_categories', 'form[kufer_categories]', implode(PHP_EOL, $category->kufer_categories), 5, false, $readonly, false);
						$options_google_type = [
							"" => rex_i18n::msg('d2u_courses_google_type_none'),
							"course" => rex_i18n::msg('d2u_courses_google_type_course'),
							"event" => rex_i18n::msg('d2u_courses_google_type_event'),
						];
						d2u_addon_backend_helper::form_select('d2u_courses_google_type', 'form[google_type]', $options_google_type, [$category->google_type], 1, false, $readonly);
						if(!rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
							d2u_addon_backend_helper::form_infotext('d2u_courses_google_type_event_hint', 'google_type_event_hint');
						}
					}
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

if ($func === '') {
	$query = 'SELECT category.category_id, CONCAT_WS(" → ", great_grand_parents.name, grand_parents.name, parents.name, category.name) AS full_name, category.priority '
		. 'FROM '. rex::getTablePrefix() .'d2u_courses_categories AS category '
		. 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parents '
			. 'ON category.parent_category_id = parents.category_id '
		. 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS grand_parents '
			. 'ON parents.parent_category_id = grand_parents.category_id '
		. 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS great_grand_parents '
			. 'ON grand_parents.parent_category_id = great_grand_parents.category_id ';
	if($this->getConfig('default_category_sort') == 'priority') {
		$query .= 'ORDER BY priority ASC';
	}
	else {
		$query .= 'ORDER BY full_name ASC';
	}
    $list = rex_list::factory($query, 1000);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon rex-icon-open-category"></i>';
 	$thIcon = "";
	$thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-module"></i></a>';

	$list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'entry_id' => '###category_id###']);

    $list->setColumnLabel('category_id', rex_i18n::msg('id'));
    $list->setColumnLayout('category_id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);

    $list->setColumnLabel('full_name', rex_i18n::msg('d2u_helper_name'));
    $list->setColumnParams('full_name', ['func' => 'edit', 'entry_id' => '###category_id###']);

	$list->setColumnLabel('priority', rex_i18n::msg('header_priority'));

	$list->addColumn(rex_i18n::msg('d2u_helper_clone'), '<i class="rex-icon fa-copy"></i> ' . rex_i18n::msg('d2u_helper_clone'));
	$list->setColumnLayout(rex_i18n::msg('d2u_helper_clone'), ['', '<td class="rex-table-action" colspan="3">###VALUE###</td>']);
	$list->setColumnParams(rex_i18n::msg('d2u_helper_clone'), ['func' => 'clone', 'entry_id' => '###category_id###']);

	$list->addColumn(rex_i18n::msg('module_functions'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'));
    $list->setColumnLayout(rex_i18n::msg('module_functions'), ['<th class="rex-table-action">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('module_functions'), ['func' => 'edit', 'entry_id' => '###category_id###']);

	$list->addColumn(rex_i18n::msg('delete_module'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete'));
	$list->setColumnLayout(rex_i18n::msg('delete_module'), ['', '<td class="rex-table-action">###VALUE###</td>']);
	$list->setColumnParams(rex_i18n::msg('delete_module'), ['func' => 'delete', 'entry_id' => '###category_id###']);
	$list->addLinkAttribute(rex_i18n::msg('delete_module'), 'data-confirm', rex_i18n::msg('d2u_helper_confirm_delete'));

    $list->setNoRowsMessage(rex_i18n::msg('d2u_helper_no_categories_found'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('d2u_helper_category'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}