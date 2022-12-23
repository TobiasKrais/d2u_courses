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
	 * @var int Sort Priority
	 */
	var $priority = 0;

	/**
	 * @var ScheduleCategory Parent schedule category
	 */
	var $parent_schedule_category = false;
	
	/**
	 * @var string[] KuferSQL category name including parent category name.
	 * Devider is " \ ".
	 * Im Kufer XML export field name "Bezeichnungsstruktur".
	 */
	var $kufer_categories = [];
		
	/**
	 * @var string Update timestamp
	 */
	var $updatedate = "";

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
			$this->priority = $result->getValue("priority");
			if($result->getValue("parent_schedule_category_id") > 0) {
				$this->parent_schedule_category = new ScheduleCategory($result->getValue("parent_schedule_category_id"));
			}
			$this->kufer_categories = array_map('trim', preg_grep('/^\s*$/s', explode(PHP_EOL, $result->getValue("kufer_categories")), PREG_GREP_INVERT));
			$this->updatedate = $result->getValue("updatedate");
		}
	}

	/**
	 * Delete object in database
	 * @return boolean true if successful, otherwise false.
	 */
	public function delete() {
		if($this->schedule_category_id > 0) {
			$result = \rex_sql::factory();

			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_schedule_categories '
					.'WHERE schedule_category_id = '. $this->schedule_category_id;
			$result->setQuery($query);

			$return = ($result->hasError() ? false : true);

			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_schedule_categories '
					.'WHERE schedule_category_id = '. $this->schedule_category_id;
			$result->setQuery($query);

			// reset priorities
			$this->setPriority(true);
			
			// Don't forget to regenerate URL cache 
			\d2u_addon_backend_helper::generateUrlCache('schedule_category_id');

			return $return;
		}
		else {
			return false;
		}
	}

	/**
	 * Is category online? It is online if there are online courses in it.
	 * @return boolean true, if online, false if offline
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
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Get all schedule categories
	 * @param boolean $online_only If true only online categories are returned
	 * @param int $parent_category_id Parent category ID if only child categories
	 * should be returned.
	 * @return ScheduleCategory[] Array with schedule category objects.
	 */
	static function getAll($online_only = false, $parent_category_id = 0) {
		$query = "SELECT schedule_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories ";
		if($parent_category_id > 0) {
			$query .= "WHERE parent_schedule_category_id = ". $parent_category_id ." ";
		}
		if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
			$query .= 'ORDER BY priority';
		}
		else {
			$query .= 'ORDER BY name';
		}
		if($online_only) {
			$query = "SELECT c2s.schedule_category_id, schedule_categories.name FROM ". \rex::getTablePrefix() ."d2u_courses_2_schedule_categories AS c2s "
					. "LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_schedule_categories AS schedule_categories "
						."ON c2s.schedule_category_id = schedule_categories.schedule_category_id "
					. "LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses "
						."ON c2s.course_id = courses.course_id AND courses.course_id > 0 "
					."WHERE online_status = 'online' "
						."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") "
						. ($parent_category_id > 0 ? "AND parent_schedule_category_id = ". $parent_category_id ." " : "" )
					."GROUP BY c2s.schedule_category_id, schedule_categories.name ";
			if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
				$query .= 'ORDER BY schedule_categories.priority';
			}
			else {
				$query .= 'ORDER BY schedule_categories.name';
			}
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();
		
		$schedule_categories = [];
		for($i = 0; $i < $num_rows; $i++) {
			$schedule_categories[] = new ScheduleCategory($result->getValue("schedule_category_id"));
			$result->next();
		}

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
				.'GROUP BY child_not.parent_schedule_category_id) ';
		if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
			$query .= 'ORDER BY child.priority';
		}
		else {
			$query .= 'ORDER BY name';
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
	 * Get all parents
	 * @param boolean $online_only If true only online categories are returned
	 * @return ScheduleCategory[] Array with ScheduleCategory objects.
	 */
	static function getAllParents($online_only = false) {
		$query = 'SELECT schedule_category_id, name, priority FROM '.
					\rex::getTablePrefix() . ($online_only ? 'd2u_courses_url_schedule_categories' : 'd2u_courses_schedule_categories')
				.' WHERE parent_schedule_category_id <= 0
				UNION
				SELECT parent_scheds.schedule_category_id, parent_scheds.name, parent_scheds.priority FROM '. \rex::getTablePrefix() .'d2u_courses_url_schedule_categories AS scheds
				LEFT JOIN '. \rex::getTablePrefix() .'d2u_courses_schedule_categories AS parent_scheds ON scheds.parent_schedule_category_id = parent_scheds.schedule_category_id
				WHERE scheds.parent_schedule_category_id > 0
				GROUP BY scheds.schedule_category_id
				ORDER BY '. (\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority' ? 'priority' : 'name');
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
	 * Get all child objects orderd by name
	 * @param boolean $online_only true, if only online children should be returned,
	 * otherwise false
	 * @return ScheduleCategory[] Array with ScheduleCategory objects
	 */
	public function getChildren($online_only = false) {
		$query = "SELECT schedule_category_id FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories "
				."WHERE parent_schedule_category_id = ". $this->schedule_category_id ." ";
		if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
			$query .= 'ORDER BY priority';
		}
		else {
			$query .= 'ORDER BY name';
		}
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
	 * @param boolean $online_only true, if only online courses should be returned,
	 * otherwise false
	 * @return Course[] Array with course objects
	 */
	public function getCourses($online_only = false) {
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
	 * Returns the URL of this object.
	 * @param string $including_domain true if Domain name should be included
	 * @return string URL
	 */
	public function getURL($including_domain = false) {
		if($this->url == "") {
			$parameterArray = [];
			$parameterArray['schedule_category_id'] = $this->schedule_category_id;
			$this->url = \rex_getUrl(\rex_config::get('d2u_courses', 'article_id_schedule_categories'), '', $parameterArray, "&");
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
	 * @return boolean true if successful.
	 */
	public function save() {
		$pre_save_object = new self($this->course_id);

		$query = "INSERT INTO ";
		if($this->schedule_category_id > 0) {
			$query = "UPDATE ";
		}
		$query .= \rex::getTablePrefix().'d2u_courses_schedule_categories SET '
			.'`name` = "'. addslashes($this->name) .'", '
			.'picture = "'. $this->picture .'", '
			.'parent_schedule_category_id = '. ($this->parent_schedule_category !== false ? $this->parent_schedule_category->schedule_category_id : 0) .', '
			.'updatedate = CURRENT_TIMESTAMP ';
		if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			$query .= ', kufer_categories = "'. implode(PHP_EOL, $this->kufer_categories) .'"';
		}
		if($this->schedule_category_id > 0) {			
			$query .= " WHERE schedule_category_id = ". $this->schedule_category_id;
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);

		if($this->schedule_category_id === 0) {
			$this->schedule_category_id = intval($result->getLastId());
		}
		
		if($this->priority != $pre_save_category->priority) {
			$this->setPriority();
		}
		
		if(!$result->hasError() && $pre_save_object->name != $this->name) {
			\d2u_addon_backend_helper::generateUrlCache('schedule_category_id');
		}
		
		return !$result->hasError();
	}
	
	/**
	 * Reassigns priority in database.
	 * @param boolean $delete Reorder priority after deletion
	 */
	private function setPriority($delete = false):void {
		// Pull prios from database
		$query = "SELECT schedule_category_id, priority FROM ". \rex::getTablePrefix() ."d2u_courses_schedule_categories "
			."WHERE schedule_category_id <> ". $this->schedule_category_id ." ORDER BY priority";
		$result = \rex_sql::factory();
		$result->setQuery($query);
		
		// When priority is too small, set at beginning
		if($this->priority <= 0) {
			$this->priority = 1;
		}
		
		// When prio is too high or was deleted, simply add at end 
		if($this->priority > $result->getRows() || $delete) {
			$this->priority = intval($result->getRows()) + 1;
		}

		$target_groups = [];
		for($i = 0; $i < $result->getRows(); $i++) {
			$target_groups[$result->getValue("priority")] = $result->getValue("schedule_category_id");
			$result->next();
		}
		array_splice($target_groups, ($this->priority - 1), 0, [$this->schedule_category_id]);

		// Save all prios
		foreach($target_groups as $prio => $schedule_category_id) {
			$query = "UPDATE ". \rex::getTablePrefix() ."d2u_courses_schedule_categories "
					."SET priority = ". (intval($prio) + 1) ." " // +1 because array_splice recounts at zero
					."WHERE schedule_category_id = ". $schedule_category_id;
			$result = \rex_sql::factory();
			$result->setQuery($query);
		}
	}
}