// ============================================================
// greensteps-id.js — Frontend dengan PHP REST API (XAMPP)
// ============================================================

const API = 'api'; // relatif ke root project

// Helper fetch ke API
async function apiFetch(path, method = 'GET', body = null) {
  const opts = {
    method,
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
  };
  if (body) opts.body = JSON.stringify(body);
  const res  = await fetch(`${API}/${path}`, opts);
  const data = await res.json().catch(() => ({}));
  return data;
}

// ===== REPUTASI LEVEL CONFIG =====
const REP_LEVELS = [
  { level: 1, name: 'Pemula Hijau',   icon: 'https://images.unsplash.com/photo-1599598425947-33004b360706?w=50', minXP: 0,   maxXP: 100,  color: '#5a9a1f', bg: '#e8f5d0' },
  { level: 2, name: 'Pejuang Hijau',  icon: 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=50', minXP: 100, maxXP: 300,  color: '#0a7a5f', bg: '#d0f0e8' },
  { level: 3, name: 'Pahlawan Hijau', icon: 'https://images.unsplash.com/photo-1518531933037-91b2f5f229cc?w=50', minXP: 300, maxXP: 9999, color: '#c97a00', bg: '#faefd0' },
];

function getLevel(xp) {
  for (let i = REP_LEVELS.length - 1; i >= 0; i--) {
    if (xp >= REP_LEVELS[i].minXP) return REP_LEVELS[i];
  }
  return REP_LEVELS[0];
}

// State global
let currentUser = null;

// ===== AUTH =====
async function doLogin() {
  const email = document.getElementById('l-email').value.trim();
  const pass  = document.getElementById('l-pass').value;
  const res   = await apiFetch('auth.php?action=login', 'POST', { email, password: pass });
  if (!res.success) {
    document.getElementById('l-err').style.display = 'block';
    return;
  }
  currentUser = res.user;
  closeModal('login');
  updateUI();
  showToast('Selamat datang, ' + currentUser.name + '! 👋');
  renderCampaigns();
  loadCarbonHistory();
}

async function doRegister() {
  const name  = document.getElementById('r-name').value.trim();
  const email = document.getElementById('r-email').value.trim();
  const city  = document.getElementById('r-city').value.trim();
  const pass  = document.getElementById('r-pass').value;
  if (!name || !email || !pass) { showToast('Lengkapi semua field!'); return; }
  const res = await apiFetch('auth.php?action=register', 'POST', { name, email, city, password: pass });
  if (!res.success) {
    document.getElementById('r-err').textContent = res.message || 'Email sudah terdaftar.';
    document.getElementById('r-err').style.display = 'block';
    return;
  }
  currentUser = res.user;
  closeModal('register');
  updateUI();
  showToast('Akun berhasil dibuat! Selamat bergabung 🎉');
  renderCampaigns();
  loadCarbonHistory();
}

async function logout() {
  await apiFetch('auth.php?action=logout', 'POST');
  currentUser = null;
  updateUI();
  renderCampaigns();
  loadCarbonHistory();
  showToast('Berhasil keluar 👋');
}

// ===== UPDATE UI (navbar) =====
function updateUI() {
  const u = currentUser;
  document.getElementById('auth-area').style.display = u ? 'none' : 'flex';
  document.getElementById('user-area').style.display = u ? 'flex' : 'none';
  if (u) {
    document.getElementById('u-av').textContent = u.name[0];
    document.getElementById('u-name').textContent = 'Hi, ' + u.name.split(' ')[0];
    document.getElementById('admin-add-wrap').style.display = u.role === 'admin' ? 'block' : 'none';
    const adminArticleWrap = document.getElementById('admin-add-article-wrap');
    if (adminArticleWrap) adminArticleWrap.style.display = u.role === 'admin' ? 'block' : 'none';

    // Level badge di navbar
    const lv = getLevel(u.xp || 0);
    let lvBadge = document.getElementById('u-level-badge');
    if (!lvBadge) {
      lvBadge = document.createElement('span');
      lvBadge.id = 'u-level-badge';
      lvBadge.style.cssText = 'font-size:0.68rem;font-weight:700;padding:2px 8px;border-radius:20px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;';
      lvBadge.onclick = () => openModal('profile');
      const userArea = document.getElementById('user-area');
      userArea.insertBefore(lvBadge, userArea.children[1]);
    }
    lvBadge.innerHTML = `<img src="${lv.icon}" style="width:14px;height:14px;object-fit:cover;border-radius:50%"> ${lv.name}`;
    lvBadge.style.background = lv.bg;
    lvBadge.style.color = lv.color;
  } else {
    const lvBadge = document.getElementById('u-level-badge');
    if (lvBadge) lvBadge.remove();
    document.getElementById('admin-add-wrap').style.display = 'none';
    const adminArticleWrap = document.getElementById('admin-add-article-wrap');
    if (adminArticleWrap) adminArticleWrap.style.display = 'none';
  }
}

// ===== CAMPAIGNS =====
let curFilter = 'all';
function setFilter(f, btn) {
  curFilter = f;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.toggle('on', b.dataset.f === f));
  renderCampaigns();
}

async function renderCampaigns() {
  const query = curFilter !== 'all' ? `?status=${curFilter}` : '';
  const res   = await apiFetch('programs.php' + query);
  const progs = res.programs || [];
  const u     = currentUser;

  const bg = ['bg1', 'bg2', 'bg3'];
  const bl = { aktif: 'badge-a', selesai: 'badge-s', 'akan-datang': 'badge-d' };
  const ll = { aktif: 'Aktif', selesai: 'Selesai', 'akan-datang': 'Segera' };
  const grid = document.getElementById('campaign-grid');

  if (!progs.length) {
    grid.innerHTML = '<p style="color:#7a9a50;font-size:0.875rem">Tidak ada program.</p>';
    return;
  }

  grid.innerHTML = progs.map((p, i) => {
    const pct    = Math.min(100, Math.round(p.participants / p.target * 100));
    const joined = u && (u.joined || []).map(Number).includes(Number(p.id));
    const isAdmin = u && u.role === 'admin';
    const desc   = p.description || p.desc || '';
    
    // Fix untuk data lama yang masih berisi karakter emoji
    const emojiMap = {
      '♻️': 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=600&q=80',
      '🌊': 'https://images.unsplash.com/photo-1618477461853-cf6ed80f417a?auto=format&fit=crop&w=600&q=80',
      '🌳': 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80',
      '☀️': 'https://images.unsplash.com/photo-1509391366360-2e959784a276?auto=format&fit=crop&w=600&q=80',
      '📚': 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=600&q=80',
      '🏞️': 'https://images.unsplash.com/photo-1469122312224-c5846569feb1?auto=format&fit=crop&w=600&q=80',
      '🌿': 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80'
    };
    let imgSrc = p.image_url;
    if (imgSrc && emojiMap[imgSrc]) {
      imgSrc = emojiMap[imgSrc];
    } else if (!imgSrc || !imgSrc.startsWith('http')) {
      imgSrc = 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80';
    }

    return `<div class="campaign-card card-hover">
      <div class="c-thumb"><img src="${imgSrc}" alt="${p.title}" loading="lazy"><span class="c-badge ${bl[p.status]}">${ll[p.status]}</span></div>
      <div class="c-body">
        <span class="c-cat">${p.category || p.cat || ''}</span>
        <h3>${p.title}</h3>
        <p style="white-space: pre-wrap;">${desc}</p>
        <div class="c-actions">
          ${p.status === 'aktif'
            ? `<button class="btn-join${joined ? ' done' : ''}" onclick="joinProg(${p.id})">${joined ? '✓ Sudah Bergabung' : 'Ikut Program'}</button>`
            : `<div style="text-align:center;font-size:0.75rem;color:#7a9a50;padding:0.4rem 0">Program ${ll[p.status]}</div>`}
          ${isAdmin ? `
            <div class="admin-btns">
              <button class="btn-edit-prog" onclick="openEditProgram(${p.id}, ${JSON.stringify(p).replace(/"/g, '&quot;')})">✏️ Edit</button>
              <button class="btn-del-prog"  onclick="deleteProgram(${p.id})">🗑️ Hapus</button>
            </div>` : ''}
        </div>
      </div>
    </div>`;
  }).join('');
}

async function joinProg(id) {
  if (!currentUser) { openModal('login'); return; }

  const joined = (currentUser.joined || []).map(Number).includes(Number(id));
  const action = joined ? 'unjoin' : 'join';
  const res    = await apiFetch(`user_programs.php?action=${action}&program_id=${id}`, 'POST');

  if (!res.success) { showToast(res.message || 'Gagal.'); return; }

  // Update XP di state lokal
  currentUser.xp = res.xp;
  if (joined) {
    currentUser.joined = (currentUser.joined || []).filter(j => Number(j) !== Number(id));
    showToast(`Kamu telah keluar dari program. -${res.xp_lost} XP`);
  } else {
    currentUser.joined = [...(currentUser.joined || []), id];
    showToast(`Berhasil bergabung! +${res.xp_gained} XP 🌱`);
  }
  updateUI();
  renderCampaigns();
}

// ── TAMBAH PROGRAM ──────────────────────────────────────────
async function doAddProgram() {
  const title = document.getElementById('ap-title').value.trim();
  const desc  = document.getElementById('ap-desc').value.trim();
  if (!title || !desc) { showToast('Lengkapi judul dan deskripsi!'); return; }

  const res = await apiFetch('programs.php', 'POST', {
    title,
    description: desc,
    category:    document.getElementById('ap-cat').value,
    status:      document.getElementById('ap-status').value,
    image_url:   document.getElementById('ap-image').value || 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80',
    target:      parseInt(document.getElementById('ap-target').value) || 100,
    date:        document.getElementById('ap-date').value || new Date().toISOString().slice(0, 10),
  });

  if (!res.success) { showToast(res.message || 'Gagal menambahkan program.'); return; }
  // Reset form
  ['ap-title','ap-desc','ap-image','ap-target','ap-date'].forEach(id => document.getElementById(id).value = '');
  closeModal('add-program');
  showToast('Program berhasil ditambahkan! 🎉');
  renderCampaigns();
}

// ── EDIT PROGRAM ─────────────────────────────────────────────
let editingProgId = null;
function openEditProgram(id, prog) {
  editingProgId = id;
  if (typeof prog === 'string') prog = JSON.parse(prog.replace(/&quot;/g, '"'));
  document.getElementById('ep-title').value  = prog.title || '';
  document.getElementById('ep-desc').value   = prog.description || prog.desc || '';
  document.getElementById('ep-cat').value    = prog.category || prog.cat || 'Zero Waste';
  document.getElementById('ep-status').value = prog.status || 'aktif';
  document.getElementById('ep-image').value  = prog.image_url || '';
  document.getElementById('ep-target').value = prog.target || 100;
  document.getElementById('ep-date').value   = prog.start_date || prog.date || '';
  openModal('edit-program');
}

async function doEditProgram() {
  const title = document.getElementById('ep-title').value.trim();
  const desc  = document.getElementById('ep-desc').value.trim();
  if (!title || !desc) { showToast('Lengkapi judul dan deskripsi!'); return; }

  const res = await apiFetch(`programs.php?id=${editingProgId}`, 'PUT', {
    title,
    description: desc,
    category:    document.getElementById('ep-cat').value,
    status:      document.getElementById('ep-status').value,
    image_url:   document.getElementById('ep-image').value || 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80',
    target:      parseInt(document.getElementById('ep-target').value) || 100,
    date:        document.getElementById('ep-date').value || new Date().toISOString().slice(0, 10),
  });

  if (!res.success) { showToast(res.message || 'Gagal memperbarui.'); return; }
  closeModal('edit-program');
  showToast('Program berhasil diperbarui! ✅');
  renderCampaigns();
}

async function deleteProgram(id) {
  if (!confirm('Hapus program ini? Tindakan ini tidak bisa dibatalkan.')) return;
  const res = await apiFetch(`programs.php?id=${id}`, 'DELETE');
  if (!res.success) { showToast(res.message || 'Gagal menghapus.'); return; }
  showToast('Program berhasil dihapus.');
  renderCampaigns();
}

// ===== PROFILE MODAL =====
function openModal(name) {
  if (name === 'profile') {
    const u = currentUser;
    if (!u) return;
    const xp    = u.xp || 0;
    const lv    = getLevel(xp);
    const nextLv = REP_LEVELS.find(l => l.level === lv.level + 1);
    const pct   = nextLv ? Math.min(100, Math.round((xp - lv.minXP) / (nextLv.minXP - lv.minXP) * 100)) : 100;

    document.getElementById('pf-av').textContent    = u.name[0];
    document.getElementById('pf-name').textContent  = u.name;
    document.getElementById('pf-email').textContent = u.email;
    document.getElementById('pf-role').textContent  = u.role === 'admin' ? 'Admin' : 'Anggota';
    document.getElementById('pf-joined').textContent = (u.joined || []).length;
    document.getElementById('pf-city').textContent  = u.city || '-';

    document.getElementById('pf-rep-icon').innerHTML = `<img src="${lv.icon}" style="width:32px;height:32px;object-fit:cover;border-radius:50%">`;
    document.getElementById('pf-rep-name').textContent  = lv.name;
    document.getElementById('pf-rep-name').style.color  = lv.color;
    document.getElementById('pf-xp-val').textContent    = xp + ' XP';
    document.getElementById('pf-xp-bar').style.width    = pct + '%';
    document.getElementById('pf-xp-bar').style.background = lv.color;
    document.getElementById('pf-xp-next').textContent   = nextLv
      ? `${xp - lv.minXP} / ${nextLv.minXP - lv.minXP} XP menuju ${nextLv.name}`
      : '🏆 Level Maksimal Tercapai!';

    // Program list — ambil dari API
    apiFetch('programs.php').then(r => {
      const progs = r.programs || [];
      document.getElementById('pf-progs').innerHTML = (u.joined || []).length
        ? (u.joined).map(id => {
            const p = progs.find(x => Number(x.id) === Number(id));
            return p ? `<div>${p.emoji} ${p.title}</div>` : '';
          }).join('')
        : 'Belum mengikuti program apapun.';
    });

    document.getElementById('pf-del-btn').style.display = u.role === 'admin' ? 'none' : 'block';
  }

  if (name === 'leaderboard') renderLeaderboard();

  if (name === 'edit-user' && currentUser) {
    document.getElementById('eu-name').value = currentUser.name;
    document.getElementById('eu-city').value = currentUser.city || '';
    document.getElementById('eu-pass').value = '';
  }

  document.getElementById('modal-' + name).classList.add('open');
}

function closeModal(name) {
  document.getElementById('modal-' + name).classList.remove('open');
  ['l-err', 'r-err'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
}
function switchModal(a, b) { closeModal(a); openModal(b); }
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-bg'))
    document.querySelectorAll('.modal-bg.open').forEach(m => m.classList.remove('open'));
});

// ===== EDIT USER =====
async function doEditUser() {
  const name = document.getElementById('eu-name').value.trim();
  const city = document.getElementById('eu-city').value.trim();
  const pass = document.getElementById('eu-pass').value;
  if (!name) { showToast('Nama tidak boleh kosong!'); return; }
  if (pass && pass.length < 6) { showToast('Password minimal 6 karakter!'); return; }

  const res = await apiFetch('users.php', 'PUT', { name, city, password: pass });
  if (!res.success) { showToast(res.message || 'Gagal memperbarui profil.'); return; }

  currentUser = { ...currentUser, ...res.user };
  closeModal('edit-user');
  updateUI();
  showToast('Profil berhasil diperbarui! ✅');
}

// ===== HAPUS AKUN =====
async function deleteAccount() {
  if (!currentUser) return;
  if (!confirm('Hapus akun ' + currentUser.name + '? Data akan hilang permanen.')) return;
  const res = await apiFetch('users.php', 'DELETE');
  if (!res.success) { showToast(res.message || 'Gagal menghapus akun.'); return; }
  currentUser = null;
  closeModal('profile');
  updateUI();
  renderCampaigns();
  showToast('Akun berhasil dihapus. Sampai jumpa! 👋');
}

// ===== LEADERBOARD =====
async function renderLeaderboard() {
  const res   = await apiFetch('users.php?action=leaderboard');
  const users = res.users || [];
  const medals = ['🥇', '🥈', '🥉'];
  document.getElementById('lb-list').innerHTML = users.map((u, i) => {
    const lv   = getLevel(u.xp || 0);
    const isMe = currentUser && u.id === currentUser.id;
    return `<div style="display:flex;align-items:center;padding:0.75rem 0;border-bottom:1px solid #f0f5e8">
      <div style="width:24px;text-align:center;font-weight:700;color:${i<3?'#3d7d1f':'#7a9a50'}">${i+1}.</div>
      <div style="flex:1;margin-left:0.75rem">
        <div style="font-weight:700;font-size:0.85rem">${u.name}</div>
        <div style="font-size:0.75rem;color:#5a7a30;display:flex;align-items:center;gap:4px">
          <img src="${lv.icon}" style="width:12px;height:12px;object-fit:cover;border-radius:50%"> ${lv.name}
        </div>
      </div>
      <div style="font-weight:800;color:#1f5c08">${u.xp || 0} <span style="font-size:0.7rem;font-weight:600">XP</span></div>
    </div>`;
  }).join('') || '<p style="color:#7a9a50;font-size:0.85rem">Belum ada data.</p>';
}

// ===== NEWSLETTER =====
function subscribeNL() {
  const v = document.getElementById('nl-email').value.trim();
  if (!v || !v.includes('@')) { showToast('Masukkan email yang valid!'); return; }
  document.getElementById('nl-email').value = '';
  showToast('Terima kasih sudah bergabung! Cek email kamu. 🌿');
}

// ===== SLIDER =====
let curSlide = 0;
function goSlide(n) {
  document.querySelectorAll('.slide').forEach((s, i) => s.classList.toggle('active', i === n));
  document.querySelectorAll('.dot').forEach((d, i)  => d.classList.toggle('active', i === n));
  curSlide = n;
}
setInterval(() => goSlide((curSlide + 1) % 3), 5000);

// ===== TOAST =====
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ===== CARBON CALCULATOR =====
const EMISSION_FACTORS = {
  transport: 0.15, // Rata-rata motor/mobil per km
  elec:      0.87, // per kWh
  food:      3.0,  // porsi daging
  shop:      0.5   // per item
};

async function calcCarbon() {
  if (!currentUser) {
    showToast('Silakan masuk (login) terlebih dahulu untuk menghitung dan menyimpan emisimu.');
    openModal('login');
    return;
  }

  const t = parseFloat(document.getElementById('cb-transport').value) || 0;
  const e = parseFloat(document.getElementById('cb-elec').value) || 0;
  const f = parseFloat(document.getElementById('cb-food').value) || 0;
  const s = parseFloat(document.getElementById('cb-shop').value) || 0;

  const resT = t * EMISSION_FACTORS.transport;
  const resE = e * EMISSION_FACTORS.elec;
  const resF = f * EMISSION_FACTORS.food;
  const resS = s * EMISSION_FACTORS.shop;
  const total = resT + resE + resF + resS;

  renderCbResult(total, resT, resE, resF, resS);
  
  // Slide to result
  document.getElementById('cb-track').style.transform = 'translateX(-50%)';

  await saveCarbonLog({ transport: resT, electricity: resE, food: resF, shopping: resS });
  loadCarbonHistory();
}

function resetCarbon() {
  document.getElementById('cb-transport').value = '';
  document.getElementById('cb-elec').value = '';
  document.getElementById('cb-food').value = '';
  document.getElementById('cb-shop').value = '';
  document.getElementById('cb-track').style.transform = 'translateX(0)';
}

function renderCbResult(total, t, e, f, s) {
  document.getElementById('cb-total-val').textContent = total.toFixed(1);
  
  const comp = document.getElementById('cb-compare');
  const avg = 7.5; // Rata-rata Indonesia per hari
  if (total === 0) comp.textContent = 'Isi form dulu yuk!';
  else if (total < avg) comp.innerHTML = '🎉 Hebat! Emisimu <strong>di bawah</strong> rata-rata orang Indonesia (' + avg + ' kg).';
  else comp.innerHTML = '⚠️ Emisimu <strong>di atas</strong> rata-rata orang Indonesia (' + avg + ' kg). Yuk kurangi!';

  const max = Math.max(total, 0.1); // Cegah div by 0
  document.getElementById('cb-bar-t').style.width = (t / max * 100) + '%';
  document.getElementById('cb-bar-e').style.width = (e / max * 100) + '%';
  document.getElementById('cb-bar-f').style.width = (f / max * 100) + '%';
  document.getElementById('cb-bar-s').style.width = (s / max * 100) + '%';
}

async function saveCarbonLog(data) {
  const res = await apiFetch('carbon.php', 'POST', data);
  if (res.success) {
    showToast(`Berhasil menyimpan emisi karbon! +${res.xp_gained} XP 🌿`);
    currentUser.xp = res.new_xp;
    updateUI();
  }
}

async function loadCarbonHistory() {
  if (!currentUser) {
    document.getElementById('cb-history-panel').style.display = 'none';
    return;
  }
  const res = await apiFetch('carbon.php');
  if (res.success && res.logs.length > 0) {
    document.getElementById('cb-history-panel').style.display = 'block';
    
    // Format tanggal manual jika tidak didukung toLocaleDateString lokal yang baik
    const formatDate = (dateStr) => {
      const d = new Date(dateStr);
      return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    document.getElementById('cb-last-date').textContent = formatDate(res.logs[0].created_at);
    
    document.getElementById('cb-history-list').innerHTML = res.logs.map(log => `
      <div class="cb-history-item">
        <span class="cb-date">${formatDate(log.created_at)}</span>
        <span class="cb-val">${parseFloat(log.total).toFixed(1)} kg</span>
      </div>
    `).join('');
  } else {
    document.getElementById('cb-history-panel').style.display = 'none';
  }
}

// ===== ARTICLES / GREENSTEPS INFO =====
let articlesData = [];

async function renderArticles() {
  const res = await apiFetch('articles.php');
  articlesData = res.articles || [];
  const grid = document.getElementById('artikel-grid');
  const isAdmin = currentUser && currentUser.role === 'admin';

  if (!articlesData.length) {
    grid.innerHTML = '<p style="color:#7a9a50;font-size:0.875rem">Belum ada artikel.</p>';
    return;
  }

  grid.innerHTML = articlesData.map((a, i) => {
    const isFeatured = i === 0; // Artikel pertama dibuat lebih besar
    const dateStr = new Date(a.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    return `<div class="a-card ${isFeatured ? 'featured ' : ''}card-hover">
      <div class="a-thumb ${isFeatured ? 'big' : ''}"><img src="${a.image_url}" alt="${a.title}" loading="lazy"></div>
      <div class="a-body">
        <p class="a-date">${dateStr}</p>
        <h3>${a.title}</h3>
        <p style="white-space: pre-wrap;">${a.excerpt || a.content.substring(0, 100) + '...'}</p>
        <span class="read-more" onclick="openReadArticle(${a.id})">Baca Selengkapnya →</span>
        ${isAdmin ? `
          <div class="admin-btns" style="margin-top:1rem">
            <button class="btn-edit-prog" onclick="openEditArticle(${a.id})">✏️ Edit</button>
            <button class="btn-del-prog" onclick="deleteArticle(${a.id})">🗑️ Hapus</button>
          </div>
        ` : ''}
      </div>
    </div>`;
  }).join('');
}

function openReadArticle(id) {
  const a = articlesData.find(x => x.id === id);
  if (!a) return;
  document.getElementById('ra-img').src = a.image_url;
  document.getElementById('ra-title').textContent = a.title;
  document.getElementById('ra-date').textContent = new Date(a.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
  document.getElementById('ra-content').textContent = a.content;
  openModal('read-article');
}

async function doAddArticle() {
  const title = document.getElementById('aa-title').value.trim();
  const excerpt = document.getElementById('aa-excerpt').value.trim();
  const content = document.getElementById('aa-content').value.trim();
  const image_url = document.getElementById('aa-image').value.trim() || 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80';

  if (!title || !content) { showToast('Judul dan isi artikel wajib diisi!'); return; }

  const res = await apiFetch('articles.php', 'POST', { title, excerpt, content, image_url });
  if (!res.success) { showToast(res.message || 'Gagal menyimpan.'); return; }
  
  ['aa-title','aa-excerpt','aa-content','aa-image'].forEach(id => document.getElementById(id).value = '');
  closeModal('add-article');
  showToast('Artikel berhasil ditambahkan! 🎉');
  renderArticles();
}

let editingArtId = null;
function openEditArticle(id) {
  const a = articlesData.find(x => x.id === id);
  if (!a) return;
  editingArtId = a.id;
  document.getElementById('ea-title').value = a.title;
  document.getElementById('ea-excerpt').value = a.excerpt || '';
  document.getElementById('ea-content').value = a.content || '';
  document.getElementById('ea-image').value = a.image_url || '';
  openModal('edit-article');
}

async function doEditArticle() {
  const title = document.getElementById('ea-title').value.trim();
  const excerpt = document.getElementById('ea-excerpt').value.trim();
  const content = document.getElementById('ea-content').value.trim();
  const image_url = document.getElementById('ea-image').value.trim() || 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80';

  if (!title || !content) { showToast('Judul dan isi artikel wajib diisi!'); return; }

  const res = await apiFetch(`articles.php?id=${editingArtId}`, 'PUT', { title, excerpt, content, image_url });
  if (!res.success) { showToast(res.message || 'Gagal menyimpan.'); return; }
  
  closeModal('edit-article');
  showToast('Artikel berhasil diperbarui! ✅');
  renderArticles();
}

async function deleteArticle(id) {
  if (!confirm('Hapus artikel ini? Tindakan ini tidak bisa dibatalkan.')) return;
  const res = await apiFetch(`articles.php?id=${id}`, 'DELETE');
  if (!res.success) { showToast(res.message || 'Gagal menghapus.'); return; }
  showToast('Artikel berhasil dihapus.');
  renderArticles();
}

// ===== INIT — Cek session PHP yang masih aktif =====
async function init() {
  const res = await apiFetch('auth.php?action=me');
  if (res.success && res.user) {
    currentUser = res.user;
  }
  updateUI();
  renderCampaigns();
  renderArticles();
  loadCarbonHistory();
}

init();
