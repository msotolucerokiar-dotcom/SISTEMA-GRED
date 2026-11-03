document.addEventListener('pointerdown', (event) => {
  document.querySelectorAll('.notification-bell[open]').forEach((bell) => {
    if (!bell.contains(event.target)) bell.open = false;
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  document.querySelectorAll('.notification-bell[open]').forEach((bell) => {
    bell.open = false;
    bell.querySelector('summary')?.focus();
  });
});
