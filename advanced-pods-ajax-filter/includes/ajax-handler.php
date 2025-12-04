<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function apaf_filter_imoveis_handler() {
    // Verify Nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'apaf_nonce' ) ) {
        wp_send_json_error( 'Permissão negada.' );
    }

    $args = array(
        'post_type'      => 'imovel',
        'posts_per_page' => -1, // Or set a limit
        'post_status'    => 'publish',
        'tax_query'      => array( 'relation' => 'AND' ),
        'meta_query'     => array( 'relation' => 'AND' ),
    );

    // --- Search Term (s) ---
    if ( ! empty( $_POST['s'] ) ) {
        $args['s'] = sanitize_text_field( $_POST['s'] );
    }

    // --- Taxonomy Queries ---
    $taxonomies = array( 'property_type', 'zone', 'cidade', 'neighborhood' );
    foreach ( $taxonomies as $tax ) {
        if ( ! empty( $_POST[ $tax ] ) ) {
            $args['tax_query'][] = array(
                'taxonomy' => $tax,
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_POST[ $tax ] ),
            );
        }
    }

    // --- Meta Queries ---

    // Price (preco_venda) - BETWEEN
    $min_price = isset( $_POST['min_price'] ) ? floatval( $_POST['min_price'] ) : 0;
    $max_price = isset( $_POST['max_price'] ) ? floatval( $_POST['max_price'] ) : 999999999;

    // Only add if not default values (though logic handles it fine, optimization)
    $args['meta_query'][] = array(
        'key'     => 'preco_venda',
        'value'   => array( $min_price, $max_price ),
        'type'    => 'NUMERIC',
        'compare' => 'BETWEEN',
    );

    // Specs: >= comparison
    // Bedrooms (quartos)
    if ( ! empty( $_POST['quartos'] ) ) {
        $args['meta_query'][] = array(
            'key'     => 'quartos',
            'value'   => intval( $_POST['quartos'] ),
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    // Bathrooms (banheiros)
    if ( ! empty( $_POST['banheiros'] ) ) {
        $args['meta_query'][] = array(
            'key'     => 'banheiros',
            'value'   => intval( $_POST['banheiros'] ),
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    // Garages (vagas)
    if ( ! empty( $_POST['vagas'] ) ) {
        $args['meta_query'][] = array(
            'key'     => 'vagas',
            'value'   => intval( $_POST['vagas'] ),
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    }

    // Financing (aceita_financiamento)
    // Check for '1' or 'yes'
    if ( ! empty( $_POST['aceita_financiamento'] ) ) {
        $args['meta_query'][] = array(
            'key'     => 'aceita_financiamento',
            'value'   => array( '1', 'yes', 'sim', 'true' ), // Broader check just in case
            'compare' => 'IN',
        );
    }

    // --- Query ---
    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        ob_start();
        while ( $query->have_posts() ) {
            $query->the_post();

            // Get Meta Data
            $price = get_post_meta( get_the_ID(), 'preco_venda', true );
            $quartos = get_post_meta( get_the_ID(), 'quartos', true );
            $banheiros = get_post_meta( get_the_ID(), 'banheiros', true );
            $vagas = get_post_meta( get_the_ID(), 'vagas', true );

            // Format Price
            $formatted_price = 'R$ ' . number_format( floatval( $price ), 2, ',', '.' );

            // Get Location (City - Neighborhood)
            $cities = get_the_terms( get_the_ID(), 'cidade' );
            $city_name = $cities && ! is_wp_error( $cities ) ? $cities[0]->name : '';

            $neighborhoods = get_the_terms( get_the_ID(), 'neighborhood' );
            $neighborhood_name = $neighborhoods && ! is_wp_error( $neighborhoods ) ? $neighborhoods[0]->name : '';

            $location = $city_name;
            if($city_name && $neighborhood_name) $location .= ' - ' . $neighborhood_name;

            // Get Featured Image
            $image_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
            if ( ! $image_url ) {
                $image_url = 'https://via.placeholder.com/300x200?text=Sem+Imagem'; // Fallback
            }

            ?>
            <div class="apaf-card">
                <div class="apaf-card-image">
                    <a href="<?php the_permalink(); ?>">
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title_attribute(); ?>">
                    </a>
                </div>
                <div class="apaf-card-content">
                    <h4 class="apaf-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                    <div class="apaf-card-price"><?php echo esc_html( $formatted_price ); ?></div>

                    <div class="apaf-card-specs">
                        <?php if ( $quartos ) : ?>
                            <div class="apaf-card-spec-item" title="Quartos">
                                <span class="dashicons dashicons-admin-home"></span> <?php echo esc_html( $quartos ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $banheiros ) : ?>
                            <div class="apaf-card-spec-item" title="Banheiros">
                                <span class="dashicons dashicons-money-alt"></span> <?php echo esc_html( $banheiros ); ?>
                                <!-- Note: dashicons-money-alt isn't bath, but standard WP dashicons are limited. Using admin-home for generic. -->
                                <!-- Ideally use FontAwesome or SVG. I'll stick to text labels if icons aren't perfect or just use generic dashicons -->
                            </div>
                        <?php endif; ?>

                        <?php if ( $vagas ) : ?>
                            <div class="apaf-card-spec-item" title="Vagas">
                                <span class="dashicons dashicons-car"></span> <?php echo esc_html( $vagas ); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="apaf-card-location">
                        <span class="dashicons dashicons-location"></span> <?php echo esc_html( $location ); ?>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
        $html = ob_get_clean();
        wp_send_json_success( $html );
    } else {
        wp_send_json_error( 'Nenhum imóvel encontrado.' );
    }
}
add_action( 'wp_ajax_apaf_filter_imoveis', 'apaf_filter_imoveis_handler' );
add_action( 'wp_ajax_nopriv_apaf_filter_imoveis', 'apaf_filter_imoveis_handler' );
