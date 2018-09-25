<?php
$sql = rex_sql::factory();

// Database cleanup
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_target_groups` DROP COLUMN `kufer_target_group_name`;');
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_target_groups` DROP COLUMN `kufer_categories`;');
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_categories` DROP COLUMN `kufer_categories`;');
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_schedule_categories` DROP COLUMN `kufer_categories`;');
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_courses` DROP COLUMN `import_type`;');

// Delete Autoimport if activated
if(rex_config::get('d2u_courses', 'kufer_sync_autoimport', 'inactive') == 'active') {
	kufer_sync_cronjob::delete();
}