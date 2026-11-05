-- Einmal auf dem vorhandenen Schema anwenden, vorher Backup erstellen.
-- Vorprüfung: SELECT * FROM registrations WHERE wants_schweinekopf + wants_eisbein <> 1;
-- Falls Zeilen erscheinen: persönlich klären und je Person genau ein Gericht setzen.
-- Die CHECK-Erweiterung bricht bei unbereinigten Daten bewusst ab.
ALTER TABLE registrations ADD CONSTRAINT one_meal CHECK (wants_schweinekopf IN (0,1) AND wants_eisbein IN (0,1) AND wants_schweinekopf + wants_eisbein = 1);
ALTER TABLE residents
 ADD COLUMN invitation_hash CHAR(64) COLLATE ascii_bin NULL UNIQUE,
 ADD COLUMN invitation_revoked BOOLEAN NOT NULL DEFAULT 0;
CREATE TABLE app_settings (id TINYINT PRIMARY KEY, registration_enabled BOOLEAN NOT NULL DEFAULT 0) ENGINE=InnoDB;
INSERT INTO app_settings (id,registration_enabled) VALUES (1,0);
CREATE TABLE admin_auth_state (actor_reference VARCHAR(80) PRIMARY KEY, last_totp_step BIGINT NOT NULL DEFAULT -1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
