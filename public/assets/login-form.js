(() => {
  const form = document.getElementById('login-form');
  if (!form) return;

  const submitButton = form.querySelector('button[type="submit"]');
  if (!submitButton) return;
  const progress = document.getElementById('login-progress');
  const email = form.querySelector('#email');
  const password = form.querySelector('#password');
  for (const field of [email, password]) {
    if (!field) continue;
    field.addEventListener('invalid', () => {
      field.setCustomValidity(field === email
        ? (field.validity.valueMissing ? 'Ingresa tu correo electrónico.' : 'Ingresa un correo electrónico válido.')
        : 'Ingresa tu contraseña.');
    });
    field.addEventListener('input', () => field.setCustomValidity(''));
  }

  let submitting = false;
  form.addEventListener('submit', (event) => {
    if (submitting) {
      event.preventDefault();
      return;
    }
    submitting = true;
    submitButton.disabled = true;
    submitButton.textContent = 'Verificando acceso...';
    if (progress) progress.textContent = 'Verificando acceso...';
  });

  window.addEventListener('pageshow', () => {
    submitting = false;
    submitButton.disabled = false;
    submitButton.textContent = 'Iniciar sesión';
    if (progress) progress.textContent = '';
  });
})();
