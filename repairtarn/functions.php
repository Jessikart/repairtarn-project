<?php
/**
 * Fonctions du thème RepairTarn.
 */

if ( ! function_exists( 'repairtarn_setup' ) ) {
    function repairtarn_setup() {
        // Support du Full Site Editing.
        add_theme_support( 'block-templates' );

        // Support des styles globaux.
        add_theme_support( 'wp-block-styles' );

        // Largeur de contenu par défaut.
        if ( ! isset( $content_width ) ) {
            $GLOBALS['content_width'] = 1200;
        }
    }
}
add_action( 'after_setup_theme', 'repairtarn_setup' );

/**
 * Charger le style principal du thème (style.css).
 */
function repairtarn_enqueue_assets() {
    wp_enqueue_style(
        'repairtarn-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'repairtarn_enqueue_assets' );
