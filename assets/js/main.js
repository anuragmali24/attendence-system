// ── BLW Attendance System — main.js ──────────────────────────────────────

// ── Live Clock ──────────────────────────────────────────────────────────
function updateClock() {
  const el = document.getElementById('liveClock');
  if (!el) return;
  const now = new Date();
  el.textContent = now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
}
setInterval(updateClock, 1000);
updateClock();

// ── Toast ────────────────────────────────────────────────────────────────
function showToast(msg, dur = 3000) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), dur);
}

// ── Modal helpers ────────────────────────────────────────────────────────
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.addEventListener('click', function (e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// ── Flash message auto-hide ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  const flash = document.querySelector('.alert');
  if (flash) setTimeout(() => flash.style.display = 'none', 4000);
});

// ── Mobile sidebar close on backdrop click ───────────────────────────────
document.addEventListener('click', function (e) {
  if (window.innerWidth <= 768 && document.body.classList.contains('sidebar-open')) {
    if (!e.target.closest('.sidebar') && !e.target.closest('.menu-toggle')) {
      document.body.classList.remove('sidebar-open');
    }
  }
});

// ── Confirm delete helper ────────────────────────────────────────────────
function confirmDelete(url, name) {
  if (confirm('Delete "' + name + '"?\nThis action cannot be undone.')) {
    window.location.href = url;
  }
}

// ── Attendance status fetch ───────────────────────────────────────────────
const empSelect = document.getElementById('empSelect');
if (empSelect) {
  empSelect.addEventListener('change', function () {
    const empId = this.value;
    const infoEl = document.getElementById('attInfo');
    if (!empId || !infoEl) return;
    fetch('ajax/att_status.php?emp_id=' + encodeURIComponent(empId))
      .then(r => r.json())
      .then(d => {
        if (d.check_in && !d.check_out) {
          infoEl.textContent = '✓ Checked in at ' + d.check_in + ' — not yet checked out';
        } else if (d.check_in && d.check_out) {
          infoEl.textContent = '✓ Check-in: ' + d.check_in + ' | Check-out: ' + d.check_out;
        } else {
          infoEl.textContent = 'No attendance marked today for this employee';
        }
      })
      .catch(() => { infoEl.textContent = ''; });
  });
}
