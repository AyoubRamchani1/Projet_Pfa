/* ── PAGE TRANSITIONS ── */
const signin = document.getElementById('page-signin');
const signup = document.getElementById('page-signup');
const spinner = document.getElementById('spinner');
const messageBox = document.getElementById('message');

function showSpinner() { spinner.classList.add('active'); }
function hideSpinner() { spinner.classList.remove('active'); }

/* ── MESSAGE FUNCTION ── */
function showMessage(text, type = "success") {
  messageBox.textContent = text;
  messageBox.className = "message show";

  if (type === "error") {
    messageBox.classList.add("error");
  }

  setTimeout(() => {
    messageBox.classList.remove("show");
  }, 3000);
}

/* ── PAGE SWITCH ── */
function showPage(incoming, outgoing, outDir, inClass) {
  showSpinner();
  outgoing.style.cssText = outDir + ';pointer-events:none;z-index:50;';
  
  setTimeout(() => {
    hideSpinner();
    incoming.style.cssText = 'z-index:100;';
    incoming.className = inClass;
  }, 400);
}

document.getElementById('to-signup').addEventListener('click', () => {
  showPage(
    signup, signin,
    'opacity:0;transform:translate(-150%,-50%)',
    'page page-signup enter-right'
  );
});

document.getElementById('to-signin').addEventListener('click', () => {
  showPage(
    signin, signup,
    'opacity:0;transform:translate(50%,-50%)',
    'page page-signin enter-left'
  );
});

/* ── CHECK URL PARAMETERS ── */
const urlParams = new URLSearchParams(window.location.search);
const message = urlParams.get('message');
const error = urlParams.get('error');

if (message) {
  showMessage(decodeURIComponent(message), 'success');
}

if (error) {
  showMessage(decodeURIComponent(error), 'error');
}

if (urlParams.get('page') === 'signup') {
  showPage(
    signup, signin,
    'opacity:0;transform:translate(-150%,-50%)',
    'page page-signup enter-right'
  );
}




