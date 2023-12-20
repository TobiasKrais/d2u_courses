<?php

use D2U_Courses\Category;

$cart = \D2U_Courses\Cart::getCart();

// Delete course / participant
if (filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0) {
    $delete_course_id = (int) filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    $delete_participant_id = (int) filter_input(INPUT_GET, 'participant', FILTER_VALIDATE_INT);
    // Delete participant, if last patricipant, course ist also deleted
    $cart->deleteParticipant($delete_course_id, $delete_participant_id);
}

// Add course
if (filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) > 0) {
    $add_course_id = (int) filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if ($add_course_id > 0) {
        $cart->addCourse($add_course_id);
    }
}

$form_data = filter_input_array(INPUT_POST);
// Add empty participant
if (is_array($form_data) && array_key_exists('participant_add', $form_data) && is_array($form_data['participant_add'])) {
    foreach ($form_data['participant_add'] as $course_id => $value) {
        $cart->addEmptyParticipant($course_id);
    }
}

// Add participant data
foreach (\D2U_Courses\Cart::getCourseIDs() as $course_id) {
    $course = new \D2U_Courses\Course($course_id);

    if (is_array($form_data) && array_key_exists('participant_'. $course_id, $form_data) && is_array($form_data['participant_'. $course_id])) {
        // courses with person details
        foreach ($form_data['participant_'. $course_id] as $participant_id => $participant_data) {
            if (!is_array($participant_data)) {
                continue;
            }
            $participant_price = '';
            $counter_row_price_salery_level_details = 0;
            foreach ($course->price_salery_level_details as $description => $price) {
                ++$counter_row_price_salery_level_details;
                if ($counter_row_price_salery_level_details === (int) $participant_data['price_salery_level_row_number']) {
                    $participant_price = $price;
                    break;
                }
            }
            $participant_data_update = [
                'firstname' => trim(false !== filter_var($participant_data['firstname']) ? filter_var($participant_data['firstname']) : ''),
                'lastname' => trim(false !== filter_var($participant_data['lastname']) ? filter_var($participant_data['lastname']) : ''),
                'birthday' => (array_key_exists('birthday', $participant_data) && false !== filter_var($participant_data['birthday']) ? trim(filter_var($participant_data['birthday'])) : ''),
                'age' => (array_key_exists('age', $participant_data) && false !== filter_var($participant_data['age']) ? trim(filter_var($participant_data['age'])) : ''),
                'emergency_number' => (array_key_exists('emergency_number', $participant_data) && false !== filter_var($participant_data['emergency_number']) ? trim(filter_var($participant_data['emergency_number'])) : ''),
                'gender' => (array_key_exists('gender', $participant_data) && false !== filter_var($participant_data['gender']) ? trim(filter_var($participant_data['gender'])) : ''),
                'pension_insurance_id' => (array_key_exists('pension_insurance_id', $participant_data) && false !== filter_var($participant_data['pension_insurance_id']) ? trim(filter_var($participant_data['pension_insurance_id'])) : ''),
                'nationality' => (array_key_exists('nationality', $participant_data) && false !== filter_var($participant_data['nationality']) ? trim(filter_var($participant_data['nationality'])) : ''),
                'nativeLanguage' => (array_key_exists('nativeLanguage', $participant_data) && false !== filter_var($participant_data['nativeLanguage']) ? trim(filter_var($participant_data['nativeLanguage'])) : ''),
                'price' => trim($participant_price),
                'price_salery_level_row_number' => (array_key_exists('price_salery_level_row_number', $participant_data) && false !== filter_var($participant_data['price_salery_level_row_number']) ? trim(filter_var($participant_data['price_salery_level_row_number'])) : ''),
            ];
            $cart->updateParticipant($course_id, (int) $participant_id, $participant_data_update);
        }
    }
    if (is_array($form_data) && array_key_exists('participant_number_'. $course_id, $form_data)) {
        $participant_price = '';
        $counter_row_price_salery_level_details = 0;
        foreach ($course->price_salery_level_details as $description => $price) {
            ++$counter_row_price_salery_level_details;
            if ($counter_row_price_salery_level_details === (int) $form_data['price_salery_level_row_number']) {
                $participant_price = $price;
                break;
            }
        }
        // courses with person number only
        $cart->updateParticipantNumber($course_id, (int) $form_data['participant_number_'. $course_id], $participant_price, (int) $form_data['participant_price_salery_level_row_'. $course_id]);
    }
}

// Forward so start page if cart is saved and shopping should be continued
if (isset($form_data['participant_save'])) {
    header('Location: '. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_courses')));
    exit;
}

$sprog = rex_addon::get('sprog');
$tag_open = $sprog->getConfig('wildcard_open_tag');
$tag_close = $sprog->getConfig('wildcard_close_tag');
$ask_age = 'REX_VALUE[1]' === '' ? 1 : (int) 'REX_VALUE[1]'; // int, due to several options /** @phpstan-ignore-line */
$ask_gender = 'REX_VALUE[5]' === 'true' ? false : true; /** @phpstan-ignore-line */
$ask_emergency_number = 'REX_VALUE[6]' === 'true' ? true : false; /** @phpstan-ignore-line */
$ask_penson_assurance_id = 'REX_VALUE[7]' === 'true' ? true : false; /** @phpstan-ignore-line */
$ask_nationality = 'REX_VALUE[8]' === 'true' ? true : false; /** @phpstan-ignore-line */
$ask_nativeLanguage = 'REX_VALUE[9]' === 'true' ? true : false; /** @phpstan-ignore-line */

$ask_age_root_category_id = [];
if ('REX_VALUE[3]' === 'true' && is_array(rex_var::toArray('REX_VALUE[4]'))) { /** @phpstan-ignore-line */
    $ask_age_root_category_id = array_map('intval', rex_var::toArray('REX_VALUE[4]'));
}

// Invoice form
if (isset($form_data['invoice_form'])) {
    // Devide participants because Kufer SQL has different registration types
    $kufer_others = [];
    $kufer_children = [];
    $kufer_self = [];
    $mail_registration = [];
    foreach (D2U_Courses\Cart::getCourseIDs() as $course_id) {
        $course = new D2U_Courses\Course($course_id);
        if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() && 'KuferSQL' === $course->import_type && '' !== $course->course_number) {
            foreach (D2U_Courses\Cart::getCourseParticipants($course_id) as $participant) {
                if (is_array($participant) && array_key_exists('firstname', $participant) && $participant['firstname'] === $form_data['invoice_form']['firstname'] && $participant['lastname'] === $form_data['invoice_form']['lastname']) {
                    // Treat children currently like normal persons
                    $kufer_self[$course_id][] = $participant;
                    // Otherwise uncomment next lines
//					if(D2U_Courses\Cart::calculateAge($participant['birthday']) < 18) {
//						$kufer_children[$course_id][] = $participant;
//					}
//					else {
//						$kufer_self[$course_id][] = $participant;
//					}
                } else {
                    // Treat children currently like normal persons
                    $kufer_others[$course_id][] = $participant;
                    // Otherwise uncomment next lines
//					if(D2U_Courses\Cart::calculateAge($participant['birthday']) < 18) {
//						$kufer_children[$course_id][] = $participant;
//					}
//					else {
//						$kufer_others[$course_id][] = $participant;
//					}
                }
            }
        } else {
            $mail_registration[$course_id] = D2U_Courses\Cart::getCourseParticipants($course_id);
        }
    }

    $error = false;
    // Create Kufer XML registrations
    if (count($kufer_self) > 0 && false === $cart->createXMLRegistration($kufer_self, $form_data['invoice_form'], 'selbst')) {
        $error = true;
    }
//    if (count($kufer_children) > 0 && false === $cart->createXMLRegistration($kufer_children, $form_data['invoice_form'], 'kind')) {
//        $error = true;
//    }
    foreach ($kufer_others as $course_id => $participant) {
        foreach ($participant as $id => $cur_participant) {
            $cart->createXMLRegistration([$course_id => [$cur_participant]], $form_data['invoice_form'], 'andere');
        }
    }
    if (count($mail_registration) > 0 && false === $cart->sendRegistrations($mail_registration, $form_data['invoice_form'])) {
        $error = true;
    }
    if (rex_plugin::get('d2u_courses', 'customer_bookings')->isAvailable()) {
        $cart->saveBookings($mail_registration, $form_data['invoice_form']);
    }

    // MultiNewsletter Anmeldemail senden
    if (rex_addon::get('multinewsletter')->isAvailable() && array_key_exists('multinewsletter', $form_data['invoice_form']) && is_array($form_data['invoice_form']['multinewsletter']) && count($form_data['invoice_form']['multinewsletter']) > 0) {
        $user = MultinewsletterUser::initByMail((string) filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
        $anrede = 'W' === $form_data['invoice_form']['gender'] ? 1 : 0;

        if ($user instanceof MultinewsletterUser) {
            $user->title = $anrede;
            $user->firstname = $form_data['invoice_form']['firstname'];
            $user->lastname = $form_data['invoice_form']['lastname'];
            $user->clang_id = rex_clang::getCurrentId();
        } else {
            $user = MultinewsletterUser::factory(
                $form_data['invoice_form']['e-mail'],
                $anrede,
                '',
                $form_data['invoice_form']['firstname'],
                $form_data['invoice_form']['lastname'],
                rex_clang::getCurrentId(),
            );
        }
        $user->group_ids = array_map('intval', $form_data['invoice_form']['multinewsletter']);
        $user->status = 0;
        $user->subscriptiontype = 'web';
        $user->privacy_policy_accepted = 1;
        $user->activationkey = (string) random_int(100000, 999999);
        $user->save();

        // Send activationmail
        $user->sendActivationMail(
            (string) rex_config::get('multinewsletter', 'sender'),
            (string) rex_config::get('multinewsletter', 'lang_'. rex_clang::getCurrentId() .'_sendername'),
            (string) rex_config::get('multinewsletter', 'lang_'. rex_clang::getCurrentId() .'_confirmsubject'),
            (string) rex_config::get('multinewsletter', 'lang_'. rex_clang::getCurrentId() .'_confirmcontent'),
        );
    }

    if (false === $error) {
        echo '<div class="col-12">';
        echo '<h1>'. $tag_open .'d2u_courses_cart_thanks'. $tag_close .'</h1><p>'. $tag_open .'d2u_courses_cart_thanks_details'. $tag_close .'</p>';
        echo '</div>';
        $cart->unsetCart();
    } else {
        echo '<div class="col-12">';
        echo '<h1>'. $tag_open .'d2u_courses_cart_error'. $tag_close .'</h1>'
            .'<p>'. $tag_open .'d2u_courses_cart_error_details'. $tag_close .'</p>';
        echo '</div>';
    }
} elseif (isset($form_data['request_courses']) && '' !== $form_data['request_courses']) {
    $payment_options = rex_config::get('d2u_courses', 'payment_options', []);
    if (!is_array($payment_options)) {
        $payment_options = [$payment_options];
    }

    // Anmeldeformular
    echo '<div class="col-12">';
    echo '<div>';
    echo '<form action="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'" method="post" enctype="multipart/form-data" id="cart">';

    echo '<div class="registration_header cart_row_title"><h1>';
    if (count($payment_options) > 0) {
        echo $tag_open .'d2u_courses_cart_bill_address'. $tag_close;
    } else {
        echo $tag_open .'d2u_courses_cart_contact_address'. $tag_close;
    }
    echo '</h1></div>';

    if ((bool) rex_config::get('d2u_courses', 'allow_company', false)) {
        echo '<p>';
        echo '<label class="cart_select" for="invoice_form-type">&nbsp;</label>';
        echo '<select class="cart_select" id="invoice_form-type" name="invoice_form[type]" size="1" onChange="business_changer()">';
        echo '<option value="P">'. $tag_open .'d2u_courses_type_private'. $tag_close .'</option>';
        echo '<option value="B">'. $tag_open .'d2u_courses_business'. $tag_close .'</option>';
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label class="cart_text" for="invoice_form-company">'. $tag_open .'d2u_courses_company'. $tag_close .' *</label>';
        echo '<input type="text" class="cart_text" name="invoice_form[company]" id="invoice_form-company" value="" maxlength="50">';
        echo '</p>';
    }
    echo '<p>';
    echo '<label class="cart_select" for="invoice_form-gender">'. $tag_open .'d2u_courses_title'. $tag_close .'</label>';
    echo '<select class="cart_select" id="invoice_form-gender" name="invoice_form[gender]" size="1">';
    echo '<option value="W">'. $tag_open .'d2u_courses_title_female'. $tag_close .'</option>';
    echo '<option value="M">'. $tag_open .'d2u_courses_title_male'. $tag_close .'</option>';
    echo '</select>';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-firstname">'. $tag_open .'d2u_courses_firstname'. $tag_close .' *</label>';
    echo '<input type="text" class="cart_text" name="invoice_form[firstname]" id="invoice_form-firstname" value="" maxlength="35" required>';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-lastname">'. $tag_open .'d2u_courses_lastname'. $tag_close .' *</label>';
    echo '<input type="text" class="cart_text" name="invoice_form[lastname]" id="invoice_form-lastname" value="" maxlength="35" required>';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-address">'. $tag_open .'d2u_courses_street'. $tag_close .' *</label>';
    echo '<input type="text" class="cart_text" name="invoice_form[address]" id="invoice_form-address" value="" maxlength="35" required>';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-zipcode">'. $tag_open .'d2u_courses_zipcode'. $tag_close .' *</label>';
    echo '<input type="text" class="cart_text" name="invoice_form[zipcode]" id="invoice_form-zipcode" value="" maxlength="5" required>';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-city">'. $tag_open .'d2u_courses_city'. $tag_close .' *</label>';
    echo '<input type="text" class="cart_text" name="invoice_form[city]" id="invoice_form-city" value="" maxlength="44" required>';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-country">'. $tag_open .'d2u_courses_country'. $tag_close .' *</label>';
    echo '<select class="cart_select" id="invoice_form-country" name="invoice_form[country]" size="1">';
    $country_options = [
        '' => 'd2u_courses_country_others',
        'CH' => 'd2u_courses_country_CH',
        'DE' => 'd2u_courses_country_DE',
        'FL' => 'd2u_courses_country_FL',
        'FR' => 'd2u_courses_country_FR',
        'IT' => 'd2u_courses_country_IT'
    ];
    foreach ($country_options as $value => $wildcard) {
        echo '<option value="'. $value .'">'. \Sprog\Wildcard::get($wildcard) .'</option>';
    }
    echo '</select>';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-phone">'. $tag_open .'d2u_courses_phone'. $tag_close .' *</label>';
    echo '<input type="text" class="cart_text" name="invoice_form[phone]" id="invoice_form-phone" value="" required>';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-e-mail">'. $tag_open .'d2u_courses_email'. $tag_close .' *</label>';
    echo '<input type="email" class="cart_text" name="invoice_form[e-mail]" id="invoice_form-e-mail" value="" required onblur="checkEmail();">';
    echo '</p>';

    echo '<p>';
    echo '<label class="cart_text" for="invoice_form-e-mail-verification">'. \Sprog\Wildcard::get('d2u_courses_email_verification') .' *</label>';
    echo '<input type="email" class="cart_text" name="invoice_form[e-mail-verification]" id="invoice_form-e-mail-verification" value="" required onblur="checkEmail();">';
    echo ' <span id="email_wrong">'. \Sprog\Wildcard::get('d2u_courses_email_verification_failure') .'</span>';
    echo '</p>';
?>
	<script>
		// verify e-mail
		function checkEmail() {
			if(document.getElementById('invoice_form-e-mail').value !== document.getElementById('invoice_form-e-mail-verification').value) {
				document.getElementById('email_wrong').style.display = "inline-block";
				return false;
			}
			else {
				document.getElementById('email_wrong').style.display = "none";
				return true;
			}
		}
	</script>
<?php
    if ('active' === rex_config::get('d2u_courses', 'ask_vacation_pass', 'inactive') && false === rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
        echo '<p>';
        echo '<label class="cart_text" for="invoice_form-vacation_pass">'. $tag_open .'d2u_courses_vacation_pass'. $tag_close .'</label>';
        echo '<input type="number" class="cart_text" name="invoice_form[vacation_pass]" id="invoice_form-vacation_pass" maxlength="5" value="">';
        echo '</p>';
    }
    echo '<br>';

    // Kufer Sync Plugin needs statistic values
    if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() && 'REX_VALUE[2]' === 'true') { /** @phpstan-ignore-line */
        echo '<div class="registration_header cart_row_title">'. $tag_open .'d2u_courses_statistic'. $tag_close .'</div>';

        echo '<p>';
        echo '<label class="cart_text" for="invoice_form-birthday">'. $tag_open .'d2u_courses_birthdate'. $tag_close .'</label>';
        echo '<input type="date" class="cart_date" name="invoice_form[birthday]" placeholder="'. $tag_open .'d2u_courses_date_placeholder'. $tag_close .'" min="1900-01-01" max="'. (date('Y') - 5) .'-01-01">';
        echo '</p>';
    }

    if (count($payment_options) > 0) {
        echo '<div class="registration_header cart_row_title"><h1>'. $tag_open .'d2u_courses_payment_data'. $tag_close .'</h1></div>';

        echo '<p>';
        echo '<label class="cart_select" for="invoice_form-payment">'. $tag_open .'d2u_courses_payment'. $tag_close .':</label>';
        echo '<select class="cart_select" id="invoice_form-payment" name="invoice_form[payment]" size="1" onChange="remove_required()">';
        if (in_array('direct_debit', $payment_options, true)) {
            echo '<option value="L">'. $tag_open .'d2u_courses_payment_debit'. $tag_close .'</option>';
        }
        if (in_array('bank_transfer', $payment_options, true)) {
            echo '<option value="Ü">'. $tag_open .'d2u_courses_payment_transfer'. $tag_close .'</option>';
        }
        if (in_array('cash', $payment_options, true)) {
            echo '<option value="B">'. $tag_open .'d2u_courses_payment_cash'. $tag_close .'</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p>';
        echo '<label class="cart_text" for="invoice_form-account_owner">'. $tag_open .'d2u_courses_payment_account_owner'. $tag_close .' *</label>';
        echo '<input type="text" class="cart_text" name="invoice_form[account_owner]" id="invoice_form-account_owner" maxlength="30" value="" required>';
        echo '</p>';

        echo '<p>';
        echo '<label class="cart_text" for="invoice_form-bank">'. $tag_open .'d2u_courses_payment_bank'. $tag_close .' *</label>';
        echo '<input type="text" class="cart_text" name="invoice_form[bank]" id="invoice_form-bank" maxlength="30" value="" required>';
        echo '</p>';

        echo '<p>';
        echo '<label class="cart_text" for="invoice_form-iban">'. $tag_open .'d2u_courses_payment_iban'. $tag_close .' *</label>';
        echo '<input type="text" class="cart_text" name="invoice_form[iban]" id="invoice_form-iban" maxlength="35" value="" onblur="alertInvalidIBAN(this);alertForeignIBAN(this.value);" required>';
        echo ' <span id="iban_wrong">'. \Sprog\Wildcard::get('d2u_courses_cart_iban_wrong') .'</span>';
        echo '<div id="iban_not_sepa"><p>'
            .'<label class="cart_text">&nbsp;</label>'
            .\Sprog\Wildcard::get('d2u_courses_cart_iban_not_sepa') .'</p></div>';
        echo '</p>';
?>
	<script>
		// check IBAN
		function alertInvalidIBAN(field) {
			if(isValidIBANNumber(field.value) !== 1) {
				field.style.cssText = 'border: 3px solid red;';
				document.getElementById('iban_wrong').style.display = "inline-block";
				return false;
			}
			else {
				field.style.cssText = '';
				document.getElementById('iban_wrong').style.display = "none";
				return true;
			}
		}

		// check foreign IBAN
		function alertForeignIBAN(iban) {
			sepa_countries = ['BE', 'DK', 'DE', 'EE', 'FI', 'FR', 'GI', 'GR', 'IT', 'HR', 'LV', 'LT', 'LU', 'MT', 'MC', 'NL', 'AT', 'PL', 'PT', 'SM', 'SE', 'SK', 'SI', 'ES', 'CZ', 'HU'];
			if(sepa_countries.includes(iban.substr(0, 2))) {
				document.getElementById('iban_not_sepa').style.display = "none";
			}
			else if (iban.length > 0) {
				document.getElementById('iban_not_sepa').style.display = "inline-block";
			}
		}
	</script>
<?php

        echo '<p>';
        echo '<label class="cart_text" for="invoice_form-bic">'. $tag_open .'d2u_courses_payment_bic'. $tag_close .' *</label>';
        echo '<input type="text" class="cart_text" name="invoice_form[bic]" id="invoice_form-bic" maxlength="11" value="" required>';
        echo '</p><br>';
?>
	<script>
		function remove_required() {
			if($('#invoice_form-payment').val() === 'L') {
				$('#invoice_form-account_owner').attr('required', true);
				$('#invoice_form-bank').attr('required', true);
				$('#invoice_form-iban').attr('required', true);
				$('#invoice_form-bic').attr('required', true);
				$('#invoice_form-account_owner').parent().slideDown();
				$('#invoice_form-bank').parent().slideDown();
				$('#invoice_form-iban').parent().slideDown();
				$('#invoice_form-bic').parent().slideDown();
			}
			else {
				$('#invoice_form-account_owner').removeAttr('required');
				$('#invoice_form-bank').removeAttr('required');
				$('#invoice_form-iban').removeAttr('required');
				$('#invoice_form-bic').removeAttr('required');
				$('#invoice_form-account_owner').parent().slideUp();
				$('#invoice_form-bank').parent().slideUp();
				$('#invoice_form-iban').parent().slideUp();
				$('#invoice_form-bic').parent().slideUp();
			}
		}
		// On init
		remove_required();
	</script>
<?php
    }
    if ((bool) rex_config::get('d2u_courses', 'allow_company', false)) {
?>
<script>
    /**
     * Add / hide company field and add / remove payment option.
     */
    function business_changer() {
        if($('#invoice_form-type').val() === 'B') {
            $('#invoice_form-company').attr('required', true);
            $('#invoice_form-company').parent().slideDown();
            <?php
                if (!in_array('bank_transfer', $payment_options, true) && (bool) rex_config::get('d2u_courses', 'allow_company_bank_transfer', false)) {
            ?>
            var option = document.createElement("option");
            option.text = '<?= $tag_open .'d2u_courses_payment_transfer'. $tag_close ?>';
            option.value = 'Ü';
            document.getElementById('invoice_form-payment').add(option);
            <?php
                }
            ?>
        }
        else {
            $('#invoice_form-company').removeAttr('required');
            $('#invoice_form-company').parent().slideUp();
            <?php
                if (!in_array('bank_transfer', $payment_options, true) && (bool) rex_config::get('d2u_courses', 'allow_company_bank_transfer', false)) {
            ?>
            var select_payment = document.getElementById('invoice_form-payment');
            for (var i = 0; i < select_payment.length; i++) {
                if (select_payment.options[i].value === 'Ü')
                    select_payment.remove(i);
            }
            <?php
                }
            ?>
        }
    }
    // On init
    business_changer();
</script>
<?php
    }
    // Bestellübersicht
    echo '<div class="registration_header cart_row_title"><h1>'. $tag_open .'d2u_courses_order_overview'. $tag_close .'</h1></div>';
    $has_minor_participants = false;
    foreach (D2U_Courses\Cart::getCourseIDs() as $course_id) {
        $course = new D2U_Courses\Course($course_id);
        echo '<div class="row">';
        echo '<div class="col-12 spacer">';
        echo '<div class="cart_row_title" style="background-color: '. ($course->category instanceof Category ? $course->category->color : 'grey') .'">';
        echo '<div class="row" data-match-height>';
        echo '<div class="col-12">';
        echo '<b>'. $course->name;
        if ('' !== $course->course_number) {
            echo ' ('. $course->course_number .')';
        }
        echo '</b><br />';
        if ('' !== $course->date_start || '' !== $course->date_end || '' !== $course->time) {
            echo $tag_open .'d2u_courses_date'. $tag_close .': ';
            $date = '';
            if ('' !== $course->date_start) {
                $date .= D2U_Courses\Cart::formatCourseDate($course->date_start);
            }
            if ('' !== $course->date_end) {
                $date .= ' - '. D2U_Courses\Cart::formatCourseDate($course->date_end);
            }
            if ('' !== $course->time) {
                if ('' !== $date) {
                    $date .= ', ';
                }
                $date .= $course->time;
            }
            echo $date .'<br>';
        }
        if (!$course->price_salery_level && $course->price > 0) {
            echo $tag_open .'d2u_courses_fee'. $tag_close . ': '. number_format($course->price, 2, ',', '.') .' €';
            if ($course->price_discount > 0) {
                echo ' ('. $tag_open .'d2u_courses_discount'. $tag_close .': '. number_format($course->price_discount, 2, ',', '.') .' €)<br>';
            }
        }
        if ('yes_number' === $course->registration_possible) {
            $participant_data = D2U_Courses\Cart::getCourseParticipants($course_id);
            if (array_key_exists('participant_number', $participant_data) && !is_array($participant_data['participant_number'])) {
                echo '<ul>';
                echo '<li>'. $tag_open .'d2u_courses_participant_number'. $tag_close .': '. $participant_data['participant_number'];
                if ($course->price_salery_level) {
                    $counter_row_price_salery_level_details = 0;
                    foreach ($course->price_salery_level_details as $description => $price) {
                        ++$counter_row_price_salery_level_details;
                        if ($counter_row_price_salery_level_details === (int) $participant_data['participant_price_salery_level_row_number'] && !is_array($participant_data['participant_price'])) {
                            echo ', '. $tag_open .'d2u_courses_fee'. $tag_close .': '. $participant_data['participant_price'] .' ('. $tag_open .'d2u_courses_price_salery_level'. $tag_close .': '. $description .')';
                            break;
                        }
                    }
                }

                echo '</li>';
                echo '</ul>';
            }
        } else {
            // registration with person details
            echo '<ul>';
            foreach (D2U_Courses\Cart::getCourseParticipants($course_id) as $id => $participant) {
                if (is_array($participant)) {
                    echo '<li>'. (array_key_exists('firstname', $participant) && '' !== $participant['firstname'] ? $participant['firstname'] .' ' : '')
                        . (array_key_exists('lastname', $participant) && '' !== $participant['lastname'] ? $participant['lastname'] .' ' : '');

                    if ($ask_gender || ($course->category instanceof Category && in_array($course->category->getPartentRoot()->category_id, $ask_age_root_category_id, true))) { /** @phpstan-ignore-line */
                        echo ' (';
                        $age_seperator = false;
                        if (1 === $ask_age && array_key_exists('birthday', $participant) && '' !== $participant['birthday']) { /** @phpstan-ignore-line */
                            echo $tag_open .'d2u_courses_birthdate'. $tag_close .': '. D2U_Courses\Cart::formatCourseDate($participant['birthday']);
                            $age_seperator = true;
                        } elseif (2 === $ask_age && array_key_exists('age', $participant)) { /** @phpstan-ignore-line */
                            echo $tag_open .'d2u_courses_age'. $tag_close .': '. D2U_Courses\Cart::formatCourseDate($participant['age']);
                            $age_seperator = true;
                        }
                        if ($ask_gender) { /** @phpstan-ignore-line */
                            if ($age_seperator) { /** @phpstan-ignore-line */
                                echo ', ';
                            }
                            echo array_key_exists('gender', $participant) && 'M' === $participant['gender'] ? $tag_open .'d2u_courses_male'. $tag_close : $tag_open .'d2u_courses_female'. $tag_close;
                        }
                        echo ')';
                    }
                    if ($course->price_salery_level) {
                        $counter_row_price_salery_level_details = 0;
                        foreach ($course->price_salery_level_details as $description => $price) {
                            ++$counter_row_price_salery_level_details;
                            if (array_key_exists('price_salery_level_row_number', $participant) && $counter_row_price_salery_level_details === (int) $participant['price_salery_level_row_number']) {
                                echo ', '. $tag_open .'d2u_courses_fee'. $tag_close .': '. $participant['price'] .' ('. $tag_open .'d2u_courses_price_salery_level'. $tag_close .': '. $description .')';
                                break;
                            }
                        }
                    }
                    echo '</li>';
                    if ('' !== $participant['birthday'] && $cart::calculateAge($participant['birthday']) < 18) {
                        $has_minor_participants = true;
                    }
                }
            }
            echo '</ul>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    // Are minors allowed to retun home by their own?
    if ('active' === rex_config::get('d2u_courses', 'ask_kids_go_home_alone', 'inactive')) {
        echo '<p class="cart_checkbox">';
        echo '<input type="checkbox" class="cart_checkbox" name="invoice_form[kids_go_home_alone]" id="invoice_kids_go_home_alone" value="yes">';
        echo '<label class="cart_checkbox" for="invoice_kids_go_home_alone">'. $tag_open .'d2u_courses_kids_go_home_alone'. $tag_close .'</label></p>';
    }

    echo '<p>&nbsp;</p>';
    if (rex_config::get('d2u_courses', 'article_id_conditions', 0) > 0 || rex_config::get('d2u_courses', 'article_id_terms_of_participation', 0) > 0) {
        echo '<p class="cart_checkbox">';
        echo '<input type="checkbox" class="cart_checkbox" name="invoice_form[conditions]" id="invoice_form-conditions" value="yes" required>';
        echo '<label class="cart_checkbox" for="invoice_form-conditions">';
        if (rex_config::get('d2u_courses', 'article_id_conditions', 0) > 0) {
            echo '<a href="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_conditions')) .'" target="blank">'. $tag_open .'d2u_courses_accept_conditions'. $tag_close .'</a>';
        }
        if (rex_config::get('d2u_courses', 'article_id_conditions', 0) > 0 && rex_config::get('d2u_courses', 'article_id_terms_of_participation', 0) > 0) {
            echo '<br>';
        }
        if (rex_config::get('d2u_courses', 'article_id_terms_of_participation', 0) > 0) {
            echo '<a href="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_terms_of_participation')) .'" target="blank">'. $tag_open .'d2u_courses_accept_terms_of_participation'. $tag_close .'</a>';
        }
        echo ' *</label></p>';
    }

    if (rex_config::get('d2u_helper', 'article_id_privacy_policy', 0) > 0) {
        echo '<p class="cart_checkbox">';
        echo '<input type="checkbox" class="cart_checkbox" name="invoice_form[privacy_policy]" id="invoice_form-privacy_policy" value="yes" required>';
        echo '<label class="cart_checkbox" for="invoice_form-privacy_policy"><a href="'. rex_getUrl((int) rex_config::get('d2u_helper', 'article_id_privacy_policy')) .'" target="blank">'.
            $tag_open .'d2u_courses_accept_privacy_policy'. $tag_close .'</a> *</label>';
        echo '</p>';
    }

    if (rex_addon::get('multinewsletter')->isAvailable() && 'show' === rex_config::get('d2u_courses', 'multinewsletter_subscribe', 'hide')) {
        $multinewsletter_config = rex_config::get('d2u_courses', 'multinewsletter_group', []);
        $multinewsletter_group = [];
        if (is_array($multinewsletter_config)) {
            $multinewsletter_group = $multinewsletter_config;
        } elseif ((int) $multinewsletter_config > 0) {
            $multinewsletter_group[] = (int) $multinewsletter_config;
        }
        if (count($multinewsletter_group) > 0) {
            echo '<p class="cart_checkbox">';
            if (1 === count($multinewsletter_group)) {
                echo '<input type="checkbox" class="cart_checkbox" name="invoice_form[multinewsletter][]" id="invoice_form-multinewsletter" value="'. (is_array(rex_config::get('d2u_courses', 'multinewsletter_group', [])) && array_key_exists(0, rex_config::get('d2u_courses', 'multinewsletter_group', [])) ? rex_config::get('d2u_courses', 'multinewsletter_group', [])[0] : '') .'">';
                echo '<label class="cart_checkbox" for="invoice_form-multinewsletter">'. $tag_open .'d2u_courses_multinewsletter'. $tag_close .'</label>';
            } else {
                echo $tag_open .'d2u_courses_multinewsletter'. $tag_close .'<br>';
                foreach ($multinewsletter_group as $newsletter_group_id) {
                    $multinewsletter_group = new MultinewsletterGroup((int) $newsletter_group_id);
                    if ($multinewsletter_group->id > 0) {
                        echo '<input type="checkbox" class="cart_checkbox" name="invoice_form[multinewsletter][]" id="invoice_form-multinewsletter" value="'. $multinewsletter_group->id .'">';
                        echo '<label class="cart_checkbox" for="invoice_form-multinewsletter">'. $multinewsletter_group->name .'</label><br>';
                    }
                }
            }
            echo '</p>';
        }
    }

    echo '* '. $tag_open .'d2u_courses_mandatory_fields'. $tag_close .'<br><br>';
    echo '<p class="formsubmit formsubmit">';
    echo '<input type="submit" class="submit save_cart" name="invoice_form[submit]" id="invoice_form-submit" value="'. $tag_open .'d2u_courses_make_booking'. $tag_close .'">';
    echo '</p>';
?>
	<script>
		document.getElementById('cart').addEventListener(
			"submit",
			function (evt) {
				if(document.getElementById('invoice_form-payment').value === 'L' && isValidIBANNumber(document.getElementById('invoice_form-iban').value) !== 1) {
					evt.preventDefault();
					document.getElementById('invoice_form-iban').focus();
					window.scrollBy({ top: -200, left: 0, behavior: "smooth" });
				}
				else if(document.getElementById('invoice_form-e-mail').value !== document.getElementById('invoice_form-e-mail-verification').value) {
					evt.preventDefault();
					document.getElementById('invoice_form-e-mail-verification').focus();
					window.scrollBy({ top: -200, left: 0, behavior: "smooth" });
				}
			}
		);
	</script>
<?php

    echo '</form>';
    echo '</div>';
    echo '</div>';
} else {
    // Warenkorb
    echo '<div class="col-12 col-md-3 spacer">';
    echo '<div class="cart_box">';
    echo '<div class="view_head">';
    echo '<img src="'.	rex_addon::get('d2u_courses')->getAssetsUrl('cart.png') .'" alt="'. $tag_open .'d2u_courses_cart'. $tag_close .'">';
    echo '</div>';
    echo '<div class="cart_box_title">'. $tag_open .'d2u_courses_cart'. $tag_close .'</div>';
    echo '</div>';
    echo '</div>';

    if (0 === count(D2U_Courses\Cart::getCourseIDs())) {
        echo '<div class="col-12 col-md-9 spacer">';
        echo '<div class="cart_row_title" id="cart_empty">'. $tag_open .'d2u_courses_cart_empty'. $tag_close .'</div>';
        echo '</div>';
    } else {
        echo '<div class="col-12 col-md-9">';
        echo '<form action="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'" method="post">';
        echo '<div class="row">';
        foreach (D2U_Courses\Cart::getCourseIDs() as $course_id) {
            $course = new D2U_Courses\Course($course_id);
            echo '<div class="col-12 spacer">';
            echo '<div class="cart_row_title" style="background-color: '. ($course->category instanceof Category ? $course->category->color : 'grey') .'">';
            echo '<div class="row">';
            echo '<div class="col-12">';
            echo '<a href="'. $course->getUrl(true) .'" title="'. $course->name .'" class="cart_course_title">';
            echo $course->name;
            if ('' !== $course->course_number) {
                echo ' ('. $course->course_number .')';
            }
            echo '</a><br />';
            if ('' !== $course->date_start || '' !== $course->date_end || '' !== $course->time) {
                echo $tag_open .'d2u_courses_date'. $tag_close .': ';
                $date = '';
                if ('' !== $course->date_start) {
                    $date .= D2U_Courses\Cart::formatCourseDate($course->date_start);
                }
                if ('' !== $course->date_end) {
                    $date .= ' - '. D2U_Courses\Cart::formatCourseDate($course->date_end);
                }
                if ('' !== $course->time) {
                    if ('' !== $date) {
                        $date .= ', ';
                    }
                    $date .= $course->time;
//					if(strpos($course->time, "Uhr") === false) {
//						$date .= ' Uhr';
//					}
                }
                echo $date .'<br>';
            }
            if ($course->price > 0) {
                echo $tag_open .'d2u_courses_fee'. $tag_close . ': '. number_format($course->price, 2, ',', '.') .' €';
                if ($course->price_discount > 0) {
                    echo ' ('. $tag_open .'d2u_courses_discount'. $tag_close .': '. number_format($course->price_discount, 2, ',', '.') .' €)<br>';
                }
            }

            if ('yes_number' === $course->registration_possible) {
                // registration without person details
                $participants_data = D2U_Courses\Cart::getCourseParticipants($course_id);
                echo '<br>';
                echo '<div class="row">';
                echo '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_participant_number'. $tag_close .'</div>'
                    . '<div class="col-10 col-sm-5 col-md-7 div_cart"><input type="number" class="text_cart" name="participant_number_'. $course_id .'" value="'. (!is_array($participants_data['participant_number']) && '' !== $participants_data['participant_number'] ? $participants_data['participant_number'] : 1) .'" min="1" max="50"></div>';
                echo '<div class="col-2 col-sm-1">';
                echo '<a href="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'?delete='. $course_id .'" onclick="return window.confirm(\''. $tag_open .'d2u_courses_cart_delete_course'. $tag_close .'\');" tabindex="-1">';
                echo '<img src="'. rex_addon::get('d2u_courses')->getAssetsUrl('delete.png') .'" alt="'. $tag_open .'d2u_courses_cart_delete'. $tag_close .'" class="delete_participant"></a>';
                echo '</div>';
                if ($course->price_salery_level) {
                    echo '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_price_salery_level'. $tag_close .'</div>';
                    echo '<div class="col-10 col-sm-5 col-md-7 div_cart"><select class="participant" name="participant_price_salery_level_row_'. $course_id .'">';
                    $counter_row_price_salery_level_details = 0;
                    foreach ($course->price_salery_level_details as $key => $value) {
                        ++$counter_row_price_salery_level_details;
                        echo '<option value="'. $counter_row_price_salery_level_details .'"'. ($counter_row_price_salery_level_details === (int) $participants_data['participant_price_salery_level_row_number'] ? 'selected' : '') .'>'. $key .': '. $value .'</option>';
                    }
                    echo '</select></div>';
                }
                echo '</div>';
            } else {
                // registration with person details
                echo '<div class="row">';
                echo '<div class="col-12">&nbsp;</div>';
                foreach (D2U_Courses\Cart::getCourseParticipants($course_id) as $participant_id => $participant_data) {
                    if (is_array($participant_data)) {
                        echo '<div class="col-12"><p><b>'. \Sprog\Wildcard::get('d2u_courses_cart_participant') .'</b></p></div>';

                        // First name
                        echo '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_firstname'. $tag_close .'</div>';
                        echo '<div class="col-10 col-sm-5 col-md-7 div_cart"><input type="text" class="text_cart" name="participant_'. $course_id .'['. $participant_id .'][firstname]" value="'. (array_key_exists('lastname', $participant_data) ? $participant_data['firstname'] : '') .'" required maxlength="20"></div>';

                        // Delete button
                        echo '<div class="col-2 col-sm-1">';
                        $ask_delete = '';
                        if (1 === D2U_Courses\Cart::getCourseParticipantsNumber($course_id)) {
                            $ask_delete = ' onclick="return window.confirm(\''. $tag_open .'d2u_courses_cart_delete_course'. $tag_close .'\');"';
                        }
                        echo '<a href="'. rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'?delete='. $course_id .'&participant='. $participant_id .'" tabindex="-1" '. $ask_delete .'>';
                        echo '<img src="'. rex_addon::get('d2u_courses')->getAssetsUrl('delete.png') .'" alt="'. $tag_open .'d2u_courses_cart_delete'. $tag_close .'" class="delete_participant"></a>';
                        echo '</div>';

                        // Last name
                        echo '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_lastname'. $tag_close .'</div>';
                        echo '<div class="col-10 col-sm-5 col-md-7 div_cart"><input type="text" class="text_cart" name="participant_'. $course_id .'['. $participant_id .'][lastname]" value="'. (array_key_exists('lastname', $participant_data) ? $participant_data['lastname'] : '') .'" required maxlength="20"></div>';

                        // Age / Birthday
                        if ($ask_age > 0 || (0 === $ask_age && $course->category instanceof Category && in_array($course->category->getPartentRoot()->category_id, $ask_age_root_category_id, true))) { /** @phpstan-ignore-line */
                            echo '<div class="col-12 col-sm-6 col-md-4">'. $tag_open . (1 === $ask_age ? 'd2u_courses_birthdate' : 'd2u_courses_age'). $tag_close .'</div>'; /** @phpstan-ignore-line */
                            echo '<div class="col-10 col-sm-5 col-md-7 div_cart">';
                            if (1 === $ask_age && array_key_exists('birthday', $participant_data)) { /** @phpstan-ignore-line */
                                echo '<input type="date" class="date" name="participant_'. $course_id .'['. $participant_id .'][birthday]" value="'. $participant_data['birthday'] .'" required placeholder="'. $tag_open .'d2u_courses_date_placeholder'. $tag_close .'" min="1900-01-01" max="'. (date('Y') - 5) .'-01-01">';
                            } elseif (2 === $ask_age && array_key_exists('age', $participant_data)) { /** @phpstan-ignore-line */
                                echo '<input type="number" name="participant_'. $course_id .'['. $participant_id .'][age]" value="'. $participant_data['age'] .'" required>';
                            }
                            echo '</div>';
                        }

                        // Emergency phone number
                        if ($ask_emergency_number) { /** @phpstan-ignore-line */
                            echo '<div class="col-12 col-sm-6 col-md-4">'. \Sprog\Wildcard::get('d2u_courses_cart_emergency_number') .'</div>';
                            echo '<div class="col-10 col-sm-5 col-md-7 div_cart">';
                            echo '<input type="text" class="date" name="participant_'. $course_id .'['. $participant_id .'][emergency_number]" value="'. $participant_data['emergency_number'] .'" required>';
                            echo '</div>';
                        }

                        // Pension insurance id
                        if ($ask_penson_assurance_id) { /** @phpstan-ignore-line */
                            echo '<div class="col-12 col-sm-6 col-md-4">'. \Sprog\Wildcard::get('d2u_courses_cart_pension_insurance_id') .'</div>';
                            echo '<div class="col-10 col-sm-5 col-md-7 div_cart">';
                            echo '<input type="text" class="date" name="participant_'. $course_id .'['. $participant_id .'][pension_insurance_id]" value="'. $participant_data['pension_insurance_id'] .'" required>';
                            echo '</div>';
                        }

                        // Nationality
                        if ($ask_nationality) { /** @phpstan-ignore-line */
                            echo '<div class="col-12 col-sm-6 col-md-4">'. \Sprog\Wildcard::get('d2u_courses_cart_nationality') .'</div>';
                            echo '<div class="col-10 col-sm-5 col-md-7 div_cart">';
                            echo '<select name="participant_'. $course_id .'['. $participant_id .'][nationality]" value="'. $participant_data['nationality'] .'">';
                            $options_nationality = [
                                'Andere' => \Sprog\Wildcard::get('d2u_courses_cart_nationality_others'),
                                'CH' => \Sprog\Wildcard::get('d2u_courses_cart_nationality_CH'),
                                'DE' => \Sprog\Wildcard::get('d2u_courses_cart_nationality_DE'),
                                'FL' => \Sprog\Wildcard::get('d2u_courses_cart_nationality_FL'),
                                'FR' => \Sprog\Wildcard::get('d2u_courses_cart_nationality_FR'),
                                'IT' => \Sprog\Wildcard::get('d2u_courses_cart_nationality_IT')
                            ];
                            foreach ($options_nationality as $key => $text) {
                                echo '<option value="'. $key .'"'. ($key === $participant_data['nationality'] ? ' selected="selected"' : '') .'>'. $text .'</option>';
                            }
                            echo '</select>';
                            echo '</div>';
                        }

                        // Native language
                        if ($ask_nativeLanguage) { /** @phpstan-ignore-line */
                            echo '<div class="col-12 col-sm-6 col-md-4">'. \Sprog\Wildcard::get('d2u_courses_cart_nativeLanguage') .'</div>';
                            echo '<div class="col-10 col-sm-5 col-md-7 div_cart">';
                            echo '<select name="participant_'. $course_id .'['. $participant_id .'][nativeLanguage]" value="'. $participant_data['nativeLanguage'] .'">';
                            $options_nationality = [
                                'Andere' => \Sprog\Wildcard::get('d2u_courses_cart_nativeLanguage_others'),
                                'DE' => \Sprog\Wildcard::get('d2u_courses_cart_nativeLanguage_DE'),
                                'FR' => \Sprog\Wildcard::get('d2u_courses_cart_nativeLanguage_FR'),
                                'IT' => \Sprog\Wildcard::get('d2u_courses_cart_nativeLanguage_IT')
                            ];
                            foreach ($options_nationality as $key => $text) {
                                echo '<option value="'. $key .'"'. ($key === $participant_data['nativeLanguage'] ? ' selected="selected"' : '') .'>'. $text .'</option>';
                            }
                            echo '</select>';
                            echo '</div>';
                        }

                        // Gender
                        if ($ask_gender) { /** @phpstan-ignore-line */
                            echo '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_gender'. $tag_close .'</div>';
                            echo '<div class="col-10 col-sm-5 col-md-7 div_cart"><select class="participant" name="participant_'. $course_id .'['. $participant_id .'][gender]">';
                            echo '<option value="M"'. (array_key_exists('gender', $participant_data) && 'M' === $participant_data['gender'] ? ' selected' : '') .'>'. $tag_open .'d2u_courses_male'. $tag_close .'</option>';
                            echo '<option value="W"'. (array_key_exists('gender', $participant_data) && 'W' === $participant_data['gender'] ? ' selected' : '') .'>'. $tag_open .'d2u_courses_female'. $tag_close .'</option>';
                            echo '</select></div>';
                        }

                        // Salery level
                        if ($course->price_salery_level) {
                            echo '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_price_salery_level'. $tag_close .'</div>';
                            echo '<div class="col-10 col-sm-5 col-md-7 div_cart"><select class="participant" name="participant_'. $course_id .'['. $participant_id .'][price_salery_level_row_number]">';
                            $counter_row_price_salery_level_details = 0;
                            foreach ($course->price_salery_level_details as $key => $value) {
                                ++$counter_row_price_salery_level_details;
                                echo '<option value="'. $counter_row_price_salery_level_details .'"'. ($counter_row_price_salery_level_details === (int) $participant_data['price_salery_level_row_number'] ? 'selected' : '') .'>'. $key .': '. $value .'</option>';
                            }
                            echo '</select></div>';
                        }
                        echo '<div class="col-12">&nbsp;</div>';
                    }
                }
                echo '</div>';

                echo '<div class="row">';
                echo '<div class="col-12">';
                echo '<input type="submit" class="add_participant" name="participant_add['. $course_id .']" value="['. $tag_open .'d2u_courses_cart_add_participant'. $tag_close .']">';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="col-12"><br></div>';
        echo '<div class="row">';

        echo '<div class="col-12 col-sm-6 spacer d-flex">';
        echo '<button type="submit" class="save_cart flex-fill" name="participant_save" value="'. $tag_open .'d2u_courses_cart_save'. $tag_close .'">'. $tag_open .'d2u_courses_cart_save'. $tag_close .'</button>';
        echo '</div>';

        echo '<div class="col-12 col-sm-6 spacer d-flex">';
        echo '<input type="submit" class="save_cart flex-fill" name="request_courses" value="'. $tag_open .'d2u_courses_cart_checkout'. $tag_close .'">';
        echo '</div>';

        echo '</div>';
        echo '</form>';
        echo '</div>';
    }
}
