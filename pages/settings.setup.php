<?php
$showChangelogOnly = defined('D2U_COURSES_SHOW_CHANGELOG');

if (!$showChangelogOnly) {
/*
 * Modules
 */
$d2u_module_manager = new \TobiasKrais\D2UHelper\ModuleManager(TobiasKrais\D2UCourses\Module::getModules(), 'modules/', 'd2u_courses');

// \TobiasKrais\D2UHelper\ModuleManager actions
$d2u_module_id = rex_request('d2u_module_id', 'string');
$paired_module = rex_request('pair_'. $d2u_module_id, 'int');
$function = rex_request('function', 'string');
if ('' !== $d2u_module_id) {
    $d2u_module_manager->doActions($d2u_module_id, $function, $paired_module);
}

// \TobiasKrais\D2UHelper\ModuleManager show list
$d2u_module_manager->showManagerList();

// Import from Redaxo 4 D2U Kurse
$sql = rex_sql::factory();
$sql->setQuery("SHOW TABLES LIKE '". rex::getTablePrefix() ."d2u_kurse_kurse'");
$old_tables_available = $sql->getRows() > 0 ? true : false;
if ('d2u_courses' === rex_request('import', 'string') && $old_tables_available) {
    $sql->setQuery('DROP TABLE IF EXISTS `'. rex::getTablePrefix() .'d2u_courses_location_categories`;
			DROP TABLE IF EXISTS `'. rex::getTablePrefix() .'d2u_courses_locations`;
			DROP TABLE IF EXISTS `'. rex::getTablePrefix() .'d2u_courses_target_groups`;
			DROP TABLE IF EXISTS `'. rex::getTablePrefix() .'d2u_courses_categories`;
			DROP TABLE IF EXISTS `'. rex::getTablePrefix() .'d2u_courses_schedule_categories`;
			DROP TABLE IF EXISTS `'. rex::getTablePrefix() .'d2u_courses_courses`;
			TRUNCATE `'. rex::getTablePrefix() .'d2u_courses_2_categories`;
			TRUNCATE `'. rex::getTablePrefix() .'d2u_courses_2_schedule_categories`;
			TRUNCATE `'. rex::getTablePrefix() .'d2u_courses_2_target_groups`;');

    // Relation tables
    $sql->setQuery('SELECT * FROM `'. rex::getTablePrefix() .'d2u_kurse_kurse`;');
    $insert_query = '';
    for ($i = 0; $i < $sql->getRows(); ++$i) {
        $course_id = $sql->getValue('kurs_id');
        $category_id = $sql->getValue('kurskategorie_id');
        $secondary_category_ids_unfiltered = preg_grep('/^\s*$/s', explode('|', (string) $sql->getValue('sekundaere_kurskategorie_ids')), PREG_GREP_INVERT);
        $secondary_category_ids = is_array($secondary_category_ids_unfiltered) ? array_map('intval', $secondary_category_ids_unfiltered) : [];
        if (!in_array($category_id, $secondary_category_ids, true)) {
            $secondary_category_ids[] = $category_id;
        }
        $target_group_ids_unfiltered = preg_grep('/^\s*$/s', explode('|', (string) $sql->getValue('zielgruppen_ids')), PREG_GREP_INVERT);
        $target_group_ids = is_array($target_group_ids_unfiltered) ? array_map('intval', $target_group_ids_unfiltered) : [];
        $schedule_category_ids_unfiltered = preg_grep('/^\s*$/s', explode('|', (string) $sql->getValue('terminkategorie_ids')), PREG_GREP_INVERT);
        $schedule_category_ids = is_array($schedule_category_ids_unfiltered) ? array_map('intval', $schedule_category_ids_unfiltered) : [];

        foreach ($target_group_ids as $target_group_id) {
            $insert_query .= 'INSERT INTO `'. rex::getTablePrefix() .'d2u_courses_2_target_groups` SET `course_id` = '. $course_id .', `target_group_id` = '. $target_group_id .';'. PHP_EOL;
        }

        foreach ($secondary_category_ids as $secondary_category_id) {
            $insert_query .= 'INSERT INTO `'. rex::getTablePrefix() .'d2u_courses_2_categories` SET `course_id` = '. $course_id .', `category_id` = '. $secondary_category_id .';'. PHP_EOL;
        }

        foreach ($schedule_category_ids as $schedule_category_id) {
            $insert_query .= 'INSERT INTO `'. rex::getTablePrefix() .'d2u_courses_2_schedule_categories` SET `course_id` = '. $course_id .', `schedule_category_id` = '. $schedule_category_id .';'. PHP_EOL;
        }

        $sql->next();
    }
    $sql->setQuery($insert_query);

    // substitute child target group ids by parent target group ids
    $sql->setQuery('SELECT c2t.course_id, c2t.target_group_id, eltern_zielgruppe_id, kufer_zielgruppe_name, kufer_kategorien_bezeichnungsstrukturen FROM `'. rex::getTablePrefix() .'d2u_courses_2_target_groups` AS c2t '
        . 'LEFT JOIN `'. rex::getTablePrefix() .'d2u_kurse_zielgruppen` AS targets '
        . 'ON c2t.target_group_id = targets.zielgruppe_id;');
    $insert_query = 'TRUNCATE `'. rex::getTablePrefix() .'d2u_courses_2_target_groups`;'.PHP_EOL;
    // Transfer Kufer names to parents
    $parent_kufer_names = [];
    // Transfer Kufer categories to parents
    $parent_kufer_categories = [];
    for ($i = 0; $i < $sql->getRows(); ++$i) {
        $parent_target_id = $sql->getValue('eltern_zielgruppe_id');
        $target_id = $sql->getValue('c2t.target_group_id');
        $kufer_name = $sql->getValue('kufer_zielgruppe_name');
        $kufer_kategorien_bezeichnungsstrukturen = preg_grep('/^\s*$/s', explode(PHP_EOL, (string) $sql->getValue('kufer_kategorien_bezeichnungsstrukturen')), PREG_GREP_INVERT);
        $kufer_categories = is_array($kufer_kategorien_bezeichnungsstrukturen) ? array_map('trim', $kufer_kategorien_bezeichnungsstrukturen) : [];
        // Transfer Kufer names to parents
        if ('' !== $kufer_name) {
            $parent_kufer_names[$parent_target_id] = $kufer_name;
        }
        // Transfer Kufer categories to parents
        if (count($kufer_categories) > 0) {
            if (!isset($parent_kufer_categories[$parent_target_id])) {
                $parent_kufer_categories[$parent_target_id] = [];
            }
            $parent_kufer_categories[$parent_target_id] = array_unique(array_merge($parent_kufer_categories[$parent_target_id], $kufer_categories));
        }

        $insert_query .= 'REPLACE INTO `'. rex::getTablePrefix() .'d2u_courses_2_target_groups` SET `course_id` = '. $sql->getValue('course_id') .', `target_group_id` = '. ($parent_target_id > 0 ? $parent_target_id : $target_id) .';' . PHP_EOL;
        $sql->next();
    }
    $sql->setQuery($insert_query);

    // Transfer Kufer names to parents
    foreach ($parent_kufer_names as $parent_id => $parent_kufer_name) {
        $sql->setQuery('UPDATE '. rex::getTablePrefix() ."d2u_kurse_zielgruppen SET kufer_zielgruppe_name = '". $parent_kufer_name ."' WHERE zielgruppe_id = ". $parent_id);
    }
    // Transfer Kufer categories to parents
    foreach ($parent_kufer_categories as $parent_id => $parent_kufer_category) {
        $sql->setQuery('UPDATE '. rex::getTablePrefix() ."d2u_kurse_zielgruppen SET kufer_kategorien_bezeichnungsstrukturen = '". implode(PHP_EOL, $parent_kufer_category) ."' WHERE zielgruppe_id = ". $parent_id);
    }

    // Normal tables
    $sql->setQuery('RENAME TABLE `'. rex::getTablePrefix() .'d2u_kurse_orte_kategorien` TO `'. rex::getTablePrefix() .'d2u_courses_location_categories`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_location_categories` CHANGE `ort_kategorie_id` `location_category_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_location_categories` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_location_categories` CHANGE `zoomstufe` `zoom_level` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_location_categories` ENGINE = InnoDB;');

    $sql->setQuery('RENAME TABLE `'. rex::getTablePrefix() .'d2u_kurse_orte` TO `'. rex::getTablePrefix() .'d2u_courses_locations`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `ort_id` `location_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `laengengrad` `longitude` decimal(14,10),;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `breitengrad` `latitude` decimal(14,10),;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `strasse` `street` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `plz` `zip_code` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `ort` `city` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `lageplan` `site_plan` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `ort_kategorie_id` `location_category_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` CHANGE `redaxo_benutzer` `redaxo_users` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_locations` ENGINE = InnoDB;');

    $sql->setQuery('RENAME TABLE `'. rex::getTablePrefix() .'d2u_kurse_zielgruppen` TO `'. rex::getTablePrefix() .'d2u_courses_target_groups`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_target_groups` CHANGE `zielgruppe_id` `target_group_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_target_groups` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_target_groups` CHANGE `kufer_zielgruppe_name` `kufer_target_group_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_target_groups` CHANGE `kufer_kategorien_bezeichnungsstrukturen` `kufer_categories` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		DELETE FROM `'. rex::getTablePrefix() .'d2u_courses_target_groups` WHERE `eltern_zielgruppe_id` > 0;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_target_groups` DROP `eltern_zielgruppe_id`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_target_groups` ENGINE = InnoDB;');

    $sql->setQuery('RENAME TABLE `'. rex::getTablePrefix() .'d2u_kurse_kategorien` TO `'. rex::getTablePrefix() .'d2u_courses_categories`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_categories` CHANGE `kurskategorie_id` `category_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_categories` ADD `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `name`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_categories` CHANGE `farbe` `color` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_categories` ADD `color_dark` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `color`;
		UPDATE `'. rex::getTablePrefix() ."d2u_courses_categories` SET `color` = CONCAT('#', `color`);
		ALTER TABLE `". rex::getTablePrefix() .'d2u_courses_categories` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_categories` CHANGE `eltern_kurskategorie_id` `parent_category_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_categories` ADD `priority` INT(10) NULL DEFAULT 0 AFTER `parent_category_id`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_categories` CHANGE `kufer_kategorien_bezeichnungsstrukturen` `kufer_categories` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_categories` ENGINE = InnoDB;');

    $sql->setQuery('RENAME TABLE `'. rex::getTablePrefix() .'d2u_kurse_terminkategorien` TO `'. rex::getTablePrefix() .'d2u_courses_schedule_categories`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_schedule_categories` CHANGE `terminkategorie_id` `schedule_category_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_schedule_categories` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_schedule_categories` CHANGE `eltern_terminkategorie_id` `parent_schedule_category_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_schedule_categories` CHANGE `kufer_kategorien_bezeichnungsstrukturen` `kufer_categories` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_schedule_categories` ENGINE = InnoDB;');

    $sql->setQuery('RENAME TABLE `'. rex::getTablePrefix() .'d2u_kurse_kurse` TO `'. rex::getTablePrefix() .'d2u_courses_courses`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `kurs_id` `course_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `titel` `name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `beschreibung` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `kursinfos` `details_course` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `meldeschluss` `details_deadline` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `alter` `details_age` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `kosten` `price` decimal(5,2) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `kosten_erm` `price_discount` decimal(5,2) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `datum_von` `date_start` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `datum_bis` `date_end` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `uhrzeit` `time` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `kurskategorie_id` `category_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `ort_id` `location_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `raum` `room` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `teilnehmer_angemeldet` `participants_number` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `teilnehmer_max` `participants_max` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `teilnehmer_min` `participants_min` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `teilnehmer_warteliste` `participants_wait_list` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `anmeldung_moeglich` `registration_possible` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		UPDATE `'. rex::getTablePrefix() ."d2u_courses_courses` SET `registration_possible` = 'yes' WHERE `registration_possible` = 'ja';
		UPDATE `". rex::getTablePrefix() ."d2u_courses_courses` SET `registration_possible` = 'no' WHERE `registration_possible` = 'nein';
		UPDATE `". rex::getTablePrefix() ."d2u_courses_courses` SET `registration_possible` = 'booked' WHERE `registration_possible` = 'ausgebucht';
		ALTER TABLE `". rex::getTablePrefix() .'d2u_courses_courses` CHANGE `status` `online_status` VARCHAR(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `url_extern` `url_external` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `redaxo_artikel` `redaxo_article` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `kursleiter` `instructor` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `kursnummer` `course_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `dokumente` `downloads` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` CHANGE `import` `import_type` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses`
			DROP `zielgruppen_ids`,
			DROP `sekundaere_kurskategorie_ids`,
			DROP `terminkategorie_ids`;
		ALTER TABLE `'. rex::getTablePrefix() .'d2u_courses_courses` ENGINE = InnoDB;');

    $sql = rex_sql::factory();
    $sql->setQuery('SELECT `category_id` FROM `'. rex::getTablePrefix() .'d2u_courses_categories` ORDER BY `parent_category_id`, `name`;');
    $update_query = '';
    for ($i = 0; $i < $sql->getRows(); ++$i) {
        $update_query .= 'UPDATE `'. rex::getTablePrefix() .'d2u_courses_categories` SET `priority` = '. ($i + 1) .' WHERE `category_id` = '. $sql->getValue('category_id') .';';
        $sql->next();
    }
    $sql->setQuery($update_query);

    $error = $sql->hasError() ? $sql->getError() : '';

    if ('' !== $error) {
        echo rex_view::error('Fehler beim Import: '. $error);
    } else {
        echo rex_view::success('Daten aus Redaxo 4 D2U Kurse Addon importiert und alte Tabellen gelöscht');
    }
} elseif ($old_tables_available) {
    echo "<fieldset style='background-color: white; padding: 1em; border: 1px solid #dfe3e9;'>";
    echo '<h2>Import aus Redaxo 4 D2U Kurse Addon</h2>';
    echo '<p>Es wurden die D2U Kurse Addon Tabellen aus Redaxo 4 in der Datenbank gefunden.'
    . 'Sollen die Daten importiert werden und die alten Tabellen gelöscht werden? ACHTUNG: dabei werden alle vorhandenen Kurse in Redaxo 5 gelöscht und durch den Import ersetzt!</p>';
    echo '<a href="'. rex_url::currentBackendPage(['import' => 'd2u_courses'], false) .'"><button class="btn btn-save">Import und vorhandene Daten löschen</button></a>';
    echo '</fieldset>';
}

?>
<h2>Beispielseiten</h2>
<ul>
	<li>D2U Veranstaltungen Addon ohne Plugins: <a href="https://skiclub-loerrach.de/" target="_blank">
		skiclub-loerrach.de</a>.</li>
	<li>D2U Veranstaltungen Addon ohne Plugins: <a href="https://tuttikiesi.de/" target="_blank">
		tuttikiesi.de</a>.</li>
	<li>D2U Veranstaltungen Addon ohne Plugins: <a href="https://gingkoblatt.de/" target="_blank">
		Naturheilpraxis am Lehbühl</a>.</li>
	<li>D2U Veranstaltungen Addon ohne Plugins: <a href="https://frauenfasten.de/" target="_blank">
		Naturzentrum am Lehbühl</a>.</li>
</ul>
<h2>Support</h2>
<p>Fehlermeldungen bitte im Git Projekt unter
	<a href="https://github.com/TobiasKrais/d2u_courses/issues" target="_blank">https://github.com/TobiasKrais/d2u_courses/issues</a> melden.</p>

<?php } ?>
