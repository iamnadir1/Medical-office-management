document.addEventListener('DOMContentLoaded', () => {
  
  const sb = document.getElementById('sidebar'), mt = document.getElementById('menuToggle');
  if (mt) mt.addEventListener('click', () => sb.classList.toggle('open'));

  
  const openModal = m => { m.classList.add('open'); const f = m.querySelector('input:not([type=hidden]),select,textarea'); if (f) setTimeout(() => f.focus(), 50); };
  const closeModal = m => m.classList.remove('open');

  document.querySelectorAll('[data-open-modal]').forEach(btn => btn.addEventListener('click', () => {
    const modal = document.getElementById(btn.dataset.openModal);
    const form = modal.querySelector('form');
    form.reset();
    // reset dynamic medicine rows if present
    const rows = form.querySelector('#medRows'); if (rows && window.resetMedRows) window.resetMedRows();
    if (btn.dataset.title) modal.querySelector('.modal-head h3').textContent = btn.dataset.title;
    if (btn.dataset.action) form.elements['action'].value = btn.dataset.action;
    if (btn.dataset.fill) {
      const data = JSON.parse(btn.dataset.fill);
      Object.keys(data).forEach(k => { if (form.elements[k] && form.elements[k].type !== 'file') form.elements[k].value = data[k] ?? ''; });
    }
    openModal(modal);
  }));
  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('mousedown', e => { if (e.target === m) closeModal(m); });
    m.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => closeModal(m)));
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(closeModal); });

  
  document.querySelectorAll('[data-filter]').forEach(inp => {
    const table = document.getElementById(inp.dataset.filter);
    inp.addEventListener('input', () => {
      const q = inp.value.toLowerCase();
      table.querySelectorAll('tbody tr[data-row]').forEach(tr => tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none');
    });
  });
  document.querySelectorAll('[data-filter-select]').forEach(sel => {
    const table = document.getElementById(sel.dataset.filterSelect);
    sel.addEventListener('change', () => {
      table.querySelectorAll('tbody tr[data-row]').forEach(tr => tr.style.display = (!sel.value || tr.dataset.status === sel.value) ? '' : 'none');
    });
  });

  
  document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', e => {
    if (!confirm(f.dataset.confirm)) e.preventDefault();
  }));

  
  document.querySelectorAll('.alert-success').forEach(a => setTimeout(() => { a.style.opacity = 0; setTimeout(() => a.remove(), 500); }, 4000));

  
  const rows = document.getElementById('medRows');
  if (rows) {
    const tpl = document.getElementById('medRowTpl');
    const add = () => { rows.appendChild(tpl.content.cloneNode(true)); };
    window.resetMedRows = () => { rows.innerHTML = ''; add(); };
    document.getElementById('addMedRow').addEventListener('click', add);
    rows.addEventListener('click', e => { if (e.target.classList.contains('rm-row') && rows.children.length > 1) e.target.closest('.med-row').remove(); });
    add();
  }

  
  const rdvSel = document.getElementById('rdvSelect'), patSel = document.getElementById('consPatient');
  if (rdvSel && patSel) rdvSel.addEventListener('change', () => {
    const o = rdvSel.selectedOptions[0]; if (o && o.dataset.patient) patSel.value = o.dataset.patient;
  });
});
