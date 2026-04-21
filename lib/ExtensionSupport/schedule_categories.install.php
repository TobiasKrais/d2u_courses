<?php

\rex_sql_table::get(\rex::getTable('d2u_courses_schedule_categories'))
    ->ensureColumn(new rex_sql_column('schedule_category_id', 'INT(11) unsigned', false, null, 'auto_increment'))
    ->setPrimaryKey('schedule_category_id')
    ->ensureColumn(new \rex_sql_column('name', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('picture', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('priority', 'INT(10)', true))
    ->ensureColumn(new \rex_sql_column('parent_schedule_category_id', 'INT(10)', true))
    ->ensureColumn(new \rex_sql_column('updatedate', 'DATETIME'))
    ->ensure();

\rex_sql_table::get(\rex::getTable('d2u_courses_2_schedule_categories'))
    ->ensureColumn(new rex_sql_column('course_id', 'INT(11)'))
    ->ensureColumn(new rex_sql_column('schedule_category_id', 'INT(11)'))
    ->setPrimaryKey(['course_id', 'schedule_category_id'])
    ->ensure();

// START create views for url addon
$sql = rex_sql::factory();
// Online schedule categories (changes need to be done here, addon pages/settings.php)
$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_schedule_categories AS
	SELECT schedules.schedule_category_id, schedules.name, schedules.name AS seo_title, schedules.picture, schedules.priority, courses.updatedate, schedules.parent_schedule_category_id
	FROM '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s
		ON schedules.schedule_category_id = c2s.schedule_category_id
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
		ON c2s.course_id = courses.course_id
	WHERE courses.online_status = "online"
		AND ('. TobiasKrais\D2UCourses\FrontendHelper::getShowTimeWhere() .')
		AND courses.updatedate = (
			SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS schedules_max
			LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON schedules_max.course_id = courses_max.course_id
			WHERE schedules.schedule_category_id = schedules_max.schedule_category_id AND courses_max.online_status = "online" AND ('. TobiasKrais\D2UCourses\FrontendHelper::getShowTimeWhere() .')
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
		AND ('. TobiasKrais\D2UCourses\FrontendHelper::getShowTimeWhere() .')
		AND courses.updatedate = (
			SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s_max
			LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2s_max.course_id = courses_max.course_id
			LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules_max ON c2s_max.schedule_category_id = c2s_max.schedule_category_id
			WHERE schedules.parent_schedule_category_id = schedules_max.schedule_category_id AND courses_max.online_status = "online" AND ('. TobiasKrais\D2UCourses\FrontendHelper::getShowTimeWhere() .')
		)
	GROUP BY schedule_category_id, name, seo_title, picture, priority, updatedate, parent_schedule_category_id;');
// END create views for url addon

// Insert url schemes
if (\rex_addon::get('url')->isAvailable()) {
    $clang_id = 1 === count(rex_clang::getAllIds()) ? rex_clang::getStartId() : 0;
    $article_id = rex_config::get('d2u_courses', 'article_id_schedule_categories', 0) > 0 ? rex_config::get('d2u_courses', 'article_id_schedule_categories') : rex_article::getSiteStartArticleId();

    // Insert url schemes Version 2.x
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'schedule_category_id';");
    $sql->setQuery('INSERT INTO '. \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
		('schedule_category_id', "
        . $article_id .', '
        . $clang_id .', '
        . "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories', "
        . "'{\"column_id\":\"schedule_category_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"parent_schedule_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"weekly\",\"sitemap_priority\":\"0.5\",\"column_sitemap_lastmod\":\"updatedate\"}', "
        . "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_schedule_categories', "
        . "'{\"column_id\":\"schedule_category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
        . "'', '[]', '', '[]', CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."', CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."');");

    rex_delete_cache();
}

// START default settings
if (0 === (int) rex_config::get('d2u_courses', 'article_id_schedule_categories', 0)) {
    rex_config::set('d2u_courses', 'article_id_schedule_categories', rex_article::getSiteStartArticleId());
}
// END default settings
