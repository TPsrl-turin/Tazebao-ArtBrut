jQuery(document).ready(function($) {
    $('.wgp-popup-wrapper').each(function() {
        var $popup = $(this);
        var popupId = $popup.data('popup-id');
        var delay = $popup.data('delay') * 1000; // Convert to ms
        var sessionKey = 'wgp_popup_closed_' + popupId;

        console.log('WGP: Init Popup ' + popupId, { delay: delay, sessionKey: sessionKey, isClosed: sessionStorage.getItem(sessionKey) });

        // Check Session
        if (sessionStorage.getItem(sessionKey)) {
            console.log('WGP: Popup ' + popupId + ' hidden due to session storage.');
            return; // Don't show if closed in this session
        }

        // Show after delay
        setTimeout(function() {
            console.log('WGP: Showing Popup ' + popupId);
            $popup.show();
            
            // Add Animation Class
            if ($popup.hasClass('wgp-mode-direct')) {
                var animOrigin = '';
                if ($popup.hasClass('wgp-anim-top')) animOrigin = 'top';
                else if ($popup.hasClass('wgp-anim-bottom')) animOrigin = 'bottom';
                else if ($popup.hasClass('wgp-anim-left')) animOrigin = 'left';
                else if ($popup.hasClass('wgp-anim-right')) animOrigin = 'right';
                
                if (animOrigin) {
                    $popup.find('.wgp-popup-content').addClass('wgp-animate-' + animOrigin);
                }
            } else {
                 // Lightbox simple fade in? Or just show.
                 // For now just show is fine as per requirements (shadowbox/lightbox)
                 // But maybe we want a fade in for lightbox too?
                 // Let's add a simple fade in for lightbox content
                 $popup.find('.wgp-popup-content').hide().fadeIn();
            }

        }, delay);

        // Close Logic
        function closePopup() {
            $popup.fadeOut();
            sessionStorage.setItem(sessionKey, 'true');
        }

        // Click on Overlay (Lightbox only)
        $popup.find('.wgp-popup-overlay').on('click', function() {
            closePopup();
        });

        // Click on .chiudi element
        $popup.on('click', '.chiudi', function() {
            closePopup();
        });
    });
});
