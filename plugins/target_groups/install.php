<?php
$sql = rex_sql::factory();
// START install database
$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_target_groups` (
	`target_group_id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`picture` varchar(255) collate utf8mb4_unicode_ci default NULL,
	`priority` int(10) default NULL,
	`updatedate` int(11) default NULL,
	PRIMARY KEY (`target_group_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");

$sql->setQuery("CREATE TABLE IF NOT EXISTS `". rex::getTablePrefix() ."d2u_courses_2_target_groups` (
	`course_id` int(10) NOT NULL,
	`target_group_id` int(10) NOT NULL,
	PRIMARY KEY (`course_id`, `target_group_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;");
// END install database

// START create views for url addon
// Online target groups (changes need to be done here, pages/settings.php and update.php)
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
// Online target groups childs (separator is 00000 - ugly, but only digits are allowed) (changes need to be done here, pages/settings.php and update.php)
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
// END create views for url addon

// Insert url schemes
if(\rex_addon::get('url')->isAvailable()) {
	// Target groups
	$sql->setQuery("SELECT * FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups'");
	if($sql->getRows() == 0) {
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". rex_config::get('d2u_courses','article_id_target_groups', rex_article::getSiteStartArticleId()) .", ". rex_clang::getStartId() .", '', '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups', '{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_id\":\"target_group_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_url_param_key\":\"target_group_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_sitemap_frequency\":\"weekly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_sitemap_priority\":\"0.7\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_relation_field\":\"\"}', '', '[]', 'before', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer');");
	}
	// Target group childs
	$sql->setQuery("SELECT * FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs'");
	if($sql->getRows() == 0) {
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". rex_config::get('d2u_courses','article_id_target_groups', rex_article::getSiteStartArticleId()) .", ". rex_clang::getStartId() .", '', '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs', '{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_id\":\"target_group_child_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_url_param_key\":\"target_group_child_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_sitemap_frequency\":\"weekly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_sitemap_priority\":\"0.5\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_relation_field\":\"target_group_id\"}', '1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups', '{\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_field_1\":\"name\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_field_2\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_field_3\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_id\":\"target_group_id\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_clang_id\":\"\"}', 'before', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer', UNIX_TIMESTAMP(), 'd2u_courses_addon_installer');");
	}
	UrlGenerator::generatePathFile([]);
}

// START default settings
if (rex_config::get('d2u_courses', 'article_id_target_groups', 0)) {
    rex_config::set('d2u_courses', 'article_id_target_groups', rex_article::getSiteStartArticleId());
}
// END default settings