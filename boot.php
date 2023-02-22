<?php

if (\rex::isBackend() && is_object(\rex::getUser())) {
    rex_perm::register('d2u_courses[]', rex_i18n::msg('d2u_courses_rights_all'));
    rex_perm::register('d2u_courses[courses_all]', rex_i18n::msg('d2u_courses_rights_courses_all'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[categories]', rex_i18n::msg('d2u_courses_rights_categories'), rex_perm::OPTIONS);
    rex_perm::register('d2u_courses[settings]', rex_i18n::msg('d2u_courses_rights_settings'), rex_perm::OPTIONS);
}

if (\rex::isBackend()) {
    rex_extension::register('ART_PRE_DELETED', 'rex_d2u_courses_article_is_in_use');
    rex_extension::register('MEDIA_IS_IN_USE', 'rex_d2u_courses_media_is_in_use');
}

/**
 * Checks if article is used by this addon.
 * @param rex_extension_point<string> $ep Redaxo extension point
 * @throws rex_api_exception If article is used
 * @return string Warning message as array
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
 * @param rex_extension_point<string> $ep Redaxo extension point
 * @return array<string> Warning message as array
 */
function rex_d2u_courses_media_is_in_use(rex_extension_point $ep)
{
    /** @var array<string> $warning */
    $warning = $ep->getSubject();
    $params = $ep->getParams();
    $filename = addslashes($params['filename']);

    // Courses
    $sql_courses = \rex_sql::factory();
    $sql_courses->setQuery('SELECT course_id, name FROM `' . \rex::getTablePrefix() . 'd2u_courses_courses` '
        .'WHERE FIND_IN_SET("'. $filename .'", downloads) OR picture = "'. $filename .'" OR description LIKE "%'. $filename .'%"');

    // Categories
    $sql_categories = \rex_sql::factory();
    $sql_categories->setQuery('SELECT category_id, name FROM `' . \rex::getTablePrefix() . 'd2u_courses_categories` '
        .'WHERE picture = "'. $filename .'"');

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
