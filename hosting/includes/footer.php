</main>
<script>
const sb = document.getElementById('sidebar'),
      tg = document.getElementById('sbToggle'),
      bd = document.getElementById('sbBackdrop');
if (tg) {
  tg.onclick = () => { sb.classList.toggle('open'); bd.classList.toggle('show'); };
  bd.onclick  = () => { sb.classList.remove('open'); bd.classList.remove('show'); };
}
// ---- тосты рендерит app.js из window.VDS_TOASTS ----
// ---- колокольчик: выпадашка + отметка «прочитано» ----
(function(){
  const bell = document.getElementById('bellBtn'), pop = document.getElementById('notifPop');
  if (!bell || !pop) return;
  bell.addEventListener('click', e => {
    e.stopPropagation();
    const open = !pop.hidden;
    pop.hidden = open;
    bell.setAttribute('aria-expanded', String(!open));
    if (!open && window.VDS_canMarkAll) markAll();
  });
  document.addEventListener('click', e => { if (!pop.contains(e.target)) { pop.hidden = true; bell.setAttribute('aria-expanded','false'); } });
  function markAll(){
    fetch('/api/notifications.php', {method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({act:'read_all', csrf: document.querySelector('input[name=csrf]')?.value || ''})})
      .then(r=>r.json()).then(d=>{
        if (!d.ok) return;
        pop.querySelectorAll('.np-item.new').forEach(i=>i.classList.remove('new'));
        const dot = document.getElementById('bellDot'); if (dot) dot.remove();
        const nb = document.querySelector('.nav-badge'); if (nb) nb.remove();
      }).catch(()=>{});
  }
  const mab = document.getElementById('markAllBtn');
  if (mab) mab.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); markAll(); });
  window.VDS_canMarkAll = true;
})();
</script>
</body>
</html>
