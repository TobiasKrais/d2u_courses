<?php
$sql = rex_sql::factory();
// 3.0.4 Database update
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories LIKE 'priority';");
if($sql->getRows() == 0) {
	$sql->setQuery("ALTER TABLE `". \rex::getTablePrefix() ."d2u_courses_schedule_categories` ADD `priority` INT(10) NULL DEFAULT 0 AFTER `picture`;");
	$sql->setQuery("SELECT schedule_category_id FROM `". \rex::getTablePrefix() ."d2u_courses_schedule_categories` ORDER BY name;");
	$update_sql = rex_sql::factory();
	for($i = 1; $i <= $sql->getRows(); $i++) {
		$update_sql->setQuery("UPDATE `". \rex::getTablePrefix() ."d2u_courses_schedule_categories` SET priority = ". $i ." WHERE schedule_category_id = ". $sql->getValue('schedule_category_id') .";");
		$sql->next();
	}
}

// 3.0.4 Database update
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
	$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_schedule_categories AS
		SELECT schedules.schedule_category_id, schedules.name, schedules.name AS seo_title, schedules.picture, schedules.priority, courses.updatedate, schedules.parent_schedule_category_id
		FROM '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s
			ON schedules.schedule_category_id = c2s.schedule_category_id
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
			ON c2s.course_id = courses.course_id
		WHERE courses.online_status = "online"
			AND ('. $where .')
			AND courses.updatedate = (
				SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS schedules_max
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON schedules_max.course_id = courses_max.course_id
				WHERE schedules.schedule_category_id = schedules_max.schedule_category_id AND courses_max.online_status = "online" AND ('. $where .')
			)
		GROUP BY schedule_category_id, name, seo_title, picture, priority, updatedate, parent_schedule_category_id
		UNION
		SELECT parents.schedule_category_id, parents.name, parents.name AS seo_title, parents.picture, schedules.priority, courses.updatedate, parents.parent_schedule_category_id
		FROM '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s
			ON schedules.schedule_category_id = c2s.schedule_category_id
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
			ON c2s.course_id = courses.course_id
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS parents
			ON schedules.parent_schedule_category_id = parents.schedule_category_id
		WHERE parents.schedule_category_id > 0
			AND courses.online_status = "online"
			AND ('. $where .')
			AND courses.updatedate = (
				SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_schedule_categories AS c2s_max
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON c2s_max.course_id = courses_max.course_id
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_schedule_categories AS schedules_max ON c2s_max.schedule_category_id = c2s_max.schedule_category_id
				WHERE schedules.parent_schedule_category_id = schedules_max.schedule_category_id AND courses_max.online_status = "online" AND ('. $where .')
			)
		GROUP BY schedule_category_id, name, seo_title, picture, priority, updatedate, parent_schedule_category_id;');

	// Update url schemes
	if(\rex_addon::get('url')->isAvailable()) {
		UrlGenerator::generatePathFile([]);
	}
}