async function adminView() {
  const b = $("#body");
  b.innerHTML = '<div class="card"><label>جست‌وجوی کاربر</label><input id="q" placeholder="نام"></div><div id="ul"></div>';
  let t;
  async function load() {
    const r = await api("/x/admin/users?q=" + encodeURIComponent($("#q").value.trim()));
    $("#ul").innerHTML = r.items.map(u => {
      const d = Math.trunc(u.extra_minutes / 480), h = Math.round((u.extra_minutes - d * 480) / 6) / 10;
      return `<div class="card" data-id="${u.id}"><label>نام و نام خانوادگی</label><input class="n" value="${esc(u.name)}">
      <div class="row"><div><label>نقش</label><select class="r">${opts(ROLES, u.role)}</select></div>
      <div><label>بخش</label><select class="s">${opts(SECTIONS, u.section || "", "—")}</select></div></div>
      <label><input type="checkbox" class="c" ${u.can_report ? "checked" : ""} style="width:auto"> دسترسی به گزارش کارمندان</label>
      <label><input type="checkbox" class="k" ${u.skip_first ? "checked" : ""} style="width:auto"> نیاز به تأیید اولیه ندارد (مستقیم برای مدیر)</label>
      <div class="row"><div><label>مانده‌ی اضافه (روز)</label><input class="d" type="number" step="1" value="${d}"></div>
      <div><label>و ساعت</label><input class="h" type="number" step="0.5" value="${h}"></div></div>
      <label>رمز جدید (خالی = بدون تغییر)</label><input class="p" dir="ltr" autocomplete="off">
      <button class="btn sv">ذخیره</button> <button class="btn no dl">حذف</button> <span class="sm msg"></span></div>`;
    }).join("") || '<div class="sm">کاربری پیدا نشد.</div>';
  }
  $("#ul").onclick = e => {
    const btn = e.target.closest("button"); if (!btn) return;
    const c = btn.closest(".card"), id = c.dataset.id, msg = c.querySelector(".msg"), q = s => c.querySelector(s);
    if (btn.classList.contains("sv")) {
      withBusy(btn, async () => {
        try {
          await post("/x/admin/users/" + id, {name: q(".n").value, role: q(".r").value, section: q(".s").value, can_report: q(".c").checked, skip_first: q(".k").checked,
            extra_minutes: Math.round(Number(q(".d").value || 0) * 480 + Number(q(".h").value || 0) * 60), password: q(".p").value});
          msg.textContent = "✅ ذخیره شد"; q(".p").value = "";
        } catch (er) { msg.textContent = er.message; }
      });
    } else if (btn.classList.contains("dl")) {
      if (!confirm("این کاربر و تمام درخواست‌هایش حذف شود؟")) return;
      withBusy(btn, async () => { try { await post("/x/admin/users/" + id + "/delete"); await load(); } catch (er) { msg.textContent = er.message; } });
    }
  };
  $("#q").oninput = () => { clearTimeout(t); t = setTimeout(load, 300); };
  await load();
}
