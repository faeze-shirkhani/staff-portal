/* صفحه‌ی مرخصی: منو و نوار بالا از سمت سرور می‌آید؛ اینجا فقط محتوای بخش انتخاب‌شده ساخته می‌شود */
let tab = "";
const TITLES = {req: "درخواست مرخصی", queue: "صف تأیید", report: "گزارش مانده مرخصی", range: "گزارش بازه‌ای مرخصی", admin: "مدیریت کاربران"};
function tabsFor() {
  const t = [];
  if (me.role !== "boss") t.push(["req", TITLES.req]);
  if (me.role === "approver" || me.role === "boss") t.push(["queue", TITLES.queue]);
  if (me.role === "boss" || me.can_report) { t.push(["report", TITLES.report]); t.push(["range", TITLES.range]); }
  if (me.role === "boss") t.push(["admin", TITLES.admin]);
  return t;
}
function render() {
  if (!me) { authView(); return; }
  const ids = tabsFor().map(t => t[0]);
  const h = location.hash.slice(1);
  tab = ids.indexOf(h) >= 0 ? h : ids[0];
  document.querySelectorAll("[data-nav]").forEach(a => a.classList.toggle("on", a.dataset.nav === tab));
  const crumb = document.getElementById("crumb"); if (crumb) crumb.textContent = TITLES[tab];
  $("#app").innerHTML = '<div id="body"></div>';
  ({req: requestView, queue: queueView, report: reportView, range: rangeView, admin: adminView})[tab]()
    .catch(e => { $("#body").innerHTML = `<div class="err">${esc(e.message)}</div>`; });
}
window.addEventListener("hashchange", () => { if (me) render(); });
(async () => { try { me = (await api("/x/me")).record; } catch (e) { me = null; } render(); })();
