<?php
/*
 * Get parameters
 */
$course = FALSE;
$category = FALSE;
$location = FALSE;
$location_category = FALSE;
$schedule_category = FALSE;
$target_group = FALSE;

$startpage = "REX_VALUE[1]";

$sprog = rex_addon::get("sprog");
$tag_open = $sprog->getConfig('wildcard_open_tag');
$tag_close = $sprog->getConfig('wildcard_close_tag');

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
	}
}
// Categories
else if(filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "courses_category_id")) {
	$category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT);

	if($category_id > 0) {
		$category = new D2U_Courses\Category($category_id);
	}
}
// Locations
else if(filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "location_id")) {
	$location_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);

	if($location_id > 0 && rex_plugin::get('d2u_courses', 'locations')) {
		$location = new D2U_Courses\Location($location_id);
	}
}
// Location category
else if(filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "location_category_id")) {
	$location_category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT);

	if($location_category_id > 0 && rex_plugin::get('d2u_courses', 'locations')) {
		$location_category = new D2U_Courses\LocationCategory($location_category_id);
	}
}
// Schedule category
else if(filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "schedule_category_id")) {
	$schedule_category_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT);

	if($schedule_category_id > 0 && rex_plugin::get('d2u_courses', 'schedule_categories')) {
		$schedule_category = new D2U_Courses\ScheduleCategory($schedule_category_id);
	}
}
// Target groups
else if(filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "target_group_id")) {
	$target_group_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT);

	if($target_group_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')) {
		$target_group = new D2U_Courses\TargetGroup($target_group_id);
	}
}
// Target group children
else if(filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || (rex_addon::get("url")->isAvailable() && $urlParamKey === "target_group_child_id")) {
	$target_group_child_id = (rex_addon::get("url")->isAvailable() && UrlGenerator::getId() > 0) ? UrlGenerator::getId() : filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT);

	if($target_group_child_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')) {
		$target_group = D2U_Courses\TargetGroup::getByChildID($target_group_child_id);
	}
}

/*
 * Function stuff
 */
if(!function_exists('formatCourseDate')) {
	/**
	 * Converts date string 2015-12-31 to 31. December 2015
	 * @param string $date date string (format: 2015-12-31)
	 * @return string converted date, eg. 31. December 2015
	 */
	function formatCourseDate($date) {
		$d = explode("-", $date);
		$unix = mktime(0, 0, 0, $d[1], $d[2], $d[0]);

		return date("d.m.Y", $unix);
	}
}

if(!function_exists('printBox')) {
	/**
	 * Print box
	 * @param string $title Box title
	 * @param string $picture_filename mediapool picture filename
	 * @param string $color Background color (Hex)
	 * @param string $url Link target url
	 */
	function printBox($title, $picture_filename, $color, $url) {
		print '<div class="col-6 col-md-4 spacer">';
		print '<div class="category_box" style="background-color: '. ($color == "" ? "grey" : "". $color) .'" data-height-watch>';
		print '<a href="'. $url .'">';
		print '<div class="view">';
		if($picture_filename != "") {
			print '<img src="index.php?rex_media_type=d2u_helper_sm&rex_media_file='. $picture_filename .'" alt="'. $title .'">';
		}
		else {
			print '<img src="'.	rex_addon::get("d2u_courses")->getAssetsUrl("empty_box.png") .'" alt="Placeholder">';
		}
		print '</div>';
		print '<div class="box_title">'. $title .'</div>';
		print '</a>';
		print '</div>';
		print '</div>';
	}
}


$courses = [];
// Get courses if search field was used
if(filter_input(INPUT_POST, 'course_search') != "") {
	$_SESSION['course_search'] = filter_input(INPUT_POST, 'course_search');
	$courses = D2U_Courses\Course::search($_SESSION['course_search']);
}
// Deal with categories
else if($category !== FALSE) {
	if(count($category->getChildren(TRUE)) > 0) {
		// Are child categories available?
		print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. $category->color .' !important">';
		print '<h1 class="page_title">'. $category->name .'</h1>';
		print '</div></div>';
		if(trim($category->description) != "") {
			print '<div class="col-12 course_row spacer">';
			print '<div class="course_box spacer_box">'. $category->description .'</div>';
			print '</div>';
		}
		// Children
		foreach ($category->getChildren(TRUE) as $child_category) {
			printBox($child_category->name, $child_category->picture, $child_category->color, $child_category->getURL());
		}
	}
	else {
		// Otherwise get courses
		$courses = $category->getCourses(TRUE);
	}
}
// Deal with location categories
else if(rex_plugin::get ('d2u_courses', 'locations')->isAvailable () && $location_category !== FALSE) {
	print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'location_bg_color', '#41b23b') .' !important">';
	print '<h1 class="page_title">'. $location_category->name .'</h1>';
	print '</div></div>';
	foreach ($location_category->getLocations(TRUE) as $location) {
		printBox($location->name, $location->picture, rex_config::get('d2u_courses', 'location_bg_color', '#41b23b'), $location->getURL());
	}
}
// Deal with locations
else if(rex_plugin::get ('d2u_courses', 'locations')->isAvailable () && $location !== FALSE) {
	$courses = $location->getCourses(TRUE);
}
// Deal with schedule categories
else if($schedule_category !== FALSE) {
	if(count($schedule_category->getChildren(TRUE)) > 0) {
		print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2') .' !important">';
		print '<h1 class="page_title">'. $schedule_category->name .'</h1>';
		print '</div></div>';
		// Children
		foreach ($schedule_category->getChildren(TRUE) as $child_schedule_category) {
			printBox($child_schedule_category->name, $child_schedule_category->picture, rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2'), $child_schedule_category->getURL());
		}		
	}
	else {
		$courses = $schedule_category->getCourses(TRUE);
	}
}
// Deal with target groups
else if($target_group !== FALSE) {
	if(count($target_group->getChildren(TRUE)) > 0) {
		print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a') .' !important">';
		print '<h1 class="page_title">'. $target_group->name .'</h1>';
		print '</div></div>';
		// Children
		foreach ($target_group->getChildren(TRUE) as $child_target_group) {
			printBox($child_target_group->name, $child_target_group->picture, rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a'), $child_target_group->getURL());
		}		
	}
	else {
		$courses = $target_group->getCourses(TRUE);
	}
}
// Nothing requested: selected startpage
else if($startpage == "locations" && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
	$location_categories = D2U_Courses\LocationCategory::getAll(TRUE);
	foreach ($location_categories as $location_category) {
		printBox($location_category->name, $location_category->picture, rex_config::get('d2u_courses', 'location_bg_color', '#41b23b'), $location_category->getURL());		
	}
}
else if($startpage == "schedule_categories" && rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
	$schedule_categories = D2U_Courses\ScheduleCategory::getAllParents(TRUE);
	foreach ($schedule_categories as $schedule_category) {
		printBox($schedule_category->name, $schedule_category->picture, rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2'), $schedule_category->getURL());		
	}
}
else if($startpage == "target_groups" && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
	$target_groups = D2U_Courses\TargetGroup::getAll(TRUE);
	foreach ($target_groups as $target_group) {
		printBox($target_group->name, $target_group->picture, rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a'), $target_group->getURL());		
	}
}
else if($course === FALSE) {
	// Only show default if no course should be shown
	$categories = D2U_Courses\Category::getAllParents(TRUE);
	foreach ($categories as $category) {
		printBox($category->name, $category->picture, $category->color, $category->getURL());
	}
}

// Course list
if(rex_config::get('d2u_courses', 'forward_single_course', 'inactive') =="active" && count($courses) == 1 && filter_input(INPUT_POST, 'course_search') == "") {
	foreach($courses as $course) {
		header('Location: '. $course->getURL());
		exit;
	}
}
else if(count($courses) > 0) {
	if(filter_input(INPUT_POST, 'course_search') != "") {
		print '<div class="col-12 spacer">';
		print '<h1>'. $tag_open .'d2u_courses_search_results'. $tag_close .' "'. $_SESSION['course_search'] .'":</h1>';
		print '</div>';
	}
	else if($target_group !== FALSE) {
		print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a') .' !important">';
		print '<h1 class="page_title">';
		if($target_group->parent_target_group !== FALSE) {
			print $target_group->parent_target_group->name .": ";
		}
		print $target_group->name;
		print '</h1>';
		print '</div></div>';
	}
	else if($schedule_category !== FALSE) {
		print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2') .' !important">';
		print '<h1 class="page_title">';
		if($schedule_category->parent_schedule_category !== FALSE) {
			print $schedule_category->parent_schedule_category->name .": ";
		}
		print $schedule_category->name;
		print '</h1>';
		print '</div></div>';
	}
	else if($location !== FALSE) {
		print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'location_bg_color', '#41b23b') .' !important">';
		print '<h1 class="page_title">'. $location->location_category->name .": ". $location->name .'</h1>';
		print '</div></div>';
	}
	else if($category !== FALSE) {
		print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. $category->color .' !important">';
		print '<h1 class="page_title">';
		if($category->parent_category !== FALSE) {
			print $category->parent_category->name .": ";
		}
		print $category->name;
		print '</h1>';
		print '</div></div>';
		if(trim($category->description) != "") {
			print '<div class="col-12 course_row spacer">';
			print '<div class="course_box spacer_box">'. $category->description .'</div>';
			print '</div>';
		}
	}
	foreach($courses as $list_course) {
		print '<div class="col-12">';
		print '<a href="'. $list_course->getURL(TRUE) .'" title="'. $list_course->name .'" class="course_row_a">';
		print '<div class="row course_row" data-match-height>';
		
		print '<div class="col-12 col-md-6 spacer_box">';
		print '<div class="course_row_title" style="background-color: '. $list_course->category->color .'" data-height-watch>';
		print $list_course->name .'<br />';
		if($list_course->date_start != "") {
			print formatCourseDate($list_course->date_start);
		}
		if($list_course->date_end != "") {
			print ' - '. formatCourseDate($list_course->date_end);
		}
		print '</div>';
		print '</div>';

		print '<div class="col-8 col-md-4">';
		print '<div class="course_box spacer_box" data-height-watch>';
		if($category !== FALSE && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
			// Show target groups if category name is in headline ...
			$counter_target_groups = 0;
			foreach($list_course->target_group_ids as $target_group_id) {
				$course_target_group = new D2U_Courses\TargetGroup($target_group_id);
				if($counter_target_groups > 0) {
					print '<br>';
				}
				print $course_target_group->name;
				$counter_target_groups++;
			}
		}
		else if($category === FALSE) {
			// ... otherwise show category name
			if($list_course->category->parent_category !== FALSE) {
				print $list_course->category->parent_category->name .': ';
			}
			print $list_course->category->name;
		}
		else {
			print $list_course->teaser;
		}
		print '</div>';
		print '</div>';
		
		print '<div class="col-4 col-md-2 course_row">';
		print '<div class="course_box spacer_box" data-height-watch>';
		if(rex_plugin::get('d2u_courses', 'locations')->isAvailable() && $list_course->location !== FALSE) {
			print $list_course->location->location_category->name;
		}
		if($list_course->registration_possible == "yes") {
			print ' <div class="open"></div>';
		}
		else if($list_course->registration_possible == "booked") {
			print ' <div class="closed"></div>';
		}
		print '</div>';
		print '</div>';

		print '</div>';
		print '</a>';
		print '</div>';
	}
}
else if(filter_input(INPUT_POST, 'course_search') != "") {
	print '<div class="col-12">';
	print '<h1>'. $tag_open .'d2u_courses_search_no_hits'. $tag_close .'</h1>';
	print '<p>'. $tag_open .'d2u_courses_search_no_hits_text'. $tag_close .'</p>';
	print '</div>';
}

// Course
if($course !== FALSE) {
	print '<div class="col-12 spacer_box">';
	print '<div class="course_row_title" style="background-color: '. $course->category->color.'">';
	print '<h1>'. $course->name;
	if($course->course_number != "") {
		print ' ('. $course->course_number .')';
	}
	if($course->registration_possible == "yes") {
		print ' <div class="open"></div>';
	}
	else if($course->anmeldung_moeglich == "ausgebucht") {
		print ' <div class="closed"></div>';
	}
	print '</h1>'. $course->details_age;
	print '</div>';
	print '</div>';

	print '</div>';
	if($course->description != "") {
		print '<div class="row" data-match-height>';

		if($course->picture != "") {
			print '<div class="col-12 col-sm-9 course_row" data-height-watch>';
		}
		else {
			print '<div class="col-12 course_row" data-height-watch>';
			
		}
		print '<div class="course_box spacer_box" data-height-watch>'. $course->description .'</div>';
		print '</div>';

		if($course->picture != "") {
			print '<div class="col-12 col-sm-3 course_row" data-height-watch>';
			print '<div class="course_box spacer_box course_picture" data-height-watch><img src="index.php?rex_media_type=d2u_helper_sm&rex_media_file='. $course->picture .'" alt="'. $course->name .'"></div>';
			print '</div>';
		}

		print '</div>';
	}
	print '<div class="row" data-match-height>';
	print '<div class="col-12 col-md-6">';
	print '<div class="row" data-match-height>';

	if($course->instructor != "") {
		print '<div class="col-12 course_row" data-height-watch>';
		print '<div class="course_box spacer_box" data-height-watch><b>'. $tag_open .'d2u_courses_instructor'. $tag_close .':</b> '. $course->instructor .'</div>';
		print '</div>';
	}
	
	if($course->date_start != "" || $course->date_end != "" || $course->time != "") {
		print '<div class="col-12 course_row" data-height-watch>';
		print '<div class="course_box spacer_box" data-height-watch><b>'. $tag_open .'d2u_courses_date'. $tag_close .':</b> ';
		if($course->date_start != "" || $course->date_end != "" || $course->time != "") {
			$date = '';
			if($course->date_start != "") {
				$date .= formatCourseDate($course->date_start);
			}
			if($course->date_end != "") {
				$date .= ' - '. formatCourseDate($course->date_end);
			}
			if($course->time != "") {
				if($date != "") {
					$date .= ', ';
				}
				$date .= $course->time;
//				if(strpos($course->time, "Uhr") === FALSE) {
//					$date .= ' '. $tag_open .'d2u_courses_oclock'. $tag_close;
//				}
			}
			print $date .'<br>';
		}
		print '</div>';
		print '</div>';
	}

	if(trim($course->details_deadline) != "") {
		print '<div class="col-12 course_row" data-height-watch>';
		print '<div class="course_box spacer_box" data-height-watch>';
		print '<b>'. $tag_open .'d2u_courses_registration_deadline'. $tag_close .':</b> '. $course->details_deadline;
		print '</div>';
		print '</div>';
	}

	if($course->price > 0) {
		print '<div class="col-12 course_row" data-height-watch>';
		print '<div class="course_box spacer_box" data-height-watch><b>'. $tag_open .'d2u_courses_fee'. $tag_close .':</b> '. number_format($course->price, 2, ",", ".") .' €';
		if($course->price_discount > 0 && $course->price_discount < $course->price) {
			print ' ('. $tag_open .'d2u_courses_discount'. $tag_close .': '. number_format($course->price_discount, 2, ",", ".") .' €)';
		}
		print '</div>';
		print '</div>';
	}

	if(trim($course->details_course) != "") {
		print '<div class="col-12 course_row" data-height-watch>';
		print '<div class="course_box spacer_box" data-height-watch>';
		print '<b>'. $tag_open .'d2u_courses_details_course'. $tag_close .':</b> '. $course->details_course;
		print '</div>';
		print '</div>';
	}

	if($course->participants_max > 0) {
		print '<div class="col-12 course_row" data-height-watch>';
		print '<div class="course_box spacer_box" data-height-watch>';
		print '<b>'. $tag_open .'d2u_courses_participants'. $tag_close .': '. $course->participants_max .'</b> ('. $tag_open .'d2u_courses_max'. $tag_close .')';
		if($course->participants_min > 0) {
			print ' / <b>'. $course->participants_min .'</b> ('. $tag_open .'d2u_courses_min'. $tag_close .')';
		}
		if($course->participants_number > 0) {
			print ' / <b>'. $course->participants_number .'</b> ('. $tag_open .'d2u_courses_booked'. $tag_close .')';
		}
		if($course->participants_wait_list > 0) {
			print ' / <b>'. $course->participants_wait_list .'</b> ('. $tag_open .'d2u_courses_wait_list'. $tag_close .')';
		}
		print '<br></div>';
		print '</div>';
	}
	
	if($course->redaxo_article > 0 || $course->url_external != "" || count($course->downloads) > 0) {
		if($course->redaxo_article > 0 || $course->url_external != "") {
			print '<div class="col-12 course_row" data-height-watch>';
			print '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_infolink'. $tag_close .':</b> ';
			if($course->redaxo_article > 0) {
				$article = rex_article::get($course->redaxo_article);
				print '<a href="'. rex_getUrl($course->redaxo_article) .'">'. $article->getName() .'</a><br>';
			}
			if($course->url_external != "") {
				print '<a href="'. $course->url_external .'" target="_blank">'. $course->url_external .'</a><br>';
			}
			print '</div>';
			print '</div>';
		}

		if(count($course->downloads) > 0) {
			print '<div class="col-12 course_row" data-height-watch>';
			print '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_downloads'. $tag_close .':</b> ';
			print '<ul>';
			foreach($course->downloads as $document) {
				$rex_document = rex_media::get($document);
				print '<li><a href="'. rex_url::media($document) .'" target="_blank">'. ($rex_document->getTitle() == "" ? $document : $rex_document->getTitle()) .'</a></li>';
			}
			print '</ul>';
			print '</div>';
			print '</div>';
		}
	}

	if($course->registration_possible == "yes" || $course->registration_possible == "booked") {
		print '</div>';
		print '<div class="row">';
		print '<div class="col-12 course_row" data-height-watch>';
		print '<div class="course_box spacer_box add_cart" data-height-watch>';
		if(D2U_Courses\Cart::getCart()->hasCourse($course->course_id)) {
			print '<a href="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart', rex_article::getSiteStartArticleId())) .'">'. $tag_open .'d2u_courses_cart_course_already_in'. $tag_close .' - '. $tag_open .'d2u_courses_cart_go_to'. $tag_close .'</a>';
		}
		else {
			print '<form action="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart', rex_article::getSiteStartArticleId())) .'" method="post">';
			print '<input type="hidden" name="course_id" value="'. $course->course_id .'">';
			print '<input type="submit" class="add_cart" name="submit" value="'. $tag_open .'d2u_courses_cart_add'. $tag_close .'">';
			print '</form>';
		}
		print '</div>';
		print '</div>';
	}

	print '</div>';
	print '</div>';
	
	if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
		print '<div class="col-12 col-md-6">';
		print '<div class="course_box spacer_box">';
		print '<div class="row">';

		print '<div class="col-12 course_row">';
		if($course->room != "") {
			print '<b>'. $tag_open .'d2u_courses_locations_room'. $tag_close .':</b><br />';
			print $course->room;
			if($course->location->site_plan != "") {
				print ' (<a href="'. rex_url::media($course->location->site_plan) .'" target="_blank">'. $tag_open .'d2u_courses_locations_site_plan'. $tag_close .'</a>)';
			}
			print '<br><br>';
		}
		else {
			if($course->location->site_plan != "") {
				print '<b><a href="'. rex_url::media($course->location->site_plan) .'" target="_blank">'. $tag_open .'d2u_courses_locations_site_plan'. $tag_close .'</a></b><br><br>';
			}
		}
		print '<b>'. $tag_open .'d2u_courses_locations_city'. $tag_close .':</b><br />';
		print $course->location->name;
		if($course->location->street != "") {
			print '<br>'. $course->location->street;
		}
		if($course->location->zip_code != "" || $course->location->city != "") {
			print '<br>'. $course->location->zip_code .' '. $course->location->city;
		}
		print '</div>';

		// Show Google map
		$show_map = "REX_VALUE[2]" == 'true' ? TRUE : FALSE;
		if($show_map) {
			print '<div class="col-12 col-md-8 course_row">';
			?>
			<script type="text/javascript" src="https://maps.google.com/maps/api/js"></script> 
			<div id="map_canvas" style="display: block; width: 100%; height: 300px"></div> 
			<script type="text/javascript"> 
				function createGeocodeMap() {
					var geocoder = new google.maps.Geocoder();
					var map; 
					var address = "<?php print $course->location->street .', '. $course->location->zip_code .' '. $course->location->city; ?>";
					if (geocoder) {
						geocoder.geocode( { 'address': address}, function(results, status) {
							if (status == google.maps.GeocoderStatus.OK) {
								map.setCenter(results[0].geometry.location);
								var marker = new google.maps.Marker({
									map: map,
									position: results[0].geometry.location
								});
								var infowindow = new google.maps.InfoWindow({
									content: "<?php print $course->location->name; ?>",
									position: results[0].geometry.location
								});
								infowindow.open(map, marker);
								google.maps.event.addListener(marker, 'click', function() {
									infowindow.open(map,marker);
								});
							} else {
								alert("Geocode was not successful for the following reason: " + status);
							}
						});
					}

					var myOptions = {
						zoom: <?php echo $course->location->location_category->zoom_level; ?>,
						mapTypeId: google.maps.MapTypeId.ROADMAP
					}
					map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
				}

				<?php
					// If longitude and latitude is available: create map
					if($course->location->latitude != 0 && $course->location->longitude != 0) {
				?>
				function createDegreeMap() {
					var myLatlng = new google.maps.LatLng(<?php print $course->location->latitude .", ". $course->location->longitude; ?>);
					var myOptions = {
						zoom: <?php echo $course->location->location_category->zoom_level; ?>,
						center: myLatlng,
						mapTypeId: google.maps.MapTypeId.ROADMAP
					}
					var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

					var marker = new google.maps.Marker({
						position: myLatlng, 
						map: map
					});

					var infowindow = new google.maps.InfoWindow({
						content: "<?php print $course->location->name; ?>",
						position: myLatlng
					});
					infowindow.open(map, marker);
					google.maps.event.addListener(marker, 'click', function() {
						infowindow.open(map,marker);
					});
				}
				// Init on direct map call
				createDegreeMap();
				<?php
					}
					// Fallback on geocoding
					else {
						print "createGeocodeMap();";
					}
				?>
			</script>
			<?php
			print '</div>';
		}
		print '</div>';
		print '</div>';
		print '</div>';
	}
	print '</div>';

	print '</div>';
}
?>
<script>
	$(window).on("load",
		function(e) {
			$("[data-match-height]").each(
				function() {
					var e=$(this),
						t=$(this).find("[data-height-watch]"),
						n=t.map(function() {
							return $(this).innerHeight();
						}).get(),
						i=Math.max.apply(Math,n);
					t.css("min-height", i+1);
				}
			)
		}
	)
</script>