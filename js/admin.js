/* TenureX — Admin section page logic */

let _adminUser = null;

document.addEventListener('DOMContentLoaded', async () => {
  _adminUser = await checkAuth(['admin']);
  if (!_adminUser) return;

  const path = window.location.pathname;

  if (path.includes('dashboard'))     initAdminDashboard();
  if (path.includes('landlord-tab'))  initUsersTab('landlord');
  if (path.includes('technian-tab') || path.includes('technician-tab')) initUsersTab('technician');
  if (path.includes('category'))      initCategoriesPage();
  if (path.includes('report-tab') || path.includes('report')) initReports();
  if (path.includes('add-landlord') || path.includes('add-tenant') || path.includes('add-technician')) initRegisterForm();
});

/* ── REGISTER USER (admin creates accounts) ─────── */
async function initRegisterForm() {
  const form = document.getElementById('registerForm');
  if (!form) return;

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const errEl = document.getElementById('regError');
    const okEl  = document.getElementById('regSuccess');
    if (errEl) errEl.classList.add('hidden');
    if (okEl)  okEl.classList.add('hidden');

    const body = {
      full_name: document.getElementById('fullName')?.value?.trim(),
      email:     document.getElementById('email')?.value?.trim(),
      phone:     document.getElementById('phone')?.value?.trim() || '',
      password:  document.getElementById('password')?.value,
      role:      document.getElementById('role')?.value || 'tenant',
    };
    if (!body.full_name || !body.email || !body.password) {
      if (errEl) { errEl.textContent = 'Name, email and password are required.'; errEl.classList.remove('hidden'); }
      return;
    }
    const res = await apiFetch('/auth/register.php', { method: 'POST', body });
    if (res.success) {
      if (okEl) { okEl.textContent = res.message || 'Account created.'; okEl.classList.remove('hidden'); }
      form.reset();
      setTimeout(() => window.history.back(), 1500);
    } else {
      if (errEl) { errEl.textContent = res.error || 'Registration failed.'; errEl.classList.remove('hidden'); }
    }
  });
}

/* ── DASHBOARD ──────────────────────────────────── */
async function initAdminDashboard() {
  const res = await apiFetch('/admin/reports.php');
  if (!res.success) return;
  const d = res.data;

  setEl('stat-properties',  d.total_properties);
  setEl('stat-tenants',     d.total_tenants);
  setEl('stat-landlords',   d.total_landlords);
  setEl('stat-revenue',     formatMoney(d.monthly_revenue));
  setEl('stat-occupancy',   d.occupancy_rate + '%');
  setEl('stat-pending',     d.pending_approvals);
  setEl('stat-maintenance', d.open_maintenance);
}

/* ── USERS TAB (landlord or technician) ─────────── */
async function initUsersTab(role) {
  const tbody = document.getElementById('usersTableBody') || document.getElementById('pendingTableBody');
  if (!tbody) return;

  async function loadUsers(status = 'pending') {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">Loading…</td></tr>';
    const res = await apiFetch(`/admin/users.php?role=${role}&status=${status}`);
    if (!res.success) { tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-500">${res.error}</td></tr>`; return; }
    const users = res.data;

    if (!users.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">No records found.</td></tr>'; return; }

    tbody.innerHTML = users.map(u => `
      <tr class="border-b border-gray-100 hover:bg-gray-50">
        <td class="px-4 py-3 font-medium text-gray-900">${h(u.full_name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(u.email)}</td>
        <td class="px-4 py-3 text-gray-600">${h(u.phone || '—')}</td>
        <td class="px-4 py-3">${statusBadge(u.approval_status)}</td>
        <td class="px-4 py-3 text-gray-500 text-sm">${formatDate(u.created_at)}</td>
        <td class="px-4 py-3">
          ${u.approval_status === 'pending' ? `
            <button onclick="userAction(${u.id},'approve')" class="px-3 py-1 bg-black text-white text-xs rounded-lg mr-1 hover:bg-gray-800">Approve</button>
            <button onclick="userAction(${u.id},'reject')" class="px-3 py-1 bg-red-50 text-red-600 text-xs rounded-lg hover:bg-red-100">Reject</button>
          ` : `<span class="text-gray-400 text-xs">No action</span>`}
        </td>
      </tr>
    `).join('');
  }

  loadUsers();

  // Filter tabs
  document.querySelectorAll('[data-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('bg-black','text-white'));
      btn.classList.add('bg-black','text-white');
      loadUsers(btn.dataset.filter);
    });
  });

  window.userAction = async (id, action) => {
    const res = await apiFetch(`/admin/users.php?id=${id}`, { method: 'PATCH', body: { action } });
    if (res.success) { showToast(`User ${action}d successfully`); loadUsers(); }
    else showToast(res.error, 'error');
  };
}

/* ── CATEGORIES ─────────────────────────────────── */
async function initCategoriesPage() {
  const tbody = document.getElementById('catTableBody');
  const form  = document.getElementById('catForm') || document.getElementById('addCatForm');

  async function loadCats() {
    if (!tbody) return;
    const res = await apiFetch('/admin/categories.php');
    if (!res.success) return;
    tbody.innerHTML = res.data.map(c => `
      <tr class="border-b border-gray-100">
        <td class="px-4 py-3 font-medium">${h(c.name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(c.description || '—')}</td>
        <td class="px-4 py-3">${statusBadge(c.is_active ? 'active' : 'closed')}</td>
        <td class="px-4 py-3">
          <button onclick="deleteCategory(${c.id})" class="px-3 py-1 bg-red-50 text-red-600 text-xs rounded-lg hover:bg-red-100">Delete</button>
        </td>
      </tr>
    `).join('') || '<tr><td colspan="4" class="text-center py-8 text-gray-400">No categories yet.</td></tr>';
  }

  loadCats();

  if (form) {
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const name = (document.getElementById('catName') || document.getElementById('categoryName'))?.value?.trim();
      const desc = (document.getElementById('catDesc') || document.getElementById('categoryDesc'))?.value?.trim() || '';
      if (!name) return showToast('Name is required', 'error');

      const res = await apiFetch('/admin/categories.php', { method: 'POST', body: { name, description: desc } });
      if (res.success) { showToast('Category added'); form.reset(); loadCats(); }
      else showToast(res.error, 'error');
    });
  }

  window.deleteCategory = async id => {
    if (!confirm('Delete this category?')) return;
    const res = await apiFetch(`/admin/categories.php?id=${id}`, { method: 'DELETE' });
    if (res.success) { showToast('Deleted'); loadCats(); }
    else showToast(res.error, 'error');
  };
}

/* ── REPORTS ────────────────────────────────────── */
async function initReports() {
  const res = await apiFetch('/admin/reports.php');
  if (!res.success) return;
  const d = res.data;

  setEl('rep-properties',  d.total_properties);
  setEl('rep-units',       d.total_units);
  setEl('rep-occupied',    d.occupied_units);
  setEl('rep-occupancy',   d.occupancy_rate + '%');
  setEl('rep-revenue',     formatMoney(d.total_revenue));
  setEl('rep-monthly',     formatMoney(d.monthly_revenue));
  setEl('rep-tenants',     d.total_tenants);
  setEl('rep-landlords',   d.total_landlords);
  setEl('rep-maintenance', d.open_maintenance);
  setEl('rep-pending',     d.pending_approvals);

  const tbody = document.getElementById('topPropertiesBody');
  if (tbody) {
    tbody.innerHTML = d.top_properties.map(p => `
      <tr class="border-b border-gray-100">
        <td class="px-4 py-3 font-medium">${h(p.name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(p.city)}</td>
        <td class="px-4 py-3">${p.occupied}/${p.total_units}</td>
        <td class="px-4 py-3 font-semibold">${formatMoney(p.revenue)}</td>
      </tr>
    `).join('') || '<tr><td colspan="4" class="text-center py-6 text-gray-400">No data</td></tr>';
  }
}

/* ── HELPERS ─────────────────────────────────────── */
function setEl(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}
function h(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
