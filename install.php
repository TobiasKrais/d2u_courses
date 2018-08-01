<?php
$sql = rex_sql::factory();
// START install database
$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_categories` (
	`category_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8_general_ci default NULL,
	`description` text collate utf8_general_ci default NULL,
	`color` varchar(255) collate utf8_general_ci default NULL,
	`picture` varchar(255) collate utf8_general_ci default NULL,
	`parent_category_id` int(10) default NULL,
	`updatedate` int(11) default NULL,
	PRIMARY KEY (`category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;");

$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_courses` (
	`course_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8_general_ci default NULL,
	`teaser` text collate utf8_general_ci default NULL,
	`description` text collate utf8_general_ci default NULL,
	`details_course` text collate utf8_general_ci default NULL,
	`details_deadline` text collate utf8_general_ci default NULL,
	`details_age` varchar(255) collate utf8_general_ci default NULL,
	`picture` varchar(255) collate utf8_general_ci default NULL,
	`price` decimal(5,2) default NULL,
	`price_discount` decimal(5,2) default NULL,
	`date_start` varchar(10) collate utf8_general_ci default NULL,
	`date_end` varchar(10) collate utf8_general_ci default NULL,
	`time` varchar(255) collate utf8_general_ci default NULL,
	`category_id` int(10) default NULL,
	`participants_number` int(10) default NULL,
	`participants_max` int(10) default NULL,
	`participants_min` int(10) default NULL,
	`participants_wait_list` int(10) default NULL,
	`registration_possible` varchar(10) collate utf8_general_ci default NULL,
	`online_status` varchar(7) collate utf8_general_ci default NULL,
	`url_external` varchar(255) collate utf8_general_ci default NULL,
	`redaxo_article` int(10) NULL default NULL,
	`instructor` varchar(255) collate utf8_general_ci default NULL,
	`course_number` varchar(50) collate utf8_general_ci default NULL,
	`downloads` text collate utf8_general_ci default NULL,
	`updatedate` int(11) default NULL,
  PRIMARY KEY (`course_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;");

$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_2_categories` (
	`course_id` int(10) NOT NULL,
	`category_id` int(10) NOT NULL,
	PRIMARY KEY (`course_id`, `category_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1;");
// END install database

// START create views for url addon
// Online categories
$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_categories AS
	SELECT categories.category_id, CONCAT_WS(" - ", parents.name, categories.name) AS name, CONCAT_WS(" - ", parents.name, categories.name) AS seo_title, categories.picture, courses.updatedate, categories.parent_category_id
	FROM '. rex::getTablePrefix() .'d2u_courses_categories AS categories
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c
		ON categories.category_id = c2c.category_id
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
		ON c2c.course_id = courses.course_id
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parents
		ON categories.parent_category_id = parents.category_id
	WHERE courses.online_status = "online"
		AND (date_start = "" OR date_start > CURDATE())
		AND courses.updatedate = (
			SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c_max
			LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2c_max.course_id = courses_max.course_id
			WHERE categories.category_id = c2c_max.category_id AND courses_max.online_status = "online" AND (date_start = "" OR date_start > CURDATE())
		)
	GROUP BY category_id, name, seo_title, picture, updatedate, parent_category_id
	UNION
	SELECT parents.category_id, parents.name, parents.name AS seo_title, parents.picture, courses.updatedate, -1 AS parent_category_id
	FROM '. rex::getTablePrefix() .'d2u_courses_categories AS categories
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c
		ON categories.category_id = c2c.category_id
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
		ON c2c.course_id = courses.course_id
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS parents
		ON categories.parent_category_id = parents.category_id
	WHERE parents.category_id > 0
		AND courses.online_status = "online"
		AND (date_start = "" OR date_start > CURDATE())
		AND courses.updatedate = (
			SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c_max
			LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2c_max.course_id = courses_max.course_id
			LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories_max ON c2c_max.category_id = categories_max.category_id
			WHERE categories.parent_category_id = categories_max.parent_category_id AND courses_max.online_status = "online" AND (date_start = "" OR date_start > CURDATE())
		)
	GROUP BY category_id, name, seo_title, picture, updatedate, parent_category_id;');
// Online courses
$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_courses AS
	SELECT course_id, name, name AS seo_title, teaser, picture, updatedate, category_id
	FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses
	WHERE courses.online_status = "online"
		AND (date_start = "" OR date_start > CURDATE());');
// END create views for url addon

// Insert url schemes
if(\rex_addon::get('url')->isAvailable()) {
	// Courses
	$sql->setQuery("SELECT * FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses'");
	$clang_id = count(rex_clang::getAllIds()) == 1 ? rex_clang::getStartId() : 0;
	if($sql->getRows() == 0) {
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". rex_config::get('d2u_courses', 'article_id_courses', rex_article::getSiteStartArticleId()) .", ". $clang_id .", '', '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses', '{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_id\":\"course_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_url_param_key\":\"course_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_seo_description\":\"teaser\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_sitemap_frequency\":\"always\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_sitemap_priority\":\"1.0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses_relation_field\":\"category_id\"}', '1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories', '{\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_field_1\":\"name\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_field_2\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_field_3\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_id\":\"category_id\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_url_categories_clang_id\":\"\"}', 'before', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer');");
	}
	// Categories
	$sql->setQuery("SELECT * FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories'");
	if($sql->getRows() == 0) {
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". rex_config::get('d2u_courses','article_id_courses', rex_article::getSiteStartArticleId()) .", ". $clang_id .", '', '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories', '{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_id\":\"category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_url_param_key\":\"courses_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_sitemap_frequency\":\"weekly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_sitemap_priority\":\"0.7\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_sitemap_lastmod\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories_relation_field\":\"\"}', '', '[]', 'before', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer');");
	}
	UrlGenerator::generatePathFile([]);
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
if(class_exists(d2u_courses_lang_helper)) {
	d2u_courses_lang_helper::factory()->install();
}