<?php
/**
 * Redaxo D2U Courses Addon
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace D2U_Courses;

/**
 * Course category class.
 */
class Cart {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Session is needed
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		
		// Create cart
		if(!isset($_SESSION['cart'])) {
			$_SESSION['cart'] = [];
		}
	}
	
	/**
	 * Adds course.
	 * @param int $course_id Course ID
	 */
	public function addCourse($course_id) {
		$course = new Course($course_id);
		if($course->registration_possible == "yes_number") {
			// registration with participant number only
			$_SESSION['cart'][$course_id] = 1;
		}
		else {
			// registration with person details
			$_SESSION['cart'][$course_id] = [];
			$_SESSION['cart'][$course_id][] = ["firstname" => "", "lastname" => "", "birthday" => "", "age" => "", "gender" => ""];
		}
	}

	/**
	 * Add empty participant to course.
	 * @param int $course_id Course ID
	 */
	public function addEmptyParticipant($course_id) {
		$_SESSION['cart'][$course_id][] = ["firstname" => "", "lastname" => "", "birthday" => "","age" => "",  "gender" => ""];
	}

	/**
	 * Calculates age from date (format DD.MM.YYYY)
	 * @param string $date Date (format DD.MM.YYYY)
	 * @return int Age in years
	 */
	public static function calculateAge($date) {
		$time = 0;
		if(strpos($date, "-")) {
			$d = explode("-", $date);
			$time = mktime(0, 0, 0, (int) $d[1], (int) $d[2], (int) $d[0]);
		}
		else if(strpos($date, ".")){
			$d = explode(".", $date);
			$time = mktime(0, 0, 0, (int) $d[1], (int) $d[0], (int) $d[2]);
		}
		else {
			$d = explode("/", $date);
			$time = mktime(0, 0, 0, (int) $d[1], (int) $d[0], (int) $d[2]);
		}
		return floor((date("Ymd") - date("Ymd", $time)) / 10000);
	}

	/**
	 * Create XML registration for KuferSQL
	 * @param mixed[] $cart Format:
	 * Array(
	 *		$course_id] => [
	 *			[0] => [
	 *				[firstname] =>
	 *				[lastname] =>
	 *				[birthday] =>
	 *				[age] =>
	 *				[gender] =>
	 *			]
	 *		]
	 *	)
	 * @param string[] $invoice_address Invoice address information
	 * @param string $registration_type Type of registration: "selbst", "kind" or "andere"
	 * @return boolean TRUE If XML file is written successfully otherwise FALSE.
	 */
	public function createXMLRegistration($cart, $invoice_address, $registration_type) {
		// <?xml version="1.0" encoding="UTF-8">
		$xml = new \DOMDocument("1.0", "UTF-8");
		$xml->formatOutput = true;

		// <ANMELDUNGEN>
		$registrations = $xml->createElement("ANMELDUNGEN");
		$xml->appendChild($registrations);

		// <ANMELDUNG>
		$registration = $xml->createElement("ANMELDUNG");
		$registrations->appendChild($registration);

		// <STAMMDATEN>
		$stammdaten = $xml->createElement("STAMMDATEN");
		$registration->appendChild($stammdaten);
		if($registration_type == "selbst" || $registration_type == "kind") {
			// <NAME_TITEL>M = Herr, W = Frau, F = Herr</NAME_TITEL>
			$name_titel = $xml->createElement("NAME_TITEL");
			if($invoice_address['gender'] == "W") {
				$name_titel->appendChild($xml->createTextNode("Frau"));
			}
			else {
				$name_titel->appendChild($xml->createTextNode("Herr"));
			}
			$stammdaten->appendChild($name_titel);
			// <NAME>Last name</NAME>
			$name = $xml->createElement("NAME");
			$name->appendChild($xml->createTextNode($invoice_address['lastname']));
			$stammdaten->appendChild($name);
			// <VORNAME>First name</VORNAME>
			$firstname = $xml->createElement("VORNAME");
			$firstname->appendChild($xml->createTextNode($invoice_address['firstname']));
			$stammdaten->appendChild($firstname);
			// <STRASSE>Street and number</STRASSE>
			$strasse = $xml->createElement("STRASSE");
			$strasse->appendChild($xml->createTextNode($invoice_address['address']));
			$stammdaten->appendChild($strasse);
			// <ORT>City</ORT>
			$city = $xml->createElement("ORT");
			$city->appendChild($xml->createTextNode(str_pad($invoice_address['zipcode'], 5, 0, STR_PAD_LEFT) .' '. $invoice_address['city']));
			$stammdaten->appendChild($city);
			if(isset($invoice_address['gender']) && $invoice_address['gender'] != "") {
				// <GESCHLECHT>M = male, W = female, F = company</GESCHLECHT>
				$gender = $xml->createElement("GESCHLECHT");
				$gender->appendChild($xml->createTextNode($invoice_address['gender']));
				$stammdaten->appendChild($gender);
			}
			if($registration_type == "selbst") {
				foreach($cart as $course_id => $participant) {
					if(is_array($participant)) {
						foreach($participant as $id => $participant_data) {
							if(isset($participant_data['birthday']) && $participant_data['birthday'] != "") {
								// <GEBDATUM>TT.MM.JJJJ</GEBDATUM>
								$gebdatum = $xml->createElement("GEBDATUM");
								$gebdatum->appendChild($xml->createTextNode(self::formatCourseDate($participant_data['birthday'])));
								$stammdaten->appendChild($gebdatum);
								// <ZUSATZ>Age</ZUSATZ>
								$zusatz = $xml->createElement("ZUSATZ");
								$zusatz->appendChild($xml->createTextNode(self::calculateAge($participant_data['birthday'])));
								$stammdaten->appendChild($zusatz);
							}
							else if(isset($participant_data['age']) && $participant_data['age'] != "") {
								// <ZUSATZ>Age</ZUSATZ>
								$zusatz = $xml->createElement("ZUSATZ");
								$zusatz->appendChild($xml->createTextNode($participant_data['age']));
								$stammdaten->appendChild($zusatz);
							}
							break 2;
						}
					}
				}
			}
			else { // $registration_type == "kind"
				if(isset($invoice_address['birthday']) && $invoice_address['birthday'] != "") {
					// <GEBDATUM>TT.MM.JJJJ</GEBDATUM>
					$gebdatum = $xml->createElement("GEBDATUM");
					$gebdatum->appendChild($xml->createTextNode(self::formatCourseDate($invoice_address['birthday'])));
					$stammdaten->appendChild($gebdatum);
					// <ZUSATZ>Age</ZUSATZ>
					$zusatz = $xml->createElement("ZUSATZ");
					$zusatz->appendChild($xml->createTextNode(self::calculateAge($invoice_address['birthday'])));
					$stammdaten->appendChild($zusatz);
				}
			}
		}
		else if($registration_type == "andere") {
			foreach($cart as $course_id => $participant) {
				if(is_array($participant)) {
					foreach($participant as $id => $participant_data) {
						// <NAME_TITEL>M = Herr, W = Frau, F = Herr</NAME_TITEL>
						$name_titel = $xml->createElement("NAME_TITEL");
						if($participant_data['gender'] == "W") {
							$name_titel->appendChild($xml->createTextNode("Frau"));
						}
						else {
							$name_titel->appendChild($xml->createTextNode("Herr"));
						}
						$stammdaten->appendChild($name_titel);
						// <NAME>Last name</NAME>
						$name = $xml->createElement("NAME");
						$name->appendChild($xml->createTextNode($participant_data['lastname']));
						$stammdaten->appendChild($name);
						// <VORNAME>First name</VORNAME>
						$firstname = $xml->createElement("VORNAME");
						$firstname->appendChild($xml->createTextNode($participant_data['firstname']));
						$stammdaten->appendChild($firstname);
						// <GESCHLECHT>M = male, W = female, F = company</GESCHLECHT>
						$gender = $xml->createElement("GESCHLECHT");
						$gender->appendChild($xml->createTextNode($participant_data['gender']));
						$stammdaten->appendChild($gender);
						if(isset($participant_data['birthday']) && $participant_data['birthday'] != "") {
							// <GEBDATUM>DD.MM.YYYY</GEBDATUM>
							$gebdatum = $xml->createElement("GEBDATUM");
							$gebdatum->appendChild($xml->createTextNode(self::formatCourseDate($participant_data['birthday'])));
							$stammdaten->appendChild($gebdatum);
							// <ZUSATZ>Age</ZUSATZ>
							$zusatz = $xml->createElement("ZUSATZ");
							$zusatz->appendChild($xml->createTextNode(self::calculateAge($participant_data['birthday'])));
							$stammdaten->appendChild($zusatz);
						}
						elseif(isset($participant_data['age'])) {
							// <ZUSATZ>Age</ZUSATZ>
							$zusatz = $xml->createElement("ZUSATZ");
							$zusatz->appendChild($xml->createTextNode($participant_data['age']));
							$stammdaten->appendChild($zusatz);
						}
						break 2;
					}
				}
			}
			// <RECHADR1>Invoice receipient full name</RECHADR1>
			$strasse = $xml->createElement("RECHADR1");
			$strasse->appendChild($xml->createTextNode($invoice_address['firstname'] ." ". $invoice_address['lastname']));
			$stammdaten->appendChild($strasse);
			// <RECHADR2>Invoice receipient street and house number</RECHADR2>
			$strasse2 = $xml->createElement("RECHADR2");
			$strasse2->appendChild($xml->createTextNode($invoice_address['address']));
			$stammdaten->appendChild($strasse2);
			// <RECHADR3>Invoice receipient zip code an city</RECHADR3>
			$city = $xml->createElement("RECHADR3");
			$city->appendChild($xml->createTextNode(str_pad($invoice_address['zipcode'], 5, 0, STR_PAD_LEFT) .' '. $invoice_address['city']));
			$stammdaten->appendChild($city);
		}
		if(isset($invoice_address['iban']) && $invoice_address['iban'] != "" ) {
			// <BANKBEZ>Bank name</BANKBEZ>
			$bank = $xml->createElement("BANKBEZ");
			$bank->appendChild($xml->createTextNode($invoice_address['bank']));
			$stammdaten->appendChild($bank);
			// <KONTOINH>Account owner</KONTOINH>
			$account_owner = $xml->createElement("KONTOINH");
			$account_owner->appendChild($xml->createTextNode($invoice_address['account_owner']));
			$stammdaten->appendChild($account_owner);
			// <BIC>BIC</BIC>
			$bic = $xml->createElement("BIC");
			$bic->appendChild($xml->createTextNode($invoice_address['bic']));
			$stammdaten->appendChild($bic);
			// <IBAN>IBAN</IBAN>
			$iban = $xml->createElement("IBAN");
			$iban->appendChild($xml->createTextNode($invoice_address['iban']));
			$stammdaten->appendChild($iban);
		} 
		
		// <BEMERKUNG>Minor kids are allowed to return home by their own.</BEMERKUNG>
		if(isset($invoice_address['kids_go_home_alone']) && $invoice_address['kids_go_home_alone'] == 'yes') {
			$bemerkung = $xml->createElement("BEMERKUNG");
			$bemerkung->appendChild($xml->createTextNode(\Sprog\Wildcard::get('d2u_courses_kids_go_home_alone')));
			$stammdaten->appendChild($bemerkung);
		}

		// <SESSIONTIME>Unix Timestamp</SESSIONTIME>
		$session_time = $xml->createElement("SESSIONTIME");
		$session_time->appendChild($xml->createTextNode(time()));
		$stammdaten->appendChild($session_time);

		// <KOMMUNIKATION>
		$kommunikation = $xml->createElement("KOMMUNIKATION");
		$stammdaten->appendChild($kommunikation);

		// <KOMMUNIKATIONSEINTRAG>
		$kommunikationseintrag_phone = $xml->createElement("KOMMUNIKATIONSEINTRAG");
		$kommunikation->appendChild($kommunikationseintrag_phone);
		// <KOMMART>T</KOMMART>
		$kommart_phone = $xml->createElement("KOMMART");
		$kommart_phone->appendChild($xml->createTextNode("T"));
		$kommunikationseintrag_phone->appendChild($kommart_phone);
		// <KOMMBEZ>Telefon</KOMMBEZ>
		$kommbez_phone = $xml->createElement("KOMMBEZ");
		$kommbez_phone->appendChild($xml->createTextNode("Telefon"));
		$kommunikationseintrag_phone->appendChild($kommbez_phone);
		// <KOMMWERT>Phone number</KOMMWERT>
		$kommwert_phone = $xml->createElement("KOMMWERT");
		$kommwert_phone->appendChild($xml->createTextNode($invoice_address['phone']));
		$kommunikationseintrag_phone->appendChild($kommwert_phone);

		// <KOMMUNIKATIONSEINTRAG>
		$kommunikationseintrag_email = $xml->createElement("KOMMUNIKATIONSEINTRAG");
		$kommunikation->appendChild($kommunikationseintrag_email);
		// <KOMMART>T</KOMMART>
		$kommart_email = $xml->createElement("KOMMART");
		$kommart_email->appendChild($xml->createTextNode("E"));
		$kommunikationseintrag_email->appendChild($kommart_email);
		// <KOMMBEZ>eMail</KOMMBEZ>
		$kommbez_email = $xml->createElement("KOMMBEZ");
		$kommbez_email->appendChild($xml->createTextNode("eMail"));
		$kommunikationseintrag_email->appendChild($kommbez_email);
		// <KOMMWERT>E-Mail address</KOMMWERT>
		$kommwert_email = $xml->createElement("KOMMWERT");
		$kommwert_email->appendChild($xml->createTextNode($invoice_address['e-mail']));
		$kommunikationseintrag_email->appendChild($kommwert_email);

		// <KURS>
		$kurse_xml = $xml->createElement("KURSE");
		$registration->appendChild($kurse_xml);

		foreach($cart as $course_id => $participant) {
			if(is_array($participant)) {
				$course = new Course($course_id);

				// <KURS>
				$kurs_xml = $xml->createElement("KURS");
				$kurse_xml->appendChild($kurs_xml);

				// <KURSNUMMER>Course number</KURSNUMMER>
				$kursnummer = $xml->createElement("KURSNUMMER");
				$kursnummer->appendChild($xml->createTextNode($course->course_number));
				$kurs_xml->appendChild($kursnummer);

				// <STATUS>A</STATUS>
				$status = $xml->createElement("STATUS");
				$status->appendChild($xml->createTextNode("A"));
				$kurs_xml->appendChild($status);

				// <ZAHLART>A</ZAHLART>
				if(isset($invoice_address['payment'])) {
					$zahlart = $xml->createElement("ZAHLART");
					$zahlart->appendChild($xml->createTextNode($invoice_address['payment']));
					$kurs_xml->appendChild($zahlart);
				}

				// <KURSGEBUEHR>35,00</KURSGEBUEHR>
				$kursgebuehr = $xml->createElement("KURSGEBUEHR");
				if($registration_type == "kind" && $course->price_discount > 0) {
					$kursgebuehr->appendChild($xml->createTextNode($course->price_discount * count($participant)));
				}
				else {
					$kursgebuehr->appendChild($xml->createTextNode($course->price * count($participant)));
				}
				$kurs_xml->appendChild($kursgebuehr);

				// <ABW_TNR>-1</ABW_TNR>
				$abw_tnr = $xml->createElement("ABW_TNR");
				$abw_tnr->appendChild($xml->createTextNode("-1"));
				$kurs_xml->appendChild($abw_tnr);

				// <ANZAHL>1</ANZAHL>
				$anzahl = $xml->createElement("ANZAHL");
				$anzahl->appendChild($xml->createTextNode(count($participant)));
				$kurs_xml->appendChild($anzahl);

				if($registration_type != "andere") {
					// <WEITEREANM>
					$weitereanm = $xml->createElement("WEITEREANM");

					$counter = 0;
					foreach($participant as $id => $participant_data) {
						if($participant_data['firstname'] == $invoice_address['firstname'] && $participant_data['lastname'] == $invoice_address['lastname']) {
						   continue;
						}
						// <WEITERANM>
						$weiteranm = $xml->createElement("WEITERANM");
						$weitereanm->appendChild($weiteranm);
						// <TYP>K = child registration, M = registrate multiple participants</TYP>
						$typ = $xml->createElement("TYP");
						if((isset($participant_data['birthday']) && date("Y") - $participant_data['birthday'] < 18) || (isset($participant_data['age']) && $participant_data['age'] < 18)) {
							$typ->appendChild($xml->createTextNode("K"));
						}
						else {
							$typ->appendChild($xml->createTextNode("M"));				
						}
						$weiteranm->appendChild($typ);

						// <KURSGEBUEHR>35,00</KURSGEBUEHR>
						$kursgebuehr = $xml->createElement("KURSGEBUEHR");
						if($registration_type == "kind" && $course->price_discount > 0) {
							$kursgebuehr->appendChild($xml->createTextNode($course->price_discount * count($participant)));
						}
						else {
							$kursgebuehr->appendChild($xml->createTextNode($course->price * count($participant)));
						}
						$weiteranm->appendChild($kursgebuehr);

						// <WEITERSTAMM>
						$weiterstamm = $xml->createElement("WEITERSTAMM");
						$weiteranm->appendChild($weiterstamm);
						// <NAME_TITEL>M = Herr, W = Frau, F = Herr</NAME_TITEL>
						$name_titel = $xml->createElement("NAME_TITEL");
						if($participant_data['gender'] == "W") {
							$name_titel->appendChild($xml->createTextNode("Frau"));
						}
						else {
							$name_titel->appendChild($xml->createTextNode("Herr"));
						}
						$weiterstamm->appendChild($name_titel);
						// <NAME>Last name</NAME>
						$name = $xml->createElement("NAME");
						$name->appendChild($xml->createTextNode($participant_data['lastname']));				
						$weiterstamm->appendChild($name);
						// <VORNAME>First name</VORNAME>
						$firstname = $xml->createElement("VORNAME");
						$firstname->appendChild($xml->createTextNode($participant_data['firstname']));				
						$weiterstamm->appendChild($firstname);
						if(isset($participant_data['birthday'])) {
							// <GEBDATUM>DD.MM.YYYY</GEBDATUM>
							$gebdatum = $xml->createElement("GEBDATUM");
							$gebdatum->appendChild($xml->createTextNode(self::formatCourseDate($participant_data['birthday'])));
							$weiterstamm->appendChild($gebdatum);
							// <ZUSATZ>Age</ZUSATZ>
							$zusatz = $xml->createElement("ZUSATZ");
							$zusatz->appendChild($xml->createTextNode(self::calculateAge($invoice_address['birthday'])));
							$weiterstamm->appendChild($zusatz);
						}
						else if(isset($participant_data['age'])) {
							// <ZUSATZ>Age</ZUSATZ>
							$zusatz = $xml->createElement("ZUSATZ");
							$zusatz->appendChild($xml->createTextNode(self::calculateAge($invoice_address['age'])));
							$weiterstamm->appendChild($zusatz);
						}
						// <GESCHLECHT>M = male, W = female</GESCHLECHT>
						$gender = $xml->createElement("GESCHLECHT");
						$gender->appendChild($xml->createTextNode($participant_data['gender']));
						$weiterstamm->appendChild($gender);

						$counter++;
					}
					if($counter > 0) {
						$kurs_xml->appendChild($weitereanm);
					}
				}
			}
		}

		// XML in Datei schreiben
		try {
			$dir = trim(\rex_config::get('d2u_courses', 'kufer_sync_xml_registration_path'), "/");
			if(!file_exists($dir)) {
				mkdir($dir, "0777", TRUE);
			}
			if(!file_exists($dir ."/.htaccess")) {
				$handle = fopen($dir ."/.htaccess", 'a');
				if($handle !== FALSE) {
					fwrite($handle, "order deny,allow". PHP_EOL ."deny from all");
				}
			}
			if($xml->save($dir ."/". time() .'-'. rand() .'.xml')) {
				return TRUE;
			}
		}
		catch(Exception $e) {
			print "Error: ". $e;
			return FALSE;
		}
	}

	/**
	 * Deletes course.
	 * @param int $course_id Course ID
	 */
	public function deleteCourse($course_id) {
		unset($_SESSION['cart'][$course_id]);
		return;
	}

	/**
	 * Deletes participant from course. If last participant is deleted, complete
	 * course is deleted from cart
	 * @param int $course_id Course ID
	 * @param int $delete_participant_id Participant number
	 */
	public function deleteParticipant($course_id, $delete_participant_id) {
		unset($_SESSION['cart'][$course_id][$delete_participant_id]);

		// If last participant was deleted: delete course
		if(count((array)$_SESSION['cart'][$course_id]) == 0) {
			unset($_SESSION['cart'][$course_id]);
			return;
		}
		
		// Sort participants - if this is not done there might be participants
		// confusions in cart
		$participants = $_SESSION['cart'][$course_id];
		unset($_SESSION['cart'][$course_id]);
		$_SESSION['cart'][$course_id] = array_values($participants);
	}
	
	/**
	 * Reformat date form 2015-12-31 to 31.12.2015
	 * @param string $date Date: format ist 2015-12-31
	 * @return string Reformatted date, e.g. 31.12.2015
	 */
	public static function formatCourseDate($date) {
		if(strpos($date, "-")) {
			$d = explode("-", $date);
			$unix = mktime(0, 0, 0, $d[1], $d[2], $d[0]);

			return date("d.m.Y", $unix);
		}
		else {
			return $date;
		}
	}

	/**
	 * Get Cart
	 * @return \D2U_Courses\Cart
	 */
	public static function getCart() {
		return new Cart();
	}
		
	/**
	 * Get Cart Courses
	 * @return int[] Course IDs
	 */
	public static function getCourseIDs() {
		// Session is needed
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

		$course_ids = [];
		if(isset($_SESSION['cart'])) {
			foreach($_SESSION['cart'] as $course_id => $participants) {
				$course_ids[] = $course_id;
			}
		}
		return $course_ids;
	}

	/**
	 * Get Cart Courses
	 * @param int $course_id Course ID
	 * @return string[] Participants
	 */
	public static function getCourseParticipants($course_id) {
		foreach($_SESSION['cart'] as $cart_course_id => $participants) {
			if($cart_course_id == $course_id) {
				return $participants;
			}
		}
		return [];
	}

	/**
	 * Proves if course is already in cart
	 * @param int $course_id Course ID
	 * @return boolean TRUE if course is in cart available, otherwise FALSE
	 */
	public function hasCourse($course_id) {
		return isset($_SESSION['cart'][$course_id]);
	}

	/**
	 * Send registrations via mail
	 * @param mixed[] $cart Format:
	 * Array(
	 *		$course_id => [
	 *			[0] => [
	 *				[firstname] =>
	 *				[lastname] =>
	 *				[birthday] =>
	 *				[age] =>
	 *				[gender] =>
	 *			]
	 *		]
	 *	)
	 * @param mixed $cart
	 * @param string[] $invoice_address
	 * @return boolean TRUE if mail was sent successfully
	 */
	function sendRegistrations($cart, $invoice_address) {
		$mail_has_content = FALSE;
		
		$mail = new \rex_mailer();
		$mail->IsHTML(TRUE);
		$mail->CharSet = "utf-8";
		$mail->From = \rex_config::get('d2u_courses', 'request_form_sender_email');
		$mail->Sender = \rex_config::get('d2u_courses', 'request_form_sender_email');
		$mail->addReplyTo($invoice_address['e-mail'], $invoice_address['firstname'] ." ". $invoice_address['lastname']);
		$mail->addCC($invoice_address['e-mail'], $invoice_address['firstname'] ." ". $invoice_address['lastname']);
				
		$mail->AddAddress(\rex_config::get('d2u_courses', 'request_form_email'));
	
		$mail->Subject = "Webanmeldung zu Kurs";
		
		$body .= "<p>Sehr ". ($invoice_address['gender'] == "W" ? "geehrte Frau" : "geehrter Herr") ." ". $invoice_address['lastname'] .",</p>";
		$body .= "<p>vielen Dank für Ihre Anmeldung / Bestellung. Nachfolgend die Details:</p>";

		$price_full = 0;
		// Course information
		foreach($cart as $course_id => $participant) {
			$body .=  "<br>";
			$course = new Course($course_id);
			if($course->name != "") {
				$mail_has_content = TRUE;
			}
			else {
				continue;
			}
			$body .= '<b>Anmeldung / Bestellung: "'. $course->name .'"';
			if($course->course_number != "" && $course->name != "") {
				$body .= " (". $course->course_number .")";
			}
			$body .=  "</b><br>";
			if($course->date_start !== "") {
				$body .= "Datum: ". (new \DateTime($course->date_start))->format('d.m.Y') . ($course->date_end != "" ? " - ". (new \DateTime($course->date_end))->format('d.m.Y') : "") . ($course->time != "" ? ", ". $course->time : "") ."<br>";
			}
			if($course->price > 0) {
				$body .= "Einzelpreis: ". $course->price  ." €". ($course->price_discount > 0 ? " (mögliche Ermäßigungen nicht mit einberechnet" : "") ."<br>";
				$price_full = $price_full + (is_array($participant) ? count($participant) * $course->price : $participant * $course->price);
			}
			if(is_array($participant)) {
				foreach($participant as $id => $participant_data) {
					$body .= "Vorname: ". $participant_data['firstname']  ."<br>";
					$body .= "Nachname: ". $participant_data['lastname']  ."<br>";
					if(isset($participant_data['birthday']) && $participant_data['birthday'] != "") {
						$body .= "Geburtsdatum: ". self::formatCourseDate($participant_data['birthday'])  ."<br>";
					}
					else if(isset($participant_data['age']) && $participant_data['age'] != "") {
						$body .= "Alter bei Veranstaltungsbeginn: ". $participant_data['age']  ."<br>";
					}
					if(isset($participant_data['gender']) && $participant_data['gender'] != "") {
						$body .= "Geschlecht: ". $participant_data['gender']  ."<br>";
					}
					$body .= "<br>";
				}
			}
			else {
					$body .= "Anzahl Anmeldungen: ". $participant  ."<br>";
			}
		}
		if(isset($invoice_address['kids_go_home_alone']) && $invoice_address['kids_go_home_alone'] == 'yes') {
			$body .= "<br><br>". \Sprog\Wildcard::get('d2u_courses_kids_go_home_alone');
		}

		// invoice data
		$body .= "<br><b>Rechnungsadresse:</b><br>";
		$body .= $invoice_address['firstname'] ." ". $invoice_address['lastname'] ."<br>";
		$body .= $invoice_address['address']  ."<br>";
		$body .= str_pad($invoice_address['zipcode'], 5, 0, STR_PAD_LEFT) .' '. $invoice_address['city']  ."<br>";
		$body .= $invoice_address['phone'] ."<br>";
		$body .= '<a href="'. $invoice_address['e-mail'] .'">'. $invoice_address['e-mail']  ."</a><br>";
		$body .= "Gewünschte Zahlungsart: ";
		if(isset($invoice_address['payment']) && $invoice_address['payment'] == "L") {
			$body .= "Lastschrift<br>";
			$body .= "Name der Bank: ". $invoice_address['bank']  ."<br>";
			$body .= "Kontoinhaber: ". $invoice_address['account_owner']  ."<br>";
			$body .= "BIC: ". $invoice_address['bic']  ."<br>";
			$body .= "IBAN: ". $invoice_address['iban'];		
		}
		else if(isset($invoice_address['payment']) && $invoice_address['payment'] == "Ü"){
			$body .= "Überweisung";
		}
		else if(isset($invoice_address['payment']) && $invoice_address['payment'] == "B") {
			$body .= "Barzahlung";
		}
		$body .=  "<br>";
		if(isset($invoice_address['vacation_pass']) && (int) $invoice_address['vacation_pass'] > 0) {
			$body .= "<br>Ferienpass: ". ((int) $invoice_address['vacation_pass']) ."<br>";
		}
		if($price_full > 0) {
			$body .= "Gesamtpreis: ". $price_full  ." €<br>";
		}
		
		// custom text
		if(\rex_config::get('d2u_courses', 'email_text', '') !== '') {
			$body .= \rex_config::get('d2u_courses', 'email_text');
		}
		
		$mail->Body = $body;
		if($mail_has_content) {
			return $mail->send();
		}
		else {
			return $mail_has_content;
		}
	}

	/**
	 * Delete all cart items.
	 */
	public function unsetCart() {
		unset($_SESSION['cart']);		
	}
	
	/**
	 * Update course participant.
	 * @param int $course_id Course ID
	 * @param int $participant_id Participant ID
	 * @param string[] $participant_data Array with participant data. Allowed keys
	 * are "firstname", "lastname", "birthday", "age", "gender"
	 */
	public function updateParticipant($course_id, $participant_id, $participant_data) {
		foreach($participant_data as $key => $value) {
			$_SESSION['cart'][$course_id][$participant_id][$key] = trim($value);
		}
	}
	
	/**
	 * Update course participant number .
	 * @param int $course_id Course ID
	 * @param int $participant_number Participant number
	 */
	public function updateParticipantNumber($course_id, $participant_number) {
		$_SESSION['cart'][$course_id] = $participant_number;
	}
}