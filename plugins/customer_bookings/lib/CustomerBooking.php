<?php
/**
 * Redaxo D2U Courses Addon.
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace D2U_Courses;

use DateInterval;
use DateTime;
use rex;
use rex_sql;

/**
 * @api
 * Class containing customer booking data.
 */
class CustomerBooking
{
    /** @var int Database ID */
    public int $id = 0;

    /** @var string Customer first or given name */
    public string $givenName = '';

    /** @var string Customer last or family name */
    public string $familyName = '';

    /** @var string Date of birth */
    public string $birthDate = '';

    /** @var string Gender */
    public string $gender = '';

    /** @var string Customer street */
    public string $street = '';

    /** @var string Customer address zip code */
    public string $zipCode = '';

    /** @var string Customer address city */
    public string $city = '';

    /** @var string Customer address country */
    public string $country = '';

    /** @var string Emergency_number */
    public string $emergency_number = '';

    /** @var string E-mail address */
    public string $email = '';

    /** @var string Nationality */
    public string $nationality = '';

    /** @var string Native language */
    public string $nativeLanguage = '';

    /** @var string Pension insurance id */
    public string $pension_insurance_id = '';

    /** @var bool kids may go home alone */
    public bool $kids_go_home_alone = true;

    /** @var string Salery level */
    public string $salery_level = '';

    /** @var int Booked course id */
    public int $course_id = 0;

    /** @var string IP address from which customer did the booking */
    public string $ipAddress = '';

    /** @var string Date of booking */
    public string $bookingDate = '';

    /**
     * Delete object in database.
     * @return bool true if successful, otherwise false
     */
    public function delete()
    {
        if ($this->id > 0) {
            $result = rex_sql::factory();

            $query = 'DELETE FROM '. rex::getTablePrefix() .'d2u_courses_customer_bookings '
                    .'WHERE id = '. $this->id;
            $result->setQuery($query);

            return ($result->hasError() ? false : true);
        }

        return false;
    }
    
    /**
     * Create a new empty self object
     * @return self Empty self object
     */
    public static function factory() {
        return new self();
    }
    
    /**
     * Get object from database
     * @param int $id booking ID
     * @return self|null Object if found in database or null
     */
    public static function get($id)
    {
        if ($id > 0) {
            $query = 'SELECT * FROM '. rex::getTablePrefix() .'d2u_courses_customer_bookings '
                    .'WHERE id = '. $id;
            $result = rex_sql::factory();
            $result->setQuery($query);

            if ($result->getRows() > 0) {
                $customer_booking = new self();
                $customer_booking->id = (int) $result->getValue('id');
                $customer_booking->givenName = (string) $result->getValue('givenName');
                $customer_booking->familyName = (string) $result->getValue('familyName');
                $customer_booking->birthDate = (string) $result->getValue('birthDate');
                $customer_booking->gender = (string) $result->getValue('gender');
                $customer_booking->street = (string) $result->getValue('street');
                $customer_booking->zipCode = (string) $result->getValue('zipCode');
                $customer_booking->city = (string) $result->getValue('city');
                $customer_booking->country = (string) $result->getValue('country');
                $customer_booking->pension_insurance_id = (string) $result->getValue('pension_insurance_id');
                $customer_booking->kids_go_home_alone = 1 === (int) $result->getValue('kids_go_home_alone');
                $customer_booking->salery_level = (string) $result->getValue('salery_level');
                $customer_booking->emergency_number = (string) $result->getValue('emergency_number');
                $customer_booking->email = (string) $result->getValue('email');
                $customer_booking->nationality = (string) $result->getValue('nationality');
                $customer_booking->nativeLanguage = (string) $result->getValue('nativeLanguage');
                $customer_booking->course_id = (int) $result->getValue('course_id');
                $customer_booking->ipAddress = (string) $result->getValue('ipAddress');
                $customer_booking->bookingDate = (string) $result->getValue('bookingDate');
                return $customer_booking;
            }
        }
        
        return null;
    }

    /**
     * Get all customer bookings for course.
     * @param int $course_id Course ID
     * @return array<int,CustomerBooking> Array with customer booking objects
     */
    public static function getAllForCourse($course_id)
    {
        $query = 'SELECT id FROM '. rex::getTablePrefix() .'d2u_courses_customer_bookings '
            .' WHERE course_id = '. $course_id;
        $result = rex_sql::factory();
        $result->setQuery($query);

        $bookings = [];
        for ($i = 0; $i < $result->getRows(); ++$i) {
            $booking = self::get((int) $result->getValue('id'));
            if ($booking instanceof self) {
                $bookings[] = $booking;
            }
            $result->next();
        }
        return $bookings;
    }

    /**
     * Save object.
     * @return bool true if successful
     */
    public function save()
    {
        $sql = rex_sql::factory();
        $sql->setTable(rex::getTablePrefix() .'d2u_courses_customer_bookings');
        $sql->setValue('givenName', $this->givenName);
        $sql->setValue('familyName', $this->familyName);
        $sql->setValue('birthDate', $this->birthDate);
        $sql->setValue('gender', $this->gender);
        $sql->setValue('street', $this->street);
        $sql->setValue('zipCode', $this->zipCode);
        $sql->setValue('city', $this->city);
        $sql->setValue('country', $this->country);
        $sql->setValue('emergency_number', $this->emergency_number);
        if (false !== filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $sql->setValue('email', $this->email);
        }
        $sql->setValue('nationality', $this->nationality);
        $sql->setValue('nativeLanguage', $this->nativeLanguage);
        $sql->setValue('pension_insurance_id', $this->pension_insurance_id);
        $sql->setValue('kids_go_home_alone', (int) $this->kids_go_home_alone);
        $sql->setValue('salery_level', $this->salery_level);
        $sql->setValue('course_id', $this->course_id);

        if ($this->id > 0) {
            $sql->setWhere(['id' => $this->id]);
            $sql->update();
        }
        else {
            $sql->setValue('ipAddress', $this->ipAddress);
            $sql->setValue('bookingDate', rex_sql::datetime());
            $sql->insert();
            $this->id = (int) $sql->getLastId();
        }

        return ($sql->hasError() ? false : true);
    }
}