<?php
/**
 * Class managing modules published by www.design-to-use.de.
 *
 * @author Tobias Krais
 */
class D2UCoursesModules
{
    /**
     * Get modules offered by this addon.
     * @return D2UModule[] Modules offered by this addon
     */
    public static function getModules()
    {
        $modules = [];
        $modules[] = new D2UModule('26-1',
            'D2U Veranstaltungen - Ausgabe Veranstaltungen',
            13);
        $modules[] = new D2UModule('26-2',
            'D2U Veranstaltungen - Warenkorb',
            8);
        $modules[] = new D2UModule('26-3',
            'D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen',
            4);
        return $modules;
    }
}
