/* TenureX — Technician section page logic */

let _techUser = null;

document.addEventListener('DOMContentLoaded', async () => {
  _techUser = await checkAuth(['technician']);
  if (!_techUser) return;

  const path = window.location.pathname;

  if (path.includes('dashboard'))      initTechDashboard();
  if (path.includes('assigned_tasks') || path.includes('assigned-tasks')) initAssignedTasks();
  if (path.includes('task_details') || path.includes('task-details'))     initTaskDetail();
  if (path.includes('history') || path.includes('service-history'))       initServiceHistory();
  if (path.includes('messages'))       initTechMessages();
});

/* ── DASHBOARD ─────────────────────────────── */
async function initTechDashboard() {
  const [tasksRes, histRes] = await Promise.all([
    apiFetch('/technician/tasks.php'),
    apiFetch('/technician/history.php'),
  ]);

  if (tasksRes.success) {
    const tasks = tasksRes.data;
    setEl('stat-total-tasks',    tasks.length);
    setEl('stat-pending-tasks',  tasks.filter(t => t.status === 'dispatched').length);
    setEl('stat-done-tasks',     tasks.filter(t => t.status === 'completed').length);

    // Recent tasks table
    const tbody = document.getElementById('recentTasksBody');
    if (tbody) {
      tbody.innerHTML = tasks.slice(0, 5).map(t => `
        <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer" onclick="window.location.href='task_details.html?id=${t.id}'">
          <td class="px-4 py-3 font-medium">${h(t.title)}</td>
          <td class="px-4 py-3 text-gray-600">${h(t.property_name)}</td>
          <td class="px-4 py-3">${statusBadge(t.priority)}</td>
          <td class="px-4 py-3">${statusBadge(t.status)}</td>
        </tr>
      `).join('') || '<tr><td colspan="4" class="text-center py-6 text-gray-400">No tasks yet.</td></tr>';
    }
  }

  if (histRes.success) {
    setEl('stat-revenue', formatMoney(histRes.data.stats?.total_revenue));
    setEl('stat-completed', histRes.data.stats?.total_jobs);
  }
}

/* ── ASSIGNED TASKS ─────────────────────────── */
async function initAssignedTasks() {
  const tbody = document.getElementById('tasksTableBody') || document.getElementById('tasksList');
  if (!tbody) return;

  async function load(status = null) {
    const url = status ? `/technician/tasks.php?status=${status}` : '/technician/tasks.php';
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-400">Loading…</td></tr>';
    const res = await apiFetch(url);
    if (!res.success) { tbody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-red-500">${res.error}</td></tr>`; return; }
    if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-400">No tasks found.</td></tr>'; return; }

    tbody.innerHTML = res.data.map(t => `
      <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer" onclick="window.location.href='task_details.html?id=${t.id}'">
        <td class="px-4 py-3 font-medium text-gray-900">${h(t.title)}</td>
        <td class="px-4 py-3 text-gray-600">${h(t.property_name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(t.unit_label || '—')}</td>
        <td class="px-4 py-3">${statusBadge(t.priority)}</td>
        <td class="px-4 py-3">${statusBadge(t.status)}</td>
      </tr>
    `).join('');
  }

  load();

  document.querySelectorAll('[data-task-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-task-filter]').forEach(b => b.classList.remove('bg-black','text-white'));
      btn.classList.add('bg-black','text-white');
      load(btn.dataset.taskFilter === 'all' ? null : btn.dataset.taskFilter);
    });
  });
}

/* ── TASK DETAIL ────────────────────────────── */
async function initTaskDetail() {
  const params = new URLSearchParams(window.location.search);
  const id     = params.get('id');
  if (!id) return;

  const res = await apiFetch(`/technician/tasks.php?id=${id}`);
  if (!res.success || !res.data) { showToast('Task not found', 'error'); return; }
  const t = res.data;

  setEl('task-title',       t.title);
  setEl('task-description', t.description || '—');
  setEl('task-category',    t.category);
  setEl('task-priority',    t.priority);
  setEl('task-property',    t.property_name);
  setEl('task-address',     (t.address_line || '') + (t.city ? ', ' + t.city : ''));
  setEl('task-unit',        t.unit_label || '—');
  setEl('task-landlord',    t.landlord_name);
  setEl('task-landlord-ph', t.landlord_phone || '—');
  setEl('task-scheduled',   formatDate(t.scheduled_for) || 'Not set');
  setEl('task-reporter',    t.reporter_name || '—');

  const badge = document.getElementById('task-status-badge');
  if (badge) badge.innerHTML = statusBadge(t.status);

  // Notes / cost / status update form
  const updateForm = document.getElementById('updateTaskForm') || document.getElementById('taskUpdateForm');
  if (updateForm) {
    const notesEl = document.getElementById('taskNotes'); if (notesEl) notesEl.value = t.notes || '';
    const costEl  = document.getElementById('taskCost');  if (costEl)  costEl.value  = t.cost_estimate || '';
    const statEl  = document.getElementById('taskStatus');if (statEl)  statEl.value  = t.status;

    updateForm.addEventListener('submit', async e => {
      e.preventDefault();
      const body = { status: statEl?.value, notes: notesEl?.value?.trim() || '', cost_estimate: parseFloat(costEl?.value) || 0 };
      const res2 = await apiFetch(`/technician/tasks.php?id=${id}`, { method: 'PATCH', body });
      if (res2.success) { showToast('Task updated'); initTaskDetail(); }
      else showToast(res2.error, 'error');
    });
  }
}

/* ── SERVICE HISTORY ────────────────────────── */
async function initServiceHistory() {
  const tbody = document.getElementById('historyTableBody');
  const res   = await apiFetch('/technician/history.php');
  if (!res.success) return;

  setEl('stat-total-jobs',  res.data.stats.total_jobs);
  setEl('stat-total-earn',  formatMoney(res.data.stats.total_revenue));

  if (tbody) {
    tbody.innerHTML = res.data.jobs.map(j => `
      <tr class="border-b border-gray-100 hover:bg-gray-50">
        <td class="px-4 py-3 font-medium">${h(j.title)}</td>
        <td class="px-4 py-3 text-gray-600">${h(j.property_name)}</td>
        <td class="px-4 py-3 text-gray-600">${h(j.category)}</td>
        <td class="px-4 py-3 font-semibold">${formatMoney(j.cost_estimate)}</td>
        <td class="px-4 py-3 text-gray-500 text-sm">${formatDate(j.created_at)}</td>
      </tr>
    `).join('') || '<tr><td colspan="5" class="text-center py-8 text-gray-400">No completed jobs yet.</td></tr>';
  }
}

/* ── MESSAGES ───────────────────────────────── */
async function initTechMessages() {
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
        <span class="font-semibold text-sm text-gray-900">${h(t.participants)}</span>
        <p class="text-xs text-gray-500 mt-1 truncate">${h(t.last_message || '')}</p>
      </div>
    `).join('') || '<p class="p-4 text-gray-400 text-sm">No conversations.</p>';
  }

  loadThreads();

  window.openThread = async id => {
    currentThread = id;
    const res = await apiFetch(`/messages/send.php?thread_id=${id}`);
    if (!res.success || !chatArea) return;
    chatArea.innerHTML = res.data.map(m => `
      <div class="flex ${m.sender_id == _techUser?.id ? 'justify-end' : 'justify-start'} mb-3">
        <div class="max-w-xs px-4 py-2 rounded-2xl text-sm ${m.sender_id == _techUser?.id ? 'bg-black text-white' : 'bg-gray-100 text-gray-800'}">
          <p>${h(m.body)}</p>
        </div>
      </div>
    `).join('');
    chatArea.scrollTop = chatArea.scrollHeight;
  };

  if (msgForm) {
    msgForm.addEventListener('submit', async e => {
      e.preventDefault();
      if (!currentThread) return;
      const input = msgForm.querySelector('input,textarea');
      const msg = input?.value?.trim();
      if (!msg) return;
      await apiFetch(`/messages/send.php?thread_id=${currentThread}`, { method: 'POST', body: { message: msg } });
      input.value = ''; openThread(currentThread);
    });
  }
}

/* ── HELPERS ────────────────────────────────── */
function setEl(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function h(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
