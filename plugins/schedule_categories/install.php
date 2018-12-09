<?php
$sql = rex_sql::factory();
// START install database
$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_schedule_categories` (
	`schedule_category_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8_general_ci default NULL,
	`picture` varchar(255) collate utf8_general_ci default NULL,
	`parent_schedule_category_id` int(10) default NULL,
	`updatedate` int(11) default NULL,
	PRIMARY KEY (`schedule_category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;");

$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_2_schedule_categories` (
	`course_id` int(10) NOT NULL,
	`schedule_category_id` int(10) NOT NULL,
	PRIMARY KEY (`course_id`, `schedule_category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;");
// END install database

// START create views for url addon
// Online schedule categories (changes need to be done here and addon pages/settings.php)
$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_schedule_categories AS
	SELECT schedules.schedule_category_id, schedules.name, schedules.name AS seo_title, schedules.picture, courses.updatedate, schedules.parent_schedule_category_id
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
	GROUP BY schedule_category_id, name, seo_title, picture, updatedate, parent_schedule_category_id
	UNION
	SELECT parents.schedule_category_id, parents.name, parents.name AS seo_title, parents.picture, courses.updatedate, parents.parent_schedule_category_id
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
	GROUP BY schedule_category_id, name, seo_title, picture, updatedate, parent_schedule_category_id;');
// END create views for url addon

// Insert url schemes
if(\rex_addon::get('url')->isAvailable()) {
	// Schedule categories
	$sql->setQuery("SELECT * FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories'");
	if($sql->getRows() == 0) {
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". rex_config::get('d2u_courses','article_id_schedule_categories', rex_article::getSiteStartArticleId()) .", ". rex_clang::getStartId() .", '', '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories', '{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_id\":\"schedule_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_url_param_key\":\"schedule_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_sitemap_frequency\":\"weekly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_sitemap_priority\":\"0.5\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_schedule_categories_relation_field\":\"parent_schedule_category_id\"}', '1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories', '{\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_field_1\":\"name\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_field_2\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_field_3\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_id\":\"schedule_category_id\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_schedule_categories_clang_id\":\"\"}', 'before', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer');");
	}
	UrlGenerator::generatePathFile([]);
}

// START default settings
if (rex_config::get('d2u_courses', 'article_id_schedule_categories', 0)) {
    rex_config::set('d2u_courses', 'article_id_schedule_categories', rex_article::getSiteStartArticleId());
}
// END default settings