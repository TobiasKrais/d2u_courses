<?php

if (!class_exists(TobiasKrais\D2UCourses\Extension::class)) {
    require_once __DIR__ .'/lib/Extension.php';
}

$d2uCoursesAction = isset($d2uCoursesAction) && is_string($d2uCoursesAction) ? $d2uCoursesAction : '';
$d2uCoursesRequestedAction = isset($d2uCoursesRequestedAction) && is_string($d2uCoursesRequestedAction) ? $d2uCoursesRequestedAction : $d2uCoursesAction;

if ('' !== $d2uCoursesAction) {
    $supportScript = __DIR__ .'/lib/ExtensionSupport/'. $d2uCoursesAction .'.uninstall.php';
    if (file_exists($supportScript)) {
        require $supportScript;
    }
    if ('' !== $d2uCoursesRequestedAction) {
        rex_config::set('d2u_courses', TobiasKrais\D2UCourses\Extension::getConfigKey($d2uCoursesRequestedAction), TobiasKrais\D2UCourses\Extension::STATE_INACTIVE);
    }

    return;
}

TobiasKrais\D2UCourses\Extension::ensureConfigInitialized();
foreach (array_reverse(TobiasKrais\D2UCourses\Extension::getKeys()) as $extensionKey) {
    if (!TobiasKrais\D2UCourses\Extension::isActive($extensionKey)) {
        continue;
    }

    $supportScript = __DIR__ .'/lib/ExtensionSupport/'. $extensionKey .'.uninstall.php';
    if (file_exists($supportScript)) {
        require $supportScript;
    }
}

$sql = rex_sql::factory();

// Delete url schemes
if (\rex_addon::get('url')->isAvailable()) {
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'course_id';");
    $sql->setQuery('DELETE FROM '. \rex::getTablePrefix() ."url_generator_profile WHERE `namespace` = 'courses_category_id';");
}

// Delete views
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_categories');
$sql->setQuery('DROP VIEW IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_url_courses');

// Delete tables
$sql->setQuery('DROP TABLE IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_2_categories');
$sql->setQuery('DROP TABLE IF EXISTS ' . \rex::getTablePrefix() . 'd2u_courses_courses');

// Delete language replacements
if (!class_exists(TobiasKrais\D2UCourses\LangHelper::class)) {
    // Load class in case addon is deactivated
    require_once 'lib/LangHelper.php';
}
TobiasKrais\D2UCourses\LangHelper::factory()->uninstall();
