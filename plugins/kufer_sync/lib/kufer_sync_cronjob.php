<?php
/**
 * Administrates background import cronjob for Kufer Sync.
 */
class kufer_sync_cronjob extends D2U_Helper\ACronJob
{
    /**
     * Create a new instance of object.
     * @return kufer_sync_cronjob CronJob object
     */
    public static function factory()
    {
        $cronjob = new self();
        $cronjob->name = 'D2U Courses Kufer Sync';
        return $cronjob;
    }

    /**
     * Install CronJob. Its also activated.
     */
    public function install(): void
    {
        $description = 'Imports Kufer XML';
        $php_code = '<?php \\\\\\\\D2U_Courses\\\\\\\\KuferSync::sync(); ?>';
        $interval = '{\"minutes\":[0],\"hours\":[21],\"days\":\"all\",\"weekdays\":\"all\",\"months\":\"all\"}';
        self::save($description, $php_code, $interval);
    }
}
