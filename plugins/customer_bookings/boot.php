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
if ('' !== $download) {
    $course_bookings = CustomerBooking::getAllForCourse($course_id);
    if (count($course_bookings) > 0 && in_array($download, $export_types, true)) {
        $data = [];

        // Create file
        $fp = fopen('php://output', 'w');
        if (false === $fp) {
            die('Cannot write export. Please contact your website administrator.');
        }

        // set headers
        $filename = 'export_'. $download .'_course_'. ('' !== $course->course_number ? 'no_'. $course->course_number : 'id_'.$course->course_id) .'.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        if ('nds' === $download) {
            $nationalities = ['CH', 'FL'];
            $data[] = ['PERSONENNUMMER', 'NAME', 'VORNAME', 'GEBURTSDATUM', 'GESCHLECHT', 'AHV_NR', 'PEID', 'NATIONALITAET', 'MUTTERSPRACHE', 'STRASSE', 'HAUSNUMMER', 'PLZ', 'ORT', 'LAND'];
            foreach ($course_bookings as $course_booking) {
                $data[] = [
                    '',
                    $course_booking->familyName,
                    $course_booking->givenName,
                    (new DateTime($course_booking->birthDate))->format('d.m.Y'),
                    $course_booking->gender,
                    str_replace(' ', '', $course_booking->pension_insurance_id),
                    '',
                    in_array($course_booking->nationality, $nationalities, true) ? $course_booking->nationality : 'ANDERE',
                    $course_booking->nativeLanguage,
                    preg_replace('/\s\d.*$/', '', $course_booking->street),
                    preg_replace('/^\D*/', '', $course_booking->street),
                    $course_booking->zipCode,
                    $course_booking->city,
                    '' !== $course_booking->country ? $course_booking->country : 'CH'];
            }
        }
        else {
            $data[] = ['Name Kurs', 'Name', 'Vorname', 'Straße ', 'Ort', 'Tel.', 'E-Mail', 'Darf alleine nach Hause', 'Gehaltsstufe', 'Bezahlt', ' Interne Bemerkungen', 'Warteliste'];
            $participant_counter = 0;
            foreach ($course_bookings as $course_booking) {
                $participant_counter++;
                $data[] = [
                    $course->name,
                    $course_booking->familyName,
                    $course_booking->givenName,
                    $course_booking->street,
                    $course_booking->zipCode .' '. $course_booking->city,
                    $course_booking->emergency_number,
                    $course_booking->email,
                    $course_booking->kids_go_home_alone ? 'Ja' : 'Nein',
                    $course_booking->salery_level,
                    $course_booking->paid ? 'Ja' : 'Nein',
                    $course_booking->remarks,
                    $course_booking->waitlist ? 'Ja' : 'Nein'
                ];
            }
        }
                
        // write content to file
        foreach ($data as $row) {
            fputcsv($fp, $row, ';');
        }

        // close file
        fclose($fp);

        exit();
    }
}