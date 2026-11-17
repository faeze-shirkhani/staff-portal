async function queueView() {
  const b = $("#body");
  async function load() {
    const p = await api("/x/list?scope=queue"), h = await api("/x/list?scope=history");
    const acts = id => `<button class="btn ok" data-i="${id}" data-a="approved">تأیید</button> <button class="btn no" data-i="${id}" data-a="rejected">رد</button>`;
    b.innerHTML = `<h3>در انتظار تصمیم شما (${fnum(p.items.length)})</h3>${p.items.map(x => card(x, acts(x.id))).join("") || '<div class="sm">درخواستی در صف نیست.</div>'}
    <h3>تصمیم‌های اخیر</h3>${h.items.map(x => card(x, me.role === "boss" ? `<button class="btn no" data-del="${x.id}">حذف</button>` : "")).join("") || '<div class="sm">—</div>'}<div class="err" id="msg"></div>`;
    b.querySelectorAll("button[data-i]").forEach(btn => btn.onclick = () => withBusy(btn, async () => {
      try { await post("/x/decide", {id: btn.dataset.i, action: btn.dataset.a}); await load(); }
      catch (e) { $("#msg").textContent = e.message; }
    }));
    b.querySelectorAll("button[data-del]").forEach(btn => btn.onclick = () => {
      if (!confirm("این درخواست حذف شود؟")) return;
      withBusy(btn, async () => { try { await post("/x/delete", {id: btn.dataset.del}); await load(); } catch (e) { $("#msg").textContent = e.message; } });
    });
  }
  await load();
}
