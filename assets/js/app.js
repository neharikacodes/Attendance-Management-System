/**
 * assets/js/app.js
 * Attendance Management System — JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Sidebar Toggle (Mobile) ─────────────────── */
  const menuBtn     = document.getElementById('menuBtn');
  const sidebar     = document.getElementById('sidebar');
  const sidebarOver = document.getElementById('sidebarOverlay');

  function openSidebar() {
    sidebar?.classList.add('open');
    sidebarOver?.classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    sidebarOver?.classList.remove('show');
    document.body.style.overflow = '';
  }

  menuBtn?.addEventListener('click', openSidebar);
  sidebarOver?.addEventListener('click', closeSidebar);

  /* ── Flash message auto-dismiss ──────────────── */
  const flash = document.getElementById('flashMsg');
  if (flash) {
    setTimeout(() => {
      flash.style.opacity = '0';
      flash.style.transform = 'translateY(-8px)';
      flash.style.transition = 'all 0.4s ease';
      setTimeout(() => flash.remove(), 400);
    }, 4500);
  }

  /* ── Modal system ────────────────────────────── */
  window.openModal = function(id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.classList.add('active');
  };

  window.closeModal = function(id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.classList.remove('active');
  };

  // Close modal clicking backdrop
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('active');
    });
  });

  // Keyboard close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
  });

  /* ── Attendance radio labels visual state ─────── */
  document.querySelectorAll('.radio-group').forEach(group => {
    const radios = group.querySelectorAll('.radio-option');
    radios.forEach(radio => {
      // Set initial state
      updateRadioLabel(radio);

      radio.addEventListener('change', () => {
        radios.forEach(r => updateRadioLabel(r));
      });
    });
  });

  function updateRadioLabel(radio) {
    const label = radio.nextElementSibling;
    if (!label) return;
    label.classList.remove('present-checked', 'absent-checked', 'late-checked');
    if (radio.checked) {
      const val = radio.value.toLowerCase();
      label.classList.add(`${val}-checked`);
    }
  }

  /* ── Animated gauge ──────────────────────────── */
  const gaugeFill = document.getElementById('gaugeFill');
  if (gaugeFill) {
    const pct  = parseFloat(gaugeFill.dataset.pct) || 0;
    const circ = parseFloat(gaugeFill.dataset.circ) || 339.3;
    const offset = circ - (pct / 100) * circ;

    // Animate on load
    setTimeout(() => {
      gaugeFill.style.strokeDashoffset = offset;
      // Color by percentage
      if (pct >= 75) {
        gaugeFill.style.stroke = '#10b981';
      } else if (pct >= 50) {
        gaugeFill.style.stroke = '#f59e0b';
      } else {
        gaugeFill.style.stroke = '#ef4444';
      }
    }, 100);
  }

  /* ── Progress bars animate in ────────────────── */
  document.querySelectorAll('.progress-bar-fill[data-width]').forEach(bar => {
    setTimeout(() => {
      bar.style.width = bar.dataset.width + '%';
    }, 200);
  });

  /* ── Confirm delete modal trigger ────────────── */
  window.confirmDelete = function(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    const overlay = document.getElementById('deleteConfirmModal');
    if (!overlay) return;

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.onclick = () => form.submit();
    overlay.classList.add('active');
  };

  /* ── Select All checkboxes (for bulk ops) ─────── */
  const selectAll = document.getElementById('selectAll');
  if (selectAll) {
    selectAll.addEventListener('change', () => {
      document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = selectAll.checked;
      });
    });
  }

  /* ── Date filter — set today as max ──────────── */
  document.querySelectorAll('input[type="date"]').forEach(input => {
    if (!input.max) {
      const today = new Date().toISOString().split('T')[0];
      input.max = today;
    }
  });

  /* ── Table search filter ─────────────────────── */
  const searchInput = document.getElementById('tableSearch');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.toLowerCase().trim();
      const rows  = document.querySelectorAll('.data-table tbody tr');
      let visible = 0;

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const show = text.includes(query);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      const noResult = document.getElementById('noSearchResult');
      if (noResult) noResult.style.display = visible === 0 ? 'block' : 'none';
    });
  }

  /* ── Form submit loading state ───────────────── */
  document.querySelectorAll('form[data-loading]').forEach(form => {
    form.addEventListener('submit', () => {
      const btn = form.querySelector('[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Please wait...';
      }
    });
  });

  /* ── Attendance mark form: Select All Present ─── */
  const markAllPresent = document.getElementById('markAllPresent');
  if (markAllPresent) {
    markAllPresent.addEventListener('click', () => {
      document.querySelectorAll('.radio-option[value="Present"]').forEach(r => {
        r.checked = true;
        updateRadioLabel(r);
      });
    });
  }

  const markAllAbsent = document.getElementById('markAllAbsent');
  if (markAllAbsent) {
    markAllAbsent.addEventListener('click', () => {
      document.querySelectorAll('.radio-option[value="Absent"]').forEach(r => {
        r.checked = true;
        updateRadioLabel(r);
      });
    });
  }

  /* ── Sidebar active link highlight ───────────── */
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && currentPath.endsWith(href)) {
      link.classList.add('active');
    }
  });

  /* ── Animate stat card values ─────────────────── */
  document.querySelectorAll('.stat-value[data-count]').forEach(el => {
    const target  = parseInt(el.dataset.count);
    const duration = 800;
    const start    = performance.now();

    function step(now) {
      const elapsed  = now - start;
      const progress = Math.min(elapsed / duration, 1);
      const eased    = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target);
      if (progress < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
  });

});
