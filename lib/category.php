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
	 * @var Category Parent category.
	 */
	var $parent_category = FALSE;
	
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
	 * @var int Update UNIX timestamp
	 */
	var $updatedate = 0;

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
				$this->parent_category = new Category($result->getValue("parent_category_id"));
			}
			$this->priority = $result->getValue("priority");
			$this->updatedate = $result->getValue("updatedate");
			if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
				$this->kufer_categories = array_map('trim', preg_grep('/^\s*$/s', explode(PHP_EOL, $result->getValue("kufer_categories")), PREG_GREP_INVERT));
			}
		}
	}

	/**
	 * Delete object in database
	 * @return boolean TRUE if successful, otherwise FALSE.
	 */
	public function delete() {
		if($this->category_id > 0) {
			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_categories '
					.'WHERE category_id = '. $this->category_id;
			$result = \rex_sql::factory();
			$result->setQuery($query);

			return ($result->hasError() ? FALSE : TRUE);
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
		$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_2_categories AS c2c "
			."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ON c2c.course_id = courses.course_id "
				."WHERE c2c.category_id = ". $this->category_id ." "
					."AND online_status = 'online' "
					."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") ";
		$result =  \rex_sql::factory();
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
	 * Get all categories
	 * @param boolean $online_only If true only online categories are returned
	 * @param int $parent_category_id Parent category ID if only child categories
	 * should be returned.
	 * @return Category[] Array with category objects.
	 */
	static function getAll($online_only = FALSE, $parent_category_id = 0) {
		$query = "SELECT category_id FROM ". \rex::getTablePrefix() ."d2u_courses_categories ";
		if($parent_category_id > 0) {
			$query .= "WHERE parent_category_id = ". $parent_category_id ." ";
		}
		if($online_only) {
			$query = "SELECT courses.category_id FROM ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ";
			if($parent_category_id > 0) {
				$query .= "LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_categories AS categories "
						."ON courses.category_id = categories.category_id "
					."WHERE parent_category_id = ". $parent_category_id ." "
						."AND online_status = 'online' ";
			}
			else {
				$query .= "WHERE online_status = 'online' ";
			}
			$query .= "AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") "
					."GROUP BY category_id ";
		}
		if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
			$query .= 'ORDER BY priority';
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
			if(!in_array($category->category_id, $ids)) {
				$ids[] = $category->category_id;
				$categories[] = $category;
			}
			$result->next();

			// Add parent categories if no special parent category is selected - parent categores contain no courses
			if($parent_category_id == 0 && $category->parent_category !== FALSE
					&& $category->parent_category->category_id > 0 && !in_array($category->parent_category->category_id, $ids)) {
				$category = new Category($category->parent_category->category_id);
				$categories[] = $category;
				$ids[] = $category->category_id;
			}
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
	 * @return Category[] Array with category objects
	 */
	public function getChildren($online_only = FALSE) {
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
	 * Get all parent categories
	 * @param boolean $online_only If true only online categories are returned
	 * @return Category[] Array with category objects.
	 */
	static function getAllParents($online_only = FALSE) {
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
			$categories[] = new Category($result->getValue("category_id"));
			$result->next();
		}

		return $categories;
	}

	/**
	 * Get all courses orderd by start date
	 * @param boolean $online_only TRUE, if only online courses should be returned,
	 * otherwise FALSE
	 * @return Course[] Array with course objects
	 */
	public function getCourses($online_only = FALSE) {
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
	 * Get the <meta rel="alternate" hreflang=""> tags for page header.
	 * @return Complete tags.
	 */
	public function getMetaAlternateHreflangTags() {
		return '<link rel="alternate" type="text/html" hreflang="'. \rex_clang::getCurrent()->getCode() .'" href="'. $this->getURL() .'" title="'. str_replace('"', '', ($this->parent_category !== FALSE ? $this->parent_category->name .': ' : ''). $this->name) .'">';
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
		return '<title>'. $this->name .' / '. ($this->parent_category !== FALSE ? $this->parent_category->name .' / ' : ''). \rex::getServerName() .'</title>';
	}
	
	/**
	 * Returns the URL of this object.
	 * @param string $including_domain TRUE if Domain name should be included
	 * @return string URL
	 */
	public function getURL($including_domain = FALSE) {
		if($this->url == "") {
			$parameterArray = [];
			$parameterArray['courses_category_id'] = $this->category_id;
			$this->url = \rex_getUrl(\rex_config::get('d2u_courses', 'course_article_id'), '', $parameterArray, "&");
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
		// save priority, but only if new or changed
		$pre_save_category = new Category($this->category_id, $this->clang_id);
		if($this->priority != $pre_save_category->priority || $this->category_id == 0) {
			$this->setPriority();
		}

		$query = "INSERT INTO ";
		if($this->category_id > 0) {
			$query = "UPDATE ";
		}
		$query .= \rex::getTablePrefix().'d2u_courses_categories SET '
			.'`name` = "'. addslashes($this->name) .'", '
			.'description = "'. addslashes($this->description) .'", '
			.'color = "'. $this->color .'", '
			.'picture = "'. $this->picture .'", '
			.'parent_category_id = '. ($this->parent_category !== FALSE ? $this->parent_category->category_id : 0) .', '
			.'updatedate = "'. time() .'"';
		if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
			$query .= ', kufer_categories = "'. implode(PHP_EOL, $this->kufer_categories) .'"';
		}
		if($this->category_id > 0) {			
			$query .= " WHERE category_id = ". $this->category_id;
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);

		if($this->category_id == 0) {
			$this->category_id = $result->getLastId();
		}
		
		return !$result->hasError();
	}
	
	/**
	 * Reassigns priority to all Categories in database.
	 */
	private function setPriority() {
		// Pull prios from database
		$query = "SELECT category_id, priority FROM ". \rex::getTablePrefix() ."d2u_courses_categories "
			."WHERE category_id <> ". $this->category_id ." ORDER BY priority";
		$result = \rex_sql::factory();
		$result->setQuery($query);
		
		// When priority is too small, set at beginning
		if($this->priority <= 0) {
			$this->priority = 1;
		}
		
		// When prio is too high, simply add at end 
		if($this->priority > $result->getRows()) {
			$this->priority = $result->getRows() + 1;
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
					."SET priority = ". ($prio + 1) ." " // +1 because array_splice recounts at zero
					."WHERE category_id = ". $category_id;
			$result = \rex_sql::factory();
			$result->setQuery($query);
		}
	}
}