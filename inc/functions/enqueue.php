<?php

 /**
 * jQuery migrate remove
 */
add_action('wp_default_scripts', function ($scripts) {
    if (!empty($scripts->registered['jquery'])) {
        $scripts->registered['jquery']->deps = array_diff($scripts->registered['jquery']->deps, ['jquery-migrate']);
    }
});



// Define path and URL to the MMM Branding.
define( 'MY_MMM_PATH', get_stylesheet_directory() . '/inc/mmm/' );
define( 'MY_MMM_URL', get_stylesheet_directory_uri() . '/inc/mmm/' );

// Include the MMM plugin.
include_once( MY_MMM_PATH . 'mmm-branding.php' );

// Customize the url setting to fix incorrect asset URLs.
add_filter('mmm/settings/url', 'my_mmm_settings_url');
function my_mmm_settings_url( $url ) {
    return MY_MMM_URL;
}

// Define path and URL to the ACF plugin.
define( 'MY_ACF_PATH', get_stylesheet_directory() . '/inc/acf/' );
define( 'MY_ACF_URL', get_stylesheet_directory_uri() . '/inc/acf/' );

// Include the ACF plugin.
include_once( MY_ACF_PATH . 'acf.php' );

// Customize the url setting to fix incorrect asset URLs.
add_filter('acf/settings/url', 'my_acf_settings_url');
function my_acf_settings_url( $url ) {
    return MY_ACF_URL;
}

/**
* Add options page ACF
*/
if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page(array(
		'page_title' 	=> 'Opzioni Generali',
		'menu_title'	=> 'Opzioni Tema',
		'menu_slug' 	=> 'theme-general-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));
}


/**
 * Rimozione dei menu contestuali
 */
function rimozione_menu_contestuali() {

	// Ottengo informazioni utente corrente
	$current_user = wp_get_current_user();

	// Se è ID --1-- allora stoppo l'esecuzione della funzione
	if ( $current_user->ID === 1 ) {
		return;
	}
	
	// Rimozione delle pagine per tutti gli altri utenti
    remove_menu_page( 'edit-comments.php' );
	remove_menu_page( 'edit.php?post_type=acf-field-group' );
	remove_menu_page( 'admin_2020_content' );
	remove_menu_page( 'index.php' );
	remove_menu_page( 'litespeed' );
	remove_menu_page( 'wppusher' );

}
add_action( 'admin_head', 'rimozione_menu_contestuali' );


/**
 * Nascondo certi plugins ad alcuni utenti dalla lista di WP
 */
function mmm_nascondo_plugins( $plugins ) {

	// Ottengo informazioni utente corrente
	$current_user = wp_get_current_user();

	// Se è ID 1 allora stoppo l'esecuzione della funzione
	if ( $current_user->ID === 1 ) {
		return $plugins;
	}

	// Rimuovo i plugins che non mi interessano
	
	
//	if ( is_plugin_active( 'post-types-order/post-types-order.php' ) ) {
//		unset( $plugins['post-types-order/post-types-order.php'] );
//	}
	
//	if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
//		unset( $plugins['contact-form-7/wp-contact-form-7.php'] );
//	}
	
	if ( is_plugin_active( 'admin-2020/admin-2020.php' ) ) {
		unset( $plugins['admin-2020/admin-2020.php'] );
	}
	
	if ( is_plugin_active( 'worker/init.php' ) ) {
		unset( $plugins['worker/init.php'] );
	}
	
	if ( is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
		unset( $plugins['advanced-custom-fields-pro/acf.php'] );
	}

	if ( is_plugin_active( 'wppusher/wppusher.php' ) ) {
		unset( $plugins['wppusher/wppusher.php'] );
	}

	if ( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) ) {
		unset( $plugins['litespeed-cache/litespeed-cache.php'] );
	}

	return $plugins;

}
add_filter( 'all_plugins', 'mmm_nascondo_plugins');


/**
 * Rimozione notifiche aggiornamenti plugin
 */
add_action('admin_enqueue_scripts', 'block_dismissable_admin_notices');
add_action('login_enqueue_scripts', 'block_dismissable_admin_notices');

function block_dismissable_admin_notices() {
   echo '<style>.wp-core-ui .notice{ display: none !important; }</style>';
}


/**
 * Rimozione full screen mode Gutenberg
 */
function rimozione_gutenberg_fullscreen() {
    $script = "jQuery( window ).load(function() { const isFullscreenMode = wp.data.select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ); if ( isFullscreenMode ) { wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' ); } });";
    wp_add_inline_script( 'wp-blocks', $script );
}
add_action( 'enqueue_block_editor_assets', 'rimozione_gutenberg_fullscreen' );



/** 
 * Disabilito aggiornamenti temi e plugin
 */
define( 'DISALLOW_FILE_EDIT', false );
define( 'DISALLOW_FILE_MODS', false );