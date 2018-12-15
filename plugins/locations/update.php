<?php
$sql = rex_sql::factory();
// Update database to 3.0.3
$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations CHANGE `latitude` `latitude` decimal(14,10) NULL DEFAULT NULL");
$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations CHANGE `longitude` `longitude` decimal(14,10) NULL DEFAULT NULL");