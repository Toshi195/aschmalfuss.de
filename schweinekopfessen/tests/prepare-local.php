<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') exit(1);
$dsn=getenv('TEST_MYSQL_DSN') ?: '';
if(!$dsn) throw new RuntimeException('TEST_MYSQL_DSN fehlt.');
$connection=new PDO($dsn,getenv('TEST_MYSQL_USER') ?: '',getenv('TEST_MYSQL_PASSWORD') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(!str_ends_with($connection->query('SELECT DATABASE()')->fetchColumn(),'_test') || $connection->query('SHOW TABLES')->fetchColumn()!==false) throw new RuntimeException('Nur leere Datenbank mit Endung _test erlaubt.');
$root=dirname(__DIR__).'/tools/test-data'; if(!is_dir($root)) mkdir($root,0700,true);
if(file_exists($root.'/test.config.php')) throw new RuntimeException('Testkonfiguration existiert bereits.');
$config=require dirname(__DIR__).'/config.example.php';
$password=bin2hex(random_bytes(24));
$config=array_replace($config,[
 'dsn'=>$dsn,'db_user'=>getenv('TEST_MYSQL_USER') ?: '', 'db_password'=>getenv('TEST_MYSQL_PASSWORD') ?: '',
 'secret'=>bin2hex(random_bytes(32)),'admin_user'=>'test-admin','admin_password_hash'=>password_hash($password,PASSWORD_DEFAULT),
 'admin_totp_secret'=>'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ','registration_open'=>true,'registration_deadline'=>'2099-12-28T23:59:59+01:00',
 'legal_address'=>'Testadresse, keine Echtdaten','legal_email'=>'test@example.org','privacy_basis'=>'Nur künstliche lokale Testdaten','delete_after'=>'2099-12-31',
]);
file_put_contents($root.'/test.config.php',"<?php\nreturn ".var_export($config,true).";\n");
file_put_contents($root.'/access.json',json_encode(['password'=>$password],JSON_THROW_ON_ERROR));
echo "Isolierte Testkonfiguration erstellt; keine Zugangsdaten ausgegeben.\n";
