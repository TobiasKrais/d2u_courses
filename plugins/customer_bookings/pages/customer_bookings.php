<?php

use TobiasKrais\D2UCourses\Course;
use TobiasKrais\D2UCourses\CustomerBooking;

$func = rex_request('func', 'string');
$entry_id = rex_request('entry_id', 'int');
$message = rex_get('message', 'string');

// Print comments
if ('' !== $message) {
    echo rex_view::success(rex_i18n::msg($message));
}

// save settings
if (1 === (int) filter_input(INPUT_POST, 'btn_save') || 1 === (int) filter_input(INPUT_POST, 'btn_apply')) {
    $form = rex_post('form', 'array', []);

    $id = $form['id'];
    $customerBooking = CustomerBooking::get($id);
    if (null === $customerBooking) {
        $customerBooking = CustomerBooking::factory();
    }
    $customerBooking->givenName = $form['givenName'];
    $customerBooking->familyName = $form['familyName'];
    $customerBooking->birthDate = $form['birthDate'];
    $customerBooking->gender = $form['gender'];
    $customerBooking->street = $form['street'];
    $customerBooking->zipCode = $form['zipCode'];
    $customerBooking->city = $form['city'];
    $customerBooking->country = $form['country'];
    $customerBooking->emergency_number = $form['emergency_number'];
    $customerBooking->email = $form['email'];
    $customerBooking->nationality = $form['nationality'];
    $customerBooking->nativeLanguage = $form['nativeLanguage'];
    $customerBooking->pension_insurance_id = $form['pension_insurance_id'];
    $customerBooking->kids_go_home_alone = array_key_exists('kids_go_home_alone', $form);
    if (array_key_exists('salery_level', $form) && null !== $form['salery_level']) {
        $customerBooking->salery_level = $form['salery_level'];
    }
    $customerBooking->course_id = $form['course_id'];
    $customerBooking->paid = array_key_exists('paid', $form);
    $customerBooking->waitlist = array_key_exists('waitlist', $form);
    $customerBooking->remarks = $form['remarks'];

    // message output
    $message = 'form_save_error';
    if ($customerBooking->save()) {
        $message = 'form_saved';
    }

    // Redirect to make reload and thus double save impossible
    if (1 === (int) filter_input(INPUT_POST, 'btn_apply', FILTER_VALIDATE_INT) && $customerBooking->id > 0) {
        header('Location: '. rex_url::currentBackendPage(['entry_id' => $customerBooking->id, 'func' => 'edit', 'message' => $message], false));
    } else {
        header('Location: '. rex_url::currentBackendPage(['message' => $message], false));
    }
    exit;
}
// Delete
if (1 === (int) filter_input(INPUT_POST, 'btn_delete', FILTER_VALIDATE_INT) || 'delete' === $func) {
    $id = $entry_id;
    if (0 === $id) {
        $form = rex_post('form', 'array', []);
        $id = $form['id'];
    }
    $customerBooking = CustomerBooking::get($id);
    if (null !== $customerBooking) {
        echo $customerBooking->delete() ? rex_view::success(rex_i18n::msg('d2u_helper_deleted') . $message) : rex_view::error(rex_i18n::msg('d2u_helper_could_not_delete') . $message);
    }

    $func = '';
}

// Form
if ('edit' === $func || 'clone' === $func || 'add' === $func) {
?>
	<form action="<?= rex_url::currentBackendPage() ?>" method="post">
		<div class="panel panel-edit">
			<header class="panel-heading"><div class="panel-title"><?= rex_i18n::msg('d2u_courses_customer_bookings') ?></div></header>
			<div class="panel-body">
				<input type="hidden" name="form[id]" value="<?= 'edit' === $func ? $entry_id : 0 ?>">
				<?php
                    $customerBooking = CustomerBooking::get($entry_id);
                    if (null === $customerBooking) {
                        $customerBooking = CustomerBooking::factory();
                    }
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_givenName', 'form[givenName]', $customerBooking->givenName, true, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_familyName', 'form[familyName]', $customerBooking->familyName, true, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_birthDate', 'form[birthDate]', $customerBooking->birthDate, false, false, 'datetime-local');
                    $options_gender = [
                        '' => rex_i18n::msg('d2u_courses_customer_bookings_gender_none'),
                        'M' => rex_i18n::msg('d2u_courses_customer_bookings_gender_male'),
                        'W' => rex_i18n::msg('d2u_courses_customer_bookings_gender_female'),
                        'D' => rex_i18n::msg('d2u_courses_customer_bookings_gender_divers')
                    ];
                    \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_customer_bookings_gender', 'form[gender]', $options_gender, [$customerBooking->gender], 1, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_street', 'form[street]', $customerBooking->street, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_zipCode', 'form[zipCode]', $customerBooking->zipCode, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_city', 'form[city]', $customerBooking->city, false, false);
                    $options_country = [
                        '' => rex_i18n::msg('d2u_courses_customer_bookings_country_others'),
                        'CH' => rex_i18n::msg('d2u_courses_customer_bookings_country_CH'),
                        'DE' => rex_i18n::msg('d2u_courses_customer_bookings_country_DE'),
                        'FL' => rex_i18n::msg('d2u_courses_customer_bookings_country_FL'),
                        'FR' => rex_i18n::msg('d2u_courses_customer_bookings_country_FR'),
                        'IT' => rex_i18n::msg('d2u_courses_customer_bookings_country_IT')
                    ];
                    \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_customer_bookings_country', 'form[country]', $options_country, [$customerBooking->country], 1, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_pension_insurance_id', 'form[pension_insurance_id]', $customerBooking->pension_insurance_id, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_emergency_number', 'form[emergency_number]', $customerBooking->emergency_number, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_email', 'form[email]', $customerBooking->email, false, false);
                    $options_nationality = [
                        'Andere' => rex_i18n::msg('d2u_courses_customer_bookings_nationality_others'),
                        'CH' => rex_i18n::msg('d2u_courses_customer_bookings_nationality_CH'),
                        'DE' => rex_i18n::msg('d2u_courses_customer_bookings_nationality_DE'),
                        'FL' => rex_i18n::msg('d2u_courses_customer_bookings_nationality_FL'),
                        'FR' => rex_i18n::msg('d2u_courses_customer_bookings_nationality_FR'),
                        'IT' => rex_i18n::msg('d2u_courses_customer_bookings_nationality_IT')
                    ];
                    \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_customer_bookings_nationality', 'form[nationality]', $options_nationality, [$customerBooking->nationality], 1, false, false);
                    $options_nativeLanguage = [
                        'Andere' => rex_i18n::msg('d2u_courses_customer_bookings_nativeLanguage_others'),
                        'DE' => rex_i18n::msg('d2u_courses_customer_bookings_nativeLanguage_DE'),
                        'FR' => rex_i18n::msg('d2u_courses_customer_bookings_nativeLanguage_FR'),
                        'IT' => rex_i18n::msg('d2u_courses_customer_bookings_nativeLanguage_IT')
                    ];
                    \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_customer_bookings_nativeLanguage', 'form[nativeLanguage]', $options_nativeLanguage, [$customerBooking->nativeLanguage], 1, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_customer_bookings_kids_go_home_alone', 'form[kids_go_home_alone]', 'true', $customerBooking->kids_go_home_alone, false);
                    if ($customerBooking->course_id > 0) {
                        $course = new Course($customerBooking->course_id);
                        if ($course->price_salery_level) {
                            $options_salery_level = [];
                            $options_salery_level[''] = rex_i18n::msg('d2u_courses_customer_bookings_salery_level_none');
                            foreach ($course->price_salery_level_details as $description => $price) {
                                $options_salery_level[$description] = $description .': '. $price;
                            }
                            \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_price_salery_level_detail', 'form[salery_level]', $options_salery_level, [$customerBooking->salery_level], 1, false, false);
                        }
                    }
                    \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_customer_bookings_paid', 'form[paid]', 'true', $customerBooking->paid, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_checkbox('d2u_courses_customer_bookings_waitlist_text', 'form[waitlist]', 'true', $customerBooking->waitlist, false);
                    $options_courses = [];
                    foreach (Course::getAll() as $course) {
                        $options_courses[$course->course_id] = $course->name;
                    }
                    natsort($options_courses);
                    \TobiasKrais\D2UHelper\BackendHelper::form_select('d2u_courses_courses', 'form[course_id]', $options_courses, [$customerBooking->course_id], 1, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_textarea('d2u_courses_customer_bookings_internal_remarks', 'form[remarks]', $customerBooking->remarks, 5, false, false, false);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_ipAddress', 'form[ipAddress]', $customerBooking->ipAddress, false, true);
                    \TobiasKrais\D2UHelper\BackendHelper::form_input('d2u_courses_customer_bookings_bookingDate', 'form[bookingDate]', $customerBooking->bookingDate, false, true, 'datetime-local');
                ?>
			</div>
			<footer class="panel-footer">
				<div class="rex-form-panel-footer">
					<div class="btn-toolbar">
						<button class="btn btn-save rex-form-aligned" type="submit" name="btn_save" value="1"><?= rex_i18n::msg('form_save') ?></button>
						<button class="btn btn-apply" type="submit" name="btn_apply" value="1"><?= rex_i18n::msg('form_apply') ?></button>
						<button class="btn btn-abort" type="submit" name="btn_abort" formnovalidate="formnovalidate" value="1"><?= rex_i18n::msg('form_abort') ?></button>
						<?= '<button class="btn btn-delete" type="submit" name="btn_delete" formnovalidate="formnovalidate" data-confirm="'. rex_i18n::msg('form_delete') .'?" value="1">'. rex_i18n::msg('form_delete') .'</button>'; ?>
					</div>
				</div>
			</footer>
		</div>
	</form>
	<br>
	<?php
        echo \TobiasKrais\D2UHelper\BackendHelper::getCSS();
        echo \TobiasKrais\D2UHelper\BackendHelper::getJS();
        echo \TobiasKrais\D2UHelper\BackendHelper::getJSOpenAll();
}

if ('' === $func) {
    $query = 'SELECT customerBooking.id, customerBooking.givenName, customerBooking.familyName, courses.name, waitlist '
        . 'FROM '. rex::getTablePrefix() .'d2u_courses_customer_bookings AS customerBooking '
        . 'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_courses AS courses '
            . 'ON customerBooking.course_id = courses.course_id ';
    $query .= 'ORDER BY name, familyName, givenName';
    $list = rex_list::factory($query, 1000);

    $list->addTableAttribute('class', 'table-striped table-hover');

    $tdIcon = '<i class="rex-icon fa-user"></i>';
    $thIcon = '';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" title="' . rex_i18n::msg('add') . '"><i class="rex-icon rex-icon-add-module"></i></a>';

    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'entry_id' => '###id###']);

    $list->setColumnLabel('id', rex_i18n::msg('id'));
    $list->setColumnLayout('id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);

    $list->setColumnLabel('familyName', rex_i18n::msg('d2u_courses_customer_bookings_familyName'));
    $list->setColumnParams('familyName', ['func' => 'edit', 'entry_id' => '###id###']);

    $list->setColumnLabel('givenName', rex_i18n::msg('d2u_courses_customer_bookings_givenName'));
    $list->setColumnParams('givenName', ['func' => 'edit', 'entry_id' => '###id###']);

    $list->setColumnLabel('name', rex_i18n::msg('d2u_courses_course'));

    $list->setColumnLabel('waitlist', rex_i18n::msg('d2u_courses_customer_bookings_waitlist'));
    $list->setColumnFormat('waitlist', 'custom', static function ($params) {
        $list_params = $params['list'];
        $waitlist = $list_params->getValue('waitlist');
        return 1 === $waitlist ? rex_i18n::msg('d2u_courses_customer_bookings_yes') : rex_i18n::msg('d2u_courses_customer_bookings_no');
    });

    $list->addColumn(rex_i18n::msg('module_functions'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('edit'));
    $list->setColumnLayout(rex_i18n::msg('module_functions'), ['<th class="rex-table-action">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('module_functions'), ['func' => 'edit', 'entry_id' => '###id###']);

    $list->addColumn(rex_i18n::msg('d2u_helper_clone'), '<i class="rex-icon fa-copy"></i> ' . rex_i18n::msg('d2u_helper_clone'));
    $list->setColumnLayout(rex_i18n::msg('d2u_helper_clone'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('d2u_helper_clone'), ['func' => 'clone', 'entry_id' => '###id###']);

    $list->addColumn(rex_i18n::msg('delete_module'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('delete'));
    $list->setColumnLayout(rex_i18n::msg('delete_module'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('delete_module'), ['func' => 'delete', 'entry_id' => '###id###']);
    $list->addLinkAttribute(rex_i18n::msg('delete_module'), 'data-confirm', rex_i18n::msg('d2u_helper_confirm_delete'));

    $list->setNoRowsMessage(rex_i18n::msg('d2u_courses_customer_bookings_no_bookings_found'));

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('d2u_courses_customer_bookings'), false);
    $fragment->setVar('content', $list->get(), false);
    echo $fragment->parse('core/page/section.php');
}