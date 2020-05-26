<?php
// save settings
if (filter_input(INPUT_POST, "btn_save") == 'save') {
	$settings = (array) rex_post('settings', 'array', array());

	// Linkmap Link and media needs special treatment
	$link_ids = filter_input_array(INPUT_POST, array('REX_INPUT_LINK'=> array('filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY)));

	$settings['article_id_courses'] = $link_ids["REX_INPUT_LINK"][1];
	$settings['article_id_shopping_cart'] = $link_ids["REX_INPUT_LINK"][2];
	$settings['article_id_conditions'] = $link_ids["REX_INPUT_LINK"][3];
	$settings['article_id_terms_of_participation'] = $link_ids["REX_INPUT_LINK"][4];

	if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
		$settings['article_id_locations'] = $link_ids["REX_INPUT_LINK"][5];
	}
	if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
		$settings['article_id_schedule_categories'] = $link_ids["REX_INPUT_LINK"][6];
	}
	if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
		$settings['article_id_target_groups'] = $link_ids["REX_INPUT_LINK"][7];
	}

	// Checkbox also need special treatment if empty
	$settings['ask_kids_go_home_alone'] = array_key_exists('ask_kids_go_home_alone', $settings) ? "active" : "inactive";
	$settings['ask_vacation_pass'] = array_key_exists('ask_vacation_pass', $settings) ? "active" : "inactive";
	$settings['forward_single_course'] = array_key_exists('forward_single_course', $settings) ? "active" : "inactive";
	$settings['lang_wildcard_overwrite'] = array_key_exists('lang_wildcard_overwrite', $settings) ? "true" : "false";
	if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
		$settings['kufer_sync_autoimport'] = array_key_exists('kufer_sync_autoimport', $settings) ? "active" : "inactive";
	}
	if(\rex_addon::get('multinewsletter')->isAvailable()) {
		$settings['multinewsletter_subscribe'] = array_key_exists('multinewsletter_subscribe', $settings) ? "show" : "hide";
	}
	
	// Multiple select box care for option if no option is needed
	$settings['payment_options'] = array_key_exists('payment_options', $settings) ? $settings['payment_options'] : [];
	
	// Save settings
	if(rex_config::set("d2u_courses", $settings)) {
		echo rex_view::success(rex_i18n::msg('form_saved'));

		// Update url schemes
		if(\rex_addon::get('url')->isAvailable()) {
			d2u_addon_backend_helper::update_url_scheme("course_id", $settings['article_id_courses']);
			d2u_addon_backend_helper::update_url_scheme("courses_category_id", $settings['article_id_courses']);
			if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
				d2u_addon_backend_helper::update_url_scheme("location_id", $settings['article_id_locations']);
				d2u_addon_backend_helper::update_url_scheme("location_category_id", $settings['article_id_locations']);
			}
			if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
				d2u_addon_backend_helper::update_url_scheme("schedule_category_id", $settings['article_id_schedule_categories']);
			}
			if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
				d2u_addon_backend_helper::update_url_scheme("target_group_child_id", $settings['article_id_target_groups']);
				d2u_addon_backend_helper::update_url_scheme("target_group_id", $settings['article_id_target_groups']);
			}
			
			// START update views for url addon
			$sql = rex_sql::factory();
			// Online courses (changes need to be done in install.php, update.php and pages/settings.php)
			$showTimeWhere = d2u_courses_frontend_helper::getShowTimeWhere();
			$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_categories AS '
				// Categories with courses
				.'SELECT categories.category_id, categories.name, parents.name AS parent_name, categories.name AS seo_title, categories.picture, courses.updatedate, IF(categories.parent_category_id > 0, categories.parent_category_id, -1) AS parent_category_id, IF(parents.parent_category_id > 0, parents.parent_category_id, -1) AS grand_parent_category_id, IF(grand_parents.parent_category_id > 0, grand_parents.parent_category_id, -1) AS great_grand_parent_category_id
				FROM '. rex::getTablePrefix() .'d2u_courses_categories AS categories
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c
					ON categories.category_id = c2c.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
					ON c2c.course_id = courses.course_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parents
					ON categories.parent_category_id = parents.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS grand_parents
					ON parents.parent_category_id = grand_parents.category_id
				WHERE courses.online_status = "online"
					AND ('. $showTimeWhere .')
					AND courses.updatedate = (
						SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c_max
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2c_max.course_id = courses_max.course_id
						WHERE categories.category_id = c2c_max.category_id AND courses_max.online_status = "online" AND ('. $showTimeWhere .')
					)
				GROUP BY category_id, name, seo_title, picture, updatedate, parent_category_id, grand_parent_category_id '
				// Parents of categories with courses
				.'UNION
				SELECT parents.category_id, parents.name, grand_parents.name AS parent_name, parents.name AS seo_title, parents.picture, courses.updatedate, IF(parents.parent_category_id > 0, parents.parent_category_id, -1) AS parent_category_id, IF(grand_parents.parent_category_id > 0, grand_parents.parent_category_id, -1) AS grand_parent_category_id, -1 AS great_grand_parent_category_id
				FROM '. rex::getTablePrefix() .'d2u_courses_categories AS categories
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c
					ON categories.category_id = c2c.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
					ON c2c.course_id = courses.course_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parents
					ON categories.parent_category_id = parents.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS grand_parents
					ON parents.parent_category_id = grand_parents.category_id
				WHERE parents.category_id > 0
					AND courses.online_status = "online"
					AND ('. $showTimeWhere .')
					AND courses.updatedate = (
						SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c_max
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2c_max.course_id = courses_max.course_id
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories_max ON c2c_max.category_id = categories_max.category_id
						WHERE categories.parent_category_id = categories_max.parent_category_id AND courses_max.online_status = "online" AND ('. $showTimeWhere .')
					)
				GROUP BY category_id, name, seo_title, picture, updatedate, parent_category_id, grand_parent_category_id '
				// Grandparents of categories with courses
				.'UNION
				SELECT grand_parents.category_id, grand_parents.name, great_grand_parents.name AS parent_name, grand_parents.name AS seo_title, grand_parents.picture, courses.updatedate, IF(grand_parents.parent_category_id > 0, grand_parents.parent_category_id, -1) AS parent_category_id, -1 AS grand_parent_category_id, -1 AS great_grand_parent_category_id
				FROM '. rex::getTablePrefix() .'d2u_courses_categories AS categories
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c
					ON categories.category_id = c2c.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
					ON c2c.course_id = courses.course_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parents
					ON categories.parent_category_id = parents.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS grand_parents
					ON parents.parent_category_id = grand_parents.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS great_grand_parents
					ON grand_parents.parent_category_id = great_grand_parents.category_id
				WHERE grand_parents.category_id > 0
					AND courses.online_status = "online"
					AND ('. $showTimeWhere .')
					AND courses.updatedate = (
						SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c_max
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2c_max.course_id = courses_max.course_id
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories_max ON c2c_max.category_id = categories_max.category_id
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parent_categories_max ON categories_max.category_id = parent_categories_max.category_id
						WHERE categories.parent_category_id = parent_categories_max.parent_category_id AND courses_max.online_status = "online" AND ('. $showTimeWhere .')
					)
				GROUP BY category_id, name, seo_title, picture, updatedate, parent_category_id, grand_parent_category_id '
				// Grandparents of categories with courses
				.'UNION
				SELECT great_grand_parents.category_id, great_grand_parents.name, NULL AS parent_name, great_grand_parents.name AS seo_title, great_grand_parents.picture, courses.updatedate, -1 AS parent_category_id, -1 AS grand_parent_category_id, -1 AS great_grand_parent_category_id
				FROM '. rex::getTablePrefix() .'d2u_courses_categories AS categories
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c
					ON categories.category_id = c2c.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
					ON c2c.course_id = courses.course_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parents
					ON categories.parent_category_id = parents.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS grand_parents
					ON parents.parent_category_id = grand_parents.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS great_grand_parents
					ON grand_parents.parent_category_id = great_grand_parents.category_id
				WHERE great_grand_parents.category_id > 0
					AND courses.online_status = "online"
					AND ('. $showTimeWhere .')
					AND courses.updatedate = (
						SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c_max
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2c_max.course_id = courses_max.course_id
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories_max ON c2c_max.category_id = categories_max.category_id
						LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parent_categories_max ON categories_max.category_id = parent_categories_max.category_id
						WHERE categories.parent_category_id = parent_categories_max.parent_category_id AND courses_max.online_status = "online" AND ('. $showTimeWhere .')
					)
				GROUP BY category_id, name, parent_name, seo_title, picture, updatedate, parent_category_id, grand_parent_category_id, great_grand_parent_category_id;');
			// Online courses (changes need to be done in install.php, update.php and pages/settings.php)
			$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_courses AS
				SELECT course_id, courses.name, courses.name AS seo_title, teaser, courses.picture, courses.updatedate, courses.category_id, categories.parent_category_id, parent_categories.parent_category_id AS grand_parent_category_id
				FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories
					ON courses.category_id = categories.category_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parent_categories
					ON categories.parent_category_id = parent_categories.category_id
				WHERE courses.online_status = "online"
					AND ('. $showTimeWhere .');');
			if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
				// Online locations (changes need to be done here and plugin install.php)
				$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_locations AS
					SELECT locations.location_id, locations.name, locations.name AS seo_title, locations.picture, courses.updatedate, locations.location_category_id
					FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations
						ON courses.location_id = locations.location_id
					WHERE courses.online_status = "online"
						AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max
							WHERE locations.location_id = courses_max.location_id AND courses_max.online_status = "online" AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						)
					GROUP BY location_id, name, seo_title, picture, updatedate, location_category_id;');
				// Online location categories (changes need to be done here and plugin install.php)
				$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_location_categories AS
					SELECT categories.location_category_id, categories.name, categories.name AS seo_title, categories.picture, courses.updatedate
					FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations
						ON courses.location_id = locations.location_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_location_categories AS categories
						ON locations.location_category_id = categories.location_category_id
					WHERE courses.online_status = "online"
						AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations_max
								ON courses_max.location_id = locations_max.location_id
							WHERE categories.location_category_id = locations_max.location_category_id AND courses_max.online_status = "online" AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						)
					GROUP BY location_category_id, name, seo_title, picture, updatedate;');
				// END create views for url addon
			}
			if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
				// Online schedule categories (changes need to be done here and plugin install.php / update.php)
				$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_schedule_categories AS
					SELECT schedules.schedule_category_id, schedules.name, schedules.name AS seo_title, schedules.picture, schedules.priority, courses.updatedate, schedules.parent_schedule_category_id
					FROM '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s
						ON schedules.schedule_category_id = c2s.schedule_category_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
						ON c2s.course_id = courses.course_id
					WHERE courses.online_status = "online"
						AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS schedules_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON schedules_max.course_id = courses_max.course_id
							WHERE schedules.schedule_category_id = schedules_max.schedule_category_id AND courses_max.online_status = "online" AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						)
					GROUP BY schedule_category_id, name, seo_title, picture, priority, updatedate, parent_schedule_category_id
					UNION
					SELECT parents.schedule_category_id, parents.name, parents.name AS seo_title, parents.picture, parents.priority, courses.updatedate, parents.parent_schedule_category_id
					FROM '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s
						ON schedules.schedule_category_id = c2s.schedule_category_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
						ON c2s.course_id = courses.course_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS parents
						ON schedules.parent_schedule_category_id = parents.schedule_category_id
					WHERE parents.schedule_category_id > 0
						AND courses.online_status = "online"
						AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2s_max.course_id = courses_max.course_id
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules_max ON c2s_max.schedule_category_id = c2s_max.schedule_category_id
							WHERE schedules.parent_schedule_category_id = schedules_max.schedule_category_id AND courses_max.online_status = "online" AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						)
					GROUP BY schedule_category_id, name, seo_title, picture, priority, updatedate, parent_schedule_category_id;');
			}
			if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
				// Online target groups childs (separator is 00000 - ugly, but only digits are allowed) (changes need to be done here and plugin install.php / update.php)
				$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_target_group_childs AS
					SELECT target.target_group_id, categories.category_id, CONCAT(target.target_group_id, "00000", categories.category_id) AS target_group_child_id, categories.name, categories.name AS seo_title, categories.picture, categories.priority, courses.updatedate
					FROM '. rex::getTablePrefix() .'d2u_courses_target_groups AS target
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_target_groups AS c2t
						ON target.target_group_id = c2t.target_group_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
						ON c2t.course_id = courses.course_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c
						ON courses.course_id = c2c.course_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories
						ON c2c.category_id = categories.category_id
					WHERE categories.category_id > 0
						AND courses.online_status = "online"
						AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS categories_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON categories_max.course_id = courses_max.course_id
							WHERE categories.category_id = categories_max.category_id AND courses_max.online_status = "online" AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						)
					GROUP BY target_group_id, category_id, target_group_child_id, name, seo_title, picture, updatedate;');
				// Online target groups (changes need to be done here and plugin install.php / update.php)
				$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_target_groups AS
					SELECT target.target_group_id, target.name, target.name AS seo_title, target.picture, target.priority, courses.updatedate
					FROM '. rex::getTablePrefix() .'d2u_courses_target_groups AS target
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_target_groups AS c2t
						ON target.target_group_id = c2t.target_group_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
						ON c2t.course_id = courses.course_id
					WHERE courses.online_status = "online"
						AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_target_groups AS targets_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON targets_max.course_id = courses_max.course_id
							WHERE target.target_group_id = targets_max.target_group_id AND courses_max.online_status = "online" AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')
						)
					GROUP BY target_group_id, name, seo_title, picture, priority, updatedate;');
			}
			// END update views for url addon

			\d2u_addon_backend_helper::generateUrlCache();
		}
		
		// Install / update language replacements
		d2u_courses_lang_helper::factory()->install();
		
		// Install / remove Cronjob
		if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			$kufer_cronjob = kufer_sync_cronjob::factory();
			if($this->getConfig('kufer_sync_autoimport') == 'active') {
				if(!$kufer_cronjob->isInstalled()) {
					$kufer_cronjob->install();
				}
			}
			else {
				$kufer_cronjob->delete();
			}
		}
	}
	else {
		echo rex_view::error(rex_i18n::msg('form_save_error'));
	}
}
?>
<form action="<?php print rex_url::currentBackendPage(); ?>" method="post">
	<div class="panel panel-edit">
		<header class="panel-heading"><div class="panel-title"><?php print rex_i18n::msg('d2u_helper_settings'); ?></div></header>
		<div class="panel-body">
			<fieldset>
				<legend><small><i class="rex-icon rex-icon-system"></i></small> <?php echo rex_i18n::msg('d2u_helper_settings'); ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
						d2u_addon_backend_helper::form_linkfield('d2u_courses_settings_article_courses', '1', $this->getConfig('article_id_courses'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						d2u_addon_backend_helper::form_checkbox('d2u_courses_settings_forward_single_course', 'settings[forward_single_course]', 'active', $this->getConfig('forward_single_course') == 'active');
						$options_category_sort = ['name' => rex_i18n::msg('d2u_helper_name'), 'priority' => rex_i18n::msg('header_priority')];
						d2u_addon_backend_helper::form_select('d2u_courses_category_sort', 'settings[default_category_sort]', $options_category_sort, [$this->getConfig('default_category_sort')]);
						$options_show_time = [
							'day_one_start' => rex_i18n::msg('d2u_courses_settings_show_time_day_one_start'),
							'day_one_end' => rex_i18n::msg('d2u_courses_settings_show_time_day_one_end'),
							'day_x_start' => rex_i18n::msg('d2u_courses_settings_show_time_day_x_start'),
							'day_x_end' => rex_i18n::msg('d2u_courses_settings_show_time_day_x_end'),
						];
						d2u_addon_backend_helper::form_select('d2u_courses_settings_show_time', 'settings[show_time]', $options_show_time, [$this->getConfig('show_time')]);
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><small><i class="rex-icon fa-shopping-cart"></i></small> <?php echo rex_i18n::msg('d2u_courses_cart'); ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
						d2u_addon_backend_helper::form_linkfield('d2u_courses_settings_article_shopping_cart', '2', $this->getConfig('article_id_shopping_cart'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						d2u_addon_backend_helper::form_input('d2u_courses_settings_request_form_email', 'settings[request_form_email]', $this->getConfig('request_form_email'), TRUE, FALSE, 'email');
						d2u_addon_backend_helper::form_input('d2u_courses_settings_request_form_sender_email', 'settings[request_form_sender_email]', $this->getConfig('request_form_sender_email'), TRUE, FALSE, 'email');
						d2u_addon_backend_helper::form_linkfield('d2u_courses_settings_article_conditions', '3', $this->getConfig('article_id_conditions'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						d2u_addon_backend_helper::form_linkfield('d2u_courses_settings_article_terms_of_participation', '4', $this->getConfig('article_id_terms_of_participation'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						$options_paymant = [
							'bank_transfer' => rex_i18n::msg('d2u_courses_payment_bank_transfer'),
							'direct_debit' => rex_i18n::msg('d2u_courses_payment_direct_debit'),
							'cash' => rex_i18n::msg('d2u_courses_payment_cash')
						];
						d2u_addon_backend_helper::form_select('d2u_courses_payment', 'settings[payment_options][]', $options_paymant, $this->getConfig('payment_options'), 3, TRUE);
						d2u_addon_backend_helper::form_checkbox('d2u_courses_settings_ask_kids_go_home_alone', 'settings[ask_kids_go_home_alone]', 'active', $this->getConfig('ask_kids_go_home_alone') == 'active');
						if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() === FALSE) {
							d2u_addon_backend_helper::form_checkbox('d2u_courses_settings_ask_vacation_pass', 'settings[ask_vacation_pass]', 'active', $this->getConfig('ask_vacation_pass') == 'active');
						}
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><small><i class="rex-icon rex-icon-language"></i></small> <?php echo rex_i18n::msg('d2u_helper_lang_replacements'); ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
						d2u_addon_backend_helper::form_checkbox('d2u_helper_lang_wildcard_overwrite', 'settings[lang_wildcard_overwrite]', 'true', $this->getConfig('lang_wildcard_overwrite') == 'true');
						foreach(rex_clang::getAll() as $rex_clang) {
							print '<dl class="rex-form-group form-group">';
							print '<dt><label>'. $rex_clang->getName() .'</label></dt>';
							print '<dd>';
							print '<select class="form-control" name="settings[lang_replacement_'. $rex_clang->getId() .']">';
							$replacement_options = [
								'd2u_helper_lang_german' => 'german',
								'd2u_helper_lang_english' => 'english',
							];
							foreach($replacement_options as $key => $value) {
								$selected = $value == $this->getConfig('lang_replacement_'. $rex_clang->getId()) ? ' selected="selected"' : '';
								print '<option value="'. $value .'"'. $selected .'>'. rex_i18n::msg('d2u_helper_lang_replacements_install') .' '. rex_i18n::msg($key) .'</option>';
							}
							print '</select>';
							print '</dl>';
						}
					?>
				</div>
			</fieldset>
			<?php
				if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon fa-map-marker"></i></small> <?php echo rex_i18n::msg('d2u_courses_locations'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_input('d2u_courses_location_settings_bg_color', 'settings[location_bg_color]', $this->getConfig('location_bg_color'), TRUE, FALSE, 'color');
							d2u_addon_backend_helper::form_linkfield('d2u_courses_location_settings_article', '5', $this->getConfig('article_id_locations'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						?>
					</div>
				</fieldset>
			<?php
				}
				if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon fa-calendar"></i></small> <?php echo rex_i18n::msg('d2u_courses_schedule_categories'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_input('d2u_courses_schedule_category_settings_bg_color', 'settings[schedule_category_bg_color]', $this->getConfig('schedule_category_bg_color'), TRUE, FALSE, 'color');
							d2u_addon_backend_helper::form_linkfield('d2u_courses_schedule_category_settings_article', '6', $this->getConfig('article_id_schedule_categories'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						?>
					</div>
				</fieldset>
			<?php
				}
				if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon fa-bullseye"></i></small> <?php echo rex_i18n::msg('d2u_courses_target_groups'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_input('d2u_courses_target_groups_settings_bg_color', 'settings[target_group_bg_color]', $this->getConfig('target_group_bg_color'), TRUE, FALSE, 'color');
							d2u_addon_backend_helper::form_linkfield('d2u_courses_target_groups_settings_article', '7', $this->getConfig('article_id_target_groups'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						?>
					</div>
				</fieldset>
			<?php
				}
				if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon fa-cloud-download"></i></small> <?php echo rex_i18n::msg('d2u_courses_kufer_sync'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_checkbox('d2u_courses_import_settings_autoimport', 'settings[kufer_sync_autoimport]', 'active', $this->getConfig('kufer_sync_autoimport') == 'active');
							d2u_addon_backend_helper::form_input('d2u_courses_import_settings_xml_url', 'settings[kufer_sync_xml_url]', $this->getConfig('kufer_sync_xml_url'), TRUE, FALSE, 'text');
							$options_categories = [];
							foreach(\D2U_Courses\Category::getAllNotParents() as $category) {
								$options_categories[$category->category_id] = ($category->parent_category ? ($category->parent_category->parent_category ? ($category->parent_category->parent_category->parent_category ? $category->parent_category->parent_category->parent_category->name ." → " : "" ). $category->parent_category->parent_category->name ." → " : "" ). $category->parent_category->name ." → " : "" ). $category->name;
							}
							d2u_addon_backend_helper::form_select('d2u_courses_import_settings_default_category', 'settings[kufer_sync_default_category_id]', $options_categories, [$this->getConfig('kufer_sync_default_category_id')], 1, FALSE, FALSE);
							if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
								$options_locations = [];
								foreach(\D2U_Courses\Location::getAll() as $location) {
									$options_locations[$location->location_id] = ($location->location_category !== FALSE ? $location->location_category->name ." → " : "" ). $location->name;
								}
								asort($options_locations);
								d2u_addon_backend_helper::form_select('d2u_courses_import_settings_default_location', 'settings[kufer_sync_default_location_id]', $options_locations, [$this->getConfig('kufer_sync_default_location_id')], 1, FALSE, FALSE);
							}
							d2u_addon_backend_helper::form_input('d2u_courses_import_settings_xml_registration_path', 'settings[kufer_sync_xml_registration_path]', $this->getConfig('kufer_sync_xml_registration_path'), TRUE, FALSE, 'text');
						?>
					</div>
				</fieldset>
			<?php
				}
				if(\rex_addon::get('multinewsletter')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon rex-icon-envelope"></i></small> <?php echo rex_i18n::msg('multinewsletter_addon_short_title'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_checkbox('d2u_courses_settings_multinewsletter_subscribe', 'settings[multinewsletter_subscribe]', 'show', $this->getConfig('multinewsletter_subscribe') == 'show');
							$options_groups = [];
							if(class_exists('MultinewsletterGroupList')) {
								// MultiNewsletter <= 3.2.0
								foreach(MultinewsletterGroupList::getAll() as $group) {
									$options_groups[$group->getId()] = $group->getName();
								}
							}
							else {
								// MultiNewsletter >= 3.2.1
								foreach(MultinewsletterGroup::getAll() as $group) {
									$options_groups[$group->id] = $group->name;
								}
							}
							d2u_addon_backend_helper::form_select('d2u_courses_settings_multinewsletter_group', 'settings[multinewsletter_group]', $options_groups, [$this->getConfig('multinewsletter_group')], 1, FALSE, FALSE);
						?>
						<script>
							function changeType() {
								if($('input[name="settings\\[multinewsletter_subscribe\\]"]').is(':checked')) {
									$('#settings\\[multinewsletter_group\\]').fadeIn();
								}
								else {
									$('#settings\\[multinewsletter_group\\]').hide();
								}
							}

							// On init
							changeType();
							// On change
							$('input[name="settings\\[multinewsletter_subscribe\\]"]').on('change', function() {
								changeType();
							});
						</script>
					</div>
				</fieldset>
			<?php
				}
			?>
		</div>
		<footer class="panel-footer">
			<div class="rex-form-panel-footer">
				<div class="btn-toolbar">
					<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="save"><?php echo rex_i18n::msg('form_save'); ?></button>
				</div>
			</div>
		</footer>
	</div>
</form>
<?php
	print d2u_addon_backend_helper::getCSS();
	print d2u_addon_backend_helper::getJS();
	print d2u_addon_backend_helper::getJSOpenAll();