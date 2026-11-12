CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visitor_stats (
    id TINYINT UNSIGNED PRIMARY KEY,
    total_visits BIGINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO visitor_stats (id, total_visits) VALUES (1, 0);

CREATE TABLE visitor_daily (
    visit_date DATE PRIMARY KEY,
    visits BIGINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opponent VARCHAR(120) NOT NULL,
    opponent_logo VARCHAR(500) NOT NULL DEFAULT '',
    psim_home TINYINT(1) NOT NULL DEFAULT 1,
    competition VARCHAR(120) NOT NULL DEFAULT 'BRI Super League 2026/27',
    kickoff_at DATETIME NULL,
    venue VARCHAR(160) NOT NULL,
    location VARCHAR(160) NOT NULL,
    status ENUM('upcoming', 'completed') NOT NULL DEFAULT 'upcoming',
    source_url VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX matches_kickoff_status (kickoff_at, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO matches (opponent, opponent_logo, kickoff_at, venue, location, status, source_url)
VALUES (
    'Persik Kediri',
    'https://assets.ileague.id/uploads/images/logo/95/v/150/37.png',
    '2026-11-21 00:00:00',
    'Stadion Brawijaya',
    'Kediri, Jawa Timur',
    'upcoming',
    'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2026-11-21/PERSIK_KEDIRI/PSIM_YOGYAKARTA'
);

UPDATE matches SET psim_home = 0, kickoff_at = '2026-11-21 15:30:00' WHERE opponent = 'Persik Kediri';

INSERT INTO matches
    (opponent, opponent_logo, psim_home, competition, kickoff_at, venue, location, status, source_url)
VALUES
    ('Persiku Kudus', 'https://assets.ileague.id/uploads/images/logo/102/h/50/49.png', 0, 'I.League Cup 2026/27', '2026-11-25 16:00:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/I.LEAGUE_CUP_2026-2027/2026-11-25/PERSIKU_KUDUS/PSIM_YOGYAKARTA'),
    ('Persijap Jepara', 'https://assets.ileague.id/uploads/images/logo/95/h/50/21.png', 1, 'BRI Super League 2026/27', '2026-11-28 15:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2026-11-28/PSIM_YOGYAKARTA/PERSIJAP_JEPARA'),
    ('Persikad', 'https://assets.ileague.id/uploads/images/logo/102/h/50/2208194.png', 1, 'I.League Cup 2026/27', '2026-12-02 19:00:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/I.LEAGUE_CUP_2026-2027/2026-12-02/PSIM_YOGYAKARTA/PERSIKAD'),
    ('Java United FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/1819851379.png', 0, 'BRI Super League 2026/27', '2026-12-05 19:00:00', 'Stadion Jatidiri', 'Semarang, Jawa Tengah', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2026-12-05/JAVA_UNITED_FC/PSIM_YOGYAKARTA'),
    ('PSS Sleman', 'https://assets.ileague.id/uploads/images/logo/95/h/50/23.png', 1, 'BRI Super League 2026/27', '2026-12-10 15:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2026-12-10/PSIM_YOGYAKARTA/PSS_SLEMAN_'),
    ('Arema FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/32.png', 1, 'BRI Super League 2026/27', '2026-12-14 15:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2026-12-14/PSIM_YOGYAKARTA/AREMA_FC'),
    ('Bali United FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/71.png', 0, 'BRI Super League 2026/27', '2026-12-18 19:00:00', 'Stadion Kapten I Wayan Dipta', 'Gianyar, Bali', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2026-12-18/BALI_UNITED_FC/PSIM_YOGYAKARTA'),
    ('Garudayaksa FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/1814914856.png', 1, 'BRI Super League 2026/27', '2026-12-22 15:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2026-12-22/PSIM_YOGYAKARTA/GARUDAYAKSA_FC'),
    ('Persib Bandung', 'https://assets.ileague.id/uploads/images/logo/95/h/50/11.png', 0, 'BRI Super League 2026/27', '2026-12-27 19:00:00', 'Stadion Gelora Bandung Lautan Api', 'Bandung, Jawa Barat', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2026-12-27/PERSIB_BANDUNG/PSIM_YOGYAKARTA'),
    ('Persija Jakarta', 'https://assets.ileague.id/uploads/images/logo/95/h/50/12.png', 0, 'BRI Super League 2026/27', '2027-02-08 20:30:00', 'Stadion Gelora Bung Karno', 'Jakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-02-08/PERSIJA_JAKARTA/PSIM_YOGYAKARTA'),
    ('Persebaya Surabaya', 'https://assets.ileague.id/uploads/images/logo/95/h/50/280.png', 1, 'BRI Super League 2026/27', '2027-02-16 20:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-02-16/PSIM_YOGYAKARTA/PERSEBAYA_SURABAYA'),
    ('PSS Sleman', 'https://assets.ileague.id/uploads/images/logo/95/h/50/23.png', 0, 'BRI Super League 2026/27', '2027-02-20 20:30:00', 'Stadion Maguwoharjo', 'Sleman, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-02-20/PSS_SLEMAN_/PSIM_YOGYAKARTA'),
    ('Madura United FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/40.png', 1, 'BRI Super League 2026/27', '2027-02-25 20:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-02-25/PSIM_YOGYAKARTA/MADURA_UNITED_FC'),
    ('PSM Makassar', 'https://assets.ileague.id/uploads/images/logo/95/h/50/50.png', 0, 'BRI Super League 2026/27', '2027-03-03 20:30:00', 'Stadion Gelora B. J. Habibie', 'Parepare, Sulawesi Selatan', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-03-03/PSM_MAKASSAR/PSIM_YOGYAKARTA'),
    ('Bhayangkara Lampung FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/30.png', 1, 'BRI Super League 2026/27', '2027-03-19 15:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-03-19/PSIM_YOGYAKARTA/BHAYANGKARA_LAMPUNG_FC'),
    ('Persib Bandung', 'https://assets.ileague.id/uploads/images/logo/95/h/50/11.png', 1, 'BRI Super League 2026/27', '2027-04-03 19:00:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-04-03/PSIM_YOGYAKARTA/PERSIB_BANDUNG'),
    ('Persijap Jepara', 'https://assets.ileague.id/uploads/images/logo/95/h/50/21.png', 0, 'BRI Super League 2026/27', '2027-04-09 15:30:00', 'Stadion Gelora Bumi Kartini', 'Jepara, Jawa Tengah', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-04-09/PERSIJAP_JEPARA/PSIM_YOGYAKARTA'),
    ('Dewa United Banten FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/209.png', 0, 'BRI Super League 2026/27', '2027-04-16 15:30:00', 'Stadion Banten International', 'Serang, Banten', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-04-16/DEWA_UNITED_BANTEN_FC/PSIM_YOGYAKARTA'),
    ('Bali United FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/71.png', 1, 'BRI Super League 2026/27', '2027-04-25 19:00:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-04-25/PSIM_YOGYAKARTA/BALI_UNITED_FC'),
    ('Persita', 'https://assets.ileague.id/uploads/images/logo/95/h/50/15.png', 0, 'BRI Super League 2026/27', '2027-04-29 15:30:00', 'Stadion Indomilk Arena', 'Tangerang, Banten', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-04-29/PERSITA/PSIM_YOGYAKARTA'),
    ('Persik Kediri', 'https://assets.ileague.id/uploads/images/logo/95/h/50/37.png', 1, 'BRI Super League 2026/27', '2027-05-03 15:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-05-03/PSIM_YOGYAKARTA/PERSIK_KEDIRI'),
    ('Borneo FC Samarinda', 'https://assets.ileague.id/uploads/images/logo/95/h/50/192.png', 0, 'BRI Super League 2026/27', '2027-05-10 19:00:00', 'Stadion Segiri', 'Samarinda, Kalimantan Timur', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-05-10/BORNEO_FC_SAMARINDA/PSIM_YOGYAKARTA'),
    ('Arema FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/32.png', 0, 'BRI Super League 2026/27', '2027-05-14 15:30:00', 'Stadion Kanjuruhan', 'Malang, Jawa Timur', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-05-14/AREMA_FC/PSIM_YOGYAKARTA'),
    ('Isenmulang Kalteng FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/2409755.png', 1, 'BRI Super League 2026/27', '2027-05-23 15:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-05-23/PSIM_YOGYAKARTA/ISENMULANG_KALTENG_FC'),
    ('Java United FC', 'https://assets.ileague.id/uploads/images/logo/95/h/50/1819851379.png', 1, 'BRI Super League 2026/27', '2027-05-29 15:30:00', 'Stadion Sultan Agung', 'Bantul, DI Yogyakarta', 'upcoming', 'https://ileague.id/result/detail/BRI_SUPER_LEAGUE_2026-27/2027-05-29/PSIM_YOGYAKARTA/JAVA_UNITED_FC');

CREATE TABLE comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    category ENUM('Saran', 'Kritik', 'Komentar') NOT NULL,
    body VARCHAR(1000) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX comments_created (created_at),
    INDEX comments_ip_created (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ratings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_hash CHAR(64) NOT NULL UNIQUE,
    ip_hash CHAR(64) NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ratings_score_range CHECK (score BETWEEN 1 AND 5),
    INDEX ratings_created (created_at),
    INDEX ratings_ip_created (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
