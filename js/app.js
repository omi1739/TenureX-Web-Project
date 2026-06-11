/* TenureX — core JS utilities loaded on every authenticated page */

const API = '/TenureX-Web-Project/api';

async function apiFetch(path, opts = {}) {
  const options = {
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  };
  if (options.body && typeof options.body === 'object') {
    options.body = JSON.stringify(options.body);
  }
  const res = await fetch(API + path, options);
  return res.json();
}

async function checkAuth(allowedRoles = []) {
  try {
    const res = await apiFetch('/auth/me.php');
    if (!res.success) {
      window.location.href = '/TenureX-Web-Project/Pages/login.html';
      return null;
    }
    if (allowedRoles.length && !allowedRoles.includes(res.data.role)) {
      redirectToDashboard(res.data.role);
      return null;
    }
    fillUserName(res.data.name);
    return res.data;
  } catch {
    window.location.href = '/TenureX-Web-Project/Pages/login.html';
    return null;
  }
}

function redirectToDashboard(role) {
  const map = {
    admin:      '/TenureX-Web-Project/admin/dashboard.html',
    landlord:   '/TenureX-Web-Project/Landlord/pages/dashboard.html',
    tenant:     '/TenureX-Web-Project/Tenant/dashboard.html',
    technician: '/TenureX-Web-Project/Technician/dashboard.html',
  };
  window.location.href = map[role] || '/TenureX-Web-Project/Pages/login.html';
}

function fillUserName(name) {
  document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = name);
}

async function logout() {
  await apiFetch('/auth/logout.php', { method: 'POST' });
  window.location.href = '/TenureX-Web-Project/Pages/login.html';
}

function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className = 'fixed top-5 right-5 z-[9999] px-5 py-3 rounded-xl text-sm font-semibold shadow-2xl text-white transition-all duration-300 ' +
    (type === 'success' ? 'bg-gray-900' : 'bg-red-600');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 3000);
}

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatMoney(n) {
  return '৳' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 0 });
}

function statusBadge(status) {
  const map = {
    pending:     'bg-yellow-100 text-yellow-700',
    in_review:   'bg-blue-100 text-blue-700',
    accepted:    'bg-green-100 text-green-700',
    rejected:    'bg-red-100 text-red-600',
    approved:    'bg-green-100 text-green-700',
    active:      'bg-green-100 text-green-700',
    open:        'bg-yellow-100 text-yellow-700',
    in_progress: 'bg-blue-100 text-blue-700',
    resolved:    'bg-green-100 text-green-700',
    closed:      'bg-gray-100 text-gray-600',
    paid:        'bg-green-100 text-green-700',
    upcoming:    'bg-blue-100 text-blue-700',
    overdue:     'bg-red-100 text-red-600',
    dispatched:  'bg-blue-100 text-blue-700',
    completed:   'bg-green-100 text-green-700',
    cancelled:   'bg-gray-100 text-gray-500',
    draft:       'bg-gray-100 text-gray-500',
  };
  const cls = map[status] || 'bg-gray-100 text-gray-600';
  const label = status ? status.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase()) : '—';
  return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold ${cls}">${label}</span>`;
}

// Wire up logout buttons
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-logout]').forEach(el => {
    el.addEventListener('click', e => { e.preventDefault(); logout(); });
  });
});
