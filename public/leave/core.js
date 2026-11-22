const $ = s => document.querySelector(s);
const esc = s => String(s == null ? "" : s).replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const SECTIONS = {sales: "فروش", site: "سایت", finance: "مالی و بازرگانی", warehouse: "انبار"};
const ROLES = {employee: "کارمند", approver: "تأییدکننده", boss: "مدیر"};
let me = null;

async function api(path, opt = {}) {
  const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || "");
  const r = await fetch(path, Object.assign({}, opt, {headers: Object.assign({"Content-Type": "application/json", "Accept": "application/json", "X-XSRF-TOKEN": xsrf}, opt.headers || {})}));
  const j = await r.json().catch(() => ({}));
  if (r.status === 401 && me && path !== "/x/me") { me = null; location.reload(); throw new Error("نشست منقضی شد؛ دوباره وارد شوید."); }
  if (!r.ok) throw new Error(j.message || "خطا");
  return j;
}
const post = (p, b) => api(p, {method: "POST", body: JSON.stringify(b || {})});

async function withBusy(btn, fn) {
  const t = btn.innerHTML; btn.disabled = true;
  btn.innerHTML = '<span class="sp"></span>لطفاً صبر کنید…';
  try { return await fn(); } finally { if (btn.isConnected) { btn.disabled = false; btn.innerHTML = t; } }
}
function opts(map, sel, blank) {
  return (blank !== undefined ? `<option value="">${blank}</option>` : "") +
    Object.entries(map).map(([k, v]) => `<option value="${k}"${k === sel ? " selected" : ""}>${v}</option>`).join("");
}
function fmtMin(m) {
  m = Number(m) || 0; const n = m < 0, a = Math.abs(m), d = Math.floor(a / 480), h = Math.round((a % 480) / 6) / 10, p = [];
  if (d) p.push(fnum(d) + " روز"); if (h || !d) p.push(fnum(h) + " ساعت");
  return (n ? "−" : "") + p.join(" و ");
}
const LB = {approved: ["تأیید شد", "ok-t"], rejected: ["رد شد", "no-t"], pending: ["در انتظار", "wa-t"]};
const bdg = s => { s = LB[s] ? s : "pending"; return `<span class="b ${LB[s][1]}">${LB[s][0]}</span>`; };
const st = x => x.fin_status === "rejected" || x.boss_status === "rejected" ? "rejected" : (x.fin_status === "approved" && x.boss_status === "approved") ? "approved" : "pending";

function card(x, extra) {
  const u = (x.expand && x.expand.user) || {};
  const sec = u.section ? ` <span class="sm">· ${SECTIONS[u.section] || ""}</span>` : "";
  const when = x.kind === "daily" ? `${showJ(x.start_date)} تا ${showJ(x.end_date)}` : `${showJ(x.start_date)} — ساعت ${fnum(x.from_time)} تا ${fnum(x.to_time)}`;
  return `<div class="card">${u.fullname ? `<b>${esc(u.fullname)}</b>${sec} ` : ""}${bdg(st(x))}
  <div>${x.kind === "daily" ? "مرخصی روزانه" : "مرخصی ساعتی"}: ${when} <span class="sm">(${fmtMin(x.minutes)})</span></div>
  ${x.reason ? `<div class="sm">${esc(x.reason)}</div>` : ""}
  ${x.created_at ? `<div class="sm">ثبت‌شده: ${showJ(x.created_at.slice(0, 10))} ساعت ${fnum(x.created_at.slice(11, 16))}</div>` : ""}
  <div class="steps"><span>تأییدکننده بخش: ${bdg(x.fin_status)}</span><span>مدیر: ${bdg(x.boss_status)}</span></div>${extra || ""}</div>`;
}