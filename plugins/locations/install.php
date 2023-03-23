<?php

\rex_sql_table::get(\rex::getTable('d2u_courses_location_categories'))
    ->ensureColumn(new rex_sql_column('location_category_id', 'INT(11) unsigned', false, null, 'auto_increment'))
    ->setPrimaryKey('location_category_id')
    ->ensureColumn(new \rex_sql_column('name', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('picture', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('zoom_level', 'INT(10)', true))
    ->ensureColumn(new \rex_sql_column('updatedate', 'DATETIME'))
    ->ensure();

\rex_sql_table::get(\rex::getTable('d2u_courses_locations'))
    ->ensureColumn(new rex_sql_column('location_id', 'INT(11) unsigned', false, null, 'auto_increment'))
    ->setPrimaryKey('location_id')
    ->ensureColumn(new \rex_sql_column('name', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('latitude', 'DECIMAL(14,10)', true, '0'))
    ->ensureColumn(new \rex_sql_column('longitude', 'DECIMAL(14,10)', true, '0'))
    ->ensureColumn(new \rex_sql_column('street', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('zip_code', 'VARCHAR(10)', true))
    ->ensureColumn(new \rex_sql_column('city', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('country_code', 'VARCHAR(3)', true))
    ->ensureColumn(new \rex_sql_column('picture', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('site_plan', 'VARCHAR(255)', true))
    ->ensureColumn(new \rex_sql_column('location_category_id', 'INT(11)', true))
    ->ensureColumn(new \rex_sql_column('redaxo_users', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('updatedate', 'DATETIME'))
    ->ensure();

rex_sql_table::get(rex::getTable('d2u_courses_courses'))
    ->ensureColumn(new \rex_sql_column('location_id', 'INT(11)', true, null))
    ->ensureColumn(new \rex_sql_column('room', 'VARCHAR(255)', true, null))
    ->alter();

// START create views for url addon
$sql = rex_sql::factory();
// Online locations (changes need to be done here and addon pages/settings.php)
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
// Online location categories (changes need to be done here and addon pages/settings.php)
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

// Insert url schemes
if (\rex_addon::get('url')->isAvailable()) {
    $clang_id = 1 === count(rex_clang::getAllIds()) ? rex_clang::getStartId() : 0;
    $article_id = rex_config::get('d2u_courses', 'article_id_locations', 0) > 0 ? rex_config::get('d2u_courses', 'article_id_locations') : rex_article::getSiteStartArticleId();

    // Insert url schemes Version 2.x
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'location_id';");
    $sql->setQuery('INSERT INTO '. \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
		('location_id', "
        . $article_id .', '
        . $clang_id .', '
        . "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations', "
        . "'{\"column_id\":\"location_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"location_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"weekly\",\"sitemap_priority\":\"0.3\",\"column_sitemap_lastmod\":\"updatedate\"}', "
        . "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_location_categories', "
        . "'{\"column_id\":\"location_category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
        . "'', '[]', '', '[]', CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."', CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."');");
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'location_category_id';");
    $sql->setQuery('INSERT INTO '. \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
		('location_category_id', "
        . $article_id .', '
        . $clang_id .', '
        . "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories', "
        . "'{\"column_id\":\"location_category_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"monthly\",\"sitemap_priority\":\"0.1\",\"column_sitemap_lastmod\":\"updatedate\"}', "
        . "'', '[]', '', '[]', '', '[]', CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."', CURRENT_TIMESTAMP, '". (rex::getUser() instanceof rex_user ? rex::getUser()->getValue('login') : '') ."');");

    rex_delete_cache();
}

// START default settings
if ((int) rex_config::get('d2u_courses', 'article_id_locations', 0) === 0) {
    rex_config::set('d2u_courses', 'article_id_locations', rex_article::getSiteStartArticleId());
}
// END default settings
