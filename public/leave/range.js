async function rangeView() {
  const b = $("#body");
  b.innerHTML = `<div class="card noprint"><div class="row"><div id="pf"></div><div id="pt"></div></div>
  <div class="row"><div><label>بخش</label><select id="sec">${opts(SECTIONS, "", "همه بخش‌ها")}</select></div>
  <div><label>وضعیت</label><select id="stt"><option value="approved">فقط تأییدشده</option><option value="all">همه (به‌جز ردشده)</option></select></div></div>
  <label>جست‌وجوی نام (اختیاری)</label><input id="q" placeholder="نام یا نام خانوادگی">
  <div class="acts"><button class="btn" id="go">نمایش گزارش</button><button class="btn gh" id="xl">دانلود اکسل</button><button class="btn gh" id="pr">چاپ / PDF</button></div>
  <div class="err" id="msg"></div></div><div id="rep"></div>`;
  const pf = makePicker("از تاریخ"), pt = makePicker("تا تاریخ");
  $("#pf").append(pf.el); $("#pt").append(pt.el);
  const n = new Date(), t = g2j(new Date(Date.UTC(n.getFullYear(), n.getMonth(), n.getDate(), 12)));
  pf.set(iso(j2g(t[0], t[1], 1))); pt.set(iso(j2g(t[0], t[1], jDim(t[0], t[1]))));
  const qs = () => `from=${pf.get()}&to=${pt.get()}&status=${$("#stt").value}&section=${$("#sec").value}&q=${encodeURIComponent($("#q").value.trim())}`;
  const check = () => { if (!pf.get() || !pt.get()) { $("#msg").textContent = "هر دو تاریخ را انتخاب کنید."; return false; } $("#msg").textContent = ""; return true; };
  const when = l => l.kind === "daily"
    ? (l.start_date === l.end_date ? showJ(l.start_date) : `${showJ(l.start_date)} تا ${showJ(l.end_date)}`)
    : `${showJ(l.start_date)} · ساعت ${fnum(l.from_time)} تا ${fnum(l.to_time)}`;
  async function load() {
    if (!check()) return;
    try {
      const r = await api("/x/report/leaves?" + qs());
      const sec = $("#sec").value ? " · بخش " + SECTIONS[$("#sec").value] : "";
      $("#rep").innerHTML = `<div class="rep"><div class="rhead"><div class="rt">گزارش مرخصی کارکنان</div><div class="rs">${showJ(r.from)} تا ${showJ(r.to)}${sec}</div></div>
      <div class="chips"><div class="chip"><b>${fnum(r.items.length)}</b><span>نفر</span></div><div class="chip"><b>${fmtMin(r.total)}</b><span>مجموع مرخصی</span></div><div class="chip"><b>${fnum(r.count)}</b><span>مورد</span></div></div>
      ${r.items.map(p => `<div class="pcard"><div class="ph"><div><b>${esc(p.name)}</b> <span class="sm">${SECTIONS[p.section] || ""}</span></div><div class="pt">${fmtMin(p.total)}</div></div>
        ${p.leaves.map(l => `<div class="lrow"><span>${when(l)}</span><span class="sm">${fmtMin(l.minutes)}${l.status !== "approved" ? " · در انتظار" : ""}</span></div>`).join("")}</div>`).join("")
        || '<div class="sm" style="padding:0 14px 14px">در این بازه مرخصی‌ای ثبت نشده است.</div>'}
      <div class="rfoot">تاریخ تهیه گزارش: ${showJ(iso(new Date()))}</div></div>`;
    } catch (e) { $("#msg").textContent = e.message; }
  }
  $("#go").onclick = () => withBusy($("#go"), load);
  $("#xl").onclick = () => { if (check()) location.href = "/x/report/leaves.xlsx?" + qs(); };
  $("#pr").onclick = () => window.print();
  await load();
}
