<?php
require dirname(__DIR__).'/src/bootstrap.php';
$input=json_decode(stream_get_contents(STDIN),true,16,JSON_THROW_ON_ERROR);
echo save_registration($input['name'],$input['code'],$input['meal']) ? 'OK' : 'FAILED';
