<?php
if(\rex::isBackend() && is_object(\rex::getUser())) {
	rex_perm::register('d2u_courses[schedule_categories]', rex_i18n::msg('d2u_courses_schedule_category_rights_schedule_categories'), rex_perm::OPTIONS);	
}

if(\rex::isBackend()) {
	rex_extension::register('ART_PRE_DELETED', 'rex_d2u_courses_schedule_categories_article_is_in_use');
	rex_extension::register('MEDIA_IS_IN_USE', 'rex_d2u_courses_schedule_categories_media_is_in_use');
}

/**
 * Checks if article is used by this addon
 * @param rex_extension_point<string> $ep Redaxo extension point
 * @return string Warning message as array
 * @throws rex_api_exception If article is used
 */
function rex_d2u_courses_schedule_categories_article_is_in_use(rex_extension_point $ep) {
	$warning = [];
	$params = $ep->getParams();
	$article_id = $params['id'];

	// Settings
	$addon = rex_addon::get("d2u_courses");
	if($addon->hasConfig("article_id_schedule_categories") && $addon->getConfig("article_id_schedule_categories") == $article_id) {
		$message = '<a href="index.php?page=d2u_courses/settings">'.
			 rex_i18n::msg('d2u_courses_rights_all') ." - ". rex_i18n::msg('d2u_helper_settings') . '</a>';
		if(!in_array($message, $warning)) {
			$warning[] = $message;
		}
	}

	if(count($warning) > 0) {
		throw new rex_api_exception(rex_i18n::msg('d2u_helper_rex_article_cannot_delete')."<ul><li>". implode("</li><li>", $warning) ."</li></ul>");
	}
	else {
		return "";
	}
}

/**
 * Checks if media is used by this addon
 * @param rex_extension_point<string> $ep Redaxo extension point
 * @return array<string> Warning message as array
 */
function rex_d2u_courses_schedule_categories_media_is_in_use(rex_extension_point $ep) {
	/** @var string[] $warning */
	$warning = $ep->getSubject();
	$params = $ep->getParams();
	$filename = addslashes($params['filename']);

	// Schedule categories
	$sql_schedule_categories = \rex_sql::factory();
	$sql_schedule_categories->setQuery('SELECT schedule_category_id, name FROM `' . \rex::getTablePrefix() . 'd2u_courses_schedule_categories` '
		.'WHERE picture = "'. $filename .'"');
	
	// Prepare warnings
	// Schedule categories
	for($i = 0; $i < $sql_schedule_categories->getRows(); $i++) {
		$message = rex_i18n::msg('d2u_courses_rights_all') ." - ". rex_i18n::msg('d2u_courses_schedule_categories') .': <a href="javascript:openPage(\'index.php?page=d2u_courses/schedule_categories&func=edit&entry_id='.
			$sql_schedule_categories->getValue('schedule_category_id') .'\')">'. $sql_schedule_categories->getValue('name') .'</a>';
		if(!in_array($message, $warning)) {
			$warning[] = $message;
		}
		$sql_schedule_categories->next();
    }

	return $warning;
}