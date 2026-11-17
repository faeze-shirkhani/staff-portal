async function reportView() {
  const b = $("#body");
  b.innerHTML = `<div class="card"><div class="row"><div><label>جست‌وجوی نام</label><input id="q" placeholder="نام یا نام خانوادگی"></div>
  <div><label>بخش</label><select id="sec">${opts(SECTIONS, "", "همه بخش‌ها")}</select></div></div></div>
  <div class="card"><div class="sm" id="cnt"></div><div class="tw" id="tbl"></div></div>`;
  let t;
  async function load() {
    const r = await api("/x/report?q=" + encodeURIComponent($("#q").value.trim()) + "&section=" + $("#sec").value);
    $("#cnt").textContent = `${fnum(r.items.length)} نفر`;
    $("#tbl").innerHTML = r.items.length
      ? '<table class="tbl"><tr><th>نام</th><th>بخش</th><th>مانده</th><th>مصرف‌شده (تأییدشده)</th><th>در انتظار</th><th>سهم کل</th></tr>' +
        r.items.map(x => `<tr><td>${esc(x.name)}</td><td>${SECTIONS[x.section] || "—"}</td><td><b>${fmtMin(x.balance)}</b></td><td>${fmtMin(x.approved)}</td><td>${fmtMin(x.pending)}</td><td>${fmtMin(x.accrued)}</td></tr>`).join("") + "</table>"
      : '<div class="sm">موردی پیدا نشد.</div>';
  }
  $("#q").oninput = () => { clearTimeout(t); t = setTimeout(load, 300); };
  $("#sec").onchange = load;
  await load();
}
