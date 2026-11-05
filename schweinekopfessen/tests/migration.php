<?php
require dirname(__DIR__).'/src/bootstrap.php';
$dsn=getenv('TEST_MIGRATION_DSN') ?: ''; if(!$dsn) throw new RuntimeException('TEST_MIGRATION_DSN fehlt.');
$connection=new PDO($dsn,getenv('TEST_MYSQL_USER') ?: '',getenv('TEST_MYSQL_PASSWORD') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(!str_ends_with($connection->query('SELECT DATABASE()')->fetchColumn(),'_test') || $connection->query('SHOW TABLES')->fetchColumn()!==false) throw new RuntimeException('Leere _test-Datenbank erforderlich.');
foreach(explode(';',file_get_contents(__DIR__.'/fixtures/schema-v1.sql')) as $sql) if(trim($sql)) $connection->exec($sql);
$connection->exec("INSERT INTO residents (first_name,last_name,normalized_name) VALUES ('Max','Muster','max muster')");
$connection->exec('INSERT INTO registrations (resident_id,wants_schweinekopf,wants_eisbein) VALUES (1,1,1)');
$migration=preg_replace('/^\s*--.*$/m','',file_get_contents(dirname(__DIR__).'/database/migrations/002_invitation_codes.sql'));
$queries=array_filter(array_map('trim',explode(';',$migration)));
try { $connection->exec(reset($queries)); throw new RuntimeException('Altbestand mit zwei Gerichten wurde ungeprüft migriert.'); } catch(PDOException) { echo "Migrationssperre bei unbereinigtem Altbestand BESTANDEN.\n"; }
$connection->exec('UPDATE registrations SET wants_eisbein=0');
foreach($queries as $sql) $connection->exec($sql);
if((int)$connection->query('SELECT COUNT(*) FROM registrations')->fetchColumn()!==1 || $connection->query('SELECT invitation_hash FROM residents WHERE id=1')->fetchColumn()!==null || (int)$connection->query('SELECT registration_enabled FROM app_settings WHERE id=1')->fetchColumn()!==0) throw new RuntimeException('Migration verlor Daten oder öffnete Anmeldung.');
echo "Migration Version 1 → 2 mit Datenerhalt BESTANDEN.\n";
