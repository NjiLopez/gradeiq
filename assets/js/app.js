// ── Toast Notifications ──────────────────────────────────────────
function showToast(message, type = 'success') {
  const container = document.getElementById('toastContainer') || createToastContainer();
  const toast = document.createElement('div');
  const icon = type === 'success' ? '✓' : '✕';
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<span style="font-weight:700;font-size:1rem">${icon}</span><span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

function createToastContainer() {
  const el = document.createElement('div');
  el.id = 'toastContainer';
  el.className = 'toast-container';
  document.body.appendChild(el);
  return el;
}

// ── Tabs ─────────────────────────────────────────────────────────
function initTabs() {
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.closest('[data-tabs]');
      if (!group) return;
      const target = btn.dataset.tab;
      group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      group.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      const content = group.querySelector(`[data-tab-content="${target}"]`);
      if (content) content.classList.add('active');
    });
  });
}

// ── Upload Zone Drag & Drop ──────────────────────────────────────
function initUploadZone() {
  document.querySelectorAll('.upload-zone').forEach(zone => {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('dragover');
      const input = zone.querySelector('input[type="file"]');
      if (input && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
      }
    });
  });

  const fileInput = document.getElementById('csvFile');
  if (fileInput) {
    fileInput.addEventListener('change', function() {
      const uploadText = document.querySelector('.upload-text');
      if (this.files.length && uploadText) {
        uploadText.innerHTML = `<strong>📄 ${this.files[0].name}</strong><small>Ready to upload — ${(this.files[0].size/1024).toFixed(1)} KB</small>`;
      }
    });
  }
}

// ── Score Ring Animation ─────────────────────────────────────────
function animateScoreRing() {
  const ring = document.querySelector('.ring-fill');
  if (!ring) return;
  const pct = parseFloat(ring.dataset.pct || 0);
  const circumference = 2 * Math.PI * 60;
  ring.style.strokeDasharray = circumference;
  ring.style.strokeDashoffset = circumference;
  setTimeout(() => {
    const offset = circumference - (pct / 100) * circumference;
    ring.style.strokeDashoffset = offset;
  }, 200);
}

// ── Progress Bars Animation ───────────────────────────────────────
function animateProgressBars() {
  document.querySelectorAll('.progress-fill[data-width]').forEach(bar => {
    bar.style.width = '0%';
    setTimeout(() => { bar.style.width = bar.dataset.width; }, 100);
  });
}

// ── Answer Grid Builder ───────────────────────────────────────────
function buildAnswerGrid(containerId, count, prefix) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.innerHTML = '';
  const options = ['A', 'B', 'C', 'D', 'E'];
  for (let i = 1; i <= count; i++) {
    const item = document.createElement('div');
    item.className = 'answer-item';
    const lbl = document.createElement('div');
    lbl.className = 'answer-item-label';
    lbl.textContent = `Q${i}`;
    const sel = document.createElement('select');
    sel.name = `${prefix}[${i}]`;
    sel.innerHTML = `<option value="">-</option>` + options.map(o => `<option value="${o}">${o}</option>`).join('');
    item.appendChild(lbl);
    item.appendChild(sel);
    container.appendChild(item);
  }
}

// ── Questions Grid Builder ─────────────────────────────────────
function buildQuestionsGrid(containerId, count, prefix) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.innerHTML = '';
  for (let i = 1; i <= count; i++) {
    const item = document.createElement('div');
    item.className = 'question-item';
    const lbl = document.createElement('div');
    lbl.className = 'question-item-label';
    lbl.textContent = `Q${i}`;
    const ta = document.createElement('textarea');
    ta.name = `${prefix}[${i}]`;
    ta.rows = 2;
    ta.placeholder = `Enter question ${i} (optional)`;
    item.appendChild(lbl);
    item.appendChild(ta);
    container.appendChild(item);
  }
}

// ── Q Count Listener ─────────────────────────────────────────────
function initQCountListener() {
  const qCountInput = document.getElementById('qCount');
  const answersGrid = document.getElementById('answersGrid');
  const studentAnswersGrid = document.getElementById('studentAnswersGrid');

  if (qCountInput) {
    const update = () => {
      const n = parseInt(qCountInput.value) || 0;
      if (n > 0 && n <= 500) {
        if (answersGrid) buildAnswerGrid('answersGrid', n, 'answers');
        if (studentAnswersGrid) buildAnswerGrid('studentAnswersGrid', n, 'student_answers');
        if (document.getElementById('questionsGrid')) buildQuestionsGrid('questionsGrid', n, 'questions');
      }
    };
    qCountInput.addEventListener('change', update);
    if (qCountInput.value) update();
  }
}

// ── Confirm Dialogs ──────────────────────────────────────────────
function confirmAction(message, form) {
  if (confirm(message)) form.submit();
  return false;
}

// ── Print Report ─────────────────────────────────────────────────
function printReport() {
  window.print();
}

// ── Copy text ────────────────────────────────────────────────────
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => showToast('Copied to clipboard!'));
}

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  initUploadZone();
  initQCountListener();
  animateScoreRing();
  animateProgressBars();

  // Mark active nav
  const currentPage = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-links a').forEach(a => {
    if (a.getAttribute('href') === currentPage) a.classList.add('active');
  });
});
