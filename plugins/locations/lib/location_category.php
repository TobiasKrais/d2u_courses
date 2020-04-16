<?php
/**
 * Redaxo D2U Courses Addon
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace D2U_Courses;

/**
 * Location category.
 */
class LocationCategory {
	/**
	 * @var int ID
	 */
	var $location_category_id = 0;
	
	/**
	 * @var string Name
	 */
	var $name = "";
	
	/**
	 * @var string Picture
	 */
	var $picture = "";
	
	/**
	 * @var int Zoom level on maps
	 */
	var $zoom_level = 10;

	/**
	 * @var string Upadte timestamp
	 */
	var $updatedate = "";

	/**
	 * @var string URL
	 */
	private $url = "";

	/**
	 * Constructor
	 * @param int $location_category_id ID
	 */
	 public function __construct($location_category_id) {
		$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_location_categories "
				."WHERE location_category_id = ". $location_category_id;
		$result = \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		if($num_rows > 0) {
			$this->location_category_id = $result->getValue("location_category_id");
			$this->name = $result->getValue("name");
			$this->picture = $result->getValue("picture");
			$this->zoom_level = $result->getValue("zoom_level");
			$this->updatedate = $result->getValue("updatedate");
		}
	}

	/**
	 * Delete object in database
	 * @return boolean TRUE if successful, otherwise FALSE.
	 */
	public function delete() {
		if($this->location_category_id > 0) {
			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_location_categories '
					.'WHERE location_category_id = '. $this->location_category_id;
			$result = \rex_sql::factory();
			$result->setQuery($query);

			// Don't forget to regenerate URL cache
			\d2u_addon_backend_helper::generateUrlCache('location_category_id');
			\d2u_addon_backend_helper::generateUrlCache('location_id');
			
			return ($result->hasError() ? FALSE : TRUE);
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Is location category online? It is, if there are locations within this
	 * category hat host online courses.
	 * @return boolean TRUE, if online, otherwise FALSE
	 */
	public function isOnline() {
		$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_courses AS courses "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_locations AS locations "
					."ON courses.location_id = locations.location_id "
				."WHERE locations.location_category_id = ". $this->location_category_id ." "
					."AND courses.online_status = 'online' "
					."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") ";
		$result = \rex_sql::factory();
		$result->setQuery($query);

		return $result->getRows() > 0 ? TRUE : FALSE;
	}

	/**
	 * Get all location categories.
	 * @param boolean $online_only If TRUE, only online location categories are
	 * returned (location categories with locations that host online corses).
	 * @return LocationCategory[] Array with location category objects.
	 */
	static function getAll($online_only = TRUE) {
		$query = "SELECT location_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_location_categories ";
		if($online_only) {
			$query = "SELECT locations.location_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_courses AS courses "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_locations AS locations "
					."ON courses.location_id = locations.location_id "
				."WHERE courses.online_status = 'online' "
					."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") "
				."GROUP BY locations.location_category_id";
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		$location_categories = [];
		for($i = 0; $i < $num_rows; $i++) {
			$location_categories[] = new LocationCategory($result->getValue("location_category_id"));
			$result->next();
		}
		return $location_categories;
	}
	
	/**
	 * Get the <link rel="canonical"> tag for page header.
	 * @return Complete tag.
	 */
	public function getCanonicalTag() {
		return '<link rel="canonical" href="'. $this->getURL() .'">';
	}
	
	/**
	 * Get all locations of this category
	 * @param boolean $online_only Get only locations hosting online courses.
	 * @return Location[] Array with locations
	 */
	function getLocations($online_only = TRUE) {
		$query = "SELECT location_id FROM ". \rex::getTablePrefix() ."d2u_courses_locations "
			."WHERE location_category_id = ". $this->location_category_id ." ";
		if($online_only) {
			$query = "SELECT courses.location_id FROM ". \rex::getTablePrefix() ."d2u_courses_courses AS courses "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_locations AS locations "
					."ON courses.location_id = locations.location_id "
				."WHERE online_status = 'online' AND location_category_id = ". $this->location_category_id ." "
					."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") "
				."GROUP BY location_id";
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		$locations = [];
		for($i = 0; $i < $num_rows; $i++) {
			$locations[] = new Location($result->getValue("location_id"));
			$result->next();
		}
		return $locations;
	}
	
	/**
	 * Get the <meta rel="alternate" hreflang=""> tags for page header.
	 * @return Complete tags.
	 */
	public function getMetaAlternateHreflangTags() {
		return '<link rel="alternate" type="text/html" hreflang="'. \rex_clang::getCurrent()->getCode() .'" href="'. $this->getURL() .'" title="'. str_replace('"', '', $this->name) .'">';
	}
	
	/**
	 * Get the <meta name="description"> tag for page header.
	 * @return Complete tag.
	 */
	public function getMetaDescriptionTag() {
		return '<meta name="description" content="">';
	}
	
	/**
	 * Get the <title> tag for page header.
	 * @return Complete title tag.
	 */
	public function getTitleTag() {
		return '<title>'. $this->name .' / '. \rex::getServerName() .'</title>';
	}

	/**
	 * Returns the URL of this object.
	 * @param string $including_domain TRUE if Domain name should be included
	 * @return string URL
	 */
	public function getURL($including_domain = FALSE) {
		if($this->url == "") {
			$parameterArray = [];
			$parameterArray['location_category_id'] = $this->location_category_id;
			$this->url = \rex_getUrl(\rex_config::get('d2u_courses', 'article_id_locations'), '', $parameterArray, "&");
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
	 * Save object.
	 * @return boolean TRUE if successful.
	 */
	public function save() {
		$pre_save_object = new self($this->course_id);

		$query = "INSERT INTO ";
		if($this->location_category_id > 0) {
			$query = "UPDATE ";
		}
		$query .= \rex::getTablePrefix().'d2u_courses_location_categories SET '
			.'`name` = "'. addslashes($this->name) .'", '
			.'picture = "'. $this->picture .'", '
			.'zoom_level = '. $this->zoom_level .', '
			.'updatedate = CURRENT_TIMESTAMP ';
		if($this->location_category_id > 0) {			
			$query .= " WHERE location_category_id = ". $this->location_category_id;
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);

		if($this->location_category_id == 0) {
			$this->location_category_id = $result->getLastId();
		}
		
		if(!$result->hasError() && $pre_save_object->name != $this->name) {
			\d2u_addon_backend_helper::generateUrlCache('location_category_id');
			\d2u_addon_backend_helper::generateUrlCache('location_id');
		}
		
		return !$result->hasError();
	}
}