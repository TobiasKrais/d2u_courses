<?php
namespace TobiasKrais\D2UCourses;

/**
 * Administrates background import cronjob for Kufer Sync.
 */
class KuferSyncCronjob extends \TobiasKrais\D2UHelper\ACronJob
{
    /**
     * Create a new instance of object.
     * @return self CronJob object
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
        $php_code = '<?php \\\\\\\\TobiasKrais\\\\\\\\D2UCourses\\\\\\\\KuferSync::sync(); ?>';
        $interval = '{\"minutes\":[0],\"hours\":[21],\"days\":\"all\",\"weekdays\":\"all\",\"months\":\"all\"}';
        self::save($description, $php_code, $interval);
    }
}
