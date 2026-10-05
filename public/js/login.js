const csrf = document.querySelector('meta[name="csrf-token"]').content;
const form = document.querySelector('#login-form');
const errorBox = document.querySelector('#login-error');

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {})},
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body.message || Object.values(body.errors || {}).flat()[0] || 'Não foi possível concluir a operação.');
    return body;
}

form.addEventListener('submit', async event => {
    event.preventDefault();
    errorBox.hidden = true;
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Entrando…';
    try {
        await request('/api/login', {method: 'POST', body: new FormData(form)});
        window.location.assign('/sistema');
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.hidden = false;
    } finally {
        button.disabled = false;
        button.textContent = 'Entrar';
    }
});

document.querySelector('#forgot-button').addEventListener('click', async () => {
    const email = prompt('Informe o e-mail cadastrado para receber as instruções:');
    if (!email) return;
    try {
        await request('/api/forgot-password', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email}),
        });
        alert('Se o e-mail estiver cadastrado, as instruções serão enviadas.');
    } catch (error) {
        alert(error.message);
    }
});
