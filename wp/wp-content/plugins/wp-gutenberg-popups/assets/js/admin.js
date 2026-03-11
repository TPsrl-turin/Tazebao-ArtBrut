jQuery(document).ready(function($) {
    // Toggle Display Mode Fields
    function toggleDisplayMode() {
        var mode = $('#wgp_display_mode').val();
        if (mode === 'direct') {
            $('#wgp-direct-settings').slideDown();
        } else {
            $('#wgp-direct-settings').slideUp();
        }
    }
    $('#wgp_display_mode').on('change', toggleDisplayMode);

    // Toggle Page Targeting Fields
    function toggleTargeting() {
        var rule = $('#wgp_target_rule').val();
        if (rule !== 'all') {
            $('#wgp-page-selection').slideDown();
        } else {
            $('#wgp-page-selection').slideUp();
        }
    }
    $('#wgp_target_rule').on('change', toggleTargeting);

    // Initialize Select2
    $('#wgp_target_pages').select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'wgp_search_pages',
                    term: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Search for pages...',
    });
});
