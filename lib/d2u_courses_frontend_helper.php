<?php
/**
 * Offers helper functions for frontend
 */
class d2u_courses_frontend_helper {
	/**
	 * Returns alternate URLs. Key is Redaxo language id, value is URL
	 * @return string[] alternate URLs
	 */
	public static function getAlternateURLs() {
		$alternate_URLs = [];
		return $alternate_URLs;
	}

	/**
	 * Returns breadcrumbs. Not from article path, but only part from this addon.
	 * @return string[] Breadcrumb elements
	 */
	public static function getBreadcrumbs() {
		$breadcrumbs = [];

		$url_namespace = d2u_addon_frontend_helper::getUrlNamespace();
		$url_id = d2u_addon_frontend_helper::getUrlId();

		// Courses
		if(filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "course_id") {
			$course_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

			if($course_id > 0) {
				$course = new D2U_Courses\Course($course_id);
				if($course->category->parent_category !== false) {
					if($course->category->parent_category->parent_category !== false) {
						if($course->category->parent_category->parent_category->parent_category !== false) {
							$breadcrumbs[] = '<a href="' . $course->category->parent_category->parent_category->parent_category->getUrl() . '">' . $course->category->parent_category->parent_category->parent_category->name . '</a>';
						}
						$breadcrumbs[] = '<a href="' . $course->category->parent_category->parent_category->getUrl() . '">' . $course->category->parent_category->parent_category->name . '</a>';
					}
					$breadcrumbs[] = '<a href="' . $course->category->parent_category->getUrl() . '">' . $course->category->parent_category->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $course->category->getUrl() . '">' . $course->category->name . '</a>';
				$breadcrumbs[] = '<a href="' . $course->getUrl() . '">' . $course->name . '</a>';
			}
		}
		// Categories
		else if(filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "courses_category_id") {
			$category_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT);

			if($category_id > 0) {
				$category = new D2U_Courses\Category($category_id);
				if($category->parent_category !== false) {
					if($category->parent_category->parent_category !== false) {
						$breadcrumbs[] = '<a href="' . $category->parent_category->parent_category->getUrl() . '">' . $category->parent_category->parent_category->name . '</a>';
					}
					$breadcrumbs[] = '<a href="' . $category->parent_category->getUrl() . '">' . $category->parent_category->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $category->getUrl() . '">' . $category->name . '</a>';
			}
		}
		// Locations
		else if(filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "location_id") {
			$location_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);

			if($location_id > 0 && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
				$location = new D2U_Courses\Location($location_id);
				if($location->location_category !== false) {
					$breadcrumbs[] = '<a href="' . $location->location_category->getUrl() . '">' . $location->location_category->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $location->getUrl() . '">' . $location->name . '</a>';
			}
		}
		// Location category
		else if(filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "location_category_id") {
			$location_category_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT);

			if($location_category_id > 0 && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
				$location_category = new D2U_Courses\LocationCategory($location_category_id);
				$breadcrumbs[] = '<a href="' . $location_category->getUrl() . '">' . $location_category->name . '</a>';
			}
		}
		// Schedule category
		else if(filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "schedule_category_id") {
			$schedule_category_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT);

			if($schedule_category_id > 0 && rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
				$schedule_category = new D2U_Courses\ScheduleCategory($schedule_category_id);
				if($schedule_category->parent_schedule_category !== false) {
					$breadcrumbs[] = '<a href="' . $schedule_category->parent_schedule_category->getUrl() . '">' . $schedule_category->parent_schedule_category->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $schedule_category->getUrl() . '">' . $schedule_category->name . '</a>';
			}
		}
		// Target groups
		else if(filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "target_group_id") {
			$target_group_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT);

			if($target_group_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
				$target_group = new D2U_Courses\TargetGroup($target_group_id);
				$breadcrumbs[] = '<a href="' . $target_group->getUrl() . '">' . $target_group->name . '</a>';
			}
		}
		// Target group children
		else if(filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "target_group_child_id") {
			$target_group_child_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT);

			if($target_group_child_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
				$target_group_child = D2U_Courses\TargetGroup::getByChildID($target_group_child_id);
				if($target_group_child->parent_target_group !== false) {
					$breadcrumbs[] = '<a href="' . $target_group_child->parent_target_group->getUrl() . '">' . $target_group_child->parent_target_group->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $target_group_child->getUrl() . '">' . $target_group_child->name . '</a>';
			}
		}

		return $breadcrumbs;
	}

	/**
	 * Get SQL WHERE clause for which courses are online or not.
	 * @return string WHERE clause
	 */
	public static function getShowTimeWhere() {
		$config_show_time = rex_config::get('d2u_courses', 'show_time', 'day_one_start');
		// Track changes of WHERE statement also in plugins/target_groups/update.php
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
		return $where;
	}
}