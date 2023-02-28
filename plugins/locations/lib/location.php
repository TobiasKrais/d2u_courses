<?php
/**
 * Redaxo D2U Courses Addon.
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace D2U_Courses;

use d2u_addon_backend_helper;
use d2u_courses_frontend_helper;
use rex;
use rex_addon;
use rex_config;
use rex_plugin;
use rex_sql;
use rex_yrewrite;

use function is_array;

/**
 * Course location.
 */
class Location
{
    /** @var int ID */
    public $location_id = 0;

    /** @var string Name */
    public $name = '';

    /** @var string Longitude */
    public $longitude = '';

    /** @var string Latitude */
    public $latitude = '';

    /** @var string Street */
    public $street = '';

    /** @var string ZIP code */
    public $zip_code = '';

    /** @var string City */
    public $city = '';

    /** @var string ISO country code */
    public $country_code = '';

    /** @var string Picture */
    public $picture = '';

    /** @var string Site plan */
    public $site_plan = '';

    /** @var LocationCategory Location category */
    public $location_category = false;

    /**
     * @var array<string> Redaxo usernames that are allowed to create courses for this
     * location
     */
    public $redaxo_users = [];

    /** @var string Update timestamp */
    public $updatedate = '';

    /** @var int Update timestamp */
    public $kufer_location_id = 0;

    /** @var string URL */
    private $url = '';

    /**
     * Constructor.
     * @param int $location_id ID
     */
    public function __construct($location_id)
    {
        $query = 'SELECT * FROM '. rex::getTablePrefix() .'d2u_courses_locations '
                .'WHERE location_id = '. $location_id;
        $result = rex_sql::factory();
        $result->setQuery($query);

        if ($result->getRows() > 0) {
            $this->location_id = $result->getValue('location_id');
            $this->name = $result->getValue('name');
            $this->latitude = $result->getValue('latitude');
            $this->longitude = $result->getValue('longitude');
            $this->city = $result->getValue('city');
            $this->zip_code = $result->getValue('zip_code');
            $this->country_code = $result->getValue('country_code');
            $this->street = $result->getValue('street');
            if ($result->getValue('location_category_id') > 0) {
                $this->location_category = new LocationCategory($result->getValue('location_category_id'));
            }
            $redaxo_users = preg_grep('/^\s*$/s', explode('|', $result->getValue('redaxo_users')), PREG_GREP_INVERT);
            $this->redaxo_users = is_array($redaxo_users) ? $redaxo_users : [];
            $this->picture = $result->getValue('picture');
            $this->site_plan = $result->getValue('site_plan');
            if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
                $this->kufer_location_id = $result->getValue('kufer_location_id');
            }
            $this->updatedate = $result->getValue('updatedate');
        }
    }

    /**
     * Delete object in database.
     * @return bool true if successful, otherwise false
     */
    public function delete()
    {
        if ($this->location_id > 0) {
            $query = 'DELETE FROM '. rex::getTablePrefix() .'d2u_courses_locations '
                    .'WHERE location_id = '. $this->location_id;
            $result = rex_sql::factory();
            $result->setQuery($query);

            // Don't forget to regenerate URL cache
            d2u_addon_backend_helper::generateUrlCache('location_id');

            return $result->hasError() ? false : true;
        }

        return false;

    }

    /**
     * Is location online? It is, if there are online courses hosted by this
     * location.
     * @return bool true if online, otherwise false
     */
    public function isOnline()
    {
        $query = 'SELECT * FROM '. rex::getTablePrefix() .'d2u_courses_courses '
                .'WHERE location_id = '. $this->location_id .' '
                    ."AND online_status = 'online' "
                    .'AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .') ';
        $result = rex_sql::factory();
        $result->setQuery($query);
        return $result->getRows() > 0 ? true : false;
    }

    /**
     * Get all course locations.
     * @param bool $online_only Get only online locations. A location is online
     * when online courses are hosted by this locations.
     * @return Location[] array containing locations
     */
    public static function getAll($online_only = false)
    {
        $query = 'SELECT location_id FROM '. rex::getTablePrefix() .'d2u_courses_locations ';
        if ($online_only) {
            $query = 'SELECT courses.location_id FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses '
                .'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations '
                    .'ON courses.location_id = locations.location_id '
                ."WHERE online_status = 'online' "
                    .'AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .') '
                .'GROUP BY location_id';
        }
        $result = rex_sql::factory();
        $result->setQuery($query);
        $num_rows = $result->getRows();

        $locations = [];
        for ($i = 0; $i < $num_rows; ++$i) {
            $locations[] = new self($result->getValue('location_id'));
            $result->next();
        }
        return $locations;
    }

    /**
     * Get location by Kufer Ort ID.
     * @param int $kufer_location_id Kufer Ort ID
     * @return Location|bool location or false if no location with this Kufer Ort ID was found
     */
    public static function getByKuferLocationId($kufer_location_id)
    {
        $query = 'SELECT location_id FROM '. rex::getTablePrefix() .'d2u_courses_locations WHERE kufer_location_id = '. $kufer_location_id;
        $result = rex_sql::factory();
        $result->setQuery($query);
        if ($result->getRows() > 0) {
            return new self($result->getValue('location_id'));
        }
        return false;
    }

    /**
     * Get courses hosted by this location orderd by start date.
     * @param bool $online_only true, if only online courses are returned
     * @return Courses[] Array with course objects
     */
    public function getCourses($online_only = true)
    {
        $query = 'SELECT course_id FROM '. rex::getTablePrefix() .'d2u_courses_courses '
                .'WHERE location_id = '. $this->location_id .' ';
        if ($online_only) {
            $query .= "AND online_status = 'online' "
                .'AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .')';
        }
        $query .= 'ORDER BY date_start, name';
        $result = rex_sql::factory();
        $result->setQuery($query);
        $num_rows = $result->getRows();

        $courses = [];
        for ($i = 0; $i < $num_rows; ++$i) {
            $courses[] = new Course($result->getValue('course_id'));
            $result->next();
        }
        return $courses;
    }

    /**
     * Returns the URL of this object.
     * @param bool $including_domain true if Domain name should be included
     * @return string URL
     */
    public function getUrl($including_domain = false)
    {
        if ('' == $this->url) {
            $parameterArray = [];
            $parameterArray['location_id'] = $this->location_id;
            $this->url = rex_getUrl(rex_config::get('d2u_courses', 'article_id_locations'), '', $parameterArray, '&');
        }

        if ($including_domain) {
            if (rex_addon::get('yrewrite') && rex_addon::get('yrewrite')->isAvailable()) {
                return str_replace(rex_yrewrite::getCurrentDomain()->getUrl() .'/', rex_yrewrite::getCurrentDomain()->getUrl(), rex_yrewrite::getCurrentDomain()->getUrl() . $this->url);
            }

            return str_replace(rex::getServer(). '/', rex::getServer(), rex::getServer() . $this->url);

        }

        return $this->url;

    }

    /**
     * Save object.
     * @return bool true if successful
     */
    public function save()
    {
        $pre_save_object = new self($this->location_id);

        $query = 'INSERT INTO ';
        if ($this->location_id > 0) {
            $query = 'UPDATE ';
        }
        $query .= rex::getTablePrefix().'d2u_courses_locations SET '
            .'`name` = "'. addslashes($this->name) .'", '
            .'latitude = "'. $this->latitude .'", '
            .'longitude = "'. $this->longitude .'", '
            .'city = "'. $this->city .'", '
            .'zip_code = "'. $this->zip_code .'", '
            .'country_code = "'. $this->country_code .'", '
            .'street = "'. $this->street .'", '
            .'location_category_id = '. (false !== $this->location_category ? $this->location_category->location_category_id : 0) .', '
            .'redaxo_users = "'. implode('|', $this->redaxo_users) .'", '
            .'picture = "'. $this->picture .'", '
            .'site_plan = "'. $this->site_plan .'", '
            .'updatedate = CURRENT_TIMESTAMP ';
        if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
            $query .= ', kufer_location_id = '. ($this->kufer_location_id > 0 ? $this->kufer_location_id : 0);
        }
        if ($this->location_id > 0) {
            $query .= ' WHERE location_id = '. $this->location_id;
        }

        $result = rex_sql::factory();
        $result->setQuery($query);

        if (0 === $this->location_id) {
            $this->location_id = (int) $result->getLastId();
        }

        if (!$result->hasError() && $pre_save_object->name != $this->name) {
            d2u_addon_backend_helper::generateUrlCache('location_id');
        }

        return !$result->hasError();
    }
}
