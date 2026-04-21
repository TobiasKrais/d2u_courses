<?php

if (!TobiasKrais\D2UCourses\Extension::guardLegacyPage('customer_bookings')) {
    return;
}

echo rex_view::title(rex_i18n::msg('d2u_courses_customer_bookings'));

rex_be_controller::includeCurrentPageSubPath();
