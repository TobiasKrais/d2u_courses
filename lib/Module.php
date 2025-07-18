<?php
namespace TobiasKrais\D2UCourses;

/**
 * Class managing modules published by www.design-to-use.de.
 *
 * @author Tobias Krais
 */
class Module
{
    /**
     * Get modules offered by this addon.
     * @return \TobiasKrais\D2UHelper\Module[] Modules offered by this addon
     */
    public static function getModules()
    {
        $modules = [];
        $modules[] = new \TobiasKrais\D2UHelper\Module('26-1',
            'D2U Veranstaltungen - Ausgabe Veranstaltungen',
            17);
        $modules[] = new \TobiasKrais\D2UHelper\Module('26-2',
            'D2U Veranstaltungen - Warenkorb',
            11);
        $modules[] = new \TobiasKrais\D2UHelper\Module('26-3',
            'D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen',
            5);
        return $modules;
    }
}
