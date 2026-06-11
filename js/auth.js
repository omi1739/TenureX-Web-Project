/* TenureX — login & registration page logic */

document.addEventListener('DOMContentLoaded', () => {

  /* ── LOGIN ─────────────────────────────────────────── */
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    // If already logged in, redirect
    apiFetch('/auth/me.php').then(res => { if (res.success) redirectToDashboard(res.data.role); });

    loginForm.addEventListener('submit', async e => {
      e.preventDefault();
      const errEl = document.getElementById('loginError');
      if (errEl) errEl.classList.add('hidden');
      const btn = loginForm.querySelector('button[type="submit"]');
      const orig = btn.textContent;
      btn.disabled = true; btn.textContent = 'Signing in…';

      try {
        const res = await apiFetch('/auth/login.php', {
          method: 'POST',
          body: {
            email:    document.getElementById('email').value.trim(),
            password: document.getElementById('password').value,
          },
        });
        if (res.success) {
          redirectToDashboard(res.data.role);
        } else {
          if (errEl) { errEl.textContent = res.error; errEl.classList.remove('hidden'); }
          else showToast(res.error, 'error');
        }
      } catch {
        if (errEl) { errEl.textContent = 'Network error — is XAMPP running?'; errEl.classList.remove('hidden'); }
      } finally {
        btn.disabled = false; btn.textContent = orig;
      }
    });
  }

  /* ── DEMO LOGIN ────────────────────────────────────── */
  window.demoLogin = async function(email, password) {
    const errEl = document.getElementById('loginError');
    if (errEl) errEl.classList.add('hidden');
    const btn = document.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Signing in…'; }

    try {
      const res = await apiFetch('/auth/login.php', { method: 'POST', body: { email, password } });
      if (res.success) {
        redirectToDashboard(res.data.role);
      } else {
        if (errEl) { errEl.textContent = res.error; errEl.classList.remove('hidden'); }
      }
    } catch {
      if (errEl) { errEl.textContent = 'Network error — is XAMPP running?'; errEl.classList.remove('hidden'); }
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Sign In'; }
    }
  };

  /* ── REGISTRATION ──────────────────────────────────── */
  const regForm = document.getElementById('registerForm');
  if (regForm) {
    regForm.addEventListener('submit', async e => {
      e.preventDefault();
      const errEl  = document.getElementById('regError');
      const succEl = document.getElementById('regSuccess');
      if (errEl)  errEl.classList.add('hidden');
      if (succEl) succEl.classList.add('hidden');
      const btn  = regForm.querySelector('button[type="submit"]');
      const orig = btn.textContent;
      btn.disabled = true; btn.textContent = 'Creating account…';

      try {
        const res = await apiFetch('/auth/register.php', {
          method: 'POST',
          body: {
            full_name: (document.getElementById('fullName') || document.getElementById('full_name'))?.value?.trim(),
            email:     document.getElementById('regEmail')?.value?.trim() || document.getElementById('email')?.value?.trim(),
            password:  document.getElementById('regPassword')?.value || document.getElementById('password')?.value,
            role:      document.getElementById('role')?.value || 'tenant',
            phone:     document.getElementById('phone')?.value?.trim() || '',
          },
        });
        if (res.success) {
          if (res.data.approval_status === 'pending') {
            if (succEl) { succEl.textContent = res.message; succEl.classList.remove('hidden'); }
            regForm.reset();
          } else {
            window.location.href = '/TenureX-Web-Project/Pages/login.html';
          }
        } else {
          if (errEl) { errEl.textContent = res.error; errEl.classList.remove('hidden'); }
          else showToast(res.error, 'error');
        }
      } catch {
        if (errEl) { errEl.textContent = 'Network error — is XAMPP running?'; errEl.classList.remove('hidden'); }
      } finally {
        btn.disabled = false; btn.textContent = orig;
      }
    });
  }
});
