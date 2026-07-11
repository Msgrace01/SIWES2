// ---- Configuration ----
const BASE_URL = "/CHATAPP/BACKEND";

// ---- In-memory session state (no browser storage used) ----
let currentUser = null;   // { id, username }
let pollTimer = null;
let lastRenderedIds = new Set();

// ---- Elements ----
const authScreen   = document.getElementById('authScreen');
const appScreen    = document.getElementById('appScreen');
const tabLogin     = document.getElementById('tabLogin');
const tabRegister  = document.getElementById('tabRegister');
const loginForm    = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const loginMsg     = document.getElementById('loginMsg');
const registerMsg  = document.getElementById('registerMsg');
const whoName      = document.getElementById('whoName');
const feed         = document.getElementById('feed');
const composerForm = document.getElementById('composerForm');
const messageInput = document.getElementById('messageInput');
const sendBtn       = document.getElementById('sendBtn');
const logoutBtn     = document.getElementById('logoutBtn');

// ---- Tab switching ----
tabLogin.addEventListener('click', () => {
  tabLogin.classList.add('active');
  tabRegister.classList.remove('active');
  loginForm.style.display = 'flex';
  registerForm.style.display = 'none';
});
tabRegister.addEventListener('click', () => {
  tabRegister.classList.add('active');
  tabLogin.classList.remove('active');
  registerForm.style.display = 'flex';
  loginForm.style.display = 'none';
});

function setMsg(el, text, ok){
  el.textContent = text;
  el.className = 'msg-line ' + (ok ? 'ok' : 'err');
}

// ---- API helper ----
async function api(path, body){
  const res = await fetch(BASE_URL + path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body || {})
  });
  let data;
  try{
    data = await res.json();
  }catch(e){
    throw new Error('Server did not return valid JSON.');
  }
  return data;
}

// ---- Register ----
registerForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  setMsg(registerMsg, 'Creating account…', true);
  try{
    const data = await api('/Register.php', {
      username: document.getElementById('regUsername').value.trim(),
      email: document.getElementById('regEmail').value.trim(),
      password: document.getElementById('regPassword').value
    });
    if(data.status === 'success'){
      setMsg(registerMsg, 'Account created — you can log in now.', true);
      registerForm.reset();
      tabLogin.click();
    } else {
      setMsg(registerMsg, data.message || 'Registration failed.', false);
    }
  }catch(err){
    setMsg(registerMsg, err.message, false);
  }
});

// ---- Login ----
loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  setMsg(loginMsg, 'Logging in…', true);
  try{
    const data = await api('/Login.php', {
      username: document.getElementById('loginUsername').value.trim(),
      password: document.getElementById('loginPassword').value
    });
    if(data.status === 'success'){
      currentUser = data.user;
      enterApp();
    } else {
      setMsg(loginMsg, data.message || 'Login failed.', false);
    }
  }catch(err){
    setMsg(loginMsg, err.message, false);
  }
});

// ---- Logout ----
logoutBtn.addEventListener('click', async () => {
  try{ await api('/Logout.php', {}); }catch(e){ /* ignore network errors on logout */ }
  stopPolling();
  currentUser = null;
  lastRenderedIds.clear();
  feed.innerHTML = '<div class="empty">No messages yet — say something.</div>';
  appScreen.classList.remove('active');
  authScreen.style.display = 'block';
  loginForm.reset();
  registerForm.reset();
  setMsg(loginMsg, '', true);
});

// ---- Send message ----
composerForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = messageInput.value.trim();
  if(!text || !currentUser) return;
  sendBtn.disabled = true;
  try{
    const data = await api('/send_message.php', {
      sender_id: currentUser.id,
      message_text: text
    });
    if(data.status === 'success'){
      messageInput.value = '';
      await loadMessages();
    } else {
      alert(data.message || 'Could not send message.');
    }
  }catch(err){
    alert(err.message);
  }finally{
    sendBtn.disabled = false;
    messageInput.focus();
  }
});

// ---- Fetch + render feed ----
async function loadMessages(){
  try{
    const res = await fetch(BASE_URL + '/get_message.php');
    const data = await res.json();
    if(data.status === 'success'){
      renderMessages(data.messages || []);
    }
  }catch(err){
    // Silent fail on a poll tick; connection blip shouldn't interrupt the UI.
    console.error('Feed refresh failed:', err);
  }
}

function renderMessages(messages){
  if(messages.length === 0){
    feed.innerHTML = '<div class="empty">No messages yet — say something.</div>';
    return;
  }
  feed.innerHTML = '';
  messages.forEach(m => {
    const mine = currentUser && Number(m.sender_id) === Number(currentUser.id);
    const bubble = document.createElement('div');
    bubble.className = 'bubble' + (mine ? ' mine' : '');
    bubble.innerHTML = `
      <div class="meta"><span>${escapeHtml(m.username || 'unknown')}</span><span>${formatTime(m.created_at)}</span></div>
      <div class="txt">${escapeHtml(m.message_text)}</div>
    `;
    feed.appendChild(bubble);
  });
  feed.scrollTop = feed.scrollHeight;
}

function escapeHtml(str){
  const div = document.createElement('div');
  div.textContent = str == null ? '' : String(str);
  return div.innerHTML;
}

function formatTime(ts){
  if(!ts) return '';
  const d = new Date(ts.replace(' ', 'T'));
  if(isNaN(d)) return ts;
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// ---- Poll every 5s, per API doc ----
function startPolling(){
  stopPolling();
  loadMessages();
  pollTimer = setInterval(loadMessages, 5000);
}
function stopPolling(){
  if(pollTimer){ clearInterval(pollTimer); pollTimer = null; }
}

function enterApp(){
  authScreen.style.display = 'none';
  appScreen.classList.add('active');
  whoName.textContent = currentUser.username;
  startPolling();
}