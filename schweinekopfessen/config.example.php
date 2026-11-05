<?php
return [
 'dsn' => 'mysql:host=localhost;dbname=schweinekopfessen;charset=utf8mb4',
 'db_user' => '', 'db_password' => '',
 'secret' => '', // Mindestens 32 zufällige Bytes, als Hex-Zeichenfolge.
 'admin_user' => 'andre', 'admin_password_hash' => '',
 'admin_totp_secret' => '', // Base32-Schlüssel aus der Authenticator-Einrichtung, mindestens 32 Zeichen.
 'registration_open' => false, // Betriebssperre; zusätzlich Schalter in der Administration.
 'online_window_minutes' => 5,
 'registration_deadline' => '2026-12-28T23:59:59+01:00',
 'event_details' => '29. Januar 2027 · 19:00 Uhr · Dorfgemeinschaftshaus Kreypau · Anmeldung bis 28. Dezember 2026',
 'legal_address' => '', 'legal_email' => '',
 'privacy_basis' => '', 'delete_after' => '',
];
