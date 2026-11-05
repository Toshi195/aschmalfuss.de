<?php
require dirname(__DIR__,2).'/src/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405); header('Allow: GET'); echo '{"error":"Methode nicht erlaubt."}'; exit; }
echo json_encode(['csrf'=>$_SESSION['csrf'],'registration_open'=>registration_open()],JSON_THROW_ON_ERROR);
