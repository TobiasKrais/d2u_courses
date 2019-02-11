<?php
$sql = rex_sql::factory();
// START install database
$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_location_categories` (
	`location_category_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`picture` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`zoom_level` int(10) default NULL,
	`updatedate` int(11) default NULL,
	PRIMARY KEY (`location_category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");

$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_locations` (
	`location_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8mb4_unicode_ci default NULL,
    `latitude` decimal(14,10),
    `longitude` decimal(14,10),
	`street` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`zip_code` varchar(10) collate utf8mb4_unicode_ci default NULL,
	`city` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`picture` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`site_plan` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`location_category_id` int(10) default NULL,
	`redaxo_users` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`updatedate` int(11) default NULL,
	PRIMARY KEY (`location_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");

// Alter machine table
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_courses LIKE 'location_id';");
if($sql->getRows() == 0) {
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_courses "
		. "ADD location_id INT(10) NULL DEFAULT NULL;");
}
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_courses LIKE 'room';");
if($sql->getRows() == 0) {
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_courses "
		. "ADD `room` varchar(255) collate utf8mb4_unicode_ci default NULL;");
}
// END install database

// START create views for url addon
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
if(\rex_addon::get('url')->isAvailable()) {
	// Location categories
	$sql->setQuery("SELECT * FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories'");
	if($sql->getRows() == 0) {
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". rex_config::get('d2u_courses','article_id_locations', rex_article::getSiteStartArticleId()) .", ". rex_clang::getStartId() .", '', '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories', '{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_id\":\"location_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_url_param_key\":\"location_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_sitemap_frequency\":\"monthly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_sitemap_priority\":\"0.1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_relation_field\":\"\"}', '', '[]', 'before', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer');");
	}
	// Locations
	$sql->setQuery("SELECT * FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations'");
	if($sql->getRows() == 0) {
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". rex_config::get('d2u_courses','article_id_locations', rex_article::getSiteStartArticleId()) .", ". rex_clang::getStartId() .", '', '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations', '{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_id\":\"location_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_url_param_key\":\"location_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_sitemap_frequency\":\"monthly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_sitemap_priority\":\"0.3\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_sitemap_lastmod\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_relation_field\":\"location_category_id\"}', '1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories', '{\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_field_1\":\"name\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_field_2\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_field_3\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_id\":\"location_category_id\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_clang_id\":\"\"}', 'before', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer');");
	}
	UrlGenerator::generatePathFile([]);
}

// START default settings
if (rex_config::get('d2u_courses', 'article_id_locations', 0)) {
    rex_config::set('d2u_courses', 'article_id_locations', rex_article::getSiteStartArticleId());
}
// END default settings