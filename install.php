<?php
$sql = rex_sql::factory();
// START install database
$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_categories` (
	`category_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`description` text collate utf8mb4_unicode_ci default NULL,
	`color` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`picture` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`parent_category_id` int(10) default NULL,
	`priority` int(10) default NULL,
	`updatedate` DATETIME default NULL,
	PRIMARY KEY (`category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");

$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_courses` (
	`course_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`teaser` text collate utf8mb4_unicode_ci default NULL,
	`description` text collate utf8mb4_unicode_ci default NULL,
	`details_course` text collate utf8mb4_unicode_ci default NULL,
	`details_deadline` text collate utf8mb4_unicode_ci default NULL,
	`details_age` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`picture` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`price` decimal(7,2) default NULL,
	`price_discount` decimal(7,2) default NULL,
	`date_start` varchar(10) collate utf8mb4_unicode_ci default NULL,
	`date_end` varchar(10) collate utf8mb4_unicode_ci default NULL,
	`time` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`category_id` int(10) default NULL,
	`participants_number` int(10) default NULL,
	`participants_max` int(10) default NULL,
	`participants_min` int(10) default NULL,
	`participants_wait_list` int(10) default NULL,
	`registration_possible` varchar(10) collate utf8mb4_unicode_ci default NULL,
	`online_status` varchar(7) collate utf8mb4_unicode_ci default NULL,
	`url_external` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`redaxo_article` int(10) NULL default NULL,
	`google_type` varchar(10) collate utf8mb4_unicode_ci default NULL,
	`instructor` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`course_number` varchar(50) collate utf8mb4_unicode_ci default NULL,
	`downloads` text collate utf8mb4_unicode_ci default NULL,
	`updatedate` DATETIME default NULL,
  PRIMARY KEY (`course_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");

$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_2_categories` (
	`course_id` int(10) NOT NULL,
	`category_id` int(10) NOT NULL,
	PRIMARY KEY (`course_id`, `category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");
// END install database

// START create views for url addon
// Online categories (changes need to be done in install.php, update.php and pages/settings.php)
$showTimeWhere = 'date_start = "" OR date_start > CURDATE()';
if(class_exists('d2u_courses_frontend_helper') && method_exists('d2u_courses_frontend_helper', 'getShowTimeWhere')) {
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
// END create views for url addon

// Insert url schemes
if(\rex_addon::get('url')->isAvailable()) {
	$clang_id = count(rex_clang::getAllIds()) == 1 ? rex_clang::getStartId() : 0;
	$article_id = rex_config::get('d2u_courses', 'article_id_courses', 0) > 0 ? rex_config::get('d2u_courses', 'article_id_courses') : rex_article::getSiteStartArticleId(); 
	if(rex_version::compare(\rex_addon::get('url')->getVersion(), '1.5', '>=')) {
		// Insert url schemes Version 2.x
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'courses_category_id';");
		$sql->setQuery("INSERT INTO ". \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `ep_pre_save_called`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('courses_category_id', "
			. $article_id .", "
			. $clang_id .", "
			. "0, "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"great_grand_parent_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"grand_parent_category_id\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"parent_category_id\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"weekly\",\"sitemap_priority\":\"0.7\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'relation_2_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'relation_3_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");

		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'course_id';");
		$sql->setQuery("INSERT INTO ". \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `ep_pre_save_called`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('course_id', "
			. $article_id .", "
			. $clang_id .", "
			. "0, "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses', "
			. "'{\"column_id\":\"course_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"course_id\",\"column_segment_part_2_separator\":\"-\",\"column_segment_part_2\":\"name\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"grand_parent_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"parent_category_id\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"category_id\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"teaser\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"always\",\"sitemap_priority\":\"1.0\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"parent_name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"name\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'relation_2_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'relation_3_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}',"
			. "CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");
	}
	else {
		// Insert url schemes Version 1.x
		// Courses
		$sql->setQuery("DELETE FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses';");
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". $article_id .", "
			. $clang_id .", "
			. "'', "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses', "
			. "'{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_field_1\":\"course_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_field_2\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_id\":\"course_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_url_param_key\":\"course_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_seo_description\":\"teaser\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_sitemap_frequency\":\"always\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_sitemap_priority\":\"1.0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_relation_field\":\"category_id\"}', "
			. "'1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories', "
			. "'{\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_field_1\":\"name\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_field_2\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_field_3\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_id\":\"category_id\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_clang_id\":\"\"}', "
			. "'before', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."');");
		// Categories
		$sql->setQuery("DELETE FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories';");
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". $article_id .", "
			. $clang_id .", "
			. "'', "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories', "
			. "'{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_id\":\"category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_url_param_key\":\"courses_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_sitemap_frequency\":\"weekly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_sitemap_priority\":\"0.7\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_sitemap_lastmod\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_relation_field\":\"\"}', "
			. "'', '[]', 'before', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."');");
	}

	\rex_delete_cache();
}

// START default settings
if (!$this->hasConfig('article_id_courses')) {
    $this->setConfig('article_id_courses', rex_article::getSiteStartArticleId());
}
if (!$this->hasConfig('forward_single_course')) {
    $this->setConfig('forward_single_course', 'active');
}
// END default settings

// Insert frontend translations
if(class_exists('d2u_courses_lang_helper')) {
	d2u_courses_lang_helper::factory()->install();
}