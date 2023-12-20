<?php
use D2U_Courses\Course;
use D2U_Courses\CustomerBooking;

if (\rex::isBackend() && is_object(\rex::getUser())) {
    rex_perm::register('d2u_courses[customer_bookings]', rex_i18n::msg('d2u_courses_customer_bookings_rights_customer_bookings'), rex_perm::OPTIONS);
}

// Exports
$download = rex_request('download', 'string');
$course_id = (int) rex_request('course_id', 'int');
$export_types = ['nds', 'participants'];
$course = new Course($course_id);
if ($course instanceof Course && '' !== $download) {
    $course_bookings = CustomerBooking::getAllForCourse($course_id);
    if (count($course_bookings) > 0 && in_array($download, $export_types)) {
        $data = [];
        if ('nds' === $download) {
            $data[] = ['NAME', 'VORNAME', 'GEBURTSDATUM', 'GESCHLECHT', 'AHV_NR', 'NATIONALITAET', 'MUTTERSPRACHE', 'PLZ', 'ORT', 'LAND'];
            foreach ($course_bookings as $course_booking) {
                $data[] = [
                    $course_booking->familyName,
                    $course_booking->givenName,
                    (new DateTime($course_booking->birthDate))->format('d.m.Y'),
                    $course_booking->gender,
                    $course_booking->pension_insurance_id,
                    $course_booking->nationality,
                    $course_booking->zipCode,
                    $course_booking->city,
                    $course_booking->country];
            }
        }
        else if ('participants' === $download) {
            $data[] = ['Kurs ID', 'Nae Kurs', 'Name', 'Vorname', 'Straße ', 'Ort', 'Tel.', 'E-Mail', 'Alleine n. Hause', 'Gehaltsstufe'];
            foreach ($course_bookings as $course_booking) {
                $data[] = [
                    $course_booking->course_id,
                    $course->name,
                    $course_booking->familyName,
                    $course_booking->givenName,
                    $course_booking->street,
                    $course_booking->zipCode .' '. $course_booking->city,
                    $course_booking->emergency_number,
                    $course_booking->email,
                    $course_booking->kids_go_home_alone ? 'Yes' : 'No',
                    $course_booking->salery_level
                ];
            }
        }
            
        $filename = 'export_'. $download .'_course_'. ('' !== $course->course_number ? 'no_'. $course->course_number : 'id_'.$course->course_id) .'.csv';
            
        // Create file
        $fp = fopen('php://output', 'w');
        
        // set headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // write content to file
        foreach ($data as $row) {
            fputcsv($fp, $row, ';');
        }
        
        // close file
        fclose($fp);

        exit();
    }
}