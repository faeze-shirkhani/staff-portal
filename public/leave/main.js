let tab = "";
function tabsFor() {
  const t = [];
  if (me.role !== "boss") t.push(["req", "درخواست مرخصی"]);
  if (me.role === "approver" || me.role === "boss") t.push(["queue", "صف تأیید"]);
  if (me.role === "boss" || me.can_report) { t.push(["report", "گزارش کارمندان"]); t.push(["range", "گزارش بازه‌ای"]); }
  if (me.role === "boss") t.push(["admin", "مدیریت کاربران"]);
  return t;
}
function render() {
  if (!me) { authView(); return; }
  const tabs = tabsFor();
  if (!tabs.find(t => t[0] === tab)) tab = tabs[0][0];
  $("#app").innerHTML = `<div class="top"><h1>${esc(me.fullname)} <span class="sm">(${ROLES[me.role]}${me.section ? " · " + SECTIONS[me.section] : ""})</span></h1><button class="btn gh" id="lo">خروج</button></div>
  <div class="mainnav"><a class="on" href="/">📅 مرخصی</a><a href="/tasks">✅ تسک‌ها</a></div>
  <div class="tabs">${tabs.map(([k, l]) => `<button data-t="${k}" class="${k === tab ? "on" : ""}">${l}</button>`).join("")}</div><div id="body"></div>`;
  $("#lo").onclick = async () => { try { await api("/x/logout"); } catch (e) {} me = null; render(); };
  document.querySelectorAll(".tabs button").forEach(b => b.onclick = () => { if (b.dataset.href) { location.href = b.dataset.href; return; } tab = b.dataset.t; render(); });
  ({req: requestView, queue: queueView, report: reportView, range: rangeView, admin: adminView})[tab]()
    .catch(e => { $("#body").innerHTML = `<div class="err">${esc(e.message)}</div>`; });
}
(async () => { try { me = (await api("/x/me")).record; } catch (e) { me = null; } render(); })();
