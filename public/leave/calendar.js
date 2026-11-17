/* ---------- تقویم شمسی (بدون کتابخانه) ---------- */
const MONTHS=["فروردین","اردیبهشت","خرداد","تیر","مرداد","شهریور","مهر","آبان","آذر","دی","بهمن","اسفند"];
const fnum=n=>String(n).replace(/\d/g,d=>"۰۱۲۳۴۵۶۷۸۹"[d]);
function g2j(d){ /* تبدیل میلادی به شمسی بدون وابستگی به مرورگر */
  var gy=d.getUTCFullYear(),gm=d.getUTCMonth()+1,gd=d.getUTCDate(),jy;
  var gdm=[0,31,59,90,120,151,181,212,243,273,304,334];
  if(gy>1600){jy=979;gy-=1600}else{jy=0;gy-=621}
  var gy2=gm>2?gy+1:gy;
  var days=365*gy+Math.floor((gy2+3)/4)-Math.floor((gy2+99)/100)+Math.floor((gy2+399)/400)-80+gd+gdm[gm-1];
  jy+=33*Math.floor(days/12053);days%=12053;
  jy+=4*Math.floor(days/1461);days%=1461;
  if(days>365){jy+=Math.floor((days-1)/365);days=(days-1)%365}
  var jm=days<186?1+Math.floor(days/31):7+Math.floor((days-186)/30);
  var jd=1+(days<186?days%31:(days-186)%30);
  return[jy,jm,jd];
}
const key=(y,m,d)=>y*400+m*32+d;
function j2g(jy,jm,jd){
  let d=new Date(Date.UTC(jy+621,2,21,12));
  d=new Date(d.getTime()+((jm-1)*30.4+jd-1)*864e5);
  const t=key(jy,jm,jd);
  for(let i=0;i<80;i++){const c=g2j(d),k=key(...c);if(k===t)return d;d=new Date(d.getTime()+(k<t?1:-1)*864e5)}
  return d;
}
function jDim(y,m){if(m<=6)return 31;if(m<=11)return 30;const d=j2g(y,12,30);return g2j(d)[1]===12?30:29}
const iso=d=>d.toISOString().slice(0,10);
function showJ(s){if(!s)return"";const[y,m,d]=s.slice(0,10).split("-").map(Number);const j=g2j(new Date(Date.UTC(y,m-1,d,12)));return`${fnum(j[2])} ${MONTHS[j[1]-1]} ${fnum(j[0])}`}

/* limit=true: فقط از امروز تا پایان ماه بعد قابل انتخاب است (بقیه خاکستری) */
function makePicker(label,onchange,limit){
  const w=document.createElement("div");w.className="dp";
  w.innerHTML=`<label>${label}</label><input readonly placeholder="انتخاب تاریخ"><div class="pop" hidden></div>`;
  const inp=w.querySelector("input"),pop=w.querySelector(".pop");
  let val="",vy,vm;
  const todayJ=g2j(new Date(Date.UTC(...(()=>{const n=new Date();return[n.getFullYear(),n.getMonth(),n.getDate(),12]})())));
  const tIso=iso(j2g(todayJ[0],todayJ[1],todayJ[2])),cur=todayJ[0]*12+todayJ[1]-1;
  function draw(){
    const first=(j2g(vy,vm,1).getUTCDay()+1)%7,n=jDim(vy,vm);
    const at=vy*12+vm-1,noNext=limit&&at>=cur+1,noPrev=limit&&at<=cur;
    let h=`<div class="h"><button type="button" data-n="1"${noNext?" disabled":""}>›</button><span>${MONTHS[vm-1]} ${fnum(vy)}</span><button type="button" data-n="-1"${noPrev?" disabled":""}>‹</button></div><div class="g">`;
    "شیدسچپج".split("").forEach(c=>h+=`<span>${c}</span>`);
    for(let i=0;i<first;i++)h+="<i></i>";
    for(let d=1;d<=n;d++){
      const s=iso(j2g(vy,vm,d)),c=[s===val?"sel":"",(vy===todayJ[0]&&vm===todayJ[1]&&d===todayJ[2])?"today":"",(first+d-1)%7===6?"fri":""].join(" ");
      h+=`<button type="button" class="${c}" data-d="${s}"${limit&&s<tIso?" disabled":""}>${fnum(d)}</button>`;
    }
    pop.innerHTML=h+"</div>";
  }
  function setV(s){val=s;inp.value=s?showJ(s):"";onchange&&onchange(s)}
  inp.onclick=()=>{
    document.querySelectorAll(".pop").forEach(p=>{if(p!==pop)p.hidden=true});
    const base=val?g2j(new Date(val+"T12:00:00Z")):todayJ;vy=base[0];vm=base[1];draw();pop.hidden=!pop.hidden};
  pop.onclick=e=>{
    const b=e.target.closest("button");if(!b||b.disabled)return;
    if(b.dataset.n){vm+=+b.dataset.n;if(vm>12){vm=1;vy++}if(vm<1){vm=12;vy--}draw()}
    else if(b.dataset.d){setV(b.dataset.d);pop.hidden=true}
  };
  document.addEventListener("click",e=>{if(!e.composedPath().includes(w))pop.hidden=true});
  return{el:w,get:()=>val,set:setV};
}