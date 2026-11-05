import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {createHmac} from 'node:crypto';
const base=process.env.LIVE_TEST_URL||'http://127.0.0.1:8089';
const fixtures=new URL('../tools/test-data/',import.meta.url);
const codes=JSON.parse(await readFile(new URL('invitations.json',fixtures),'utf8'));
const access=JSON.parse(await readFile(new URL('access.json',fixtures),'utf8'));
function otp(){
 const counter=Buffer.alloc(8);counter.writeBigUInt64BE(BigInt(Math.floor(Date.now()/30000)));
 const hmac=createHmac('sha1','12345678901234567890').update(counter).digest();const offset=hmac[19]&15;
 return String((hmac.readUInt32BE(offset)&0x7fffffff)%1000000).padStart(6,'0');
}
async function session(path='/'){
 const r=await fetch(base+path);assert.equal(r.status,200);const html=await r.text();
 return {cookie:r.headers.get('set-cookie').split(';')[0],csrf:html.match(/name="csrf" value="([a-f0-9]+)"/)[1],html};
}
async function post(path,s,data){return fetch(base+path,{method:'POST',redirect:'manual',headers:{cookie:s.cookie},body:new URLSearchParams({csrf:s.csrf,...data})});}
test('Vollständige Einladungscode-Anmeldung und Änderung über HTTP',async()=>{
 const s=await session();await new Promise(resolve=>setTimeout(resolve,2100));
 for(const meal of ['schweinekopf','eisbein']){
  const r=await post('/',s,{name:'Max Muster',invitation:codes.code,meal}); assert.equal(r.status,303);
  const confirm=await (await fetch(base+'/',{headers:{cookie:s.cookie}})).text();assert.match(confirm,/Dein Essenswunsch wurde gespeichert/);
 }
 const bad=await post('/',s,{name:'Erika Beispiel',invitation:codes.code,meal:'eisbein'}); assert.equal(bad.status,200);assert.match(await bad.text(),/Bitte prüfe Name und Einladungscode/);
 const both=await post('/',s,{name:'Max Muster',invitation:codes.code,meal:'both'});assert.match(await both.text(),/Bitte wähle genau ein Gericht/);
});
test('Statistik zählt Wiederholung kontrolliert und antwortet mit Aggregaten',async()=>{
 const s=await session();const options={headers:{cookie:s.cookie}};
 const first=await (await fetch(base+'/stats.php?view=1',options)).json();
 const second=await (await fetch(base+'/stats.php?view=1',options)).json();
 assert.equal(second.total,first.total);assert.equal(second.today,first.today);
 assert.ok(second.online>=1);assert.deepEqual(Object.keys(second).sort(),['online','today','total','yesterday']);
});
test('Zwei-Faktor-Login, geschützter CSV-Export und Logout',async()=>{
 const s=await session('/admin.php');
 const missing=await post('/admin.php',s,{action:'login',user:'test-admin',password:access.password,otp:''});assert.match(await missing.text(),/Anmeldung fehlgeschlagen/);
 const r=await post('/admin.php',s,{action:'login',user:'test-admin',password:access.password,otp:otp()});assert.equal(r.status,303);
 const rotated=r.headers.get('set-cookie');assert.ok(rotated);s.cookie=rotated.split(';')[0];
 const page=await (await fetch(base+'/admin.php',{headers:{cookie:s.cookie}})).text();assert.match(page,/Einwohner und Einladungscodes/);s.csrf=page.match(/name="csrf" value="([a-f0-9]+)"/)[1];
 const csv=await post('/admin.php',s,{action:'export'});assert.equal(csv.status,200);assert.match(csv.headers.get('content-type'),/text\/csv/);assert.match(await csv.text(),/Vorname;Nachname;Gericht/);
 const anonymous=await fetch(base+'/admin.php',{method:'POST',body:new URLSearchParams({action:'export'})});assert.equal(anonymous.status,403);
 const logout=await post('/admin.php',s,{action:'logout'});assert.equal(logout.status,303);
 const stale=await (await fetch(base+'/admin.php',{headers:{cookie:s.cookie}})).text();assert.doesNotMatch(stale,/<table/);
});
test('JSON-API speichert nur mit gültigem Sitzungstoken und Code',async()=>{
 const start=await fetch(base+'/api/session.php');const cookie=start.headers.get('set-cookie').split(';')[0];const info=await start.json();assert.equal(info.registration_open,true);
 const wrongType=await fetch(base+'/api/registration.php',{method:'POST',headers:{cookie,'Content-Type':'application/jsonx','X-CSRF-Token':info.csrf},body:'{}'});assert.equal(wrongType.status,415);
 await new Promise(resolve=>setTimeout(resolve,2100));
 const r=await fetch(base+'/api/registration.php',{method:'POST',headers:{cookie,'Content-Type':'application/json','X-CSRF-Token':info.csrf},body:JSON.stringify({name:'Max Muster',invitation:codes.second_code,meal:'eisbein'})});
 assert.equal(r.status,200);assert.match((await r.json()).message,/gespeichert/);
});
