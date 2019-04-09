<?php
$sql = rex_sql::factory();
// Update database to 3.0.3
$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_locations SET `latitude` = '0' WHERE `latitude` = '';");
$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations CHANGE `latitude` `latitude` decimal(14,10) NULL DEFAULT NULL");
$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_locations SET `longitude` = '0' WHERE `longitude` = '';");
$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations CHANGE `longitude` `longitude` decimal(14,10) NULL DEFAULT NULL");

// Update database to 3.0.5
$sql->setQuery("ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_location_categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$sql->setQuery("ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

if (rex_string::versionCompare($this->getVersion(), '3.0.5', '<')) {
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_location_categories ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;");
	$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_location_categories SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_location_categories DROP updatedate;");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_location_categories CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;");

	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;");
	$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_locations SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations DROP updatedate;");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;");
}

// Insert url schemes
if(\rex_addon::get('url')->isAvailable()) {
	$clang_id = count(rex_clang::getAllIds()) == 1 ? rex_clang::getStartId() : 0;
	$article_id = rex_config::get('d2u_courses', 'article_id_locations', 0) > 0 ? rex_config::get('d2u_courses', 'article_id_locations') : rex_article::getSiteStartArticleId(); 
	if(rex_string::versionCompare(\rex_addon::get('url')->getVersion(), '1.5', '>=')) {
		// Insert url schemes Version 2.x
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."rex_url_generator_profile WHERE `namespace` = 'location_id';");
		$sql->setQuery("INSERT INTO `rex_url_generator_profile` (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('location_id', "
			. $article_id .", "
			. $clang_id .", "
			. "'1_xxx_rex_d2u_courses_url_locations', "
			. "'{\"column_id\":\"location_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"location_category_id\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"weekly\",\"sitemap_priority\":\"0.3\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'relation_1_xxx_1_xxx_rex_d2u_courses_location_categories', "
			. "'{\"column_id\":\"location_category_id\",\"column_clang_id\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\"}', "
			. "'', '[]', '', '[]', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."rex_url_generator_profile WHERE `namespace` = 'location_category_id';");
		$sql->setQuery("INSERT INTO `rex_url_generator_profile` (`namespace`, `article_id`, `clang_id`, `table_name`, `table_parameters`, `relation_1_table_name`, `relation_1_table_parameters`, `relation_2_table_name`, `relation_2_table_parameters`, `relation_3_table_name`, `relation_3_table_parameters`, `createdate`, `createuser`, `updatedate`, `updateuser`) VALUES
			('location_category_id', "
			. $article_id .", "
			. $clang_id .", "
			. "'1_xxx_rex_d2u_courses_url_location_categories', "
			. "'{\"column_id\":\"location_category_id\",\"column_clang_id\":\"\",\"restriction_1_column\":\"\",\"restriction_1_comparison_operator\":\"=\",\"restriction_1_value\":\"\",\"restriction_2_logical_operator\":\"\",\"restriction_2_column\":\"\",\"restriction_2_comparison_operator\":\"=\",\"restriction_2_value\":\"\",\"restriction_3_logical_operator\":\"\",\"restriction_3_column\":\"\",\"restriction_3_comparison_operator\":\"=\",\"restriction_3_value\":\"\",\"column_segment_part_1\":\"name\",\"column_segment_part_2_separator\":\"\\/\",\"column_segment_part_2\":\"\",\"column_segment_part_3_separator\":\"\\/\",\"column_segment_part_3\":\"\",\"relation_1_column\":\"\",\"relation_1_position\":\"BEFORE\",\"relation_2_column\":\"\",\"relation_2_position\":\"BEFORE\",\"relation_3_column\":\"\",\"relation_3_position\":\"BEFORE\",\"append_user_paths\":\"\",\"append_structure_categories\":\"0\",\"column_seo_title\":\"seo_title\",\"column_seo_description\":\"\",\"column_seo_image\":\"picture\",\"sitemap_add\":\"1\",\"sitemap_frequency\":\"monthly\",\"sitemap_priority\":\"0.1\",\"column_sitemap_lastmod\":\"updatedate\"}', "
			. "'', '[]', '', '[]', '', '[]', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."', CURRENT_TIMESTAMP, '". rex::getUser()->getValue('login') ."');");
	}
	else {
		// Insert url schemes Version 1.x
		// Location categories
		$sql->setQuery("DELETE FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories';");
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". $article_id .", "
			. rex_clang::getStartId() .", "
			. "'', "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories', "
			. "'{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_id\":\"location_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_url_param_key\":\"location_category_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_sitemap_frequency\":\"monthly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_sitemap_priority\":\"0.1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_sitemap_lastmod\":\"updatedate\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_location_categories_relation_field\":\"\"}', "
			. "'', '[]', 'before', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."');");
		// Locations
		$sql->setQuery("DELETE FROM ". rex::getTablePrefix() ."url_generate WHERE `table` = '1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations';");
		$sql->setQuery("INSERT INTO `". rex::getTablePrefix() ."url_generate` (`article_id`, `clang_id`, `url`, `table`, `table_parameters`, `relation_table`, `relation_table_parameters`, `relation_insert`, `createdate`, `createuser`, `updatedate`, `updateuser`)
			VALUES(". $article_id .", "
			. rex_clang::getStartId() .", "
			. "'', "
			. "'1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations', "
			. "'{\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_field_1\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_field_2\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_field_3\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_id\":\"location_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_clang_id\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_restriction_field\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_restriction_operator\":\"=\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_restriction_value\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_url_param_key\":\"location_id\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_seo_title\":\"name\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_seo_description\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_seo_image\":\"picture\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_sitemap_add\":\"1\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_sitemap_frequency\":\"monthly\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_sitemap_priority\":\"0.3\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_sitemap_lastmod\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_path_names\":\"\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_path_categories\":\"0\",\"1_xxx_". rex::getTablePrefix() ."d2u_courses_url_locations_relation_field\":\"location_category_id\"}', "
			. "'1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories', "
			. "'{\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_field_1\":\"name\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_field_2\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_field_3\":\"\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_id\":\"location_category_id\",\"1_xxx_relation_". rex::getTablePrefix() ."d2u_courses_location_categories_clang_id\":\"\"}', "
			. "'before', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."', UNIX_TIMESTAMP(), '". rex::getUser()->getValue('login') ."');");
	}

	d2u_addon_backend_helper::generateUrlCache();
}