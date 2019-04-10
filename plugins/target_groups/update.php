<?php
$sql = rex_sql::factory();

// 3.0.4 Database update
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_target_groups LIKE 'priority';");
if($sql->getRows() == 0) {
	$sql->setQuery("ALTER TABLE `". \rex::getTablePrefix() ."d2u_courses_target_groups` ADD `priority` INT(10) NULL DEFAULT 0 AFTER `picture`;");
	$sql->setQuery("SELECT target_group_id FROM `". \rex::getTablePrefix() ."d2u_courses_target_groups` ORDER BY name;");
	$update_sql = rex_sql::factory();
	for($i = 1; $i <= $sql->getRows(); $i++) {
		$update_sql->setQuery("UPDATE `". \rex::getTablePrefix() ."d2u_courses_target_groups` SET priority = ". $i ." WHERE target_group_id = ". $sql->getValue('target_group_id') .";");
		$sql->next();
	}
}

// 3.0.3 / 3.0.4 Database update
if (rex_string::versionCompare($this->getVersion(), '3.0.4', '<')) {
	$config_show_time = rex_config::get('d2u_courses', 'show_time', 'day_one_start');
	// Track changes of WHERE statement in d2u_courses_frontend_helper::getShowTimeWhere()
	$where = 'date_start = "" OR date_start > CURDATE()';
	if($config_show_time == 'day_one_end') {
		$where = 'date_start = "" OR date_start >= CURDATE()';				
	}
	else if($config_show_time == 'day_x_start') {
		$where = 'date_start = "" OR date_start >= CURDATE() OR date_end > CURDATE()';				
	}
	else if($config_show_time == 'day_x_end') {
		$where = 'date_start = "" OR date_start >= CURDATE() OR date_end >= CURDATE()';				
	}

	// Online target groups childs (separator is 00000 - ugly, but only digits are allowed)
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
			AND ('. $where .')
			AND courses.updatedate = (
				SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS categories_max
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON categories_max.course_id = courses_max.course_id
				WHERE categories.category_id = categories_max.category_id AND courses_max.online_status = "online" AND ('. $where .')
			)
		GROUP BY target_group_id, category_id, target_group_child_id, name, seo_title, picture, updatedate;');
	// Online target groups (changes need to be done here, pages/settings.php and update.php)
	$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_target_groups AS
		SELECT target.target_group_id, target.name, target.name AS seo_title, target.picture, target.priority, courses.updatedate
		FROM '. rex::getTablePrefix() .'d2u_courses_target_groups AS target
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_target_groups AS c2t
			ON target.target_group_id = c2t.target_group_id
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
			ON c2t.course_id = courses.course_id
		WHERE courses.online_status = "online"
			AND ('. $where .')
			AND courses.updatedate = (
				SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_target_groups AS targets_max
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON targets_max.course_id = courses_max.course_id
				WHERE target.target_group_id = targets_max.target_group_id AND courses_max.online_status = "online" AND ('. $where .')
			)
		GROUP BY target_group_id, name, seo_title, picture, priority, updatedate;');
}

// Update database to 3.0.5
$sql->setQuery("ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_target_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$sql->setQuery("ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_2_target_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

// Insert url schemes
if(\rex_addon::get('url')->isAvailable()) {
	$clang_id = count(rex_clang::getAllIds()) == 1 ? rex_clang::getStartId() : 0;
	$article_id = rex_config::get('d2u_courses', 'article_id_target_groups', 0) > 0 ? rex_config::get('d2u_courses', 'article_id_target_groups') : rex_article::getSiteStartArticleId(); 
	if(rex_string::versionCompare(\rex_addon::get('url')->getVersion(), '1.5', '>=')) {
		// Insert url schemes Version 2.x
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'target_group_child_id';");
		$sql->setQuery("INSERT INTO ". \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('target_group_child_id', "
			. $article_id .", "
			. $clang_id .", "
			. "'1_xxx_rex_d2u_courses_url_target_group_childs', "
			. "'{\"column_id\":\"target_group_child_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"target_group_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"daily\",\"sitemap_priority\":\"0.7\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'relation_1_xxx_1_xxx_rex_d2u_courses_target_groups', "
			. "'{\"column_id\":\"target_group_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'', '[]', '', '[]', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'target_group_id';");
		$sql->setQuery("INSERT INTO ". \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('target_group_id', "
			. $article_id .", "
			. $clang_id .", "
			. "'1_xxx_rex_d2u_courses_url_target_groups', "
			. "'{\"column_id\":\"target_group_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"weekly\",\"sitemap_priority\":\"0.7\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'', '[]', '', '[]', '', '[]', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");
	}
	else {
		// Insert url schemes Version 1.x
		// Target groups
		$sql->setQuery("DELETE FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups';");
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". $article_id .", "
			. rex_clang::getStartId() .", "
			. "'', "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups', "
			. "'{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_id\":\"target_group_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_url_param_key\":\"target_group_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_sitemap_frequency\":\"weekly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_sitemap_priority\":\"0.7\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_groups_relation_field\":\"\"}', "
			. "'', '[]', 'before', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."');");
		// Target group childs
		$sql->setQuery("DELETE FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs';");
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". $article_id .", "
			. rex_clang::getStartId() .", "
			. "'', "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs', "
			. "'{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_id\":\"target_group_child_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_url_param_key\":\"target_group_child_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_sitemap_frequency\":\"weekly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_sitemap_priority\":\"0.5\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_target_group_childs_relation_field\":\"target_group_id\"}', "
			. "'1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups', "
			. "'{\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_field_1\":\"name\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_field_2\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_field_3\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_id\":\"target_group_id\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_target_groups_clang_id\":\"\"}', "
			. "'before', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."');");
	}

	d2u_addon_backend_helper::generateUrlCache();
}