//calcola la larghezza della scrollbar e la inserisce come variabile css in html
function _calculateScrollbarWidth() {
    document.documentElement.style.setProperty('--scrollbar-width', (window.innerWidth - document.documentElement.clientWidth) + "px");
}

// on resize
window.addEventListener('resize', _calculateScrollbarWidth, false);
// on dom load
document.addEventListener('DOMContentLoaded', _calculateScrollbarWidth, false); 
// on load (assets loaded as well)
window.addEventListener('load', _calculateScrollbarWidth);

//spostamento del banner ue allo scroll
document.addEventListener('DOMContentLoaded', function () {
    const banner = document.getElementById('banner_ue');

    if (!banner) return;

    let hasChanged = false;

    function checkScroll() {
        if (!hasChanged && window.scrollY > 300) {
            banner.style.display = 'none';
            hasChanged = true;
            window.removeEventListener('scroll', checkScroll);
        }
    }

    window.addEventListener('scroll', checkScroll);
});
