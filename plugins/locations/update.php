<?php
$sql = rex_sql::factory();
// Update database to 3.0.3
$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_locations SET `latitude` = '0' WHERE `latitude` = '';");
$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations CHANGE `latitude` `latitude` decimal(14,10) NULL DEFAULT NULL");
$sql->setQuery("UPDATE ". \rex::getTablePrefix() ."d2u_courses_locations SET `longitude` = '0' WHERE `longitude` = '';");
$sql->setQuery("ALTER TABLE ". \rex::getTablePrefix() ."d2u_courses_locations CHANGE `longitude` `longitude` decimal(14,10) NULL DEFAULT NULL");