jQuery(document).ready(function($) {

    // --- UI Logic ---

    // Open Modal
    $('#apaf-open-filters').on('click', function() {
        $('#apaf-modal').addClass('show');
        $('body').css('overflow', 'hidden'); // Prevent background scrolling
    });

    // Close Modal
    $('.apaf-close-modal, .apaf-modal-overlay').on('click', function() {
        $('#apaf-modal').removeClass('show');
        $('body').css('overflow', '');
    });

    // Initialize noUiSlider
    var slider = document.getElementById('apaf-price-slider');
    if (slider) {
        noUiSlider.create(slider, {
            start: [0, 10000000], // Initial range
            connect: true,
            range: {
                'min': 0,
                'max': 10000000
            },
            step: 10000,
            format: {
                to: function (value) {
                    return parseInt(value);
                },
                from: function (value) {
                    return parseInt(value);
                }
            }
        });

        var minPriceInput = document.getElementById('apaf-min-price');
        var maxPriceInput = document.getElementById('apaf-max-price');
        var minPriceLabel = document.getElementById('apaf-price-min-label');
        var maxPriceLabel = document.getElementById('apaf-price-max-label');

        slider.noUiSlider.on('update', function (values, handle) {
            var value = values[handle];
            if (handle) {
                maxPriceInput.value = value;
                maxPriceLabel.innerHTML = 'R$ ' + new Intl.NumberFormat('pt-BR').format(value);
            } else {
                minPriceInput.value = value;
                minPriceLabel.innerHTML = 'R$ ' + new Intl.NumberFormat('pt-BR').format(value);
            }
        });
    }

    // --- Search Logic ---

    function performSearch() {
        var formData = $('#apaf-search-form').serialize();

        // Show loading state (optional but good for UX)
        $('#apaf-results-grid').html('<div class="apaf-loading">Carregando imóveis...</div>');

        $.ajax({
            url: apaf_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=apaf_filter_imoveis&nonce=' + apaf_ajax.nonce,
            success: function(response) {
                if(response.success) {
                    $('#apaf-results-grid').html(response.data);
                } else {
                    $('#apaf-results-grid').html('<div class="apaf-no-results">Nenhum imóvel encontrado.</div>');
                }
            },
            error: function() {
                $('#apaf-results-grid').html('<div class="apaf-error">Ocorreu um erro na busca. Tente novamente.</div>');
            }
        });
    }

    // Intercept "Buscar" button
    $('#apaf-search-btn').on('click', function(e) {
        e.preventDefault();
        performSearch();
    });

    // Intercept "Aplicar Filtros" button
    $('#apaf-apply-filters').on('click', function(e) {
        e.preventDefault();
        $('#apaf-modal').removeClass('show');
        $('body').css('overflow', '');
        performSearch();
    });

    // Optional: Trigger search on enter in text input
    $('.apaf-input-pill').on('keypress', function(e) {
        if(e.which == 13) {
            e.preventDefault();
            performSearch();
        }
    });

    // Initial Search (Optional: load all properties on page load)
    performSearch();

});
