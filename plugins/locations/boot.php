<?php

if (\rex::isBackend() && is_object(\rex::getUser())) {
    rex_perm::register('d2u_courses[locations]', rex_i18n::msg('d2u_courses_locations_rights_locations'), rex_perm::OPTIONS);
}

if (\rex::isBackend()) {
    rex_extension::register('ART_PRE_DELETED', 'rex_d2u_courses_locations_article_is_in_use');
    rex_extension::register('MEDIA_IS_IN_USE', 'rex_d2u_courses_locations_media_is_in_use');
}

/**
 * Checks if article is used by this addon.
 * @param rex_extension_point<string> $ep Redaxo extension point
 * @throws rex_api_exception If article is used
 * @return string Warning message as array
 */
function rex_d2u_courses_locations_article_is_in_use(rex_extension_point $ep)
{
    $warning = [];
    $params = $ep->getParams();
    $article_id = $params['id'];

    // Settings
    $addon = rex_addon::get('d2u_courses');
    if ($addon->hasConfig('article_id_locations') && $addon->getConfig('article_id_locations') == $article_id) {
        $message = '<a href="index.php?page=d2u_courses/settings">'.
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
 * Checks if media is used by this addon.
 * @param rex_extension_point<array<string>> $ep Redaxo extension point
 * @return array<string> Warning message as array
 */
function rex_d2u_courses_locations_media_is_in_use(rex_extension_point $ep)
{
    $warning = $ep->getSubject();
    $params = $ep->getParams();
    $filename = addslashes($params['filename']);

    // Locations
    $sql_locations = \rex_sql::factory();
    $sql_locations->setQuery('SELECT location_id, name FROM `' . \rex::getTablePrefix() . 'd2u_courses_locations` '
        .'WHERE picture = "'. $filename .'"');

    // Categories
    $sql_categories = \rex_sql::factory();
    $sql_categories->setQuery('SELECT location_category_id, name FROM `' . \rex::getTablePrefix() . 'd2u_courses_location_categories` '
        .'WHERE picture = "'. $filename .'"');

    // Prepare warnings
    // Courses
    for ($i = 0; $i < $sql_locations->getRows(); ++$i) {
        $message = rex_i18n::msg('d2u_courses_rights_all') .' - '. rex_i18n::msg('d2u_courses_locations') .': <a href="javascript:openPage(\'index.php?page=d2u_courses/location&func=edit&entry_id='.
            $sql_locations->getValue('location_id') .'\')">'. $sql_locations->getValue('name') .'</a>';
        if (!in_array($message, $warning, true)) {
            $warning[] = $message;
        }
        $sql_locations->next();
    }

    // Categories
    for ($i = 0; $i < $sql_categories->getRows(); ++$i) {
        $message = rex_i18n::msg('d2u_courses_rights_all') .' - '. rex_i18n::msg('d2u_courses_location_categories') .': <a href="javascript:openPage(\'index.php?page=d2u_courses/location_category&func=edit&entry_id='. $sql_categories->getValue('location_category_id') .'\')">'.
             $sql_categories->getValue('name') . '</a>';
        if (!in_array($message, $warning, true)) {
            $warning[] = $message;
        }
        $sql_categories->next();
    }

    return $warning;
}
