<?php
// Ausschließlich eine NEUE, LEERE Testdatenbank verwenden. Keine Produktionsdaten!
require dirname(__DIR__).'/src/bootstrap.php';
$dsn=getenv('TEST_MYSQL_DSN');
if (!$dsn) { fwrite(STDERR,"Nicht ausgeführt: TEST_MYSQL_DSN fehlt.\n"); exit(2); }
$conn=new PDO($dsn,getenv('TEST_MYSQL_USER') ?: '',getenv('TEST_MYSQL_PASSWORD') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
$database=$conn->query('SELECT DATABASE()')->fetchColumn();
if (!str_ends_with($database,'_test') || $conn->query('SHOW TABLES')->fetchColumn()!==false) throw new RuntimeException('Abbruch: Datenbank muss leer sein und auf _test enden.');
foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/schema.sql')) as $sql) if(trim($sql)) $conn->exec($sql);
$conn->beginTransaction();
$insert=$conn->prepare('INSERT INTO residents (first_name,last_name,normalized_name) VALUES (?,?,?)');
$insert->execute(['Max','Muster',normalize_name('Max Muster')]); $id=$conn->lastInsertId();
$match=$conn->prepare('SELECT id FROM residents WHERE normalized_name=? AND active=1 FOR UPDATE');
$match->execute([normalize_name('  MAX   Muster ')]);
if(count($match->fetchAll())!==1) throw new RuntimeException('Normalisierter Abgleich');
$match->execute(["' OR 1=1 --"]); if($match->fetchAll()) throw new RuntimeException('Injection-Abwehr');
$register=$conn->prepare('INSERT INTO registrations (resident_id,wants_schweinekopf,wants_eisbein) VALUES (?,1,0)');
$register->execute([$id]);
try { $register->execute([$id]); throw new RuntimeException('Doppelanmeldung nicht verhindert'); } catch(PDOException $ex) { if($ex->getCode()!=='23000') throw $ex; }
$conn->exec('DELETE FROM residents WHERE id='.(int)$id);
if((int)$conn->query('SELECT COUNT(*) FROM registrations')->fetchColumn()!==0) throw new RuntimeException('Fremdschlüssel-Kaskade');
$conn->rollBack();
echo "MySQL-Schema, Namensabgleich, Parameterbindung, Einzigartigkeit und Kaskade erfolgreich. Leeres Testschema bleibt erhalten.\n";
