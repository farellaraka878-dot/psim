<?php
declare(strict_types=1);

// Isi nilai langsung untuk hosting, atau set variabel PSIM_DB_* di lingkungan lokal.
define('DB_HOST', getenv('PSIM_DB_HOST') ?: 'localhost');
define('DB_PORT', (int) (getenv('PSIM_DB_PORT') ?: 3306));
define('DB_NAME', getenv('PSIM_DB_NAME') ?: 'nama_database');
define('DB_USER', getenv('PSIM_DB_USER') ?: 'nama_pengguna_database');
define('DB_PASS', getenv('PSIM_DB_PASS') ?: '');

// Untuk hosting, ganti dengan string acak panjang sebelum website dipublikasikan.
define('APP_KEY', getenv('PSIM_APP_KEY') ?: 'GANTI_DENGAN_KUNCI_ACAK_PANJANG_SEBELUM_DIPAKAI');
