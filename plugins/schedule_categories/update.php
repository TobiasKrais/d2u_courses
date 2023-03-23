<?php

$sql = rex_sql::factory();
// 3.0.4 Database update
$sql->setQuery('SHOW COLUMNS FROM '. \rex::getTablePrefix() ."d2u_courses_schedule_categories LIKE 'priority';");
if (0 === (int) $sql->getRows()) {
    $sql->setQuery('ALTER TABLE `'. \rex::getTablePrefix() .'d2u_courses_schedule_categories` ADD `priority` INT(10) NULL DEFAULT 0 AFTER `picture`;');
    $sql->setQuery('SELECT schedule_category_id FROM `'. \rex::getTablePrefix() .'d2u_courses_schedule_categories` ORDER BY name;');
    $update_sql = rex_sql::factory();
    for ($i = 1; $i <= $sql->getRows(); ++$i) {
        $update_sql->setQuery('UPDATE `'. \rex::getTablePrefix() .'d2u_courses_schedule_categories` SET priority = '. $i .' WHERE schedule_category_id = '. $sql->getValue('schedule_category_id') .';');
        $sql->next();
    }
}

// Update database to 3.0.5
if (rex_version::compare($this->getVersion(), '3.0.5', '<')) { /** @phpstan-ignore-line */
    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_schedule_categories ADD COLUMN `updatedate_new` DATETIME NOT NULL AFTER `updatedate`;');
    $sql->setQuery('UPDATE '. \rex::getTablePrefix() .'d2u_courses_schedule_categories SET `updatedate_new` = FROM_UNIXTIME(`updatedate`);');
    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_schedule_categories DROP updatedate;');
    $sql->setQuery('ALTER TABLE '. \rex::getTablePrefix() .'d2u_courses_schedule_categories CHANGE `updatedate_new` `updatedate` DATETIME NOT NULL;');
}

// use path relative to __DIR__ to get correct path in update temp dir
$this->includeFile(__DIR__.'/install.php'); /** @phpstan-ignore-line */
