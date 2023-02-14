<?php
/**
 * Offers helper functions for language issues.
 */
class d2u_courses_lang_helper extends \D2U_Helper\ALangHelper {
	/**
	 * @var array<string, string> Array with english replacements. Key is the wildcard,
	 * value the replacement. 
	 */
	var $replacements_english = [
		'd2u_courses_accept_conditions' => 'I accept the conditions.',
		'd2u_courses_accept_privacy_policy' => 'I have read the privacy policy and accept it.',
		'd2u_courses_accept_terms_of_participation' => 'I accept the terms of participations.',
		'd2u_courses_age' => 'Age when course starts',
		'd2u_courses_birthdate' => 'Date of birth',
		'd2u_courses_booked' => 'booked',
		'd2u_courses_booked_complete' => 'booked up',
		'd2u_courses_cart' => 'Cart',
		'd2u_courses_cart_add' => 'Add to cart',
		'd2u_courses_cart_add_participant' => 'Add additional participant',
		'd2u_courses_cart_bill_address' => 'Billing address',
		'd2u_courses_cart_contact_address' => 'Contact address',
		'd2u_courses_cart_checkout' => 'Check out',
		'd2u_courses_cart_course_already_in' => 'Event is already in cart',
		'd2u_courses_cart_delete' => 'Delete from cart.',
		'd2u_courses_cart_delete_course' => 'Do you really want to delete event?',
		'd2u_courses_cart_emergency_number' => 'Emergency phone number',
		'd2u_courses_cart_empty' => 'Your cart is empty.',
		'd2u_courses_cart_error' => 'Error',
		'd2u_courses_cart_error_details' => 'Saving your registration failed with an error. Please call us. You can find our phone number in our impress.',
		'd2u_courses_cart_go_to' => 'Go to cart',
		'd2u_courses_cart_iban_not_sepa' => 'To avoid extra costs, check whether your bank allows free sepa direct debits.',
		'd2u_courses_cart_iban_wrong' => 'IBAN is wrong. Please check.',
		'd2u_courses_cart_participant' => 'Participant',
		'd2u_courses_cart_save' => 'Save cart and continue shopping',
		'd2u_courses_cart_thanks' => 'Thank you for your registration',
		'd2u_courses_cart_thanks_details' => 'We prove your request manually. Please note that your registration is only legally binding after our confirmation. You will receive a confirmation email after our manual check. This takes up to 3-4 working days. If you do not receive our confirmation mail in exceptional cases, please call us. Thank you for your understanding!',
		'd2u_courses_city' => 'City',
		'd2u_courses_company' => 'Company',
		'd2u_courses_country' => 'Country',
		'd2u_courses_date' => 'Date',
		'd2u_courses_date_placeholder' => 'DD.MM.YYYY',
		'd2u_courses_details_course' => 'Additional information',
		'd2u_courses_discount' => 'price discount',
		'd2u_courses_downloads' => 'Downloads',
		'd2u_courses_email' => 'E-mail',
		'd2u_courses_email_verification' => 'E-mail verification',
		'd2u_courses_email_verification_failure' => 'Entries are not the same. Please correct your e-mail address.',
		'd2u_courses_fee' => 'Fee per person',
		'd2u_courses_female' => 'Female',
		'd2u_courses_firstname' => 'First name',
		'd2u_courses_gender' => 'Gender',
		'd2u_courses_kids_go_home_alone' => 'Registered minors are allowed to return home by their own.',
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
		'd2u_courses_participant_number' => 'Anzahl Anmeldungen',
		'd2u_courses_oclock' => "o'clock",
		'd2u_courses_order_overview' => 'Order overview',
		'd2u_courses_participants' => 'Participants',
		'd2u_courses_payment' => 'Payment',
		'd2u_courses_payment_account_owner' => 'Account owner',
		'd2u_courses_payment_bank' => 'Bank name',
		'd2u_courses_payment_bic' => 'BIC',
		'd2u_courses_payment_cash' => 'Cash',
		'd2u_courses_payment_data' => 'Payment data',
		'd2u_courses_payment_debit' => 'Debit',
		'd2u_courses_payment_iban' => 'IBAN',
		'd2u_courses_payment_transfer' => 'Transfer',
		'd2u_courses_phone' => 'Phone',
		'd2u_courses_price_salery_level' => 'Monthly family gross income',
		'd2u_courses_price_salery_level_details' => 'The price is based on the monthly family gross income. We trust that the classification will be made honestly. We reserve the right to check on our part.',
		'd2u_courses_registration_deadline' => 'Registration deadline',
		'd2u_courses_search_no_hits' => 'No hits',
		'd2u_courses_search_no_hits_text' => 'Your search was not successfull. Try to search only parts of words might be more successfull.',
		'd2u_courses_search_results' => 'Search results for',
		'd2u_courses_statistic' => 'Statistical data',
		'd2u_courses_street' => 'Street, No.',
		'd2u_courses_title' => 'Title',
		'd2u_courses_title_female' => 'Mrs',
		'd2u_courses_title_male' => 'Mr',
		'd2u_courses_business' => 'Business',
		'd2u_courses_type_private' => 'Private person',
		'd2u_courses_vacation_pass' => 'Ferienpass Nummer',
		'd2u_courses_wait_list' => 'wait list',
		'd2u_courses_zipcode' => 'ZIP code',
	];
	
	/**
	 * @var array<string, string> Array with german replacements. Key is the wildcard,
	 * value the replacement. 
	 */
	protected array $replacements_german = [
		'd2u_courses_accept_conditions' => 'Hiermit stimme ich den AGBs zu.',
		'd2u_courses_accept_privacy_policy' => 'Hiermit bestätige ich die Datenschutzbestimmungen gelesen zu haben und stimme ihr zu.',
		'd2u_courses_accept_terms_of_participation' => 'Hiermit stimme ich den Teilnahmebedingungen zu.',
		'd2u_courses_age' => 'Alter bei Kursbeginn',
		'd2u_courses_birthdate' => 'Geburtsdatum',
		'd2u_courses_booked' => 'gebucht',
		'd2u_courses_booked_complete' => 'ausgebucht',
		'd2u_courses_cart' => 'Warenkorb',
		'd2u_courses_cart_add' => 'Zum Warenkorb hinzufügen',
		'd2u_courses_cart_add_participant' => 'weiteren Teilnehmer hinzufügen',
		'd2u_courses_cart_bill_address' => 'Rechnungsdaten',
		'd2u_courses_cart_contact_address' => 'Kontaktdaten',
		'd2u_courses_cart_checkout' => 'Zur Anmeldung',
		'd2u_courses_cart_course_already_in' => 'Angebot befindet sich im Warenkorb',
		'd2u_courses_cart_delete' => 'Aus Warenkorb entfernen.',
		'd2u_courses_cart_delete_course' => 'Wollen Sie das Angebot aus dem Warenkorb entfernen?',
		'd2u_courses_cart_emergency_number' => 'Notfallnummer',
		'd2u_courses_cart_empty' => 'Ihr Warenkorb ist noch leer.',
		'd2u_courses_cart_error' => 'Fehler',
		'd2u_courses_cart_error_details' => 'Beim Speichern der Anmeldung ist ein Fehler aufgetreten. Bitte wenden Sie sich telefonisch unter den im Impressum angegebenen Kontaktdaten an uns.',
		'd2u_courses_cart_go_to' => 'Zum Warenkorb',
		'd2u_courses_cart_iban_not_sepa' => 'Um unerwartete Kosten zu vermeiden prüfen Sie bitte, ob Ihre Bank kostenlose SEPA Lastschriften ermöglicht.',
		'd2u_courses_cart_iban_wrong' => 'IBAN ist falsch. Bitte prüfen Sie die Eingabe.',
		'd2u_courses_cart_participant' => 'Teilnehmer*in',
		'd2u_courses_cart_save' => 'Eingaben merken und weiter einkaufen',
		'd2u_courses_cart_thanks' => 'Vielen Dank für Ihre Anmeldung',
		'd2u_courses_cart_thanks_details' => 'Wir prüfen Ihre Anfrage. Bitte beachten Sie, dass Ihre Anmeldung erst nach unserer Bestätigung rechtsverbindlich wirksam ist. Bitte beachten Sie außerdem, dass Sie erst nach unserer manuellen Prüfung eine Bestätigungsmail erhalten werden. Dies dauert bis zu 3-4 Werktagen. Sollten wir uns in Ausnahmefällen nicht bei Ihnen melden, rufen Sie uns bitte an. Vielen Dank für Ihr Verständnis!',
		'd2u_courses_city' => 'Stadt',
		'd2u_courses_company' => 'Firma',
		'd2u_courses_country' => 'Land',
		'd2u_courses_date' => 'Termin',
		'd2u_courses_date_placeholder' => 'TT.MM.JJJJ',
		'd2u_courses_details_course' => 'Weitere Informationen',
		'd2u_courses_discount' => 'ermäßigt',
		'd2u_courses_downloads' => 'Downloads',
		'd2u_courses_email' => 'E-Mail',
		'd2u_courses_email_verification' => 'E-Mail Verifizierung',
		'd2u_courses_email_verification_failure' => 'Eingaben stimmen nicht überein. Bitte korrigieren Sie die E-Mail-Adresse.',
		'd2u_courses_fee' => 'Gebühr / Person',
		'd2u_courses_female' => 'Weiblich',
		'd2u_courses_firstname' => 'Vorname',
		'd2u_courses_gender' => 'Geschlecht',
		'd2u_courses_lastname' => 'Nachname',
		'd2u_courses_infolink' => 'Weiterführende Informationen',
		'd2u_courses_instructor' => 'Leiter/in',
		'd2u_courses_kids_go_home_alone' => 'Angemeldete minderjährige Kinder dürfen alleine nach Hause gehen.',
		'd2u_courses_locations_city' => 'Ort',
		'd2u_courses_locations_room' => 'Raum',
		'd2u_courses_locations_site_plan' => 'Lageplan',
		'd2u_courses_make_booking' => 'Jetzt verbindlich buchen',
		'd2u_courses_male' => 'Männlich',
		'd2u_courses_mandatory_fields' => 'Pflichtfelder',
		'd2u_courses_max' => 'maximal',
		'd2u_courses_min' => 'minimal',
		'd2u_courses_multinewsletter' => 'Bitte informieren Sie mich per E-Mail-Newsletter wenn ein neues Programm erscheint. Sie erhalten 2 bis 4 Newsletter pro Jahr. Ihre Anmeldung können Sie jederzeit unter den im Newsletter angegebenen Abmeldelink widerrufen.',
		'd2u_courses_oclock' => 'Uhr',
		'd2u_courses_order_overview' => 'Bestellübersicht',
		'd2u_courses_participants' => 'Anzahl Plätze',
		'd2u_courses_payment' => 'Zahlungsart',
		'd2u_courses_payment_account_owner' => 'Kontoinhaber(in)',
		'd2u_courses_payment_bank' => 'Bankname',
		'd2u_courses_payment_bic' => 'BIC',
		'd2u_courses_payment_cash' => 'Barzahlung',
		'd2u_courses_payment_data' => 'Zahlungsdaten',
		'd2u_courses_payment_debit' => 'SEPA Lastschrift',
		'd2u_courses_payment_iban' => 'IBAN',
		'd2u_courses_payment_transfer' => 'Überweisung',
		'd2u_courses_phone' => 'Telefon',
		'd2u_courses_price_salery_level' => 'Monatliches Familienbruttoeinkommen',
		'd2u_courses_price_salery_level_details' => 'Der Preis richtet sich nach dem monatlichen Familienbruttoeinkommen. Wir vertrauen darauf, dass Ihre Einstufung wahrheitsgemäß und ehrlich erfolgt. Eine Überprüfung unsererseits bleibt uns vorbehalten.',
		'd2u_courses_registration_deadline' => 'An-/Abmeldeschluss',
		'd2u_courses_search_no_hits' => 'Keine Treffer',
		'd2u_courses_search_no_hits_text' => 'Ihre Suche in unseren Kursen und Angeboten erzielte keine Treffer.<br>Versuchenn Sie nur nach einem Wortteil zu suchen, z.B. statt "Malworkshop" einfach nur "Mal" oder "malen".',
		'd2u_courses_search_results' => 'Treffer für Ihre Suche nach',
		'd2u_courses_statistic' => 'Statistische Angaben',
		'd2u_courses_street' => 'Straße, Nr.',
		'd2u_courses_title' => 'Anrede',
		'd2u_courses_title_female' => 'Frau',
		'd2u_courses_title_male' => 'Herr',
		'd2u_courses_business' => 'Geschäftlich',
		'd2u_courses_type_private' => 'Privatperson',
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
	public function install():void {
		foreach($this->replacements_english as $key => $value) {
			foreach (rex_clang::getAllIds() as $clang_id) {
				$lang_replacement = rex_config::get('d2u_courses', 'lang_replacement_'. $clang_id, '');

				// Load values for input
				if($lang_replacement === 'german' && isset($this->replacements_german) && isset($this->replacements_german[$key])) {
					$value = $this->replacements_german[$key];
				}
				else { 
					$value = $this->replacements_english[$key];
				}

				$overwrite = rex_config::get('d2u_courses', 'lang_wildcard_overwrite', false) === "true" ? true : false;
				parent::saveValue($key, $value, $clang_id, $overwrite);
			}
		}
	}
}