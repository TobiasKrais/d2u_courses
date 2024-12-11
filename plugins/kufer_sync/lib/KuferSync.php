<?php

namespace TobiasKrais\D2UCourses;

use rex_config;
use rex_i18n;
use rex_plugin;
use rex_view;

use function array_key_exists;
use function count;
use function in_array;
use function strlen;

/**
 * @api
 * Reads kufer XML file and imports contents into database.
 */
class KuferSync
{
    /** @var string import name */
    public static string $import_name = 'KuferSQL';

    /**
     * Formats date form DD.MM.YYYY to YYYY-MM-DD.
     * @param string $date Date formatted DD.MM.YYYY
     * @return string reformatted date: YYYY-MM-DD
     */
    public static function formatDate($date)
    {
        $d = explode('.', $date);
        $unix = (int) mktime(0, 0, 0, (int) $d[1], (int) $d[0], (int) $d[2]);

        return date('Y-m-d', $unix);
    }

    /**
     * Imports data from Kufer SQL exported XML file.
     * If an error occurs, a message is printed using echo command.
     */
    public static function sync(): void
    {
        // Read XML file
        $context = stream_context_create(['http' => ['header' => 'Accept: application/xml']]);
        $xmlstring = file_get_contents((string) rex_config::get('d2u_courses', 'kufer_sync_xml_url', ''), false, $context);
        if (false === $xmlstring) {
            echo rex_view::error(rex_i18n::msg('d2u_courses_import_error_corrupt'));
            return;
        }
        else if ('' === $xmlstring) {
            echo rex_view::error(rex_i18n::msg('d2u_courses_import_error_empty'));
            return;
        }
        libxml_use_internal_errors(true);
        $kufer_courses = simplexml_load_string($xmlstring, null, LIBXML_NOCDATA);
        if (false === $kufer_courses) {
            echo rex_view::error(rex_i18n::msg('d2u_courses_import_error_corrupt'));
            echo rex_i18n::msg('d2u_courses_import_error_corrupt_details') .'<br>';
            foreach(libxml_get_errors() as $error) {
                echo rex_i18n::msg('d2u_courses_import_error_line') .' '. $error->line
                    .' '. rex_i18n::msg('d2u_courses_import_error_message') .' '. mb_convert_encoding($error->message, 'ISO-8859-1', 'UTF-8')
                    .'<br>';
            }
            return;
        }

        // Read current courses, in case they need to be updated - only imported ones
        $old_courses_all = Course::getAll();
        $old_courses = [];
        foreach ($old_courses_all as $course) {
            if ($course->import_type === self::$import_name) {
                $old_courses[$course->course_number] = $course;
            }
        }

        // Kufer Kurse hinzufügen oder aktualisieren
        $counter_new = 0;
        $counter_update = 0;

        $course_categories = Category::getAll(false);
        $schedule_categories = [];
        if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
            $schedule_categories = ScheduleCategory::getAll(false);
        }
        $target_groups = [];
        if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
            $target_groups = TargetGroup::getAll(false);
        }

        foreach ($kufer_courses->kurs as $kufer_course) {
            // Cancelled courses will not be imported ...
            if (isset($kufer_course->ausfall)) {
                continue;
            }
            // ... also courses that already started / ended (see settings
            if (isset($kufer_course->beginndat) && '' !== (string) $kufer_course->beginndat) {
                $beginndat = self::formatDate($kufer_course->beginndat);
                $endedat = self::formatDate($kufer_course->endedat);

                $config_show_time = rex_config::get('d2u_courses', 'show_time', 'day_one_start');
                if ('day_one_start' === $config_show_time && date('Y-m-d', (int) mktime(0, 0, 0, (int) date('m'), date('d') - 1, (int) date('Y'))) > $beginndat) {
                    continue;
                }
                if ('day_one_end' === $config_show_time && date('Y-m-d') > $beginndat) {
                    continue;
                }
                if ('day_x_start' === $config_show_time && date('Y-m-d', (int) mktime(0, 0, 0, (int) date('m'), date('d') - 1, (int) date('Y'))) > $endedat) {
                    continue;
                }
                if ('day_x_end' === $config_show_time && date('Y-m-d') > $endedat) {
                    continue;
                }
            }

            $new_course = Course::factory();
            // Was course imported last time?
            if (isset($kufer_course->knr)) {
                $kufer_course_number = (string) $kufer_course->knr;
                if (array_key_exists($kufer_course_number, $old_courses)) {
                    // Take the already imported one. It will be updated.
                    $new_course = $old_courses[$kufer_course_number];
                    // Remove it from old courses array. Remaining one will be deleted
                    unset($old_courses[$kufer_course_number]);
                }
            }

            // Import and update
            // Coures number
            if (isset($kufer_course->knr)) {
                $new_course->course_number = (string) $kufer_course->knr;
            }

            // Title 
            if (isset($kufer_course->titellang_bf) && '' !== (string) $kufer_course->titellang_bf) {
                $new_course->name = str_replace('"', "'", (string) $kufer_course->titellang_bf);
            } elseif (isset($kufer_course->haupttitel) && '' !== (string) $kufer_course->haupttitel) {
                $new_course->name = str_replace('"', "'", (string) $kufer_course->haupttitel);
            } elseif (isset($kufer_course->titelkurz) && '' !== (string) $kufer_course->titelkurz) {
                $new_course->name = str_replace('"', "'", (string) $kufer_course->titelkurz);
            } elseif (isset($kufer_course->titellang) && '' !== (string) $kufer_course->titellang) {
                $new_course->name = str_replace('"', "'", (string) $kufer_course->titellang);
            }

            // Description
            $new_course->description = ''; // Reset first
            if (isset($kufer_course->titellang) && '' !== (string) $kufer_course->titellang) {
                $new_course->description = '<p><b>'. (string) $kufer_course->titellang .'</b></p>';
            }
            if (isset($kufer_course->web_info) && '' !== (string) $kufer_course->web_info) {
                $new_course->description .= '<p>'. nl2br($kufer_course->web_info) .'</p>';
            } elseif (isset($kufer_course->inhalt) && '' !== (string) $kufer_course->inhalt) {
                $new_course->description .= '<p>'. nl2br($kufer_course->inhalt) .'</p>';
            }

            // Course details, e.g. material costs
            if (isset($kufer_course->material) && '' !== (string) $kufer_course->material) {
                $new_course->details_course = $kufer_course->material;
            }

            // Is registration for this course possible?
            if (isset($kufer_course->keinewebanmeldung) && 'F' === (string) $kufer_course->keinewebanmeldung) {
                $new_course->registration_possible = 'yes';
            } elseif (isset($kufer_course->keinewebanmeldung) && 'W' === (string) $kufer_course->keinewebanmeldung) {
                $new_course->registration_possible = 'no';
            }
            if (isset($kufer_course->tnmax) && isset($kufer_course->tnanmeldungen) && (int) $kufer_course->tnmax > 0 && (int) $kufer_course->tnanmeldungen >= (int) $kufer_course->tnmax) {
                $new_course->registration_possible = 'booked';
            }

            // Date
            if (isset($kufer_course->beginndat) && '' !== (string) $kufer_course->beginndat) {
                // Start
                $new_course->date_start = self::formatDate($kufer_course->beginndat);
                // End
                if (isset($kufer_course->endedat) && '' !== (string) $kufer_course->endedat && trim($kufer_course->beginndat) !== trim((string) $kufer_course->endedat)) {
                    $new_course->date_end = self::formatDate($kufer_course->endedat);
                    $new_course->time = '';
                } else {
                    $new_course->date_end = '';
                    // Time if course lasts one day
                    if (isset($kufer_course->beginnuhr) && '' !== (string) $kufer_course->beginnuhr) {
                        $new_course->time = $kufer_course->beginnuhr;
                        if (isset($kufer_course->endeuhr) && '' !== (string) $kufer_course->endeuhr) {
                            $new_course->time .= ' - '. $kufer_course->endeuhr;
                        }
                        $new_course->time .= ' Uhr';
                    }
                }
            }
            if (isset($kufer_course->termine) && $kufer_course->termine->termin->count() > 1) {
                // ... if course lasts several days, put infos into description
                $new_course->description .= '<p><b>Termine</b></p>';
                $new_course->description .= '<ul>';
                for ($i = 0; $i < $kufer_course->termine->termin->count(); ++$i) {
                    $new_course->description .= '<li>'. $kufer_course->termine->termin[$i]->tag;
                    if (null !== $kufer_course->termine->termin[$i]->zeitvon && '' !== (string) $kufer_course->termine->termin[$i]->zeitvon) {
                        $new_course->description .= ': '. $kufer_course->termine->termin[$i]->zeitvon;
                        if (null !== $kufer_course->termine->termin[$i]->zeitbis && '' !== (string) $kufer_course->termine->termin[$i]->zeitbis) {
                            $new_course->description .= ' - '. $kufer_course->termine->termin[$i]->zeitbis;
                        }
                        $new_course->description .= ' Uhr';
                    }
                    $new_course->description .= '</li>';
                }
                $new_course->description .= '</ul>';
            }

            // Deadline infos
            if (isset($kufer_course->anm_ende) && '' !== (string) $kufer_course->anm_ende) {
                $new_course->details_deadline = $kufer_course->anm_ende;
            }

            // Location: take from settings
            if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
                if (isset($kufer_course->ortid) && $kufer_course->ortid > 0) {
                    $new_course->location = Location::getByKuferLocationId((int) $kufer_course->ortid);
                }
                if (false === $new_course->location) {
                    $new_course->location = new Location((int) rex_config::get('d2u_courses', 'kufer_sync_default_location_id', 0));
                }

                // room name
                if (isset($kufer_course->ortraumname) && '' !== (string) $kufer_course->ortraumname) {
                    $new_course->room = str_replace('"', "'", (string) $kufer_course->ortraumname);
                }
            }

            // Price
            if (isset($kufer_course->gebnorm) && (float) str_replace(',', '.', $kufer_course->gebnorm) > 0) {
                $new_course->price = (float) str_replace(',', '.', $kufer_course->gebnorm);
            }
            if (isset($kufer_course->geberm) && (float) str_replace(',', '.', $kufer_course->geberm) > 0) {
                $new_course->price_discount = (float) str_replace(',', '.', $kufer_course->geberm);
            }
            if (isset($kufer_course->gebuehrergaenztext) && '' !== $kufer_course->gebuehrergaenztext) {
                $new_course->price_notes = $kufer_course->gebuehrergaenztext;
            }

            // Max. participants
            if (isset($kufer_course->tnmax) && $kufer_course->tnmax > 0) {
                $new_course->participants_max = (int) $kufer_course->tnmax; // int conversion necessary
            }
            // Min. Participants
            if (isset($kufer_course->tnmin) && $kufer_course->tnmin > 0) {
                $new_course->participants_min = (int) $kufer_course->tnmin; // int conversion necessary
            }
            // Number registrations
            if (isset($kufer_course->tnanmeldungen) && $kufer_course->tnanmeldungen > 0) {
                $new_course->participants_number = (int) $kufer_course->tnanmeldungen; // int conversion necessary
            } else {
                $new_course->participants_number = 0;
            }
            // Number wait list
            if (isset($kufer_course->tnwarteliste) && $kufer_course->tnwarteliste > 0) {
                $new_course->participants_wait_list = (int) $kufer_course->tnwarteliste; // int conversion necessary
            } else {
                $new_course->participants_wait_list = 0;
            }

            // Instructor
            if (isset($kufer_course->dozenten) && count($kufer_course->dozenten) > 0) {
                $new_course->instructor = ''; // reset first
                for ($i = 0; $i < $kufer_course->dozenten->dozent->count(); ++$i) {
                    $dozent = $kufer_course->dozenten->dozent[$i];
                    if (strlen($new_course->instructor) > 3) {
                        $new_course->instructor .= ', ';
                    }
                    if (property_exists($dozent, 'titel')) {
                        $new_course->instructor .= $dozent->titel .' ';
                    }
                    if (property_exists($dozent, 'vorname') && property_exists($dozent, 'name')) {
                        $new_course->instructor .= $dozent->vorname .' '. $dozent->name;
                    }
                }
            }

            // Categories: reset first
            $new_course->category = false;
            $new_course->secondary_category_ids = [];
            $new_course->schedule_category_ids = [];
            $new_course->target_group_ids = [];

            if (isset($kufer_course->kategorien) && count($kufer_course->kategorien) > 0) {
                // Get Kufer Kursbezeichnungen
                $kufer_kurs_bezeichnungstrukturen = [];
                for ($i = 0; $i < $kufer_course->kategorien->kategorie->count(); ++$i) {
                    $kufer_kurs_bezeichnungstrukturen[] = (string) $kufer_course->kategorien->kategorie[$i]->bezeichnungstruktur;
                }

                // Categories
                $kurskategorie_counter = 0;
                foreach ($course_categories as $course_category) {
                    foreach ($course_category->kufer_categories as $kufer_category) {
                        if (in_array($kufer_category, $kufer_kurs_bezeichnungstrukturen, true) && !in_array($course_category->category_id, $new_course->secondary_category_ids, true)) {
                            if (0 === $kurskategorie_counter) {
                                $new_course->category = $course_category;
                            }
                            $new_course->secondary_category_ids[] = $course_category->category_id;
                            ++$kurskategorie_counter;
                        }
                    }
                }

                // Schedule categories
                if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
                    foreach ($schedule_categories as $schedule_category) {
                        foreach ($schedule_category->kufer_categories as $kufer_schedule_category) {
                            if (in_array($kufer_schedule_category, $kufer_kurs_bezeichnungstrukturen, true) && !in_array($schedule_category->schedule_category_id, $new_course->schedule_category_ids, true)) {
                                $new_course->schedule_category_ids[] = $schedule_category->schedule_category_id;
                            }
                        }
                    }
                }

                // Target groups
                if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
                    foreach ($target_groups as $target_group) {
                        foreach ($target_group->kufer_categories as $kufer_target_group) {
                            if (in_array($kufer_target_group, $kufer_kurs_bezeichnungstrukturen, true) && !in_array($target_group->target_group_id, $new_course->target_group_ids, true)) {
                                $new_course->target_group_ids[] = $target_group->target_group_id;
                            }
                        }
                    }
                }
            }
            // In case default course category is still not set
            if (false === $new_course->category) {
                $new_course->category = new Category((int) rex_config::get('d2u_courses', 'kufer_sync_default_category_id', 0));
                $new_course->secondary_category_ids = [(int) rex_config::get('d2u_courses', 'kufer_sync_default_category_id', 0)];
            }

            // Target group
            if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
                if (isset($kufer_course->zielgruppe) && '' !== (string) $kufer_course->zielgruppe) {
                    foreach ($target_groups as $target_group) {
                        if ($target_group->kufer_target_group_name === (string) $kufer_course->zielgruppe && !in_array($target_group->target_group_id, $new_course->target_group_ids, true)) {
                            $new_course->target_group_ids[] = $target_group->target_group_id;
                        }
                    }
                }
            }

            // Status
            $new_course->online_status = 'online';

            // Importiert durch Kufer SQL
            $new_course->import_type = self::$import_name;

            // Set google type
            $new_course->google_type = $new_course->category->google_type;

            // Kurs speichern und Ergebnis loggen
            if (0 === $new_course->course_id) {
                ++$counter_new;
            } else {
                ++$counter_update;
            }
            $new_course->save();
        }

        // Delete cache to solve URL addon issues
        rex_delete_cache();

        echo rex_view::success('Kufer Import Ergebnis: ');
        echo '<ul>';
        echo '<li>'. $counter_new .' Kurse hinzugefügt. </li>';
        echo '<li>'. $counter_update .' Kurse aktualisiert. </li>';

        // Übrige importierte Kurse löschen
        $counter_delete = 0;
        foreach ($old_courses as $importierter_kurs) {
            $importierter_kurs->delete();
            ++$counter_delete;
        }

        echo '<li>'. $counter_delete .' Kurse gelöscht. </li>';
        echo '</ul>';
    }
}
