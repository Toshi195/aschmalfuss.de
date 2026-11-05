<?php
declare(strict_types=1);

function valid_person_name(string $name): bool {
 return mb_strlen($name) <= 200 && (bool)preg_match("/^[\p{L}\p{M}][\p{L}\p{M} .’'\-]*\s[\p{L}\p{M}][\p{L}\p{M} .’'\-]*$/u", trim($name));
}
function valid_name_part(string $name): bool {
 return mb_strlen($name) <= 100 && (bool)preg_match("/^[\p{L}\p{M}][\p{L}\p{M} .’'\-]*$/u", $name);
}
function meal_flags(mixed $meal): ?array {
 return match($meal) { 'schweinekopf'=>[1,0], 'eisbein'=>[0,1], default=>null };
}
function normalize_invitation(string $code): string {
 return strtolower(preg_replace('/[\s-]/', '', $code));
}
function invitation_hash(string $code): string {
 global $config;
 return hash_hmac('sha256', normalize_invitation($code), $config['secret']);
}
function new_invitation(): string { return strtoupper(implode('-',str_split(bin2hex(random_bytes(16)),4))); }
function registration_window_open(array $settings, ?int $now=null): bool {
 $now ??= time();
 if (!$settings['registration_open']) return false;
 $deadline=$settings['registration_deadline'];
 return $deadline==='' || (($end=strtotime($deadline))!==false && $now<=$end);
}
function csv_safe(mixed $value): string {
 $value=(string)$value;
 return preg_match('/^[\s]*[=+@-]|^[\t\r\n]/u',$value) ? "'".$value : $value;
}
function parse_residents_csv(string $csv): array {
 if(strlen($csv)>100000) throw new InvalidArgumentException('Maximal 100 KB pro Import.');
 $rows=[]; $seen=[];
 $stream=fopen('php://temp','r+'); fwrite($stream,preg_replace('/^\xEF\xBB\xBF/','',$csv)); rewind($stream);
 $line=0;
 try {
  while(($parts=fgetcsv($stream,null,';','"',''))!==false) {
   $line++;
   if($parts===[null]) continue;
   if(count($parts)!==2) throw new InvalidArgumentException("Zeile $line: Vorname;Nachname erwartet.");
   [$first,$last]=array_map('trim',$parts);
   if(!valid_name_part($first) || !valid_name_part($last)) throw new InvalidArgumentException("Zeile $line: Name ungültig.");
   $normalized=normalize_name($first.' '.$last);
   if(isset($seen[$normalized])) throw new InvalidArgumentException("Zeile $line: Doppelter Name. Namensgleiche Einwohner bitte einzeln und bewusst anlegen.");
   $seen[$normalized]=true; $rows[]=[$first,$last,$normalized];
  }
 } finally { fclose($stream); }
 if(!$rows) throw new InvalidArgumentException('Die Importliste ist leer.');
 return $rows;
}
function base32_decode_secret(string $encoded): string {
 if(!preg_match('/^[A-Z2-7]{32,128}$/D',$encoded)) throw new InvalidArgumentException('Ungültiger Authenticator-Schlüssel.');
 $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $buffer=0; $bits=0; $result='';
 foreach(str_split($encoded) as $char) {
  $buffer=($buffer<<5)|strpos($alphabet,$char); $bits+=5;
  if($bits>=8) { $bits-=8; $result.=chr(($buffer>>$bits)&255); $buffer &= (1<<$bits)-1; }
 }
 return $result;
}
function totp(string $base32,int $step,int $digits=6): string {
 $hash=hash_hmac('sha1',pack('N2',intdiv($step,4294967296),$step%4294967296),base32_decode_secret($base32),true);
 $offset=ord($hash[19])&15;
 $binary=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;
 return str_pad((string)($binary%(10**$digits)),$digits,'0',STR_PAD_LEFT);
}
function totp_step(string $secret,string $code,?int $now=null): ?int {
 if(!preg_match('/^\d{6}$/D',$code)) return null;
 $current=intdiv($now ?? time(),30);
 foreach([0,-1,1] as $offset) {
  $step=$current+$offset;
  if($step>=0 && hash_equals(totp($secret,$step),$code)) return $step;
 }
 return null;
}
