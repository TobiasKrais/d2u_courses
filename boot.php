<?php

use TobiasKrais\D2UCourses\Extension;
use TobiasKrais\D2UCourses\FrontendHelper;

require_once __DIR__ .'/lib/deprecated_classes.php';

if (\rex::isBackend() && is_object(\rex::getUser())) {
    Extension::ensureConfigInitialized();

    $page = $this->getProperty('page');
    if (is_array($page)) {
        $this->setProperty('page', Extension::removeInactivePagesFromNavigation($page));
    }

    rex_perm::register('d2u_courses[]', rex_i18n::msg('d2u_courses_rights_all'));
    rex_perm::register('d2u_courses[courses_all]', rex_i18n::msg('d2u_courses_rights_courses_all'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[categories]', rex_i18n::msg('d2u_courses_rights_categories'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[locations]', rex_i18n::msg('d2u_courses_locations_rights_locations'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[schedule_categories]', rex_i18n::msg('d2u_courses_schedule_category_rights_schedule_categories'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[target_groups]', rex_i18n::msg('d2u_courses_target_groups_rights_target_groups'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[customer_bookings]', rex_i18n::msg('d2u_courses_customer_bookings_rights_customer_bookings'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[kufer_sync]', rex_i18n::msg('d2u_courses_kufer_sync_rights_kufer_sync'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[settings]', rex_i18n::msg('d2u_courses_rights_settings'), rex_perm::OPTIONS);

    Extension::hideInactiveBackendPages();
}

if (\rex::isBackend()) {
    rex_extension::register('ART_PRE_DELETED', rex_d2u_courses_article_is_in_use(...));
    rex_extension::register('MEDIA_IS_IN_USE', rex_d2u_courses_media_is_in_use(...));

    if (Extension::isActive('locations')) {
        require_once __DIR__ .'/lib/ExtensionSupport/locations.boot.php';
    }
    if (Extension::isActive('schedule_categories')) {
        require_once __DIR__ .'/lib/ExtensionSupport/schedule_categories.boot.php';
    }
    if (Extension::isActive('target_groups')) {
        require_once __DIR__ .'/lib/ExtensionSupport/target_groups.boot.php';
    }
    if (Extension::isActive('customer_bookings')) {
        require_once __DIR__ .'/lib/ExtensionSupport/customer_bookings.boot.php';
    }
    if (Extension::isActive('kufer_sync')) {
        require_once __DIR__ .'/lib/ExtensionSupport/kufer_sync.boot.php';
    }
}
else {
    rex_extension::register('D2U_HELPER_BREADCRUMBS', rex_d2u_courses_breadcrumbs(...));
}

/**
 * Checks if article is used by this addon.
 * @param rex_extension_point<string> $ep Redaxo extension point
 * @throws rex_api_exception If article is used
 * @return string Warning message
 */
function rex_d2u_courses_article_is_in_use(rex_extension_point $ep)
{
    $warning = [];
    $params = $ep->getParams();
    $article_id = $params['id'];

    // Courses
    $sql_courses = \rex_sql::factory();
    $sql_courses->setQuery('SELECT course_id, name FROM `' . \rex::getTablePrefix() . 'd2u_courses_courses` '
        .'WHERE redaxo_article = "'. $article_id .'"');

    // Prepare warnings
    // Courses
    for ($i = 0; $i < $sql_courses->getRows(); ++$i) {
        $message = '<a href="javascript:openPage(\'index.php?page=d2u_courses/courses&func=edit&entry_id='.
            $sql_courses->getValue('course_id') .'\')">'. rex_i18n::msg('d2u_courses_rights_all') .' - '. rex_i18n::msg('d2u_courses_courses') .': '. $sql_courses->getValue('name') .'</a>';
        if (!in_array($message, $warning, true)) {
            $warning[] = $message;
        }
        $sql_courses->next();
    }

    // Settings
    $addon = rex_addon::get('d2u_courses');
    if (($addon->hasConfig('article_id_courses') && (int) $addon->getConfig('article_id_courses') === $article_id) ||
        ($addon->hasConfig('article_id_shopping_cart') && (int) $addon->getConfig('article_id_shopping_cart') === $article_id) ||
        ($addon->hasConfig('article_id_conditions') && (int) $addon->getConfig('article_id_conditions') === $article_id) ||
        ($addon->hasConfig('article_id_terms_of_participation') && (int) $addon->getConfig('article_id_terms_of_participation') === $article_id)) {
        $message = '<a href="index.php?page=d2u_courses/settings/settings">'.
             rex_i18n::msg('d2u_courses_rights_all') .' - '. rex_i18n::msg('d2u_helper_settings') . '</a>';
        if (!in_array($message, $warning, true)) {
            $warning[] = $message;
        }
    }

    if (count($warning) > 0) {
        throw new rex_api_exception(rex_i18n::msg('d2u_helper_rex_article_cannot_delete').'<ul><li>'. implode('</li><li>', $warning) .'</li></ul>');
    }

    return '';

}

/**
 * Get breadcrumb part for courses.
 * @param rex_extension_point<array<string>> $ep Redaxo extension point
 * @return array<int,string> HTML formatted breadcrumb elements
 */
function rex_d2u_courses_breadcrumbs(rex_extension_point $ep)
{
    $params = $ep->getParams();
    $url_namespace = (string) $params['url_namespace'];
    $url_id = (int) $params['url_id'];

    $breadcrumbs = FrontendHelper::getBreadcrumbs($url_namespace, $url_id);
    if (count($breadcrumbs) === 0) {
        $breadcrumbs = $ep->getSubject();
    }

    return $breadcrumbs;
}

/**
 * Checks if media is used by this addon.
 * @param rex_extension_point<array<string>> $ep Redaxo extension point
 * @return array<string> Warning message as array
 */
function rex_d2u_courses_media_is_in_use(rex_extension_point $ep)
{
    $warning = $ep->getSubject();
    $params = $ep->getParams();
    $filename = (string) $params['filename'];
    $filenameLike = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filename) . '%';

    // Courses
    $sql_courses = \rex_sql::factory();
    $sql_courses->setQuery('SELECT course_id, `name` FROM `' . \rex::getTablePrefix() . 'd2u_courses_courses` '
        .'WHERE FIND_IN_SET(:filename, downloads) OR picture = :filename OR description LIKE :filenameLike', [':filename' => $filename, ':filenameLike' => $filenameLike]);

    // Categories
    $sql_categories = \rex_sql::factory();
    $sql_categories->setQuery('SELECT category_id, name FROM `' . \rex::getTablePrefix() . 'd2u_courses_categories` '
        .'WHERE picture = :filename', [':filename' => $filename]);

    // Prepare warnings
    // Courses
    for ($i = 0; $i < $sql_courses->getRows(); ++$i) {
        $message = rex_i18n::msg('d2u_courses_rights_all') .' - '. rex_i18n::msg('d2u_courses_courses') .': <a href="javascript:openPage(\'index.php?page=d2u_courses/course&func=edit&entry_id='.
            $sql_courses->getValue('course_id') .'\')">'. $sql_courses->getValue('name') .'</a>';
        if (!in_array($message, $warning, true)) {
            $warning[] = $message;
        }
        $sql_courses->next();
    }

    // Categories
    for ($i = 0; $i < $sql_categories->getRows(); ++$i) {
        $message = rex_i18n::msg('d2u_courses_rights_all') .' - '. rex_i18n::msg('d2u_helper_categories') .': <a href="javascript:openPage(\'index.php?page=d2u_courses/category&func=edit&entry_id='. $sql_categories->getValue('category_id') .'\')">'.
             $sql_categories->getValue('name') . '</a>';
        if (!in_array($message, $warning, true)) {
            $warning[] = $message;
        }
        $sql_categories->next();
    }

    return $warning;
}
