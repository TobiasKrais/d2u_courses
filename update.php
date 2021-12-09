<?php
// 3.0.1 Database update: set priorities
$sql = rex_sql::factory();
$sql->setQuery("SHOW COLUMNS FROM ". \rex::getTablePrefix() ."d2u_courses_categories LIKE 'priority';");
if($sql->getRows() == 0) {
	$sql->setQuery("ALTER TABLE `". \rex::getTablePrefix() ."d2u_courses_categories` ADD `priority` INT(10) NULL DEFAULT 0 AFTER `parent_category_id`;");
	$sql->setQuery("SELECT category_id FROM `". \rex::getTablePrefix() ."d2u_courses_categories` ORDER BY name;");
	$update_sql = rex_sql::factory();
	for($i = 1; $i <= $sql->getRows(); $i++) {
		$update_sql->setQuery("UPDATE `". \rex::getTablePrefix() ."d2u_courses_categories` SET priority = ". $i ." WHERE category_id = ". $sql->getValue('category_id') .";");
		$sql->next();
	}
}

// Update database to 3.0.5: convert updatedate to DATETIME
if (rex_version::compare($this->getVersion(), '3.0.5', '<')) {
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_categories ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;");
	$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_categories SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_categories DROP updatedate;");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_categories CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;");

	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_courses ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;");
	$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_courses SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_courses DROP updatedate;");
	$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_courses CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;");
}

// 3.0.1 Config Update
if(!rex_config::has('d2u_courses', 'payment_options')) {
	rex_config::set('d2u_courses', 'payment_options', ["bank_transfer", "cash", "direct_debit"]);
}

// use path relative to __DIR__ to get correct path in update temp dir
$this->includeFile(__DIR__.'/install.php');

// Update modules
if(class_exists('D2UModuleManager')) {
	$modules = [];
	$modules[] = new D2UModule("26-1",
		"D2U Veranstaltungen - Ausgabe Veranstaltungen",
		9);
	$modules[] = new D2UModule("26-2",
		"D2U Veranstaltungen - Warenkorb",
		6);
	$modules[] = new D2UModule("26-3",
		"D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen",
		3);
	$d2u_module_manager = new D2UModuleManager($modules, "", "d2u_courses");
	$d2u_module_manager->autoupdate();
}

// Update translations
if(!class_exists('d2u_courses_lang_helper')) {
	// Load class in case addon is deactivated
	require_once 'lib/d2u_courses_lang_helper.php';
}
d2u_courses_lang_helper::factory()->install();