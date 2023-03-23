<?php

if (rex_version::compare($this->getVersion(), '3.0.5', '<')) { /** @phpstan-ignore-line */
    $sql = rex_sql::factory();
    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_location_categories ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;');
    $sql->setQuery('UPDATE '. \rex::getTablePrefix() .'d2u_courses_location_categories SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);');
    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_location_categories DROP updatedate;');
    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_location_categories CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;');

    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_locations ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;');
    $sql->setQuery('UPDATE '. \rex::getTablePrefix() .'d2u_courses_locations SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);');
    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_locations DROP updatedate;');
    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_locations CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;');
}

// use path relative to __DIR__ to get correct path in update temp dir
$this->includeFile(__DIR__.'/install.php'); /** @phpstan-ignore-line */
