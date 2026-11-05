<?php
require dirname(__DIR__).'/src/bootstrap.php';
if(!str_ends_with(query('SELECT DATABASE()')->fetchColumn(),'_test')) throw new RuntimeException('Nur Testdatenbank erlaubt.');
$fixture=json_decode(file_get_contents(dirname(__DIR__).'/tools/test-data/invitations.json'),true,16,JSON_THROW_ON_ERROR);
$processes=[];
foreach(['schweinekopf','eisbein'] as $meal) {
 $command=[PHP_BINARY,'-d','extension_dir='.ini_get('extension_dir'),'-d','extension=intl','-d','extension=mbstring','-d','extension=pdo_mysql',__DIR__.'/concurrency-worker.php'];
 $process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
 if(!is_resource($process)) throw new RuntimeException('Worker konnte nicht gestartet werden.');
 fwrite($pipes[0],json_encode(['name'=>'Max Muster','code'=>$fixture['code'],'meal'=>$meal],JSON_THROW_ON_ERROR)); fclose($pipes[0]);
 $processes[]=[$process,$pipes];
}
foreach($processes as [$process,$pipes]) {
 $result=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $exit=proc_close($process);
 if($exit!==0 || $result!=='OK') throw new RuntimeException('Parallele Anmeldung fehlgeschlagen: '.$error);
}
$rows=query('SELECT * FROM registrations WHERE resident_id=?',[$fixture['id']])->fetchAll();
if(count($rows)!==1 || (int)$rows[0]['wants_schweinekopf']+(int)$rows[0]['wants_eisbein']!==1) throw new RuntimeException('Doppelte oder inkonsistente parallele Anmeldung.');
echo "Parallele Anmeldung/Änderung in zwei PHP-Prozessen BESTANDEN.\n";
