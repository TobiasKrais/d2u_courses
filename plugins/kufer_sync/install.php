<?php

rex_sql_table::get(rex::getTable('d2u_courses_courses'))
    ->ensureColumn(new \rex_sql_column('import_type', 'varchar(10)', true, null))
    ->alter();

rex_sql_table::get(rex::getTable('d2u_courses_categories'))
    ->ensureColumn(new \rex_sql_column('kufer_categories', 'text', true, null))
    ->ensureColumn(new \rex_sql_column('google_type', 'varchar(10)', true, null))
    ->alter();

if (rex_plugin::get('d2u_courses', 'target_groups')->isInstalled()) {
    rex_sql_table::get(rex::getTable('d2u_courses_target_groups'))
        ->ensureColumn(new \rex_sql_column('kufer_target_group_name', 'varchar(255)', true, null))
        ->ensureColumn(new \rex_sql_column('kufer_categories', 'text', true, null))
        ->alter();
}

if (rex_plugin::get('d2u_courses', 'schedule_categories')->isInstalled()) {
    rex_sql_table::get(rex::getTable('d2u_courses_schedule_categories'))
        ->ensureColumn(new \rex_sql_column('kufer_categories', 'text', true, null))
        ->alter();
}

if (rex_plugin::get('d2u_courses', 'locations')->isInstalled()) {
    rex_sql_table::get(rex::getTable('d2u_courses_locations'))
        ->ensureColumn(new \rex_sql_column('kufer_location_id', 'int(10)', true, null))
        ->alter();
}
