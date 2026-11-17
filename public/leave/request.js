async function requestView() {
  const b = $("#body");
  b.innerHTML = `<div class="card"><div class="bal" id="bal"></div></div>
  <div class="card"><div class="seg"><label class="on" id="k1">روزانه</label><label id="k2">ساعتی</label></div>
  <div id="dyn"></div><label>توضیحات (اختیاری)</label><textarea id="rs" rows="2"></textarea>
  <button class="btn" id="send">ثبت درخواست</button><div id="msg"></div>
  ${me.role === "approver" ? '<div class="sm">درخواست شما مستقیم برای مدیر ارسال می‌شود.</div>' : ""}</div>
  <h3>درخواست‌های من</h3><div id="list"></div>`;
  let kind = "daily", pA, pB;
  function form() {
    const d = $("#dyn"); d.innerHTML = "";
    const row = document.createElement("div"); row.className = "row";
    if (kind === "daily") {
      pA = makePicker("از تاریخ", s => { if (!pB.get() || pB.get() < s) pB.set(s); }, true); pB = makePicker("تا تاریخ", null, true);
      row.append(pA.el, pB.el); d.append(row);
    } else {
      pA = makePicker("تاریخ", null, true); pB = null; d.append(pA.el);
      row.innerHTML = '<div><label>از ساعت</label><input type="time" id="ft" value="09:00"></div><div><label>تا ساعت</label><input type="time" id="tt" value="11:00"></div>';
      d.append(row);
    }
  }
  $("#k1").onclick = () => { kind = "daily"; $("#k1").classList.add("on"); $("#k2").classList.remove("on"); form(); };
  $("#k2").onclick = () => { kind = "hourly"; $("#k2").classList.add("on"); $("#k1").classList.remove("on"); form(); };
  form();
  async function load() {
    const bl = await api("/x/balance");
    $("#bal").innerHTML = `<div>مانده<b>${fmtMin(bl.balance)}</b></div><div>سهم انباشته<b>${fmtMin(bl.accrued)}</b></div><div>مصرف/رزرو شده<b>${fmtMin(bl.used)}</b></div>`;
    const r = await api("/x/list?scope=mine");
    $("#list").innerHTML = r.items.map(x => card(x, x.boss_status === "pending" ? `<button class="btn no" data-del="${x.id}">حذف درخواست</button>` : "")).join("") || '<div class="sm">هنوز درخواستی ثبت نشده.</div>';
    document.querySelectorAll("#list button[data-del]").forEach(btn => btn.onclick = () => {
      if (!confirm("این درخواست حذف شود؟")) return;
      withBusy(btn, async () => { try { await post("/x/delete", {id: btn.dataset.del}); await load(); } catch (e) { alert(e.message); } });
    });
  }
  const doSend = async () => {
    const m = $("#msg"); m.className = ""; m.textContent = "";
    if (!pA.get()) { m.className = "err"; m.textContent = "تاریخ را انتخاب کنید."; return; }
    const body = {kind, start_date: pA.get(), reason: $("#rs").value.trim()};
    if (kind === "daily") {
      if (!pB.get()) { m.className = "err"; m.textContent = "تاریخ پایان را انتخاب کنید."; return; }
      body.end_date = pB.get();
    } else { body.from_time = $("#ft").value; body.to_time = $("#tt").value; }
    try { await post("/x/create", body); m.className = "okm"; m.textContent = "✅ درخواست ثبت شد."; $("#rs").value = ""; await load(); }
    catch (e) { m.className = "err"; m.textContent = e.message; }
  };
  $("#send").onclick = () => withBusy($("#send"), doSend);
  await load();
}
