<?php

if (\rex::isBackend() && is_object(\rex::getUser())) {
    rex_perm::register('d2u_courses[kufer_sync]', rex_i18n::msg('d2u_courses_kufer_sync_rights_kufer_sync'), rex_perm::OPTIONS);
}
