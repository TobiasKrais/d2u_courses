<?php
/**
 * @api
 * Redaxo D2U Courses Addon.
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace TobiasKrais\D2UCourses;

use DateTime;
use DOMDocument;
use Exception;
use rex_config;

use rex_mailer;
use rex_plugin;
use rex_request;

use function array_key_exists;
use function count;
use function is_array;

/**
 * @api
 * Course category class.
 */
class Cart
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        // Session is needed
        if (PHP_SESSION_NONE === session_status()) {
            session_start();
        }

        // Create cart
        if ('' === rex_session('cart')) {
            rex_request::setSession('cart', []);
        }
    }

    /**
     * Adds course. In case the site requesting this function has a field named
     * price_salery_level_row_number, the corresponding field is automatically set.
     * @param int $course_id Course ID
     */
    public function addCourse($course_id): void
    {
        $course = new Course($course_id);
        $cart = rex_request::session('cart');
        if (!is_array($cart)) {
            $cart = [];
        }
        $cart[$course_id] = [];
        if ('yes_number' === $course->registration_possible) {
            // registration with participant number only
            $this->updateParticipantNumber($course_id, 1, null, rex_request('participant_price_salery_level_row_add', 'int', 0));
        } else {
            // registration with person details
            $cart[$course_id][] = [
                'firstname' => '',
                'lastname' => '',
                'birthday' => '',
                'age' => '',
                'emergency_number' => '',
                'gender' => '',
                'pension_insurance_id' => '',
                'nationality' => '',
                'nativeLanguage' => '',
                'price' => '',
                'price_salery_level_row_number' => rex_request('participant_price_salery_level_row_add', 'int', 0)
            ];
        }
        rex_request::setSession('cart', $cart);
    }

    /**
     * Add empty participant to course.
     * @param int $course_id Course ID
     */
    public function addEmptyParticipant($course_id): void
    {
        $cart = rex_request::session('cart');
        if (!is_array($cart)) {
            $cart = [];
        }
        if (!array_key_exists($course_id, $cart)) {
            $cart[$course_id] = [];
        }
        if (is_array($cart[$course_id])) {
            $cart[$course_id][] = [
                'firstname' => '',
                'lastname' => '',
                'birthday' => '',
                'age' => '',
                'emergency_number' => '',
                'gender' => '',
                'pension_insurance_id' => '',
                'nationality' => '',
                'nativeLanguage' => '',
                'price' => '',
                'price_salery_level_row_number' => rex_request('participant_price_salery_level_row_add', 'int', 0)
            ];
        }
        rex_request::setSession('cart', $cart);
    }

    /**
     * Calculates age from date (format DD.MM.YYYY).
     * @param string $date Date (format DD.MM.YYYY)
     * @return int Age in years
     */
    public static function calculateAge($date)
    {
        $time = 0;
        if (str_contains($date, '-')) {
            $d = explode('-', $date);
            $time = mktime(0, 0, 0, (int) $d[1], (int) $d[2], (int) $d[0]);
        } elseif (str_contains($date, '.')) {
            $d = explode('.', $date);
            $time = mktime(0, 0, 0, (int) $d[1], (int) $d[0], (int) $d[2]);
        } elseif (str_contains($date, '/')) {
            $d = explode('/', $date);
            $time = mktime(0, 0, 0, (int) $d[1], (int) $d[0], (int) $d[2]);
        } else {
            return 0;
        }
        return (int) floor((date('Ymd') - date('Ymd', false === $time ? 0 : $time)) / 10000);
    }

    /**
     * Create XML registration for KuferSQL.
     * @param mixed[] $cart Format:
     * Array(
     *		$course_id] => [
     *			[0] => [
     *				[firstname] =>
     *				[lastname] =>
     *				[birthday] =>
     *				[age] =>
     *				[emergency_number] =>
     *				[gender] =>
     *				[pension_insurance_id] =>
     *              [nationality] =>
     *              [nativeLanguage] =>
     *				[price] =>
     *				[price_salery_level_row_number] =>
     *			]
     *		]
     *	)
     * @param string[] $invoice_address Invoice address information
     * @param string $registration_type Type of registration: "selbst", "kind" or "andere"
     * @return bool true If XML file is written successfully otherwise false
     */
    public function createXMLRegistration($cart, $invoice_address, $registration_type)
    {
        // <?xml version="1.0" encoding="UTF-8">
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // <ANMELDUNGEN>
        $registrations = $xml->createElement('ANMELDUNGEN');
        $xml->appendChild($registrations);

        // <ANMELDUNG>
        $registration = $xml->createElement('ANMELDUNG');
        $registrations->appendChild($registration);

        // <STAMMDATEN>
        $stammdaten = $xml->createElement('STAMMDATEN');
        $registration->appendChild($stammdaten);
        if ('selbst' === $registration_type || 'kind' === $registration_type) {
            // <NAME>Last name</NAME>
            $name = $xml->createElement('NAME');
            $name->appendChild($xml->createTextNode($invoice_address['lastname']));
            $stammdaten->appendChild($name);
            // <VORNAME>First name</VORNAME>
            $firstname = $xml->createElement('VORNAME');
            $firstname->appendChild($xml->createTextNode($invoice_address['firstname']));
            $stammdaten->appendChild($firstname);
            // <STRASSE>Street and number</STRASSE>
            $strasse = $xml->createElement('STRASSE');
            $strasse->appendChild($xml->createTextNode($invoice_address['address']));
            $stammdaten->appendChild($strasse);
            // <ORT>City</ORT>
            $city = $xml->createElement('ORT');
            $city->appendChild($xml->createTextNode($invoice_address['zipcode'] .' '. $invoice_address['city']));
            $stammdaten->appendChild($city);
            // <NATION>Land</NATION>
            $country = $xml->createElement('NATION');
            $country->appendChild($xml->createTextNode($invoice_address['country']));
            $stammdaten->appendChild($country);
            if ('' !== trim($invoice_address['gender'])) {
                // <GESCHLECHT>M = male, W = female, F = company</GESCHLECHT>
                $gender = $xml->createElement('GESCHLECHT');
                $gender->appendChild($xml->createTextNode($invoice_address['gender']));
                $stammdaten->appendChild($gender);
            }
            if ('selbst' === $registration_type) {
                foreach ($cart as $course_id => $participant) {
                    if (is_array($participant)) {
                        foreach ($participant as $id => $participant_data) {
                            if (is_array($participant_data) && isset($participant_data['birthday']) && '' !== trim((string) $participant_data['birthday'])) {
                                // <GEBDATUM>TT.MM.JJJJ</GEBDATUM>
                                $gebdatum = $xml->createElement('GEBDATUM');
                                $gebdatum->appendChild($xml->createTextNode(self::formatCourseDate((string) $participant_data['birthday'])));
                                $stammdaten->appendChild($gebdatum);
                                // <ZUSATZ>Age</ZUSATZ>
                                $zusatz = $xml->createElement('ZUSATZ');
                                $zusatz->appendChild($xml->createTextNode((string) self::calculateAge((string) $participant_data['birthday'])));
                                $stammdaten->appendChild($zusatz);
                            } elseif (is_array($participant_data) && isset($participant_data['age']) && '' !== trim((string) $participant_data['age'])) {
                                // <ZUSATZ>Age</ZUSATZ>
                                $zusatz = $xml->createElement('ZUSATZ');
                                $zusatz->appendChild($xml->createTextNode((string) $participant_data['age']));
                                $stammdaten->appendChild($zusatz);
                            }
                            break 2;
                        }
                    }
                }
            } else { // $registration_type == "kind"
                if (isset($invoice_address['birthday']) && '' !== trim($invoice_address['birthday'])) {
                    // <GEBDATUM>TT.MM.JJJJ</GEBDATUM>
                    $gebdatum = $xml->createElement('GEBDATUM');
                    $gebdatum->appendChild($xml->createTextNode(self::formatCourseDate($invoice_address['birthday'])));
                    $stammdaten->appendChild($gebdatum);
                    // <ZUSATZ>Age</ZUSATZ>
                    $zusatz = $xml->createElement('ZUSATZ');
                    $zusatz->appendChild($xml->createTextNode((string) self::calculateAge($invoice_address['birthday'])));
                    $stammdaten->appendChild($zusatz);
                }
            }
        } elseif ('andere' === $registration_type) {
            foreach ($cart as $course_id => $participant) {
                if (is_array($participant)) {
                    foreach ($participant as $id => $participant_data) {
                        if (!is_array($participant_data) || !array_key_exists('lastname', $participant_data) || !array_key_exists('firstname', $participant_data)) {
                            continue;
                        }
                        // <NAME>Last name</NAME>
                        $name = $xml->createElement('NAME');
                        $name->appendChild($xml->createTextNode((string) $participant_data['lastname']));
                        $stammdaten->appendChild($name);
                        // <VORNAME>First name</VORNAME>
                        $firstname = $xml->createElement('VORNAME');
                        $firstname->appendChild($xml->createTextNode((string) $participant_data['firstname']));
                        $stammdaten->appendChild($firstname);
                        if (array_key_exists('gender', $participant_data) && '' !== $participant_data['gender']) {
                            // <GESCHLECHT>M = male, W = female, F = company</GESCHLECHT>
                            $gender = $xml->createElement('GESCHLECHT');
                            $gender->appendChild($xml->createTextNode((string) $participant_data['gender']));
                            $stammdaten->appendChild($gender);
                        }
                        if (array_key_exists('birthday', $participant_data) && '' !== $participant_data['birthday']) {
                            // <GEBDATUM>DD.MM.YYYY</GEBDATUM>
                            $gebdatum = $xml->createElement('GEBDATUM');
                            $gebdatum->appendChild($xml->createTextNode(self::formatCourseDate((string) $participant_data['birthday'])));
                            $stammdaten->appendChild($gebdatum);
                            // <ZUSATZ>Age</ZUSATZ>
                            $zusatz = $xml->createElement('ZUSATZ');
                            $zusatz->appendChild($xml->createTextNode((string) self::calculateAge((string) $participant_data['birthday'])));
                            $stammdaten->appendChild($zusatz);
                        } elseif (array_key_exists('age', $participant_data) && '' !== $participant_data['age']) {
                            // <ZUSATZ>Age</ZUSATZ>
                            $zusatz = $xml->createElement('ZUSATZ');
                            $zusatz->appendChild($xml->createTextNode((string) $participant_data['age']));
                            $stammdaten->appendChild($zusatz);
                        }
                        break 2;
                    }
                }
            }
        }
        $rechaddr_field_nr = 1;
        // <RECHADR1>Invoice receipient full name</RECHADR1>
        $type = $invoice_address['type'] ?? 'P';
        if ('B' === $type) {
            $company = $xml->createElement('RECHADR'. $rechaddr_field_nr);
            $company->appendChild($xml->createTextNode($invoice_address['company']));
            $stammdaten->appendChild($company);
            ++$rechaddr_field_nr;
        }

        $title = isset($invoice_address['gender']) && 'W' === $invoice_address['gender'] ? \Sprog\Wildcard::get('d2u_courses_title_female') : \Sprog\Wildcard::get('d2u_courses_title_male');
        $strasse = $xml->createElement('RECHADR'. $rechaddr_field_nr);
        $strasse->appendChild($xml->createTextNode($title .' '. trim($invoice_address['firstname']) .' '. trim($invoice_address['lastname'])));
        $stammdaten->appendChild($strasse);
        ++$rechaddr_field_nr;
        // <RECHADR2>Invoice receipient street and house number</RECHADR2>
        $strasse2 = $xml->createElement('RECHADR'. $rechaddr_field_nr);
        $strasse2->appendChild($xml->createTextNode($invoice_address['address']));
        $stammdaten->appendChild($strasse2);
        ++$rechaddr_field_nr;
        // <RECHADR3>Invoice receipient zip code an city</RECHADR3>
        $city = $xml->createElement('RECHADR'. $rechaddr_field_nr);
        $city->appendChild($xml->createTextNode($invoice_address['zipcode'] .' '. $invoice_address['city']));
        $stammdaten->appendChild($city);
        ++$rechaddr_field_nr;
        // <RECHADR4>Invoice receipient country</RECHADR4>
        $country = $xml->createElement('RECHADR'. $rechaddr_field_nr);
        $country->appendChild($xml->createTextNode($invoice_address['country']));
        $stammdaten->appendChild($country);

        if (isset($invoice_address['iban']) && '' !== $invoice_address['iban']) {
            // <BANKBEZ>Bank name</BANKBEZ>
            $bank = $xml->createElement('BANKBEZ');
            $bank->appendChild($xml->createTextNode($invoice_address['bank']));
            $stammdaten->appendChild($bank);
            // <KONTOINH>Account owner</KONTOINH>
            $account_owner = $xml->createElement('KONTOINH');
            $account_owner->appendChild($xml->createTextNode($invoice_address['account_owner']));
            $stammdaten->appendChild($account_owner);
            // <BIC>BIC</BIC>
            $bic = $xml->createElement('BIC');
            $bic->appendChild($xml->createTextNode($invoice_address['bic']));
            $stammdaten->appendChild($bic);
            // <IBAN>IBAN</IBAN>
            $iban = $xml->createElement('IBAN');
            $iban->appendChild($xml->createTextNode($invoice_address['iban']));
            $stammdaten->appendChild($iban);
        }

        // <BEMERKUNG>Minor kids are allowed to return home by their own.</BEMERKUNG>
        if (isset($invoice_address['kids_go_home_alone']) && 'yes' === $invoice_address['kids_go_home_alone']) {
            $bemerkung = $xml->createElement('BEMERKUNG');
            $bemerkung->appendChild($xml->createTextNode(\Sprog\Wildcard::get('d2u_courses_kids_go_home_alone')));
            $stammdaten->appendChild($bemerkung);
        }

        // <SESSIONTIME>Unix Timestamp</SESSIONTIME>
        $session_time = $xml->createElement('SESSIONTIME');
        $session_time->appendChild($xml->createTextNode((string) time()));
        $stammdaten->appendChild($session_time);

        // <KOMMUNIKATION>
        $kommunikation = $xml->createElement('KOMMUNIKATION');
        $stammdaten->appendChild($kommunikation);

        // <KOMMUNIKATIONSEINTRAG>
        $kommunikationseintrag_phone = $xml->createElement('KOMMUNIKATIONSEINTRAG');
        $kommunikation->appendChild($kommunikationseintrag_phone);
        // <KOMMART>T</KOMMART>
        $kommart_phone = $xml->createElement('KOMMART');
        $kommart_phone->appendChild($xml->createTextNode('T'));
        $kommunikationseintrag_phone->appendChild($kommart_phone);
        // <KOMMBEZ>Telefon</KOMMBEZ>
        $kommbez_phone = $xml->createElement('KOMMBEZ');
        $kommbez_phone->appendChild($xml->createTextNode('Telefon'));
        $kommunikationseintrag_phone->appendChild($kommbez_phone);
        // <KOMMWERT>Phone number</KOMMWERT>
        $kommwert_phone = $xml->createElement('KOMMWERT');
        $kommwert_phone->appendChild($xml->createTextNode($invoice_address['phone']));
        $kommunikationseintrag_phone->appendChild($kommwert_phone);

        // <KOMMUNIKATIONSEINTRAG>
        $kommunikationseintrag_email = $xml->createElement('KOMMUNIKATIONSEINTRAG');
        $kommunikation->appendChild($kommunikationseintrag_email);
        // <KOMMART>T</KOMMART>
        $kommart_email = $xml->createElement('KOMMART');
        $kommart_email->appendChild($xml->createTextNode('E'));
        $kommunikationseintrag_email->appendChild($kommart_email);
        // <KOMMBEZ>eMail</KOMMBEZ>
        $kommbez_email = $xml->createElement('KOMMBEZ');
        $kommbez_email->appendChild($xml->createTextNode('eMail'));
        $kommunikationseintrag_email->appendChild($kommbez_email);
        // <KOMMWERT>E-Mail address</KOMMWERT>
        $kommwert_email = $xml->createElement('KOMMWERT');
        $kommwert_email->appendChild($xml->createTextNode($invoice_address['e-mail']));
        $kommunikationseintrag_email->appendChild($kommwert_email);

        // Emergency number
        foreach ($cart as $course_id => $participant) {
            if (is_array($participant)) {
                foreach ($participant as $id => $participant_data) {
                    if (is_array($participant_data) && array_key_exists('emergency_number', $participant_data) && '' !== $participant_data['emergency_number']) {
                        // <KOMMUNIKATIONSEINTRAG>
                        $kommunikationseintrag_emergency = $xml->createElement('KOMMUNIKATIONSEINTRAG');
                        $kommunikation->appendChild($kommunikationseintrag_emergency);
                        // <KOMMART>T</KOMMART>
                        $kommart_emergency = $xml->createElement('KOMMART');
                        $kommart_emergency->appendChild($xml->createTextNode('T'));
                        $kommunikationseintrag_emergency->appendChild($kommart_emergency);
                        // <KOMMBEZ>Notfallnummer</KOMMBEZ>
                        $kommbez_emergency = $xml->createElement('KOMMBEZ');
                        $kommbez_emergency->appendChild($xml->createTextNode(\Sprog\Wildcard::get('d2u_courses_cart_emergency_number')));
                        $kommunikationseintrag_emergency->appendChild($kommbez_emergency);
                        // <KOMMWERT>Nummer</KOMMWERT>
                        $kommwert_emergency = $xml->createElement('KOMMWERT');
                        $kommwert_emergency->appendChild($xml->createTextNode((string) $participant_data['emergency_number']));
                        $kommunikationseintrag_emergency->appendChild($kommwert_emergency);
                    }
                    break 2;
                }
            }
        }

        // <KURS>
        $kurse_xml = $xml->createElement('KURSE');
        $registration->appendChild($kurse_xml);

        foreach ($cart as $course_id => $participant) {
            if (is_array($participant)) {
                $course = new Course($course_id);

                // <KURS>
                $kurs_xml = $xml->createElement('KURS');
                $kurse_xml->appendChild($kurs_xml);

                // <KURSNUMMER>Course number</KURSNUMMER>
                $kursnummer = $xml->createElement('KURSNUMMER');
                $kursnummer->appendChild($xml->createTextNode($course->course_number));
                $kurs_xml->appendChild($kursnummer);

                // <STATUS>A</STATUS>
                $status = $xml->createElement('STATUS');
                $status->appendChild($xml->createTextNode('A'));
                $kurs_xml->appendChild($status);

                // <ZAHLART>A</ZAHLART>
                if (isset($invoice_address['payment'])) {
                    $zahlart = $xml->createElement('ZAHLART');
                    $zahlart->appendChild($xml->createTextNode($invoice_address['payment']));
                    $kurs_xml->appendChild($zahlart);
                }

                // <KURSGEBUEHR>35,00</KURSGEBUEHR>
                $kursgebuehr = $xml->createElement('KURSGEBUEHR');
                if ('kind' === $registration_type && $course->price_discount > 0) {
                    $kursgebuehr->appendChild($xml->createTextNode((string) ($course->price_discount * count($participant))));
                } else {
                    $kursgebuehr->appendChild($xml->createTextNode((string) ($course->price * count($participant))));
                }
                $kurs_xml->appendChild($kursgebuehr);

                // <ABW_TNR>-1</ABW_TNR>
                $abw_tnr = $xml->createElement('ABW_TNR');
                $abw_tnr->appendChild($xml->createTextNode('-1'));
                $kurs_xml->appendChild($abw_tnr);

                // <ANZAHL>1</ANZAHL>
                $anzahl = $xml->createElement('ANZAHL');
                $anzahl->appendChild($xml->createTextNode((string) count($participant)));
                $kurs_xml->appendChild($anzahl);

                if ('andere' !== $registration_type) {
                    // <WEITEREANM>
                    $weitereanm = $xml->createElement('WEITEREANM');

                    $counter = 0;
                    foreach ($participant as $id => $participant_data) {
                        if (is_array($participant_data)) {
                            if ($participant_data['firstname'] === $invoice_address['firstname'] && $participant_data['lastname'] === $invoice_address['lastname']) {
                                continue;
                            }
                            // <WEITERANM>
                            $weiteranm = $xml->createElement('WEITERANM');
                            $weitereanm->appendChild($weiteranm);
                            // <TYP>K = child registration, M = registrate multiple participants</TYP>
                            $typ = $xml->createElement('TYP');
                            if ((isset($participant_data['birthday']) && date('Y') - $participant_data['birthday'] < 18) || (isset($participant_data['age']) && $participant_data['age'] < 18)) {
                                $typ->appendChild($xml->createTextNode('K'));
                            } else {
                                $typ->appendChild($xml->createTextNode('M'));
                            }
                            $weiteranm->appendChild($typ);

                            // <KURSGEBUEHR>35,00</KURSGEBUEHR>
                            $kursgebuehr = $xml->createElement('KURSGEBUEHR');
                            if ('kind' === $registration_type && $course->price_discount > 0) {
                                $kursgebuehr->appendChild($xml->createTextNode((string) ($course->price_discount * count($participant))));
                            } else {
                                $kursgebuehr->appendChild($xml->createTextNode((string) ($course->price * count($participant))));
                            }
                            $weiteranm->appendChild($kursgebuehr);

                            // <WEITERSTAMM>
                            $weiterstamm = $xml->createElement('WEITERSTAMM');
                            $weiteranm->appendChild($weiterstamm);
                            // <NAME_TITEL>M = Herr, W = Frau, F = Herr</NAME_TITEL>
                            $name_titel = $xml->createElement('NAME_TITEL');
                            if ('W' === $participant_data['gender']) {
                                $name_titel->appendChild($xml->createTextNode('Frau'));
                            } else {
                                $name_titel->appendChild($xml->createTextNode('Herr'));
                            }
                            $weiterstamm->appendChild($name_titel);
                            // <NAME>Last name</NAME>
                            $name = $xml->createElement('NAME');
                            $name->appendChild($xml->createTextNode((string) $participant_data['lastname']));
                            $weiterstamm->appendChild($name);
                            // <VORNAME>First name</VORNAME>
                            $firstname = $xml->createElement('VORNAME');
                            $firstname->appendChild($xml->createTextNode((string) $participant_data['firstname']));
                            $weiterstamm->appendChild($firstname);
                            if (array_key_exists('birthday', $participant_data) && '' !== $participant_data['birthday']) {
                                // <GEBDATUM>DD.MM.YYYY</GEBDATUM>
                                $gebdatum = $xml->createElement('GEBDATUM');
                                $gebdatum->appendChild($xml->createTextNode(self::formatCourseDate($participant_data['birthday'])));
                                $weiterstamm->appendChild($gebdatum);
                                // <ZUSATZ>Age</ZUSATZ>
                                $zusatz = $xml->createElement('ZUSATZ');
                                $zusatz->appendChild($xml->createTextNode((string) self::calculateAge($invoice_address['birthday'])));
                                $weiterstamm->appendChild($zusatz);
                            } elseif (array_key_exists('age', $participant_data) && '' !== $participant_data['age']) {
                                // <ZUSATZ>Age</ZUSATZ>
                                $zusatz = $xml->createElement('ZUSATZ');
                                $zusatz->appendChild($xml->createTextNode((string) self::calculateAge($invoice_address['age'])));
                                $weiterstamm->appendChild($zusatz);
                            }
                            // <GESCHLECHT>M = male, W = female</GESCHLECHT>
                            $gender = $xml->createElement('GESCHLECHT');
                            $gender->appendChild($xml->createTextNode((string) $participant_data['gender']));
                            $weiterstamm->appendChild($gender);

                            if (array_key_exists('emergency_number', $participant_data) && '' !== $participant_data['emergency_number']) {
                                // <KOMMUNIKATIONSEINTRAG>
                                $kommunikationseintrag_emergency = $xml->createElement('KOMMUNIKATIONSEINTRAG');
                                $weiterstamm->appendChild($kommunikationseintrag_emergency);
                                // <KOMMART>T</KOMMART>
                                $kommart_emergency = $xml->createElement('KOMMART');
                                $kommart_emergency->appendChild($xml->createTextNode('T'));
                                $kommunikationseintrag_emergency->appendChild($kommart_emergency);
                                // <KOMMBEZ>Notfallnummer</KOMMBEZ>
                                $kommbez_emergency = $xml->createElement('KOMMBEZ');
                                $kommbez_emergency->appendChild($xml->createTextNode(\Sprog\Wildcard::get('d2u_courses_cart_emergency_number')));
                                $kommunikationseintrag_emergency->appendChild($kommbez_emergency);
                                // <KOMMWERT>Nummer</KOMMWERT>
                                $kommwert_emergency = $xml->createElement('KOMMWERT');
                                $kommwert_emergency->appendChild($xml->createTextNode($invoice_address['emergency_number']));
                                $kommunikationseintrag_emergency->appendChild($kommwert_emergency);
                            }

                            ++$counter;
                        }
                    }
                    if ($counter > 0) {
                        $kurs_xml->appendChild($weitereanm);
                    }
                }
            }
        }

        // XML in Datei schreiben
        try {
            $dir = trim((string) rex_config::get('d2u_courses', 'kufer_sync_xml_registration_path'), '/');
            if (!file_exists($dir)) {
                \rex_dir::create(\rex_path::base($dir));
            }
            if (!file_exists($dir .'/.htaccess')) {
                $handle = fopen($dir .'/.htaccess', 'a');
                if (false !== $handle) {
                    fwrite($handle, 'order deny,allow'. PHP_EOL .'deny from all');
                }
            }
            if (false !== $xml->save($dir .'/'. time() .'-'. random_int(0, getrandmax()) .'.xml')) {
                return true;
            }
        } catch (Exception $e) {
            echo 'Error: '. $e;
            return false;
        }
        return true;
    }

    /**
     * Deletes course.
     * @param int $course_id Course ID
     */
    public function deleteCourse(int $course_id): void
    {
        $cart = rex_request::session('cart');
        if (is_array($cart)) {
            unset($cart[$course_id]);
        }
        rex_request::setSession('cart', $cart);
    }

    /**
     * Deletes participant from course. If last participant is deleted, complete
     * course is deleted from cart.
     * @param int $course_id Course ID
     * @param int $delete_participant_id Participant number
     */
    public function deleteParticipant(int $course_id, int $delete_participant_id): void
    {
        $cart = rex_request::session('cart');
        if (is_array($cart) && is_array($cart[$course_id])) {
            unset($cart[$course_id][$delete_participant_id]);
        }

        if (is_array($cart) && 0 === count((array) $cart[$course_id])) {
            // If last participant was deleted: delete course
            unset($cart[$course_id]);
        } elseif (is_array($cart)) {
            // Sort participants - if this is not done there might be participants confusions in cart
            $cart[$course_id] = array_values((array) $cart[$course_id]);
        }

        rex_request::setSession('cart', $cart);
    }

    /**
     * Reformat date form 2015-12-31 to 31.12.2015.
     * @param string $date Date: format ist 2015-12-31
     * @return string Reformatted date, e.g. 31.12.2015
     */
    public static function formatCourseDate($date): string
    {
        if (str_contains($date, '-')) {
            $d = explode('-', $date);
            $unix = mktime(0, 0, 0, (int) $d[1], (int) $d[2], (int) $d[0]);

            if (false !== $unix) {
                return date('d.m.Y', $unix);
            }
        }

        return $date;
    }

    /**
     * Get Cart.
     * @return \D2U_Courses\Cart
     */
    public static function getCart(): self
    {
        return new self();
    }

    /**
     * Get Cart Courses.
     * @return int[] Course IDs
     */
    public static function getCourseIDs(): array
    {
        // Session is needed
        if (PHP_SESSION_NONE === session_status()) {
            session_start();
        }

        $course_ids = [];
        if ('' !== rex_session('cart')) {
            $cart = rex_request::session('cart');
            if (is_array($cart)) {
                foreach ($cart as $course_id => $participants) {
                    $course_ids[] = (int) $course_id;
                }
            }
        }
        return $course_ids;
    }

    /**
     * Get Cart Courses.
     * @param int $course_id Course ID
     * @return array<int,array<string,string>>|array<string,string> Participants
     */
    public static function getCourseParticipants(int $course_id): array
    {
        $cart = rex_request::session('cart');
        if (is_array($cart)) {
            foreach ($cart as $cart_course_id => $participants) {
                if ((int) $cart_course_id === $course_id) {
                    return $participants; /** @phpstan-ignore-line */
                }
            }
        }
        return [];
    }

    /**
     * Get Cart Courses participant number.
     * @param int $course_id Course ID
     * @return int number of participants
     */
    public static function getCourseParticipantsNumber(int $course_id): int
    {
        $cart = rex_request::session('cart');
        if (is_array($cart)) {
            foreach ($cart as $cart_course_id => $participants) {
                if ((int) $cart_course_id === $course_id) {
                    $course = new Course($course_id);
                    if ('yes_number' === $course->registration_possible) {
                        return (int) $participants;
                    }
                    if (is_array($participants)) {
                        return count($participants);
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Proves if course is already in cart.
     * @param int $course_id Course ID
     * @return bool true if course is in cart available, otherwise false
     */
    public function hasCourse(int $course_id): bool
    {
        $cart = rex_request::session('cart');
        if (is_array($cart) && array_key_exists($course_id, $cart)) {
            return true;
        }
        return false;
    }

    /**
     * Save bookings in case customer_bookings plugin is installed
     * @param array<int,array<int|string,string|array<string,string>>> $cart with course details
     * @param array<string,string> $invoice_address Array with invoice address details
     * @return bool true if saved successfully
     */
    public function saveBookings($cart, $invoice_address): bool
    {
        if (!rex_plugin::get('d2u_courses', 'customer_bookings')->isAvailable()) {
            return false;
        }

        $return = true;
        foreach ($cart as $course_id => $participant) {
            foreach ($participant as $id => $participant_data) {
                if(!is_array($participant_data)) {
                    // if course->registration_possible !== yes, no participant details are available
                    continue;
                }
                $booking = CustomerBooking::factory();
                $booking->givenName = $participant_data['firstname'];
                $booking->familyName = $participant_data['lastname'];
                $booking->birthDate = $participant_data['birthday'] .' 00:00:00';
                $booking->gender = $participant_data['gender'];
                $booking->street = $invoice_address['address'];
                $booking->zipCode = $invoice_address['zipcode'];
                $booking->city = $invoice_address['city'];
                $booking->country = $invoice_address['country'];
                $booking->pension_insurance_id = $participant_data['pension_insurance_id'];
                $booking->nationality = $participant_data['nationality'];
                $booking->nativeLanguage = $participant_data['nativeLanguage'];
                $booking->emergency_number = array_key_exists('emergency_number', $participant_data) && '' !== $participant_data['emergency_number'] ? $participant_data['emergency_number'] : $invoice_address['phone'];
                $booking->email = $invoice_address['e-mail'];
                $booking->kids_go_home_alone = array_key_exists('kids_go_home_alone', $invoice_address) && 'yes' === $invoice_address['kids_go_home_alone'];
                if ($course_id > 0) {
                    $course = new Course($course_id);
                    if ($course->price_salery_level) {
                        $price_level_row_counter = 0;
                        foreach ($course->price_salery_level_details as $level_description => $level_price) {
                            ++$price_level_row_counter;
                            if ($price_level_row_counter === (int) $participant_data['price_salery_level_row_number']) {
                                $booking->salery_level = $level_description;
                                break;
                            }
                        }
                    }
                }
                $booking->course_id = $course_id;
                $booking->ipAddress = rex_request::server('REMOTE_ADDR', 'string');
                if('booked' === $course->registration_possible || ($course->participants_max > 0 && CustomerBooking::getNumberForCourse($course->course_id) > $course->participants_max)) {
                    $booking->waitlist = true;
                }
                if (false === $booking->save()) {
                    $return = false;
                }
            }
        }
        return $return;
    }
    
    /**
     * Send registrations via mail.
     * @param array<int,array<int|string,string|array<string,string>>> $cart with course details
     * @param array<string,string> $invoice_address Array with invoice address details
     * @return bool true if mail was sent successfully
     */
    public function sendRegistrations($cart, $invoice_address): bool
    {
        $mail_has_content = false;

        $mail = new rex_mailer();
        $mail->isHTML(true);
        $mail->CharSet = 'utf-8';
        $mail->From = (string) rex_config::get('d2u_courses', 'request_form_sender_email');
        $mail->Sender = (string) rex_config::get('d2u_courses', 'request_form_sender_email');
        $mail->addReplyTo($invoice_address['e-mail'], $invoice_address['firstname'] .' '. $invoice_address['lastname']);
        $mail->addCC($invoice_address['e-mail'], $invoice_address['firstname'] .' '. $invoice_address['lastname']);

        $mail->addAddress((string) rex_config::get('d2u_courses', 'request_form_email'));

        $mail->Subject = 'Anmeldung / Bestellung';

        $body = '<p>Sehr '. ('W' === $invoice_address['gender'] ? 'geehrte Frau' : 'geehrter Herr') .' '. $invoice_address['lastname'] .',</p>';
        $body .= '<p>vielen Dank für Ihre Anmeldung / Bestellung. Nachfolgend die Details:</p>';

        $price_full = 0;
        // Course information
        foreach ($cart as $course_id => $participant) {
            $body .= '<br>';
            $course = new Course($course_id);
            if ('' !== $course->name) {
                $mail_has_content = true;
            } else {
                continue;
            }
            $body .= '<b>Anmeldung / Bestellung: "'. $course->name .'"';
            if ('' !== $course->course_number) {
                $body .= ' ('. $course->course_number .')';
            }
            $body .= '</b><br>';
            if ('' !== $course->date_start) {
                $body .= 'Datum: '. (new DateTime($course->date_start))->format('d.m.Y') . ('' !== $course->date_end ? ' - '. (new DateTime($course->date_end))->format('d.m.Y') : '') . ('' !== $course->time ? ', '. $course->time : '') .'<br>';
            }
            if ($course->price_salery_level) {
                if ('yes_number' === $course->registration_possible && !is_array($participant['participant_price'])) {
                    $body .= 'Einzelpreis: '. $participant['participant_price'] .'<br>';
                    $price_full = $price_full + ((int) $participant['participant_number'] * ((float) str_replace(',', '.', str_replace('.', '', $participant['participant_price']))));
                }
            } elseif ($course->price > 0) {
                $body .= 'Einzelpreis: '. $course->price  .' €'. ($course->price_discount > 0 ? ' (mögliche Ermäßigungen nicht mit einberechnet)' : '') .'<br>';
                $price_full = $price_full + (isset($participant['participant_number']) ? (int) $participant['participant_number'] * $course->price : count($participant) * $course->price);
            }
            if ('yes_number' !== $course->registration_possible) {
                foreach ($participant as $id => $participant_data) {
                    if (is_array($participant_data)) {
                        $body .= 'Vorname: '. $participant_data['firstname'] .'<br>';
                        $body .= 'Nachname: '. $participant_data['lastname'] .'<br>';
                        if (isset($participant_data['birthday']) && '' !== $participant_data['birthday']) {
                            $body .= 'Geburtsdatum: '. self::formatCourseDate($participant_data['birthday'])  .'<br>';
                        } elseif (isset($participant_data['age']) && '' !== $participant_data['age']) {
                            $body .= 'Alter bei Veranstaltungsbeginn: '. $participant_data['age']  .'<br>';
                        }
                        if (array_key_exists('emergency_number', $participant_data) && '' !== $participant_data['emergency_number']) {
                            $body .= 'Notfallnummer: '. $participant_data['emergency_number']  .'<br>';
                        }
                        if (array_key_exists('pension_insurance_id', $participant_data) && '' !== $participant_data['pension_insurance_id']) {
                            $body .= 'Rentenversicherungsnummer: '. $participant_data['pension_insurance_id']  .'<br>';
                        }
                        if (array_key_exists('nationality', $participant_data) && '' !== $participant_data['nationality']) {
                            $body .= 'Nationalität: '. $participant_data['nationality']  .'<br>';
                        }
                        if (array_key_exists('nativeLanguage', $participant_data) && '' !== $participant_data['nativeLanguage']) {
                            $body .= 'Muttersprache: '. $participant_data['nativeLanguage']  .'<br>';
                        }
                        if (isset($participant_data['gender']) && '' !== $participant_data['gender']) {
                            $body .= 'Geschlecht: '. $participant_data['gender']  .'<br>';
                        }
                        $price_level_description = '';
                        if ($course->price_salery_level && isset($participant_data['price']) && isset($participant_data['price_salery_level_row_number']) && '' !== $participant_data['price']) {
                            $price_level_row_counter = 0;
                            foreach ($course->price_salery_level_details as $level_description => $level_price) {
                                ++$price_level_row_counter;
                                if ($price_level_row_counter === (int) $participant_data['price_salery_level_row_number']) {
                                    $price_level_description = $level_description;
                                    break;
                                }
                            }
                            $body .= 'Preis nach Preismodell: '. $participant_data['price']  .' (monatliches Familieneinkommen: '. $price_level_description .')<br>';
                            $price_full = $price_full + ((float) str_replace(',', '.', str_replace('.', '', $participant_data['price'])));
                        }

                        if('booked' === $course->registration_possible || (rex_plugin::get('d2u_courses', 'customer_bookings')->isAvailable() && $course->participants_max > 0 && CustomerBooking::getNumberForCourse($course->course_id) > $course->participants_max)) {
                            $body .= 'Mit dieser Buchung sind Sie auf der Warteliste eingetragen.<br>';
                        }
                        $body .= '<br>';
                    }
                }
            }
            elseif (!is_array($participant['participant_number'])) {
                $body .= 'Anzahl Anmeldungen: '. $participant['participant_number'] .'<br>';
            }
        }
        if (isset($invoice_address['kids_go_home_alone']) && 'yes' === $invoice_address['kids_go_home_alone']) {
            $body .= '<br>'. \Sprog\Wildcard::get('d2u_courses_kids_go_home_alone') .'<br>';
        }

        // invoice data
        $body .= '<br><b>Adresse:</b><br>';
        if (isset($invoice_address['type']) && 'B' === $invoice_address['type']) {
            $body .= $invoice_address['company'] .'<br>';
        }
        $body .= $invoice_address['firstname'] .' '. $invoice_address['lastname'] .'<br>';
        $body .= $invoice_address['address'] .'<br>';
        $body .= $invoice_address['zipcode'] .' '. $invoice_address['city'] .'<br>';
        $body .= $invoice_address['country'] .'<br>';
        $body .= $invoice_address['phone'] .'<br>';
        $body .= '<a href="'. $invoice_address['e-mail'] .'">'. $invoice_address['e-mail']  .'</a><br>';
        if (isset($invoice_address['payment']) && '' !== $invoice_address['payment']) {
            $body .= 'Gewünschte Zahlungsart: ';
            if ('L' === $invoice_address['payment']) {
                $body .= 'Lastschrift<br>';
                $body .= 'Name der Bank: '. $invoice_address['bank']  .'<br>';
                $body .= 'Kontoinhaber: '. $invoice_address['account_owner']  .'<br>';
                $body .= 'BIC: '. $invoice_address['bic']  .'<br>';
                $body .= 'IBAN: '. $invoice_address['iban'];
            } elseif ('Ü' === $invoice_address['payment']) {
                $body .= 'Überweisung';
            } elseif ('B' === $invoice_address['payment']) {
                $body .= 'Barzahlung';
            }
        }
        $body .= '<br>';
        if (isset($invoice_address['vacation_pass']) && (int) $invoice_address['vacation_pass'] > 0) {
            $body .= '<br>Ferienpass: '. ((int) $invoice_address['vacation_pass']) .'<br>';
        }
        if ($price_full > 0) {
            $body .= 'Gesamtpreis: '. number_format($price_full, 2)  .' €<br>';
        }

        // custom text
        if ('' !== rex_config::get('d2u_courses', 'email_text', '')) {
            $body .= rex_config::get('d2u_courses', 'email_text');
        }

        $mail->Body = $body;

        if ($mail_has_content) {
            return $mail->send();
        }

        return $mail_has_content;
    }

    /**
     * Delete all cart items.
     */
    public function unsetCart(): void
    {
        rex_request::setSession('cart', null);
    }

    /**
     * Update course participant.
     * @param int $course_id Course ID
     * @param int $participant_id Participant ID
     * @param array<string,string> $participant_data Array with participant data. Allowed keys
     * are "firstname", "lastname", "birthday", "age", "gender", "emergency_number", "pension_insurance_id", "nationality", "nativeLanguage", "salery_level"
     */
    public function updateParticipant($course_id, $participant_id, $participant_data): void
    {
        $cart = rex_request::session('cart');
        foreach ($participant_data as $key => $value) {
            if (!is_array($cart)) {
                $cart = [];
            }
            if (!array_key_exists($course_id, $cart) || !is_array($cart[$course_id])) {
                $cart[$course_id] = [];
            }
            if (!array_key_exists($participant_id, $cart[$course_id]) || !is_array($cart[$course_id][$participant_id])) {
                $cart[$course_id][$participant_id] = [];
            }
            $cart[$course_id][$participant_id][$key] = trim($value);
        }
        rex_request::setSession('cart', $cart);
    }

    /**
     * Update course participant number.
     * @param int $course_id Course ID
     * @param int $participant_number Participant number
     * @param string $participant_price price per participant, e.g. 30 €
     * @param int $participant_price_salery_level_row_number row number of price salery level used as so to say id
     */
    public function updateParticipantNumber($course_id, $participant_number, $participant_price = null, $participant_price_salery_level_row_number = 0): void
    {
        $cart = rex_request::session('cart');
        if (!is_array($cart)) {
            $cart = [];
        }
        $cart[$course_id] = [];
        $cart[$course_id]['participant_number'] = $participant_number;
        if ('' !== $participant_price || $participant_price_salery_level_row_number > 0) {
            if ($participant_price_salery_level_row_number > 0) {
                $course = new Course($course_id);
                if ($course->price_salery_level) {
                    $counter_row_price_salery_level_details = 0;
                    foreach ($course->price_salery_level_details as $description => $price) {
                        ++$counter_row_price_salery_level_details;
                        if ($counter_row_price_salery_level_details === $participant_price_salery_level_row_number) {
                            $participant_price = $price;
                            break;
                        }
                    }
                }
            }
            $cart[$course_id]['participant_price'] = $participant_price;
            $cart[$course_id]['participant_price_salery_level_row_number'] = $participant_price_salery_level_row_number;
        }
        rex_request::setSession('cart', $cart);
    }
}
