
  /* ── Navigation avec animation ── */
function navigateTo(page, btn) {
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  document.body.style.opacity = '0';
  document.body.style.transition = 'opacity 0.2s ease';

  setTimeout(() => {
    sessionStorage.setItem('noFade', 'true');
    window.location.href = page;
  }, 200);
}

/* ── Animation entrée ── */
window.addEventListener('load', () => {
  if (sessionStorage.getItem('noFade') === 'true') {
    sessionStorage.removeItem('noFade');
    document.body.style.transition = 'none';
  }
  document.body.classList.add('loaded');
});

/* ── Scroll ── */
function scroll(rowId) {
  const row = document.getElementById(rowId);
  if (row) row.scrollBy({ left: 500, behavior: 'smooth' });
}

/* ── Active nav ── */
function setActive(btn) {
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

/* ── Card hover ── */
document.querySelectorAll('.card').forEach(card => {
  card.addEventListener('mouseenter', () => card.style.zIndex = '10');
  card.addEventListener('mouseleave', () => card.style.zIndex = '');
});

/* ── Redirection simple ── */
function goToPage(page) {
  window.location.href = page;
}

/* ── Message ── */
function showMessage(text, type = "success") {
  const messageBox = document.getElementById('message');
  if (!messageBox) return;
  messageBox.textContent = text;
  messageBox.className = "message show";
  setTimeout(() => messageBox.classList.remove("show"), 3000);
}

/* ── URL Params ── */
const urlParams = new URLSearchParams(window.location.search);
const user = urlParams.get('user');
if (user) showMessage(decodeURIComponent(user), 'success');
