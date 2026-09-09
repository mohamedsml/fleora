-- Exécuté une seule fois, à la création du volume de la base.
-- Les tests tournent sur MariaDB (pas SQLite) pour attraper les erreurs de
-- typage et de verrouillage que SQLite laisse passer silencieusement.
CREATE DATABASE IF NOT EXISTS fleora_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
