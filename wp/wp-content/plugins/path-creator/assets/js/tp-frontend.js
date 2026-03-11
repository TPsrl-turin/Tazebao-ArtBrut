jQuery(document).ready(function($) {
    console.log('TP Debug: Script Loaded'); // Init check
    
    // --- Modal Logic ---
    var modal = $('#tp-step-modal');
    var btn = $('#tp-add-step-btn');
    var span = $('.tp-close-modal');

    btn.on('click', function() {
        modal.show();
        scrollModalToTop();
        
        // Scroll background to form section
        scrollToForm();

        // Reset form
        $('#tp-step-form')[0].reset();
        $('#step_id').val('0');
        $('#tp-modal-title').text('Add New Step');
        $('#tp-step-save-btn').text('Create Step');
        $('#step_items').empty().trigger('change');
    });

    span.on('click', function() {
        modal.hide();
    });

    $(window).on('click', function(event) {
        if (event.target == modal[0]) {
            modal.hide();
        }
    });

    // --- Select2 Logic ---
    function initSelect2(filter) {
        $('#step_items').select2({
            ajax: {
                url: tp_ajax_obj.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        action: 'tp_get_items',
                        term: params.term,
                        filter: filter
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            minimumInputLength: 0,
            placeholder: 'Select items...',
            templateResult: formatItem,
            templateSelection: formatItemSelection,
            dropdownParent: $('#tp-step-modal')
        });
    }

    function formatItem(item) {
        if (item.loading) {
            return item.text;
        }

        var $container = $(
            "<div class='tp-select2-item'>" +
                (item.image ? "<div class='tp-select2-thumbnail'><img src='" + item.image + "' /></div>" : "") +
                "<div class='tp-select2-text'>" + item.text + "</div>" +
            "</div>"
        );

        return $container;
    }

    function formatItemSelection(item) {
        return item.text; // Keep selection simple or add thumb if desired
    }

    // Initial Select2
    initSelect2('all');

    // Filter Change
    $('input[name="items_filter"]').on('change', function() {
        var filter = $(this).val();
        $('#step_items').empty().trigger('change'); // Clear selection and options
        $('#step_items').select2('destroy'); // Destroy previous instance
        initSelect2(filter); // Re-init with new filter
    });

    // --- Step Creation/Update AJAX ---
    $('#tp-step-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var stepId = $('#step_id').val();
        formData += '&action=tp_create_step&nonce=' + tp_ajax_obj.nonce;

        $.ajax({
            url: tp_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if (stepId !== '0') {
                        // Update existing item in list
                        var $item = $('.tp-step-item[data-id="' + response.data.id + '"]');
                        $item.find('.tp-step-title').text(response.data.title);
                    } else {
                        // Add new to list
                        var stepHtml = '<div class="tp-step-item" data-id="' + response.data.id + '">';
                        stepHtml += '<span class="tp-step-title">' + response.data.title + '</span>';
                        stepHtml += '<input type="hidden" name="steps[]" value="' + response.data.id + '">';
                        stepHtml += '<div class="tp-step-actions">';
                        stepHtml += '<button type="button" class="tp-edit-step button-link">Edit<span class="material-symbols-outlined">edit</span></button>';
                        stepHtml += '<button type="button" class="tp-remove-step button-link" style="margin-top:1rem">Delete<span class="material-symbols-outlined">delete</span></button>';
                        stepHtml += '</div>';
                        stepHtml += '</div>';
                        
                        $('#tp-steps-list').append(stepHtml);
                    }
                    
                    // Close modal
                    modal.hide();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('AJAX Error');
            }
        });
    });

    // --- Edit Step ---
    $(document).on('click', '.tp-edit-step', function() {
        var stepId = $(this).closest('.tp-step-item').data('id');
        scrollModalToTop();
        scrollToForm();
        
        $.ajax({
            url: tp_ajax_obj.ajax_url,
            type: 'GET',
            data: {
                action: 'tp_get_step',
                nonce: tp_ajax_obj.nonce,
                step_id: stepId
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#step_id').val(data.id);
                    $('#step_title').val(data.title);
                    $('#content_1').val(data.content_1);
                    $('#content_2').val(data.content_2);
                    $('#step_bg_color').val(data.background_color);
                    $('#step_text_color').val(data.text_color);
                    
                    $('#tp-modal-title').text('Edit Step');
                    $('#tp-step-save-btn').text('Update Step');
                    
                    // Populate Select2
                    $('#step_items').empty().trigger('change');
                    if (data.items && data.items.length > 0) {
                        data.items.forEach(function(item) {
                            var option = new Option(item.text, item.id, true, true);
                            $('#step_items').append(option).trigger('change');
                        });
                    }
                    
                    modal.show();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('AJAX Error');
            }
        });
    });

    // --- Remove Step ---
    $(document).on('click', '.tp-remove-step', function() {
        $(this).parent().remove();
    });

    // --- Favorites Logic ---
    
    // Toggle UI State Helper
    function toggleFavoriteUI($btn, isSaved) {
        if (isSaved) {
            $btn.addClass('unsave-item');
            $btn.find('.to_save').hide();
            $btn.find('.saved').css('display', 'inline-flex'); // Assuming flex for alignment
        } else {
            $btn.removeClass('unsave-item');
            $btn.find('.to_save').css('display', 'inline-flex');
            $btn.find('.saved').hide();
        }
    }

    // Init UI based on server state (if rendered with class)
    // Note: If the HTML comes pre-rendered with 'is-saved' class or similar, we should respect it.
    // For now, we assume the button starts in 'to_save' state unless we check on load (which is expensive for lists).
    // Better approach: The theme/template should render the correct state.
    // Here we just handle the click.

    $(document).on('click', '.save_btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var itemId = $btn.data('item-id');
        
        console.log('TP Debug: Clicked save-item', itemId); // Debug

        if (!tp_ajax_obj.is_logged_in) {
            // Show Signup Popup
            $('#tp-signup-modal').show();
            return;
        }

        // Optimistic UI Update (Optional, but feels faster)
        // var isCurrentlySaved = $btn.hasClass('is-saved');
        // toggleFavoriteUI($btn, !isCurrentlySaved);

        $.ajax({
            url: tp_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'tp_toggle_favorite',
                nonce: tp_ajax_obj.nonce,
                item_id: itemId
            },
            success: function(response) {
                if (response.success) {
                    var isSaved = response.data.action === 'added';
                    toggleFavoriteUI($btn, isSaved);
                    
                    // Update all other buttons for the same item on the page
                    $('.save_btn[data-item-id="' + itemId + '"]').each(function() {
                        toggleFavoriteUI($(this), isSaved);
                    });
                } else {
                    alert('Error: ' + response.data);
                    // Revert UI if optimistic
                }
            },
            error: function() {
                alert('Connection Error');
            }
        });
    });

    $(window).on('click', function(event) {
        if (event.target == $('#tp-signup-modal')[0]) {
            $('#tp-signup-modal').hide();
        }
    });

    // --- Dashboard Splide & Limit Logic ---
    
    var $dashboard = $('.tp-dashboard');
    if ($dashboard.length) {
        var pathCount = parseInt($dashboard.data('path-count')) || 0;
        var maxPaths = parseInt($dashboard.data('max-paths')) || 4;
        var createUrl = $('#tp-create-path-url').val();
        var limitModal = $('#tp-limit-modal');

        // 1. Splide Initialization
        var $slider = $dashboard.find('.tp-dashboard-slider');
        console.log('TP Debug: Slider element found:', $slider.length);
        
        if ($slider.length && typeof Splide !== 'undefined') {
            console.log('TP Debug: Initializing Splide for dashboard');
            var splide = new Splide($slider[0], {
                perPage: 4,
                gap: '1rem',
                arrows: false,
                pagination: true,
                breakpoints: {
                    1024: {
                        perPage: 3,
                    },
                    768: {
                        perPage: 1,
                    }
                }
            });

            function checkSliderState() {
                var w = $(window).width();
                console.log('TP Debug: checkSliderState', { width: w, pathCount: pathCount });
                
                if (w > 1024 && pathCount < 4) {
                    console.log('TP Debug: Desktop + few items -> Centered View');
                    if (splide.state.is(Splide.STATES.MOUNTED)) {
                        splide.destroy();
                    }
                    $slider.addClass('is-centered-view');
                    // Ensure it's visible even if destroyed
                    $slider.css('visibility', 'visible').css('display', 'block');
                } else {
                    console.log('TP Debug: Mobile/Tablet OR Desktop + many items -> Slider View');
                    $slider.removeClass('is-centered-view');
                    if (!splide.state.is(Splide.STATES.MOUNTED)) {
                        splide.mount();
                    }
                }
            }

            // Initial check
            checkSliderState();
            
            // Resize check
            $(window).on('resize', function() {
                checkSliderState();
            });
        } else {
            console.log('TP Debug: Slider NOT initialized. Splide defined?', typeof Splide !== 'undefined');
        }

        // 2. Create Path Trigger
        // Use event delegation for dynamically added buttons (though usually static in Gutenberg)
        $(document).on('click', '.tp-create-path-trigger', function(e) {
            e.preventDefault();
            
            if (pathCount >= maxPaths) {
                limitModal.show();
            } else {
                window.location.href = createUrl;
            }
        });

        // Close Limit Modal
        $('.tp-close-limit').on('click', function() {
            limitModal.hide();
        });

        $(window).on('click', function(event) {
            if (event.target == limitModal[0]) {
                limitModal.hide();
            }
        });
    }

    // --- Auto-Scroll Logic ---
    
    function scrollToForm() {
        var $el = $('#tp-path-form-section');
        if ($el.length) {
            // Calculate target: element offset minus header height (approx 100px)
            // Using .stop() to prevent "jumps" if multiple animations trigger
            var target = $el.offset().top - 100;
            $('html, body').stop().animate({
                scrollTop: target
            }, 1000);
        }
    }

    // 1. Scroll to form on load if tp_action is present (new/edit path)
    if (window.location.search.indexOf('tp_action=') !== -1 || window.location.hash === '#tp-path-form-section') {
        // Wait for Splide and other layout shifts to settle
        setTimeout(scrollToForm, 500);
    }

    // 2. Helper to scroll modal to top
    function scrollModalToTop() {
        $('#tp-step-modal').animate({ scrollTop: 0 }, 300);
    }

});
