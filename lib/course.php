<?php
/**
 * Redaxo D2U Courses Addon
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace D2U_Courses;

/**
 * Class containing course data
 */
class Course {
	/**
	 * @var int Course database ID
	 */
	var $course_id = 0;
	
	/**
	 * @var string Name
	 */
	var $name = "";
	
	/**
	 * @var string Teaser - short description
	 */
	var $teaser = "";
	
	/**
	 * @var string Description
	 */
	var $description = "";
	
	/**
	 * @var string Course details
	 */
	var $details_course = "";
	
	/**
	 * @var string Deadline details
	 */
	var $details_deadline = "";
	
	/**
	 * @var string Age details
	 */
	var $details_age = "";
	
	/**
	 * @var string Picture
	 */
	var $picture = "";
	
	/**
	 * @var float Own cost contribution
	 */
	var $price = 0;
	
	/**
	 * @var float Own cost contribution (discount price)
	 */
	var $price_discount = 0;
	
	/**
	 * @var string Course start date
	 */
	var $date_start = "";
	
	/**
	 * @var string Course end date
	 */
	var $date_end = "";
	
	/**
	 * @var string course time details
	 */
	var $time = "";
	
	/**
	 * @var int[] Array with target group IDs 
	 */
	var $target_group_ids = [];
	
	/**
	 * @var Category Course category.
	 */
	var $category = FALSE;
	
	/**
	 * @var int[] Array containing secondary course category IDs
	 */
	var $secondary_category_ids = [];
	
	/**
	 * @var Location Course location
	 */
	var $location = FALSE;
	
	/**
	 * @var string Location room (if several rooms are available)
	 */
	var $room = "";
	
	/**
	 * @var int[] Schedule category IDs
	 */
	var $schedule_category_ids = [];
	
	/**
	 * @var int Number current participants.
	 */
	var $participants_number = 0;
	
	/**
	 * @var int Maximum participants number.
	 */
	var $participants_max = 0;
	
	/**
	 * @var int Minimum participants number.
	 */
	var $participants_min = 0;
	
	/**
	 * @var int Number waiting list participants
	 */
	var $participants_wait_list = 0;
	
	/**
	 * @var string Is online registration possible "yes", "no" oder "booked".
	 */
	var $registration_possible = "";
	
	/**
	 * @var string Course status: "online" or "offline".
	 */
	var $online_status = "";
	
	/**
	 * @var string External web page link
	 */
	var $url_external = "";

	/**
	 * @var string Redaxo article id
	 */
	var $redaxo_article = "";

	/**
	 * @var string Course instructor name
	 */
	var $instructor = "";

	/**
	 * @var string Course number.
	 */
	var $course_number = "";

	/**
	 * @var string[] Array with media pool document names
	 */
	var $downloads = [];

	/**
	 * @var string Update timestamp
	 */
	var $updatedate = "";

	/**
	 * @var string In case object was imported, this string contains import
	 * software string: "KuferSQL" for Kufer import plugin.
	 */
	var $import_type = "";

	/**
	 * @var string course URL
	 */
	private $url = "";

	/**
	 * Constructor.
	 * @param int $course_id Course ID
	 */
	 public function __construct($course_id) {
		if($course_id > 0) {
			$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_courses "
					."WHERE course_id = ". $course_id;
			$result = \rex_sql::factory();
			$result->setQuery($query);

			if($result->getRows() > 0) {
				$this->course_id = $result->getValue("course_id");
				$this->name = stripslashes($result->getValue("name"));
				$this->teaser = stripslashes($result->getValue("teaser"));
				$this->description = stripslashes($result->getValue("description"));
				$this->details_course =  stripslashes($result->getValue("details_course"));
				$this->details_deadline =  stripslashes($result->getValue("details_deadline"));
				$this->details_age = $result->getValue("details_age");
				$this->picture = $result->getValue("picture");
				$this->price = $result->getValue("price");
				$this->price_discount = $result->getValue("price_discount");
				$this->date_start = str_replace(".", "-", $result->getValue("date_start"));
				$this->date_end = str_replace(".", "-", $result->getValue("date_end"));
				$this->time = $result->getValue("time");
				if($result->getValue("category_id") > 0) {
					$this->category = new Category($result->getValue("category_id"));
				}
				if(\rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
					$this->location = $result->getValue("location_id") > 0 ? new Location($result->getValue("location_id")) : FALSE;
					$this->room = stripslashes($result->getValue("room"));
				}
				$this->participants_max = $result->getValue("participants_max");
				$this->participants_min = $result->getValue("participants_min");
				$this->participants_number = $result->getValue("participants_number");
				$this->participants_wait_list = $result->getValue("participants_wait_list");
				$this->registration_possible = $result->getValue("registration_possible");
				$this->online_status = $result->getValue("online_status");
				$this->url_external = $result->getValue("url_external");
				$this->redaxo_article = $result->getValue("redaxo_article");
				$this->instructor = $result->getValue("instructor");
				$this->course_number = $result->getValue("course_number");
				$this->downloads = preg_grep('/^\s*$/s', explode(",", $result->getValue("downloads")), PREG_GREP_INVERT);
				$this->updatedate = $result->getValue("updatedate");

				if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
					$this->import_type = $result->getValue("import_type");
				}
			}
			
			// Get secondary category ids
			$result->setQuery('SELECT * FROM '. \rex::getTablePrefix() .'d2u_courses_2_categories WHERE course_id = '. $this->course_id .';');
			for($i = 0; $i < $result->getRows(); $i++) {
				$this->secondary_category_ids[] = $result->getValue("category_id");
				$result->next();
			}
			// Get schedule categories
			if(\rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
				$result->setQuery('SELECT * FROM '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories WHERE course_id = '. $this->course_id .';');
				for($i = 0; $i < $result->getRows(); $i++) {
					$this->schedule_category_ids[] = $result->getValue("schedule_category_id");
					$result->next();
				}
			}
			// Get target group ids
			if(\rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
				$result->setQuery('SELECT * FROM '. \rex::getTablePrefix() .'d2u_courses_2_target_groups WHERE course_id = '. $this->course_id .';');
				for($i = 0; $i < $result->getRows(); $i++) {
					$this->target_group_ids[] = $result->getValue("target_group_id");
					$result->next();
				}
			}
		}
	}

	/**
	 * Changes the status of a machine
	 */
	public function changeStatus() {
		if($this->online_status == "online") {
			if($this->course_id > 0) {
				$query = "UPDATE ". \rex::getTablePrefix() ."d2u_courses_courses "
					."SET online_status = 'offline' "
					."WHERE course_id = ". $this->course_id;
				$result = \rex_sql::factory();
				$result->setQuery($query);
			}
			$this->online_status = "offline";
		}
		else {
			if($this->course_id > 0) {
				$query = "UPDATE ". \rex::getTablePrefix() ."d2u_courses_courses "
					."SET online_status = 'online' "
					."WHERE course_id = ". $this->course_id;
				$result = \rex_sql::factory();
				$result->setQuery($query);
			}
			$this->online_status = "online";			
		}
	}
	
	/**
	 * Delete object in database
	 * @return boolean TRUE if successful, otherwise FALSE.
	 */
	public function delete() {
		if($this->course_id > 0) {
			$result = \rex_sql::factory();

			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_courses '
					.'WHERE course_id = '. $this->course_id;
			$result->setQuery($query);

			$return = ($result->hasError() ? FALSE : TRUE);
			
			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_categories '
					.'WHERE course_id = '. $this->course_id;
			$result->setQuery($query);
			
			if(\rex_plugin::get('d2u_courses', 'schedule_categories')->isInstalled()) {
				$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories '
						.'WHERE course_id = '. $this->course_id;
				$result->setQuery($query);
			}
			
			if(\rex_plugin::get('d2u_courses', 'target_groups')->isInstalled()) {
				$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_target_groups '
						.'WHERE course_id = '. $this->course_id;
				$result->setQuery($query);
			}
			
			return $return;
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Creates an empty object
	 * @return Course empty course object
	 */
	static public function factory() {
		$course = new Course(0);
		return $course;
	}

	/**
	 * Is course online? To be online, course need status online and start date
	 * is in future
	 * @return boolean TRUE if online, otherwise FALSE
	 */
	public function isOnline() {
		if($this->online_status == "online" && $this->date_start > date("Y-m-d", time())) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Get all courses
	 * @param boolean $online_only If TRUE, return only online courses.
	 * @return Course[] Array with Course objects
	 */
	static function getAll($online_only = FALSE) {
		$query = "SELECT course_id FROM ". \rex::getTablePrefix() ."d2u_courses_courses";
		if($online_only) {
			$query .= " WHERE online_status = 'online'";
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);

		$courses = [];
		for($i = 0; $i < $result->getRows(); $i++) {
			$courses[] = new Course($result->getValue("course_id"));
			$result->next();
		}
		return $courses;
	}

	/**
	 * Get the <link rel="canonical"> tag for page header.
	 * @return Complete tag.
	 */
	public function getCanonicalTag() {
		return '<link rel="canonical" href="'. $this->getURL() .'">';
	}

	/**
	 * Get the <meta rel="alternate" hreflang=""> tags for page header.
	 * @return Complete tags.
	 */
	public function getMetaAlternateHreflangTags() {
		return '<link rel="alternate" type="text/html" hreflang="'. \rex_clang::getCurrent()->getCode() .'" href="'. $this->getURL() .'" title="'. str_replace('"', '', ($this->category !== FALSE && $this->category->parent_category !== FALSE ? $this->category->parent_category->name .': ' : '') . ($this->category !== FALSE ? $this->category->name .': ' : ''). $this->name) .'">';
	}
	
	/**
	 * Get the <meta name="description"> tag for page header.
	 * @return Complete tag.
	 */
	public function getMetaDescriptionTag() {
		return '<meta name="description" content="'. $this->teaser .'">';
	}
	
	/**
	 * Get the <title> tag for page header.
	 * @return Complete title tag.
	 */
	public function getTitleTag() {
		return '<title>'. $this->name .' / '
			.($this->category !== FALSE ? $this->category->name .' / ' : '')
			.($this->category !== FALSE && $this->category->parent_category !== FALSE ? $this->category->parent_category->name .' / ' : '')
			. \rex::getServerName() .'</title>';
	}
		
	/**
	 * Returns the URL of this object.
	 * @param string $including_domain TRUE if Domain name should be included
	 * @return string URL
	 */
	public function getURL($including_domain = FALSE) {
		if($this->url == "") {
			$parameterArray = [];
			$parameterArray['course_id'] = $this->course_id;
			$this->url = \rex_getUrl(\rex_config::get('d2u_courses', 'course_article_id'), '', $parameterArray, "&");
		}

		if($including_domain) {
			if(\rex_addon::get('yrewrite') && \rex_addon::get('yrewrite')->isAvailable())  {
				return str_replace(\rex_yrewrite::getCurrentDomain()->getUrl() .'/', \rex_yrewrite::getCurrentDomain()->getUrl(), \rex_yrewrite::getCurrentDomain()->getUrl() . $this->url);
			}
			else {
				return str_replace(\rex::getServer(). '/', \rex::getServer(), \rex::getServer() . $this->url);
			}
		}
		else {
			return $this->url;
		}
	}
	
	/**
	 * Save object
	 * @return boolean TRUE if successful.
	 */
	public function save() {
		$pre_save_object = new self($this->course_id);

		$query = "INSERT INTO ";
		if($this->course_id > 0) {
			$query = "UPDATE ";
		}
		$query .= \rex::getTablePrefix() .'d2u_courses_courses SET '
			.'`name` = "'. addslashes($this->name) .'", '
			.'teaser = "'. addslashes($this->teaser) .'", '
			.'description = "'. addslashes($this->description) .'", '
			.'details_course = "'. addslashes($this->details_course) .'", '
			.'details_deadline = "'. addslashes($this->details_deadline) .'", '
			.'details_age = "'. $this->details_age .'", '
			.'picture = "'. $this->picture .'", '
			.'price = '. number_format(($this->price > 0 ? $this->price : 0), 2) .', '
			.'price_discount = '. number_format(($this->price_discount > 0 ? $this->price_discount : 0), 2) .', '
			.'date_start = "'. $this->date_start .'", '
			.'date_end = "'. $this->date_end .'", '
			.'`time` = "'. $this->time .'", '
			.'category_id = '. ($this->category !== FALSE ? $this->category->category_id : 0) .', '
			.'participants_max = '. ($this->participants_max > 0 ? $this->participants_max : 0) .', '
			.'participants_min = '. ($this->participants_min > 0 ? $this->participants_min : 0) .', '
			.'participants_number = '. ($this->participants_number > 0 ? $this->participants_number : 0) .', '
			.'participants_wait_list = '. ($this->participants_wait_list > 0 ? $this->participants_wait_list : 0) .', '
			.'registration_possible = "'. $this->registration_possible .'", '
			.'online_status = "'. $this->online_status .'", '
			.'url_external = "'. $this->url_external .'", '
			.'redaxo_article = '. ($this->redaxo_article > 0 ? $this->redaxo_article : 0) .', '
			.'instructor = "'. $this->instructor .'", '
			.'course_number = "'. $this->course_number .'", '
			.'downloads = "'. implode(",", $this->downloads) .'", '
			.'updatedate = CURRENT_TIMESTAMP ';
		if(\rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			$query .= ', location_id = '. ($this->location !== FALSE ? $this->location->location_id : 0) .', '
				.'`room` = "'. addslashes($this->room) .'" ';
		}
		if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			$query .= ', import_type = "'. $this->import_type .'"';
		}
		if($this->course_id > 0) {			
			$query .= " WHERE course_id = ". $this->course_id;
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);

		if($this->course_id == 0) {
			$this->course_id = $result->getLastId();
		}
		if(!$result->hasError() && $pre_save_object->name != $this->name) {
			\d2u_addon_backend_helper::generateUrlCache('course_id');
		}
		
		// Save secondary category IDs
		$query_category = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_categories WHERE course_id = '. $this->course_id .';'. PHP_EOL;
		foreach($this->secondary_category_ids as $secondary_category_id) {
			$query_category .= 'INSERT INTO '. \rex::getTablePrefix() .'d2u_courses_2_categories SET course_id = '. $this->course_id .', category_id = '. $secondary_category_id .';'. PHP_EOL;
		}
		if($this->category !== FALSE) {
			$query_category .= 'REPLACE INTO '. \rex::getTablePrefix() .'d2u_courses_2_categories SET course_id = '. $this->course_id .', category_id = '. $this->category->category_id .';'. PHP_EOL;
		}
		$result->setQuery($query_category);

		// Save schedule category IDs
		if(\rex_plugin::get('d2u_courses', 'schedule_categories')->isAvailable()) {
			$query_schedules = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories WHERE course_id = '. $this->course_id .';'. PHP_EOL;
			foreach($this->schedule_category_ids as $schedule_category_ids) {
				$query_schedules .= 'INSERT INTO '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories SET course_id = '. $this->course_id .', schedule_category_id = '. $schedule_category_ids .';'. PHP_EOL;
			}
			$result->setQuery($query_schedules);
		}

		// Save target group IDs
		if(\rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
			$query_target = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_target_groups WHERE course_id = '. $this->course_id .';'. PHP_EOL;
			foreach($this->target_group_ids as $target_group_id) {
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
	static public function search($keyword) {
		$query = "SELECT courses.course_id FROM ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ";
		if(\rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			$query .= "LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_locations AS locations "
				."ON courses.location_id = locations.location_id ";
		}
		$query .= "LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_categories AS categories "
				."ON courses.category_id = categories.category_id "
			."WHERE courses.online_status = 'online'"
				."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") "
				."AND ("
					."courses.description LIKE '%". $keyword ."%' "
					."OR courses.instructor LIKE '%". $keyword ."%' "
					."OR courses.course_number LIKE '%". $keyword ."%' "
					."OR courses.name LIKE '%". $keyword ."%' "
					."OR categories.name LIKE '%". $keyword ."%' "
					."OR categories.description LIKE '%". $keyword ."%' ";
		if(\rex_plugin::get('d2u_courses', 'locations')->isAvailable()) {
			$query .= "OR locations.name LIKE '%". $keyword ."%' "
					."OR locations.city LIKE '%". $keyword ."%' "
					."OR locations.zip_code LIKE '%". $keyword ."%' ";
		}
		$query .= ")";
		$result = \rex_sql::factory();
		$result->setQuery($query);

		$courses = [];
		for($i = 0; $i <  $result->getRows(); $i++) {
			$courses[] = new Course($result->getValue("course_id"), \rex::getTablePrefix());
			$result->next();
		}
		return $courses;
	}
}