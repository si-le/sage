<?php

namespace App;

use Roots\Sage\Container;
use Roots\Sage\Assets\JsonManifest;
use Roots\Sage\Template\Blade;
use Roots\Sage\Template\BladeProvider;

/**
 * Theme assets
 */
add_action( 'wp_enqueue_scripts',
    function () {

        // we load jquer and migrate from local node_modules
        wp_deregister_script( 'jquery-core' );
        wp_register_script( 'jquery-core', asset_path( 'scripts/jquery.min.js' ), array(), '3.5.1' );
        wp_deregister_script( 'jquery-migrate' );
        wp_register_script( 'jquery-migrate', asset_path( 'scripts/jquery-migrate.min.js' ), array(), '3.3.1' );

        wp_enqueue_style( 'sage/main.css', asset_path( 'styles/main.css' ), false, null );
        wp_enqueue_script( 'sage/main.js', asset_path( 'scripts/main.js' ), [ 'jquery' ], null, true );

        if ( is_single() && comments_open() && get_option( 'thread_comments' ) ) {
            wp_enqueue_script( 'comment-reply' );
        }
    },
    100 );

/**
 * Block Editor Scripts
 */
add_action( 'enqueue_block_editor_assets',
    function () {
        /**
         * Register block blacklist
         */
        wp_enqueue_script(
            'sage/editor.js',
            asset_path( 'scripts/editor.js' ),
            [ 'wp-editor', 'wp-dom-ready', 'wp-edit-post' ],
            null,
            true
        );
    } );

/**
 * Theme setup
 */
add_action( 'after_setup_theme',
    function () {
        /**
         * Enable features from Soil when plugin is activated
         * @link https://roots.io/plugins/soil/
         */
        add_theme_support( 'soil-clean-up' );
        //add_theme_support('soil-jquery-cdn');
        add_theme_support( 'soil-nav-walker' );
        add_theme_support( 'soil-nice-search' );
        add_theme_support( 'soil-relative-urls' );
        //add_theme_support( 'soil-disable-rest-api' );
        add_theme_support( 'soil-disable-asset-versioning' );
        add_theme_support( 'soil-disable-trackbacks' );
        add_theme_support( 'soil-js-to-footer' );

        /**
         * Add theme support for Wide Alignment
         * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#wide-alignment
         */
        add_theme_support( 'align-wide' );

        /**
         * Enable responsive embeds
         * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#responsive-embedded-content
         */
        add_theme_support( 'responsive-embeds' );

        /**
         * Dequeue Gutenberg CSS
         * @link https://wordpress.org/gutenberg/?s=dequeue (404)
         */
        add_action( 'wp_enqueue_scripts',
            function () {
                wp_dequeue_style( 'wp-block-library' );
            },
            100 );

        /**
         * Add color palette support
         */
        add_theme_support( 'editor-color-palette', ( block_vars() )->colors );

        /**
         * Add font size support
         */
        add_theme_support( 'editor-font-sizes', ( block_vars() )->font_sizes );

        /**
         * Add editor styles
         * @link https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#editor-styles
         */
        add_theme_support( 'editor-styles' );
        add_editor_style( asset_path( 'styles/main.css' ) );

        /**
         * Enable plugins to manage the document title
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
         */
        add_theme_support( 'title-tag' );

        /**
         * Register navigation menus
         * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
         */
        register_nav_menus( [
            'primary_navigation' => __( 'Primary Navigation', 'sage' ),
        ] );

        /**
         * Enable post thumbnails
         * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
         */
        add_theme_support( 'post-thumbnails' );

        /**
         * Enable HTML5 markup support
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
         */
        add_theme_support( 'html5', [ 'caption', 'comment-form', 'comment-list', 'gallery', 'search-form' ] );

        /**
         * Enable selective refresh for widgets in customizer
         * @link https://developer.wordpress.org/themes/advanced-topics/customizer-api/#theme-support-in-sidebars
         */
        add_theme_support( 'customize-selective-refresh-widgets' );

    },
    20 );

/**
 * Register sidebars
 */
add_action( 'widgets_init',
    function () {
        $config = [
            'before_widget' => '<section class="widget %1$s %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3>',
            'after_title'   => '</h3>',
        ];
        register_sidebar( [
                              'name' => __( 'Primary', 'sage' ),
                              'id'   => 'sidebar-primary',
                          ] + $config );
        register_sidebar( [
                              'name' => __( 'Footer', 'sage' ),
                              'id'   => 'sidebar-footer',
                          ] + $config );
    } );

/**
 * Updates the `$post` variable on each iteration of the loop.
 * Note: updated value is only available for subsequently loaded views, such as partials
 */
add_action( 'the_post',
    function ( $post ) {
        sage( 'blade' )->share( 'post', $post );
    } );

/**
 * Setup Sage options
 */
add_action( 'after_setup_theme',
    function () {
        /**
         * Add JsonManifest to Sage container
         */
        sage()->singleton( 'sage.assets',
            function () {
                return new JsonManifest( config( 'assets.manifest' ), config( 'assets.uri' ) );
            } );

        /**
         * Add Blade to Sage container
         */
        sage()->singleton( 'sage.blade',
            function ( Container $app ) {
                $cachePath = config( 'view.compiled' );
                if ( ! file_exists( $cachePath ) ) {
                    wp_mkdir_p( $cachePath );
                }
                ( new BladeProvider( $app ) )->register();

                return new Blade( $app['view'] );
            } );

        /**
         * Create @asset() Blade directive
         */
        sage( 'blade' )->compiler()->directive( 'asset',
            function ( $asset ) {
                return "<?= " . __NAMESPACE__ . "\\asset_path({$asset}); ?>";
            } );
    } );
