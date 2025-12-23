// script.js
document.addEventListener('DOMContentLoaded', () => {
    
    // Désactiver le clic droit sur toute la page (Protection basique)
    document.addEventListener('contextmenu', event => event.preventDefault());

    // Désactiver certains raccourcis clavier (CTRL+P, CTRL+S)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 's')) {
            e.preventDefault();
            alert("L'impression et la sauvegarde sont désactivées.");
        }
    });

    console.log("Rucher École - Protection active");
});
