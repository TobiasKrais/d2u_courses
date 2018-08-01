<?php
$sql = rex_sql::factory();

$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_target_groups LIKE 'kufer_target_group_name';");
if($sql->getRows() == 0) {
	$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_target_groups` ADD `kufer_target_group_name` varchar(255) default NULL AFTER `picture`;');
	$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_target_groups` ADD `kufer_categories` text default NULL AFTER `kufer_target_group_name`;');
}
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_categories LIKE 'kufer_categories';");
if($sql->getRows() == 0) {
	$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_categories` ADD `kufer_categories` text default NULL AFTER `parent_category_id`;');
}
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories LIKE 'kufer_categories';");
if($sql->getRows() == 0) {
	$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_schedule_categories` ADD `kufer_categories` text default NULL AFTER `parent_schedule_category_id`;');
}
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_courses LIKE 'import_type';");
if($sql->getRows() == 0) {
	$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_courses` ADD `import_type` varchar(10) default NULL AFTER `downloads`;');
}