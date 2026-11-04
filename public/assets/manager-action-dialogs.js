document.addEventListener('click', (event) => {
  const opener = event.target.closest('[data-modal-open]');
  if (opener) {
    const dialog = document.getElementById(opener.dataset.modalOpen);
    if (dialog instanceof HTMLDialogElement) dialog.showModal();
    return;
  }
  const closer = event.target.closest('[data-modal-close]');
  if (closer) closer.closest('dialog')?.close();
});

document.querySelectorAll('.manager-action-dialog').forEach((dialog) => {
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });
});

document.addEventListener('pointerdown', (event) => {
  document.querySelectorAll('[data-workload-menu][open]').forEach((menu) => {
    if (!menu.contains(event.target)) menu.open = false;
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  document.querySelectorAll('[data-workload-menu][open]').forEach((menu) => {
    menu.open = false;
    menu.querySelector('summary')?.focus();
  });
});
