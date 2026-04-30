<?php
/*
 * Get parameters
 */

use TobiasKrais\D2UCourses\Category;
use TobiasKrais\D2UCourses\Course;

$category_id = (int) 'REX_VALUE[1]';
$category = $category_id > 0 ? new \TobiasKrais\D2UCourses\Category($category_id) : false; /** @phpstan-ignore-line */
$courses = $category instanceof Category ? $category->getCourses(true) : []; /** @phpstan-ignore-line */
$box_per_line = 4 === (int) 'REX_VALUE[5]' ? 4 : 3; /** @phpstan-ignore-line */


/*
 * Function stuff
 */
if (!function_exists('printBoxModule26_3')) {
    /**
     * Print box.
     * @param string $title Box title
     * @param string $picture_filename mediapool picture filename
     * @param string $color Background color (Hex)
     * @param string $darkColor Dark mode background color (Hex)
     * @param string $url Link target url
     * @param int $box_per_line number boxes per line on large screens
     */
    function printBoxModule26_3($title, $picture_filename, $color, $darkColor, $url, $box_per_line = 3): void
    {
        echo '<div class="col-6 col-md-4 col-lg-'. (4 === $box_per_line ? '3' : '4') .' spacer">';
        echo '<div class="category_box" style="'. TobiasKrais\D2UCourses\FrontendHelper::getThemeColorStyle('background-color', $color, $darkColor, 'grey') .'" data-height-watch>';
        echo '<a href="'. rex_escape($url) .'">';
        echo '<div class="view">';
        if ('' !== $picture_filename) {
            echo '<img src="index.php?rex_media_type=d2u_helper_sm&rex_media_file='. rex_escape(rawurlencode($picture_filename)) .'" alt="'. rex_escape($title) .'">';
        } else {
            echo '<img src="'.	rex_addon::get('d2u_courses')->getAssetsUrl('empty_box.png') .'" alt="Placeholder">';
        }
        echo '</div>';
        echo '<div class="box_title">'. rex_escape($title) .'</div>';
        echo '</a>';
        echo '</div>';
        echo '</div>';
    }
}

if (rex::isBackend()) {
    echo 'Ausgabe Veranstaltungen der Kategorie <b>'. ($category instanceof Category ? $category->name : 'Kategorie muss ausgewählt werden') .'</b>.'; /** @phpstan-ignore-line */
} else {
    // Course list
    if ('active' === rex_config::get('d2u_courses', 'forward_single_course', 'inactive') && 1 === count($courses) && null === filter_input(INPUT_POST, 'course_search')) { /** @phpstan-ignore-line */
        foreach ($courses as $course) {
            header('Location: '. $course->getUrl());
            exit;
        }
    } elseif (count($courses) > 0) { /** @phpstan-ignore-line */
        if ($category instanceof Category) { /** @phpstan-ignore-line */
            if ('REX_VALUE[2]' === 'true') { /** @phpstan-ignore-line */
                echo '<div class="col-12 spacer"><div class="page_title_bg" style="'. TobiasKrais\D2UCourses\FrontendHelper::getThemeColorStyle('background-color', $category->color, $category->color_dark, '#5e5c64', true) .'">';
                echo '<h1 class="page_title">';
                if ($category->parent_category instanceof Category) {
                    echo $category->parent_category->name .': ';
                }
                echo $category->name;
                echo '</h1>';
                echo '</div></div>';
            }
            if ('REX_VALUE[3]' === 'true' && '' !== trim($category->description)) { /** @phpstan-ignore-line */
                echo '<div class="col-12 course_row spacer">';
                echo '<div class="course_box spacer_box">'. $category->description .'</div>';
                echo '</div>';
            }
        }
        foreach ($courses as $list_course) {
            if ($list_course instanceof Course) { /** @phpstan-ignore-line */
                $title = $list_course->name .'<br><small>'
                    . ('REX_VALUE[3]' === 'true' && '' !== $list_course->teaser ? $list_course->teaser .'<br>' : '') /** @phpstan-ignore-line */
                    . (new DateTime($list_course->date_start))->format('d.m.Y') .'</small>';
                printBoxModule26_3($title, $list_course->picture, $list_course->category->color, $list_course->category->color_dark, $list_course->getUrl(true), $box_per_line);
            }
        }
    }
    ?>
    <script>
        window.addEventListener('load', function () {
            document.querySelectorAll('[data-match-height]').forEach(function (container) {
                var items = Array.from(container.querySelectorAll('[data-height-watch]'));
                var maxHeight = 0;

                items.forEach(function (item) {
                    maxHeight = Math.max(maxHeight, item.clientHeight);
                });

                items.forEach(function (item) {
                    item.style.minHeight = (maxHeight + 1) + 'px';
                });
            });
        });
    </script>
<?php
}
