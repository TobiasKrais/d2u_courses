<?php

$sql = rex_sql::factory();

// Database cleanup
rex_sql_table::get(rex::getTable('d2u_courses_courses'))
    ->removeColumn('import_type')
    ->alter();
rex_sql_table::get(rex::getTable('d2u_courses_categories'))
    ->removeColumn('kufer_categories')
    ->alter();

if (rex_plugin::get('d2u_courses', 'locations')->isInstalled()) {
    rex_sql_table::get(rex::getTable('d2u_courses_locations'))
        ->removeColumn('kufer_location_id')
        ->alter();
}

if (rex_plugin::get('d2u_courses', 'schedule_categories')->isInstalled()) {
    rex_sql_table::get(rex::getTable('d2u_courses_schedule_categories'))
        ->removeColumn('kufer_categories')
        ->alter();
}

if (rex_plugin::get('d2u_courses', 'target_groups')->isInstalled()) {
    rex_sql_table::get(rex::getTable('d2u_courses_target_groups'))
        ->removeColumn('kufer_target_group_name')
        ->removeColumn('kufer_categories')
        ->alter();
}

// Delete CronJob if activated
if ('active' === rex_config::get('d2u_courses', 'kufer_sync_autoimport', 'inactive')) {
    if (!class_exists(TobiasKrais\D2UCourses\KuferSyncCronjob::class)) {
        // Load class in case addon is deactivated
        require_once 'lib/KuferSyncCronjob.php';
    }
    $kufer_cronjob = TobiasKrais\D2UCourses\KuferSyncCronjob::factory();
    if ($kufer_cronjob->isInstalled()) {
        $kufer_cronjob->delete();
    }
}
