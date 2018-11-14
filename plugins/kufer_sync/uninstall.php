<?php
$sql = rex_sql::factory();

// Database cleanup
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_target_groups` DROP COLUMN `kufer_target_group_name`;');
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_target_groups` DROP COLUMN `kufer_categories`;');
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_categories` DROP COLUMN `kufer_categories`;');
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_schedule_categories` DROP COLUMN `kufer_categories`;');
$sql->setQuery('ALTER TABLE `'. rex::getTablePrefix() . 'd2u_courses_courses` DROP COLUMN `import_type`;');

// Delete CronJob if activated
if(rex_config::get('d2u_courses', 'kufer_sync_autoimport', 'inactive') == 'active') {
	if(!class_exists('kufer_sync_cronjob')) {
		// Load class in case addon is deactivated
		require_once 'lib/kufer_sync_cronjob.php';
	}
	$kufer_cronjob = kufer_sync_cronjob::factory();
	if($kufer_cronjob->isInstalled()) {
		$kufer_cronjob->delete();
	}
}