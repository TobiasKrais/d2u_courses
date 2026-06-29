<?php

$sql = rex_sql::factory();

// Delete url schemes
if (\rex_addon::get('url')->isAvailable()) {
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'location_id';");
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'location_category_id';");
}

// Delete views
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_locations');
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_location_categories');

// Delete tables
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_location_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_locations');

// Remove columns only if they still exist to avoid errors on repeated uninstall
$coursesTable = \rex_sql_table::get(\rex::getTable('d2u_courses_courses'));
if ($coursesTable->exists()) {
    if ($coursesTable->hasColumn('location_id')) {
        $coursesTable->removeColumn('location_id');
    }
    if ($coursesTable->hasColumn('room')) {
        $coursesTable->removeColumn('room');
    }
    $coursesTable->alter();
}
