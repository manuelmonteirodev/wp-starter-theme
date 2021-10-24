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
	remove_menu_page( 'elementor' );
	remove_menu_page( 'edit.php?post_type=elementor_library' );

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
 * Inserisco il preload nel link rel [Google page speed]
 */
function add_rel_preload($html, $handle, $href, $media) {
    
    if (is_admin())
        return $html;

     $html = <<<EOT
<link rel='preload' as='style' onload="this.onload=null;this.rel='stylesheet'" id='$handle' href='$href' type='text/css' media='all' />
EOT;
    return $html;
}
add_filter( 'style_loader_tag', 'add_rel_preload', 10, 4 );



/** 
 * Disabilito aggiornamenti temi e plugin
 */
define( 'DISALLOW_FILE_EDIT', false );
define( 'DISALLOW_FILE_MODS', false );



/**
 * Inserisco il css critico qui // IMPORTANTE CAMBIARE PER OGNI SITO INSERIRE IL CSS CRITICO MINIFICATO PRENDERLO DA QUESTO SITO ->  https://www.sitelocity.com/critical-path-css-generator
 */
function hook_css() {
    ?>
           <style> /* CAMBIARE QUESTA STRINGA */
			   @charset "UTF-8";html{line-height:1.15}body{margin:0}a{background-color:transparent}*::-webkit-file-upload-button{-webkit-appearance:button;font-family:inherit;font-size:inherit;font-style:inherit;font-variant:inherit;font-weight:inherit;line-height:inherit}*,::before,::after{box-sizing:inherit}html{box-sizing:border-box}body{color:rgb(64,64,64);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;font-size:1rem;line-height:1.5}h2{clear:both}i{font-style:italic}body{background-color:rgb(255,255,255);background-position:initial initial;background-repeat:initial initial}ul{margin:0 0 1.5em 3em}ul{list-style:disc}a{color:rgb(65,105,225)}.page{margin:0 0 1.5em}@font-face{font-family:eicons;src:url(../fonts/eicons.eot?5.13.0#iefix) format('embedded-opentype'),url(../fonts/eicons.woff2?5.13.0) format('woff2'),url(../fonts/eicons.woff?5.13.0) format('woff'),url(../fonts/eicons.ttf?5.13.0) format('truetype'),url(../fonts/eicons.svg?5.13.0#eicon) format('svg');font-weight:400;font-style:normal}[class^="eicon"]{display:inline-block;font-family:eicons;font-size:inherit;font-weight:400;font-style:normal;font-variant:normal;line-height:1;text-rendering:auto;-webkit-font-smoothing:antialiased}.eicon-menu-bar::before{content:'\e816'}.elementor-screen-only{position:absolute;top:-10000em;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0 0 0 0);border:0}.elementor{-webkit-hyphens:manual}.elementor *,.elementor ::after,.elementor ::before{box-sizing:border-box}.elementor a{-webkit-box-shadow:none;box-shadow:none;text-decoration:none}.elementor-section{position:relative}.elementor-section .elementor-container{display:-webkit-box;margin-right:auto;margin-left:auto;position:relative}.elementor-section.elementor-section-boxed>.elementor-container{max-width:1140px}.elementor-widget-wrap{position:relative;width:100%}.elementor:not(.elementor-bc-flex-widget) .elementor-widget-wrap{display:-webkit-box}.elementor-widget-wrap>.elementor-element{width:100%}.elementor-widget{position:relative}.elementor-column{min-height:1px}.elementor-column{position:relative;display:-webkit-box}.elementor-column-gap-default>.elementor-column>.elementor-element-populated{padding:10px}@media (max-width:767px){.elementor-column{width:100%}}[class^="eicon"]{display:inline-block;font-family:eicons;font-size:inherit;font-weight:400;font-style:normal;font-variant:normal;line-height:1;text-rendering:auto;-webkit-font-smoothing:antialiased}.eicon-menu-bar::before{content:'\e816'}.elementor-shape{overflow:hidden;position:absolute;left:0;width:100%;line-height:0;direction:ltr}.elementor-shape-bottom{bottom:-1px}.elementor-shape-bottom:not([data-negative="true"]) svg{z-index:-1}.elementor-shape[data-negative="false"].elementor-shape-bottom{-webkit-transform:rotate(180deg)}.elementor-shape svg{display:block;width:calc(100% + 1.3px);position:relative;left:50%;-webkit-transform:translateX(-50%)}.elementor-shape .elementor-shape-fill{fill:#fff;-webkit-transform-origin:50% 50%;-webkit-transform:rotateY(0deg)}.elementor-heading-title{padding:0;margin:0;line-height:1}.elementor-widget-heading .elementor-heading-title[class*="elementor-size-"]>a{color:inherit;font-size:inherit;line-height:inherit}.elementor-section.elementor-section-boxed>.elementor-container{max-width:1140px}@media (max-width:1024px){.elementor-section.elementor-section-boxed>.elementor-container{max-width:1024px}}@media (max-width:767px){.elementor-section.elementor-section-boxed>.elementor-container{max-width:767px}}.elementor-location-header::before{content:'';display:table;clear:both}.elementor-item::after,.elementor-item::before{display:block;position:absolute}.elementor-item:not(:hover):not(:focus):not(.elementor-item-active):not(.highlighted)::after,.elementor-item:not(:hover):not(:focus):not(.elementor-item-active):not(.highlighted)::before{opacity:0}.elementor-item-active::after,.elementor-item-active::before{-webkit-transform:scale(1)}.elementor-nav-menu--main .elementor-nav-menu a{padding:13px 20px}.elementor-nav-menu--layout-horizontal{display:-webkit-box}.elementor-nav-menu--layout-horizontal .elementor-nav-menu{display:-webkit-box}.elementor-nav-menu--layout-horizontal .elementor-nav-menu a{white-space:nowrap}.elementor-nav-menu__align-right .elementor-nav-menu{margin-left:auto}.elementor-nav-menu__align-right .elementor-nav-menu{-webkit-box-pack:end}.elementor-widget-nav-menu .elementor-widget-container{display:-webkit-box;-webkit-box-orient:vertical;-webkit-box-direction:normal}.elementor-nav-menu{position:relative;z-index:2}.elementor-nav-menu::after{content:'\00a0';display:block;height:0;font-style:normal;font-variant:normal;font-weight:400;font-size:0;line-height:0;font-family:serif;clear:both;visibility:hidden;overflow:hidden}.elementor-nav-menu,.elementor-nav-menu li{display:block;list-style:none;margin:0;padding:0;line-height:normal}.elementor-nav-menu a,.elementor-nav-menu li{position:relative}.elementor-nav-menu li{border-width:0}.elementor-nav-menu a{display:-webkit-box;-webkit-box-align:center}.elementor-nav-menu a{padding:10px 20px;line-height:20px}.elementor-nav-menu--dropdown .elementor-item.elementor-item-active{background-color:rgb(85,89,92);color:rgb(255,255,255)}.elementor-menu-toggle{display:-webkit-box;-webkit-box-align:center;-webkit-box-pack:center;font-size:22px;padding:.25em;border:0 solid;border-top-left-radius:3px;border-top-right-radius:3px;border-bottom-right-radius:3px;border-bottom-left-radius:3px;background-color:rgba(0,0,0,.0470588);color:rgb(73,76,79)}.elementor-nav-menu--dropdown{background-color:rgb(255,255,255);font-size:13px}.elementor-nav-menu--dropdown.elementor-nav-menu__container{margin-top:10px;-webkit-transform-origin:50% 0%;overflow:auto}.elementor-nav-menu--dropdown a{color:rgb(73,76,79)}.elementor-nav-menu--toggle .elementor-menu-toggle:not(.elementor-active)+.elementor-nav-menu__container{-webkit-transform:scaleY(0);max-height:0}@media (max-width:1024px){.elementor-nav-menu--dropdown-tablet .elementor-nav-menu--main{display:none}}.elementor-7 .elementor-element.elementor-element-fe821dd:not(.elementor-motion-effects-element-type-background){background-color:rgb(249,249,249)}.elementor-7 .elementor-element.elementor-element-fe821dd{border-top-left-radius:0;border-top-right-radius:0;border-bottom-right-radius:0;border-bottom-left-radius:0}.elementor-7 .elementor-element.elementor-element-fe821dd{padding:100px 0 0}.elementor-7 .elementor-element.elementor-element-fe821dd>.elementor-shape-bottom .elementor-shape-fill{fill:#000}.elementor-7 .elementor-element.elementor-element-fe821dd>.elementor-shape-bottom svg{width:calc(100% + 1.3px);height:45px}.elementor-7 .elementor-element.elementor-element-fe821dd>.elementor-shape-bottom{z-index:2}.elementor-21 .elementor-element.elementor-element-11cd26b:not(.elementor-motion-effects-element-type-background){background-color:rgb(255,255,255)}.elementor-21 .elementor-element.elementor-element-c1d44af .elementor-heading-title{color:rgb(68,68,68);font-family:'IBM Plex Sans',sans-serif;font-size:30px;font-weight:900;text-transform:lowercase}.elementor-21 .elementor-element.elementor-element-c396173 .elementor-menu-toggle{margin:0 auto}.elementor-21 .elementor-element.elementor-element-c396173 .elementor-nav-menu .elementor-item{font-family:'IBM Plex Sans',sans-serif;font-size:20px;font-weight:300;text-transform:lowercase}.elementor-21 .elementor-element.elementor-element-c396173 .elementor-nav-menu--main .elementor-item{color:rgb(68,68,68);fill:#444}@font-face{font-family:'IBM Plex Sans';font-style:italic;font-weight:100;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX8KVElMYYaJe8bpLHnCwDKhdTmdJZLUdc.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:italic;font-weight:200;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX7KVElMYYaJe8bpLHnCwDKhdTm2Idcdvfr.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:italic;font-weight:300;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX7KVElMYYaJe8bpLHnCwDKhdTmvIRcdvfr.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:italic;font-weight:400;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX-KVElMYYaJe8bpLHnCwDKhdTuF6ZM.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:italic;font-weight:500;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX7KVElMYYaJe8bpLHnCwDKhdTm5IVcdvfr.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:italic;font-weight:600;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX7KVElMYYaJe8bpLHnCwDKhdTmyIJcdvfr.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:italic;font-weight:700;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX7KVElMYYaJe8bpLHnCwDKhdTmrINcdvfr.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:normal;font-weight:100;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX-KVElMYYaJe8bpLHnCwDKjbLuF6ZM.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:normal;font-weight:200;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX9KVElMYYaJe8bpLHnCwDKjR7_AIFscQ.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:normal;font-weight:300;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX9KVElMYYaJe8bpLHnCwDKjXr8AIFscQ.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:normal;font-weight:400;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYXgKVElMYYaJe8bpLHnCwDKhdHeEA.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:normal;font-weight:500;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX9KVElMYYaJe8bpLHnCwDKjSL9AIFscQ.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:normal;font-weight:600;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX9KVElMYYaJe8bpLHnCwDKjQ76AIFscQ.ttf) format('truetype')}@font-face{font-family:'IBM Plex Sans';font-style:normal;font-weight:700;src:url(https://fonts.gstatic.com/s/ibmplexsans/v9/zYX9KVElMYYaJe8bpLHnCwDKjWr7AIFscQ.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:italic;font-weight:100;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOiCnqEu92Fr1Mu51QrEzAdKg.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:italic;font-weight:300;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOjCnqEu92Fr1Mu51TjASc6CsE.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:italic;font-weight:400;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOkCnqEu92Fr1Mu51xIIzc.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:italic;font-weight:500;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOjCnqEu92Fr1Mu51S7ACc6CsE.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:italic;font-weight:700;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOjCnqEu92Fr1Mu51TzBic6CsE.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:italic;font-weight:900;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOjCnqEu92Fr1Mu51TLBCc6CsE.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:normal;font-weight:100;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOkCnqEu92Fr1MmgVxIIzc.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:normal;font-weight:300;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOlCnqEu92Fr1MmSU5fBBc9.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:normal;font-weight:400;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOmCnqEu92Fr1Mu4mxP.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:normal;font-weight:500;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOlCnqEu92Fr1MmEU9fBBc9.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:normal;font-weight:700;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOlCnqEu92Fr1MmWUlfBBc9.ttf) format('truetype')}@font-face{font-family:Roboto;font-style:normal;font-weight:900;src:url(https://fonts.gstatic.com/s/roboto/v29/KFOlCnqEu92Fr1MmYUtfBBc9.ttf) format('truetype')}
		   </style>
    <?php
}
add_action('wp_head', 'hook_css');