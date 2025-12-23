document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('theme-toggle');
    const body = document.body;
    const icon = toggleBtn.querySelector('i');

    // 1. Vérifier le stockage local
    // Si l'utilisateur a EXPLICITEMENT demandé "light", on l'active
    const savedTheme = localStorage.getItem('theme');
    
    if (savedTheme === 'light') {
        body.classList.add('light-mode');
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon'); // Affiche la lune pour revenir au sombre
    } else {
        // Sinon (défaut ou 'dark'), on reste en sombre
        // L'icône par défaut dans le HTML doit être le Soleil
    }

    // 2. Clic sur le bouton
    toggleBtn.addEventListener('click', () => {
        body.classList.toggle('light-mode');
        
        if (body.classList.contains('light-mode')) {
            localStorage.setItem('theme', 'light');
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        } else {
            localStorage.setItem('theme', 'dark');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    });

    // Protection Clic Droit
    document.addEventListener('contextmenu', e => e.preventDefault());
});