/* TenureX — Tenant section page logic */

let _tenantUser = null;

document.addEventListener('DOMContentLoaded', async () => {
  _tenantUser = await checkAuth(['tenant']);
  if (!_tenantUser) return;

  const path = window.location.pathname;

  if (path.includes('dashboard'))               initTenantDashboard();
  if (path.includes('browse-property'))         initBrowse();
  if (path.includes('myApplication'))           initMyApplications();
  if (path.includes('myRental'))                initMyRental();
  if (path.includes('maintenance') && !path.includes('status')) initTenantMaintenance();
  if (path.includes('maintenance-status'))      initMaintenanceTracker();
  if (path.includes('messages'))                initTenantMessages();
});

/* ── DASHBOARD ─────────────────────────────── */
async function initTenantDashboard() {
  const [leaseRes, payRes, mrRes] = await Promise.all([
    apiFetch('/tenant/lease.php'),
    apiFetch('/tenant/payments.php'),
    apiFetch('/tenant/maintenance.php'),
  ]);

  if (leaseRes.success && leaseRes.data) {
    const l = leaseRes.data;
    setEl('dash-property',   l.property_name);
    setEl('dash-unit',       l.unit_label);
    setEl('dash-rent',       formatMoney(l.monthly_rent));
    setEl('dash-lease-end',  formatDate(l.end_date));
    setEl('dash-landlord',   l.landlord_name);
    if (l.next_payment) setEl('dash-next-due', formatDate(l.next_payment.due_date));
  }
  if (payRes.success) setEl('dash-payments-count', payRes.data.filter(p => p.status==='paid').length);
  if (mrRes.success)  setEl('dash-mr-count', mrRes.data.length);
}

/* ── BROWSE PROPERTIES ──────────────────────── */
async function initBrowse() {
  const container = document.getElementById('propertiesGrid') || document.getElementById('browseGrid');
  const searchForm = document.getElementById('searchForm') || document.getElementById('filterForm');

  async function load(params = '') {
    if (!container) return;
    container.innerHTML = '<p class="col-span-full text-center py-8 text-gray-400">Loading properties…</p>';
    const res = await apiFetch('/tenant/browse.php' + (params ? '?' + params : ''));
    if (!res.success) { container.innerHTML = `<p class="col-span-full text-red-500">${res.error}</p>`; return; }
    if (!res.data.length) { container.innerHTML = '<p class="col-span-full text-center py-8 text-gray-400">No available units found.</p>'; return; }

    container.innerHTML = res.data.map(u => `
      <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
        <div class="h-44 bg-gray-100">
          ${u.cover_image_url ? `<img src="${h(u.cover_image_url)}" class="w-full h-full object-cover" alt="">` : '<div class="w-full h-full flex items-center justify-center"><svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></div>'}
        </div>
        <div class="p-5">
          <h3 class="font-semibold text-gray-900">${h(u.property_name)}</h3>
          <p class="text-gray-500 text-sm">${h(u.city)} · ${h(u.unit_label)}</p>
          <div class="flex gap-3 mt-2 text-sm text-gray-600">
            <span>${u.bedrooms} bed</span>
            <span>${u.bathrooms} bath</span>
            ${u.area_sqft ? `<span>${u.area_sqft} sqft</span>` : ''}
          </div>
          <p class="text-xl font-bold text-gray-900 mt-2">${formatMoney(u.monthly_rent)}<span class="text-sm font-normal text-gray-500">/mo</span></p>
          <button onclick="applyForUnit(${u.unit_id},'${h(u.property_name)}','${h(u.unit_label)}')" class="mt-4 w-full px-4 py-2 bg-black text-white rounded-xl text-sm font-medium hover:bg-gray-800">Apply Now</button>
        </div>
      </div>
    `).join('');
  }

  load();

  if (searchForm) {
    searchForm.addEventListener('submit', e => {
      e.preventDefault();
      const city    = document.getElementById('searchCity')?.value?.trim() || '';
      const minRent = document.getElementById('minRent')?.value || 0;
      const maxRent = document.getElementById('maxRent')?.value || 999999;
      load(`city=${encodeURIComponent(city)}&min_rent=${minRent}&max_rent=${maxRent}`);
    });
  }

  window.applyForUnit = (unitId, propName, unitLabel) => {
    // Show apply modal or navigate to application page
    const modal = document.getElementById('applyModal');
    if (modal) {
      document.getElementById('modalPropName').textContent  = propName;
      document.getElementById('modalUnitLabel').textContent = unitLabel;
      document.getElementById('applyUnitId').value = unitId;
      modal.classList.remove('hidden');
    } else {
      // Navigate with query param
      window.location.href = `myApplication.html?apply=${unitId}&prop=${encodeURIComponent(propName)}&unit=${encodeURIComponent(unitLabel)}`;
    }
  };

  // Apply modal submit
  const applyForm = document.getElementById('applyForm');
  if (applyForm) {
    applyForm.addEventListener('submit', async e => {
      e.preventDefault();
      const unitId = document.getElementById('applyUnitId')?.value;
      const res = await apiFetch('/tenant/applications.php', {
        method: 'POST',
        body: {
          unit_id: parseInt(unitId),
          occupation:       document.getElementById('occupation')?.value?.trim() || '',
          profile_summary:  document.getElementById('profileSummary')?.value?.trim() || '',
          requested_move_in:document.getElementById('moveInDate')?.value || '',
          lease_months:     parseInt(document.getElementById('leaseMonths')?.value || 12),
        },
      });
      if (res.success) { showToast('Application submitted!'); document.getElementById('applyModal')?.classList?.add('hidden'); }
      else showToast(res.error, 'error');
    });

    document.getElementById('closeApplyModal')?.addEventListener('click', () => {
      document.getElementById('applyModal')?.classList?.add('hidden');
    });
  }
}

/* ── MY APPLICATIONS ────────────────────────── */
async function initMyApplications() {
  const container = document.getElementById('applicationsGrid') || document.getElementById('appList');

  // Handle direct apply from browse page
  const params = new URLSearchParams(window.location.search);
  const applyId = params.get('apply');
  if (applyId) {
    const modal = document.getElementById('applyModal');
    if (modal) {
      document.getElementById('applyUnitId').value = applyId;
      if (params.get('prop'))  document.getElementById('modalPropName').textContent  = params.get('prop');
      if (params.get('unit'))  document.getElementById('modalUnitLabel').textContent = params.get('unit');
      modal.classList.remove('hidden');
    }
  }

  if (!container) return;
  container.innerHTML = '<p class="text-gray-400">Loading…</p>';
  const res = await apiFetch('/tenant/applications.php');
  if (!res.success) { container.innerHTML = `<p class="text-red-500">${res.error}</p>`; return; }
  if (!res.data.length) { container.innerHTML = '<p class="text-gray-400 text-center py-8">No applications yet. <a href="browse-property.html" class="text-black underline">Browse properties</a></p>'; return; }

  container.innerHTML = res.data.map(a => `
    <div class="bg-white border border-gray-100 rounded-2xl p-5">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="font-semibold text-gray-900">${h(a.property_name)}</h3>
          <p class="text-gray-500 text-sm">${h(a.unit_label)} · ${formatMoney(a.monthly_rent)}/mo</p>
          <p class="text-gray-500 text-sm">${h(a.city)}</p>
        </div>
        ${statusBadge(a.status)}
      </div>
      <p class="text-xs text-gray-400 mt-3">Applied ${formatDate(a.applied_at)}</p>
    </div>
  `).join('');
}

/* ── MY RENTAL / LEASE ──────────────────────── */
async function initMyRental() {
  const res = await apiFetch('/tenant/lease.php');
  if (!res.success) return;

  if (!res.data) {
    document.getElementById('noLeaseMsg')?.classList?.remove('hidden');
    document.getElementById('leaseContent')?.classList?.add('hidden');
    return;
  }

  const l = res.data;
  setEl('lease-property',  l.property_name);
  setEl('lease-address',   l.address_line + ', ' + l.city);
  setEl('lease-unit',      l.unit_label);
  setEl('lease-rent',      formatMoney(l.monthly_rent));
  setEl('lease-start',     formatDate(l.start_date));
  setEl('lease-end',       formatDate(l.end_date));
  setEl('lease-bedrooms',  l.bedrooms);
  setEl('lease-bathrooms', l.bathrooms);
  setEl('lease-area',      l.area_sqft ? l.area_sqft + ' sqft' : '—');
  setEl('lease-landlord',  l.landlord_name);
  setEl('lease-landlord-email', l.landlord_email);

  if (l.next_payment) {
    setEl('next-due-amount', formatMoney(l.next_payment.amount));
    setEl('next-due-date',   formatDate(l.next_payment.due_date));
  }

  // Payment history table
  const tbody = document.getElementById('payHistoryBody');
  if (tbody && l.payments) {
    tbody.innerHTML = l.payments.map(p => `
      <tr class="border-b border-gray-100">
        <td class="px-4 py-3">${formatDate(p.due_date)}</td>
        <td class="px-4 py-3 font-semibold">${formatMoney(p.amount)}</td>
        <td class="px-4 py-3 text-gray-500">${formatDate(p.paid_at) || '—'}</td>
        <td class="px-4 py-3">${statusBadge(p.status)}</td>
        <td class="px-4 py-3 text-gray-500 text-xs">${h(p.reference || '—')}</td>
      </tr>
    `).join('') || '<tr><td colspan="5" class="text-center py-6 text-gray-400">No payments yet.</td></tr>';
  }

  // Pay now button
  const payBtn = document.getElementById('payNowBtn');
  if (payBtn && l.next_payment) {
    payBtn.addEventListener('click', async () => {
      const res2 = await apiFetch('/tenant/payments.php', {
        method: 'POST',
        body: { payment_id: l.next_payment.id, method: 'card' },
      });
      if (res2.success) {
        showToast('Payment successful! Ref: ' + res2.data.reference);
        document.getElementById('paySuccessModal')?.classList?.remove('hidden');
        initMyRental();
      } else showToast(res2.error, 'error');
    });
  }

  document.getElementById('closePayModal')?.addEventListener('click', () => {
    document.getElementById('paySuccessModal')?.classList?.add('hidden');
  });
}

/* ── MAINTENANCE ────────────────────────────── */
async function initTenantMaintenance() {
  const tbody = document.getElementById('mrTableBody') || document.getElementById('maintenanceList');
  const form  = document.getElementById('mrForm') || document.getElementById('maintenanceForm');

  async function loadRequests() {
    if (!tbody) return;
    const res = await apiFetch('/tenant/maintenance.php');
    if (!res.success) return;
    tbody.innerHTML = res.data.map(m => `
      <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer" onclick="window.location.href='maintenance-status-tracker.html?id=${m.id}'">
        <td class="px-4 py-3 font-medium">${h(m.title)}</td>
        <td class="px-4 py-3 text-gray-600">${h(m.category)}</td>
        <td class="px-4 py-3">${statusBadge(m.priority)}</td>
        <td class="px-4 py-3">${statusBadge(m.status)}</td>
        <td class="px-4 py-3 text-gray-500 text-sm">${formatDate(m.reported_on)}</td>
      </tr>
    `).join('') || '<tr><td colspan="5" class="text-center py-8 text-gray-400">No maintenance requests yet.</td></tr>';
  }
  loadRequests();

  if (form) {
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const body = {
        title:       document.getElementById('mrTitle')?.value?.trim(),
        description: document.getElementById('mrDesc')?.value?.trim() || '',
        category:    document.getElementById('mrCategory')?.value || 'other',
        priority:    document.getElementById('mrPriority')?.value || 'medium',
      };
      if (!body.title) return showToast('Title is required', 'error');
      const res = await apiFetch('/tenant/maintenance.php', { method: 'POST', body });
      if (res.success) { showToast('Maintenance request submitted'); form.reset(); loadRequests(); document.getElementById('mrModal')?.classList?.add('hidden'); }
      else showToast(res.error, 'error');
    });
  }
}

/* ── MAINTENANCE STATUS TRACKER ─────────────── */
async function initMaintenanceTracker() {
  const params = new URLSearchParams(window.location.search);
  const id     = params.get('id');
  if (!id) return;

  const res = await apiFetch(`/tenant/maintenance.php?id=${id}`);
  if (!res.success || !res.data) return;
  const m = res.data;

  setEl('mr-title',       m.title);
  setEl('mr-description', m.description || '—');
  setEl('mr-category',    m.category);
  setEl('mr-priority',    m.priority);
  setEl('mr-status',      m.status);
  setEl('mr-reported',    formatDate(m.reported_on));
  setEl('mr-property',    m.property_name);
  setEl('mr-unit',        m.unit_label || '—');
  setEl('mr-tech',        m.tech_name || 'Not assigned');
  setEl('mr-scheduled',   formatDate(m.scheduled_for) || '—');

  const badge = document.getElementById('mr-status-badge');
  if (badge) badge.innerHTML = statusBadge(m.status);
}

/* ── MESSAGES ───────────────────────────────── */
async function initTenantMessages() {
  // Same pattern as landlord messages
  const threadList = document.getElementById('threadList');
  const chatArea   = document.getElementById('chatMessages');
  const msgForm    = document.getElementById('msgForm') || document.getElementById('sendMessageForm');

  let currentThread = null;

  async function loadThreads() {
    if (!threadList) return;
    const res = await apiFetch('/messages/threads.php');
    if (!res.success) return;
    threadList.innerHTML = res.data.map(t => `
      <div class="cursor-pointer p-4 hover:bg-gray-50 border-b border-gray-100" onclick="openThread(${t.id})">
        <div class="flex justify-between">
          <span class="font-semibold text-sm text-gray-900">${h(t.participants || 'Unknown')}</span>
          <span class="text-xs text-gray-400">${formatDate(t.last_message_at)}</span>
        </div>
        <p class="text-xs text-gray-500 mt-1 truncate">${h(t.last_message || '')}</p>
      </div>
    `).join('') || '<p class="p-4 text-gray-400 text-sm">No conversations yet.</p>';
  }

  loadThreads();

  window.openThread = async (id) => {
    currentThread = id;
    const res = await apiFetch(`/messages/send.php?thread_id=${id}`);
    if (!res.success || !chatArea) return;
    chatArea.innerHTML = res.data.map(m => `
      <div class="flex ${m.sender_id == _tenantUser?.id ? 'justify-end' : 'justify-start'} mb-3">
        <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-2xl text-sm ${m.sender_id == _tenantUser?.id ? 'bg-black text-white' : 'bg-gray-100 text-gray-800'}">
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
      if (res.success) { input.value = ''; openThread(currentThread); }
      else showToast(res.error, 'error');
    });
  }
}

/* ── HELPERS ────────────────────────────────── */
function setEl(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function h(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
