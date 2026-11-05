async function stats(view=false){
 try {
  const response=await fetch('/stats.php'+(view?'?view=1':''),{credentials:'same-origin',cache:'no-store'});
  if(!response.ok) throw new Error();
  const data=await response.json();
  for(const key of ['today','yesterday','total','online']) document.querySelector(`[data-stat="${key}"]`).textContent=new Intl.NumberFormat('de-DE').format(data[key]);
 } catch { document.getElementById('stats-note').textContent='Besucherstatistik momentan nicht verfügbar.'; }
}
stats(true);
setInterval(()=>{if(document.visibilityState==='visible')stats();},60000);
