<?php
$sql = rex_sql::factory();

// Delete url schemes
if(\rex_addon::get('url')->isAvailable()) {
	$sql->setQuery("DELETE FROM `". rex::getTablePrefix() ."url_generate` WHERE `table` LIKE '%d2u_courses_url_schedule_categories%'");
}

// Delete views
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_schedule_categories');

// Delete tables
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_schedule_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_2_schedule_categories');

// Delete language replacements
d2u_courses_lang_helper::factory()->uninstall();