import test from 'node:test';
import assert from 'node:assert/strict';
const base=process.env.TEST_URL||'http://127.0.0.1:8088';
test('Startseite, Datenschutz und geschützte Administration',async()=>{
 for(const path of ['/','/legal.php','/admin.php']){
  const r=await fetch(base+path); assert.equal(r.status,200);
  assert.match(r.headers.get('content-security-policy'),/frame-ancestors 'none'/);
  assert.match(r.headers.get('cache-control'),/\bno-store\b/);
  assert.match(r.headers.get('set-cookie'),/HttpOnly/i);
  assert.match(r.headers.get('set-cookie'),/SameSite=Strict/i);
  const html=await r.text(); assert.match(html,/<html lang="de">/);
  if(path==='/admin.php') {assert.match(html,/type="password"/);assert.doesNotMatch(html,/<table/);}
 }
});
test('POST ohne CSRF scheitert für öffentliche und administrative Aktionen',async()=>{
 for(const path of ['/','/admin.php']) {
  const r=await fetch(base+path,{method:'POST',body:new URLSearchParams({action:'purge',confirm:'LOESCHEN',name:'Max Muster',schweinekopf:'1'})});
  assert.equal(r.status,403);
 }
});
test('Geschlossene Anmeldung speichert nichts und maskiert XSS-Eingaben',async()=>{
 const initial=await fetch(base+'/'); const cookie=initial.headers.get('set-cookie').split(';')[0]; const html=await initial.text();
 const token=html.match(/name="csrf" value="([a-f0-9]+)"/)[1];
 const r=await fetch(base+'/',{method:'POST',headers:{cookie},body:new URLSearchParams({csrf:token,name:'<script>alert(1)</script>',schweinekopf:'1'})});
 assert.equal(r.status,200); const result=await r.text();
 assert.match(result,/derzeit geschlossen/); assert.doesNotMatch(result,/<script>alert/); assert.match(result,/&lt;script&gt;/);
});
test('Statistik ohne Konfiguration zeigt keine erfundenen Zahlen',async()=>{
 const r=await fetch(base+'/stats.php?view=1'); assert.equal(r.status,503); assert.deepEqual(await r.json(),{});
});
test('Statische Dateien vorhanden und keine externen Trackingressourcen',async()=>{
 for(const path of ['/style.css','/app.js']) assert.equal((await fetch(base+path)).status,200);
 const html=await (await fetch(base+'/')).text(); assert.doesNotMatch(html,/(?:src|href)="https?:\/\//);
});
test('Genau eine Speise, Code-Pflichtfeld und zweiter Adminfaktor vorhanden',async()=>{
 const html=await (await fetch(base+'/')).text();
 assert.equal((html.match(/type="radio" name="meal"/g)||[]).length,2);
 assert.doesNotMatch(html,/type="checkbox"/);
 assert.match(html,/id="invitation"[^>]*required/);
 const admin=await (await fetch(base+'/admin.php')).text(); assert.match(admin,/id="otp"/);
});
test('JSON-API ohne Sitzungsschutz gesperrt, keine Einwohnerdaten',async()=>{
 assert.equal((await fetch(base+'/api/registration.php')).status,405);
 const bad=await fetch(base+'/api/registration.php',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'}); assert.equal(bad.status,403);
 const data=await (await fetch(base+'/api/session.php')).json(); assert.equal(data.registration_open,false); assert.match(data.csrf,/^[a-f0-9]{64}$/); assert.deepEqual(Object.keys(data).sort(),['csrf','registration_open']);
});
