<?php
/**
 * Redaxo D2U Course Addon
 * @author Tobias Krais
 * @author <a href="http://www.design-to-use.de">www.design-to-use.de</a>
 */

namespace D2U_Courses;

/**
 * Target groups class
 */
class TargetGroup {
	/**
	 * @var int ID
	 */
	var $target_group_id = 0;
	
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
	 * @var TargetGroup Parent TargetGroup
	 */
	var $parent_target_group = FALSE;
	
	/**
	 * @var string KuferSQL target group name
	 */
	var $kufer_target_group_name = "";
	
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
	 * Constructor
	 * @param int $target_group_id ID
	 */
	public function __construct($target_group_id) {
		$query = "SELECT * FROM ". \rex::getTablePrefix() ."d2u_courses_target_groups "
				."WHERE target_group_id = ". $target_group_id;
		$result = \rex_sql::factory();
		$result->setQuery($query);

		if($result->getRows() > 0) {
			$this->target_group_id = $result->getValue("target_group_id");
			$this->name = $result->getValue("name");
			$this->picture = $result->getValue("picture");
			$this->priority = $result->getValue("priority");
			$this->kufer_target_group_name = $result->getValue("kufer_target_group_name");
			$this->kufer_categories = array_map('trim', preg_grep('/^\s*$/s', explode(PHP_EOL, $result->getValue("kufer_categories")), PREG_GREP_INVERT));
			$this->updatedate = $result->getValue("updatedate");
		}
	}
	
	/**
	 * Delete object in database. Only delete target groups without parent target group id,
	 * because children are fake target groups. In fact children are category
	 * objects, thus they cannot be deleted as target group objects.
	 * @return boolean TRUE if successful, otherwise FALSE.
	 */
	public function delete() {
		// Do not delete children, because they are fake objects
		if($this->parent_target_group === FALSE && $this->target_group_id > 0) {
			$result = \rex_sql::factory();

			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_target_groups '
					.'WHERE target_group_id = '. $this->target_group_id;
			$result->setQuery($query);

			$return = ($result->hasError() ? FALSE : TRUE);

			$query = 'DELETE FROM '. \rex::getTablePrefix() .'d2u_courses_2_target_groups '
					.'WHERE target_group_id = '. $this->target_group_id;
			$result->setQuery($query);

			// reset priorities
			$this->setPriority(TRUE);
			
			return $return;
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Get all target groups.
	 * @param boolean $online_only If true only online categories are returned
	 * @return TargetGroup[] Array with target group IDs
	 */
	static public function getAll($online_only = TRUE, $parent_target_group_id = 0) {
		$query = "SELECT target_group_id FROM ". \rex::getTablePrefix() ."d2u_courses_target_groups ";
		if($online_only) {
			$query = "SELECT target_group_id FROM ". \rex::getTablePrefix() ."d2u_courses_url_target_groups ";
		}
		if(\rex_addon::get('d2u_courses')->getConfig('default_category_sort', 'name') == 'priority') {
			$query .= 'ORDER BY priority';
		}
		else {
			$query .= 'ORDER BY name';
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);

		$target_groups = [];
		for($i = 0; $i < $result->getRows(); $i++) {
			$target_groups[] = new TargetGroup($result->getValue("target_group_id"));
			$result->next();
		}

		return $target_groups;
	}
	
	/**
	 * Get by target_group_child_id. target_group_child_id format is
	 * target_group_id, category_id, devider is "-"
	 * @param string target_group_child_id ID
	 */
	public static function getByChildID($target_group_child_id) {
		// Separator is ugly, but needs to consist of digits only
		$target_group_id = substr($target_group_child_id, 0, strrpos($target_group_child_id, "00000"));
		$category_id = substr($target_group_child_id, strrpos($target_group_child_id, "00000") + 5);

		$target_child = new TargetGroup(0);
		$target_child->parent_target_group = new TargetGroup($target_group_id);
		$target_child->target_group_id = $category_id;
		$category = new Category($category_id);
		$target_child->name = $category->name;
		$target_child->picture = $category->picture;
		$target_child->updatedate = $category->updatedate;
		return $target_child;
	}

	/**
	 * Get the <link rel="canonical"> tag for page header.
	 * @return Complete tag.
	 */
	public function getCanonicalTag() {
		return '<link rel="canonical" href="'. $this->getURL() .'">';
	}

	/**
	 * Get child courses for this object. If target group is already a child, it
	 * can not have children and an empty array is returned.
	 * @return TargetGroup[] Array with target groups
	 */
	public function getChildren() {
		if($this->parent_target_group != FALSE) {
			return [];
		}
		
		// Only target groups without parent can have children
		$query = "SELECT category_id FROM ". \rex::getTablePrefix() ."d2u_courses_url_target_group_childs  "
				."WHERE target_group_id = ". $this->target_group_id ." ";
		if(\rex_config::get('d2u_courses', 'default_category_sort') == 'priority') {
			$query .= 'ORDER BY priority';
		}
		else {
			$query .= 'ORDER BY name';
		}
		$result = \rex_sql::factory();
		$result->setQuery($query);

		$target_children = [];
		for($i = 0; $i < $result->getRows(); $i++) {
			$category = new Category($result->getValue("category_id"));
			$target_child = new TargetGroup(0);
			$target_child->target_group_id = $category->category_id;
			$target_child->parent_target_group = $this;
			$target_child->name = $category->name;
			$target_child->picture= $category->picture;
			$target_child->updatedate = $category->updatedate;
			$target_children[] = $target_child;
			$result->next();
		}
		return $target_children;
	}

	/**
	 * Get courses for this object
	 * @param boolean $online_only TRUE, if only online courses are returned.
	 * @return Course[] Array with Course objects
	 */
	public function getCourses($online_only = FALSE) {
		$query = "";
		if($this->parent_target_group === FALSE) {
			// If object is NOT a child
			$query = "SELECT courses.course_id FROM ". \rex::getTablePrefix() ."d2u_courses_2_target_groups AS c2t "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ON c2t.course_id = courses.course_id "
				."WHERE c2t.target_group_id = ". $this->target_group_id ." ";
		}
		else {
			// If object IS a child
			$query = "SELECT courses.course_id FROM ". \rex::getTablePrefix() ."d2u_courses_2_target_groups AS c2t "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ON c2t.course_id = courses.course_id "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_2_categories AS c2c ON c2c.course_id = courses.course_id "
				."WHERE c2t.target_group_id = ". $this->parent_target_group->target_group_id ." "
					."AND c2c.category_id = ". $this->target_group_id ." ";
		}
		if($online_only) {
			$query .= "AND online_status = 'online' "
				."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") ";
		}
		$query .= "GROUP BY course_id "
			."ORDER BY date_start, name";
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
	 * Get the <meta rel="alternate" hreflang=""> tags for page header.
	 * @return Complete tags.
	 */
	public function getMetaAlternateHreflangTags() {
		return '<link rel="alternate" type="text/html" hreflang="'. \rex_clang::getCurrent()->getCode() .'" href="'. $this->getURL() .'" title="'. str_replace('"', '', ($this->parent_target_group !== FALSE ? $this->parent_target_group->name .': ' : ''). $this->name) .'">';
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
		return '<title>'. $this->name .' / '. ($this->parent_target_group !== FALSE ? $this->parent_target_group->name .' / ' : ''). \rex::getServerName() .'</title>';
	}

	/**
	 * Is target group online? It is online if there are online courses in it
	 * that start in future.
	 * @return boolean TRUE, if online, FALSE if offline
	 */
	public function isOnline() {
		$query = "SELECT courses.course_id FROM ". \rex::getTablePrefix() ."d2u_courses_2_target_groups AS c2t "
				."LEFT JOIN ". \rex::getTablePrefix() ."d2u_courses_courses AS courses ON c2t.course_id = courses.course_id"
				."WHERE c2t.target_group_id = ". $this->target_group_id ." "
					."AND online_status = 'online' "
					."AND (". \d2u_courses_frontend_helper::getShowTimeWhere() .") ";
		$result = \rex_sql::factory();
		$result->setQuery($query);

		return $result->getRows() > 0 ? TRUE : FALSE;
	}

	/**
	 * Returns the URL of this object.
	 * @param string $including_domain TRUE if Domain name should be included
	 * @return string URL
	 */
	public function getURL($including_domain = FALSE) {
		if($this->url == "") {
			$parameterArray = [];
			if($this->parent_target_group !== FALSE) {
				// Separator is ugly, but needs to consist of digits only
				$parameterArray['target_group_child_id'] = $this->parent_target_group->target_group_id ."00000". $this->target_group_id;
			}
			else {
				$parameterArray['target_group_id'] = $this->target_group_id;
			}
			$this->url = \rex_getUrl(\rex_config::get('d2u_courses', 'article_id_target_groups'), '', $parameterArray, "&");
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
	 * Save object. Only save target groups without parent target group id,
	 * because children are fake target groups. In fact children are category
	 * objects, thus they cannot be saved as target group objects.
	 * @return boolean TRUE if successful.
	 */
	public function save() {
		// Do not save children, because they are fake objects
		if($this->parent_target_group === FALSE) {
			$pre_save_object = new self($this->course_id);

			$query = "INSERT INTO ";
			if($this->target_group_id > 0) {
				$query = "UPDATE ";
			}
			$query .= \rex::getTablePrefix().'d2u_courses_target_groups SET '
				.'`name` = "'. addslashes($this->name) .'", '
				.'picture = "'. $this->picture .'", '
				.'updatedate = CURRENT_TIMESTAMP ';
			if(\rex_plugin::get('d2u_courses', 'kufer_sync')->isAvailable()) {
				$query .= ', kufer_categories = "'. implode(PHP_EOL, $this->kufer_categories) .'"'
					.', kufer_target_group_name = "'. $this->kufer_target_group_name .'"';
			}
			if($this->target_group_id > 0) {			
				$query .= " WHERE target_group_id = ". $this->target_group_id;
			}
			$result = \rex_sql::factory();
			$result->setQuery($query);

			if($this->target_group_id == 0) {
				$this->target_group_id = $result->getLastId();
			}

			if($this->priority != $pre_save_category->priority) {
				$this->setPriority();
			}
			
			if(!$result->hasError() && $pre_save_object->name != $this->name) {
				\d2u_addon_backend_helper::generateUrlCache('target_group_id');
				\d2u_addon_backend_helper::generateUrlCache('target_group_child_id');
			}
		
			return !$result->hasError();
		}
		else {
			return FALSE;
		}
	}
	
	/**
	 * Reassigns priority in database.
	 * @param boolean $delete Reorder priority after deletion
	 */
	private function setPriority($delete = FALSE) {
		// Pull prios from database
		$query = "SELECT target_group_id, priority FROM ". \rex::getTablePrefix() ."d2u_courses_target_groups "
			."WHERE target_group_id <> ". $this->target_group_id ." ORDER BY priority";
		$result = \rex_sql::factory();
		$result->setQuery($query);
		
		// When priority is too small, set at beginning
		if($this->priority <= 0) {
			$this->priority = 1;
		}
		
		// When prio is too high or was deleted, simply add at end 
		if($this->priority > $result->getRows() || $delete) {
			$this->priority = $result->getRows() + 1;
		}

		$target_groups = [];
		for($i = 0; $i < $result->getRows(); $i++) {
			$target_groups[$result->getValue("priority")] = $result->getValue("target_group_id");
			$result->next();
		}
		array_splice($target_groups, ($this->priority - 1), 0, [$this->target_group_id]);

		// Save all prios
		foreach($target_groups as $prio => $target_group_id) {
			$query = "UPDATE ". \rex::getTablePrefix() ."d2u_courses_target_groups "
					."SET priority = ". ($prio + 1) ." " // +1 because array_splice recounts at zero
					."WHERE target_group_id = ". $target_group_id;
			$result = \rex_sql::factory();
			$result->setQuery($query);
		}
	}
}