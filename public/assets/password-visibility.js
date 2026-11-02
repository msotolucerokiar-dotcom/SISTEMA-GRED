(() => {
  const input = document.getElementById('password');
  const button = document.getElementById('password-visibility');
  const hiddenMark = document.getElementById('password-hidden-mark');
  if (!input || !button || !hiddenMark) return;

  button.addEventListener('click', () => {
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
    button.setAttribute('aria-pressed', String(visible));
    hiddenMark.style.display = visible ? 'none' : '';
  });
})();
