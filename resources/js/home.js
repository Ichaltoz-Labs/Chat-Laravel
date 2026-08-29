export function initHome() {
    const form = document.getElementById('create-room-form');
    if (!form) return;

    const name = form.querySelector('#name');
    const stored = localStorage.getItem('tempchat_name');

    if (stored && !name.value) {
        name.value = stored;
    }

    form.addEventListener('submit', () => {
        localStorage.setItem('tempchat_name', name.value.trim());
    });
}