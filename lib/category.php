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
class Category {
	/**
	 * @var int Database ID
	 */
	var $category_id = 0;
	
	/**
	 * @var string Name
	 */
	var $name = "";
	
	/**
	 * @var string Description
	 */
	var $description = "";
	
	/**
	 * @var string Color (hexadecimal value or CSS keyword)
	 */
	var $color = "grey";
	
	/**
	 * @var string Picture
	 */
	var $picture = "";
	
	/**
	 * @var Category|bool Parent category.
	 */
	var $parent_category = false;
	
	/**
	 * @var int Sort Priority
	 */
	var $priority = 0;

	/**
	 * @var string[] KuferSQL category name including parent category name.
	 * Devider is " \ ".
	 * Im Kufer XML export field name "Bezeichnungsstruktur".
	 */
	var $kufer_categories = [];
	
	/**
	 * @var string Course type for Google JSON+LD Format: "event", "course"
	 * or simply empty.
	 */
	var $google_type = "";
	
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
	 * @param int $category_id Category ID.
	 */
	 public function __construct($category_id) {
		$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_categories "
				."WHERE category_id = ". $category_id;
		$result =  \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		if($num_rows > 0) {
			$this->category_id = $result->getValue("category_id");
			$this->name = $result->getValue("name");
			$this->description = stripslashes($result->getValue("description"));
			if($result->getValue("color") != "") {
				$this->color = $result->getValue("color");
			}
			$this->picture = $result->getValue("picture");
			if($result->getValue("parent_category_id") > 0) {
				$this->parent_category = new Category((int) $result->getValue("parent_category_id"));
			}
			$this->priority = $result->getValue("priority");
			$this->updatedate = $result->getValue("updatedate");
			if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
				$this->kufer_categories = array_map('trim', preg_grep('/^\s*$/s', explode(PHP_EOL, $result->getValue("kufer_categories")), PREG_GREP_INVERT));
				$this->google_type = $result->getValue("google_type");
			}
		}
	}

	/**
	 * Delete object in database
	 * @return boolean true if successful, otherwise false.
	 */
	public function delete() {
		if($this->category_id > 0) {
			$result = \rex_sql::factory();

			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_categories '
					.'WHERE category_id = '. $this->category_id;
			$result->setQuery($query);

			$return = ($result->hasError() ? false : true);
			
			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_categories '
					.'WHERE category_id = '. $this->category_id;
			$result->setQuery($query);

			// reset priorities
			$this->setPriority(true);
			
			// Don't forget to regenerate URL cache
			\d2u_addon_backend_helper::generateUrlCache();
			
			return $return;
		}
		else {
			return false;
		}
	}

	/**
	 * Is category online? It is online if there are online courses in it or one
	 * of its children.
	 * @return boolean true, if online, false if offline
	 */
	public function isOnline() {
		$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_url_categories "
				."WHERE category_id = ". $this->category_id;
		$result =  \rex_sql::factory();
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
	 * Get all categories
	 * @param boolean $online_only If true only online categories are returned
	 * @param int $parent_category_id Parent category ID if only child categories
	 * should be returned.
	 * @return Category[] Array with category objects.
	 */
	static function getAll($online_only = false, $parent_category_id = 0) {
		$query = "SELECT category_id, priority FROM ". \rex::getTablePrefix() ."d2u_courses_categories AS categories ";
		if($parent_category_id > 0) {
			$query .= "WHERE parent_category_id = ". $parent_category_id ." ";
		}
		if($online_only) {
			$query = "SELECT courses.category_id, categories.priority FROM ". \rex::getTablePrefix() ."d2u_courses_courses AS courses "
					."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_categories AS categories "
						."ON courses.category_id = categories.category_id ";
			if($parent_category_id > 0) {
				$query .= "WHERE parent_category_id = ". $parent_category_id ." "
						."AND online_status = 'online' ";
			}
			else {
				$query .= "WHERE online_status = 'online' ";
			}
			$query .= "AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") "
					."GROUP BY category_id ";
		}
		if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
			$query .= 'ORDER BY categories.priority';
		}
		else {
			$query .= 'ORDER BY name';
		}
		$result =  \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		$categories = [];
		$ids = [];
		for($i = 0; $i < $num_rows; $i++) {
			$category = new Category($result->getValue("category_id"));
			// Add parent categories if no special parent category is selected - parent categories contain no courses
			if($parent_category_id == 0 && $category->parent_category !== false
					&& $category->parent_category->category_id > 0 && !in_array($category->parent_category->category_id, $ids)) {
				$category = new Category($category->parent_category->category_id);
				$categories[] = $category;
				$ids[] = $category->category_id;
			}

			if(!in_array($category->category_id, $ids)) {
				$ids[] = $category->category_id;
				$categories[] = $category;
			}
			$result->next();
		}

		return $categories;
	}

	/**
	 * Get all child objects orderd by name
	 * @param boolean $online_only true, if only online children should be returned,
	 * otherwise false
	 * @return Category[] Array with category objects
	 */
	public function getChildren($online_only = false) {
		$query = "SELECT category_id FROM ". \rex::getTablePrefix() ."d2u_courses_categories "
				."WHERE parent_category_id = ". $this->category_id ." ";
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
			$child_category = new Category($result->getValue("category_id"));
			if(($online_only && $child_category->isOnline()) || !$online_only) {
				$child_categories[] = $child_category;
			}
			$result->next();
		}
		return $child_categories;
	}
	
	/**
	 * Get all categories which are not parents
	 * @param boolean $online_only If true only online categories are returned
	 * @return Category[] Array with category objects.
	 */
	static function getAllNotParents() {
		$query = 'SELECT CONCAT_WS(" → ", parent.name, child.name) AS name, child.category_id FROM ' . \rex::getTablePrefix() . 'd2u_courses_categories AS child '
			.'LEFT JOIN ' . \rex::getTablePrefix() . 'd2u_courses_categories AS parent '
				.'ON child.parent_category_id = parent.category_id '
			.'LEFT JOIN ' . \rex::getTablePrefix() . 'd2u_courses_categories AS grand_parent '
				.'ON parent.parent_category_id = grand_parent.category_id '
			.'WHERE (child.name, child.category_id) NOT IN '
				.'(SELECT parent_not.name, parent_not.category_id FROM ' . \rex::getTablePrefix() . 'd2u_courses_categories AS child_not '
					.'LEFT JOIN ' .\rex::getTablePrefix() . 'd2u_courses_categories AS parent_not '
						.'ON child_not.parent_category_id = parent_not.category_id '
				.'WHERE parent_not.category_id > 0 '
				.'GROUP BY child_not.parent_category_id) ';
		if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
			$query .= 'ORDER BY child.priority';
		}
		else {
			$query .= 'ORDER BY child.name';
		}
		$result =  \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		$categories = [];
		for($i = 0; $i < $num_rows; $i++) {
			$categories[] = new Category($result->getValue("category_id"));
			$result->next();
		}

		return $categories;
	}

	/**
	 * Get all root parent categories
	 * @param boolean $online_only If true only online categories are returned
	 * @return Category[] Array with category objects.
	 */
	static function getAllParents($online_only = false) {
		$query = "SELECT category_id FROM ". \rex::getTablePrefix() ."d2u_courses_categories AS cats"
			." WHERE parent_category_id <= 0 ";
		if($online_only) {
			$query = "SELECT url_cat.category_id FROM ". \rex::getTablePrefix() ."d2u_courses_url_categories AS url_cat "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_categories AS cats "
					."ON url_cat.category_id = cats.category_id "
				." WHERE url_cat.parent_category_id <= 0 ";
		}
		if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
			$query .= 'ORDER BY cats.priority';
		}
		else {
			$query .= 'ORDER BY cats.name';
		}
		$result =  \rex_sql::factory();
		$result->setQuery($query);
		$num_rows = $result->getRows();

		$categories = [];
		for($i = 0; $i < $num_rows; $i++) {
			$categories[$result->getValue("category_id")] = new Category($result->getValue("category_id"));
			$result->next();
		}

		return $categories;
	}

	/**
	 * Get all courses orderd by start date
	 * @param boolean $online_only true, if only online courses should be returned,
	 * otherwise false
	 * @return Course[] Array with course objects
	 */
	public function getCourses($online_only = false) {
		$query = "SELECT courses.course_id FROM ". \rex::getTablePrefix() ."d2u_courses_2_categories AS c2c "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ON c2c.course_id = courses.course_id "
				."WHERE c2c.category_id = ". $this->category_id ." AND courses.course_id  > 0 ";
		if($online_only) {
			$query .= "AND online_status = 'online' "
				."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") ";
		}
		$query .= "ORDER BY date_start, name";
		$result =  \rex_sql::factory();
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
	 * Get root parent of this category
	 * @return Category root parent category
	 */
	public function getPartentRoot() {
		if($this->parent_category !== false) {
			if($this->parent_category->parent_category !== false) {
				if($this->parent_category->parent_category->parent_category !== false) {
					return $this->parent_category->parent_category->parent_category;
				}
				return $this->parent_category->parent_category;
			}
			return $this->parent_category;
		}
		return $this;
	}

	/**
	 * Returns the URL of this object.
	 * @param string $including_domain true if Domain name should be included
	 * @return string URL
	 */
	public function getURL($including_domain = false) {
		if($this->url == "") {
			$parameterArray = [];
			$parameterArray['courses_category_id'] = $this->category_id;
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
	 * Save object.
	 * @return boolean true if successful.
	 */
	public function save() {
		// save priority, but only if new or changed
		$pre_save_object = new self($this->category_id);

		$query = "INSERT INTO ";
		if($this->category_id > 0) {
			$query = "UPDATE ";
		}
		$query .= \rex::getTablePrefix().'d2u_courses_categories SET '
			.'`name` = "'. addslashes($this->name) .'", '
			.'description = "'. addslashes($this->description) .'", '
			.'color = "'. $this->color .'", '
			.'picture = "'. $this->picture .'", '
			.'parent_category_id = '. ($this->parent_category !== false ? $this->parent_category->category_id : 0) .', '
			.'updatedate = CURRENT_TIMESTAMP';
		if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			$query .= ', kufer_categories = "'. implode(PHP_EOL, $this->kufer_categories) .'"'
				.', google_type = "'. $this->google_type .'"';
		}
		if($this->category_id > 0) {			
			$query .= " WHERE category_id = ". $this->category_id;
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);
		$error = $result->hasError();

		if($this->category_id === 0) {
			$this->category_id = intval($result->getLastId());
		}

		if($this->priority !== $pre_save_object->priority) {
			$this->setPriority();
		}
		
		if(!$error && $pre_save_object->name != $this->name) {
			\d2u_addon_backend_helper::generateUrlCache('courses_category_id');
			\d2u_addon_backend_helper::generateUrlCache('course_id');
			if(\rex_plugin::get('d2u_courses', 'target_groups')->isAvailable()) {
				\d2u_addon_backend_helper::generateUrlCache('target_group_child_id');		
			}
		}
		
		return !$error;
	}
	
	/**
	 * Reassigns priorities in database.
	 * @param boolean $delete Reorder priority after deletion
	 */
	private function setPriority($delete = false):void {
		// Pull prios from database
		$query = "SELECT category_id, priority FROM ". \rex::getTablePrefix() ."d2u_courses_categories "
			."WHERE category_id <> ". $this->category_id ." ORDER BY priority";
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

		$categories = [];
		for($i = 0; $i < $result->getRows(); $i++) {
			$categories[$result->getValue("priority")] = $result->getValue("category_id");
			$result->next();
		}
		array_splice($categories, ($this->priority - 1), 0, [$this->category_id]);

		// Save all prios
		foreach($categories as $prio => $category_id) {
			$query = "UPDATE ". \rex::getTablePrefix() ."d2u_courses_categories "
					."SET priority = ". (intval($prio) + 1) ." " // +1 because array_splice recounts at zero
					."WHERE category_id = ". $category_id;
			$result = \rex_sql::factory();
			$result->setQuery($query);
		}
	}
}