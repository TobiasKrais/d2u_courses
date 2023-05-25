<?php

use D2U_Courses\Category;
use D2U_Courses\Course;
use D2U_Courses\Location;
use D2U_Courses\LocationCategory;
use D2U_Courses\ScheduleCategory;
use D2U_Courses\TargetGroup;

if (PHP_SESSION_NONE === session_status()) {
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

$startpage = 'REX_VALUE[1]';

$news_category_id = (int) 'REX_VALUE[5]';
$news = [];
if ($news_category_id > 0) { /** @phpstan-ignore-line */
    $new_category = new \D2U_News\Category($news_category_id, rex_clang::getCurrentId());
    $news = $new_category->getNews(true);
}
$linkbox_category_id = (int) 'REX_VALUE[6]';
$linkboxes = [];
if ($linkbox_category_id > 0) { /** @phpstan-ignore-line */
    $new_category = new \D2U_Linkbox\Category($linkbox_category_id, rex_clang::getCurrentId());
    $linkboxes = $new_category->getLinkboxes(true);
}
$box_per_line = 4 === (int) 'REX_VALUE[8]' ? 4 : 3; /** @phpstan-ignore-line */

$sprog = rex_addon::get('sprog');
$tag_open = $sprog->getConfig('wildcard_open_tag');
$tag_close = $sprog->getConfig('wildcard_close_tag');

$url_namespace = d2u_addon_frontend_helper::getUrlNamespace();
$url_id = d2u_addon_frontend_helper::getUrlId();

// Courses
if (filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0 || 'course_id' === $url_namespace) {
    $course_id = (rex_addon::get('url')->isAvailable() && $url_id > 0) ? $url_id : (int) filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

    if ($course_id > 0) {
        $course = new D2U_Courses\Course($course_id);
        // Redirect if object is not online
        if ('online' !== $course->online_status) {
            rex_redirect(rex_article::getNotfoundArticleId(), rex_clang::getCurrentId());
        }
    }
}
// Categories
elseif (filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0 || 'courses_category_id' === $url_namespace) {
    $category_id = (rex_addon::get('url')->isAvailable() && $url_id > 0) ? $url_id : (int) filter_input(INPUT_GET, 'courses_category_id', FILTER_VALIDATE_INT);

    if ($category_id > 0) {
        $category = new D2U_Courses\Category($category_id);
    }
}
// Locations
elseif (filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0 || 'location_id' === $url_namespace) {
    $location_id = (rex_addon::get('url')->isAvailable() && $url_id > 0) ? $url_id : (int) filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);

    if ($location_id > 0 && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
        $location = new D2U_Courses\Location($location_id);
    }
}
// Location category
elseif (filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0 || 'location_category_id' === $url_namespace) {
    $location_category_id = (rex_addon::get('url')->isAvailable() && $url_id > 0) ? $url_id : (int) filter_input(INPUT_GET, 'location_category_id', FILTER_VALIDATE_INT);

    if ($location_category_id > 0 && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
        $location_category = new D2U_Courses\LocationCategory($location_category_id);
    }
}
// Schedule category
elseif (filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0 || 'schedule_category_id' === $url_namespace) {
    $schedule_category_id = (rex_addon::get('url')->isAvailable() && $url_id > 0) ? $url_id : (int) filter_input(INPUT_GET, 'schedule_category_id', FILTER_VALIDATE_INT);

    if ($schedule_category_id > 0 && rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
        $schedule_category = new D2U_Courses\ScheduleCategory($schedule_category_id);
    }
}
// Target groups
elseif (filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0 || 'target_group_id' === $url_namespace) {
    $target_group_id = (rex_addon::get('url')->isAvailable() && $url_id > 0) ? $url_id : (int) filter_input(INPUT_GET, 'target_group_id', FILTER_VALIDATE_INT);

    if ($target_group_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
        $target_group = new D2U_Courses\TargetGroup($target_group_id);
    }
}
// Target group children
elseif (filter_input(INPUT_GET, 'target_group_child_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0 || 'target_group_child_id' === $url_namespace) {
    $target_group_child_id = (rex_addon::get('url')->isAvailable() && $url_id > 0) ? $url_id : (string) filter_input(INPUT_GET, 'target_group_child_id');

    if ($target_group_child_id > 0 && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
        $target_group = D2U_Courses\TargetGroup::getByChildID((string) $target_group_child_id);
    }
}

/*
 * Function stuff
 */
if (!function_exists('printBoxModule26_1')) {
    /**
     * Print box.
     * @param string $title Box title
     * @param string $picture_filename mediapool picture filename
     * @param string $color Background color (Hex)
     * @param string $url Link target url
     * @param int $number_columns can be 2, 3 or 4
     */
    function printBoxModule26_1($title, $picture_filename, $color, $url, $number_columns = 3): void
    {
        echo '<div class="col-6'. ($number_columns >= 3 ? ' col-md-4' : '') . (4 === $number_columns ? ' col-lg-3' : '') .' spacer">';
        echo '<div class="category_box" style="background-color: '. ('' === $color ? 'grey' : ''. $color) .'" data-height-watch>';
        echo '<a href="'. $url .'">';
        echo '<div class="view">';
        if ('' !== $picture_filename) {
            echo '<img src="index.php?rex_media_type=d2u_helper_sm&rex_media_file='. $picture_filename .'" alt="'. $title .'">';
        } else {
            echo '<img src="'.	rex_addon::get('d2u_courses')->getAssetsUrl('empty_box.png') .'" alt="Placeholder">';
        }
        echo '</div>';
        echo '<div class="box_title">'. $title .'</div>';
        echo '</a>';
        echo '</div>';
        echo '</div>';
    }
}

if (rex::isBackend()) {
    echo 'Ausgabe Veranstaltungen des D2U Veranstaltungen Addons'
        . ($new_category > 0 ? ' / mit News aus dem D2U News Addon' : '') /** @phpstan-ignore-line */
        . ($linkbox_category_id > 0 ? ' / mit Linxboxen aus dem D2U Linkbox Addon' : ''); /** @phpstan-ignore-line */
} else {
    // Course search box
    $show_search = 'REX_VALUE[3]' === 'true' ? true : false; /** @phpstan-ignore-line */
    if ($show_search) { /** @phpstan-ignore-line */
        echo '<div class="d-none d-sm-block col-sm-6 col-md-8 spacer d-print-none">&nbsp;</div>';
        echo '<div class="col-12 col-sm-6 col-md-4 spacer d-print-none">';
        echo '<div class="search_div">';
        echo '<form action="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_courses')) .'" method="post">'
            . '<input class="search_box" name="course_search" value="'. filter_input(INPUT_POST, 'course_search') .'" type="text">'
            . '<button class="search_button"><img src="'. rex_url::addonAssets('d2u_courses', 'lens.png').'"></button>'
            . '</form>';
        echo '</div>';
        echo '</div>';
    }

    $courses = [];
    // Get courses if search field was used
    if (null !== filter_input(INPUT_POST, 'course_search')) {
        $courses = D2U_Courses\Course::search((string) filter_input(INPUT_POST, 'course_search'));
    }
    // Deal with categories
    elseif ($category instanceof Category) {
        if (count($category->getChildren(true)) > 0) {
            // Are child categories available?
            echo '<div class="col-12 course-title"><div class="page_title_bg" style="background-color: '. $category->color .' !important">';
            echo '<h1 class="page_title">'. $category->name .'</h1>';
            echo '</div></div>';
            if ('' !== trim($category->description)) {
                echo '<div class="col-12 course_row spacer">';
                echo '<div class="course_box spacer_box">'. $category->description .'</div>';
                echo '</div>';
            }
            // Children
            foreach ($category->getChildren(true) as $child_category) {
                printBoxModule26_1($child_category->name, $child_category->picture, $child_category->color, $child_category->getUrl(), $box_per_line);
            }
        } else {
            // Otherwise get courses
            $courses = $category->getCourses(true);
        }
    }
    // Deal with location categories
    elseif (rex_plugin::get('d2u_courses', 'locations')->isAvailable() && $location_category instanceof LocationCategory) {
        echo '<div class="col-12 course-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'location_bg_color', '#41b23b') .' !important">';
        echo '<h1 class="page_title">'. $location_category->name .'</h1>';
        echo '</div></div>';
        foreach ($location_category->getLocations(true) as $current_location) {
            printBoxModule26_1($current_location->name, $current_location->picture, (string) rex_config::get('d2u_courses', 'location_bg_color', '#41b23b'), $current_location->getUrl(), $box_per_line);
        }
    }
    // Deal with locations
    elseif (rex_plugin::get('d2u_courses', 'locations')->isAvailable() && $location instanceof Location) {
        $courses = $location->getCourses(true);
    }
    // Deal with schedule categories
    elseif ($schedule_category instanceof ScheduleCategory) {
        if (count($schedule_category->getChildren(true)) > 0) {
            echo '<div class="col-12 course-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2') .' !important">';
            echo '<h1 class="page_title">'. $schedule_category->name .'</h1>';
            echo '</div></div>';
            // Children
            foreach ($schedule_category->getChildren(true) as $child_schedule_category) {
                printBoxModule26_1($child_schedule_category->name, $child_schedule_category->picture, (string) rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2'), $child_schedule_category->getUrl(), $box_per_line);
            }
        } else {
            $courses = $schedule_category->getCourses(true);
        }
    }
    // Deal with target groups
    elseif ($target_group instanceof TargetGroup) {
        if (count($target_group->getChildren()) > 0) {
            echo '<div class="col-12 course-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a') .' !important">';
            echo '<h1 class="page_title">'. $target_group->name .'</h1>';
            echo '</div></div>';
            // Children
            foreach ($target_group->getChildren() as $child_target_group) {
                printBoxModule26_1($child_target_group->name, $child_target_group->picture, (string) rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a'), $child_target_group->getUrl(), $box_per_line);
            }
        } else {
            $courses = $target_group->getCourses(true);
        }
    }
    // Nothing requested: selected startpage
    else {
        $tmp_box_per_line = $box_per_line;
        if (count($news) > 0 && false === $course) { /** @phpstan-ignore-line */
            --$tmp_box_per_line;
            echo '<div class="col-12 col-lg-8" data-match-height>';
            echo '<div class="row">';
        }
        if ('locations' === $startpage && rex_plugin::get('d2u_courses', 'locations')->isAvailable()) { /** @phpstan-ignore-line */
            $location_categories = D2U_Courses\LocationCategory::getAll(true);
            foreach ($location_categories as $current_location_category) {
                printBoxModule26_1($current_location_category->name, $current_location_category->picture, (string) rex_config::get('d2u_courses', 'location_bg_color', '#41b23b'), $current_location_category->getUrl(), $tmp_box_per_line);
            }
        } elseif ('schedule_categories' === $startpage && rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) { /** @phpstan-ignore-line */
            $schedule_categories = D2U_Courses\ScheduleCategory::getAllParents(true);
            foreach ($schedule_categories as $currecnt_schedule_category) {
                printBoxModule26_1($currecnt_schedule_category->name, $currecnt_schedule_category->picture, (string) rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2'), $currecnt_schedule_category->getUrl(), $tmp_box_per_line);
            }
        } elseif ('target_groups' === $startpage && rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) { /** @phpstan-ignore-line */
            $target_groups = D2U_Courses\TargetGroup::getAll(true);
            foreach ($target_groups as $current_target_group) {
                printBoxModule26_1($current_target_group->name, $current_target_group->picture, (string) rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a'), $current_target_group->getUrl(), $tmp_box_per_line);
            }
        } elseif (false === $course) {
            // Only show default if no course should be shown
            $categories = D2U_Courses\Category::getAllParents(true);
            foreach ($categories as $current_category) {
                printBoxModule26_1($current_category->name, $current_category->picture, $current_category->color, $current_category->getUrl(), $tmp_box_per_line);
            }
            if (count($linkboxes) > 0) { /** @phpstan-ignore-line */
                foreach ($linkboxes as $linkbox) {
                    printBoxModule26_1($linkbox->title, $linkbox->picture, $linkbox->background_color, $linkbox->getUrl(), $tmp_box_per_line);
                }
            }
            if (count($news) > 0) { /** @phpstan-ignore-line */
                echo '</div>';
                echo '</div>';
                echo '<div class="col-12 col-lg-4">';
                echo '<div class="row">';
                foreach ($news as $selected_news) {
                    echo '<div class="col-12 col-md-6 col-lg-12 spacer">';
                    echo '<div class="news_box" data-height-watch>';
                    $url = $selected_news->getUrl();
                    if ($url) {
                        echo '<a href="'. $selected_news->getUrl() .'">';
                    }
                    echo '<div class="box_title">'. $selected_news->name .'</div>';
                    echo '<div class="box_description">'. $selected_news->teaser .'</div>';
                    if ($url) {
                        echo '</a>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }
    }

    // Course list
    if ('active' === rex_config::get('d2u_courses', 'forward_single_course', 'inactive') && 1 === count($courses) && '' === filter_input(INPUT_POST, 'course_search')) {
        foreach ($courses as $current_course) {
            header('Location: '. $current_course->getUrl());
            exit;
        }
    } elseif (count($courses) > 0) {
        if (null !== filter_input(INPUT_POST, 'course_search')) {
            echo '<div class="col-12 course-title">';
            echo '<div class="search_title">';
            echo '<h1>'. $tag_open .'d2u_courses_search_results'. $tag_close .' "'. filter_input(INPUT_POST, 'course_search') .'":</h1>';
            echo '</div>';
            echo '</div>';
        } elseif ($target_group instanceof TargetGroup) {
            echo '<div class="col-12 course-title course-list-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'target_group_bg_color', '#fab20a') .' !important">';
            echo '<h1 class="page_title">';
            echo $target_group->name;
            echo '</h1>';
            echo '</div></div>';
            if ('' !== trim($target_group->description)) {
                echo '<div class="col-12 course_row spacer">';
                echo '<div class="course_box spacer_box">'. $target_group->description .'</div>';
                echo '</div>';
            }
        } elseif ($schedule_category instanceof ScheduleCategory) {
            echo '<div class="col-12 course-title course-list-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'schedule_category_bg_color', '#66ccc2') .' !important">';
            echo '<h1 class="page_title">';
            echo $schedule_category->name;
            echo '</h1>';
            echo '</div></div>';
        } elseif ($location instanceof Location) {
            echo '<div class="col-12 course-title course-list-title"><div class="page_title_bg" style="background-color: '. rex_config::get('d2u_courses', 'location_bg_color', '#41b23b') .' !important">';
            echo '<h1 class="page_title">'. ($location->location_category instanceof LocationCategory ? $location->location_category->name .': ' : '') . $location->name .'</h1>';
            echo '</div></div>';
        } elseif ($category instanceof Category) {
            echo '<div class="col-12 course-title course-list-title"><div class="page_title_bg" style="background-color: '. $category->color .' !important">';
            echo '<h1 class="page_title">';
            echo $category->name;
            echo '</h1>';
            echo '</div></div>';
            if ('' !== trim($category->description)) {
                echo '<div class="col-12 course_row spacer">';
                echo '<div class="course_box spacer_box">'. $category->description .'</div>';
                echo '</div>';
            }
        }

        $course_list_box_style = 'REX_VALUE[4]' === 'true' ? true : false;  /** @phpstan-ignore-line */
        foreach ($courses as $list_course) {
            if ($course_list_box_style) { /** @phpstan-ignore-line */
                $title = $list_course->name .'<br><small>'
                    . (new DateTime($list_course->date_start))->format('d.m.Y') .'</small>';
                printBoxModule26_1($title, $list_course->picture, $list_course->category instanceof Category ? $list_course->category->color : '#eee', $list_course->getUrl(true), $box_per_line);
            } else {
                echo '<div class="col-12">';
                $title = $list_course->name;
                if ('booked' === $list_course->registration_possible) {
                    $title .= ' - '. $tag_open .'d2u_courses_booked_complete'. $tag_close;
                }

                echo '<a href="'. $list_course->getUrl(true) .'" title="'. $title .'" class="course_row_a">';
                echo '<div class="row course_row" data-match-height>';

                echo '<div class="col-12 col-md-6 spacer_box">';
                echo '<div class="course_row_title" '.  ($list_course->category instanceof Category ? 'style="background-color:'. $list_course->category->color .'" ' : '') .'" data-height-watch>';
                echo $title;
                echo '</div>';
                echo '</div>';

                echo '<div class="col-8 col-md-4">';
                echo '<div class="course_box spacer_box" data-height-watch>';
                if ('' !== $list_course->date_start) {
                    echo (new DateTime($list_course->date_start))->format('d.m.Y')
                        .('' !== $list_course->date_end ? ' - '. (new DateTime($list_course->date_end))->format('d.m.Y') : '')
                        .('' !== $list_course->time ? '<br>'. $list_course->time .'' : '');
                } elseif ('' !== $list_course->time) {
                    echo $list_course->time;
                } else {
                    echo $list_course->teaser;
                }
                echo '</div>';
                echo '</div>';

                echo '<div class="col-4 col-md-2 course_row">';
                echo '<div class="course_box spacer_box" data-height-watch>';
                if (rex_plugin::get('d2u_courses', 'locations')->isAvailable() && $list_course->location instanceof Location && $list_course->location->location_category instanceof LocationCategory) {
                    echo $list_course->location->location_category->name;
                }
                if ('yes' === $list_course->registration_possible || 'yes_number' === $list_course->registration_possible) {
                    echo ' <div class="open"></div>';
                } elseif ('booked' === $list_course->registration_possible) {
                    echo ' <div class="closed"></div>';
                }
                echo '</div>';
                echo '</div>';

                echo '</div>';
                echo '</a>';
                echo '</div>';
            }
        }
    } elseif (null !== filter_input(INPUT_POST, 'course_search')) {
        echo '<div class="col-12">';
        echo '<div class="search_title">';
        echo '<h1>'. $tag_open .'d2u_courses_search_no_hits'. $tag_close .'</h1>';
        echo '<p>'. $tag_open .'d2u_courses_search_no_hits_text'. $tag_close .'</p>';
        echo '</div>';
        echo '</div>';
    }

    // Course
    if ($course instanceof Course) {
        echo '<div class="col-12">';
        echo '<div class="row">';

        echo '<div class="col-12 course-title_box">';
        echo '<div class="course_title"'. ($course->category instanceof Category ? ' style="background-color: '. $course->category->color .'"' : '') .'>';
        echo '<h1>'. $course->name;
        if ('' !== $course->course_number) {
            echo ' ('. $course->course_number .')';
        }
        if ('yes' === $course->registration_possible || 'yes_number' === $course->registration_possible) {
            echo ' <div class="open"></div>';
        } elseif ('booked' === $course->registration_possible) {
            echo ' <div class="closed"></div>';
        }
        echo '</h1>'. ('' !== $course->details_age ? '<div class="details_age">'. $course->details_age .'</div>' : '');
        echo '</div>';
        echo '</div>';

        echo '</div>'; // End row

        $box_details = '';

        if ('' !== $course->instructor) {
            $box_details .= '<div class="col-12 course_row">';
            $box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_instructor'. $tag_close .':</b> '. $course->instructor .'</div>';
            $box_details .= '</div>';
        }

        if ('' !== $course->date_start || '' !== $course->date_end || '' !== $course->time) {
            $box_details .= '<div class="col-12 course_row">';
            $box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_date'. $tag_close .':</b> ';
            if ('' !== $course->date_start || '' !== $course->date_end || '' !== $course->time) {
                $date = '';
                if ('' !== $course->date_start) {
                    $date .= (new DateTime($course->date_start))->format('d.m.Y');
                }
                if ('' !== $course->date_end) {
                    $date .= ' - '. (new DateTime($course->date_end))->format('d.m.Y');
                }
                if ('' !== $course->time) {
                    if ('' !== $date) {
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

        if ('' !== trim($course->details_deadline)) {
            $box_details .= '<div class="col-12 course_row">';
            $box_details .= '<div class="course_box spacer_box">';
            $box_details .= '<b>'. $tag_open .'d2u_courses_registration_deadline'. $tag_close .':</b> '. $course->details_deadline;
            $box_details .= '</div>';
            $box_details .= '</div>';
        }

        if ($course->price > 0 || ($course->price_salery_level && count($course->price_salery_level_details) > 0)) {
            $box_details .= '<div class="col-12 course_row">';
            $box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_fee'. $tag_close .':</b> ';
            if ($course->price_salery_level) {
                $box_details .= $tag_open. 'd2u_courses_price_salery_level_details'. $tag_close .'<br>';
                $box_details .= '<select class="participant" name="participant_price_salery_level_row_add"'. ($course->category instanceof Category ? ' style="border-color: '. $course->category->color .'"' : '') .'>';
                $counter_row_price_salery_level_details = 0;
                foreach ($course->price_salery_level_details as $key => $value) {
                    ++$counter_row_price_salery_level_details;
                    $box_details .= '<option value="'. $counter_row_price_salery_level_details .'">'. $key .': '. $value .'</option>';
                }
                $box_details .= '</select>';
            } else {
                $box_details .= number_format($course->price, 2, ',', '.') .' €';
                if ($course->price_discount > 0 && $course->price_discount < $course->price) {
                    $box_details .= ' ('. $tag_open .'d2u_courses_discount'. $tag_close .': '. number_format($course->price_discount, 2, ',', '.') .' €)';
                }
            }
            $box_details .= '</div>';
            $box_details .= '</div>';
        }

        if ('' !== trim($course->details_course)) {
            $box_details .= '<div class="col-12 course_row">';
            $box_details .= '<div class="course_box spacer_box">';
            $box_details .= '<b>'. $tag_open .'d2u_courses_details_course'. $tag_close .':</b> '. $course->details_course;
            $box_details .= '</div>';
            $box_details .= '</div>';
        }

        if ($course->participants_max > 0) {
            $box_details .= '<div class="col-12 course_row">';
            $box_details .= '<div class="course_box spacer_box">';
            $box_details .= '<b>'. $tag_open .'d2u_courses_participants'. $tag_close .': '. $course->participants_max .'</b> ('. $tag_open .'d2u_courses_max'. $tag_close .')';
            if ($course->participants_min > 0) {
                $box_details .= ' / <b>'. $course->participants_min .'</b> ('. $tag_open .'d2u_courses_min'. $tag_close .')';
            }
            if ($course->participants_number > 0) {
                $box_details .= ' / <b>'. $course->participants_number .'</b> ('. $tag_open .'d2u_courses_booked'. $tag_close .')';
            }
            if ($course->participants_wait_list > 0) {
                $box_details .= ' / <b>'. $course->participants_wait_list .'</b> ('. $tag_open .'d2u_courses_wait_list'. $tag_close .')';
            }
            $box_details .= '<br></div>';
            $box_details .= '</div>';
        }

        if ($course->redaxo_article > 0 || '' !== $course->url_external || count($course->downloads) > 0) {
            if ($course->redaxo_article > 0 || '' !== $course->url_external) {
                $box_details .= '<div class="col-12 course_row">';
                $box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_infolink'. $tag_close .':</b> ';
                if ($course->redaxo_article > 0) {
                    $article = rex_article::get($course->redaxo_article);
                    if ($article instanceof rex_article) {
                        $box_details .= '<a href="'. rex_getUrl($course->redaxo_article) .'">'. $article->getName() .'</a><br>';
                    }
                }
                if ('' !== $course->url_external) {
                    $box_details .= '<a href="'. $course->url_external .'" target="_blank">'. $course->url_external .'</a><br>';
                }
                $box_details .= '</div>';
                $box_details .= '</div>';
            }

            if (count($course->downloads) > 0) {
                $box_details .= '<div class="col-12 course_row">';
                $box_details .= '<div class="course_box spacer_box"><b>'. $tag_open .'d2u_courses_downloads'. $tag_close .':</b> ';
                $box_details .= '<ul>';
                foreach ($course->downloads as $document) {
                    $rex_document = rex_media::get($document);
                    $box_details .= '<li><a href="'. rex_url::media($document) .'" target="_blank">';
                    if ($rex_document instanceof rex_media && '' !== $rex_document->getTitle()) {
                        $box_details .= $rex_document->getTitle();
                    } else {
                        $box_details .= $document;
                    }
                    $box_details .= '</a></li>';
                }
                $box_details .= '</ul>';
                $box_details .= '</div>';
                $box_details .= '</div>';
            }
        }

        if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
            $box_details .= '<div class="col-12 course_row">';
            $box_details .= '<div class="course_box spacer_box">';
            if ('' !== $course->room) {
                $box_details .= '<b>'. $tag_open .'d2u_courses_locations_room'. $tag_close .':</b><br />';
                $box_details .= $course->room;
                if ($course->location instanceof Location && '' !== $course->location->site_plan) {
                    $box_details .= ' (<a href="'. rex_url::media($course->location->site_plan) .'" target="_blank">'. $tag_open .'d2u_courses_locations_site_plan'. $tag_close .'</a>)';
                }
                $box_details .= '<br><br>';
            } else {
                if ($course->location instanceof Location && '' !== $course->location->site_plan) {
                    $box_details .= '<b><a href="'. rex_url::media($course->location->site_plan) .'" target="_blank">'. $tag_open .'d2u_courses_locations_site_plan'. $tag_close .'</a></b><br><br>';
                }
            }
            if ($course->location instanceof Location) {
                $box_details .= '<b>'. $tag_open .'d2u_courses_locations_city'. $tag_close .':</b><br />';
                $box_details .= $course->location->name;
                if ('' !== $course->location->street) {
                    $box_details .= '<br>'. $course->location->street;
                }
                if ('' !== $course->location->zip_code || '' !== $course->location->city) {
                    $box_details .= '<br>'. $course->location->zip_code .' '. $course->location->city;
                }
            }
            $box_details .= '</div>';
            $box_details .= '</div>';
        }

        if ('yes' === $course->registration_possible || 'yes_number' === $course->registration_possible || 'booked' === $course->registration_possible) {
            $box_details .= '<div class="col-12 course_row">';
            $box_details .= '<div class="course_box spacer_box add_cart"'. ($course->category instanceof Category ? ' style="background-color: '. $course->category->color .'"' : '') .'>';
            if (D2U_Courses\Cart::getCart()->hasCourse($course->course_id)) {
                $box_details .= '<a href="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_shopping_cart', rex_article::getSiteStartArticleId())) .'">'. $tag_open .'d2u_courses_cart_course_already_in'. $tag_close .' - '. $tag_open .'d2u_courses_cart_go_to'. $tag_close .'</a>';
            } else {
                $box_details .= '<form action="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_shopping_cart', rex_article::getSiteStartArticleId())) .'" method="post">';
                $box_details .= '<input type="hidden" name="course_id" value="'. $course->course_id .'">';
                $box_details .= '<input type="submit" class="add_cart" name="submit" value="'. $tag_open .'d2u_courses_cart_add'. $tag_close .'"'. ($course->category instanceof Category ? ' style="background-color: '. $course->category->color .'"' : '') .'>';
            }
            $box_details .= '</div>';
            $box_details .= '</div>';

            $box_details .= '</form>';
        }

        $box_picture = '';
        if ('' !== $course->picture) {
            $box_picture = '<div class="col-12 col-md-6 course_row">';
            $box_picture .= '<div class="course_box spacer_box course_picture">';
            $box_picture .= '<img src="index.php?rex_media_type=d2u_helper_sm&rex_media_file='. $course->picture .'" alt="'. $course->name .'">';
            $box_picture .= '</div>';
            $box_picture .= '</div>';
        }

        $box_description = '';
        if ('' !== $course->description) {
            $box_description .= '<div class="row" data-match-height>';
            $box_description .= '<div class="col-12'. ('' === $box_details ? ' col-md-6' : '') .' course_row">';
            $box_description .= '<div class="course_box spacer_box" data-height-watch>'. $course->description .'</div>';
            $box_description .= '</div>';
            if ('' === $box_details) {
                $box_description .= $box_picture;
            }
            $box_description .= '</div>';
        }

        // Output
        echo $box_description;
        echo '<div class="row" data-match-height>';
        if ('' !== $box_details) {
            echo '<div class="col-12 col-md-6 course_row">';
            echo '<div class="row">';
            echo $box_details;
            echo '</div>';
            echo '</div>';
            echo $box_picture; // only if not included in $box_decription
        }

        if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
            // Show Google map
            $show_map = 'REX_VALUE[2]' === 'true' ? true : false; /** @phpstan-ignore-line */
            if ($show_map && $course->location instanceof Location) { /** @phpstan-ignore-line */
                echo '<div class="col-12 course_row">';
                echo '<div class="course_box spacer_box">';

                $map_type = 'REX_VALUE[7]' === '' ? 'google' : 'REX_VALUE[7]'; /** @phpstan-ignore-line */
                if ('google' === $map_type) { /** @phpstan-ignore-line */
                    $api_key = '';
                    if (rex_config::has('d2u_helper', 'maps_key')) {
                        $api_key = (string) rex_config::get('d2u_helper', 'maps_key');
                    }
                    ?>
					<script src="https://maps.googleapis.com/maps/api/js?key=<?= $api_key ?>"></script>
					<div id="map_canvas" style="display: block; width: 100%; height: 400px"></div>
					<script>
					<?php
                        // If longitude and latitude is available: create map
                        if ((0 > $course->location->latitude || 0 < $course->location->latitude) && (0 > $course->location->longitude || 0 < $course->location->longitude)) {
                    ?>
						var myLatlng = new google.maps.LatLng(<?= $course->location->latitude .','. $course->location->longitude ?>);
						var myOptions = {
							zoom: <?= $course->location->location_category instanceof LocationCategory ? $course->location->location_category->zoom_level : 10 ?>,
							center: myLatlng,
							mapTypeId: google.maps.MapTypeId.ROADMAP
						};
						var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

						var marker = new google.maps.Marker({
							position: myLatlng,
							map: map
						});

						var infowindow = new google.maps.InfoWindow({
							content: "<?= $course->location->name ?>",
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
						var address = "<?= $course->location->street .', '. $course->location->zip_code .' '. $course->location->city ?>";
						if (geocoder) {
							geocoder.geocode( { 'address': address}, function(results, status) {
								if (status === google.maps.GeocoderStatus.OK) {
									map.setCenter(results[0].geometry.location);
									var marker = new google.maps.Marker({
										map: map,
										position: results[0].geometry.location
									});
									var infowindow = new google.maps.InfoWindow({
										content: "<?= $course->location->name ?>",
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
							zoom: <?= $course->location->location_category instanceof LocationCategory ? $course->location->location_category->zoom_level : 10 ?>,
							mapTypeId: google.maps.MapTypeId.ROADMAP
						};
						map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
					<?php
                        }
                    ?>
					</script>
				<?php
                } elseif ('osm' === $map_type && rex_addon::get('osmproxy')->isAvailable()) {  /** @phpstan-ignore-line */
                    $map_id = random_int(0, getrandmax());

                    $leaflet_js_file = 'modules/04-2/leaflet.js';
                    echo '<script src="'. rex_url::addonAssets('d2u_helper', $leaflet_js_file) .'?buster='. filemtime(rex_path::addonAssets('d2u_helper', $leaflet_js_file)) .'"></script>' . PHP_EOL;

                ?>
					<div id="map-<?= $map_id ?>" style="width:100%;height:400px"></div>
					<script type="text/javascript" async="async">
						var map = L.map('map-<?= $map_id ?>').setView([<?= $course->location->latitude .','. $course->location->longitude ?>], <?= $course->location->location_category instanceof LocationCategory ? $course->location->location_category->zoom_level : 10 ?>);
						L.tileLayer('/?osmtype=german&z={z}&x={x}&y={y}', {
							attribution: 'Map data &copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
						}).addTo(map);
						map.scrollWheelZoom.disable();
						var myIcon = L.icon({
							iconUrl: '<?= rex_url::addonAssets('d2u_helper', 'modules/04-2/marker-icon.png') ?>',
							shadowUrl: '<?= rex_url::addonAssets('d2u_helper', 'modules/04-2/marker-shadow.png') ?>',

							iconSize:     [25, 41], // size of the icon
							shadowSize:   [41, 41], // size of the shadow
							iconAnchor:   [12, 40], // point of the icon which will correspond to marker's location
							shadowAnchor: [13, 40], // the same for the shadow
							popupAnchor:  [0, -41]  // point from which the popup should open relative to the iconAnchor
						});
						var marker = L.marker([<?= $course->location->latitude .','. $course->location->longitude ?>], {
							draggable: false,
							icon: myIcon
						}).addTo(map).bindPopup('<?= addslashes($course->location->name) ?>').openPopup();
					</script>
				<?php
                } elseif (rex_addon::get('geolocation')->isAvailable()) {
                    try {
                        if (rex::isFrontend()) {
                            if(rex_version::compare('2.0.0', rex_addon::get('geolocation')->getVersion(), '<=')) {
                                // Geolocation 2.x
                                \FriendsOfRedaxo\Geolocation\Tools::echoAssetTags();
                            }
                            else {
                                // Geolocation 1.x
                                \Geolocation\tools::echoAssetTags();
                            }
                        }
            ?>
				<script>
					Geolocation.default.positionColor = '<?= (string) rex_config::get('d2u_helper', 'article_color_h') ?>';

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
                    } catch (Exception $e) {
                    }

                    $mapsetId = (int) 'REX_VALUE[9]';

                    if(rex_version::compare('2.0.0', rex_addon::get('geolocation')->getVersion(), '<=')) {
                        // Geolocation 2.x
                        echo \FriendsOfRedaxo\Geolocation\Mapset::take($mapsetId)
                            ->attributes('id', (string) $mapsetId)
                            ->attributes('style', 'height:400px;width:100%;')
                            ->dataset('center', [[$course->location->latitude, $course->location->longitude], $course->location->location_category instanceof LocationCategory ? $course->location->location_category->zoom_level : 10])
                            ->dataset('position', [$course->location->latitude, $course->location->longitude])
                            ->dataset('infobox', [[$course->location->latitude, $course->location->longitude], $course->location->name])
                            ->parse();
                    }
                    else {
                        // Geolocation 1.x
                        echo \Geolocation\mapset::take($mapsetId)
                        ->attributes('id', (string) $mapsetId)
                        ->attributes('style', 'height:400px;width:100%;')
                        ->dataset('center', [[$course->location->latitude, $course->location->longitude], $course->location->location_category instanceof LocationCategory ? $course->location->location_category->zoom_level : 10])
                        ->dataset('position', [$course->location->latitude, $course->location->longitude])
                        ->dataset('infobox', [[$course->location->latitude, $course->location->longitude], $course->location->name])
                        ->parse();
                    }
                }
                echo '</div>';
                echo '</div>';
            }
        }
        echo '</div>'; // End row

        echo '</div>';

        // Google JSON+LD code
        if ('course' === $course->google_type) {
            echo $course->getJsonLdCourseCarouselCode();
        } elseif ('event' === $course->google_type) {
            echo $course->getJsonLdEventCode();
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
