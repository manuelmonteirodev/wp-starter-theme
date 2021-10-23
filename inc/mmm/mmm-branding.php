<?php

/*
  Plugin Name: WordPress Custom Option
  Plugin URI: http://www.manuelmonteiro.dev
  Description: Permette di sostituire alcune opzioni e brand WordPress con quelli di mmm (compresa la dashboard utente).
  Author: MMM S.r.l.
  Version: 1.0
  Author URI: http://www.manuelmonteiro.dev/
 */

if (!class_exists('mmmBranding')) {

    class mmmBranding {

        public static function init() {
            
            // disabilito xmlrpc
            add_filter('xmlrpc_enabled', '__return_false');

            // definisco la chiave Akismet, caso non ci sia in wp-config.php
            add_action('muplugins_loaded', array(__CLASS__, 'defineAkismetApiKey'), 5);
            
            // definisco la chiave di ACF PRO
            // NON FUNZIONA // add_action('init', array(__CLASS__, 'acf_auto_set_license_keys'), 5);

            // sistemo i widget della dashboard di WordPress
            add_action('wp_dashboard_setup', array(__class__, 'removeDashboardWidgets'), 99, 2);

            // personalizzo le capabilities dell'utente Editor
            add_action('admin_init', array(__class__, 'superEditor'));
            
            // rimuovo alcune capability "critiche" (installare temi e plugin) per utenti non-mmm
            add_action('wp_login', array(__CLASS__, 'removeAdminCapabilities'), 10, 2);

            // aggiungo shortcode mmmINCLUDE
            add_shortcode('mmmINCLUDE', array(__class__, 'mmmIncludeShortcode'));
            
            // nascondo interfaccia admin di ACF se sono in ambiente di produzione
            // add_filter('acf/settings/show_admin', array(__class__, 'showACFAdmin'));
            
            // nascondo gli update alert in produzione
            add_action('admin_head', array(__class__, 'hide_update_notice'), 1);
            add_action("wp_dashboard_setup", array(__class__, "hide_php_update_notice"));            
            
            // nascondo gli alert di woocommerce
            add_filter('woocommerce_helper_suppress_admin_notices', array(__class__, 'woocommerce_helper_suppress_admin_notices'));

            // alla creazione di un nuovo sito, elimino alcune noiose impostazioni predefinite di WP
            add_action('wpmu_new_blog', array(__class__, 'newSiteCreated'), 10, 6);

            // sovrascrivo le impostazioni di phpmailer per personalizzare il server di invio, nome mittente, ecc.
            add_action('phpmailer_init', array(__class__, 'mmmPhpmailerOverride'));  
            
            // evito che vengano inviate delle mail a indirizzi @test.com
            add_filter('wp_mail', array(__class__, 'wp_mail_remove_test_com'));
            
            /*
             * aggiungo i dati (pressoché) completi della richiesta effettuata dall'utente 
             * per la finalità di dimostrare l'avvenuto consenso (GDPR)
             */
            add_filter('wpcf7_mail_components', array(__class__, 'add_gdpr_data_to_cf7_emails'), 30, 3);
            
            // disabilito l'indice dell'autore
            add_filter('template_redirect', array(__class__, 'remove_author_page'));
            
            // disabilito invio notifiche di cambio email utente se fatto da admin mmm
            add_filter('send_password_change_email', array(__CLASS__, 'disable_password_change_notification'));
            
            /*
             * disabilito la schermata di verifica email administrator
             * cfr. https://make.wordpress.org/core/2019/10/17/wordpress-5-3-admin-email-verification-screen/
             */
            add_filter('admin_email_check_interval', '__return_false');
            
            /*
             * debug mail di aggiornamento automatico
             */
            // add_filter('auto_plugin_update_send_email', array(__CLASS__, 'debug_update_send_email'), 10, 2); // serve a testare il payload che viene passato alla funzione durante l'aggiornamento automatico 
            // add_filter('auto_theme_update_send_email', array(__CLASS__, 'debug_update_send_email'), 10, 2); // serve a testare il payload che viene passato alla funzione durante l'aggiornamento automatico
            add_filter('auto_theme_update_send_email', array(__CLASS__, 'enable_auto_plugin_update_send_email'), 11, 2); // uso per l'aggiornamento del tema la stessa funzione che uso per i plugin... il payload è uguale
            add_filter('auto_plugin_update_send_email', array(__CLASS__, 'enable_auto_plugin_update_send_email'), 11, 2);
        }
        
        /**
         * Abilito le notifiche mail per gli aggiornamenti automatici solo se si è verificato un errore
         * 
         * @param bool $enabled
         * @param array $update_results
         * @return boolean
         */
        static function enable_auto_plugin_update_send_email($enabled, $update_results) {
            
            // di default non mando
            $ret_val = false;
            
            // ciclo gli aggiornamenti fatti
            foreach ($update_results as $update_result) {
                
                // se incontro un errore, allora abilito la notifica
                if (!$update_result->result)
                    $ret_val = true;
                
            }
            
            // alé.
            return $ret_val;
        }
        
        /**
         * debugger stupido
         * @TODO rimuovere
         */
        static function debug_update_send_email($enabled, $update_results) {
            
            $message  = "enabled: $enabled \n";
            $message .= "update_result: " . print_r($update_results, true);
            
            wp_mail('m@manuelmonteiro.dev', '[DEBUG] debug_update_send_email', $message);
            
            return $enabled;
        }
        
        public static function remove_author_page() {
            global $wp_query;
            
            /*
             * la costante mmm_ENABLE_AUTHOR_ARCHIVE mi permette di abilitare l'archivio se necessario
             */
            if (is_author() && !is_404() && (!defined('mmm_ENABLE_AUTHOR_ARCHIVE') || mmm_ENABLE_AUTHOR_ARCHIVE === false)) {
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
            }
        }
        
        public static function disable_password_change_notification($send) {
            
            $cu = wp_get_current_user();
            $is_mmmman = preg_match('/(@|\.)manuelmonteiro\.dev$/', $cu->user_email);
            
            // se l'utente ha mail @studioazione.it il cambio password viene fatto silenziosamente
            if ($is_mmmman) {
                $send = false;
            }
            
            return $send;
        }

        public static function add_gdpr_data_to_cf7_emails($components, $wpcf7_get_current_contact_form, $instance) {
            
            $components['body'] .= "\n\n\n\n--- dati completi della richiesta effettuata dall'utente ---\n\n";
            $components['body'] .= "HTTP POST:\n" . print_r($_POST, true) . "\n\n";
            $components['body'] .= "COOKIE:\n" . print_r($_SERVER['HTTP_COOKIE'], true) . "\n\n";
            $components['body'] .= "REFERER:\n" . print_r($_SERVER['HTTP_REFERER'], true) . "\n\n";
            $components['body'] .= "USER_AGENT:\n" . print_r($_SERVER['HTTP_USER_AGENT'], true) . "\n\n";
            $components['body'] .= "REQUEST_TIME:\n" . print_r($_SERVER['REQUEST_TIME'], true) . "\n\n";
            $components['body'] .= "REMOTE_ADDR:\n" . print_r($_SERVER['REMOTE_ADDR'], true) . "\n\n";
            
            /*
             * fix per CloudFlare
             */
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']))
                $components['body'] .= "REMOTE_ADDR_CF:\n" . print_r($_SERVER['HTTP_CF_CONNECTING_IP'], true) . "\n\n";
            
            $components['body'] .= "HTTP GET:\n" . print_r($_GET, true) . "\n\n";

            return $components;
        }
        
        public static function woocommerce_helper_suppress_admin_notices($i) {
            return !WP_DEBUG;
        }
        
        public static function hide_update_notice() {
            if (!WP_DEBUG) {
                remove_action('admin_notices', 'update_nag', 3);
            }
        }
        
        public static function hide_php_update_notice() {
            remove_meta_box('dashboard_php_nag', 'dashboard', 'normal');
        }

        public static function loginRebranding() {
            
        }
        
        public static function showACFAdmin() {
            return isset($_GET['showACFAdmin']) ? true : WP_DEBUG; // un rigurgito di retrocompatibilità
        }
        
        public static function newSiteCreated($blog_id, $user_id, $domain, $path, $site_id, $meta) {

            global $switched;
            switch_to_blog($blog_id);

            // rimuovo "Ecco un altro sito WPCerto"
            update_option('blogdescription', '');

            // disabilito i commenti
            update_option('default_comment_status', 'closed');

            // disabilito il ping
            update_option('default_ping_status', 'closed');

            // abilito la moderazione dei commenti
            update_option('comment_moderation', 1);

            // disabilito il whitelisting degli utenti che hanno già commentato
            update_option('comment_whitelist', '');
            
            // disabilito l'utilizzo degli avatar nei commenti
            update_option('show_avatars', '');

            restore_current_blog();
        }

        public static function removeDashboardWidgets() {
            global $wp_meta_boxes;

            // sposto il widget "in sintesi" sulla destra
            $wp_meta_boxes['dashboard']['side']['core']['dashboard_right_now'] = $wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now'];

            // rimuovo praticamente tutti i widget di default
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
            unset($wp_meta_boxes['dashboard']['normal']['core']['wpseo-dashboard-overview']);
            unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts']);
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
            unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
            unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
            
            // debug
            // wp_die('<pre>' . print_r($wp_meta_boxes, true) . '</pre>');
        }
        
        /**
         * imposta automaticamente la license key di ACF PRO
         * cfr. https://gist.github.com/mattradford/6d5b8f3cd11ce1f62480#gistcomment-1933361
         */
        public static function acf_auto_set_license_keys() {
            
            if (
                    function_exists('acf_pro_get_license_key') &&
                    function_exists('acf_pro_update_license') &&
                    is_admin() &&
                    !acf_pro_get_license_key()
            ) {
                acf_pro_update_license('b3JkZXJfaWQ9ODE4MTd8dHlwZT1kZXZlbG9wZXJ8ZGF0ZT0yMDE2LTA1LTE4IDA5OjE0OjM0');
            }

        }

        /**
         * modifica le capabilities dell'utente Editor
         */
        public static function superEditor() {

            $roleObject = get_role('editor');

            // attribuisco all'editor la capacità di gestire i menu
            if (!$roleObject->has_cap('edit_theme_options')) {
                $roleObject->add_cap('edit_theme_options');
            }

            /*
             * GESTIONE DEGLI UTENTI
             * queste le capabilities coinvolte
             * NB è necessario attivare la funzionalità inserendo nella config:
             * 
             * define('WPCERTO_SUPEREDITOR_CAN_MANAGE_USERS', true);
             * 
             * NB2: in configurazione multisite, è necessario abilitare 
             * la possibilità di creare utenti per gli admin
             * http://wpcerto.dev/wp-admin/network/settings.php
             */
            $can_manage_users_caps = array(
                'create_users',
                'delete_users',
                'edit_users',
                'manage_network_users',
                'list_users',
                'promote_users',
                'remove_users',
            );

            if (defined('WPCERTO_SUPEREDITOR_CAN_MANAGE_USERS') && WPCERTO_SUPEREDITOR_CAN_MANAGE_USERS) {

                // se la gestione degli utenti è abilitata, procedo
                foreach ($can_manage_users_caps as $cap) {

                    if (!$roleObject->has_cap($cap)) {
                        $roleObject->add_cap($cap);
                    }
                }
            } else {
                
                // viceversa disattivo se attivato in precedenza
                foreach ($can_manage_users_caps as $cap) {

                    if ($roleObject->has_cap($cap)) {
                        $roleObject->remove_cap($cap);
                    }
                }
            }
        }

        /**
         * Override delle impostazioni di default del mailer, per funzionare con l'SMTP di WPCerto
         * @param Obj $phpmailer
         */
        public static function mmmPhpmailerOverride($phpmailer) {
            
            /*
             * cfr. https://www.register.it/assistenza/parametri-email/
             */
            
//            $phpmailer->isSMTP();     
////            $phpmailer->ReplyTo = get_bloginfo('admin_email');
//            $phpmailer->Host = 'authsmtp.securemail.pro';
//            $phpmailer->SMTPAuth = true; // Force it to use Username and Password to authenticate
//            $phpmailer->SMTPSecure = 'ssl';  
//            $phpmailer->Port = 465;
//            $phpmailer->Username = 'smtp@wpcerto.com';
//            $phpmailer->Password = '.jknkj.kjAA65v2bjd32hbskj__23';
//            $phpmailer->From = 'noreply@wpcerto.com';
//            $phpmailer->FromName = get_bloginfo('name');
            
            $phpmailer->isSMTP();     
//            $phpmailer->ReplyTo = get_bloginfo('admin_email');
            $phpmailer->Host = 'smtp.mailgun.org';
            $phpmailer->SMTPAuth = true; // Force it to use Username and Password to authenticate
            $phpmailer->SMTPSecure = 'tls';  
            $phpmailer->Port = 25;
            $phpmailer->Username = 'noreply@mg.wpcerto.com';
            $phpmailer->Password = 'Jb2j3_s2LPgs6';
            $phpmailer->From = 'noreply@mg.wpcerto.com';
            $phpmailer->FromName = get_bloginfo('name');
            
            if (empty($phpmailer->getReplyToAddresses())) {
                $phpmailer->AddReplyTo(get_option('admin_email'), get_bloginfo('name'));
            }            
        } 
        
        private static function replace_test_mail($mail) {

            return preg_replace('/@test\.com\b/', '@wpcerto.com', $mail);
        }

        static function wp_mail_remove_test_com($atts) {
            
//            mmmlog('wp_mail_remove_tests');
//            mmmlog($atts['to']);
//            mmmlog($atts['headers']);
            
            /*
             * intercetto e modifico il destinatario principale
             */
            if (!is_array($atts['to'])) {
                $atts['to'] = self::replace_test_mail($atts['to']);
            } else {
                
                $to = array();
                
                foreach ($atts['to'] as $destinatario) {
                    $to[] = self::replace_test_mail($destinatario);
                }
                
                $atts['to'] = $to;
                
            }
            
            /*
             * intercetto e modifico i vari Cc e Bcc, Reply-to ecc.
             */
            $atts['headers'] = self::replace_test_mail($atts['headers']);
            
//            mmmlog($atts['to']);
//            mmmlog($atts['headers']);
            
            return $atts;
        }
        
        static function init_test() {
            
            wp_mail(['m@manuelmonteiro.dev', 'test@test.com'], 'ciao test', 'Questo è il testo della mail.');
            
        }
        
        /**
         * Rimuovo alcune capability critiche per utenti non-mmm
         * 
         * @param string $user_login
         * @param WP_User $user
         */
        public static function removeAdminCapabilities($user_login, $user) {
            
//            mmmlog('ciao ' . $user_login);
            
            /*
             * quanto segue ha senso solo se il ruolo dell'utente è "administrator"
             */
            if (!in_array('administrator', $user->roles))
                return;
            
            /*
             * email autorizzate ad usare le capability proibite
             * sovrascrivibile per tema/plugin
             */
            $allowed_email_domains = apply_filters('mmm_remove_admin_capabilities_allowed_email_domains', array(
                '@manuelmonteiro.dev',
            ));
            
            /*
             * elenco delle capability da rimuovere
             * sovrascrivibile per tema/plugin
             * cfr. https://wordpress.org/support/article/roles-and-capabilities/
             */
            $forbidden_caps = apply_filters('mmm_remove_admin_capabilities_forbidden_capabilities', array(
                'install_plugins',
                'install_themes',
                'switch_themes',
                'update_core',
                'update_plugins',
                'update_themes',
                'activate_plugins',
                'delete_plugins',
                'delete_themes',
            ));
            
            $user_email = (string) $user->user_email;
            
            $is_allowed = false;
            
            foreach ($allowed_email_domains as $domain) {
                
                /*
                 * verifico se l'utente ha una mail autorizzata... 
                 * in caso il semaforo $is_allowed diventa true
                 */
                if (substr($user_email, -strlen($domain)) == $domain) {
                    $is_allowed = true;
                    break;
                }
            }
            
//            mmmlog('is allowed?');
//            mmmlog($is_allowed);
            
            /*
             * spengo/accendo le capability a seconda del valore di $is_allowed
             */
            foreach ($forbidden_caps as $cap) {
                $user->add_cap($cap, $is_allowed);
            }
            
            // bye.
        }
        
        /**
         * definisco la chiave di attivazione di Akismet
         */
        public static function defineAkismetApiKey() {
            if (!defined('WPCOM_API_KEY')) {
                define('WPCOM_API_KEY', '6d6bfdbe7c22');
            }
        }

        /**
         * funzione per includere un pezzo di template (file .php) dentro il post
         * shortcode [mmmINCLUDE]
         */
        public static function mmmIncludeShortcode($atts) {

            $atts = shortcode_atts(array(
                'slug' => 'mmminclude',
                'name' => '',
                    ), $atts);

            $slug = sanitize_file_name($atts['slug']);
            $name = sanitize_file_name($atts['name']);

            // se non indico un file da includere, restituisco il nulla
            if (!$name)
                return null;

            // cfr. https://kovshenin.com/2013/get_template_part-within-shortcodes/
            ob_start();
            get_template_part($atts['slug'], $name);
            return ob_get_clean();
        }
        
    }

}

mmmBranding::init();




if (!class_exists('mmmInfoHub')) {
    
    class mmmInfoHub {
        
        static function send_data() {
            
            $theme = wp_get_theme();
            
            $datazione = preg_match('/^([0-9]{4})/', $theme->Version, $matches) ? $matches[1] : null;
            
            $info = array(
                'sito' => trim(str_replace(array('http://', 'https://'), '', get_site_url()), '/'),
                'titolo' => get_bloginfo('name'),
                'parent_theme' => is_child_theme() ? $theme->parent_theme : null,
                'wp_version' => get_bloginfo('version'),
                'datazione' => (int) $datazione,
                'is_multisite' => is_multisite() ? 'YES' : null,
            );
            
            $check_plugins = array(
                'contact-form-7/wp-contact-form-7.php',
                'wp-fail2ban/wp-fail2ban.php',
                'mmm-lightweight-hide-login/mmm-lightweight-hide-login.php',
            );
            
            foreach ($check_plugins as $p) {
                
                $plugin_label = explode('/', $p);
                
                $info['is_plugin_active_' . $plugin_label[0]] = self::is_plugin_active($p) ? 'YES' : null;
            }

            if (WP_DEBUG) {
                
                self::log('mmmInfoHub: invio dati all\'endpoint:');
                self::log($info);
                
            } else {

                wp_remote_post('https://infohub.wpcerto.com/insert', array(
                    'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                    'body'        => json_encode($info, JSON_PRETTY_PRINT),
                    'method'      => 'POST',
                    'data_format' => 'body', 
                    'blocking'    => WP_DEBUG,
                ));
            }
        }
        
        private static function is_plugin_active($plugin) {
            return in_array($plugin, (array) get_option('active_plugins', array()), true) || self::is_plugin_active_for_network($plugin);
        }
        
        private static function is_plugin_active_for_network($plugin) {
            if (!is_multisite()) {
                return false;
            }

            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins[$plugin])) {
                return true;
            }

            return false;
        }
        
        private static function log($log) {
            if (true === WP_DEBUG) {
                if (is_array($log) || is_object($log)) {
                    error_log(print_r($log, true));
                } else {
                    error_log($log);
                }
            }
        }

        static function cron_init() {
            
            if (!wp_next_scheduled('mmm_infohub_schedule')) {
                
                self::log('mmmInfoHub: setup del CRON');
                
                wp_schedule_event(time(), 'daily', 'mmm_infohub_schedule');
            }
        }

        static function init() {
            
            /*
             * creo CRON ricorrente
             */
            add_action('init', array(__CLASS__, 'cron_init'));
            
            /*
             * pianifico esecuzione
             */
            add_action('mmm_infohub_schedule', array(__CLASS__, 'send_data'));
        }
        
    }
    
}

mmmInfoHub::init();


/**
 * Cambio logo admin wp
 */
if ( !function_exists('tf_wp_admin_login_logo') ) :
 
    function tf_wp_admin_login_logo() { ?>
        <style type="text/css">
            body.login div#login h1 a {
                background-image: url('<?php echo get_template_directory_uri()."/img/logo_mmm_black.png"; ?>');
				background-size:90%;
				width:300px;
            }
        </style>
    <?php }
 
    add_action( 'login_enqueue_scripts', 'tf_wp_admin_login_logo' );
 
endif;



/*
 * ID Aggiunta della colonna
 */ 
add_filter('manage_users_columns', 'pippin_add_user_id_column');
function pippin_add_user_id_column($columns) {
    $columns['user_id'] = 'User ID';
    return $columns;
}
 
add_action('manage_users_custom_column',  'pippin_show_user_id_column_content', 10, 3);
function pippin_show_user_id_column_content($value, $column_name, $user_id) {
    $user = get_userdata( $user_id );
	if ( 'user_id' == $column_name )
		return $user_id;
    return $value;
}


