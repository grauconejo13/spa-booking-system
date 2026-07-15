const toggle = document.querySelector('.nav-toggle');
const navigation = document.querySelector('.primary-nav');

if (toggle instanceof HTMLButtonElement && navigation instanceof HTMLElement) {
    document.documentElement.classList.add('js');
    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!isOpen));
        navigation.classList.toggle('is-open', !isOpen);
    });
}

