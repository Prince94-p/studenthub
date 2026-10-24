<?php
// Practical 13 - front-end for the JSON API: list / add / update / delete with Fetch, no page reload.
require_once __DIR__ . '/../includes/helpers.php';
require_login();
$title = 'Events (AJAX)'; require __DIR__ . '/../includes/header.php';
$admin = is_admin();
?>
<h3>Events - AJAX CRUD</h3>
<div id="msg"></div>
<input id="search" class="form-control mb-3" placeholder="Search events (debounced)...">
<?php if ($admin): ?>
<form id="evForm" class="card card-body mb-3">
  <input type="hidden" id="id">
  <div class="row g-2">
    <div class="col-md-3"><input class="form-control" id="title" placeholder="Title"></div>
    <div class="col-md-2"><input type="date" class="form-control" id="event_date"></div>
    <div class="col-md-2"><input class="form-control" id="venue" placeholder="Venue"></div>
    <div class="col-md-2"><select class="form-select" id="category"><option>Technical</option><option>Cultural</option><option>Sports</option><option>Workshop</option></select></div>
    <div class="col-md-1"><select class="form-select" id="status"><option>open</option><option>closed</option></select></div>
    <div class="col-md-2"><button class="btn btn-primary" id="saveBtn">Add</button> <button type="button" class="btn btn-link" id="resetBtn">Clear</button></div>
  </div>
  <input class="form-control mt-2" id="description" placeholder="Description">
</form>
<?php endif; ?>
<div id="loading" class="text-muted d-none">Loading...</div>
<table class="table table-striped bg-white"><thead><tr><th>Title</th><th>Date</th><th>Venue</th><th>Category</th><th>Status</th><?php if ($admin): ?><th>Actions</th><?php endif; ?></tr></thead><tbody id="rows"></tbody></table>

<script>
const CSRF = <?= json_encode(csrf_token()) ?>, ADMIN = <?= json_encode($admin) ?>, API = 'api.php';
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let events = [];

function notify(type, text) {
  $('msg').innerHTML = `<div class="alert alert-${type}">${esc(text)}</div>`;
  setTimeout(() => $('msg').innerHTML = '', 4000);
}
async function call(options, url = API) {                    // central request + error handling
  const res = await fetch(url, options);
  let data; try { data = await res.json(); } catch { throw new Error('Server returned invalid JSON'); }
  if (!res.ok || !data.success) throw new Error(data.message || ('HTTP ' + res.status));
  return data;
}
async function load(q = '') {
  $('loading').classList.remove('d-none');                   // loading state
  try { events = (await call({}, API + '?q=' + encodeURIComponent(q))).data; render(); }
  catch (err) { notify('danger', err.message); }              // error state
  finally { $('loading').classList.add('d-none'); }
}
function render() {
  $('rows').innerHTML = events.map(e => `<tr><td>${esc(e.title)}</td><td>${esc(e.event_date)}</td><td>${esc(e.venue)}</td>
    <td>${esc(e.category)}</td><td>${esc(e.status)}</td>` + (ADMIN ? `<td>
    <button class="btn btn-sm btn-primary" onclick="edit(${e.id})">Edit</button>
    <button class="btn btn-sm btn-danger" onclick="del(${e.id})">Delete</button></td>` : '') + '</tr>').join('')
    || '<tr><td colspan="6" class="text-center text-muted">No events found.</td></tr>';
}
function edit(id) {
  const e = events.find(x => x.id == id);
  for (const k of ['id','title','event_date','venue','category','status','description']) $(k).value = e[k] ?? '';
  $('saveBtn').textContent = 'Update'; window.scrollTo(0, 0);
}
function resetForm() { $('evForm').reset(); $('id').value = ''; $('saveBtn').textContent = 'Add'; }
async function del(id) {
  if (!confirm('Delete this event?')) return;
  try {
    const r = await call({ method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF }, body: JSON.stringify({ action: 'delete', id }) });
    notify('success', r.message); load($('search').value);
  } catch (err) { notify('danger', err.message); }
}
if (ADMIN) {
  $('resetBtn').onclick = resetForm;
  $('evForm').addEventListener('submit', async ev => {
    ev.preventDefault();
    const body = { action: $('id').value ? 'update' : 'add' };
    for (const k of ['id','title','event_date','venue','category','status','description']) body[k] = $(k).value;
    $('saveBtn').disabled = true;
    try {
      const r = await call({ method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF }, body: JSON.stringify(body) });
      notify('success', r.message); resetForm(); load($('search').value);
    } catch (err) { notify('danger', err.message); }
    finally { $('saveBtn').disabled = false; }
  });
}
let timer; $('search').addEventListener('input', e => { clearTimeout(timer); timer = setTimeout(() => load(e.target.value), 350); });   // AJAX search with debounce
load();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
