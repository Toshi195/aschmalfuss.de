<?php
require dirname(__DIR__,2).'/src/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
function api_result(int $status,string $message): never { http_response_code($status); echo json_encode(['message'=>$message],JSON_THROW_ON_ERROR); exit; }
if($_SERVER['REQUEST_METHOD']!=='POST') { header('Allow: POST'); api_result(405,'Methode nicht erlaubt.'); }
if(!is_string($_SERVER['HTTP_X_CSRF_TOKEN']??null) || !hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN'])) api_result(403,'Bitte Seite neu laden.');
if(strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]))!=='application/json') api_result(415,'JSON erwartet.');
if(!registration_open()) api_result(409,'Die Anmeldung ist derzeit geschlossen.');
if(!limit_attempts('register',8)) api_result(429,'Bitte versuche es in 15 Minuten erneut.');
$raw=file_get_contents('php://input',false,null,0,4097);
if(strlen($raw)>4096) api_result(413,'Anfrage zu groß.');
try { $body=json_decode($raw,true,16,JSON_THROW_ON_ERROR); } catch(JsonException) { api_result(400,'Ungültige Anfrage.'); }
if(!is_array($body) || !is_string($body['name']??null) || !is_string($body['invitation']??null) || !meal_flags($body['meal']??null)) api_result(422,'Bitte Name, Einladungscode und genau ein Gericht eingeben.');
$start=microtime(true); $saved=false;
if(empty($body['website']) && time()-$_SESSION['started']>=2) $saved=save_registration($body['name'],$body['invitation'],$body['meal']);
usleep(max(0,(int)((.35-(microtime(true)-$start))*1000000)));
if(!$saved) api_result(422,'Die Anmeldung war nicht möglich. Bitte prüfe Name und Einladungscode oder wende dich an André.');
api_result(200,'Dein Essenswunsch wurde gespeichert.');
