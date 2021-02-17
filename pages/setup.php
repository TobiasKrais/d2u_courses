<?php
/*
 * Modules
 */
$d2u_module_manager = new D2UModuleManager(D2UCoursesModules::getModules(), "modules/", "d2u_courses");

// D2UModuleManager actions
$d2u_module_id = rex_request('d2u_module_id', 'string');
$paired_module = rex_request('pair_'. $d2u_module_id, 'int');
$function = rex_request('function', 'string');
if($d2u_module_id != "") {
	$d2u_module_manager->doActions($d2u_module_id, $function, $paired_module);
}

// D2UModuleManager show list
$d2u_module_manager->showManagerList();

// Import from Redaxo 4 D2U Kurse
$sql = rex_sql::factory();
$sql->setQuery("SHOW TABLES LIKE '". rex::getTablePrefix() ."d2u_kurse_kurse'");
$old_tables_available = $sql->getRows() > 0 ? TRUE : FALSE;
if(rex_request('import', 'string') == "d2u_courses" && $old_tables_available) {
	$sql->setQuery("DROP TABLE IF EXISTS `". rex::getTablePrefix() ."d2u_courses_location_categories`;		
			DROP TABLE IF EXISTS `". rex::getTablePrefix() ."d2u_courses_locations`;		
			DROP TABLE IF EXISTS `". rex::getTablePrefix() ."d2u_courses_target_groups`;		
			DROP TABLE IF EXISTS `". rex::getTablePrefix() ."d2u_courses_categories`;		
			DROP TABLE IF EXISTS `". rex::getTablePrefix() ."d2u_courses_schedule_categories`;
			DROP TABLE IF EXISTS `". rex::getTablePrefix() ."d2u_courses_courses`;
			TRUNCATE `". rex::getTablePrefix() ."d2u_courses_2_categories`;
			TRUNCATE `". rex::getTablePrefix() ."d2u_courses_2_schedule_categories`;
			TRUNCATE `". rex::getTablePrefix() ."d2u_courses_2_target_groups`;");

	// Relation tables
	$sql->setQuery("SELECT * FROM `". rex::getTablePrefix() ."d2u_kurse_kurse`;");
	$insert_query = "";
	for($i = 0; $i < $sql->getRows(); $i++) {
		$course_id = $sql->getValue("kurs_id");
		$category_id = $sql->getValue("kurskategorie_id");
		$secondary_category_ids = preg_grep('/^\s*$/s', explode("|", $sql->getValue("sekundaere_kurskategorie_ids")), PREG_GREP_INVERT);
		if(!in_array($category_id, $secondary_category_ids)) {
			$secondary_category_ids[] = $category_id;
		}
		$target_group_ids = preg_grep('/^\s*$/s', explode("|", $sql->getValue("zielgruppen_ids")), PREG_GREP_INVERT);
		$schedule_category_ids = preg_grep('/^\s*$/s', explode("|", $sql->getValue("terminkategorie_ids")), PREG_GREP_INVERT);

		foreach ($target_group_ids as $target_group_id) {
			$insert_query .= "INSERT INTO `". rex::getTablePrefix() ."d2u_courses_2_target_groups` SET `course_id` = ". $course_id .", `target_group_id` = ". $target_group_id .";". PHP_EOL;
		}

		foreach ($secondary_category_ids as $secondary_category_id) {
			$insert_query .= "INSERT INTO `". rex::getTablePrefix() ."d2u_courses_2_categories` SET `course_id` = ". $course_id .", `category_id` = ". $secondary_category_id .";". PHP_EOL;
		}

		foreach ($schedule_category_ids as $schedule_category_id) {
			$insert_query .= "INSERT INTO `". rex::getTablePrefix() ."d2u_courses_2_schedule_categories` SET `course_id` = ". $course_id .", `schedule_category_id` = ". $schedule_category_id .";". PHP_EOL;
		}

		$sql->next();
	}
	$sql->setQuery($insert_query);
	
	// substitute child target group ids by parent target group ids
	$sql->setQuery("SELECT c2t.course_id, c2t.target_group_id, eltern_zielgruppe_id, kufer_zielgruppe_name, kufer_kategorien_bezeichnungsstrukturen FROM `". rex::getTablePrefix() ."d2u_courses_2_target_groups` AS c2t "
		. "LEFT JOIN `". rex::getTablePrefix() ."d2u_kurse_zielgruppen` AS targets "
		. "ON c2t.target_group_id = targets.zielgruppe_id;");
	$insert_query = "TRUNCATE `". rex::getTablePrefix() ."d2u_courses_2_target_groups`;".PHP_EOL;
	// Transfer Kufer names to parents
	$parent_kufer_names = [];
	// Transfer Kufer categories to parents
	$parent_kufer_categories = [];
	for($i = 0; $i < $sql->getRows(); $i++) {
		$parent_target_id = $sql->getValue("eltern_zielgruppe_id");
		$target_id = $sql->getValue("c2t.target_group_id");
		$kufer_name = $sql->getValue("kufer_zielgruppe_name");
		$kufer_categories = array_map('trim', preg_grep('/^\s*$/s', explode(PHP_EOL, $sql->getValue("kufer_kategorien_bezeichnungsstrukturen")), PREG_GREP_INVERT));
		// Transfer Kufer names to parents
		if($kufer_name != "") {
			$parent_kufer_names[$parent_target_id] = $kufer_name;
		}
		// Transfer Kufer categories to parents
		if(count($kufer_categories) > 0) {
			if(!isset($parent_kufer_categories[$parent_target_id])) {
				$parent_kufer_categories[$parent_target_id] = [];
			}
			$parent_kufer_categories[$parent_target_id] = array_unique(array_merge($parent_kufer_categories[$parent_target_id], $kufer_categories));
		}
		
		$insert_query .= "REPLACE INTO `". rex::getTablePrefix() ."d2u_courses_2_target_groups` SET `course_id` = ". $sql->getValue("course_id") .", `target_group_id` = ". ($parent_target_id > 0 ? $parent_target_id : $target_id) .";" . PHP_EOL;
		$sql->next();
	}
	$sql->setQuery($insert_query);

	// Transfer Kufer names to parents
	foreach($parent_kufer_names as $parent_id => $parent_kufer_name) {
		$sql->setQuery("UPDATE ". rex::getTablePrefix() ."d2u_kurse_zielgruppen SET kufer_zielgruppe_name = '". $parent_kufer_name ."' WHERE zielgruppe_id = ". $parent_id);
	}
	// Transfer Kufer categories to parents
	foreach($parent_kufer_categories as $parent_id => $parent_kufer_category) {
		$sql->setQuery("UPDATE ". rex::getTablePrefix() ."d2u_kurse_zielgruppen SET kufer_kategorien_bezeichnungsstrukturen = '". implode(PHP_EOL, $parent_kufer_category) ."' WHERE zielgruppe_id = ". $parent_id);
	}

	// Normal tables
	$sql->setQuery("RENAME TABLE `". rex::getTablePrefix() ."d2u_kurse_orte_kategorien` TO `". rex::getTablePrefix() ."d2u_courses_location_categories`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_location_categories` CHANGE `ort_kategorie_id` `location_category_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_location_categories` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_location_categories` CHANGE `zoomstufe` `zoom_level` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_location_categories` ENGINE = InnoDB;");

	$sql->setQuery("RENAME TABLE `". rex::getTablePrefix() ."d2u_kurse_orte` TO `". rex::getTablePrefix() ."d2u_courses_locations`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `ort_id` `location_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `laengengrad` `longitude` decimal(14,10),;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `breitengrad` `latitude` decimal(14,10),;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `strasse` `street` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `plz` `zip_code` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `ort` `city` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `lageplan` `site_plan` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `ort_kategorie_id` `location_category_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` CHANGE `redaxo_benutzer` `redaxo_users` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_locations` ENGINE = InnoDB;");

	$sql->setQuery("RENAME TABLE `". rex::getTablePrefix() ."d2u_kurse_zielgruppen` TO `". rex::getTablePrefix() ."d2u_courses_target_groups`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_target_groups` CHANGE `zielgruppe_id` `target_group_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_target_groups` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_target_groups` CHANGE `kufer_zielgruppe_name` `kufer_target_group_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_target_groups` CHANGE `kufer_kategorien_bezeichnungsstrukturen` `kufer_categories` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		DELETE FROM `". rex::getTablePrefix() ."d2u_courses_target_groups` WHERE `eltern_zielgruppe_id` > 0;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_target_groups` DROP `eltern_zielgruppe_id`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_target_groups` ENGINE = InnoDB;");

	$sql->setQuery("RENAME TABLE `". rex::getTablePrefix() ."d2u_kurse_kategorien` TO `". rex::getTablePrefix() ."d2u_courses_categories`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` CHANGE `kurskategorie_id` `category_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` ADD `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `name`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` CHANGE `farbe` `color` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		UPDATE `". rex::getTablePrefix() ."d2u_courses_categories` SET `color` = CONCAT('#', `color`);
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` CHANGE `eltern_kurskategorie_id` `parent_category_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` ADD `priority` INT(10) NULL DEFAULT 0 AFTER `parent_category_id`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` CHANGE `kufer_kategorien_bezeichnungsstrukturen` `kufer_categories` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_categories` ENGINE = InnoDB;");

	$sql->setQuery("RENAME TABLE `". rex::getTablePrefix() ."d2u_kurse_terminkategorien` TO `". rex::getTablePrefix() ."d2u_courses_schedule_categories`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_schedule_categories` CHANGE `terminkategorie_id` `schedule_category_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_schedule_categories` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_schedule_categories` CHANGE `eltern_terminkategorie_id` `parent_schedule_category_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_schedule_categories` CHANGE `kufer_kategorien_bezeichnungsstrukturen` `kufer_categories` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_schedule_categories` ENGINE = InnoDB;");

	$sql->setQuery("RENAME TABLE `". rex::getTablePrefix() ."d2u_kurse_kurse` TO `". rex::getTablePrefix() ."d2u_courses_courses`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `kurs_id` `course_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `titel` `name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `beschreibung` `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `kursinfos` `details_course` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `meldeschluss` `details_deadline` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `alter` `details_age` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `bild` `picture` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `kosten` `price` decimal(5,2) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `kosten_erm` `price_discount` decimal(5,2) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `datum_von` `date_start` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `datum_bis` `date_end` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `uhrzeit` `time` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `kurskategorie_id` `category_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `ort_id` `location_id` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `raum` `room` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `teilnehmer_angemeldet` `participants_number` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `teilnehmer_max` `participants_max` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `teilnehmer_min` `participants_min` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `teilnehmer_warteliste` `participants_wait_list` INT(10) NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `anmeldung_moeglich` `registration_possible` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		UPDATE `". rex::getTablePrefix() ."d2u_courses_courses` SET `registration_possible` = 'yes' WHERE `registration_possible` = 'ja';
		UPDATE `". rex::getTablePrefix() ."d2u_courses_courses` SET `registration_possible` = 'no' WHERE `registration_possible` = 'nein';
		UPDATE `". rex::getTablePrefix() ."d2u_courses_courses` SET `registration_possible` = 'booked' WHERE `registration_possible` = 'ausgebucht';
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `status` `online_status` VARCHAR(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `url_extern` `url_external` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `redaxo_artikel` `redaxo_article` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `kursleiter` `instructor` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `kursnummer` `course_number` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `dokumente` `downloads` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` CHANGE `import` `import_type` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses`
			DROP `zielgruppen_ids`,
			DROP `sekundaere_kurskategorie_ids`,
			DROP `terminkategorie_ids`;
		ALTER TABLE `". rex::getTablePrefix() ."d2u_courses_courses` ENGINE = InnoDB;");
	
	$sql = rex_sql::factory();
	$sql->setQuery("SELECT `category_id` FROM `". rex::getTablePrefix() ."d2u_courses_categories` ORDER BY `parent_category_id`, `name`;");
	$update_query = "";
	for($i = 0; $i < $sql->getRows(); $i++) {
		$update_query .= "UPDATE `". rex::getTablePrefix() ."d2u_courses_categories` SET `priority` = ". ($i + 1) ." WHERE `category_id` = ". $sql->getValue('category_id') .";";
		$sql->next();
	}
	$sql->setQuery($update_query);
	
	$error = $sql->hasError() ? $sql->getError() : "";

	if($error != "") {
		print rex_view::error('Fehler beim Import: '. $error);
	}
	else {
		print rex_view::success('Daten aus Redaxo 4 D2U Kurse Addon importiert und alte Tabellen gelöscht');
	}
}
else if($old_tables_available) {
	print "<fieldset style='background-color: white; padding: 1em; border: 1px solid #dfe3e9;'>";
	print "<h2>Import aus Redaxo 4 D2U Kurse Addon</h2>";
	print "<p>Es wurden die D2U Kurse Addon Tabellen aus Redaxo 4 in der Datenbank gefunden."
	. "Sollen die Daten importiert werden und die alten Tabellen gelöscht werden? ACHTUNG: dabei werden alle vorhandenen Kurse in Redaxo 5 gelöscht und durch den Import ersetzt!</p>";
	print '<a href="'. rex_url::currentBackendPage(["import" => "d2u_courses"], FALSE) .'"><button class="btn btn-save">Import und vorhandene Daten löschen</button></a>';
	print "</fieldset>";
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
	<li>D2U Veranstaltungen Addon: <a href="https://kaltenbach-stiftung.de/" target="_blank">
		kaltenbach-stiftung.de</a>.</li>
</ul>
<h2>Support</h2>
<p>Fehlermeldungen bitte im Git Projekt unter
	<a href="https://github.com/TobiasKrais/d2u_courses/issues" target="_blank">https://github.com/TobiasKrais/d2u_courses/issues</a> melden.</p>
<fieldset style='background-color: white; padding: 1em; border: 1px solid #dfe3e9;'>
	<p style="margin-bottom: 0.5em;">Sag einfach Danke oder unterstütze die Weiterentwicklung durch eine Spende:</p>
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
		<input type="hidden" name="cmd" value="_s-xclick" />
		<input type="hidden" name="hosted_button_id" value="CB7B6QTLM76N6" />
		<input type="image" src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Spenden mit dem PayPal-Button" />
		<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1" />
	</form>
</fieldset>

<h2>Changelog</h2>
<p>3.2.1-DEV:</p>
<ul>
	<li>Modul 26-2 "Warenkorb": Es können nun mehrere Newsletter bestellt werden. Die angebotenen Newsletter können in den Einstellungen angepasst werden.</li>
	<li>Modul 26-2 "Warenkorb": Wenn das kufer_sync Plugin nicht installiert ist, kann die Frage nach dem Geschlecht der Kursteilnehmer optional dekativiert werden.</li>
	<li>locations Plugin: Redaxo Nutzer für die Rechtevergabe sind nun alphabetisch sortiert.</li>
	<li>In den Einstellungen gibt es neu ein Textfeld für einen Text in der Bestätigungsmail.</li>
	<li>Bestätigungsmail enthält nun auch den Presi, das Datum und die Uhrzeit der Veranstaltung.</li>
	<li>Bugfix: in einigen Fällen wurde der Redaxo Artikel in den Einstellungen nicht korrekt gespeichert.</li>
	<li>Bugfix: in einigen Fällen konnten mehrere gleiche Kategorien nebeneinander ausgegeben werden.</li>
</ul>
<p>3.2.0:</p>
<ul>
	<li>Bugfix beim Speichern von Kursen deren Gebühren über 999,- € lagen.</li>
	<li>Bugfix beim Update des Addons und aktiviertem Autoupdate der Beispielmodule gingen die Moduldaten verloren.</li>
	<li>Kurse können nun im JSON+LD Format für das Kurskarussell in der Google Suche oder für Events in Google Maps ausgegeben werden. Für die Nutzung von Events wird das locations Plugin installiert und aktiviert sein.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Bugfix. Kursdetailansicht zeigt nun korrektes Enddatum an.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Bugfix. Plugin wurde nicht immer korrekt auf Vorhandensein geprüft.</li>
	<li>Modul 26-1 "Ausgabe Veranstaltungen": Gibt Kurs nun auch im JSON+LD Format für Google aus.</li>
	<li>Modul 26-2 "Warenkorb": Anrede wird abgefragt.</li>
</ul>
<p>3.1.0:</p>
<ul>
	<li>Benötigt Redaxo >= 5.10, da die neue Klasse rex_version verwendet wird.</li>
	<li>Klonen von Kategorien möglich.</li>
	<li>Aktualisiert beim Speichern automatisch den search_it index.</li>
	<li>Bugfix: beim Reinstallieren des Addons wurden die Einstellungen wie lange ein Kurs angezeigt werden soll überschrieben.</li>
	<li>Bugfix: Fehler beim Speichern von Örtlichkeiten und deren Kategorien behoben.</li>
	<li>Bugfix kufer_import Plugin: Import berücksichtigt nun auch Einstellungen wie lange Kurse angezeigt werden.</li>
	<li>Elternkategorien können nun Großeltern- und Urgroßelternkategorien haben. Damit ist eine Kategorietiefe von 4 möglich.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" leitet Offlinekurse auf die Fehlerseite weiter.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" gibt im Plugin target_groups nun auch die Beschreibung einer Kategorie aus.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" hatte unter bestimmten Umständen Backendseite ins Frontend weitergeleitet.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" kann optional auf der Startseite News aus dem D2U News Addon und Linkboxen aus dem D2U Linkbox Addon einbinden.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" Veranstaltungslisten zeigen nun einheitlich das Datum mit Uhrzeit, bzw. als Ersatz die Kurzbeschreibung.</li>
	<li>Modul 26-2 "D2U Veranstaltungen - Warenkorb" kann jetzt die Teilnahmebedingungen anzeigen ohne dass ein AGB Artikel festgelegt wurde.</li>
	<li>Modul 26-2 "D2U Veranstaltungen - Warenkorb" kann jetzt statt dem Geburtsdatum nach dem Alter zu Veranstaltungsbeginn fragen.</li>
	<li>Modul 26-2 "D2U Veranstaltungen - Warenkorb" kann jetzt Wurzelkategorien auswählen, in denen Teilnehmer nach dem Alter gefragt werden.</li>
	<li>Modul 26-3 "D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen" hatte unter bestimmten Umständen Backendseite ins Frontend weitergeleitet.</li>
	<li>Backend: Beim online stellen einer Veranstaltung in der Veranstaltungsliste gab es beim Aufruf im Frontend einen Fatal Error, da der URL cache nicht neu generiert wurde.</li>
</ul>
<p>3.0.6:</p>
<ul>
	<li>Backend: Einstellungen und Setup Tabs rechts eingeordnet um sie vom Inhalt besser zu unterscheiden.</li>
	<li>Für jeden Kurs Option hinzugefügt, dass im Warenkorb nur nach Anzahl Teilnehmer gefragt wird, anstatt Teilnehmerdetails.</li>
	<li>Weitere Anpassungen an URL Addon Version 2.x.</li>
	<li>Bugfix: das Löschen eines Bildes im Medienpool wurde unter Umständen mit der Begründung verhindert, dass das Bild in Benutzung sei, obwohl das nicht der Fall war.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" mit neuer Option: Kursliste als Kacheln darstellen.</li>
	<li>Modul 26-1 "D2U Veranstaltungen - Ausgabe Veranstaltungen" fehlten in der Kursdetailansicht zwei div's.</li>
	<li>Modul 26-3 "D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen" hinzugefügt.</li>
</ul>
<p>3.0.5:</p>
<ul>
	<li>Fehler beim Speichern eines Kurses wenn noch keine Kategorie angelegt wurde behoben.</li>
	<li>Anpassungen an URL Addon Version 2.x.</li>
	<li>Listen im Backend werden jetzt nicht mehr in Seiten unterteilt.</li>
	<li>YRewrite Multidomain support.</li>
	<li>Modul 26-1 mit verbesserter Ausgabe des Buchungsstatus.</li>
	<li>Modul 26-2 Warenkorb: Beim Speichern des Warenkorbs wird nun auf die Kursseite weitergeleitet.</li>
	<li>Konvertierung der Datenbanktabellen zu utf8mb4.</li>
	<li>Option zur Eingabe einer Ferienpass Nummer in den Einstellungen / Warenkorb hinzugefügt.</li>
	<li>PHP Warning im Warenkorb entfernt.</li>
	<li>Bugfix schedule_categories Plugin: schedule_category::getAllParents() gibt nun auch Eltern mit Kindern zurück.</li>
</ul>
<p>3.0.4:</p>
<ul>
	<li>schedule_categories Plugin sortiert nun auch die Elternkategorien nach Namen.</li>
	<li>schedule_categories Plugin beherrscht nun auch Prioritäten.</li>
	<li>target_groups Plugin beherrscht nun auch Prioritäten.</li>
	<li>Bugfix: Prioritäten wurden beim Löschen nicht reorganisiert.</li>
	<li>Wenn Kategorien im Frontend nach Priorität sortiert angezeigt werden, werden sie nun auch im Backend und bei den Zielgruppen Kind Kategorien so sortiert.</li>
	<li>Bugfix kufer_sync Plugin: Beim target_groups Plugin wurden nicht alle Zielgruppen korrekt zugeordnet.</li>
	<li>Beim Löschen werden jetzt auch die Zuordnungen zu Kategorien, usw. gelöscht.</li>
	<li>Listennamen der Kategorien und Terminkategorien im Backend verbessert: Elternkategorien werden dem Namen vorangestellt.</li>
</ul>
<p>3.0.3:</p>
<ul>
	<li>Suche in Modul 26-1 bei aktiviertem locations Plugin: Karte nutzt nun Google Maps API Key aus D2U Helper Addon.</li>
	<li>locations Plugin: Bei der Eingabe einer Adresse gibt es jetzt die Möglichkeit eine Adresse direkt zu geocodieren wenn im D2U Helper Addon ein Google Maps API Key mit Zugriff auf die Geocoding API hinterlegt ist.
		Geocodierte Adressen werden auf der Karte schneller geladen und belasten das Budget des Google Kontos weniger.</li>
	<li>Bugfix locations Plugin: Der Klasse für die Ortskategorie hatten die Methoden für die Meta Tags gefehlt.</li>
	<li>Einstellung: Option zur Dauer der Anzeige der Kurse eingefügt: bis Anfang / Ende erster Tag, bis Anfang / Ende letzter Tag.</li>
	<li>Bugfix target_groups Plugin: Zielgruppenkinder URLs wurden mit rex_getUrl() nicht korrekt gebaut.</li>
	<li>Bugfix target_groups Plugin: Zielgruppenkinder können selbst keine Kinder mehr haben.</li>
</ul>
<p>3.0.2:</p>
<ul>
	<li>Bugfix: Deaktiviertes Addon zu deinstallieren führte zu fatal error.</li>
	<li>In den Einstellungen gibt es jetzt eine Option, eigene Übersetzungen in SProg dauerhaft zu erhalten.</li>
	<li>Warenkorb Modul: Anpassung an MultiNewsletter > 3.2.0.</li>
	<li>Ausgabe Modul: Detailverbesserungen in der Darstellung wenn nur die Beschreibung ausgefüllt ist.</li>
	<li>Bugfix: CronJob wird - wenn installiert - nicht immer richtig aktiviert.</li>
	<li>Bugfix: Import aus Redaxo 4 Datenbank verbessert.</li>
	<li>Kategorie kann nicht mehr sich selbst als Elternkategorie haben.</li>
	<li>Warenkorb Modul: CSS für Pflichtfelder intuitiver gestaltet.</li>
	<li>Warenkorb Modul: Wenn Kufer Sync Plugin deaktiviert ist, werden statistische Angaben ausgeblendet.</li>
	<li>Warenkorb Modul: Wenn keine Zahlungsoptionen ausgewählt sind, werden Zahlungsangaben nicht abgefragt.</li>
</ul>
<p>3.0.1:</p>
<ul>
	<li>Design Verbesserungen im Warenkorb Beispielmodul.</li>
	<li>Fehlerbehebung Zahlungsart bei Kufer Anmeldung.</li>
	<li>Bei der Anmeldung Minderjähriger kann nun gefragt werden, ob diese alleine nach Hause gehen dürfen.</li>
	<li>Verfügbare Zahlungsarten können in Einstellungen nach Bedarf ausgewählt werden.</li>
	<li>Priorität Feld für Kategorien hinzugefügt.</li>
	<li>Suche in Modul 26-1 integriert und Bugfix Suchfunktion.</li>
</ul>
<p>3.0.0:</p>
<ul>
	<li>Redaxo 5 Migration</li>
</ul>