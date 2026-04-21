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
            'D2U Veranstaltungen - Ausgabe Veranstaltungen (BS4, deprecated)',
            17);
        $modules[] = new \TobiasKrais\D2UHelper\Module('26-2',
            'D2U Veranstaltungen - Warenkorb (BS4, deprecated)',
            11);
        $modules[] = new \TobiasKrais\D2UHelper\Module('26-3',
            'D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen (BS4, deprecated)',
            5);
        $modules[] = new \TobiasKrais\D2UHelper\Module('26-4',
            'D2U Veranstaltungen - Ausgabe Veranstaltungen (BS5)',
            1);
        $modules[] = new \TobiasKrais\D2UHelper\Module('26-5',
            'D2U Veranstaltungen - Warenkorb (BS5)',
            1);
        $modules[] = new \TobiasKrais\D2UHelper\Module('26-6',
            'D2U Veranstaltungen - Ausgabe Veranstaltungen einer Kategorie in Boxen (BS5)',
            1);
        return $modules;
    }
}
