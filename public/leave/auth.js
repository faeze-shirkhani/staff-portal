function authView() {
  let mode = "login";
  const draw = () => {
    const reg = mode === "reg";
    $("#app").innerHTML = `<h1>📅 سامانه مرخصی</h1><div class="card"><div class="seg"><label class="${reg ? "" : "on"}" id="m1">ورود</label><label class="${reg ? "on" : ""}" id="m2">ثبت‌نام</label></div>
    <div class="row"><div><label>نام</label><input id="fn"></div><div><label>نام خانوادگی</label><input id="ln"></div></div>
    ${reg ? `<label>بخش</label><select id="sc">${opts(SECTIONS, "", "انتخاب بخش…")}</select>` : ""}
    <label>رمز عبور</label><input id="pw" type="password" inputmode="numeric" dir="ltr">
    ${reg ? '<label>تکرار رمز عبور</label><input id="pw2" type="password" inputmode="numeric" dir="ltr">' : ""}
    <button class="btn" id="go">${reg ? "ثبت‌نام" : "ورود"}</button><div class="err" id="msg"></div></div>`;
    $("#m1").onclick = () => { mode = "login"; draw(); };
    $("#m2").onclick = () => { mode = "reg"; draw(); };
    const go = async () => {
      try {
        const first = $("#fn").value.trim(), last = $("#ln").value.trim(), password = $("#pw").value;
        if (!first || !last || !password) throw new Error("نام، نام خانوادگی و رمز را کامل کنید.");
        let section;
        if (reg) {
          section = $("#sc").value;
          if (!section) throw new Error("بخش خود را انتخاب کنید.");
          if (password !== $("#pw2").value) throw new Error("رمز و تکرار آن یکسان نیست.");
        }
        const r = await post(reg ? "/x/register" : "/x/login", {first, last, password, section});
        me = r.record; tab = ""; render();
      } catch (e) { $("#msg").textContent = e.message || "خطای ناشناخته"; }
    };
    const run = () => { const b = $("#go"); if (!b.disabled) withBusy(b, go); };
    $("#go").onclick = run;
    $("#pw").onkeydown = e => { if (e.key === "Enter") run(); };
  };
  draw();
}
