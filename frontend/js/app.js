/* ============================================
   GRH System - Shared Application JavaScript
   ============================================ */

// ---- Demo Data Store ----
const DemoData = {
  employees: [
    { id: 1, name: 'Adel Selmi', email: 'adel@grh.com', department: 'Informatique', position: 'Développeur', phone: '+216 50 123 456', joinDate: '2024-01-15', status: 'active' },
    { id: 2, name: 'Feki Mohamed', email: 'feki@grh.com', department: 'Informatique', position: 'Développeur', phone: '+216 50 789 012', joinDate: '2024-02-01', status: 'active' },
    { id: 3, name: 'Sami Ben Ali', email: 'sami@grh.com', department: 'Marketing', position: 'Chef de projet', phone: '+216 55 111 222', joinDate: '2023-06-10', status: 'active' },
    { id: 4, name: 'Leila Mansour', email: 'leila@grh.com', department: 'RH', position: 'Responsable RH', phone: '+216 55 333 444', joinDate: '2023-03-01', status: 'active' },
    { id: 5, name: 'Ahmed Trabelsi', email: 'ahmed@grh.com', department: 'Finance', position: 'Comptable', phone: '+216 50 555 666', joinDate: '2024-05-20', status: 'inactive' },
    { id: 6, name: 'Nour Khediri', email: 'nour@grh.com', department: 'Marketing', position: 'Designer', phone: '+216 55 777 888', joinDate: '2024-08-01', status: 'active' },
  ],

  leaves: [
    { id: 1, employeeId: 1, employeeName: 'Adel Selmi', type: 'Annuel', startDate: '2026-02-20', endDate: '2026-02-25', days: 5, reason: 'Vacances familiales', status: 'pending' },
    { id: 2, employeeId: 2, employeeName: 'Feki Mohamed', type: 'Maladie', startDate: '2026-02-10', endDate: '2026-02-12', days: 2, reason: 'Grippe', status: 'approved' },
    { id: 3, employeeId: 3, employeeName: 'Sami Ben Ali', type: 'Annuel', startDate: '2026-03-01', endDate: '2026-03-07', days: 5, reason: 'Voyage personnel', status: 'approved' },
    { id: 4, employeeId: 4, employeeName: 'Leila Mansour', type: 'Personnel', startDate: '2026-02-15', endDate: '2026-02-16', days: 1, reason: 'Affaires personnelles', status: 'rejected' },
    { id: 5, employeeId: 6, employeeName: 'Nour Khediri', type: 'Annuel', startDate: '2026-03-10', endDate: '2026-03-14', days: 4, reason: 'Mariage', status: 'pending' },
  ],

  workHours: [
    { id: 1, employeeId: 1, employeeName: 'Adel Selmi', date: '2026-02-14', clockIn: '08:00', clockOut: '17:00', hours: 9, status: 'complete' },
    { id: 2, employeeId: 2, employeeName: 'Feki Mohamed', date: '2026-02-14', clockIn: '08:30', clockOut: '17:30', hours: 9, status: 'complete' },
    { id: 3, employeeId: 3, employeeName: 'Sami Ben Ali', date: '2026-02-14', clockIn: '09:00', clockOut: '18:00', hours: 9, status: 'complete' },
    { id: 4, employeeId: 4, employeeName: 'Leila Mansour', date: '2026-02-14', clockIn: '08:15', clockOut: null, hours: null, status: 'in-progress' },
    { id: 5, employeeId: 6, employeeName: 'Nour Khediri', date: '2026-02-14', clockIn: '08:45', clockOut: '16:45', hours: 8, status: 'complete' },
    { id: 6, employeeId: 1, employeeName: 'Adel Selmi', date: '2026-02-13', clockIn: '08:00', clockOut: '17:30', hours: 9.5, status: 'complete' },
    { id: 7, employeeId: 1, employeeName: 'Adel Selmi', date: '2026-02-12', clockIn: '08:15', clockOut: '17:00', hours: 8.75, status: 'complete' },
  ]
};

// ---- Auth Helpers ----
function getCurrentUser() {
  const data = localStorage.getItem('grh_user');
  return data ? JSON.parse(data) : null;
}

function requireAuth(role) {
  const user = getCurrentUser();
  if (!user) {
    window.location.href = '../login.html';
    return null;
  }
  if (role && user.role !== role) {
    window.location.href = '../login.html';
    return null;
  }
  return user;
}

function logout() {
  localStorage.removeItem('grh_user');
  window.location.href = '../login.html';
}

// ---- Sidebar Active Link ----
function setActiveNavLink() {
  const current = window.location.pathname.split('/').pop();
  document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && href.includes(current)) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });
}

// ---- Mobile Sidebar Toggle ----
function initSidebarToggle() {
  const toggle = document.querySelector('.sidebar-toggle');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('show');
      if (overlay) overlay.classList.toggle('show');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
  }
}

// ---- Live Clock ----
function startClock(timeEl, dateEl) {
  function update() {
    const now = new Date();
    if (timeEl) {
      timeEl.textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    if (dateEl) {
      dateEl.textContent = now.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
  }
  update();
  setInterval(update, 1000);
}

// ---- Format Helpers ----
function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function getInitials(name) {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

function getStatusBadge(status) {
  const labels = {
    approved: 'Approuvé',
    pending: 'En attente',
    rejected: 'Refusé',
    active: 'Actif',
    inactive: 'Inactif',
    complete: 'Complet',
    'in-progress': 'En cours'
  };
  const cls = {
    approved: 'approved',
    pending: 'pending',
    rejected: 'rejected',
    active: 'active',
    inactive: 'inactive',
    complete: 'approved',
    'in-progress': 'pending'
  };
  return `<span class="status-badge ${cls[status] || ''}">${labels[status] || status}</span>`;
}

// ---- Toast ----
function showToast(message, type = 'success') {
  const container = document.querySelector('.toast-container') || (() => {
    const c = document.createElement('div');
    c.className = 'toast-container';
    document.body.appendChild(c);
    return c;
  })();

  const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
  const colors = { success: 'var(--success)', error: 'var(--danger)', info: 'var(--info)', warning: 'var(--warning)' };

  const toast = document.createElement('div');
  toast.className = 'custom-toast';
  toast.innerHTML = `
    <i class="bi ${icons[type] || icons.info}" style="color: ${colors[type] || colors.info}; font-size: 1.2rem;"></i>
    <span style="color: var(--text-primary); font-size: 0.9rem;">${message}</span>
  `;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(30px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// ---- Init Common ----
document.addEventListener('DOMContentLoaded', () => {
  setActiveNavLink();
  initSidebarToggle();
});
