// ===== DB =====
const DB = {
  get: k => JSON.parse(localStorage.getItem('gs3_'+k)||'null'),
  set: (k,v) => localStorage.setItem('gs3_'+k, JSON.stringify(v)),
};
function initDB() {
  if (!DB.get('users')) DB.set('users', [
    {id:1,name:'Admin GreenSteps',email:'admin@greensteps.id',password:'admin123',city:'Jakarta',role:'admin',joined:[]},
    {id:2,name:'Sari Dewi',email:'sari@email.com',password:'sari123',city:'Bandung',role:'member',joined:[1,2]},
    {id:3,name:'Budi Santoso',email:'budi@email.com',password:'budi123',city:'Surabaya',role:'member',joined:[1]},
  ]);
  if (!DB.get('programs')) DB.set('programs', [
    {id:1,title:'30 Hari Zero Waste',desc:'Tantang dirimu selama 30 hari penuh untuk mengurangi sampah dalam kehidupan sehari-hari. Dari membawa tumbler, belanja tanpa plastik, hingga kompos sampah dapur.',cat:'Zero Waste',status:'aktif',emoji:'♻️',target:500,participants:340,date:'2025-01-01'},
    {id:2,title:'Beach Cleanup Nasional',desc:'Bergabunglah dalam aksi bersih pantai serentak di 20 kota pesisir Indonesia. Bersama kita jaga keindahan lautan untuk generasi mendatang.',cat:'Bersih-Bersih',status:'aktif',emoji:'🌊',target:1000,participants:723,date:'2025-03-15'},
    {id:3,title:'1000 Pohon Jawa Barat',desc:'Program penanaman 1000 pohon di lahan kritis Jawa Barat. Setiap peserta mendapatkan bibit pohon dan panduan perawatan untuk ditanam di sekitar tempat tinggal.',cat:'Penghijauan',status:'aktif',emoji:'🌳',target:1000,participants:487,date:'2025-04-22'},
    {id:4,title:'Solar Panel Kampung',desc:'Edukasi dan bantuan instalasi panel surya untuk kampung yang belum terjangkau listrik di wilayah terpencil Indonesia.',cat:'Energi',status:'akan-datang',emoji:'☀️',target:200,participants:0,date:'2025-06-01'},
    {id:5,title:'Sekolah Hijau 2024',desc:'Program edukasi lingkungan ke 50 sekolah dasar di seluruh Indonesia. Mengajarkan anak-anak tentang pentingnya menjaga lingkungan sejak dini.',cat:'Edukasi',status:'selesai',emoji:'📚',target:500,participants:512,date:'2024-08-01'},
    {id:6,title:'Sungai Bersih Ciliwung',desc:'Aksi bersih sungai Ciliwung yang melibatkan warga sekitar, komunitas, dan pemerintah daerah untuk memulihkan ekosistem sungai.',cat:'Bersih-Bersih',status:'selesai',emoji:'🏞️',target:300,participants:298,date:'2024-10-05'},
  ]);
  if (!DB.get('session')) DB.set('session', null);
}

// ===== AUTH =====
function getUser() { const id=DB.get('session'); return id?DB.get('users').find(u=>u.id===id):null; }

function doLogin() {
  const email=document.getElementById('l-email').value.trim();
  const pass=document.getElementById('l-pass').value;
  const u=DB.get('users').find(u=>u.email===email&&u.password===pass);
  if (!u) { document.getElementById('l-err').style.display='block'; return; }
  DB.set('session',u.id); closeModal('login'); updateUI(); showToast('Selamat datang, '+u.name+'! 👋'); renderCampaigns();
}

function doRegister() {
  const name=document.getElementById('r-name').value.trim();
  const email=document.getElementById('r-email').value.trim();
  const city=document.getElementById('r-city').value.trim();
  const pass=document.getElementById('r-pass').value;
  if (!name||!email||!pass){showToast('Lengkapi semua field!');return;}
  const users=DB.get('users');
  if (users.find(u=>u.email===email)){document.getElementById('r-err').style.display='block';return;}
  const nu={id:Date.now(),name,email,password:pass,city:city||'-',role:'member',joined:[]};
  users.push(nu); DB.set('users',users); DB.set('session',nu.id);
  closeModal('register'); updateUI(); showToast('Akun berhasil dibuat! Selamat bergabung 🎉'); renderCampaigns();
}

function logout() { DB.set('session',null); updateUI(); renderCampaigns(); showToast('Berhasil keluar 👋'); }

function updateUI() {
  const u=getUser();
  document.getElementById('auth-area').style.display=u?'none':'flex';
  document.getElementById('user-area').style.display=u?'flex':'none';
  if(u){
    document.getElementById('u-av').textContent=u.name[0];
    document.getElementById('u-name').textContent='Hi, '+u.name.split(' ')[0];
    document.getElementById('admin-add-wrap').style.display=u.role==='admin'?'block':'none';
  } else { document.getElementById('admin-add-wrap').style.display='none'; }
}

// ===== CAMPAIGNS =====
let curFilter='all';
function setFilter(f,btn){
  curFilter=f;
  document.querySelectorAll('.filter-btn').forEach(b=>{
    b.classList.toggle('on',b.dataset.f===f);
  });
  renderCampaigns();
}

function renderCampaigns(){
  let progs=DB.get('programs');
  if(curFilter!=='all') progs=progs.filter(p=>p.status===curFilter);
  const u=getUser();
  const bg=['bg1','bg2','bg3'];
  const bl={aktif:'badge-a',selesai:'badge-s','akan-datang':'badge-d'};
  const ll={aktif:'Aktif',selesai:'Selesai','akan-datang':'Segera'};
  const grid=document.getElementById('campaign-grid');
  if(!progs.length){grid.innerHTML='<p style="color:#7a9a50;font-size:0.875rem">Tidak ada program.</p>';return;}
  grid.innerHTML=progs.map((p,i)=>{
    const pct=Math.min(100,Math.round(p.participants/p.target*100));
    const joined=u&&u.joined.includes(p.id);
    return `<div class="campaign-card card-hover">
      <div class="c-thumb ${bg[i%3]}">${p.emoji}<span class="c-badge ${bl[p.status]}">${ll[p.status]}</span></div>
      <div class="c-body">
        <span class="c-cat">${p.cat}</span>
        <h3>${p.title}</h3>
        <p>${p.desc}</p>
        <div class="prog-bar"><div class="prog-fill" style="width:${pct}%"></div></div>
        <div class="prog-meta"><span>${p.participants.toLocaleString('id-ID')} peserta</span><span>${pct}% dari ${p.target.toLocaleString('id-ID')}</span></div>
        ${p.status==='aktif'
          ?`<button class="btn-join${joined?' done':''}" onclick="joinProg(${p.id})">${joined?'✓ Sudah Bergabung':'Ikut Program'}</button>`
          :`<div style="text-align:center;font-size:0.75rem;color:#7a9a50;padding:0.4rem 0">Program ${ll[p.status]}</div>`}
      </div>
    </div>`;
  }).join('');
}

function joinProg(id){
  const u=getUser(); if(!u){openModal('login');return;}
  const users=DB.get('users'),progs=DB.get('programs');
  const user=users.find(x=>x.id===u.id),prog=progs.find(x=>x.id===id);
  if(!user||!prog||user.joined.includes(id))return;
  user.joined.push(id); prog.participants++;
  DB.set('users',users); DB.set('programs',progs);
  showToast('Berhasil bergabung ke "'+prog.title+'"! 🌱');
  renderCampaigns();
}

function doAddProgram(){
  const title=document.getElementById('ap-title').value.trim();
  const desc=document.getElementById('ap-desc').value.trim();
  if(!title||!desc){showToast('Lengkapi judul dan deskripsi!');return;}
  const progs=DB.get('programs');
  progs.push({id:Date.now(),title,desc,cat:document.getElementById('ap-cat').value,status:document.getElementById('ap-status').value,emoji:document.getElementById('ap-emoji').value||'🌿',target:parseInt(document.getElementById('ap-target').value)||100,participants:0,date:document.getElementById('ap-date').value||new Date().toISOString().slice(0,10)});
  DB.set('programs',progs); closeModal('add-program'); showToast('Program berhasil ditambahkan! 🎉'); renderCampaigns();
}

// ===== PROFILE MODAL =====
function openModal(name){
  if(name==='profile'){
    const u=getUser(); if(!u)return;
    const progs=DB.get('programs');
    document.getElementById('pf-av').textContent=u.name[0];
    document.getElementById('pf-name').textContent=u.name;
    document.getElementById('pf-email').textContent=u.email;
    document.getElementById('pf-role').textContent=u.role==='admin'?'Admin':'Anggota';
    document.getElementById('pf-joined').textContent=u.joined.length;
    document.getElementById('pf-city').textContent=u.city||'-';
    document.getElementById('pf-progs').innerHTML=u.joined.length?u.joined.map(id=>{const p=progs.find(x=>x.id===id);return p?`<div>${p.emoji} ${p.title}</div>`:''}).join(''):'Belum mengikuti program apapun.';
  }
  document.getElementById('modal-'+name).classList.add('open');
}

function closeModal(name){
  document.getElementById('modal-'+name).classList.remove('open');
  ['l-err','r-err'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display='none';});
}
function switchModal(a,b){closeModal(a);openModal(b);}
document.addEventListener('click',e=>{if(e.target.classList.contains('modal-bg'))document.querySelectorAll('.modal-bg.open').forEach(m=>m.classList.remove('open'));});

// ===== NEWSLETTER =====
function subscribeNL(){
  const v=document.getElementById('nl-email').value.trim();
  if(!v||!v.includes('@')){showToast('Masukkan email yang valid!');return;}
  document.getElementById('nl-email').value='';
  showToast('Terima kasih sudah bergabung! Cek email kamu. 🌿');
}

// ===== SLIDER =====
let curSlide=0;
function goSlide(n){
  document.querySelectorAll('.slide').forEach((s,i)=>s.classList.toggle('active',i===n));
  document.querySelectorAll('.dot').forEach((d,i)=>d.classList.toggle('active',i===n));
  curSlide=n;
}
setInterval(()=>goSlide((curSlide+1)%3),5000);

// ===== TOAST =====
function showToast(msg){
  const t=document.getElementById('toast');
  t.textContent=msg; t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3000);
}

// ===== INIT =====
initDB(); updateUI(); renderCampaigns();
