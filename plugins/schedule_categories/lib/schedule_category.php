<?php
/**
 * Redaxo D2U Courses Addon
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace D2U_Courses;

/**
 * Schedule category.
 */
class ScheduleCategory {
	/**
	 * @var int ID
	 */
	var $schedule_category_id = 0;
	
	/**
	 * @var string Name
	 */
	var $name = "";
	
	/**
	 * @var string Picture
	 */
	var $picture = "";
	
	/**
	 * @var ScheduleCategory Parent schedule category
	 */
	var $parent_schedule_category = FALSE;
	
	/**
	 * @var string[] KuferSQL category name including parent category name.
	 * Devider is " \ ".
	 * Im Kufer XML export field name "Bezeichnungsstruktur".
	 */
	var $kufer_categories = [];
		
	/**
	 * @var int Update UNIX timestamp
	 */
	var $updatedate = 0;

	/**
	 * @var string URL
	 */
	private $url = "";

	/**
	 * Constructor.
	 * @param int $schedule_category_id ID.
	 */
	 public function __construct($schedule_category_id) {
		$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories "
				."WHERE schedule_category_id = ". $schedule_category_id;
		$result = \rex_sql::factory();
		$result->setQuery($query);

		if($result->getRows() > 0) {
			$this->schedule_category_id = $result->getValue("schedule_category_id");
			$this->name = $result->getValue("name");
			$this->picture = $result->getValue("picture");
			if($result->getValue("parent_schedule_category_id") > 0) {
				$this->parent_schedule_category = new ScheduleCategory($result->getValue("parent_schedule_category_id"));
			}
			$this->kufer_categories = array_map('trim', preg_grep('/^\s*$/s', explode(PHP_EOL, $result->getValue("kufer_categories")), PREG_GREP_INVERT));
			$this->updatedate = $result->getValue("updatedate");
		}
	}

	/**
	 * Delete object in database
	 * @return boolean TRUE if successful, otherwise FALSE.
	 */
	public function delete() {
		if($this->schedule_category_id > 0) {
			$result = \rex_sql::factory();

			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_schedule_categories '
					.'WHERE schedule_category_id = '. $this->schedule_category_id;
			$result->setQuery($query);

			$return = ($result->hasError() ? FALSE : TRUE);

			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories '
					.'WHERE schedule_category_id = '. $this->schedule_category_id;
			$result->setQuery($query);

			return $return;
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Is category online? It is online if there are online courses in it.
	 * @return boolean TRUE, if online, FALSE if offline
	 */
	public function isOnline() {
		$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_2_schedule_categories AS c2s "
			."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ON c2s.course_id = courses.course_id "
				."WHERE c2s.schedule_category_id = ". $this->schedule_category_id ." "
					."AND online_status = 'online' "
					."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") ";
		$result = \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		if($num_rows > 0) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Get all schedule categories
	 * @param boolean $online_only If true only online categories are returned
	 * @param int $parent_category_id Parent category ID if only child categories
	 * should be returned.
	 * @return ScheduleCategory[] Array with schedule category objects.
	 */
	static function getAll($online_only = FALSE, $parent_category_id = 0) {
		$query = "SELECT schedule_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories ";
		if($parent_category_id > 0) {
			$query .= "WHERE parent_schedule_category_id = ". $parent_category_id ." ";
		}
		$query .= "ORDER BY name";
		if($online_only) {
			$query = "SELECT schedule_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_2_schedule_categories AS c2s "
					. "LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses "
						."ON c2s.course_id = courses.course_id AND courses.course_id > 0 "
					."WHERE online_status = 'online' "
						."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") "
					."GROUP BY schedule_category_id";
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();
		
		$schedule_category_ids = [];
		for($i = 0; $i < $num_rows; $i++) {
			if($online_only) {
				$schedule_category_ids = array_merge($schedule_category_ids, preg_grep('/^\s*$/s', explode("|", $result->getValue("schedule_category_ids")), PREG_GREP_INVERT));
			}
			else {
				$schedule_category_ids[] = $result->getValue("schedule_category_id");
			}
			$result->next();
		}
		$schedule_category_ids = array_unique($schedule_category_ids);

		
		$schedule_categories = [];
		foreach($schedule_category_ids as $schedule_category_id) {
			$schedule_category = new ScheduleCategory($schedule_category_id);
			if($schedule_category->schedule_category_id > 0) {
				if($parent_category_id > 0 && $schedule_category->parent_schedule_category !== FALSE && $schedule_category->parent_schedule_category->schedule_category_id == $parent_category_id) {
					$schedule_categories[] = $schedule_category;
				}
				if($parent_category_id == 0) {
					if($schedule_category->parent_schedule_category !== FALSE &&
						!in_array($schedule_category->parent_schedule_category, $schedule_categories)) {
						$schedule_categories[$schedule_category->parent_schedule_category->name] = $schedule_category->parent_schedule_category;
					}
					$schedule_categories[$schedule_category->name] = $schedule_category;
				}
			}
		}
		ksort($schedule_categories);
		return $schedule_categories;
	}
	
	/**
	 * Get all categories which are not parents
	 * @param boolean $online_only If true only online categories are returned
	 * @return ScheduleCategory[] Array with ScheduleCategory objects.
	 */
	static function getAllNotParents() {
		$query = 'SELECT CONCAT_WS(" → ", parent.name, child.name) AS name, child.schedule_category_id FROM ' . \rex::getTablePrefix() . 'd2u_courses_schedule_categories AS child '
			.'LEFT JOIN ' . \rex::getTablePrefix() . 'd2u_courses_schedule_categories AS parent '
				.'ON child.parent_schedule_category_id = parent.schedule_category_id '
			.'WHERE (child.name, child.schedule_category_id) NOT IN '
				.'(SELECT parent_not.name, parent_not.schedule_category_id FROM ' . \rex::getTablePrefix() . 'd2u_courses_schedule_categories AS child_not '
					.'LEFT JOIN ' .\rex::getTablePrefix() . 'd2u_courses_schedule_categories AS parent_not '
						.'ON child_not.parent_schedule_category_id = parent_not.schedule_category_id '
				.'WHERE parent_not.schedule_category_id > 0 '
				.'GROUP BY child_not.parent_schedule_category_id) '
			.'ORDER BY name';
		$result =  \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		$categories = [];
		for($i = 0; $i < $num_rows; $i++) {
			$categories[] = new ScheduleCategory($result->getValue("schedule_category_id"));
			$result->next();
		}

		return $categories;
	}

	/**
	 * Get all parents
	 * @param boolean $online_only If true only online categories are returned
	 * @return ScheduleCategory[] Array with ScheduleCategory objects.
	 */
	static function getAllParents($online_only = FALSE) {
		$query = "SELECT schedule_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories"
			." WHERE parent_schedule_category_id <= 0";
		if($online_only) {
			$query = "SELECT schedule_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_url_schedule_categories "
				." WHERE parent_schedule_category_id <= 0";
		}
		$result =  \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		$categories = [];
		for($i = 0; $i < $num_rows; $i++) {
			$categories[] = new ScheduleCategory($result->getValue("schedule_category_id"));
			$result->next();
		}

		return $categories;
	}

	/**
	 * Get the <link rel="canonical"> tag for page header.
	 * @return Complete tag.
	 */
	public function getCanonicalTag() {
		return '<link rel="canonical" href="'. $this->getURL() .'">';
	}

	/**
	 * Get all child objects orderd by name
	 * @param boolean $online_only TRUE, if only online children should be returned,
	 * otherwise FALSE
	 * @return ScheduleCategory[] Array with ScheduleCategory objects
	 */
	public function getChildren($online_only = FALSE) {
		$query = "SELECT schedule_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories "
				."WHERE parent_schedule_category_id = ". $this->schedule_category_id ." ";
		$query .= "ORDER BY name";
		$result =  \rex_sql::factory();
		$result->setQuery($query);

		$child_categories = [];
		for($i = 0; $i < $result->getRows(); $i++) {
			$child_category = new ScheduleCategory($result->getValue("schedule_category_id"));
			if(($online_only && $child_category->isOnline()) || !$online_only) {
				$child_categories[] = $child_category;
			}
			$result->next();
		}
		return $child_categories;
	}
	
	/**
	 * Get all courses orderd by start date
	 * @param boolean $online_only TRUE, if only online courses should be returned,
	 * otherwise FALSE
	 * @return Course[] Array with course objects
	 */
	public function getCourses($online_only = FALSE) {
		$query = "SELECT courses.course_id FROM ". \rex::getTablePrefix() ."d2u_courses_2_schedule_categories AS c2s "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ON c2s.course_id = courses.course_id "
				."WHERE c2s.schedule_category_id = ". $this->schedule_category_id ." ";
		if($online_only) {
			$query .= "AND online_status = 'online' "
				."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") ";
		}
		$query .= "ORDER BY date_start, name";

		$result = \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		$courses = [];
		for($i = 0; $i < $num_rows; $i++) {
			$courses[] = new Course($result->getValue("course_id"));
			$result->next();
		}
		return $courses;
	}

	/**
	 * Get the <meta rel="alternate" hreflang=""> tags for page header.
	 * @return Complete tags.
	 */
	public function getMetaAlternateHreflangTags() {
		return '<link rel="alternate" type="text/html" hreflang="'. \rex_clang::getCurrent()->getCode() .'" href="'. $this->getURL() .'" title="'. str_replace('"', '', ($this->parent_schedule_category !== FALSE ? $this->parent_schedule_category->name .': ' : ''). $this->name) .'">';
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
		return '<title>'. $this->name .' / '. ($this->parent_schedule_category !== FALSE ? $this->parent_schedule_category->name .' / ' : ''). \rex::getServerName() .'</title>';
	}
		
	/**
	 * Returns the URL of this object.
	 * @param string $including_domain TRUE if Domain name should be included
	 * @return string URL
	 */
	public function getURL($including_domain = FALSE) {
		if($this->url == "") {
			$parameterArray = [];
			$parameterArray['schedule_category_id'] = $this->schedule_category_id;
			$this->url = \rex_getUrl(\rex_config::get('d2u_courses', 'article_id_schedule_categories'), '', $parameterArray, "&");
		}

		if($including_domain) {
			if(\rex_addon::get('yrewrite')->isAvailable())  {
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
		$query = "INSERT INTO ";
		if($this->schedule_category_id > 0) {
			$query = "UPDATE ";
		}
		$query .= \rex::getTablePrefix().'d2u_courses_schedule_categories SET '
			.'`name` = "'. addslashes($this->name) .'", '
			.'picture = "'. $this->picture .'", '
			.'parent_schedule_category_id = '. ($this->parent_schedule_category !== FALSE ? $this->parent_schedule_category->schedule_category_id : 0) .', '
			.'updatedate = "'. time() .'"';
		if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			$query .= ', kufer_categories = "'. implode(PHP_EOL, $this->kufer_categories) .'"';
		}
		if($this->schedule_category_id > 0) {			
			$query .= " WHERE schedule_category_id = ". $this->schedule_category_id;
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);

		if($this->schedule_category_id == 0) {
			$this->schedule_category_id = $result->getLastId();
		}
		
		return !$result->hasError();
	}
}