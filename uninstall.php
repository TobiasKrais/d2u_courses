<?php
$sql = rex_sql::factory();

// Delete url schemes
if(\rex_addon::get('url')->isAvailable()) {
	if(rex_string::versionCompare(\rex_addon::get('url')->getVersion(), '1.5', '>=')) {
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."rex_url_generator_profile WHERE `namespace` = 'course_id';");
		$sql->setQuery("DELETE FROM ". \rex::getTablePrefix() ."rex_url_generator_profile WHERE `namespace` = 'courses_category_id';");		
	}
	else {
		$sql->setQuery("DELETE FROM `". \rex::getTablePrefix() ."url_generate` WHERE `table` LIKE '%d2u_courses%'");
	}
}

// Delete views
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_categories');
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_courses');

// Delete tables
$sql->setQuery('DROP TABLE IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_2_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_courses');

// Delete language replacements
if(!class_exists('d2u_courses_lang_helper')) {
	// Load class in case addon is deactivated
	require_once 'lib/d2u_courses_lang_helper.php';
}
d2u_courses_lang_helper::factory()->uninstall();