<?php
$cart = D2U_Courses\Cart::getCart();

// Delete course / participant
if(filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0) {
	$delete_course_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
	$delete_participant_id = filter_input(INPUT_GET, 'participant', FILTER_VALIDATE_INT);
	if(is_null($delete_participant_id)) {
		// Delete course
		$cart->deleteCourse($delete_course_id);
	}
	else {
		// Delete participant
		$cart->deleteParticipant($delete_course_id, $delete_participant_id);
	}
}

// Add course
if(filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT, ['options' => ['default'=> 0]]) > 0) {
	$add_course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
	$cart->addCourse($add_course_id);
}

$form_data = filter_input_array(INPUT_POST);
// Add empty participant
if(isset($form_data['participant_add'])) {
	foreach($form_data['participant_add'] as $course_id => $value) {
		$cart->addEmptyParticipant($course_id);
	}
}

// Add participant data
foreach($cart->getCourseIDs() as $course_id) {
	$course = new \D2U_Courses\Course($course_id);
	
	if(isset($form_data['participant_'. $course_id])) {
		// courses with person details
		foreach($form_data['participant_'. $course_id] as $participant_id => $patricipant_data) {
			$participant_price = "";
			$counter_row_price_salery_level_details = 0;
			foreach($course->price_salery_level_details as $description => $price) {
				$counter_row_price_salery_level_details++;
				if($counter_row_price_salery_level_details == $patricipant_data["price_salery_level_row_number"]) {
					$participant_price = $price;
					break;
				}
			}
			$participant_data = [
				"firstname" => trim(filter_var($patricipant_data["firstname"])),
				"lastname" => trim(filter_var($patricipant_data["lastname"])),
				"birthday" => trim(filter_var($patricipant_data["birthday"])),
				"age" => trim(filter_var($patricipant_data["age"])),
				"gender" => trim(filter_var($patricipant_data["gender"])),
				"price" => trim($participant_price),
				"price_salery_level_row_number" => trim(filter_var($patricipant_data["price_salery_level_row_number"])),
			];
			$cart->updateParticipant($course_id, $participant_id, $participant_data);
		}
	}
	if(isset($form_data['participant_number_'. $course_id])) {
		$participant_price = "";
		$counter_row_price_salery_level_details = 0;
		foreach($course->price_salery_level_details as $description => $price) {
			$counter_row_price_salery_level_details++;
			if($counter_row_price_salery_level_details == $form_data["price_salery_level_row_number"]) {
				$participant_price = $price;
				break;
			}
		}
		// courses with person number only
		$cart->updateParticipantNumber($course_id, $form_data['participant_number_'. $course_id], $participant_price, $form_data['participant_price_salery_level_row_'. $course_id]);
	}
}

// Forward so start page if cart is saved and shopping should be continued
if(isset($form_data['participant_save'])) {
	header('Location: '. rex_getUrl(rex_config::get('d2u_courses', 'article_id_courses')));
   	exit();
}

$sprog = rex_addon::get("sprog");
$tag_open = $sprog->getConfig('wildcard_open_tag');
$tag_close = $sprog->getConfig('wildcard_close_tag');
$ask_age = "REX_VALUE[1]" == "" ? 1 : "REX_VALUE[1]";
$ask_age_root_category_id = [];
if("REX_VALUE[3]" == 'true' && is_array(rex_var::toArray("REX_VALUE[4]"))) {
	$ask_age_root_category_id = rex_var::toArray("REX_VALUE[4]");
}	

// Invoice form
if(isset($form_data['invoice_form'])) {
	// Devide participants because Kufer SQL has different registration types
	$kufer_others = [];
	$kufer_children = [];
	$kufer_self = [];
	$mail_registration = [];
	foreach($cart->getCourseIDs() as $course_id) {
		$course = new D2U_Courses\Course($course_id);
		if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() && $course->import_type == "KuferSQL" && $course->course_number != "") {
			foreach($cart->getCourseParticipants($course_id) as $participant) {
				if($participant['firstname'] == $form_data['invoice_form']['firstname'] && $participant['lastname'] == $form_data['invoice_form']['lastname']) {
					// Treat children currently like normal persons 
					$kufer_self[$course_id][] = $participant;
					// Otherwise uncomment next lines
//					if(D2U_Courses\Cart::calculateAge($participant['birthday']) < 18) {
//						$kufer_children[$course_id][] = $participant;
//					}
//					else {
//						$kufer_self[$course_id][] = $participant;
//					}
				}
				else {
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
		}
		else {
			$mail_registration[$course_id] = $cart->getCourseParticipants($course_id);
		}
	}

	$error = false;
	// Create Kufer XML registrations
	if(count($kufer_self) > 0 && $cart->createXMLRegistration($kufer_self, $form_data['invoice_form'], "selbst") == false) {
		$error = true;
	}
	if(count($kufer_children) > 0 && $cart->createXMLRegistration($kufer_children, $form_data['invoice_form'], "kind") == false) {
		$error = true;
	}
	foreach($kufer_others as $course_id => $participant) {
		foreach($participant as $id => $cur_participant) {
			$cart->createXMLRegistration([$course_id => [$cur_participant]], $form_data['invoice_form'], "andere");
		}
	}
	if(count($mail_registration) > 0 && $cart->sendRegistrations($mail_registration, $form_data['invoice_form']) == false) {
		$error = true;
	}
	
	// MultiNewsletter Anmeldemail senden
	if(rex_addon::get('multinewsletter')->isAvailable() && is_array($form_data['invoice_form']['multinewsletter']) && count($form_data['invoice_form']['multinewsletter']) > 0) {
		$user = MultinewsletterUser::initByMail(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
		$anrede = $form_data['invoice_form']['gender'] == "W" ? 1 : 0;

		if($user !== false) {
			$user->title = $anrede;
			$user->firstname = $form_data['invoice_form']['firstname'];
			$user->lastname = $form_data['invoice_form']['lastname'];
			$user->clang_id = rex_clang::getCurrentId();
		}
		else {
			$user = MultinewsletterUser::factory(
				$form_data['invoice_form']['e-mail'],
				$anrede,
				'',
				$form_data['invoice_form']['firstname'],
				$form_data['invoice_form']['lastname'],
				rex_clang::getCurrentId()
			);
		}
		$user->group_ids = $form_data['invoice_form']['multinewsletter'];
		$user->status = 0;
		$user->subscriptiontype = 'web';
		$user->privacy_policy_accepted = 1;
		$user->activationkey = rand(100000, 999999);
		$user->save();

		// Send activationmail
		$user->sendActivationMail(
			rex_config::get('multinewsletter', 'sender'),
			rex_config::get('multinewsletter', 'lang_'. rex_clang::getCurrentId() .'_sendername'),
			rex_config::get('multinewsletter', 'lang_'. rex_clang::getCurrentId() .'_confirmsubject'),
			rex_config::get('multinewsletter', 'lang_'. rex_clang::getCurrentId() .'_confirmcontent')
		);
	}
	
	if($error == false) {
		print '<div class="col-12">';
		print '<h1>'. $tag_open .'d2u_courses_cart_thanks'. $tag_close .'</h1><p>'. $tag_open .'d2u_courses_cart_thanks_details'. $tag_close .'</p>';
		print '</div>';
		$cart->unsetCart();
	}
	else {
		print '<div class="col-12">';
		print '<h1>'. $tag_open .'d2u_courses_cart_error'. $tag_close .'</h1><p><a href="'. rex_getUrl(rex_config::get('d2u_helper', 'article_id_impress', rex_article::getSiteStartArticleId())) .'">'. $tag_open .'d2u_courses_cart_error_details'. $tag_close .'</a></p>';
		print '</div>';
	}
}
else if(isset($form_data['request_courses']) && $form_data['request_courses'] != "") {
	$payment_options = rex_config::get("d2u_courses", 'payment_options', []);

	// Anmeldeformular
	print '<div class="col-12">';
	print '<div>';
    print '<form action="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'" method="post" enctype="multipart/form-data">';

	print '<div class="registration_header cart_row_title">';
	print '<h1>'. $tag_open .'d2u_courses_cart_heading'. $tag_close .'</h1>';
	if(count($payment_options) > 0) {
		print $tag_open .'d2u_courses_cart_bill_address'. $tag_close;
	}
	else {
		print $tag_open .'d2u_courses_cart_contact_address'. $tag_close;		
	}
	print '</div>';

	print '<p>';
	print '<label class="cart_select" for="invoice_form-gender">'. $tag_open .'d2u_courses_title'. $tag_close .'</label>';
	print '<select class="cart_select" id="invoice_form-gender" name="invoice_form[gender]" size="1">';
	print '<option value="W">'. $tag_open .'d2u_courses_title_female'. $tag_close .'</option>';
	print '<option value="M">'. $tag_open .'d2u_courses_title_male'. $tag_close .'</option>';
	print '</select>';
	print '</p>';

	print '<p>';
    print '<label class="cart_text" for="invoice_form-firstname">'. $tag_open .'d2u_courses_firstname'. $tag_close .' *</label>';
    print '<input type="text" class="cart_text" name="invoice_form[firstname]" id="invoice_form-firstname" value="" maxlength="35" required>';
	print '</p>';
	
	print '<p>';
    print '<label class="cart_text" for="invoice_form-lastname">'. $tag_open .'d2u_courses_lastname'. $tag_close .' *</label>';
	print '<input type="text" class="cart_text" name="invoice_form[lastname]" id="invoice_form-lastname" value="" maxlength="35" required>';
	print '</p>';
	
	print '<p>';
    print '<label class="cart_text" for="invoice_form-address">'. $tag_open .'d2u_courses_street'. $tag_close .' *</label>';
    print '<input type="text" class="cart_text" name="invoice_form[address]" id="invoice_form-address" value="" maxlength="35" required>';
	print '</p>';
	
	print '<p>';
    print '<label class="cart_text" for="invoice_form-zipcode">'. $tag_open .'d2u_courses_zipcode'. $tag_close .' *</label>';
    print '<input type="number" class="cart_text" name="invoice_form[zipcode]" id="invoice_form-zipcode" value="" maxlength="5" required>';
	print '</p>';
	
	print '<p>';
    print '<label class="cart_text" for="invoice_form-city">'. $tag_open .'d2u_courses_city'. $tag_close .' *</label>';
    print '<input type="text" class="cart_text" name="invoice_form[city]" id="invoice_form-city" value="" maxlength="44" required>';
	print '</p>';
	
	print '<p>';
    print '<label class="cart_text" for="invoice_form-phone">'. $tag_open .'d2u_courses_phone'. $tag_close .' *</label>';
    print '<input type="text" class="cart_text" name="invoice_form[phone]" id="invoice_form-phone" value="" required>';
	print '</p>';
	
	print '<p>';
    print '<label class="cart_text" for="invoice_form-e-mail">'. $tag_open .'d2u_courses_email'. $tag_close .' *</label>';
    print '<input type="email" class="cart_text" name="invoice_form[e-mail]" id="invoice_form-e-mail" value="" required>';
	print '</p>';
	
	if(rex_config::get('d2u_courses', 'ask_vacation_pass', 'inactive') == 'active' && rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() === false) {
		print '<p>';
		print '<label class="cart_text" for="invoice_form-vacation_pass">'. $tag_open .'d2u_courses_vacation_pass'. $tag_close .'</label>';
		print '<input type="number" class="cart_text" name="invoice_form[vacation_pass]" id="invoice_form-vacation_pass" maxlength="5" value="">';
		print '</p>';
	}
	print '<br>';
	
	// Kufer Sync Plugin needs statistic values
	if(rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable() && "REX_VALUE[2]" == 'true') {
		print '<div class="registration_header cart_row_title">'. $tag_open .'d2u_courses_statistic'. $tag_close .'</div>';

		print '<p>';
		print '<label class="cart_text" for="invoice_form-birthday">'. $tag_open .'d2u_courses_birthdate'. $tag_close .'</label>';
		print '<input type="date" class="cart_date" name="invoice_form[birthday]" placeholder="'. $tag_open .'d2u_courses_date_placeholder'. $tag_close .'" min="1900-01-01" max="'. (date("Y") - 5) .'-01-01">';
		print '</p>';
	}
	
	if(count($payment_options) > 0) {
		print '<div class="registration_header cart_row_title">'. $tag_open .'d2u_courses_payment_data'. $tag_close .'</div>';

		print '<p>';
		print '<label class="cart_select" for="invoice_form-payment">'. $tag_open .'d2u_courses_payment'. $tag_close .':</label>';
		if(count($payment_options) > 1) {
			print '<select class="cart_select" id="invoice_form-payment" name="invoice_form[payment]" size="1" onChange="remove_required()">';
			if(in_array("direct_debit", $payment_options)) {
				print '<option value="L">'. $tag_open .'d2u_courses_payment_debit'. $tag_close .'</option>';
			}
			if(in_array("bank_transfer", $payment_options)) {
				print '<option value="Ü">'. $tag_open .'d2u_courses_payment_transfer'. $tag_close .'</option>';
			}
			if(in_array("cash", $payment_options)) {
				print '<option value="B">'. $tag_open .'d2u_courses_payment_cash'. $tag_close .'</option>';
			}
			print '</select>';
		}
		else {
			if(in_array("direct_debit", $payment_options)) {
				print '<input type="hidden" name="invoice_form[payment]" id="invoice_form-payment" value="L">';		
				print $tag_open .'d2u_courses_payment_debit'. $tag_close;
			}
			if(in_array("bank_transfer", $payment_options)) {
				print '<input type="hidden" name="invoice_form[payment]" id="invoice_form-payment" value="Ü">';		
				print $tag_open .'d2u_courses_payment_transfer'. $tag_close;
			}
			if(in_array("cash", $payment_options)) {
				print '<input type="hidden" name="invoice_form[payment]" id="invoice_form-payment" value="B">';		
				print $tag_open .'d2u_courses_payment_cash'. $tag_close;
			}
		}
		print '</p>';

		print '<p>';
		print '<label class="cart_text" for="invoice_form-account_owner">'. $tag_open .'d2u_courses_payment_account_owner'. $tag_close .' *</label>';
		print '<input type="text" class="cart_text" name="invoice_form[account_owner]" id="invoice_form-account_owner" maxlength="30" value="" required>';
		print '</p>';

		print '<p>';
		print '<label class="cart_text" for="invoice_form-bank">'. $tag_open .'d2u_courses_payment_bank'. $tag_close .' *</label>';
		print '<input type="text" class="cart_text" name="invoice_form[bank]" id="invoice_form-bank" maxlength="30" value="" required>';
		print '</p>';

		print '<p>';
		print '<label class="cart_text" for="invoice_form-iban">'. $tag_open .'d2u_courses_payment_iban'. $tag_close .' *</label>';
		print '<input type="text" class="cart_text" name="invoice_form[iban]" id="invoice_form-iban" maxlength="35" value="" required>';
		print '</p>';

		print '<p>';
		print '<label class="cart_text" for="invoice_form-bic">'. $tag_open .'d2u_courses_payment_bic'. $tag_close .' *</label>';
		print '<input type="text" class="cart_text" name="invoice_form[bic]" id="invoice_form-bic" maxlength="11" value="" required>';
		print '</p><br>';
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
	// Bestellübersicht
	print '<div class="registration_header cart_row_title"><h1>'. $tag_open .'d2u_courses_order_overview'. $tag_close .'</h1></div>';
	$has_minor_participants = false;
	foreach($cart->getCourseIDs() as $course_id) {
		$course = new D2U_Courses\Course($course_id);
		print '<div class="row">';
		print '<div class="col-12 spacer">';
		print '<div class="cart_row_title" style="background-color: '. ($course->category !== false ? $course->category->color : 'grey') .'">';
		print '<div class="row" data-match-height>';
		print '<div class="col-12">';
		print '<b>'. $course->name;
		if($course->course_number != "") {
			print ' ('. $course->course_number .')';
		}
		print '</b><br />';
		if($course->date_start != "" || $course->date_end != "" || $course->time != "") {
			print $tag_open .'d2u_courses_date'. $tag_close .': ';
			$date = '';
			if($course->date_start != "") {
				$date .= D2U_Courses\Cart::formatCourseDate($course->date_start);
			}
			if($course->date_end != "") {
				$date .= ' - '. D2U_Courses\Cart::formatCourseDate($course->date_end);
			}
			if($course->time != "") {
				if($date != "") {
					$date .= ', ';
				}
				$date .= $course->time;
//				if(strpos($course->time, "Uhr") === false) {
//					$date .= ' Uhr';
//				}
			}
			print $date .'<br>';
		}
		if(!$course->price_salery_level && $course->price) {
			print $tag_open .'d2u_courses_fee'. $tag_close . ': '. number_format($course->price, 2, ",", ".") .' €';
			if($course->price_discount > 0) {
				print ' ('. $tag_open .'d2u_courses_discount'. $tag_close .': '. number_format($course->price_discount, 2, ",", ".") .' €)' .'<br>';
			}
		}
		if($course->registration_possible == "yes_number") {
			$participant_data = $cart->getCourseParticipants($course_id);
			print '<ul>';
			print '<li>'. $tag_open .'d2u_courses_participant_number'. $tag_close .': '. $participant_data['participant_number'];
			if($course->price_salery_level) {
				$counter_row_price_salery_level_details = 0;
				foreach($course->price_salery_level_details as $description => $price) {
					$counter_row_price_salery_level_details++;
					if($counter_row_price_salery_level_details == $participant_data["participant_price_salery_level_row_number"]) {
						print ', '. $tag_open .'d2u_courses_fee'. $tag_close .': '. $participant_data['participant_price'] .' ('. $tag_open .'d2u_courses_price_salery_level'. $tag_close .': '. $description .')';
						break;
					}
				}
			}

			print '</li>';
			print '</ul>';
		}
		else {
			// registration with person details
			print '<ul>';
			foreach($cart->getCourseParticipants($course_id) as $id => $participant) {
				print '<li>'. $participant['firstname'] .' '. $participant['lastname'];

				if("REX_VALUE[5]" !== 'true' || (in_array($course->category->getPartentRoot()->category_id, $ask_age_root_category_id))) {
					print ' (';
					$age_seperator = false;
					if($ask_age == 1 && $participant['birthday']) {
						print $tag_open .'d2u_courses_birthdate'. $tag_close .': '. D2U_Courses\Cart::formatCourseDate($participant['birthday']);
						$age_seperator = true;
					}
					elseif ($ask_age == 2) {
						print $tag_open .'d2u_courses_age'. $tag_close .': '. D2U_Courses\Cart::formatCourseDate($participant['age']);
						$age_seperator = true;
					}
					if("REX_VALUE[5]" !== 'true') {
						if($age_seperator === true ) {
							print ', ';
						}
						print $participant["gender"] == "M" ? $tag_open .'d2u_courses_male'. $tag_close : $tag_open .'d2u_courses_female'. $tag_close;
					}
					print ')';
				}
				if($course->price_salery_level) {
					$counter_row_price_salery_level_details = 0;
					foreach($course->price_salery_level_details as $description => $price) {
						$counter_row_price_salery_level_details++;
						if($counter_row_price_salery_level_details == $participant["price_salery_level_row_number"]) {
							print ', '. $tag_open .'d2u_courses_fee'. $tag_close .': '. $participant['price'] .' ('. $tag_open .'d2u_courses_price_salery_level'. $tag_close .': '. $description .')';
							break;
						}
					}
				}
				print '</li>';
				if($cart::calculateAge($participant['birthday']) < 18) {
					$has_minor_participants = true;
				}
			}
			print '</ul>';
		}
		print '</div>';
		print '</div>';
		print '</div>';
		print '</div>';
		print '</div>';
	}
	// Are minors allowed to retun home by their own?
	if(rex_config::get('d2u_courses', 'ask_kids_go_home_alone', 'inactive') == 'active') {
		print '<p class="cart_checkbox">';
		print '<input type="checkbox" class="cart_checkbox" name="invoice_form[kids_go_home_alone]" id="invoice_kids_go_home_alone" value="yes">';
		print '<label class="cart_checkbox" for="invoice_kids_go_home_alone">'. $tag_open .'d2u_courses_kids_go_home_alone'. $tag_close .'</label></p>';
	}

	print '<p>&nbsp;</p>';
	if(rex_config::get('d2u_courses', 'article_id_conditions', 0) > 0 || rex_config::get('d2u_courses', 'article_id_terms_of_participation', 0) > 0) {
		print '<p class="cart_checkbox">';
		print '<input type="checkbox" class="cart_checkbox" name="invoice_form[conditions]" id="invoice_form-conditions" value="yes" required>';
		print '<label class="cart_checkbox" for="invoice_form-conditions">';
		if(rex_config::get('d2u_courses', 'article_id_conditions', 0) > 0) {
			print '<a href="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_conditions')) .'" target="blank">'. $tag_open .'d2u_courses_accept_conditions'. $tag_close .'</a>';
		}
		if(rex_config::get('d2u_courses', 'article_id_conditions', 0) > 0 && rex_config::get('d2u_courses', 'article_id_terms_of_participation', 0) > 0) {
			print '<br>';
		}
		if(rex_config::get('d2u_courses', 'article_id_terms_of_participation', 0) > 0) {
			print '<a href="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_terms_of_participation')) .'" target="blank">'. $tag_open .'d2u_courses_accept_terms_of_participation'. $tag_close .'</a>';
		}
		print ' *</label></p>';
	}

	if(rex_config::get('d2u_helper', 'article_id_privacy_policy', 0) > 0) {
		print '<p class="cart_checkbox">';
		print '<input type="checkbox" class="cart_checkbox" name="invoice_form[privacy_policy]" id="invoice_form-privacy_policy" value="yes" required>';
		print '<label class="cart_checkbox" for="invoice_form-privacy_policy"><a href="'. rex_getUrl(rex_config::get('d2u_helper', 'article_id_privacy_policy')) .'" target="blank">'.
			$tag_open .'d2u_courses_accept_privacy_policy'. $tag_close .'</a> *</label>';
		print '</p>';
	}

	if(rex_addon::get('multinewsletter')->isAvailable() && rex_config::get('d2u_courses', 'multinewsletter_subscribe', 'hide') == 'show') {
		print '<p class="cart_checkbox">';
		if(count(rex_config::get('d2u_courses', 'multinewsletter_group', [])) == 1) {
			print '<input type="checkbox" class="cart_checkbox" name="invoice_form[multinewsletter][]" id="invoice_form-multinewsletter" value="'. rex_config::get('d2u_courses', 'multinewsletter_group', [])[0] .'">';
			print '<label class="cart_checkbox" for="invoice_form-multinewsletter">'. $tag_open .'d2u_courses_multinewsletter'. $tag_close .'</label>';
		}
		else if(count(rex_config::get('d2u_courses', 'multinewsletter_group', [])) > 1) {
			print $tag_open .'d2u_courses_multinewsletter'. $tag_close .'<br>';
			foreach(rex_config::get('d2u_courses', 'multinewsletter_group', []) as $newsletter_group_id) {
				$multinewsletter_group = new MultinewsletterGroup($newsletter_group_id);
				if($multinewsletter_group && $multinewsletter_group->id > 0) {
					print '<input type="checkbox" class="cart_checkbox" name="invoice_form[multinewsletter][]" id="invoice_form-multinewsletter" value="'. $multinewsletter_group->id .'">';
					print '<label class="cart_checkbox" for="invoice_form-multinewsletter">'. $multinewsletter_group->name .'</label><br>';
				}
			}
		}
		print '</p>';
	}

	print '* '. $tag_open .'d2u_courses_mandatory_fields'. $tag_close .'<br><br>';
	print '<p class="formsubmit formsubmit">';
    print '<input type="submit" class="submit save_cart" name="invoice_form[submit]" id="invoice_form-submit" value="'. $tag_open .'d2u_courses_make_booking'. $tag_close .'">';
	print '</p>';

	print '</form>';
	print '</div>';
	print '</div>';
}
else {
	// Warenkorb
	print '<div class="col-12 col-md-3 spacer">';
	print '<div class="cart_box">';
	print '<div class="view_head">';
	print '<img src="'.	rex_addon::get("d2u_courses")->getAssetsUrl("cart.png") .'" alt="'. $tag_open .'d2u_courses_cart'. $tag_close .'">';
	print '</div>';
	print '<div class="cart_box_title">'. $tag_open .'d2u_courses_cart'. $tag_close .'</div>';
	print '</div>';
	print '</div>';

	if(count($cart->getCourseIDs()) == 0) {
		print '<div class="col-12 col-md-9 spacer">';
		print '<div class="cart_row_title" id="cart_empty">'. $tag_open .'d2u_courses_cart_empty'. $tag_close .'</div>';
		print '</div>';
	}
	else {
		print '<div class="col-12 col-md-9">';
		print '<form action="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'" method="post">';	
		print '<div class="row">';
		foreach($cart->getCourseIDs() as $course_id) {
			$course = new D2U_Courses\Course($course_id);
			print '<div class="col-12 spacer">';
			print '<div class="cart_row_title" style="background-color: '. ($course->category !== false ? $course->category->color : 'grey') .'">';
			print '<div class="row">';
			print '<div class="col-12">';
			print '<a href="'. $course->getURL(true) .'" title="'. $course->name .'" class="cart_course_title">';
			print $course->name;
			if($course->course_number != "") {
				print ' ('. $course->course_number .')';
			}
			print '</a><br />';
			if($course->date_start != "" || $course->date_end != "" || $course->time != "") {
				print $tag_open .'d2u_courses_date'. $tag_close .': ';
				$date = '';
				if($course->date_start != "") {
					$date .= D2U_Courses\Cart::formatCourseDate($course->date_start);
				}
				if($course->date_end != "") {
					$date .= ' - '. D2U_Courses\Cart::formatCourseDate($course->date_end);
				}
				if($course->time != "") {
					if($date && $course->time != "") {
						$date .= ', ';
					}
					$date .= $course->time;
//					if(strpos($course->time, "Uhr") === false) {
//						$date .= ' Uhr';
//					}
				}
				print $date .'<br>';
			}
			if($course->price > 0) {
				print $tag_open .'d2u_courses_fee'. $tag_close . ': '. number_format($course->price, 2, ",", ".") .' €';
				if($course->price_discount > 0) {
					print ' ('. $tag_open .'d2u_courses_discount'. $tag_close .': '. number_format($course->price_discount, 2, ",", ".") .' €)' .'<br>';
				}
			}
			
			if($course->registration_possible == "yes_number") {
				// registration without person details
				$participants_data = $cart->getCourseParticipants($course_id);
				print '<br>';
				print '<div class="row">';
				print '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_participant_number'. $tag_close .'</div>'
					. '<div class="col-10 col-sm-5 col-md-7 div_cart"><input type="number" class="text_cart" name="participant_number_'. $course_id .'" value="'. ($participants_data['participant_number'] ?: 1) .'" min="1" max="50"></div>';
				print '<div class="col-2 col-sm-1">';
				print '<a href="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'?delete='. $course_id .'" onclick="return window.confirm(\''. $tag_open .'d2u_courses_cart_delete_course'. $tag_close .'\');">';
				print '<img src="'. rex_addon::get("d2u_courses")->getAssetsUrl("delete.png") .'" alt="'. $tag_open .'d2u_courses_cart_delete'. $tag_close .'" class="delete_participant"></a>';
				print '</div>';
				if($course->price_salery_level) {
					print '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_price_salery_level'. $tag_close .'</div>';
					print '<div class="col-10 col-sm-5 col-md-7 div_cart"><select class="participant" name="participant_price_salery_level_row_'. $course_id .'">';
					$counter_row_price_salery_level_details = 0;
					foreach($course->price_salery_level_details as $key => $value) {
						$counter_row_price_salery_level_details++;
						print '<option value="'. $counter_row_price_salery_level_details .'"'. ($counter_row_price_salery_level_details == $participants_data['participant_price_salery_level_row_number'] ? 'selected' : '') .'>'. $key .': '. $value .'</option>';
					}
					print '</select></div>';
				}
				print '</div>';
			}
			else {
				// registration with person details
				print '<div class="row">';
				print '<div class="col-12">&nbsp;</div>';
				foreach($cart->getCourseParticipants($course_id) as $participant_id => $participant_data) {
					// First name
					print '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_firstname'. $tag_close .'</div>';
					print '<div class="col-10 col-sm-5 col-md-7 div_cart"><input type="text" class="text_cart" name="participant_'. $course_id .'['. $participant_id .'][firstname]" value="'. $participant_data['firstname'] .'" required maxlength="20"></div>';

					// Delete button
					print '<div class="col-2 col-sm-1">';
					$ask_delete = "";
					if($cart->getCourseParticipantsNumber($course_id) == 1) {
						$ask_delete = ' onclick="return window.confirm(\''. $tag_open .'d2u_courses_cart_delete_course'. $tag_close .'\');"';
					}
					print '<a href="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'?delete='. $course_id .'&participant='. $participant_id .'" '. $ask_delete .'>';
					print '<img src="'. rex_addon::get("d2u_courses")->getAssetsUrl("delete.png") .'" alt="'. $tag_open .'d2u_courses_cart_delete'. $tag_close .'" class="delete_participant"></a>';
					print '</div>';

					// Last name
					print '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_lastname'. $tag_close .'</div>';
					print '<div class="col-10 col-sm-5 col-md-7 div_cart"><input type="text" class="text_cart" name="participant_'. $course_id .'['. $participant_id .'][lastname]" value="'. $participant_data['lastname'] .'" required maxlength="20"></div>';

					// Age / Birthday
					if(in_array($course->category->getPartentRoot()->category_id, $ask_age_root_category_id)) {
						print '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .($ask_age == 1 ? 'd2u_courses_birthdate' : 'd2u_courses_age'). $tag_close .'</div>';
						print '<div class="col-10 col-sm-5 col-md-7 div_cart">';
						if($ask_age == 1) {
							print '<input type="date" class="date" name="participant_'. $course_id .'['. $participant_id .'][birthday]" value="'. $participant_data['birthday'] .'" required placeholder="'. $tag_open .'d2u_courses_date_placeholder'. $tag_close .'" min="1900-01-01" max="'. (date("Y") - 5) .'-01-01">';
						}
						elseif ($ask_age == 2) {
							print '<input type="number" name="participant_'. $course_id .'['. $participant_id .'][age]" value="'. $participant_data['age'] .'" required>';
						}
						print '</div>';
					}
					
					// Gender
					if("REX_VALUE[5]" !== 'true') {
						print '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_gender'. $tag_close .'</div>';
						print '<div class="col-10 col-sm-5 col-md-7 div_cart"><select class="participant" name="participant_'. $course_id .'['. $participant_id .'][gender]">';
						print '<option value="M"'. ($participant_data["gender"] == "M" ? ' selected' : '') .'>'. $tag_open .'d2u_courses_male'. $tag_close .'</option>';
						print '<option value="W"'. ($participant_data["gender"] == "W" ? ' selected' : '') .'>'. $tag_open .'d2u_courses_female'. $tag_close .'</option>';
						print '</select></div>';
					}
					
					// Salery level
					if($course->price_salery_level) {
						print '<div class="col-12 col-sm-6 col-md-4">'. $tag_open .'d2u_courses_price_salery_level'. $tag_close .'</div>';
						print '<div class="col-10 col-sm-5 col-md-7 div_cart"><select class="participant" name="participant_'. $course_id .'['. $participant_id .'][price_salery_level_row_number]">';
						$counter_row_price_salery_level_details = 0;
						foreach($course->price_salery_level_details as $key => $value) {
							$counter_row_price_salery_level_details++;
							print '<option value="'. $counter_row_price_salery_level_details .'"'. ($counter_row_price_salery_level_details == $participant_data['price_salery_level_row_number'] ? 'selected' : '') .'>'. $key .': '. $value .'</option>';
						}
						print '</select></div>';
					}
					print '<div class="col-12">&nbsp;</div>';
				}
				print '</div>';
				
				print '<div class="row">';
				print '<div class="col-12">';
				print '<input type="submit" class="add_participant" name="participant_add['. $course_id .']" value="['. $tag_open .'d2u_courses_cart_add_participant'. $tag_close .']">';
				print '</div>';
				print '</div>';
			}
			print '</div>';
			print '</div>';
			print '</div>';
			print '</div>';
		}
		print '</div>';
		print '<div class="col-12"><br></div>';
		print '<div class="row">';
		
		print '<div class="col-12 col-sm-6 spacer">';
		print '<input type="submit" class="save_cart" name="participant_save" value="'. $tag_open .'d2u_courses_cart_save'. $tag_close .'">';
		print '</div>';

		print '<div class="col-12 col-sm-6 spacer">';
		print '<input type="submit" class="save_cart" name="request_courses" value="'. $tag_open .'d2u_courses_cart_checkout'. $tag_close .'">';
		print '</div>';

		print '</div>';
		print '</form>';
		print '</div>';
	}
}