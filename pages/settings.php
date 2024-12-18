<?php
// save settings

use TobiasKrais\D2UCourses\Category;
use TobiasKrais\D2UCourses\FrontendHelper;
use TobiasKrais\D2UCourses\KuferSyncCronjob;
use TobiasKrais\D2UCourses\LocationCategory;

if ('save' === filter_input(INPUT_POST, 'btn_save')) {
    $settings = rex_post('settings', 'array', []);

    // Linkmap Link and media needs special treatment
    $link_ids = filter_input_array(INPUT_POST, ['REX_INPUT_LINK' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY]]);

    $settings['article_id_courses'] = is_array($link_ids['REX_INPUT_LINK']) && $link_ids['REX_INPUT_LINK'][1] > 0 ? $link_ids['REX_INPUT_LINK'][1] : rex_article::getSiteStartArticleId();
    $settings['article_id_shopping_cart'] = is_array($link_ids['REX_INPUT_LINK']) ? $link_ids['REX_INPUT_LINK'][2] : 0;
    $settings['article_id_conditions'] = is_array($link_ids['REX_INPUT_LINK']) ? $link_ids['REX_INPUT_LINK'][3] : 0;
    $settings['article_id_terms_of_participation'] = is_array($link_ids['REX_INPUT_LINK']) ? $link_ids['REX_INPUT_LINK'][4] : 0;

    if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
        $settings['article_id_locations'] = is_array($link_ids['REX_INPUT_LINK']) && $link_ids['REX_INPUT_LINK'][5] > 0 ? $link_ids['REX_INPUT_LINK'][5] : $settings['article_id_courses'];
		$settings['forward_single_location'] = array_key_exists('forward_single_location', $settings);
    }
    if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
        $settings['article_id_schedule_categories'] = is_array($link_ids['REX_INPUT_LINK']) && $link_ids['REX_INPUT_LINK'][6] > 0 ? $link_ids['REX_INPUT_LINK'][6] : $settings['article_id_courses'];
    }
    if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
        $settings['article_id_target_groups'] = is_array($link_ids['REX_INPUT_LINK']) && $link_ids['REX_INPUT_LINK'][7] > 0 ? $link_ids['REX_INPUT_LINK'][7] : $settings['article_id_courses'];
    }

    // Checkbox also need special treatment if empty
    $settings['allow_company'] = array_key_exists('allow_company', $settings);
    $settings['allow_company_bank_transfer'] = array_key_exists('allow_company_bank_transfer', $settings);
    $settings['ask_kids_go_home_alone'] = array_key_exists('ask_kids_go_home_alone', $settings) ? 'active' : 'inactive';
    $settings['ask_vacation_pass'] = array_key_exists('ask_vacation_pass', $settings) ? 'active' : 'inactive';
    $settings['forward_single_course'] = array_key_exists('forward_single_course', $settings) ? 'active' : 'inactive';
    $settings['lang_wildcard_overwrite'] = array_key_exists('lang_wildcard_overwrite', $settings) ? 'true' : 'false';
	
    if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
        $settings['kufer_sync_autoimport'] = array_key_exists('kufer_sync_autoimport', $settings) ? 'active' : 'inactive';
    }
    if (\rex_addon::get('multinewsletter')->isAvailable()) {
        $settings['multinewsletter_subscribe'] = array_key_exists('multinewsletter_subscribe', $settings) ? 'show' : 'hide';
    }

    // Multiple select box care for option if no option is needed
    $settings['payment_options'] = array_key_exists('payment_options', $settings) ? $settings['payment_options'] : [];
    if (\rex_addon::get('multinewsletter')->isAvailable()) {
        $settings['multinewsletter_group'] = array_key_exists('multinewsletter_group', $settings) ? $settings['multinewsletter_group'] : [];
    }

    // Save settings
    if (rex_config::set('d2u_courses', $settings)) {
        echo rex_view::success(rex_i18n::msg('form_saved'));
        // Update url schemes
        if (\rex_addon::get('url')->isAvailable()) {
            \TobiasKrais\D2UHelper\BackendHelper::update_url_scheme(\rex::getTablePrefix() .'d2u_courses_url_courses', $settings['article_id_courses']);
            \TobiasKrais\D2UHelper\BackendHelper::update_url_scheme(\rex::getTablePrefix() .'d2u_courses_url_categories', $settings['article_id_courses']);
            if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
                \TobiasKrais\D2UHelper\BackendHelper::update_url_scheme(\rex::getTablePrefix() .'d2u_courses_url_location_categories', $settings['article_id_locations']);
                \TobiasKrais\D2UHelper\BackendHelper::update_url_scheme(\rex::getTablePrefix() .'d2u_courses_url_locations', $settings['article_id_locations']);
            }
            if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
                \TobiasKrais\D2UHelper\BackendHelper::update_url_scheme(\rex::getTablePrefix() .'d2u_courses_url_schedule_categories', $settings['article_id_schedule_categories']);
            }
            if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
                \TobiasKrais\D2UHelper\BackendHelper::update_url_scheme(\rex::getTablePrefix() .'d2u_courses_url_target_groups', $settings['article_id_target_groups']);
                \TobiasKrais\D2UHelper\BackendHelper::update_url_scheme(\rex::getTablePrefix() .'d2u_courses_url_target_group_childs', $settings['article_id_target_groups']);
            }

            // START update views for url addon
            $sql = rex_sql::factory();
            // Online courses (changes need to be done in install.php and pages/settings.php)
            $showTimeWhere = FrontendHelper::getShowTimeWhere();
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
				GROUP BY category_id, name, parent_name, seo_title, picture, updatedate, parent_category_id, grand_parent_category_id, great_grand_parent_category_id;', );
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
            if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
                // Online locations (changes need to be done here and plugin install.php)
                $sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_locations AS
					SELECT locations.location_id, locations.name, locations.name AS seo_title, locations.picture, courses.updatedate, locations.location_category_id
					FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations
						ON courses.location_id = locations.location_id
					WHERE courses.online_status = "online"
						AND ('. FrontendHelper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max
							WHERE locations.location_id = courses_max.location_id AND courses_max.online_status = "online" AND ('. FrontendHelper::getShowTimeWhere() .')
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
						AND ('. FrontendHelper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations_max
								ON courses_max.location_id = locations_max.location_id
							WHERE categories.location_category_id = locations_max.location_category_id AND courses_max.online_status = "online" AND ('. FrontendHelper::getShowTimeWhere() .')
						)
					GROUP BY location_category_id, name, seo_title, picture, updatedate;');
                // END create views for url addon
            }
            if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
                // Online schedule categories (changes need to be done here and plugin install.php / update.php)
                $sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_schedule_categories AS
					SELECT schedules.schedule_category_id, schedules.name, schedules.name AS seo_title, schedules.picture, schedules.priority, courses.updatedate, schedules.parent_schedule_category_id
					FROM '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s
						ON schedules.schedule_category_id = c2s.schedule_category_id
					LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
						ON c2s.course_id = courses.course_id
					WHERE courses.online_status = "online"
						AND ('. FrontendHelper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS schedules_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON schedules_max.course_id = courses_max.course_id
							WHERE schedules.schedule_category_id = schedules_max.schedule_category_id AND courses_max.online_status = "online" AND ('. FrontendHelper::getShowTimeWhere() .')
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
						AND ('. FrontendHelper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2s_max.course_id = courses_max.course_id
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules_max ON c2s_max.schedule_category_id = c2s_max.schedule_category_id
							WHERE schedules.parent_schedule_category_id = schedules_max.schedule_category_id AND courses_max.online_status = "online" AND ('. FrontendHelper::getShowTimeWhere() .')
						)
					GROUP BY schedule_category_id, name, seo_title, picture, priority, updatedate, parent_schedule_category_id;');
            }
            if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
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
						AND ('. FrontendHelper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS categories_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON categories_max.course_id = courses_max.course_id
							WHERE categories.category_id = categories_max.category_id AND courses_max.online_status = "online" AND ('. FrontendHelper::getShowTimeWhere() .')
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
						AND ('. FrontendHelper::getShowTimeWhere() .')
						AND courses.updatedate = (
							SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_target_groups AS targets_max
							LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON targets_max.course_id = courses_max.course_id
							WHERE target.target_group_id = targets_max.target_group_id AND courses_max.online_status = "online" AND ('. FrontendHelper::getShowTimeWhere() .')
						)
					GROUP BY target_group_id, name, seo_title, picture, priority, updatedate;');
            }
            // END update views for url addon

            \TobiasKrais\D2UHelper\BackendHelper::generateUrlCache();
        }

        // Install / update language replacements
        TobiasKrais\D2UCourses\LangHelper::factory()->install();

        // Install / remove Cronjob
        if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
            $kufer_cronjob = KuferSyncCronjob::factory();
            if ('active' === rex_config::get('d2u_courses', 'kufer_sync_autoimport')) {
                if (!$kufer_cronjob->isInstalled()) {
                    $kufer_cronjob->install();
                }
            } else {
                $kufer_cronjob->delete();
            }
        }
    } else {
        echo rex_view::error(rex_i18n::msg('form_save_error'));
    }
}
?>
<form action="<?= rex_url::currentBackendPage() ?>" method="post">
	<div class="panel panel-edit">
		<header class="panel-heading"><div class="panel-title"><?= rex_i18n::msg('d2u_helper_settings') ?></div></header>
		<div class="panel-body">
			<fieldset>
				<legend><small><i class="rex-icon rex-icon-system"></i></small> <?= rex_i18n::msg('d2u_helper_settings') ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
                        \TobiasKrais\D2UHelper\BackendHelper::form_linkfield('d2u_courses_settings_article_courses', '1', (int) rex_config::get('d2u_courses', 'article_id_courses'), (int) rex_config::get('d2u_helper', 'default_lang', rex_clang::getStartId()));
                        \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_settings_forward_single_course', 'settings[forward_single_course]', 'active', 'active' === rex_config::get('d2u_courses', 'forward_single_course'));
                        $options_category_sort = ['name' => rex_i18n::msg('d2u_helper_name'), 'priority' => rex_i18n::msg('header_priority')];
                        \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_category_sort', 'settings[default_category_sort]', $options_category_sort, [(string) rex_config::get('d2u_courses', 'default_category_sort')]);
                        $options_show_time = [
                            'day_one_start' => rex_i18n::msg('d2u_courses_settings_show_time_day_one_start'),
                            'day_one_end' => rex_i18n::msg('d2u_courses_settings_show_time_day_one_end'),
                            'day_x_start' => rex_i18n::msg('d2u_courses_settings_show_time_day_x_start'),
                            'day_x_end' => rex_i18n::msg('d2u_courses_settings_show_time_day_x_end'),
                        ];
                        \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_settings_show_time', 'settings[show_time]', $options_show_time, [(string) rex_config::get('d2u_courses', 'show_time')]);
                        \TobiasKrais\D2UHelper\BackendHelper::form_textarea('d2u_courses_settings_email_text', 'settings[email_text]', (string) rex_config::get('d2u_courses', 'email_text'), 5, false, false, true);
                    ?>
				</div>
			</fieldset>
			<fieldset>
				<legend><small><i class="rex-icon fa-google"></i></small> <?= rex_i18n::msg('d2u_courses_settings_google') ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
                        \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_settings_company_name', 'settings[company_name]', (string) rex_config::get('d2u_courses', 'company_name'), true, false, 'text');
                    ?>
				</div>
			</fieldset>
			<fieldset>
				<legend><small><i class="rex-icon fa-shopping-cart"></i></small> <?= rex_i18n::msg('d2u_courses_cart') ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
                        \TobiasKrais\D2UHelper\BackendHelper::form_linkfield('d2u_courses_settings_article_shopping_cart', '2', (int) rex_config::get('d2u_courses', 'article_id_shopping_cart'), (int) rex_config::get('d2u_helper', 'default_lang', rex_clang::getStartId()));
                        \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_settings_request_form_email', 'settings[request_form_email]', (string) rex_config::get('d2u_courses', 'request_form_email'), true, false, 'email');
                        \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_settings_request_form_sender_email', 'settings[request_form_sender_email]', (string) rex_config::get('d2u_courses', 'request_form_sender_email'), true, false, 'email');
                        \TobiasKrais\D2UHelper\BackendHelper::form_linkfield('d2u_courses_settings_article_conditions', '3', (int) rex_config::get('d2u_courses', 'article_id_conditions'), (int) rex_config::get('d2u_helper', 'default_lang', rex_clang::getStartId()));
                        \TobiasKrais\D2UHelper\BackendHelper::form_linkfield('d2u_courses_settings_article_terms_of_participation', '4', (int) rex_config::get('d2u_courses', 'article_id_terms_of_participation'), (int) rex_config::get('d2u_helper', 'default_lang', rex_clang::getStartId()));
                        \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_settings_ask_kids_go_home_alone', 'settings[ask_kids_go_home_alone]', 'active', 'active' === (string) rex_config::get('d2u_courses', 'ask_kids_go_home_alone'));
                        if (false === rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
                            \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_settings_ask_vacation_pass', 'settings[ask_vacation_pass]', 'active', 'active' === (string) rex_config::get('d2u_courses', 'ask_vacation_pass'));
                        }
                        $options_paymant = [
                            'bank_transfer' => rex_i18n::msg('d2u_courses_payment_bank_transfer'),
                            'direct_debit' => rex_i18n::msg('d2u_courses_payment_direct_debit'),
                            'cash' => rex_i18n::msg('d2u_courses_payment_cash'),
                        ];
                        \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_payment', 'settings[payment_options][]', $options_paymant, rex_config::get('d2u_courses', 'payment_options', []), 3, true); /** @phpstan-ignore-line */
                        \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_settings_allow_company', 'settings[allow_company]', 'true', (bool) rex_config::get('d2u_courses', 'allow_company'));
                        \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_settings_payment_options_allow_company_bank_transfer', 'settings[allow_company_bank_transfer]', 'true', (bool) rex_config::get('d2u_courses', 'allow_company_bank_transfer'));
                    ?>
					<script>
						function changeCompany() {
							if($('input[name="settings\\[allow_company\\]"]').is(':checked')) {
								$('#settings\\[allow_company_bank_transfer\\]').fadeIn();
							}
							else {
								$('#settings\\[allow_company_bank_transfer\\]').hide();
							}
						}

						// On init
						changeCompany();
						// On change
						$('input[name="settings\\[allow_company\\]"]').on('change', function() {
							changeCompany();
						});
					</script>
				</div>
			</fieldset>
			<fieldset>
				<legend><small><i class="rex-icon rex-icon-language"></i></small> <?= rex_i18n::msg('d2u_helper_lang_replacements') ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
                        \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_helper_lang_wildcard_overwrite', 'settings[lang_wildcard_overwrite]', 'true', 'true' === rex_config::get('d2u_courses', 'lang_wildcard_overwrite'));
                        foreach (rex_clang::getAll() as $rex_clang) {
                            echo '<dl class="rex-form-group form-group">';
                            echo '<dt><label>'. $rex_clang->getName() .'</label></dt>';
                            echo '<dd>';
                            echo '<select class="form-control" name="settings[lang_replacement_'. $rex_clang->getId() .']">';
                            $replacement_options = [
                                'd2u_helper_lang_german' => 'german',
                                'd2u_helper_lang_english' => 'english',
                            ];
                            foreach ($replacement_options as $key => $value) {
                                $selected = $value === rex_config::get('d2u_courses', 'lang_replacement_'. $rex_clang->getId()) ? ' selected="selected"' : '';
                                echo '<option value="'. $value .'"'. $selected .'>'. rex_i18n::msg('d2u_helper_lang_replacements_install') .' '. rex_i18n::msg($key) .'</option>';
                            }
                            echo '</select>';
                            echo '</dl>';
                        }
                    ?>
				</div>
			</fieldset>
			<?php
                if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
            ?>
				<fieldset>
					<legend><small><i class="rex-icon fa-map-marker"></i></small> <?= rex_i18n::msg('d2u_courses_locations') ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_location_settings_bg_color', 'settings[location_bg_color]', (string) rex_config::get('d2u_courses', 'location_bg_color'), true, false, 'color');
                            \TobiasKrais\D2UHelper\BackendHelper::form_linkfield('d2u_courses_location_settings_article', '5', (int) rex_config::get('d2u_courses', 'article_id_locations'), (int) rex_config::get('d2u_helper', 'default_lang', rex_clang::getStartId()));
							\TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_location_settings_forward_single_location', 'settings[forward_single_location]', 'true', (bool) rex_config::get('d2u_courses', 'forward_single_location'));
						?>
					</div>
				</fieldset>
			<?php
                }
                if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
            ?>
				<fieldset>
					<legend><small><i class="rex-icon fa-calendar"></i></small> <?= rex_i18n::msg('d2u_courses_schedule_categories') ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_schedule_category_settings_bg_color', 'settings[schedule_category_bg_color]', (string) rex_config::get('d2u_courses', 'schedule_category_bg_color'), true, false, 'color');
                            \TobiasKrais\D2UHelper\BackendHelper::form_linkfield('d2u_courses_schedule_category_settings_article', '6', (int) rex_config::get('d2u_courses', 'article_id_schedule_categories'), (int) rex_config::get('d2u_helper', 'default_lang', rex_clang::getStartId()));
                        ?>
					</div>
				</fieldset>
			<?php
                }
                if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
            ?>
				<fieldset>
					<legend><small><i class="rex-icon fa-bullseye"></i></small> <?= rex_i18n::msg('d2u_courses_target_groups') ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_target_groups_settings_bg_color', 'settings[target_group_bg_color]', (string) rex_config::get('d2u_courses', 'target_group_bg_color'), true, false, 'color');
                            \TobiasKrais\D2UHelper\BackendHelper::form_linkfield('d2u_courses_target_groups_settings_article', '7', (int) rex_config::get('d2u_courses', 'article_id_target_groups'), (int) rex_config::get('d2u_helper', 'default_lang', rex_clang::getStartId()));
                        ?>
					</div>
				</fieldset>
			<?php
                }
                if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
            ?>
				<fieldset>
					<legend><small><i class="rex-icon fa-cloud-download"></i></small> <?= rex_i18n::msg('d2u_courses_kufer_sync') ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
                            \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_import_settings_autoimport', 'settings[kufer_sync_autoimport]', 'active', 'active' === rex_config::get('d2u_courses', 'kufer_sync_autoimport'));
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_import_settings_xml_url', 'settings[kufer_sync_xml_url]', (string) rex_config::get('d2u_courses', 'kufer_sync_xml_url'), true, false, 'text');
                            $options_categories = [];
                            foreach (\TobiasKrais\D2UCourses\Category::getAllNotParents() as $category) {
                                $options_categories[$category->category_id] = ($category->parent_category instanceof Category ? ($category->parent_category->parent_category instanceof Category ? ($category->parent_category->parent_category->parent_category instanceof Category ? $category->parent_category->parent_category->parent_category->name .' → ' : ''). $category->parent_category->parent_category->name .' → ' : ''). $category->parent_category->name .' → ' : ''). $category->name;
                            }
                            \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_import_settings_default_category', 'settings[kufer_sync_default_category_id]', $options_categories, [(int) rex_config::get('d2u_courses', 'kufer_sync_default_category_id')], 1, false, false);
                            if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
                                $options_locations = [];
                                foreach (\TobiasKrais\D2UCourses\Location::getAll() as $location) {
                                    $options_locations[$location->location_id] = ($location->location_category instanceof LocationCategory ? $location->location_category->name .' → ' : ''). $location->name;
                                }
                                asort($options_locations);
                                \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_import_settings_default_location', 'settings[kufer_sync_default_location_id]', $options_locations, [(int) rex_config::get('d2u_courses', 'kufer_sync_default_location_id')], 1, false, false);
                            }
                            \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_import_settings_xml_registration_path', 'settings[kufer_sync_xml_registration_path]', (string) rex_config::get('d2u_courses', 'kufer_sync_xml_registration_path'), true, false, 'text');
                        ?>
					</div>
				</fieldset>
			<?php
                }
                if (\rex_addon::get('multinewsletter')->isAvailable()) {
            ?>
				<fieldset>
					<legend><small><i class="rex-icon rex-icon-envelope"></i></small> <?= rex_i18n::msg('multinewsletter_addon_short_title') ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
                            \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_settings_multinewsletter_subscribe', 'settings[multinewsletter_subscribe]', 'show', 'show' === rex_config::get('d2u_courses', 'multinewsletter_subscribe'));
                            $options_groups = [];
                            foreach (FriendsOfRedaxo\MultiNewsletter\Group::getAll() as $group) {
                                $options_groups[$group->id] = $group->name;
                            }
                            \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_settings_multinewsletter_group', 'settings[multinewsletter_group][]', $options_groups, array_map('intval', rex_config::get('d2u_courses', 'multinewsletter_group')), 3, true, false); /** @phpstan-ignore-line */
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
					<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="save"><?= rex_i18n::msg('form_save') ?></button>
				</div>
			</div>
		</footer>
	</div>
</form>
<?php
    echo \TobiasKrais\D2UHelper\BackendHelper::getCSS();
    echo \TobiasKrais\D2UHelper\BackendHelper::getJS();
