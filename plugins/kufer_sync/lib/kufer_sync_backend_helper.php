<?php
/**
 * Offers helper functions for export plugin
 */
class kufer_sync_backend_helper  {
	/**
	 * @var string Name of CronJob
	 */
	 static $CRONJOB_NAME = "D2U Courses Kufer Sync";
	
	/**
	 * Deactivate autoexport.
	 */
	public static function autoimportDelete() {
		if(\rex_addon::get('cronjob')->isAvailable()) {
			$query = "DELETE FROM `". \rex::getTablePrefix() ."cronjob` WHERE `name` = '". kufer_sync_backend_helper::$CRONJOB_NAME ."'";
			$sql = \rex_sql::factory();
			$sql->setQuery($query);
		}
	}

	/**
	 * Activate autoexport.
	 */
	public static function autoimportInstall() {
		if(\rex_addon::get('cronjob')->isAvailable()) {
			$query = "INSERT INTO `". \rex::getTablePrefix() ."cronjob` (`name`, `description`, `type`, `parameters`, `interval`, `nexttime`, `environment`, `execution_moment`, `execution_start`, `status`, `createdate`, `createuser`) VALUES "
				."('". kufer_sync_backend_helper::$CRONJOB_NAME ."', 'Imports Kufer XML', 'rex_cronjob_phpcode', '{\"rex_cronjob_phpcode_code\":\"<?php KuferSync::sync(); ?>\"}', '{\"minutes\":[0],\"hours\":[21],\"days\":\"all\",\"weekdays\":\"all\",\"months\":\"all\"}', '". date("Y-m-d H:i:s", strtotime("+5 min")) ."', '|frontend|backend|', 0, '1970-01-01 01:00:00', 1, '". date("Y-m-d H:i:s") ."', 'd2u_courses');";
			$sql = \rex_sql::factory();
			$sql->setQuery($query);
		}
	}

	/**
	 * Checks if autoexport is installed.
	 * @return boolean TRUE if Cronjob is installed, otherwise false.
	 */
	public static function autoimportIsInstalled() {
		if(\rex_addon::get('cronjob')->isAvailable()) {
			$query = "SELECT `name` FROM `". \rex::getTablePrefix() ."cronjob` WHERE `name` = '". kufer_sync_backend_helper::$CRONJOB_NAME ."'";
			$sql = \rex_sql::factory();
			$sql->setQuery($query);
			if($sql->getRows() > 0) {
				return TRUE;
			}
			else {
				return FALSE;
			}
		}
	}
}