<?php
/**
 * Offers helper functions for language issues.
 */
class d2u_courses_lang_helper {
	/**
	 * @var string[] Array with english replacements. Key is the wildcard,
	 * value the replacement. 
	 */
	protected $replacements_english = [
		'd2u_courses_accept_conditions' => 'I accept the conditions.',
		'd2u_courses_accept_privacy_policy' => 'I have read the privacy policy and accept it.',
		'd2u_courses_accept_terms_of_participation' => 'And I also accept the terms of participations.',
		'd2u_courses_birthdate' => 'Date of birth',
		'd2u_courses_booked' => 'booked',
		'd2u_courses_cart' => 'Cart',
		'd2u_courses_cart_add' => 'Add to cart',
		'd2u_courses_cart_add_participant' => 'Add participant',
		'd2u_courses_cart_bill_address' => 'Bill address',
		'd2u_courses_cart_checkout' => 'Check out',
		'd2u_courses_cart_course_already_in' => 'Course is already in cart',
		'd2u_courses_cart_delete' => 'Delete from cart.',
		'd2u_courses_cart_delete_course' => 'Do you really want to delete course?',
		'd2u_courses_cart_empty' => 'Your cart is empty.',
		'd2u_courses_cart_error' => 'Error',
		'd2u_courses_cart_error_details' => 'Saving your registration failed with an error. Please call us. You can find our phone number in our impress.',
		'd2u_courses_cart_go_to' => 'Go to cart',
		'd2u_courses_cart_heading' => 'Registration',
		'd2u_courses_cart_save' => 'Save cart',
		'd2u_courses_cart_thanks' => 'Thank you for your registration',
		'd2u_courses_cart_thanks_details' => 'We prove your request manually. Please note that your registration is only legally binding after our confirmation. You will receive a confirmation email after our manual check. This takes up to 3-4 working days. If you do not receive our confirmation mail in exceptional cases, please call us. Thank you for your understanding!',
		'd2u_courses_city' => 'City',
		'd2u_courses_date' => 'Date',
		'd2u_courses_date_placeholder' => 'DD.MM.YYYY',
		'd2u_courses_details_course' => 'Additional information',
		'd2u_courses_discount' => 'price discount',
		'd2u_courses_downloads' => 'Downloads',
		'd2u_courses_email' => 'E-mail',
		'd2u_courses_fee' => 'Fee per person',
		'd2u_courses_female' => 'Female',
		'd2u_courses_firstname' => 'First name',
		'd2u_courses_gender' => 'Gender',
		'd2u_courses_lastname' => 'Last name',
		'd2u_courses_infolink' => 'Additional information',
		'd2u_courses_instructor' => 'Instructor',
		'd2u_courses_locations_city' => 'City',
		'd2u_courses_locations_room' => 'Room',
		'd2u_courses_locations_site_plan' => 'Site plan',
		'd2u_courses_male' => 'Male',
		'd2u_courses_make_booking' => 'Book now',
		'd2u_courses_mandatory_fields' => 'Mandatory fields',
		'd2u_courses_max' => 'maximum',
		'd2u_courses_min' => 'minimum',
		'd2u_courses_multinewsletter' => 'Please inform me by e-mail newsletter about our new program. You will receive 2 to 4 newsletters per year. You can revoke your registration at any time under the unsubscribe link provided in the newsletter.',
		'd2u_courses_oclock' => "o'clock",
		'd2u_courses_order_overview' => 'Order overview',
		'd2u_courses_participants' => 'Participants',
		'd2u_courses_payment' => 'Payment',
		'd2u_courses_payment_account_owner' => 'Account owner',
		'd2u_courses_payment_bank' => 'Bank name',
		'd2u_courses_payment_bic' => 'BIC',
		'd2u_courses_payment_data' => 'Payment data',
		'd2u_courses_payment_debit' => 'Debit',
		'd2u_courses_payment_iban' => 'IBAN',
		'd2u_courses_payment_transfer' => 'Transfer',
		'd2u_courses_phone' => 'Phone',
		'd2u_courses_registration_deadline' => 'Registration deadline',
		'd2u_courses_search_no_hits' => 'No hits',
		'd2u_courses_search_no_hits_text' => 'Your course search was not successfull. Try to search only parts of words might be more successfull.',
		'd2u_courses_search_results' => 'Search results for',
		'd2u_courses_statistic' => 'Statistical data',
		'd2u_courses_street' => 'Street, No.',
		'd2u_courses_wait_list' => 'wait list',
		'd2u_courses_zipcode' => 'ZIP code',
	];
	
	/**
	 * @var string[] Array with german replacements. Key is the wildcard,
	 * value the replacement. 
	 */
	protected $replacements_german = [
		'd2u_courses_accept_conditions' => 'Hiermit stimme ich den AGBs zu.',
		'd2u_courses_accept_privacy_policy' => 'Hiermit bestätige ich die Datenschutzbestimmungen gelesen zu haben und stimme ihr zu.',
		'd2u_courses_accept_terms_of_participation' => 'Und stimme ich den Teilnahmebedingungen zu.',
		'd2u_courses_birthdate' => 'Geburtsdatum',
		'd2u_courses_booked' => 'gebucht',
		'd2u_courses_cart' => 'Warenkorb',
		'd2u_courses_cart_add' => 'Zum Warenkorb hinzufügen',
		'd2u_courses_cart_add_participant' => 'Teilnehmer hinzufügen',
		'd2u_courses_cart_bill_address' => 'Rechnungsadresse',
		'd2u_courses_cart_checkout' => 'Zur Anmeldung',
		'd2u_courses_cart_course_already_in' => 'Kurs befindet sich im Warenkorb',
		'd2u_courses_cart_delete' => 'Aus Warenkorb entfernen.',
		'd2u_courses_cart_delete_course' => 'Wollen Sie den Kurs aus dem Warenkorb entfernen?',
		'd2u_courses_cart_empty' => 'Ihr Warenkorb ist noch leer.',
		'd2u_courses_cart_error' => 'Fehler',
		'd2u_courses_cart_error_details' => 'Beim Speichern der Anmeldung ist ein Fehler aufgetreten. Bitte wenden Sie sich telefonisch unter den im Impressum angegebenen Kontaktdaten an uns.',
		'd2u_courses_cart_go_to' => 'Zum Warenkorb',
		'd2u_courses_cart_heading' => 'Kursanmeldung',
		'd2u_courses_cart_save' => 'Eingaben merken',
		'd2u_courses_cart_thanks' => 'Vielen Dank für Ihre Anmeldung',
		'd2u_courses_cart_thanks_details' => 'Wir prüfen Ihre Anfrage. Bitte beachten Sie, dass Ihre Anmeldung erst nach unserer Bestätigung rechtsverbindlich wirksam ist. Bitte beachten Sie außerdem, dass Sie erst nach unserer manuellen Prüfung eine Bestätigungsmail erhalten werden. Dies dauert bis zu 3-4 Werktagen. Sollten wir uns in Ausnahmefällen nicht bei Ihnen melden, rufen Sie uns bitte an. Vielen Dank für Ihr Verständnis!',
		'd2u_courses_city' => 'Stadt',
		'd2u_courses_date' => 'Termin',
		'd2u_courses_date_placeholder' => 'TT.MM.JJJJ',
		'd2u_courses_details_course' => 'Informationen zum Kurs',
		'd2u_courses_discount' => 'ermäßigt',
		'd2u_courses_downloads' => 'Downloads',
		'd2u_courses_email' => 'E-Mail',
		'd2u_courses_fee' => 'Gebühr / Person',
		'd2u_courses_female' => 'Weiblich',
		'd2u_courses_firstname' => 'Vorname',
		'd2u_courses_gender' => 'Geschlecht',
		'd2u_courses_lastname' => 'Nachname',
		'd2u_courses_infolink' => 'Weiterführende Informationen',
		'd2u_courses_instructor' => 'Leiter/in',
		'd2u_courses_locations_city' => 'Ort',
		'd2u_courses_locations_room' => 'Raum',
		'd2u_courses_locations_site_plan' => 'Lageplan',
		'd2u_courses_make_booking' => 'Jetzt verbindlich buchen',
		'd2u_courses_male' => 'Männlich',
		'd2u_courses_mandatory_fields' => 'Pflichtfelder',
		'd2u_courses_max' => 'maximal',
		'd2u_courses_min' => 'minimal',
		'd2u_courses_multinewsletter' => 'Bitte informieren Sie per E-Mail Newsletter über ein neues Programm. Sie erhalten 2 bis 4 Newsletter pro Jahr. Ihre Anmeldung können Sie jederzeit unter den im Newsletter angegebenen Abmeldelink widerrufen.',
		'd2u_courses_oclock' => 'Uhr',
		'd2u_courses_order_overview' => 'Bestellübersicht',
		'd2u_courses_participants' => 'Anzahl Plätze',
		'd2u_courses_payment' => 'Zahlungsart',
		'd2u_courses_payment_account_owner' => 'Kontoinhaber(in)',
		'd2u_courses_payment_bank' => 'Bankname',
		'd2u_courses_payment_bic' => 'BIC',
		'd2u_courses_payment_data' => 'Zahlungsdaten',
		'd2u_courses_payment_debit' => 'Lastschrift',
		'd2u_courses_payment_iban' => 'IBAN',
		'd2u_courses_payment_transfer' => 'Überweisung',
		'd2u_courses_phone' => 'Telefon',
		'd2u_courses_registration_deadline' => 'An-/Abmeldeschluss',
		'd2u_courses_search_no_hits' => 'Keine Treffer',
		'd2u_courses_search_no_hits_text' => 'Ihre Suche in unseren Kursen und Angeboten erzielte keine Treffer.<br>Versuchenn Sie nur nach einem Wortteil zu suchen, z.B. statt "Malworkshop" einfach nur "Mal" oder "malen".',
		'd2u_courses_search_results' => 'Treffer für Ihre Suche nach',
		'd2u_courses_statistic' => 'Statistische Angaben',
		'd2u_courses_street' => 'Straße, Nr.',
		'd2u_courses_wait_list' => 'Warteliste',
		'd2u_courses_zipcode' => 'Postleitzahl',
	];

	/**
	 * Factory method.
	 * @return d2u_courses_lang_helper Object
	 */
	public static function factory() {
		return new d2u_courses_lang_helper();
	}
	
	/**
	 * Installs the replacement table for this addon.
	 */
	public function install() {
		$d2u_courses = rex_addon::get('d2u_courses');
		
		foreach($this->replacements_english as $key => $value) {
			$addWildcard = rex_sql::factory();

			foreach (rex_clang::getAllIds() as $clang_id) {
				// Load values for input
				if($d2u_courses->hasConfig('lang_replacement_'. $clang_id) && $d2u_courses->getConfig('lang_replacement_'. $clang_id) == 'german'
					&& isset($this->replacements_german) && isset($this->replacements_german[$key])) {
					$value = $this->replacements_german[$key];
				}
				else { 
					$value = $this->replacements_english[$key];
				}

				if(\rex_addon::get('sprog')->isAvailable()) {
					$select_pid_query = "SELECT pid FROM ". rex::getTablePrefix() ."sprog_wildcard WHERE wildcard = '". $key ."' AND clang_id = ". $clang_id;
					$select_pid_sql = rex_sql::factory();
					$select_pid_sql->setQuery($select_pid_query);
					if($select_pid_sql->getRows() > 0) {
						// Update
						$query = "UPDATE ". rex::getTablePrefix() ."sprog_wildcard SET "
							."`replace` = '". addslashes($value) ."', "
							."updatedate = '". rex_sql::datetime() ."', "
							."updateuser = '". rex::getUser()->getValue('login') ."' "
							."WHERE pid = ". $select_pid_sql->getValue('pid');
						$sql = rex_sql::factory();
						$sql->setQuery($query);						
					}
					else {
						$id = 1;
						// Before inserting: id (not pid) must be same in all langs
						$select_id_query = "SELECT id FROM ". rex::getTablePrefix() ."sprog_wildcard WHERE wildcard = '". $key ."' AND id > 0";
						$select_id_sql = rex_sql::factory();
						$select_id_sql->setQuery($select_id_query);
						if($select_id_sql->getRows() > 0) {
							$id = $select_id_sql->getValue('id');
						}
						else {
							$select_id_query = "SELECT MAX(id) + 1 AS max_id FROM ". rex::getTablePrefix() ."sprog_wildcard";
							$select_id_sql = rex_sql::factory();
							$select_id_sql->setQuery($select_id_query);
							if($select_id_sql->getValue('max_id') != NULL) {
								$id = $select_id_sql->getValue('max_id');
							}
						}
						// Save
						$query = "INSERT INTO ". rex::getTablePrefix() ."sprog_wildcard SET "
							."id = ". $id .", "
							."clang_id = ". $clang_id .", "
							."wildcard = '". $key ."', "
							."`replace` = '". addslashes($value) ."', "
							."createdate = '". rex_sql::datetime() ."', "
							."createuser = '". rex::getUser()->getValue('login') ."', "
							."updatedate = '". rex_sql::datetime() ."', "
							."updateuser = '". rex::getUser()->getValue('login') ."'";
						$sql = rex_sql::factory();
						$sql->setQuery($query);
					}
				}
			}
		}
	}

	/**
	 * Uninstalls the replacement table for this addon.
	 * @param int $clang_id Redaxo language ID, if 0, replacements of all languages
	 * will be deleted. Otherwise only one specified language will be deleted.
	 */
	public function uninstall($clang_id = 0) {
		foreach($this->replacements_english as $key => $value) {
			if(\rex_addon::get('sprog')->isAvailable()) {
				// Delete 
				$query = "DELETE FROM ". rex::getTablePrefix() ."sprog_wildcard WHERE wildcard = '". $key ."'";
				if($clang_id > 0) {
					$query .= " AND clang_id = ". $clang_id;
				}
				$select = rex_sql::factory();
				$select->setQuery($query);
			}
		}
	}
}