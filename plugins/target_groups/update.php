<?php
// 3.0.3 Database update
if (rex_string::versionCompare($this->getVersion(), '3.0.3', '<')) {
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

	$sql = rex_sql::factory();
	// Online target groups childs (separator is 00000 - ugly, but only digits are allowed)
	$sql->setQuery('CREATE OR REPLACE VIEW '. rex::getTablePrefix() .'d2u_courses_url_target_group_childs AS
		SELECT target.target_group_id, categories.category_id, CONCAT(target.target_group_id, "00000", categories.category_id) AS target_group_child_id, categories.name, categories.name AS seo_title, categories.picture, courses.updatedate
		FROM '. rex::getTablePrefix() .'d2u_courses_target_groups AS target
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_target_groups AS c2t
			ON target.target_group_id = c2t.target_group_id
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses
			ON c2t.course_id = courses.course_id
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_2_categories AS c2c
			ON courses.course_id = c2c.course_id
		LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_categories AS categories
			ON c2c.category_id = categories.category_id
		WHERE categories.category_id > 0
			AND courses.online_status = "online"
			AND ('. $where .')
			AND courses.updatedate = (
				SELECT MAX(courses_max.updatedate) FROM '. rex::getTablePrefix() .'d2u_courses_2_categories AS categories_max
				LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses_max ON categories_max.course_id = courses_max.course_id
				WHERE categories.category_id = categories_max.category_id AND courses_max.online_status = "online" AND ('. $where .')
			)
		GROUP BY target_group_id, category_id, target_group_child_id, name, seo_title, picture, updatedate;');

	// Update url schemes
	if(\rex_addon::get('url')->isAvailable()) {
		UrlGenerator::generatePathFile([]);
	}
}