<?php
// 3.0.1 Database update
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
// 3.0.1 Config Update
if(!rex_config::has('d2u_courses', 'payment_options')) {
	rex_config::set('d2u_courses', 'payment_options', ["bank_transfer", "cash", "direct_debit"]);
}

// Update modules
if(class_exists('D2UModuleManager')) {
	$modules = [];
		$modules[] = new D2UModule("26-1",
			"D2U Veranstaltungen - Ausgabe Veranstaltungen",
			2);
		$modules[] = new D2UModule("26-2",
			"D2U Veranstaltungen - Warenkorb",
			2);
	$d2u_module_manager = new D2UModuleManager($modules);
	$d2u_module_manager->autoupdate();
}

// Update translations
if ($this->hasConfig('lang_replacements_install', 'false') == 'true') {
	d2u_helper_lang_helper::factory()->install();
}