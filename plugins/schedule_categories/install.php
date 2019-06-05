<?php
$sql = rex_sql::factory();
// START install database
$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_schedule_categories` (
	`schedule_category_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`picture` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`priority` int(10) default NULL,
	`parent_schedule_category_id` int(10) default NULL,
	`updatedate` DATETIME default NULL,
	PRIMARY KEY (`schedule_category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");

$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_2_schedule_categories` (
	`course_id` int(10) NOT NULL,
	`schedule_category_id` int(10) NOT NULL,
	PRIMARY KEY (`course_id`, `schedule_category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");
// END install database

// START create views for url addon
// Online schedule categories (changes need to be done here, addon pages/settings.php and update.php)
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
// END create views for url addon

// Insert url schemes
if(\rex_addon::get('url')->isAvailable()) {
	$clang_id = count(rex_clang::getAllIds()) == 1 ? rex_clang::getStartId() : 0;
	$article_id = rex_config::get('d2u_courses', 'article_id_schedule_categories', 0) > 0 ? rex_config::get('d2u_courses', 'article_id_schedule_categories') : rex_article::getSiteStartArticleId(); 
	if(rex_string::versionCompare(\rex_addon::get('url')->getVersion(), '1.5', '>=')) {
		// Insert url schemes Version 2.x
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'schedule_category_id';");
		$sql->setQuery("INSERT INTO ". \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('schedule_category_id', "
			. $article_id .", "
			. $clang_id .", "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories', "
			. "'{\"column_id\":\"schedule_category_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"parent_schedule_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"weekly\",\"sitemap_priority\":\"0.5\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_schedule_categories', "
			. "'{\"column_id\":\"schedule_category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'', '[]', '', '[]', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");
	}
	else {
		// Insert url schemes Version 1.x
		// Schedule categories
		$sql->setQuery("DELETE FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories';");
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". $article_id .", "
			. rex_clang::getStartId() .", "
			. "'', "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories', "
			. "'{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_id\":\"schedule_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_url_param_key\":\"schedule_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_sitemap_frequency\":\"weekly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_sitemap_priority\":\"0.5\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_relation_field\":\"parent_schedule_category_id\"}', "
			. "'1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories', "
			. "'{\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_field_1\":\"name\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_field_2\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_field_3\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_id\":\"schedule_category_id\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_clang_id\":\"\"}', "
			. "'before', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."');");
	}

	\d2u_addon_backend_helper::generateUrlCache();
}

// START default settings
if (rex_config::get('d2u_courses', 'article_id_schedule_categories', 0)) {
    rex_config::set('d2u_courses', 'article_id_schedule_categories', rex_article::getSiteStartArticleId());
}
// END default settings