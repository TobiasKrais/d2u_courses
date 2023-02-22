<?php

$sql = rex_sql::factory();

// 3.0.4 Database update
$sql->setQuery('SHOW COLUMNS FROM '. \rex::getTablePrefix() ."d2u_courses_target_groups LIKE 'priority';");
if (0 === (int) $sql->getRows()) {
    $sql->setQuery('ALTER TABLE `'. \rex::getTablePrefix() .'d2u_courses_target_groups` ADD `priority` INT(10) NULL DEFAULT 0 AFTER `picture`;');
    $sql->setQuery('SELECT target_group_id FROM `'. \rex::getTablePrefix() .'d2u_courses_target_groups` ORDER BY name;');
    $update_sql = rex_sql::factory();
    for ($i = 1; $i <= $sql->getRows(); ++$i) {
        $update_sql->setQuery('UPDATE `'. \rex::getTablePrefix() .'d2u_courses_target_groups` SET priority = '. $i .' WHERE target_group_id = '. $sql->getValue('target_group_id') .';');
        $sql->next();
    }
}

// use path relative to __DIR__ to get correct path in update temp dir
$this->includeFile(__DIR__.'/install.php'); /** @phpstan-ignore-line */
