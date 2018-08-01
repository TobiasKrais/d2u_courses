<?php
// save settings
if (filter_input(INPUT_POST, "btn_save") == 'save') {
	$settings = (array) rex_post('settings', 'array', array());

	// Linkmap Link and media needs special treatment
	$link_ids = filter_input_array(INPUT_POST, array('REX_INPUT_LINK'=> array('filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY)));

	$settings['article_id_courses'] = $link_ids["REX_INPUT_LINK"][1];
	$settings['article_id_shopping_cart'] = $link_ids["REX_INPUT_LINK"][2];
	$settings['article_id_conditions'] = $link_ids["REX_INPUT_LINK"][3];
	$settings['article_id_terms_of_participation'] = $link_ids["REX_INPUT_LINK"][4];

	if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
		$settings['article_id_locations'] = $link_ids["REX_INPUT_LINK"][5];
	}
	if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
		$settings['article_id_schedule_categories'] = $link_ids["REX_INPUT_LINK"][6];
	}
	if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
		$settings['article_id_target_groups'] = $link_ids["REX_INPUT_LINK"][7];
	}

	// Checkbox also need special treatment if empty
	$settings['forward_single_course'] = array_key_exists('forward_single_course', $settings) ? "active" : "inactive";
	if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
		$settings['kufer_sync_autoimport'] = array_key_exists('kufer_sync_autoimport', $settings) ? "active" : "inactive";
	}
	if(\rex_addon::get('multinewsletter')->isAvailable()) {
		$settings['multinewsletter_subscribe'] = array_key_exists('multinewsletter_subscribe', $settings) ? "show" : "hide";
	}
	
	// Save settings
	if(rex_config::set("d2u_courses", $settings)) {
		echo rex_view::success(rex_i18n::msg('form_saved'));

		// Update url schemes
		if(\rex_addon::get('url')->isAvailable()) {
			d2u_addon_backend_helper::update_url_scheme(\rex::getTablePrefix() ."d2u_courses_url_courses", $settings['article_id_courses']);
			d2u_addon_backend_helper::update_url_scheme(\rex::getTablePrefix() ."d2u_courses_url_categories", $settings['article_id_courses']);
			if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
				d2u_addon_backend_helper::update_url_scheme(\rex::getTablePrefix() ."d2u_courses_url_location_categories", $settings['article_id_locations']);
				d2u_addon_backend_helper::update_url_scheme(\rex::getTablePrefix() ."d2u_courses_url_locations", $settings['article_id_locations']);
			}
			if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
				d2u_addon_backend_helper::update_url_scheme(\rex::getTablePrefix() ."d2u_courses_url_schedule_categories", $settings['article_id_schedule_categories']);
			}
			if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
				d2u_addon_backend_helper::update_url_scheme(\rex::getTablePrefix() ."d2u_courses_url_target_groups", $settings['article_id_target_groups']);
				d2u_addon_backend_helper::update_url_scheme(\rex::getTablePrefix() ."d2u_courses_url_target_group_childs", $settings['article_id_target_groups']);
			}
			UrlGenerator::generatePathFile([]);
		}
		
		// Install / update language replacements
		d2u_courses_lang_helper::factory()->install();
		
		// Install / remove Cronjob
		if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			if($this->getConfig('kufer_sync_autoimport') == 'active') {
				if(!kufer_sync_backend_helper::autoimportIsInstalled()) {
					kufer_sync_backend_helper::autoimportInstall();
				}
			}
			else {
				kufer_sync_backend_helper::autoimportDelete();
			}
		}
	}
	else {
		echo rex_view::error(rex_i18n::msg('form_save_error'));
	}
}
?>
<form action="<?php print rex_url::currentBackendPage(); ?>" method="post">
	<div class="panel panel-edit">
		<header class="panel-heading"><div class="panel-title"><?php print rex_i18n::msg('d2u_helper_settings'); ?></div></header>
		<div class="panel-body">
			<fieldset>
				<legend><small><i class="rex-icon rex-icon-system"></i></small> <?php echo rex_i18n::msg('d2u_helper_settings'); ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
						d2u_addon_backend_helper::form_input('d2u_courses_settings_request_form_email', 'settings[request_form_email]', $this->getConfig('request_form_email'), TRUE, FALSE, 'email');
						d2u_addon_backend_helper::form_input('d2u_courses_settings_request_form_sender_email', 'settings[request_form_sender_email]', $this->getConfig('request_form_sender_email'), TRUE, FALSE, 'email');
						d2u_addon_backend_helper::form_linkfield('d2u_courses_settings_article_courses', '1', $this->getConfig('article_id_courses'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						d2u_addon_backend_helper::form_linkfield('d2u_courses_settings_article_shopping_cart', '2', $this->getConfig('article_id_shopping_cart'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						d2u_addon_backend_helper::form_linkfield('d2u_courses_settings_article_conditions', '3', $this->getConfig('article_id_conditions'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						d2u_addon_backend_helper::form_linkfield('d2u_courses_settings_article_terms_of_participation', '4', $this->getConfig('article_id_terms_of_participation'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						d2u_addon_backend_helper::form_checkbox('d2u_courses_settings_forward_single_course', 'settings[forward_single_course]', 'active', $this->getConfig('forward_single_course') == 'active');
					?>
				</div>
			</fieldset>
			<fieldset>
				<legend><small><i class="rex-icon rex-icon-language"></i></small> <?php echo rex_i18n::msg('d2u_helper_lang_replacements'); ?></legend>
				<div class="panel-body-wrapper slide">
					<?php
						foreach(rex_clang::getAll() as $rex_clang) {
							print '<dl class="rex-form-group form-group">';
							print '<dt><label>'. $rex_clang->getName() .'</label></dt>';
							print '<dd>';
							print '<select class="form-control" name="settings[lang_replacement_'. $rex_clang->getId() .']">';
							$replacement_options = [
								'd2u_helper_lang_german' => 'german',
								'd2u_helper_lang_english' => 'english',
							];
							foreach($replacement_options as $key => $value) {
								$selected = $value == $this->getConfig('lang_replacement_'. $rex_clang->getId()) ? ' selected="selected"' : '';
								print '<option value="'. $value .'"'. $selected .'>'. rex_i18n::msg('d2u_helper_lang_replacements_install') .' '. rex_i18n::msg($key) .'</option>';
							}
							print '</select>';
							print '</dl>';
						}
					?>
				</div>
			</fieldset>
			<?php
				if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon fa-map-marker"></i></small> <?php echo rex_i18n::msg('d2u_courses_locations'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_input('d2u_courses_location_settings_bg_color', 'settings[location_bg_color]', $this->getConfig('location_bg_color'), TRUE, FALSE, 'color');
							d2u_addon_backend_helper::form_linkfield('d2u_courses_location_settings_article', '5', $this->getConfig('article_id_locations'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						?>
					</div>
				</fieldset>
			<?php
				}
				if(rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon fa-calendar"></i></small> <?php echo rex_i18n::msg('d2u_courses_schedule_categories'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_input('d2u_courses_schedule_category_settings_bg_color', 'settings[schedule_category_bg_color]', $this->getConfig('schedule_category_bg_color'), TRUE, FALSE, 'color');
							d2u_addon_backend_helper::form_linkfield('d2u_courses_schedule_category_settings_article', '6', $this->getConfig('article_id_schedule_categories'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						?>
					</div>
				</fieldset>
			<?php
				}
				if(rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon fa-bullseye"></i></small> <?php echo rex_i18n::msg('d2u_courses_target_groups'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_input('d2u_courses_target_groups_settings_bg_color', 'settings[target_group_bg_color]', $this->getConfig('target_group_bg_color'), TRUE, FALSE, 'color');
							d2u_addon_backend_helper::form_linkfield('d2u_courses_target_groups_settings_article', '7', $this->getConfig('article_id_target_groups'), rex_config::get("d2u_helper", "default_lang", rex_clang::getStartId()));
						?>
					</div>
				</fieldset>
			<?php
				}
				if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon fa-cloud-download"></i></small> <?php echo rex_i18n::msg('d2u_courses_kufer_sync'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_checkbox('d2u_courses_import_settings_autoimport', 'settings[kufer_sync_autoimport]', 'active', $this->getConfig('kufer_sync_autoimport') == 'active');
							d2u_addon_backend_helper::form_input('d2u_courses_import_settings_xml_url', 'settings[kufer_sync_xml_url]', $this->getConfig('kufer_sync_xml_url'), TRUE, FALSE, 'text');
							$options_categories = [];
							foreach(\D2U_Courses\Category::getAllNotParents() as $category) {
								$options_categories[$category->category_id] = ($category->parent_category !== FALSE ? $category->parent_category->name ." → " : "" ). $category->name;
							}
							d2u_addon_backend_helper::form_select('d2u_courses_import_settings_default_category', 'settings[kufer_sync_default_category_id]', $options_categories, [$this->getConfig('kufer_sync_default_category_id')], 1, FALSE, FALSE);
							if(rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
								$options_locations = [];
								foreach(\D2U_Courses\Location::getAll() as $location) {
									$options_locations[$location->location_id] = ($location->location_category !== FALSE ? $location->location_category->name ." → " : "" ). $location->name;
								}
								asort($options_locations);
								d2u_addon_backend_helper::form_select('d2u_courses_import_settings_default_location', 'settings[kufer_sync_default_location_id]', $options_locations, [$this->getConfig('kufer_sync_default_location_id')], 1, FALSE, FALSE);
							}
							d2u_addon_backend_helper::form_input('d2u_courses_import_settings_xml_registration_path', 'settings[kufer_sync_xml_registration_path]', $this->getConfig('kufer_sync_xml_registration_path'), TRUE, FALSE, 'text');
						?>
					</div>
				</fieldset>
			<?php
				}
				if(\rex_addon::get('multinewsletter')->isAvailable()) {
			?>
				<fieldset>
					<legend><small><i class="rex-icon rex-icon-envelope"></i></small> <?php echo rex_i18n::msg('multinewsletter_addon_short_title'); ?></legend>
					<div class="panel-body-wrapper slide">
						<?php
							d2u_addon_backend_helper::form_checkbox('d2u_courses_settings_multinewsletter_subscribe', 'settings[multinewsletter_subscribe]', 'show', $this->getConfig('multinewsletter_subscribe') == 'show');
							$options_groups = [];
							foreach(MultinewsletterGroupList::getAll() as $group) {
								$options_groups[$group->getId()] = $group->getName();
							}
							d2u_addon_backend_helper::form_select('d2u_courses_settings_multinewsletter_group', 'settings[multinewsletter_group]', $options_groups, [$this->getConfig('multinewsletter_group')], 1, FALSE, FALSE);
						?>
					</div>
				</fieldset>
			<?php
				}
			?>
		</div>
		<footer class="panel-footer">
			<div class="rex-form-panel-footer">
				<div class="btn-toolbar">
					<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="save"><?php echo rex_i18n::msg('form_save'); ?></button>
				</div>
			</div>
		</footer>
	</div>
</form>
<?php
	print d2u_addon_backend_helper::getCSS();
	print d2u_addon_backend_helper::getJS();
	print d2u_addon_backend_helper::getJSOpenAll();