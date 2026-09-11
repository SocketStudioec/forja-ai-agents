/* Aplica el tema guardado antes del primer pintado: evita el destello blanco.
   Vive en un archivo propio porque la política de seguridad de contenido
   (CSP) no permite scripts en línea. */
(function () {
    try {
        var t = localStorage.getItem('theme');
        if (t === 'light' || t === 'dark') {
            document.documentElement.setAttribute('data-theme', t);
        }
    } catch (e) {}
})();
