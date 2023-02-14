<?php
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}

/*
 * Get parameters
 */
$course = false;
$category = false;
$location = false;
$location_category = false;
$schedule_category = false;
$target_group = false;

$startpage = "REX_VALUE[1]";

$news_category_id = "REX_VALUE[5]";
$news = [];
if($news_category_id > 0) {
	$new_category = new \D2U_News\Category($news_category_id, rex_clang::getCurrentId());
	$news = $new_category->getNews(true);
}
$linkbox_category_id = "REX_VALUE[6]";
$linkboxes = [];
if($linkbox_category_id > 0) {
	$new_category = new \D2U_Linkbox\Category($linkbox_category_id, rex_clang::getCurrentId());
	$linkboxes = $new_category->getLinkboxes(true);
}
$box_per_line = "REX_VALUE[8]" == 4 ? 4 : 3;

$sprog = rex_addon::get("sprog");
$tag_open = $sprog->getConfig('wildcard_open_tag');
$tag_close = $sprog->getConfig('wildcard_close_tag');

$url_namespace = d2u_addon_frontend_helper::getUrlNamespace();
$url_id = d2u_addon_frontend_helper::getUrlId();

// Courses
if(filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "course_id") {
	$course_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

	if($course_id > 0) {
		$course = new D2U_Courses\Course($course_id);
		// Redirect if object is not online
		if($course->online_status != "online") {
			\rex_redirect(rex_article::getNotfoundArticleId(), rex_clang::getCurrentId());
		}
	}
}
// Categories
else if(filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "courses_category_id") {
	$category_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT);

	if($category_id > 0) {
		$category = new D2U_Courses\Category($category_id);
	}
}
// Locations
else if(filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "location_id") {
	$location_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);

	if($location_id > 0 && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
		$location = new D2U_Courses\Location($location_id);
	}
}
// Location category
else if(filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "location_category_id") {
	$location_category_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT);

	if($location_category_id > 0 && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
		$location_category = new D2U_Courses\LocationCategory($location_category_id);
	}
}
// Schedule category
else if(filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "schedule_category_id") {
	$schedule_category_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT);

	if($schedule_category_id > 0 && rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
		$schedule_category = new D2U_Courses\ScheduleCategory($schedule_category_id);
	}
}
// Target groups
else if(filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "target_group_id") {
	$target_group_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT);

	if($target_group_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
		$target_group = new D2U_Courses\TargetGroup($target_group_id);
	}
}
// Target group children
else if(filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0 || $url_namespace === "target_group_child_id") {
	$target_group_child_id = (rex_addon::get("url")->isAvailable() && $url_id > 0) ? $url_id : filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT);

	if($target_group_child_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
		$target_group = D2U_Courses\TargetGroup::getByChildID($target_group_child_id);
	}
}

/*
 * Function stuff
 */
if(!function_exists('printBox')) {
	/**
	 * Print box
	 * @param string $title Box title
	 * @param string $picture_filename mediapool picture filename
	 * @param string $color Background color (Hex)
	 * @param string $url Link target url
	 * @param int $number_columns can be 2, 3 or 4
	 */
	function printBox($title, $picture_filename, $color, $url, $number_columns = 3) {
		print '<div class="col-6'. ($number_columns >= 3 ? ' col-md-4' : '') . ($number_columns == 4 ? ' col-lg-3' : '') .' spacer">';
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

if(rex::isBackend()) {
	print "Ausgabe Veranstaltungen des D2U Veranstaltungen Addons"
		. ($new_category > 0 ? " / mit News aus dem D2U News Addon" : "")
		. ($linkbox_category_id > 0 ? " / mit Linxboxen aus dem D2U Linkbox Addon" : "");
}
else {
	// Course search box
	$show_search = "REX_VALUE[3]" == 'true' ? true : false;
	if($show_search) {
		print '<div class="d-none d-sm-block col-sm-6 col-md-8 spacer d-print-none">&nbsp;</div>';
		print '<div class="col-12 col-sm-6 col-md-4 spacer d-print-none">';
		print '<div class="search_div">';
		print '<form action="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_courses')) .'" method="post">'
			. '<input class="search_box" name="course_search" value="'. filter_input(INPUT_POST, 'course_search') .'" type="text">'
			. '<button class="search_button"><img src="'. rex_url::addonAssets('d2u_courses', 'lens.png').'"></button>'
			. '</form>';
		print '</div>';
		print '</div>';
	}

	$courses = [];
	// Get courses if search field was used
	if(filter_input(INPUT_POST, 'course_search') != "") {
		$courses = D2U_Courses\Course::search(filter_input(INPUT_POST, 'course_search'));
	}
	// Deal with categories
	else if($category) {
		if(count($category->getChildren(true)) > 0) {
			// Are child categories available?
			print '<div class="col-12 course-title"><div class="page_title_bg" style="background-color: '. $category->color .' !important">';
			print '<h1 class="page_title">'. $category->name .'</h1>';
			print '</div></div>';
			if(trim($category->description) != "") {
				print '<div class="col-12 course_row spacer">';
				print '<div class="course_box spacer_box">'. $category->description .'</div>';
				print '</div>';
			}
			// Children
			foreach ($category->getChildren(true) as $child_category) {
				printBox($child_category->name, $child_category->picture, $child_category->color, $child_category->getURL(), $box_per_line);
			}
		}
		else {
			// Otherwise get courses
			$courses = $category->getCourses(true);
		}
	}
	// Deal with location categories
	else if(rex_plugin::get ('d2u_courses', 'locations')->isAvailable() && $location_category) {
		print '<div class="col-12 course-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'location_bg_color', '#41b23b') .' !important">';
		print '<h1 class="page_title">'. $location_category->name .'</h1>';
		print '</div></div>';
		foreach ($location_category->getLocations(true) as $location) {
			printBox($location->name, $location->picture, rex_config::get('d2u_courses', 'location_bg_color', '#41b23b'), $location->getURL(), $box_per_line);
		}
	}
	// Deal with locations
	else if(rex_plugin::get ('d2u_courses', 'locations')->isAvailable () && $location) {
		$courses = $location->getCourses(true);
	}
	// Deal with schedule categories
	else if($schedule_category) {
		if(count($schedule_category->getChildren(true)) > 0) {
			print '<div class="col-12 course-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2') .' !important">';
			print '<h1 class="page_title">'. $schedule_category->name .'</h1>';
			print '</div></div>';
			// Children
			foreach ($schedule_category->getChildren(true) as $child_schedule_category) {
				printBox($child_schedule_category->name, $child_schedule_category->picture, rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2'), $child_schedule_category->getURL(), $box_per_line);
			}		
		}
		else {
			$courses = $schedule_category->getCourses(true);
		}
	}
	// Deal with target groups
	else if($target_group) {
		if(count($target_group->getChildren(true)) > 0) {
			print '<div class="col-12 course-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a') .' !important">';
			print '<h1 class="page_title">'. $target_group->name .'</h1>';
			print '</div></div>';
			// Children
			foreach ($target_group->getChildren(true) as $child_target_group) {
				printBox($child_target_group->name, $child_target_group->picture, rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a'), $child_target_group->getURL(), $box_per_line);
			}		
		}
		else {
			$courses = $target_group->getCourses(true);
		}
	}
	// Nothing requested: selected startpage
	else {
		$tmp_box_per_line = $box_per_line;
		if(count($news) > 0 && $course === false) {
			$tmp_box_per_line--;
			print '<div class="col-12 col-lg-8" data-match-height>';
			print '<div class="row">';
		}
		if($startpage == "locations" && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			$location_categories = D2U_Courses\LocationCategory::getAll(true);
			foreach ($location_categories as $location_category) {
				printBox($location_category->name, $location_category->picture, rex_config::get('d2u_courses', 'location_bg_color', '#41b23b'), $location_category->getURL(), $tmp_box_per_line);		
			}
		}
		else if($startpage == "schedule_categories" && rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
			$schedule_categories = D2U_Courses\ScheduleCategory::getAllParents(true);
			foreach ($schedule_categories as $schedule_category) {
				printBox($schedule_category->name, $schedule_category->picture, rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2'), $schedule_category->getURL(), $tmp_box_per_line);		
			}
		}
		else if($startpage == "target_groups" && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
			$target_groups = D2U_Courses\TargetGroup::getAll(true);
			foreach ($target_groups as $target_group) {
				printBox($target_group->name, $target_group->picture, rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a'), $target_group->getURL(), $tmp_box_per_line);		
			}
		}
		else if($course === false) {
			// Only show default if no course should be shown
			$categories = D2U_Courses\Category::getAllParents(true);
			foreach ($categories as $category) {
				printBox($category->name, $category->picture, $category->color, $category->getURL(), $tmp_box_per_line);
			}
			if(count($linkboxes) > 0) {
				foreach($linkboxes as $linkbox) {
					printBox($linkbox->title, $linkbox->picture, $linkbox->background_color, $linkbox->getURL(), $tmp_box_per_line);
				}
			}
			if(count($news) > 0) {
				print '</div>';
				print '</div>';
				print '<div class="col-12 col-lg-4">';
				print '<div class="row">';
				foreach($news as $selected_news) {
					print '<div class="col-12 col-md-6 col-lg-12 spacer">';
					print '<div class="news_box" data-height-watch>';
					$url = $selected_news->getUrl();
					if($url) {
						print '<a href="'. $selected_news->getUrl() .'">';
					}
					print '<div class="box_title">'. $selected_news->name .'</div>';
					print '<div class="box_description">'. $selected_news->teaser .'</div>';
					if($url) {
						print '</a>';
					}
					print '</div>';
					print '</div>';
				}
				print '</div>';
				print '</div>';
				print '</div>';
			}
		}
	}

	// Course list
	if(rex_config::get('d2u_courses', 'forward_single_course', 'inactive') == "active" && count($courses) == 1 && filter_input(INPUT_POST, 'course_search') == "") {
		foreach($courses as $course) {
			header('Location: '. $course->getURL());
			exit;
		}
	}
	else if(count($courses) > 0) {
		if(filter_input(INPUT_POST, 'course_search') != "") {
			print '<div class="col-12 course-title">';
			print '<div class="search_title">';	
			print '<h1>'. $tag_open .'d2u_courses_search_results'. $tag_close .' "'. filter_input(INPUT_POST, 'course_search') .'":</h1>';
			print '</div>';
			print '</div>';
		}
		else if($target_group !== false) {
			print '<div class="col-12 course-title course-list-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a') .' !important">';
			print '<h1 class="page_title">';
			print $target_group->name;
			print '</h1>';
			print '</div></div>';
			if(trim($target_group->description) != "") {
				print '<div class="col-12 course_row spacer">';
				print '<div class="course_box spacer_box">'. $target_group->description .'</div>';
				print '</div>';
			}
		}
		else if($schedule_category !== false) {
			print '<div class="col-12 course-title course-list-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2') .' !important">';
			print '<h1 class="page_title">';
			print $schedule_category->name;
			print '</h1>';
			print '</div></div>';
		}
		else if($location !== false) {
			print '<div class="col-12 course-title course-list-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'location_bg_color', '#41b23b') .' !important">';
			print '<h1 class="page_title">'. $location->location_category->name .": ". $location->name .'</h1>';
			print '</div></div>';
		}
		else if($category !== false) {
			print '<div class="col-12 course-title course-list-title"><div class="page_title_bg" style="background-color: '. $category->color .' !important">';
			print '<h1 class="page_title">';
			print $category->name;
			print '</h1>';
			print '</div></div>';
			if(trim($category->description) != "") {
				print '<div class="col-12 course_row spacer">';
				print '<div class="course_box spacer_box">'. $category->description .'</div>';
				print '</div>';
			}
		}

		$course_list_box_style = "REX_VALUE[4]" == 'true' ? true : false;
		foreach($courses as $list_course) {
			if($course_list_box_style) {
				$title = $list_course->name ."<br><small>"
					. (new DateTime($list_course->date_start))->format('d.m.Y') ."</small>";
				printBox($title, $list_course->picture, $list_course->category->color, $list_course->getURL(true), $box_per_line);
			}
			else {
				print '<div class="col-12">';
				$title = $list_course->name;
				if($list_course->registration_possible == "booked") {
					$title .= ' - '. $tag_open .'d2u_courses_booked_complete'. $tag_close;
				}

				print '<a href="'. $list_course->getURL(true) .'" title="'. $title .'" class="course_row_a">';
				print '<div class="row course_row" data-match-height>';

				print '<div class="col-12 col-md-6 spacer_box">';
				print '<div class="course_row_title" style="background-color: '. $list_course->category->color .'" data-height-watch>';
				print $title;
				print '</div>';
				print '</div>';

				print '<div class="col-8 col-md-4">';
				print '<div class="course_box spacer_box" data-height-watch>';
				if($list_course->date_start) {
					print (new DateTime($list_course->date_start))->format('d.m.Y')
						.($list_course->date_end ? ' - '. (new DateTime($list_course->date_end))->format('d.m.Y') : '')
						.($list_course->time ? '<br>'. $list_course->time .'' : '');
				}
				else if($list_course->time) {
					print $list_course->time;
				}
				else {
					print $list_course->teaser;
				}
				print '</div>';
				print '</div>';

				print '<div class="col-4 col-md-2 course_row">';
				print '<div class="course_box spacer_box" data-height-watch>';
				if(rex_plugin::get('d2u_courses', 'locations')->isAvailable() && $list_course->location !== false) {
					print $list_course->location->location_category->name;
				}
				if($list_course->registration_possible == "yes" || $list_course->registration_possible == "yes_number") {
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
	}
	else if(filter_input(INPUT_POST, 'course_search') != "") {
		print '<div class="col-12">';
		print '<div class="search_title">';
		print '<h1>'. $tag_open .'d2u_courses_search_no_hits'. $tag_close .'</h1>';
		print '<p>'. $tag_open .'d2u_courses_search_no_hits_text'. $tag_close .'</p>';
		print '</div>';
		print '</div>';
	}

	// Course
	if($course !== false) {
		print '<div class="col-12">';
		print '<div class="row">';

		print '<div class="col-12 course-title_box">';
		print '<div class="course_title" style="background-color: '. $course->category->color.'">';
		print '<h1>'. $course->name;
		if($course->course_number != "") {
			print ' ('. $course->course_number .')';
		}
		if($course->registration_possible == "yes" || $course->registration_possible == "yes_number") {
			print ' <div class="open"></div>';
		}
		else if($list_course->registration_possible == "booked") {
			print ' <div class="closed"></div>';
		}
		print '</h1>'. ($course->details_age ? '<div class="details_age">'. $course->details_age .'</div>' : '');
		print '</div>';
		print '</div>';

		print '</div>'; // End row

		$box_details = '';

		if($course->instructor != "") {
			$box_details .= '<div class="col-12 course_row">';
			$box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_instructor'. $tag_close .':</b> '. $course->instructor .'</div>';
			$box_details .= '</div>';
		}

		if($course->date_start != "" || $course->date_end != "" || $course->time != "") {
			$box_details .= '<div class="col-12 course_row">';
			$box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_date'. $tag_close .':</b> ';
			if($course->date_start != "" || $course->date_end != "" || $course->time != "") {
				$date = '';
				if($course->date_start != "") {
					$date .= (new DateTime($course->date_start))->format('d.m.Y');
				}
				if($course->date_end != "") {
					$date .= ' - '. (new DateTime($course->date_end))->format('d.m.Y');
				}
				if($course->time != "") {
					if($date != "") {
						$date .= ', ';
					}
					$date .= $course->time;
	//				if(strpos($course->time, "Uhr") === false) {
	//					$date .= ' '. $tag_open .'d2u_courses_oclock'. $tag_close;
	//				}
				}
				$box_details .= $date .'<br>';
			}
			$box_details .= '</div>';
			$box_details .= '</div>';
		}

		if(trim($course->details_deadline) != "") {
			$box_details .= '<div class="col-12 course_row">';
			$box_details .= '<div class="course_box spacer_box">';
			$box_details .= '<b>'. $tag_open .'d2u_courses_registration_deadline'. $tag_close .':</b> '. $course->details_deadline;
			$box_details .= '</div>';
			$box_details .= '</div>';
		}

		if($course->price > 0 || ($course->price_salery_level && $course->price_salery_level_details)) {
			$box_details .= '<div class="col-12 course_row">';
			$box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_fee'. $tag_close .':</b> ';
			if($course->price_salery_level) {
				$box_details .= $tag_open. 'd2u_courses_price_salery_level_details'. $tag_close .'<br>';
				$box_details .= '<select class="participant" name="participant_price_salery_level_row_add" style="border-color:'. $course->category->color .'">';
				$counter_row_price_salery_level_details = 0;
				foreach($course->price_salery_level_details as $key => $value) {
					$counter_row_price_salery_level_details++;
					$box_details .= '<option value="'. $counter_row_price_salery_level_details .'">'. $key .': '. $value .'</option>';
				}
				$box_details .=  '</select>';
			}
			else {
				$box_details .= number_format($course->price, 2, ",", ".") .' €';
				if($course->price_discount > 0 && $course->price_discount < $course->price) {
					$box_details .= ' ('. $tag_open .'d2u_courses_discount'. $tag_close .': '. number_format($course->price_discount, 2, ",", ".") .' €)';
				}
			}
			$box_details .= '</div>';
			$box_details .= '</div>';
		}

		if(trim($course->details_course) != "") {
			$box_details .= '<div class="col-12 course_row">';
			$box_details .= '<div class="course_box spacer_box">';
			$box_details .= '<b>'. $tag_open .'d2u_courses_details_course'. $tag_close .':</b> '. $course->details_course;
			$box_details .= '</div>';
			$box_details .= '</div>';
		}

		if($course->participants_max > 0) {
			$box_details .= '<div class="col-12 course_row">';
			$box_details .= '<div class="course_box spacer_box">';
			$box_details .= '<b>'. $tag_open .'d2u_courses_participants'. $tag_close .': '. $course->participants_max .'</b> ('. $tag_open .'d2u_courses_max'. $tag_close .')';
			if($course->participants_min > 0) {
				$box_details .= ' / <b>'. $course->participants_min .'</b> ('. $tag_open .'d2u_courses_min'. $tag_close .')';
			}
			if($course->participants_number > 0) {
				$box_details .= ' / <b>'. $course->participants_number .'</b> ('. $tag_open .'d2u_courses_booked'. $tag_close .')';
			}
			if($course->participants_wait_list > 0) {
				$box_details .= ' / <b>'. $course->participants_wait_list .'</b> ('. $tag_open .'d2u_courses_wait_list'. $tag_close .')';
			}
			$box_details .= '<br></div>';
			$box_details .= '</div>';
		}

		if($course->redaxo_article > 0 || $course->url_external != "" || count($course->downloads) > 0) {
			if($course->redaxo_article > 0 || $course->url_external != "") {
				$box_details .= '<div class="col-12 course_row">';
				$box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_infolink'. $tag_close .':</b> ';
				if($course->redaxo_article > 0) {
					$article = rex_article::get($course->redaxo_article);
					$box_details .= '<a href="'. rex_getUrl($course->redaxo_article) .'">'. $article->getName() .'</a><br>';
				}
				if($course->url_external != "") {
					$box_details .= '<a href="'. $course->url_external .'" target="_blank">'. $course->url_external .'</a><br>';
				}
				$box_details .= '</div>';
				$box_details .= '</div>';
			}

			if(count($course->downloads) > 0) {
				$box_details .= '<div class="col-12 course_row">';
				$box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_downloads'. $tag_close .':</b> ';
				$box_details .= '<ul>';
				foreach($course->downloads as $document) {
					$rex_document = rex_media::get($document);
					$box_details .= '<li><a href="'. rex_url::media($document) .'" target="_blank">'. ($rex_document->getTitle() == "" ? $document : $rex_document->getTitle()) .'</a></li>';
				}
				$box_details .= '</ul>';
				$box_details .= '</div>';
				$box_details .= '</div>';
			}
		}

		if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			$box_details .= '<div class="col-12 course_row">';
			$box_details .= '<div class="course_box spacer_box">';
			if($course->room != "") {
				$box_details .= '<b>'. $tag_open .'d2u_courses_locations_room'. $tag_close .':</b><br />';
				$box_details .= $course->room;
				if($course->location->site_plan != "") {
					$box_details .= ' (<a href="'. rex_url::media($course->location->site_plan) .'" target="_blank">'. $tag_open .'d2u_courses_locations_site_plan'. $tag_close .'</a>)';
				}
				$box_details .= '<br><br>';
			}
			else {
				if($course->location->site_plan != "") {
					$box_details .= '<b><a href="'. rex_url::media($course->location->site_plan) .'" target="_blank">'. $tag_open .'d2u_courses_locations_site_plan'. $tag_close .'</a></b><br><br>';
				}
			}
			$box_details .= '<b>'. $tag_open .'d2u_courses_locations_city'. $tag_close .':</b><br />';
			$box_details .= $course->location->name;
			if($course->location->street != "") {
				$box_details .= '<br>'. $course->location->street;
			}
			if($course->location->zip_code != "" || $course->location->city != "") {
				$box_details .= '<br>'. $course->location->zip_code .' '. $course->location->city;
			}
			$box_details .= '</div>';
			$box_details .= '</div>';
		}

		if($course->registration_possible == "yes" || $course->registration_possible == "yes_number" || $course->registration_possible == "booked") {
			$box_details .= '<div class="col-12 course_row">';
			$box_details .= '<div class="course_box spacer_box add_cart" style="background-color: '. $course->category->color.'">';
			if(D2U_Courses\Cart::getCart()->hasCourse($course->course_id)) {
				$box_details .= '<a href="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart', rex_article::getSiteStartArticleId())) .'">'. $tag_open .'d2u_courses_cart_course_already_in'. $tag_close .' - '. $tag_open .'d2u_courses_cart_go_to'. $tag_close .'</a>';
			}
			else {
				$box_details .= '<form action="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart', rex_article::getSiteStartArticleId())) .'" method="post">';
				$box_details .= '<input type="hidden" name="course_id" value="'. $course->course_id .'">';
				$box_details .= '<input type="submit" class="add_cart" name="submit" value="'. $tag_open .'d2u_courses_cart_add'. $tag_close .'" style="background-color: '. $course->category->color.'">';
			}
			$box_details .= '</div>';
			$box_details .= '</div>';

			$box_details .= '</form>';
		}


		if($course->picture != "") {
			$box_picture = '<div class="col-12 col-md-6 course_row">';
			$box_picture .= '<div class="course_box spacer_box course_picture">';
			$box_picture .= '<img src="index.php?rex_media_type=d2u_helper_sm&rex_media_file='. $course->picture .'" alt="'. $course->name .'">';
			$box_picture .= '</div>';
			$box_picture .= '</div>';
		}

		$box_description = '';
		if($course->description != "") {
			$box_description .= '<div class="row" data-match-height>';
			$box_description .= '<div class="col-12'. ($box_details == '' ? ' col-md-6' : '') .' course_row">';
			$box_description .= '<div class="course_box spacer_box" data-height-watch>'. $course->description .'</div>';
			$box_description .= '</div>';
			if($box_details == '') {
				$box_description .= $box_picture;
			}
			$box_description .= '</div>';
		}

		// Output
		print $box_description;
		print '<div class="row" data-match-height>';
		if($box_details != '') {
			print '<div class="col-12 col-md-6 course_row">';
			print '<div class="row">';
			print $box_details;
			print '</div>';
			print '</div>';
			print $box_picture; // only if not included in $box_decription
		}

		if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			// Show Google map
			$show_map = "REX_VALUE[2]" == 'true' ? true : false;
			if($show_map) {
				print '<div class="col-12 course_row">';
				print '<div class="course_box spacer_box">';

				$map_type = "REX_VALUE[1]" == '' ? 'google' : "REX_VALUE[1]"; // Backward compatibility
				if($map_type == 'google') {
					$api_key = "";
					if(rex_config::has('d2u_helper', 'maps_key')) {
						$api_key = rex_config::get('d2u_helper', 'maps_key');
					}
					?>
					<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $api_key; ?>"></script> 
					<div id="map_canvas" style="display: block; width: 100%; height: 400px"></div> 
					<script> 
					<?php
						// If longitude and latitude is available: create map
						if($course->location->latitude != 0 && $course->location->longitude != 0) {
					?>
						var myLatlng = new google.maps.LatLng(<?php print $course->location->latitude .",". $course->location->longitude; ?>);
						var myOptions = {
							zoom: <?php echo $course->location->location_category->zoom_level; ?>,
							center: myLatlng,
							mapTypeId: google.maps.MapTypeId.ROADMAP
						};
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
					<?php
						}
						// Fallback on geocoding
						else {
					?>
						var geocoder = new google.maps.Geocoder();
						var map; 
						var address = "<?php print $course->location->street .', '. $course->location->zip_code .' '. $course->location->city; ?>";
						if (geocoder) {
							geocoder.geocode( { 'address': address}, function(results, status) {
								if (status === google.maps.GeocoderStatus.OK) {
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
						};
						map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
					<?php
						}
					?>
					</script>
				<?php
				}
				else if($map_type == 'osm' && rex_addon::get('osmproxy')->isAvailable()) {
					$map_id = rand();

					$leaflet_js_file = 'modules/04-2/leaflet.js';
					print '<script src="'. rex_url::addonAssets('d2u_helper', $leaflet_js_file) .'?buster='. filemtime(rex_path::addonAssets('d2u_helper', $leaflet_js_file)) .'"></script>' . PHP_EOL;

				?>
					<div id="map-<?php echo $map_id; ?>" style="width:100%;height:400px"></div>
					<script type="text/javascript" async="async">
						var map = L.map('map-<?php echo $map_id; ?>').setView([<?= $course->location->latitude .','. $course->location->longitude; ?>], <?php echo $course->location->location_category->zoom_level; ?>);
						L.tileLayer('/?osmtype=german&z={z}&x={x}&y={y}', {
							attribution: 'Map data &copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
						}).addTo(map);
						map.scrollWheelZoom.disable();
						var myIcon = L.icon({
							iconUrl: '<?php echo rex_url::addonAssets('d2u_helper', 'modules/04-2/marker-icon.png'); ?>',
							shadowUrl: '<?php echo rex_url::addonAssets('d2u_helper', 'modules/04-2/marker-shadow.png'); ?>',

							iconSize:     [25, 41], // size of the icon
							shadowSize:   [41, 41], // size of the shadow
							iconAnchor:   [12, 40], // point of the icon which will correspond to marker's location
							shadowAnchor: [13, 40], // the same for the shadow
							popupAnchor:  [0, -41]  // point from which the popup should open relative to the iconAnchor
						});
						var marker = L.marker([<?= $course->location->latitude .','. $course->location->longitude; ?>], {
							draggable: false,
							icon: myIcon
						}).addTo(map).bindPopup('<?php echo addslashes($course->location->name); ?>').openPopup();
					</script>
				<?php
				}
				else if(rex_addon::get('geolocation')->isAvailable()) {
					try {
						if(rex::isFrontend()) {
							\Geolocation\tools::echoAssetTags();
						}
			?>
				<script>
					Geolocation.default.positionColor = '<?= rex_config::get('d2u_helper', 'article_color_h'); ?>';

					// adjust zoom level
					Geolocation.Tools.Center = class extends Geolocation.Tools.Template{
						constructor ( ...args){
							super(args);
							this.zoom = this.zoomDefault = Geolocation.default.zoom;
							this.center = this.centerDefault = L.latLngBounds( Geolocation.default.bounds ).getCenter();
							return this;
						}
						setValue( data ){
							super.setValue( data );
							this.center = L.latLng( data[0] ) || this.centerDefault;
							this.zoom = data[1] || this.zoomDefault;
							this.radius = data[2];
							this.circle = null;
							if( data[2] ) {
								let options = Geolocation.default.styleCenter;
								options.color = data[3] || options.color;
								options.radius = this.radius;
								this.circle = L.circle( this.center, options );
							}
							if( this.map ) this.show( this.map );
							return this;
						}
						show( map ){
							super.show( map );
							map.setView( this.center, this.zoom );
							if( this.circle instanceof L.Circle ) this.circle.addTo( map );
							return this;
						}
						remove(){
							if( this.circle instanceof L.Circle ) this.circle.remove();
							super.remove();
							return this;
						}
						getCurrentBounds(){
							if( this.circle instanceof L.Circle ) {
								return this.radius ? this.circle.getBounds() : this.circle.getLatLng();
							}
							return this.center;
						}
					};
					Geolocation.tools.center = function(...args) { return new Geolocation.Tools.Center(args); };

					// add info box
					Geolocation.Tools.Infobox = class extends Geolocation.Tools.Position{
						setValue( dataset ) {
							// keine Koordinaten => Abbruch
							if( !dataset[0] ) return this;

							// GGf. Default-Farbe temporär ändern, normalen Position-Marker erzeugen
							let color = Geolocation.default.positionColor;
							Geolocation.default.positionColor = dataset[2] || Geolocation.default.positionColor;
							super.setValue(dataset[0]);
							Geolocation.default.positionColor = color;

							// Wenn angegeben: Text als Popup hinzufügen
							if( this.marker && dataset[1] ) {
								this.marker.bindPopup(dataset[1]);
								this.marker.on('click', function (e) {
									this.openPopup();
								});
							}
							return this;
						}
					};
					Geolocation.tools.infobox = function(...args) { return new Geolocation.Tools.Infobox(args); };
				</script>
			<?php
					}
					catch (Exception $e) {}

					$mapsetId = (int) 'REX_VALUE[9]';

					echo \Geolocation\mapset::take($mapsetId)
						->attributes('id', $mapsetId)
						->attributes('style', 'height:400px;width:100%;')
						->dataset('center', [[$course->location->latitude, $course->location->longitude], $course->location->location_category->zoom_level])
						->dataset('position', [$course->location->latitude, $course->location->longitude])
						->dataset('infobox',[[$course->location->latitude, $course->location->longitude], $course->location->name])
						->parse();
				}
				print '</div>';
				print '</div>';
			}
		}
		print '</div>'; // End row

		print '</div>';
		
		// Google JSON+LD code
		if($course->google_type == "course") {
			print $course->getJsonLdCourseCarouselCode();
		}
		else if($course->google_type == "event") {
			print $course->getJsonLdEventCode();
		}
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
				);
			}
		);
	</script>
<?php
}