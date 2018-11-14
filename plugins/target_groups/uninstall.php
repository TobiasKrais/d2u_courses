<?php
$sql = rex_sql::factory();

// Delete url schemes
if(\rex_addon::get('url')->isAvailable()) {
	$sql->setQuery("DELETE FROM `". rex::getTablePrefix() ."url_generate` WHERE `table` LIKE '%d2u_courses_url_target_group%'");
}

// Delete views
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_target_groups');
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_target_group_childs');

// Delete tables
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_target_groups');
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_2_target_groups');