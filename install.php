<?php

// START install database
\rex_sql_table::get(\rex::getTable('d2u_courses_categories'))
    ->ensureColumn(new rex_sql_column('category_id', 'INT(11) unsigned', false, null, 'auto_increment'))
    ->setPrimaryKey('category_id')
    ->ensureColumn(new \rex_sql_column('name', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('description', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('color', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('picture', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('parent_category_id', 'INT(11)', true))
    ->ensureColumn(new \rex_sql_column('priority', 'INT(11)', true))
    ->ensureColumn(new \rex_sql_column('updatedate', 'DATETIME'))
    ->ensure();

\rex_sql_table::get(\rex::getTable('d2u_courses_courses'))
    ->ensureColumn(new rex_sql_column('course_id', 'INT(11) unsigned', false, null, 'auto_increment'))
    ->setPrimaryKey('course_id')
    ->ensureColumn(new \rex_sql_column('name', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('teaser', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('description', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('details_course', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('details_deadline', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('details_age', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('picture', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('price', 'DECIMAL(7,2)', true))
    ->ensureColumn(new \rex_sql_column('price_discount', 'DECIMAL(7,2)', true))
    ->ensureColumn(new \rex_sql_column('price_salery_level', 'TINYINT(1)', false, '0'))
    ->ensureColumn(new \rex_sql_column('price_salery_level_details', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('date_start', 'VARCHAR(10)', true))
    ->ensureColumn(new \rex_sql_column('date_end', 'VARCHAR(10)', true))
    ->ensureColumn(new \rex_sql_column('time', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('category_id', 'INT(11)', true))
    ->ensureColumn(new \rex_sql_column('participants_number', 'INT(6)', true))
    ->ensureColumn(new \rex_sql_column('participants_max', 'INT(6)', true))
    ->ensureColumn(new \rex_sql_column('participants_min', 'INT(6)', true))
    ->ensureColumn(new \rex_sql_column('participants_wait_list', 'INT(6)', true))
    ->ensureColumn(new \rex_sql_column('registration_possible', 'VARCHAR(10)', true))
    ->ensureColumn(new \rex_sql_column('online_status', 'VARCHAR(7)', true))
    ->ensureColumn(new \rex_sql_column('url_external', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('redaxo_article', 'INT(11)', true))
    ->ensureColumn(new \rex_sql_column('google_type', 'VARCHAR(10)', true))
    ->ensureColumn(new \rex_sql_column('instructor', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('course_number', 'VARCHAR(50)', true))
    ->ensureColumn(new \rex_sql_column('downloads', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('updatedate', 'DATETIME'))
    ->ensure();

\rex_sql_table::get(\rex::getTable('d2u_courses_2_categories'))
    ->ensureColumn(new rex_sql_column('course_id', 'INT(11)'))
    ->ensureColumn(new rex_sql_column('category_id', 'INT(11)'))
    ->setPrimaryKey(['course_id', 'category_id'])
    ->ensure();
// END install database

// START create views for url addon
// Online categories (changes need to be done in install.php and pages/settings.php)
$sql = rex_sql::factory();
$showTimeWhere = 'date_start = "" OR date_start > CURDATE()';
if (class_exists('d2u_courses_frontend_helper') && method_exists('d2u_courses_frontend_helper', 'getShowTimeWhere')) { /** @phpstan-ignore-line */
    $showTimeWhere = d2u_courses_frontend_helper::getShowTimeWhere();
}
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
// END create views for url addon

// Insert url schemes
if (\rex_addon::get('url')->isAvailable()) {
    $clang_id = 1 === count(rex_clang::getAllIds()) ? rex_clang::getStartId() : 0;
    $article_id = rex_config::get('d2u_courses', 'article_id_courses', 0) > 0 ? rex_config::get('d2u_courses', 'article_id_courses') : rex_article::getSiteStartArticleId();

    // Insert url schemes Version 2.x
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'courses_category_id';");
    $sql->setQuery('INSERT INTO '. \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `ep_pre_save_called`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
		('courses_category_id', "
        . $article_id .', '
        . $clang_id .', '
        . '0, '
        . "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories', "
        . "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"great_grand_parent_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"grand_parent_category_id\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"parent_category_id\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"weekly\",\"sitemap_priority\":\"0.7\",\"column_sitemap_lastmod\":\"updatedate\"}', "
        . "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
        . "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
        . "'relation_2_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
        . "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
        . "'relation_3_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
        . "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
        . "CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."', CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."');");

    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'course_id';");
    $sql->setQuery('INSERT INTO '. \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `ep_pre_save_called`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
		('course_id', "
        . $article_id .', '
        . $clang_id .', '
        . '0, '
        . "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses', "
        . "'{\"column_id\":\"course_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"course_id\",\"column_segment_part_2_separator\":\"-\",\"column_segment_part_2\":\"name\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"grand_parent_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"parent_category_id\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"category_id\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"teaser\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"always\",\"sitemap_priority\":\"1.0\",\"column_sitemap_lastmod\":\"updatedate\"}', "
        . "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories', "
        . "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"parent_name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"name\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
        . "'relation_2_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
        . "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
        . "'relation_3_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
        . "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}',"
        . "CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."', CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."');");
    rex_delete_cache();
}

// START default settings
if (!rex_config::has('d2u_courses', 'article_id_courses')) {
    rex_config::set('d2u_courses', 'article_id_courses', rex_article::getSiteStartArticleId());
}
// END default settings

// Update modules
if (class_exists('D2UModuleManager')) {
    $modules = [];
    $modules[] = new D2UModule('26-1',
        'D2U Veranstaltungen - Ausgabe Veranstaltungen',
        14);
    $modules[] = new D2UModule('26-2',
        'D2U Veranstaltungen - Warenkorb',
        8);
    $modules[] = new D2UModule('26-3',
        'D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen',
        4);
    $d2u_module_manager = new D2UModuleManager($modules, '', 'd2u_courses');
    $d2u_module_manager->autoupdate();
}

// Update translations
if (!class_exists('d2u_courses_lang_helper')) {
    // Load class in case addon is deactivated
    require_once 'lib/d2u_courses_lang_helper.php';
}
d2u_courses_lang_helper::factory()->install();
