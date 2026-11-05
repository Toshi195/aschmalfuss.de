<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') exit(1);
$root=dirname(__DIR__);
// Ausschließlich die öffentliche Beispielkonfiguration verwenden. Niemals Echtzugänge rendern.
putenv('SKE_CONFIG_FILE='.$root.'/config.example.php');
$_SERVER['REQUEST_METHOD']='GET'; $_POST=[]; $_GET=[];
ob_start(); require $root.'/public/index.php'; $html=ob_get_clean();
$html=preg_replace('~<script\b[^>]*>.*?</script>~s','',$html);
$html=preg_replace('~<input[^>]*type="hidden"[^>]*>~','',$html);
$html=str_replace(['/assets/'],['../public/assets/'],$html);
$html=str_replace('href="/style.css"','href="../public/style.css"',$html);
$html=str_replace('href="/legal.php','href="rechtliches.html',$html);
$html=str_replace('href="/"','href="../../index.html"',$html);
$html=preg_replace('~<a href="/admin.php">.*?</a>~','',$html);
$html=preg_replace('~<form\b[^>]*>~','<div class="preview-form">',$html);
$html=str_replace('</form>','</div>',$html);
$html=preg_replace('~<input\b~','<input disabled',$html);
$html=preg_replace('~<section class="stats".*?</section>~s','<p class="preview-note">In dieser Vorschau werden keine Anmeldungen oder Besucherzahlen gespeichert.</p>',$html);
$html=str_replace('<body>','<body><aside class="preview-banner">Vorschau · Die Online-Anmeldung ist noch nicht geöffnet.</aside>',$html);
$html=str_replace('<title>','<meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="preview.css"><title>Vorschau · ',$html);
$target=$root.'/vorschau'; if(!is_dir($target)) mkdir($target,0755,true);
file_put_contents($target.'/index.html',$html);
ob_start(); require $root.'/public/legal.php'; $legal=ob_get_clean();
$legal=str_replace(['href="/style.css"','href="/"'],['href="../public/style.css"','href="index.html"'],$legal);
$legal=str_replace('<body>','<body><aside class="preview-banner">Entwurf · Betreiberangaben müssen vor der echten Anmeldung vervollständigt werden.</aside>',$legal);
$legal=str_replace('<title>','<meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="preview.css"><title>Vorschau · ',$legal);
file_put_contents($target.'/rechtliches.html',$legal);
echo "Statische Vorschau ohne Formulare, Sitzungstoken, Skripte und Backendaufrufe erstellt.\n";
