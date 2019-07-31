<?php
// 3.0.1 Database update
$sql = rex_sql::factory();
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_categories LIKE 'priority';");
if($sql->getRows() == 0) {
	$sql->setQuery("ALTER TABLE `". \rex::getTablePrefix() ."d2u_courses_categories` ADD `priority` INT(10) NULL DEFAULT 0 AFTER `parent_category_id`;");
	$sql->setQuery("SELECT category_id FROM `". \rex::getTablePrefix() ."d2u_courses_categories` ORDER BY name;");
	$update_sql = rex_sql::factory();
	for($i = 1; $i <= $sql->getRows(); $i++) {
		$update_sql->setQuery("UPDATE `". \rex::getTablePrefix() ."d2u_courses_categories` SET priority = ". $i ." WHERE category_id = ". $sql->getValue('category_id') .";");
		$sql->next();
	}
}

// Update database to 3.0.5
$sql->setQuery("ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$sql->setQuery("ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$sql->setQuery("ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_2_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
if (rex_string::versionCompare($this->getVersion(), '3.0.5', '<')) {
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_categories ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;");
	$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_categories SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_categories DROP updatedate;");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_categories CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;");

	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_courses ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;");
	$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_courses SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_courses DROP updatedate;");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_courses CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;");
}

// 3.0.1 Config Update
if(!rex_config::has('d2u_courses', 'payment_options')) {
	rex_config::set('d2u_courses', 'payment_options', ["bank_transfer", "cash", "direct_debit"]);
}

// START create views for url addon
// Online categories (changes need to be done in install.php, update.php and pages/settings.php)
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
// Online courses (changes need to be done in install.php, update.php and pages/settings.php)
$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_courses AS
	SELECT course_id, courses.name, courses.name AS seo_title, teaser, courses.picture, courses.updatedate, courses.category_id, categories.parent_category_id
	FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses
	LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories
		ON courses.category_id = categories.category_id
	WHERE courses.online_status = "online"
		AND (date_start = "" OR date_start > CURDATE());');
// END create views for url addon

// Insert url schemes
if(\rex_addon::get('url')->isAvailable()) {
	$clang_id = count(rex_clang::getAllIds()) == 1 ? rex_clang::getStartId() : 0;
	$article_id = rex_config::get('d2u_courses', 'article_id_courses', 0) > 0 ? rex_config::get('d2u_courses', 'article_id_courses') : rex_article::getSiteStartArticleId(); 
	if(rex_string::versionCompare(\rex_addon::get('url')->getVersion(), '1.5', '>=')) {
		// Insert url schemes Version 2.x
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'course_id';");
		$sql->setQuery("INSERT INTO ". \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('course_id', "
			. $article_id .", "
			. $clang_id .", "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_courses', "
			. "'{\"column_id\":\"course_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"course_id\",\"column_segment_part_2_separator\":\"-\",\"column_segment_part_2\":\"name\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"parent_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"category_id\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"teaser\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"always\",\"sitemap_priority\":\"1.0\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'relation_2_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'', '[]', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'courses_category_id';");
		$sql->setQuery("INSERT INTO ". \rex::getTablePrefix() ."url_generator_profile (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('courses_category_id', "
			. $article_id .", "
			. $clang_id .", "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"parent_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"weekly\",\"sitemap_priority\":\"0.7\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'relation_1_xxx_1_xxx_". rex::getTablePrefix() ."d2u_courses_categories', "
			. "'{\"column_id\":\"category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'', '[]', '', '[]', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");
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

	\d2u_addon_backend_helper::generateUrlCache();
}

// Update modules
if(class_exists('D2UModuleManager')) {
	$modules = [];
		$modules[] = new D2UModule("26-1",
			"D2U Veranstaltungen - Ausgabe Veranstaltungen",
			6);
		$modules[] = new D2UModule("26-2",
			"D2U Veranstaltungen - Warenkorb",
			4);
	$d2u_module_manager = new D2UModuleManager($modules);
	$d2u_module_manager->autoupdate();
}

// Update translations
if(!class_exists('d2u_courses_lang_helper')) {
	// Load class in case addon is deactivated
	require_once 'lib/d2u_courses_lang_helper.php';
}
d2u_courses_lang_helper::factory()->install();