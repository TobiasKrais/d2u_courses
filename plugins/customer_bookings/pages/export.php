<?php
use D2U_Courses\Course;
use D2U_Courses\CustomerBooking;

/*
 * Export itself can be found in boot.php
 */

?>
<section class="rex-page-section">
    <div class="panel panel-default">
        <header class="panel-heading"><div class="panel-title"><?= rex_i18n::msg('d2u_courses_customer_bookings_export'); ?></div></header>
        <table class="table table-striped table-hover">
            <tbody>
                <?php
                    foreach (Course::getAll() as $course) {
                        $course_bookings = CustomerBooking::getAllForCourse($course->course_id);
                        if (count($course_bookings) > 0) {
                            echo '<tr>';
                            echo '<td class="rex-table-id">'. $course->course_id .'</td>';
                            echo '<td>'. $course->name .'</td>';
                            echo '<td class="rex-table-action"><a href="?page='. rex_be_controller::getCurrentPage() .'&course_id='. $course->course_id .'&download=nds" class="rex-link-expanded"><i class="rex-icon fa-cloud-download"></i> '. rex_i18n::msg('d2u_courses_customer_bookings_export_nds') .'</a></td>';
                            echo '<td class="rex-table-action"><a href="?page='. rex_be_controller::getCurrentPage() .'&course_id='. $course->course_id .'&download=participants" class="rex-link-expanded"><i class="rex-icon fa-cloud-download"></i> '. rex_i18n::msg('d2u_courses_customer_bookings_export_participants') .'</a></td>';
                            echo '</tr>';
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>
</section>