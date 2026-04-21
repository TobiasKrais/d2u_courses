<?php

if (!TobiasKrais\D2UCourses\Extension::guardLegacyPage('locations')) {
    return;
}

echo rex_view::title(rex_i18n::msg('d2u_courses_locations'));

rex_be_controller::includeCurrentPageSubPath();
