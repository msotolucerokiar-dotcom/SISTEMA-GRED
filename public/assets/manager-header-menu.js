const managerHeaderMenus = '.manager-account[open]';

document.addEventListener('pointerdown', (event) => {
  document.querySelectorAll(managerHeaderMenus).forEach((menu) => {
    if (!menu.contains(event.target)) menu.open = false;
  });
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  document.querySelectorAll(managerHeaderMenus).forEach((menu) => {
    menu.open = false;
    menu.querySelector('summary')?.focus();
  });
});
