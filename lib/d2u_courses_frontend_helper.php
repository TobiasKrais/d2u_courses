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

		$urlParamKey = "";
		if(\rex_addon::get("url")->isAvailable()) {
			$url_data = UrlGenerator::getData();
			$urlParamKey = isset($url_data->urlParamKey) ? $url_data->urlParamKey : "";
		}

		// Courses
		if(filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "course_id")) {
			$course_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

			if($course_id > 0) {
				$course = new D2U_Courses\Course($course_id);
				if($course->category->parent_category !== FALSE) {
					$breadcrumbs[] = '<a href="' . $course->category->parent_category->getUrl() . '">' . $course->category->parent_category->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $course->category->getUrl() . '">' . $course->category->name . '</a>';
				$breadcrumbs[] = '<a href="' . $course->getUrl() . '">' . $course->name . '</a>';
			}
		}
		// Categories
		else if(filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "courses_category_id")) {
			$category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT);

			if($category_id > 0) {
				$category = new D2U_Courses\Category($category_id);
				if($category->parent_category !== FALSE) {
					$breadcrumbs[] = '<a href="' . $category->parent_category->getUrl() . '">' . $category->parent_category->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $category->getUrl() . '">' . $category->name . '</a>';
			}
		}
		// Locations
		else if(filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "location_id")) {
			$location_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);

			if($location_id > 0 && rex_plugin::get('d2u_courses', 'locations')) {
				$location = new D2U_Courses\Location($location_id);
				if($location->location_category !== FALSE) {
					$breadcrumbs[] = '<a href="' . $location->location_category->getUrl() . '">' . $location->location_category->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $location->getUrl() . '">' . $location->name . '</a>';
			}
		}
		// Location category
		else if(filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "location_category_id")) {
			$location_category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT);

			if($location_category_id > 0 && rex_plugin::get('d2u_courses', 'locations')) {
				$location_category = new D2U_Courses\LocationCategory($location_category_id);
				$breadcrumbs[] = '<a href="' . $location_category->getUrl() . '">' . $location_category->name . '</a>';
			}
		}
		// Schedule category
		else if(filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "schedule_category_id")) {
			$schedule_category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT);

			if($schedule_category_id > 0 && rex_plugin::get('d2u_courses', 'schedule_categories')) {
				$schedule_category = new D2U_Courses\ScheduleCategory($schedule_category_id);
				if($schedule_category->parent_schedule_category !== FALSE) {
					$breadcrumbs[] = '<a href="' . $schedule_category->parent_schedule_category->getUrl() . '">' . $schedule_category->parent_schedule_category->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $schedule_category->getUrl() . '">' . $schedule_category->name . '</a>';
			}
		}
		// Target groups
		else if(filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "target_group_id")) {
			$target_group_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT);

			if($target_group_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')) {
				$target_group = new D2U_Courses\TargetGroup($target_group_id);
				$breadcrumbs[] = '<a href="' . $target_group->getUrl() . '">' . $target_group->name . '</a>';
			}
		}
		// Target group children
		else if(filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "target_group_child_id")) {
			$target_group_child_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT);

			if($target_group_child_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')) {
				$target_group_child = D2U_Courses\TargetGroup::getByChildID($target_group_child_id);
				if($target_group_child->parent_target_group !== FALSE) {
					$breadcrumbs[] = '<a href="' . $target_group_child->parent_target_group->getUrl() . '">' . $target_group_child->parent_target_group->name . '</a>';
				}
				$breadcrumbs[] = '<a href="' . $target_group_child->getUrl() . '">' . $target_group_child->name . '</a>';
			}
		}

		return $breadcrumbs;
	}

	/**
	 * Returns breadcrumbs. Not from article path, but only part from this addon.
	 * @return string[] Breadcrumb elements
	 */
	public static function getMetaTags() {
		$meta_tags = "";

		// Prepare objects first for sorting in correct order
		$urlParamKey = "";
		if(\rex_addon::get("url")->isAvailable()) {
			$url_data = UrlGenerator::getData();
			$urlParamKey = isset($url_data->urlParamKey) ? $url_data->urlParamKey : "";
		}

		// Courses
		if(filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "course_id")) {
			$course_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

			if($course_id > 0) {
				$course = new D2U_Courses\Course($course_id);
				$meta_tags .= $course->getMetaAlternateHreflangTags();
				$meta_tags .= $course->getCanonicalTag() . PHP_EOL;
				$meta_tags .= $course->getMetaDescriptionTag() . PHP_EOL;
				$meta_tags .= $course->getTitleTag() . PHP_EOL;
			}
		}
		// Categories
		else if(filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "courses_category_id")) {
			$category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT);

			if($category_id > 0) {
				$category = new D2U_Courses\Category($category_id);
				$meta_tags .= $category->getMetaAlternateHreflangTags();
				$meta_tags .= $category->getCanonicalTag() . PHP_EOL;
				$meta_tags .= $category->getMetaDescriptionTag() . PHP_EOL;
				$meta_tags .= $category->getTitleTag() . PHP_EOL;
			}
		}
		// Locations
		else if(filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "location_id")) {
			$location_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);

			if($location_id > 0 && rex_plugin::get('d2u_courses', 'locations')) {
				$location = new D2U_Courses\Location($location_id);
				$meta_tags .= $location->getMetaAlternateHreflangTags();
				$meta_tags .= $location->getCanonicalTag() . PHP_EOL;
				$meta_tags .= $location->getMetaDescriptionTag() . PHP_EOL;
				$meta_tags .= $location->getTitleTag() . PHP_EOL;
			}
		}
		// Location category
		else if(filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "location_category_id")) {
			$location_category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT);

			if($location_category_id > 0 && rex_plugin::get('d2u_courses', 'locations')) {
				$location_category = new D2U_Courses\LocationCategory($location_category_id);
				$meta_tags .= $location_category->getMetaAlternateHreflangTags();
				$meta_tags .= $location_category->getCanonicalTag() . PHP_EOL;
				$meta_tags .= $location_category->getMetaDescriptionTag() . PHP_EOL;
				$meta_tags .= $location_category->getTitleTag() . PHP_EOL;
			}
		}
		// Schedule category
		else if(filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "schedule_category_id")) {
			$schedule_category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT);

			if($schedule_category_id > 0 && rex_plugin::get('d2u_courses', 'schedule_categories')) {
				$schedule_category = new D2U_Courses\ScheduleCategory($schedule_category_id);
				$meta_tags .= $schedule_category->getMetaAlternateHreflangTags();
				$meta_tags .= $schedule_category->getCanonicalTag() . PHP_EOL;
				$meta_tags .= $schedule_category->getMetaDescriptionTag() . PHP_EOL;
				$meta_tags .= $schedule_category->getTitleTag() . PHP_EOL;
			}
		}
		// Target groups
		else if(filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "target_group_id")) {
			$target_group_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT);

			if($target_group_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')) {
				$target_group = new D2U_Courses\TargetGroup($target_group_id);
				$meta_tags .= $target_group->getMetaAlternateHreflangTags();
				$meta_tags .= $target_group->getCanonicalTag() . PHP_EOL;
				$meta_tags .= $target_group->getMetaDescriptionTag() . PHP_EOL;
				$meta_tags .= $target_group->getTitleTag() . PHP_EOL;
			}
		}
		// Target group children
		else if(filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "target_group_child_id")) {
			$target_group_child_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT);

			if($target_group_child_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')) {
				$target_group_child = D2U_Courses\TargetGroup::getByChildID($target_group_child_id);
				$meta_tags .= $target_group_child->getMetaAlternateHreflangTags();
				$meta_tags .= $target_group_child->getCanonicalTag() . PHP_EOL;
				$meta_tags .= $target_group_child->getMetaDescriptionTag() . PHP_EOL;
				$meta_tags .= $target_group_child->getTitleTag() . PHP_EOL;
			}
		}

		return $meta_tags;
	}
}

