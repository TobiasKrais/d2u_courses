<?php
$sql = rex_sql::factory();

// Delete url schemes
if(\rex_addon::get('url')->isAvailable()) {
	$sql->setQuery("DELETE FROM `". rex::getTablePrefix() ."url_generate` WHERE `table` LIKE '%d2u_courses%'");
}

// Delete views
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_categories');
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_courses');

// Delete tables
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_2_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_courses');

// Delete language replacements
d2u_courses_lang_helper::factory()->uninstall();