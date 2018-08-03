<?php
// Update modules
if(class_exists(D2UModuleManager)) {
	$modules = [];
		$modules[] = new D2UModule("26-1",
			"D2U Veranstaltungen - Ausgabe Veranstaltungen",
			2);
		$modules[] = new D2UModule("26-2",
			"D2U Veranstaltungen - Warenkorb",
			1);
	$d2u_module_manager = new D2UModuleManager($modules);
	$d2u_module_manager->autoupdate();
}

// Update translations
if ($this->hasConfig('lang_replacements_install', 'false') == 'true') {
	d2u_helper_lang_helper::factory()->install();
}