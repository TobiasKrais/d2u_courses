<?php
if(\rex::isBackend() && is_object(\rex::getUser())) {
	rex_perm::register('d2u_courses[target_groups]', rex_i18n::msg('d2u_courses_target_groups_rights_target_groups'), rex_perm::OPTIONS);	
}

if(\rex::isBackend()) {
	rex_extension::register('ART_PRE_DELETED', 'rex_d2u_courses_target_groups_article_is_in_use');
	rex_extension::register('MEDIA_IS_IN_USE', 'rex_d2u_courses_target_groups_media_is_in_use');
}

/**
 * Checks if article is used by this addon
 * @param rex_extension_point $ep Redaxo extension point
 * @return string Warning message as array
 * @throws rex_api_exception If article is used
 */
function rex_d2u_courses_target_groups_article_is_in_use(rex_extension_point $ep) {
	$warning = [];
	$params = $ep->getParams();
	$article_id = $params['id'];

	// Settings
	$addon = rex_addon::get("d2u_courses");
	if($addon->hasConfig("article_id_target_groups") && $addon->getConfig("article_id_target_groups") == $article_id) {
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
 * @param rex_extension_point $ep Redaxo extension point
 * @return string[] Warning message as array
 */
function rex_d2u_courses_target_groups_media_is_in_use(rex_extension_point $ep) {
	$warning = $ep->getSubject();
	$params = $ep->getParams();
	$filename = addslashes($params['filename']);

	// Target groups
	$sql_target_groups = \rex_sql::factory();
	$sql_target_groups->setQuery('SELECT target_group_id, name FROM `' . \rex::getTablePrefix() . 'd2u_courses_target_groups` '
		.'WHERE picture = "'. $filename .'"');
	
	// Prepare warnings
	// Target groups
	for($i = 0; $i < $sql_target_groups->getRows(); $i++) {
		$message = rex_i18n::msg('d2u_courses_rights_all') ." - ". rex_i18n::msg('d2u_courses_target_groups') .': <a href="javascript:openPage(\'index.php?page=d2u_courses/target_groups&func=edit&entry_id='.
			$sql_target_groups->getValue('target_group_id') .'\')">'. $sql_target_groups->getValue('name') .'</a>';
		if(!in_array($message, $warning)) {
			$warning[] = $message;
		}
		$sql_target_groups->next();
    }

	return $warning;
}