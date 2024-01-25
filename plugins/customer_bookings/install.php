<?php

\rex_sql_table::get(\rex::getTable('d2u_courses_customer_bookings'))
    ->ensureColumn(new rex_sql_column('id', 'INT(11) unsigned', false, null, 'auto_increment'))
    ->setPrimaryKey('id')
    ->ensureColumn(new \rex_sql_column('givenName', 'VARCHAR(100)', true))
    ->ensureColumn(new \rex_sql_column('familyName', 'VARCHAR(100)', true))
    ->ensureColumn(new \rex_sql_column('birthDate', 'DATETIME'))
    ->ensureColumn(new \rex_sql_column('gender', 'VARCHAR(50)', true))
    ->ensureColumn(new \rex_sql_column('street', 'VARCHAR(100)', true))
    ->ensureColumn(new \rex_sql_column('zipCode', 'VARCHAR(10)', true))
    ->ensureColumn(new \rex_sql_column('city', 'VARCHAR(100)', true))
    ->ensureColumn(new \rex_sql_column('country', 'VARCHAR(3)', true))
    ->ensureColumn(new \rex_sql_column('pension_insurance_id', 'VARCHAR(50)', true))
    ->ensureColumn(new \rex_sql_column('emergency_number', 'VARCHAR(50)', true))
    ->ensureColumn(new \rex_sql_column('email', 'VARCHAR(100)', true))
    ->ensureColumn(new \rex_sql_column('kids_go_home_alone', 'TINYINT(1)', true))
    ->ensureColumn(new \rex_sql_column('waitlist', 'INT(1)', true))
    ->ensureColumn(new \rex_sql_column('salery_level', 'VARCHAR(100)', true))
    ->ensureColumn(new \rex_sql_column('paid', 'INT(1)', true))
    ->ensureColumn(new \rex_sql_column('nationality', 'VARCHAR(50)', true))
    ->ensureColumn(new \rex_sql_column('nativeLanguage', 'VARCHAR(50)', true))
    ->ensureColumn(new \rex_sql_column('course_id', 'INT(11)', true))
    ->ensureColumn(new \rex_sql_column('remarks', 'TEXT', true))
    ->ensureColumn(new \rex_sql_column('ipAddress', 'VARCHAR(39)', true))
    ->ensureColumn(new \rex_sql_column('bookingDate', 'DATETIME'))
    ->ensure();