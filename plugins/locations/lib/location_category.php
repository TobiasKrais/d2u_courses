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
use rex_sql;
use rex_yrewrite;

/**
 * @api
 * Location category.
 */
class LocationCategory
{
    /** @var int ID */
    public int $location_category_id = 0;

    /** @var string Name */
    public string $name = '';

    /** @var string Picture */
    public string $picture = '';

    /** @var int Zoom level on maps */
    public int $zoom_level = 10;

    /** @var string Upadte timestamp */
    public string $updatedate = '';

    /** @var string URL */
    private string $url = '';

    /**
     * Constructor.
     * @param int $location_category_id ID
     */
    public function __construct(int $location_category_id = 0)
    {
        $query = 'SELECT * FROM '. rex::getTablePrefix() .'d2u_courses_location_categories '
                .'WHERE location_category_id = '. $location_category_id;
        $result = rex_sql::factory();
        $result->setQuery($query);
        $num_rows = $result->getRows();

        if ($num_rows > 0) {
            $this->location_category_id = (int) $result->getValue('location_category_id');
            $this->name = (string) $result->getValue('name');
            $this->picture = (string) $result->getValue('picture');
            $this->zoom_level = (int) $result->getValue('zoom_level');
            $this->updatedate = (string) $result->getValue('updatedate');
        }
    }

    /**
     * Delete object in database.
     * @return bool true if successful, otherwise false
     */
    public function delete()
    {
        if ($this->location_category_id > 0) {
            $query = 'DELETE FROM '. rex::getTablePrefix() .'d2u_courses_location_categories '
                    .'WHERE location_category_id = '. $this->location_category_id;
            $result = rex_sql::factory();
            $result->setQuery($query);

            // Don't forget to regenerate URL cache
            d2u_addon_backend_helper::generateUrlCache('location_category_id');
            d2u_addon_backend_helper::generateUrlCache('location_id');

            return $result->hasError() ? false : true;
        }

        return false;

    }

    /**
     * Is location category online? It is, if there are locations within this
     * category hat host online courses.
     * @return bool true, if online, otherwise false
     */
    public function isOnline()
    {
        $query = 'SELECT * FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses '
                .'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations '
                    .'ON courses.location_id = locations.location_id '
                .'WHERE locations.location_category_id = '. $this->location_category_id .' '
                    ."AND courses.online_status = 'online' "
                    .'AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .') ';
        $result = rex_sql::factory();
        $result->setQuery($query);

        return $result->getRows() > 0 ? true : false;
    }

    /**
     * Get all location categories.
     * @param bool $online_only if true, only online location categories are
     * returned (location categories with locations that host online corses)
     * @return LocationCategory[] array with location category objects
     */
    public static function getAll($online_only = true)
    {
        $query = 'SELECT location_category_id FROM '. rex::getTablePrefix() .'d2u_courses_location_categories ';
        if ($online_only) {
            $query = 'SELECT locations.location_category_id FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses '
                .'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations '
                    .'ON courses.location_id = locations.location_id '
                ."WHERE courses.online_status = 'online' "
                    .'AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .') '
                .'GROUP BY locations.location_category_id';
        }
        $result = rex_sql::factory();
        $result->setQuery($query);
        $num_rows = $result->getRows();

        $location_categories = [];
        for ($i = 0; $i < $num_rows; ++$i) {
            $location_categories[] = new self((int) $result->getValue('location_category_id'));
            $result->next();
        }
        return $location_categories;
    }

    /**
     * Get all locations of this category.
     * @param bool $online_only get only locations hosting online courses
     * @return Location[] Array with locations
     */
    public function getLocations($online_only = true)
    {
        $query = 'SELECT location_id FROM '. rex::getTablePrefix() .'d2u_courses_locations '
            .'WHERE location_category_id = '. $this->location_category_id .' ';
        if ($online_only) {
            $query = 'SELECT courses.location_id FROM '. rex::getTablePrefix() .'d2u_courses_courses AS courses '
                .'LEFT JOIN '. rex::getTablePrefix() .'d2u_courses_locations AS locations '
                    .'ON courses.location_id = locations.location_id '
                ."WHERE online_status = 'online' AND location_category_id = ". $this->location_category_id .' '
                    .'AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .') '
                .'GROUP BY location_id';
        }
        $result = rex_sql::factory();
        $result->setQuery($query);
        $num_rows = $result->getRows();

        $locations = [];
        for ($i = 0; $i < $num_rows; ++$i) {
            $locations[] = new Location((int) $result->getValue('location_id'));
            $result->next();
        }
        return $locations;
    }

    /**
     * Returns the URL of this object.
     * @param bool $including_domain true if Domain name should be included
     * @return string URL
     */
    public function getUrl($including_domain = false)
    {
        if ('' === $this->url) {
            $parameterArray = [];
            $parameterArray['location_category_id'] = $this->location_category_id;
            $this->url = rex_getUrl((int) rex_config::get('d2u_courses', 'article_id_locations'), '', $parameterArray, '&');
        }

        if ($including_domain) {
            if (\rex_addon::get('yrewrite') instanceof \rex_addon_interface && rex_addon::get('yrewrite')->isAvailable()) {
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
        $pre_save_object = new self($this->location_category_id);

        $query = 'INSERT INTO ';
        if ($this->location_category_id > 0) {
            $query = 'UPDATE ';
        }
        $query .= rex::getTablePrefix().'d2u_courses_location_categories SET '
            .'`name` = "'. addslashes($this->name) .'", '
            .'picture = "'. $this->picture .'", '
            .'zoom_level = '. $this->zoom_level .', '
            .'updatedate = CURRENT_TIMESTAMP ';
        if ($this->location_category_id > 0) {
            $query .= ' WHERE location_category_id = '. $this->location_category_id;
        }
        $result = rex_sql::factory();
        $result->setQuery($query);

        if (0 === $this->location_category_id) {
            $this->location_category_id = (int) $result->getLastId();
        }

        if (!$result->hasError() && $pre_save_object->name !== $this->name) {
            d2u_addon_backend_helper::generateUrlCache('location_category_id');
            d2u_addon_backend_helper::generateUrlCache('location_id');
        }

        return !$result->hasError();
    }
}
