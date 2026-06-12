/* TenureX — Landlord section page logic */

let _landlordUser = null;

document.addEventListener('DOMContentLoaded', async () => {
  _landlordUser = await checkAuth(['landlord']);
  if (!_landlordUser) return;

  const path = window.location.pathname;

  if (path.includes('dashboard'))          initLandlordDashboard();
  if (path.includes('my-properties'))      initMyProperties();
  if (path.includes('add-property'))       initAddProperty();
  if (path.includes('edit-property'))      initEditProperty();
  if (path.includes('rental-requests'))    initRentalRequests();
  if (path.includes('rent-tracking'))      initRentTracking();
  if (path.includes('maintenance'))        initMaintenance();
  if (path.includes('technician-request')) initTechDispatch();
  if (path.includes('request-technician-approval')) initTechApproval();
  if (path.includes('messages'))           initMessages();
  if (path.includes('active-tenants'))     initActiveTenants();
  if (path.includes('settings'))           initLandlordSettings();
});

/* ── SETTINGS ────────────────────────────────── */
function initLandlordSettings() {
  // Personal identity comes from the logged-in session user
  const nameEl  = document.getElementById('settingsName');
  const emailEl = document.getElementById('settingsEmail');
  if (nameEl)  nameEl.value  = _landlordUser.name  || '';
  if (emailEl) emailEl.value = _landlordUser.email || '';
}

/* ── DASHBOARD ────────────────────────────────── */
async function initLandlordDashboard() {
  const [propsRes, reqRes, payRes, mrRes] = await Promise.all([
    apiFetch('/landlord/properties.php'),
    apiFetch('/landlord/rental-requests.php?status=pending'),
    apiFetch('/landlord/payments.php'),
    apiFetch('/landlord/maintenance.php?status=open'),
  ]);

  if (propsRes.success) {
    setEl('stat-properties', propsRes.data.length);
  }
  if (reqRes.success) {
    setEl('stat-requests', reqRes.data.length);
    // populate recent requests table
    const tbody = document.getElementById('dash-req-body');
    if (tbody) {
      tbody.innerHTML = reqRes.data.slice(0,5).map(r => `
        <tr class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-6 py-3">${h(r.applicant_name)}</td>
          <td class="px-6 py-3 text-gray-500">${h(r.property_name)}</td>
          <td class="px-6 py-3">${statusBadge(r.status)}</td>
        </tr>
      `).join('') || '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-400">No pending requests</td></tr>';
    }
  }
  if (mrRes.success) {
    setEl('stat-maintenance', mrRes.data.length);
    // populate recent maintenance table
    const tbody = document.getElementById('dash-maint-body');
    if (tbody) {
      tbody.innerHTML = mrRes.data.slice(0,5).map(m => `
        <tr class="border-b border-gray-100 hover:bg-gray-50">
          <td class="px-6 py-3">${h(m.title)}</td>
          <td class="px-6 py-3 text-gray-500">${h(m.unit_label || m.property_name)}</td>
          <td class="px-6 py-3">${statusBadge(m.status)}</td>
        </tr>
      `).join('') || '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-400">No maintenance issues</td></tr>';
    }
  }
  if (payRes.success) {
    // count unique active tenants from payments
    const seen = new Set((payRes.data.payments||[]).map(p => p.tenant_email).filter(Boolean));
    setEl('stat-tenants', seen.size);
  }
}

/* ── MY PROPERTIES ───────────────────────────── */
async function initMyProperties() {
  const container = document.getElementById('propertiesGrid') || document.getElementById('propertiesList');
  if (!container) return;

  const res = await apiFetch('/landlord/properties.php');
  if (!res.success) { container.innerHTML = `<p class="text-red-500">${res.error}</p>`; return; }

  if (!res.data.length) {
    container.innerHTML = '<div class="col-span-full text-center py-16 text-gray-400"><p class="text-lg">No properties yet.</p><a href="add-property.html" class="mt-3 inline-block px-5 py-2 bg-black text-white rounded-lg text-sm">Add Property</a></div>';
    return;
  }

  container.innerHTML = res.data.map(p => `
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
      <div class="h-44 bg-gray-100 relative">
        ${p.cover_image_url ? `<img src="${h(p.cover_image_url)}" class="w-full h-full object-cover" alt="">` : '<div class="w-full h-full flex items-center justify-center text-gray-300"><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></div>'}
        <span class="absolute top-3 right-3">${statusBadge(p.status)}</span>
      </div>
      <div class="p-5">
        <h3 class="font-semibold text-gray-900 text-lg">${h(p.name)}</h3>
        <p class="text-gray-500 text-sm mt-1">${h(p.city)}</p>
        <div class="flex gap-4 mt-3 text-sm text-gray-600">
          <span>${p.unit_count || 0} units</span>
          <span class="text-green-600">${p.occupied_count || 0} occupied</span>
        </div>
        <p class="text-xl font-bold text-gray-900 mt-2">${formatMoney(p.monthly_value)}<span class="text-sm font-normal text-gray-500">/mo</span></p>
        <div class="flex gap-2 mt-4">
          <a href="add-property.html?edit=${p.id}" class="flex-1 text-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Edit</a>
          <button onclick="deleteProperty(${p.id})" class="px-3 py-2 bg-red-50 text-red-600 rounded-lg text-sm hover:bg-red-100">Delete</button>
        </div>
      </div>
    </div>
  `).join('');

  window.deleteProperty = async id => {
    if (!confirm('Delete this property and all its units?')) return;
    const res = await apiFetch(`/landlord/properties.php?id=${id}`, { method: 'DELETE' });
    if (res.success) { showToast('Property deleted'); initMyProperties(); }
    else showToast(res.error, 'error');
  };
}

/* ── ADD / EDIT PROPERTY ─────────────────────── */
async function initAddProperty() {
  const form = document.getElementById('addPropertyForm') || document.getElementById('propertyForm');
  if (!form) return;

  // Pre-fill for edit mode
  const params = new URLSearchParams(window.location.search);
  const editId = params.get('edit');
  if (editId) {
    document.querySelectorAll('h1,h2').forEach(el => { if (el.textContent.toLowerCase().includes('add')) el.textContent = el.textContent.replace(/add/i,'Edit'); });
    const res = await apiFetch(`/landlord/properties.php?id=${editId}`);
    if (res.success) {
      const p = res.data;
      const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
      set('propName', p.name); set('propAddress', p.address_line); set('propCity', p.city);
      set('propMonthly', p.monthly_value); set('propUnits', p.total_units);
      set('propImage', p.cover_image_url); set('propStatus', p.status);
    }
  }

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const body = {
      name:           document.getElementById('propName')?.value?.trim(),
      address_line:   document.getElementById('propAddress')?.value?.trim(),
      city:           document.getElementById('propCity')?.value?.trim(),
      monthly_value:  document.getElementById('propMonthly')?.value,
      total_units:    document.getElementById('propUnits')?.value,
      cover_image_url:document.getElementById('propImage')?.value?.trim() || '',
      status:         document.getElementById('propStatus')?.value || 'active',
    };
    if (!body.name || !body.address_line || !body.city) return showToast('Name, address and city are required','error');

    const url    = editId ? `/landlord/properties.php?id=${editId}` : '/landlord/properties.php';
    const method = editId ? 'PUT' : 'POST';
    const res    = await apiFetch(url, { method, body });
    if (res.success) { showToast(editId ? 'Property updated' : 'Property added'); setTimeout(() => window.location.href = 'my-properties.html', 1000); }
    else showToast(res.error, 'error');
  });
}

function initEditProperty() { initAddProperty(); }

/* ── RENTAL REQUESTS ─────────────────────────── */
async function initRentalRequests() {
  const tbody = document.getElementById('requestsTableBody') || document.getElementById('rentalReqBody');
  if (!tbody) return;

  async function load(status = null) {
    const url = status ? `/landlord/rental-requests.php?status=${status}` : '/landlord/rental-requests.php';
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">Loading…</td></tr>';
    const res = await apiFetch(url);
    if (!res.success) { tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-red-500">${res.error}</td></tr>`; return; }
    if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">No requests found.</td></tr>'; return; }

    tbody.innerHTML = res.data.map(r => `
      <tr class="border-b border-gray-100 hover:bg-gray-50">
        <td class="px-4 py-3 font-medium text-gray-900">${h(r.applicant_name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(r.applicant_email)}</td>
        <td class="px-4 py-3 text-gray-600">${h(r.property_name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(r.unit_label)}</td>
        <td class="px-4 py-3 font-semibold">${formatMoney(r.monthly_rent)}</td>
        <td class="px-4 py-3">${statusBadge(r.status)}</td>
        <td class="px-4 py-3">
          ${r.status === 'pending' || r.status === 'in_review' ? `
            <button onclick="updateRequest(${r.id},'accept')" class="px-3 py-1 bg-black text-white text-xs rounded-lg mr-1">Accept</button>
            <button onclick="updateRequest(${r.id},'reject')" class="px-3 py-1 bg-red-50 text-red-600 text-xs rounded-lg">Reject</button>
          ` : '<span class="text-gray-400 text-xs">Decided</span>'}
        </td>
      </tr>
    `).join('');
  }

  load();

  document.querySelectorAll('[data-req-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-req-filter]').forEach(b => b.classList.remove('bg-black','text-white'));
      btn.classList.add('bg-black','text-white');
      load(btn.dataset.reqFilter === 'all' ? null : btn.dataset.reqFilter);
    });
  });

  window.updateRequest = async (id, action) => {
    const res = await apiFetch(`/landlord/rental-requests.php?id=${id}`, { method: 'PATCH', body: { action } });
    if (res.success) { showToast(`Request ${action}ed`); load(); }
    else showToast(res.error, 'error');
  };
}

/* ── RENT TRACKING ───────────────────────────── */
async function initRentTracking() {
  const tbody = document.getElementById('paymentsTableBody') || document.getElementById('rentTableBody');
  const res   = await apiFetch('/landlord/payments.php');
  if (!res.success) return;

  const { payments, stats } = res.data;
  setEl('stat-collected', formatMoney(stats?.total_collected));
  setEl('stat-pending-pay', stats?.pending_count);
  setEl('stat-overdue', stats?.overdue_count);

  if (tbody && payments) {
    tbody.innerHTML = payments.map(p => `
      <tr class="border-b border-gray-100 hover:bg-gray-50">
        <td class="px-4 py-3 font-medium">${h(p.tenant_name || '—')}</td>
        <td class="px-4 py-3 text-gray-600">${h(p.property_name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(p.unit_label)}</td>
        <td class="px-4 py-3 font-semibold">${formatMoney(p.amount)}</td>
        <td class="px-4 py-3 text-gray-600">${formatDate(p.due_date)}</td>
        <td class="px-4 py-3 text-gray-600">${formatDate(p.paid_at) || '—'}</td>
        <td class="px-4 py-3">${statusBadge(p.status)}</td>
      </tr>
    `).join('') || '<tr><td colspan="7" class="text-center py-8 text-gray-400">No payments yet.</td></tr>';
  }
}

/* ── MAINTENANCE ─────────────────────────────── */
async function initMaintenance() {
  const tbody = document.getElementById('maintenanceTableBody') || document.getElementById('mrBody');
  if (!tbody) return;

  async function load() {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">Loading…</td></tr>';
    const res = await apiFetch('/landlord/maintenance.php');
    if (!res.success) { tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-500">${res.error}</td></tr>`; return; }
    if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">No maintenance requests.</td></tr>'; return; }

    tbody.innerHTML = res.data.map(m => `
      <tr class="border-b border-gray-100 hover:bg-gray-50">
        <td class="px-4 py-3 font-medium">${h(m.title)}</td>
        <td class="px-4 py-3 text-gray-600">${h(m.property_name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(m.unit_label || '—')}</td>
        <td class="px-4 py-3">${statusBadge(m.priority)}</td>
        <td class="px-4 py-3">${statusBadge(m.status)}</td>
        <td class="px-4 py-3 text-gray-500 text-sm">${formatDate(m.reported_on)}</td>
      </tr>
    `).join('');
  }
  load();
}

/* ── ACTIVE TENANTS ──────────────────────────── */
async function initActiveTenants() {
  const tbody = document.getElementById('tenantsTableBody');
  if (!tbody) return;
  const res = await apiFetch('/landlord/payments.php');
  if (!res.success) return;

  const seen = new Set();
  const tenants = (res.data.payments || []).filter(p => {
    if (seen.has(p.tenant_email)) return false;
    seen.add(p.tenant_email);
    return true;
  });

  tbody.innerHTML = tenants.map(t => `
    <tr class="border-b border-gray-100 hover:bg-gray-50">
      <td class="px-4 py-3 font-medium">${h(t.tenant_name || '—')}</td>
      <td class="px-4 py-3 text-gray-600">${h(t.tenant_email || '—')}</td>
      <td class="px-4 py-3 text-gray-600">${h(t.property_name)}</td>
      <td class="px-4 py-3 text-gray-600">${h(t.unit_label)}</td>
      <td class="px-4 py-3 font-semibold">${formatMoney(t.monthly_rent)}</td>
      <td class="px-4 py-3">${statusBadge('active')}</td>
    </tr>
  `).join('') || '<tr><td colspan="6" class="text-center py-8 text-gray-400">No active tenants yet.</td></tr>';
}

/* ── TECHNICIAN DISPATCH ─────────────────────── */
async function initTechDispatch() {
  // Load technicians list for dropdown
  const techSelect = document.getElementById('technicianId');
  if (techSelect) {
    // We'll need a public endpoint for technicians — use maintenance API response
    techSelect.innerHTML = '<option value="">Select technician…</option>';
    // For now provide a generic option; full implementation would have a /landlord/technicians endpoint
  }
}

/* ── TECHNICIAN APPROVAL FORM ────────────────── */
async function initTechApproval() {
  // Load previous submissions
  const tbody = document.getElementById('submissionsTableBody');
  async function loadSubmissions() {
    if (!tbody) return;
    const res = await apiFetch('/landlord/technician-approval.php');
    if (!res.success) return;
    tbody.innerHTML = res.data.map(r => `
      <tr class="border-b border-gray-100">
        <td class="px-4 py-3 font-medium">${h(r.full_name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(r.specialization)}</td>
        <td class="px-4 py-3 text-gray-600">${h(r.phone)}</td>
        <td class="px-4 py-3">${statusBadge(r.status)}</td>
        <td class="px-4 py-3 text-gray-500 text-sm">${formatDate(r.submitted_at)}</td>
      </tr>
    `).join('') || '<tr><td colspan="5" class="text-center py-8 text-gray-400">No submissions yet.</td></tr>';
  }
  loadSubmissions();

  // Wire submission form (already has inline JS; we override it with API call)
  const submitBtn = document.getElementById('submitBtn');
  if (submitBtn) {
    submitBtn.addEventListener('click', async () => {
      const body = {
        full_name:        document.getElementById('techName')?.value?.trim(),
        phone:            document.getElementById('techPhone')?.value?.trim(),
        specialization:   document.getElementById('techSpec')?.value?.trim(),
        nid:              document.getElementById('techNid')?.value?.trim(),
        years_experience: document.getElementById('techExp')?.value,
        note:             document.getElementById('techNote')?.value?.trim(),
      };
      if (!body.full_name || !body.phone || !body.specialization || !body.nid) {
        return showToast('Please fill all required fields', 'error');
      }
      const res = await apiFetch('/landlord/technician-approval.php', { method: 'POST', body });
      if (res.success) {
        document.getElementById('successModal')?.classList?.remove('hidden');
        document.getElementById('techName').value = '';
        document.getElementById('techPhone').value = '';
        document.getElementById('techNote').value = '';
        loadSubmissions();
      } else {
        showToast(res.error, 'error');
      }
    });
  }
}

/* ── MESSAGES ────────────────────────────────── */
async function initMessages() {
  const threadList = document.getElementById('threadList');
  const chatArea   = document.getElementById('chatMessages');
  const msgForm    = document.getElementById('msgForm') || document.getElementById('sendMessageForm');

  let currentThread = null;

  async function loadThreads() {
    if (!threadList) return;
    const res = await apiFetch('/messages/threads.php');
    if (!res.success) return;
    threadList.innerHTML = res.data.map(t => `
      <div class="cursor-pointer p-4 hover:bg-gray-50 border-b border-gray-100 ${t.unread_count > 0 ? 'bg-blue-50' : ''}" onclick="openThread(${t.id},'${h(t.subject)}','${h(t.participants)}')">
        <div class="flex justify-between">
          <span class="font-semibold text-sm text-gray-900">${h(t.participants || 'Unknown')}</span>
          <span class="text-xs text-gray-400">${formatDate(t.last_message_at)}</span>
        </div>
        <p class="text-xs text-gray-500 mt-1 truncate">${h(t.last_message || '')}</p>
        ${t.unread_count > 0 ? `<span class="text-xs bg-black text-white rounded-full px-2">${t.unread_count}</span>` : ''}
      </div>
    `).join('') || '<p class="p-4 text-gray-400 text-sm">No conversations yet.</p>';
  }

  loadThreads();

  window.openThread = async (id, subject, participants) => {
    currentThread = id;
    const res = await apiFetch(`/messages/send.php?thread_id=${id}`);
    if (!res.success || !chatArea) return;

    if (document.getElementById('chatHeader')) document.getElementById('chatHeader').textContent = participants;
    chatArea.innerHTML = res.data.map(m => `
      <div class="flex ${m.sender_id == _landlordUser?.id ? 'justify-end' : 'justify-start'} mb-3">
        <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-2xl text-sm ${m.sender_id == _landlordUser?.id ? 'bg-black text-white' : 'bg-gray-100 text-gray-800'}">
          <p>${h(m.body)}</p>
          <p class="text-xs mt-1 opacity-60">${formatDate(m.sent_at)}</p>
        </div>
      </div>
    `).join('');
    chatArea.scrollTop = chatArea.scrollHeight;
  };

  if (msgForm) {
    msgForm.addEventListener('submit', async e => {
      e.preventDefault();
      if (!currentThread) return showToast('Select a conversation first', 'error');
      const input = msgForm.querySelector('input[type="text"],textarea');
      const msg = input?.value?.trim();
      if (!msg) return;
      const res = await apiFetch(`/messages/send.php?thread_id=${currentThread}`, { method: 'POST', body: { message: msg } });
      if (res.success) { input.value = ''; openThread(currentThread, '', ''); }
      else showToast(res.error, 'error');
    });
  }
}

/* ── HELPERS ─────────────────────────────────── */
function setEl(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function h(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
