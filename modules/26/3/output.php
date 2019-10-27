<?php
/*
 * Get parameters
 */
$category_id = "REX_VALUE[1]";
$category = $category_id > 0 ? new \D2U_Courses\Category($category_id) : FALSE;
$courses = $category->getCourses(TRUE);


$sprog = rex_addon::get("sprog");
$tag_open = $sprog->getConfig('wildcard_open_tag');
$tag_close = $sprog->getConfig('wildcard_close_tag');

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

// Course list
if(rex_config::get('d2u_courses', 'forward_single_course', 'inactive') == "active" && count($courses) == 1 && filter_input(INPUT_POST, 'course_search') == "") {
	foreach($courses as $course) {
		header('Location: '. $course->getURL());
		exit;
	}
}
else if(count($courses) > 0) {
	if($category !== FALSE) {
		if("REX_VALUE[2]" == 'true') {
			print '<div class="col-12 spacer"><div class="page_title_bg" style="background-color: '. $category->color .' !important">';
			print '<h1 class="page_title">';
			if($category->parent_category !== FALSE) {
				print $category->parent_category->name .": ";
			}
			print $category->name;
			print '</h1>';
			print '</div></div>';
		}
		if("REX_VALUE[3]" == 'true' && trim($category->description) != "") {
			print '<div class="col-12 course_row spacer">';
			print '<div class="course_box spacer_box">'. $category->description .'</div>';
			print '</div>';
		}
	}
	foreach($courses as $list_course) {
		$title = $list_course->name ."<br><small>"
			. ("REX_VALUE[3]" == 'true' && $list_course->teaser != "" ? $list_course->teaser ."<br>" : "")
			. formatCourseDate($list_course->date_start) ."</small>";
		printBox($title, $list_course->picture, $list_course->category->color, $list_course->getURL(TRUE));
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