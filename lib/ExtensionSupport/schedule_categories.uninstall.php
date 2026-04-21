<?php

$sql = rex_sql::factory();

// Delete url schemes
if (\rex_addon::get('url')->isAvailable()) {
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'schedule_category_id';");

    // Delete URL Cache
    rex_delete_cache();
}

// Delete views
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_schedule_categories');

// Delete tables
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_schedule_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . rex::getTablePrefix() . 'd2u_courses_2_schedule_categories');
