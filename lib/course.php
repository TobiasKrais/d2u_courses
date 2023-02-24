<?php
/**
 * Redaxo D2U Courses Addon.
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace D2U_Courses;

use d2u_addon_backend_helper;
use d2u_courses_frontend_helper;
use DateTime;
use rex_addon;
use rex_config;
use rex_plugin;
use rex_sql;
use rex_url;
use rex_yrewrite;

use function count;
use function is_array;

/**
 * Class containing course data.
 */
class Course
{
    /** @var int Course database ID */
    public $course_id = 0;

    /** @var string Name */
    public $name = '';

    /** @var string Teaser - short description */
    public $teaser = '';

    /** @var string Description */
    public $description = '';

    /** @var string Course details */
    public $details_course = '';

    /** @var string Deadline details */
    public $details_deadline = '';

    /** @var string Age details */
    public $details_age = '';

    /** @var string Picture */
    public $picture = '';

    /** @var float Own cost contribution */
    public $price = 0;

    /** @var float Own cost contribution (discount price) */
    public $price_discount = 0;

    /** @var bool Is price based on salery level */
    public $price_salery_level = false;

    /** @var array<string> Salery level price details */
    public $price_salery_level_details = [];

    /** @var string Course start date */
    public $date_start = '';

    /** @var string Course end date */
    public $date_end = '';

    /** @var string course time details */
    public $time = '';

    /** @var int[] Array with target group IDs */
    public $target_group_ids = [];

    /** @var Category course category */
    public $category = false;

    /** @var int[] Array containing secondary course category IDs */
    public $secondary_category_ids = [];

    /** @var Location Course location */
    public $location = false;

    /** @var string Location room (if several rooms are available) */
    public $room = '';

    /** @var int[] Schedule category IDs */
    public $schedule_category_ids = [];

    /** @var int number current participants */
    public $participants_number = 0;

    /** @var int maximum participants number */
    public $participants_max = 0;

    /** @var int minimum participants number */
    public $participants_min = 0;

    /** @var int Number waiting list participants */
    public $participants_wait_list = 0;

    /**
     * @var string is online registration possible "yes", "yes_number" (= ask
     * only for number participants) "no" oder "booked"
     */
    public $registration_possible = '';

    /** @var string course status: "online" or "offline" */
    public $online_status = '';

    /**
     * @var string course type for Google JSON+LD Format: "event", "course"
     * or simply empty
     */
    public $google_type = '';

    /** @var string External web page link */
    public $url_external = '';

    /** @var string Redaxo article id */
    public $redaxo_article = '';

    /** @var string Course instructor name */
    public $instructor = '';

    /** @var string course number */
    public $course_number = '';

    /** @var array<string> Array with media pool document names */
    public $downloads = [];

    /** @var string Update timestamp */
    public $updatedate = '';

    /**
     * @var string in case object was imported, this string contains import
     * software string: "KuferSQL" for Kufer import plugin
     */
    public $import_type = '';

    /** @var string course URL */
    private $url = '';

    /**
     * Constructor.
     * @param int $course_id Course ID
     */
    public function __construct($course_id)
    {
        if ($course_id > 0) {
            $query = 'SELECT * FROM '. \rex::getTablePrefix() .'d2u_courses_courses '
                    .'WHERE course_id = '. $course_id;
            $result = rex_sql::factory();
            $result->setQuery($query);

            if ($result->getRows() > 0) {
                $this->course_id = $result->getValue('course_id');
                $this->name = stripslashes($result->getValue('name'));
                $this->teaser = stripslashes($result->getValue('teaser'));
                $this->description = stripslashes($result->getValue('description'));
                $this->details_course = stripslashes($result->getValue('details_course'));
                $this->details_deadline = stripslashes($result->getValue('details_deadline'));
                $this->details_age = $result->getValue('details_age');
                $this->picture = $result->getValue('picture');
                $this->price = $result->getValue('price');
                $this->price_discount = $result->getValue('price_discount');
                $this->price_salery_level = $result->getValue('price_salery_level') > 0 ? true : false;
                $this->price_salery_level_details = json_decode($result->getValue('price_salery_level_details'), true);
                $this->date_start = str_replace('.', '-', $result->getValue('date_start'));
                $this->date_end = str_replace('.', '-', $result->getValue('date_end'));
                $this->time = $result->getValue('time');
                if ($result->getValue('category_id') > 0) {
                    $this->category = new Category($result->getValue('category_id'));
                }
                if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
                    $this->location = $result->getValue('location_id') > 0 ? new Location($result->getValue('location_id')) : false;
                    $this->room = stripslashes($result->getValue('room'));
                }
                $this->participants_max = $result->getValue('participants_max');
                $this->participants_min = $result->getValue('participants_min');
                $this->participants_number = $result->getValue('participants_number');
                $this->participants_wait_list = $result->getValue('participants_wait_list');
                $this->registration_possible = $result->getValue('registration_possible');
                $this->online_status = $result->getValue('online_status');
                $this->google_type = $result->getValue('google_type');
                $this->url_external = $result->getValue('url_external');
                $this->redaxo_article = $result->getValue('redaxo_article');
                $this->instructor = $result->getValue('instructor');
                $this->course_number = $result->getValue('course_number');
                $downloads = preg_grep('/^\s*$/s', explode(',', $result->getValue('downloads')), PREG_GREP_INVERT);
                $this->downloads = is_array($downloads) ? $downloads : [];
                $this->updatedate = $result->getValue('updatedate');

                if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
                    $this->import_type = $result->getValue('import_type');
                }
            }

            // Get secondary category ids
            $result->setQuery('SELECT * FROM '. \rex::getTablePrefix() .'d2u_courses_2_categories WHERE course_id = '. $this->course_id .';');
            for ($i = 0; $i < $result->getRows(); ++$i) {
                $this->secondary_category_ids[] = $result->getValue('category_id');
                $result->next();
            }
            // Get schedule categories
            if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
                $result->setQuery('SELECT * FROM '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories WHERE course_id = '. $this->course_id .';');
                for ($i = 0; $i < $result->getRows(); ++$i) {
                    $this->schedule_category_ids[] = $result->getValue('schedule_category_id');
                    $result->next();
                }
            }
            // Get target group ids
            if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
                $result->setQuery('SELECT * FROM '. \rex::getTablePrefix() .'d2u_courses_2_target_groups WHERE course_id = '. $this->course_id .';');
                for ($i = 0; $i < $result->getRows(); ++$i) {
                    $this->target_group_ids[] = $result->getValue('target_group_id');
                    $result->next();
                }
            }
        }
    }

    /**
     * Changes the status of a machine.
     */
    public function changeStatus(): void
    {
        if ('online' === $this->online_status) {
            if ($this->course_id > 0) {
                $query = 'UPDATE '. \rex::getTablePrefix() .'d2u_courses_courses '
                    ."SET online_status = 'offline' "
                    .'WHERE course_id = '. $this->course_id;
                $result = rex_sql::factory();
                $result->setQuery($query);
            }
            $this->online_status = 'offline';
        } else {
            if ($this->course_id > 0) {
                $query = 'UPDATE '. \rex::getTablePrefix() .'d2u_courses_courses '
                    ."SET online_status = 'online' "
                    .'WHERE course_id = '. $this->course_id;
                $result = rex_sql::factory();
                $result->setQuery($query);
            }
            $this->online_status = 'online';
        }

        // Don't forget to regenerate URL cache to make online course available
        d2u_addon_backend_helper::generateUrlCache();
    }

    /**
     * Delete object in database.
     * @return bool true if successful, otherwise false
     */
    public function delete()
    {
        if ($this->course_id > 0) {
            $result = rex_sql::factory();

            $query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_courses '
                    .'WHERE course_id = '. $this->course_id;
            $result->setQuery($query);

            $return = ($result->hasError() ? false : true);

            $query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_categories '
                    .'WHERE course_id = '. $this->course_id;
            $result->setQuery($query);

            if (rex_plugin::get('d2u_courses', 'schedule_categories')->isInstalled()) {
                $query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories '
                        .'WHERE course_id = '. $this->course_id;
                $result->setQuery($query);
            }

            if (rex_plugin::get('d2u_courses', 'target_groups')->isInstalled()) {
                $query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_target_groups '
                        .'WHERE course_id = '. $this->course_id;
                $result->setQuery($query);
            }

            // Don't forget to regenerate URL cache
            d2u_addon_backend_helper::generateUrlCache('course_id');

            return $return;
        }

        return false;

    }

    /**
     * Creates an empty object.
     * @return Course empty course object
     */
    public static function factory()
    {
        $course = new self(0);
        return $course;
    }

    /**
     * Is course online? To be online, course need status online and start date
     * is in future.
     * @return bool true if online, otherwise false
     */
    public function isOnline()
    {
        if ('online' === $this->online_status && $this->date_start > date('Y-m-d', time())) {
            return true;
        }

        return false;

    }

    /**
     * Get all courses.
     * @param bool $online_only if true, return only online courses
     * @return Course[] Array with Course objects
     */
    public static function getAll($online_only = false)
    {
        $query = 'SELECT course_id FROM '. \rex::getTablePrefix() .'d2u_courses_courses';
        if ($online_only) {
            $query .= " WHERE online_status = 'online'";
        }
        $result = rex_sql::factory();
        $result->setQuery($query);

        $courses = [];
        for ($i = 0; $i < $result->getRows(); ++$i) {
            $courses[] = new self($result->getValue('course_id'));
            $result->next();
        }
        return $courses;
    }

    /**
     * Get course as structured data JSON LD code for Google course carousel.
     * @return string JSON LD code including script tag
     */
    public function getJsonLdCourseCarouselCode()
    {
        $json_data = '<script type="application/ld+json">'. PHP_EOL
            .'{'.PHP_EOL
                .'"@context" : "https://schema.org/",'. PHP_EOL
                .'"@type" : "Course",'. PHP_EOL
                .'"name" : "'. addcslashes($this->name, '"') .'",'. PHP_EOL
                .'"description" : "'. ('' != $this->teaser ? addcslashes($this->teaser, '"') : addcslashes($this->name, '"')) .'",'. PHP_EOL
                .'"provider" : {'. PHP_EOL
                    .'"@type" : "Organization",'. PHP_EOL
                    .'"name" : "'. addcslashes(rex_config::get('d2u_courses', 'company_name', ''), '"') .'",'. PHP_EOL
                    .'"sameAs" : "'. (rex_addon::get('yrewrite')->isAvailable() ? rex_yrewrite::getCurrentDomain()->getUrl() : rex::getServer()) .'"'. PHP_EOL
                .'}'. PHP_EOL
            .'}'. PHP_EOL
        .'</script>';
        return $json_data;
    }

    /**
     * Get course as structured data JSON LD code for Google events.
     * @return string JSON LD code including script tag
     */
    public function getJsonLdEventCode()
    {
        // Only return content if minimum requirements are matched
        if ('' == $this->date_start || false === $this->location) {
            return '';
        }

        // Get start and end time
        $time_start = '';
        $time_end = '';
        if ('' != $this->time) {
            preg_match_all('@(\\d*:\\d*)@', $this->time, $matches);
            $time_start = $matches[0][0] ?? '';
            $time_end = isset($matches[0][count($matches[0]) - 1]) ? $matches[0][count($matches[0]) - 1] : '';
        }

        $json_data = '<script type="application/ld+json">'. PHP_EOL
            .'{'.PHP_EOL
                .'"@context" : "https://schema.org/",'. PHP_EOL
                .'"@type" : "Event",'. PHP_EOL
                .'"name" : "'. addcslashes($this->name, '"') .'",'. PHP_EOL
                .'"startDate" : "'. (new DateTime($this->date_start .('' != $time_start ? ''. $time_start : '')))->format('c') .'",'. PHP_EOL
                .'"endDate" : "'. (new DateTime(($this->date_end ?? $this->date_start) .('' != $time_start ? ''. $time_start : '')))->format('c') .'",'. PHP_EOL
                // eventAttendanceMode options: OfflineEventAttendanceMode, OnlineEventAttendanceMode, MixedEventAttendanceMode
                .'"eventAttendanceMode" : "https://schema.org/OfflineEventAttendanceMode",'. PHP_EOL
                .'"eventStatus" : "https://schema.org/EventScheduled",'. PHP_EOL
                .'"location" : {'. PHP_EOL
                    .'"@type" : "Place",'. PHP_EOL
                    .'"name" : "'. addcslashes($this->location->name, '"') .'",'. PHP_EOL
                    .'"address" : {'. PHP_EOL
                        .'"@type" : "PostalAddress"'
                        .('' == $this->location->street ?: ','. PHP_EOL .'"streetAddress" : "'. addcslashes($this->location->street, '"') .'"')
                        .('' == $this->location->city ?: ','. PHP_EOL .'"addressLocality" : "'. addcslashes($this->location->city, '"') .'"')
                        .('' == $this->location->zip_code ?: ','. PHP_EOL .'"postalCode" : "'. $this->location->zip_code .'"')
                        .('' == $this->location->country_code ?: ','. PHP_EOL .'"addressCountry" : "'. $this->location->country_code .'"')
                    . PHP_EOL.'}'. PHP_EOL
                .'},'. PHP_EOL;
        if ('' != $this->picture) {
            $json_data .= '"image": ['
                .'"'. rex_yrewrite::getCurrentDomain()->getUrl() . ltrim(rex_url::media($this->picture), '/') .'"'
                .'],'. PHP_EOL;
        }
        $json_data .= '"description" : "'. ('' != $this->teaser ? addcslashes($this->teaser, '"') : addcslashes($this->name, '"')) .'",'. PHP_EOL
                .'"offers" : {'. PHP_EOL
                    .'"@type" : "Offer",'. PHP_EOL
                    .'"url" : "'. $this->getUrl(true) .'",'. PHP_EOL;
        if ('' != $this->price) {
            $json_data .= '"price" : "'. $this->price .'",'. PHP_EOL
                    .'"priceCurrency" : "EUR"';
        }
        if ('yes' == $this->registration_possible || 'yes_number' == $this->registration_possible) {
            $json_data .= ','. PHP_EOL .'"availability" : "https://schema.org/InStock"'. PHP_EOL;
        } elseif ('booked' == $this->registration_possible) {
            $json_data .= ','. PHP_EOL .'"availability" : "https://schema.org/SoldOut"'. PHP_EOL;
        }
        $json_data .= PHP_EOL .'},'. PHP_EOL;
        if ('' != $this->instructor) {
            $json_data .= '"performer" : {'. PHP_EOL
                    .'"@type" : "PerformingGroup",'. PHP_EOL
                    .'"name" : "'. $this->instructor .'"'. PHP_EOL
                .'},'. PHP_EOL;
        }
        $json_data .= '"organizer" : {'. PHP_EOL
                    .'"@type" : "Organization",'. PHP_EOL
                    .'"name" : "'. addcslashes(rex_config::get('d2u_courses', 'company_name', ''), '"') .'",'. PHP_EOL
                    .'"url" : "'. rex_yrewrite::getCurrentDomain()->getUrl() .'"'. PHP_EOL
                .'}'. PHP_EOL
            .'}'. PHP_EOL
            .'</script>';
        return $json_data;
    }

    /**
     * Returns the URL of this object.
     * @param string $including_domain true if Domain name should be included
     * @return string URL
     */
    public function getUrl($including_domain = false)
    {
        if ('' == $this->url) {
            $parameterArray = [];
            $parameterArray['course_id'] = $this->course_id;
            $this->url = rex_getUrl(rex_config::get('d2u_courses', 'course_article_id'), '', $parameterArray, '&');
        }

        if ($including_domain) {
            if (rex_addon::get('yrewrite') && rex_addon::get('yrewrite')->isAvailable()) {
                return str_replace(rex_yrewrite::getCurrentDomain()->getUrl() .'/', rex_yrewrite::getCurrentDomain()->getUrl(), rex_yrewrite::getCurrentDomain()->getUrl() . $this->url);
            }

            return str_replace(\rex::getServer(). '/', \rex::getServer(), \rex::getServer() . $this->url);

        }

        return $this->url;

    }

    /**
     * Save object.
     * @return bool true if successful
     */
    public function save()
    {
        $pre_save_object = new self($this->course_id);

        $query = 'INSERT INTO ';
        if ($this->course_id > 0) {
            $query = 'UPDATE ';
        }
        $query .= \rex::getTablePrefix() .'d2u_courses_courses SET '
            .'`name` = "'. addslashes($this->name) .'", '
            .'teaser = "'. addslashes($this->teaser) .'", '
            .'description = "'. addslashes($this->description) .'", '
            .'details_course = "'. addslashes($this->details_course) .'", '
            .'details_deadline = "'. addslashes($this->details_deadline) .'", '
            .'details_age = "'. $this->details_age .'", '
            .'picture = "'. $this->picture .'", '
            .'price = '. number_format($this->price > 0 ? $this->price : 0, 2, '.', '') .', '
            .'price_discount = '. number_format($this->price_discount > 0 ? $this->price_discount : 0, 2, '.', '') .', '
            .'price_salery_level = '. ($this->price_salery_level ? 1 : 0) .', '
            ."price_salery_level_details = '". json_encode($this->price_salery_level_details, JSON_UNESCAPED_UNICODE) ."', "
            .'date_start = "'. $this->date_start .'", '
            .'date_end = "'. $this->date_end .'", '
            .'`time` = "'. $this->time .'", '
            .'category_id = '. (false !== $this->category ? $this->category->category_id : 0) .', '
            .'participants_max = '. ($this->participants_max ?: 0) .', '
            .'participants_min = '. ($this->participants_min ?: 0) .', '
            .'participants_number = '. ($this->participants_number ?: 0) .', '
            .'participants_wait_list = '. ($this->participants_wait_list ?: 0) .', '
            .'registration_possible = "'. $this->registration_possible .'", '
            .'online_status = "'. $this->online_status .'", '
            .'google_type = "'. $this->google_type .'", '
            .'url_external = "'. $this->url_external .'", '
            .'redaxo_article = '. ($this->redaxo_article ?: 0) .', '
            .'instructor = "'. $this->instructor .'", '
            .'course_number = "'. $this->course_number .'", '
            .'downloads = "'. implode(',', $this->downloads) .'", '
            .'updatedate = CURRENT_TIMESTAMP ';
        if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
            $query .= ', location_id = '. (false !== $this->location ? $this->location->location_id : 0) .', '
                .'`room` = "'. addslashes($this->room) .'" ';
        }
        if (rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
            $query .= ', import_type = "'. $this->import_type .'"';
        }
        if ($this->course_id > 0) {
            $query .= ' WHERE course_id = '. $this->course_id;
        }
        $result = rex_sql::factory();
        $result->setQuery($query);

        if (0 === $this->course_id) {
            $this->course_id = (int) $result->getLastId();
        }
        if (!$result->hasError() && $pre_save_object->name != $this->name) {
            d2u_addon_backend_helper::generateUrlCache('course_id');
        }

        // Save secondary category IDs
        $query_category = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_categories WHERE course_id = '. $this->course_id .';'. PHP_EOL;
        foreach ($this->secondary_category_ids as $secondary_category_id) {
            $query_category .= 'INSERT INTO '. \rex::getTablePrefix() .'d2u_courses_2_categories SET course_id = '. $this->course_id .', category_id = '. $secondary_category_id .';'. PHP_EOL;
        }
        if (false !== $this->category) {
            $query_category .= 'REPLACE INTO '. \rex::getTablePrefix() .'d2u_courses_2_categories SET course_id = '. $this->course_id .', category_id = '. $this->category->category_id .';'. PHP_EOL;
        }
        $result->setQuery($query_category);

        // Save schedule category IDs
        if (rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
            $query_schedules = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories WHERE course_id = '. $this->course_id .';'. PHP_EOL;
            foreach ($this->schedule_category_ids as $schedule_category_ids) {
                $query_schedules .= 'INSERT INTO '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories SET course_id = '. $this->course_id .', schedule_category_id = '. $schedule_category_ids .';'. PHP_EOL;
            }
            $result->setQuery($query_schedules);
        }

        // Save target group IDs
        if (rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
            $query_target = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_target_groups WHERE course_id = '. $this->course_id .';'. PHP_EOL;
            foreach ($this->target_group_ids as $target_group_id) {
                $query_target .= 'INSERT INTO '. \rex::getTablePrefix() .'d2u_courses_2_target_groups SET course_id = '. $this->course_id .', target_group_id = '. $target_group_id .';'. PHP_EOL;
            }
            $result->setQuery($query_target);
        }

        return !$result->hasError();
    }

    /**
     * Searches course infos for keyword.
     * @param string $keyword Keyword
     */
    public static function search($keyword)
    {
        $query = 'SELECT courses.course_id FROM '. \rex::getTablePrefix() .'d2u_courses_courses AS courses ';
        if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
            $query .= 'LEFT JOIN '. \rex::getTablePrefix() .'d2u_courses_locations AS locations '
                .'ON courses.location_id = locations.location_id ';
        }
        $query .= 'LEFT JOIN '. \rex::getTablePrefix() .'d2u_courses_categories AS categories '
                .'ON courses.category_id = categories.category_id '
            ."WHERE courses.online_status = 'online'"
                .'AND ('. d2u_courses_frontend_helper::getShowTimeWhere() .') '
                .'AND ('
                    ."courses.description LIKE '%". $keyword ."%' "
                    ."OR courses.instructor LIKE '%". $keyword ."%' "
                    ."OR courses.course_number LIKE '%". $keyword ."%' "
                    ."OR courses.name LIKE '%". $keyword ."%' "
                    ."OR categories.name LIKE '%". $keyword ."%' "
                    ."OR categories.description LIKE '%". $keyword ."%' ";
        if (rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
            $query .= "OR locations.name LIKE '%". $keyword ."%' "
                    ."OR locations.city LIKE '%". $keyword ."%' "
                    ."OR locations.zip_code LIKE '%". $keyword ."%' ";
        }
        $query .= ')';
        $result = rex_sql::factory();
        $result->setQuery($query);

        $courses = [];
        for ($i = 0; $i < $result->getRows(); ++$i) {
            $courses[] = new self($result->getValue('course_id'), \rex::getTablePrefix());
            $result->next();
        }
        return $courses;
    }
}
