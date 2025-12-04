<?php
/*
Plugin Name: Advanced Pods AJAX Filter
Description: V7 - AJAX Search Handler with V6 UI Design.
Version: 7.0
Author: Senior WordPress Developer
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define Plugin Constants
define( 'APAF_PATH', plugin_dir_path( __FILE__ ) );
define( 'APAF_URL', plugin_dir_url( __FILE__ ) );

// Enqueue Scripts and Styles
function apaf_enqueue_scripts() {
    // noUiSlider CDN
    wp_enqueue_style( 'nouislider-css', 'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css' );
    wp_enqueue_script( 'nouislider-js', 'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js', array(), '15.7.0', true );

    // Plugin Styles
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style( 'apaf-style', APAF_URL . 'assets/css/style.css', array( 'dashicons' ), '7.0' );

    // Plugin Script
    wp_enqueue_script( 'apaf-script', APAF_URL . 'assets/js/script.js', array( 'jquery', 'nouislider-js' ), '7.0', true );

    // Localize Script for AJAX
    wp_localize_script( 'apaf-script', 'apaf_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'apaf_nonce' )
    ));
}
add_action( 'wp_enqueue_scripts', 'apaf_enqueue_scripts' );

// Include AJAX Handler
require_once APAF_PATH . 'includes/ajax-handler.php';

// Helper function to get terms for dropdowns
function apaf_get_terms_dropdown( $taxonomy, $label ) {
    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => true, // Or false depending on preference
    ) );

    $output = '<select name="' . esc_attr( $taxonomy ) . '" class="apaf-select">';
    $output .= '<option value="">' . esc_html( $label ) . '</option>';

    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            $output .= '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>';
        }
    }

    $output .= '</select>';
    return $output;
}

// Shortcode
function apaf_shortcode_output() {
    ob_start();
    ?>
    <div class="apaf-wrapper">
        <!-- Sticky Bar -->
        <div class="apaf-sticky-bar">
            <form id="apaf-search-form">
                <div class="apaf-bar-container">
                    <!-- Search Input -->
                    <div class="apaf-search-input-wrapper">
                        <input type="text" name="s" placeholder="Buscar por código, bairro ou cidade..." class="apaf-input-pill">
                    </div>

                    <!-- Trigger Modal -->
                    <button type="button" id="apaf-open-filters" class="apaf-btn apaf-btn-pill apaf-btn-outline">
                        <span class="dashicons dashicons-filter"></span> Filtros
                    </button>

                    <!-- Search Button -->
                    <button type="submit" id="apaf-search-btn" class="apaf-btn apaf-btn-pill apaf-btn-primary">
                        Buscar
                    </button>
                </div>

                <!-- Modal -->
                <div id="apaf-modal" class="apaf-modal">
                    <div class="apaf-modal-overlay"></div>
                    <div class="apaf-modal-content">
                        <div class="apaf-modal-header">
                            <h3>Filtrar Imóveis</h3>
                            <span class="apaf-close-modal">&times;</span>
                        </div>

                        <div class="apaf-modal-body">
                            <!-- Taxonomies -->
                            <div class="apaf-row">
                                <div class="apaf-col">
                                    <label>Tipo de Imóvel</label>
                                    <?php echo apaf_get_terms_dropdown('property_type', 'Todos os tipos'); ?>
                                </div>
                                <div class="apaf-col">
                                    <label>Zona</label>
                                    <?php echo apaf_get_terms_dropdown('zone', 'Todas as zonas'); ?>
                                </div>
                            </div>

                            <div class="apaf-row">
                                <div class="apaf-col">
                                    <label>Cidade</label>
                                    <?php echo apaf_get_terms_dropdown('cidade', 'Todas as cidades'); ?>
                                </div>
                                <div class="apaf-col">
                                    <label>Bairro</label>
                                    <?php echo apaf_get_terms_dropdown('neighborhood', 'Todos os bairros'); ?>
                                </div>
                            </div>

                            <!-- Price Range (noUiSlider) -->
                            <div class="apaf-filter-group apaf-price-group">
                                <label>Faixa de Preço</label>
                                <div id="apaf-price-slider"></div>
                                <input type="hidden" name="min_price" id="apaf-min-price">
                                <input type="hidden" name="max_price" id="apaf-max-price">
                                <div class="apaf-price-labels">
                                    <span id="apaf-price-min-label">R$ 0</span> - <span id="apaf-price-max-label">R$ 10.000.000+</span>
                                </div>
                            </div>

                            <!-- Specs (Square Buttons) -->
                            <div class="apaf-filter-group">
                                <label>Quartos</label>
                                <div class="apaf-specs-buttons">
                                    <label><input type="radio" name="quartos" value=""> <span>Qto</span></label>
                                    <label><input type="radio" name="quartos" value="1"> <span>1+</span></label>
                                    <label><input type="radio" name="quartos" value="2"> <span>2+</span></label>
                                    <label><input type="radio" name="quartos" value="3"> <span>3+</span></label>
                                    <label><input type="radio" name="quartos" value="4"> <span>4+</span></label>
                                </div>
                            </div>

                            <div class="apaf-filter-group">
                                <label>Banheiros</label>
                                <div class="apaf-specs-buttons">
                                    <label><input type="radio" name="banheiros" value=""> <span>Ban</span></label>
                                    <label><input type="radio" name="banheiros" value="1"> <span>1+</span></label>
                                    <label><input type="radio" name="banheiros" value="2"> <span>2+</span></label>
                                    <label><input type="radio" name="banheiros" value="3"> <span>3+</span></label>
                                    <label><input type="radio" name="banheiros" value="4"> <span>4+</span></label>
                                </div>
                            </div>

                            <div class="apaf-filter-group">
                                <label>Vagas</label>
                                <div class="apaf-specs-buttons">
                                    <label><input type="radio" name="vagas" value=""> <span>Vag</span></label>
                                    <label><input type="radio" name="vagas" value="1"> <span>1+</span></label>
                                    <label><input type="radio" name="vagas" value="2"> <span>2+</span></label>
                                    <label><input type="radio" name="vagas" value="3"> <span>3+</span></label>
                                </div>
                            </div>

                            <!-- Financing -->
                            <div class="apaf-filter-group">
                                <label class="apaf-checkbox-label">
                                    <input type="checkbox" name="aceita_financiamento" value="1"> Aceita Financiamento
                                </label>
                            </div>
                        </div>

                        <div class="apaf-modal-footer">
                            <button type="button" id="apaf-apply-filters" class="apaf-btn apaf-btn-primary apaf-btn-block">Aplicar Filtros</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Container -->
        <div id="apaf-results-grid" class="apaf-results-grid">
            <!-- Results will be loaded here via AJAX -->
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'pods_advanced_filter', 'apaf_shortcode_output' );
