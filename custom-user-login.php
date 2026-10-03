<?php
/**
 * Plugin Name: Custom User Registration & Login
 * Plugin URI: https://github.com/Joaquindati/plugin-wp-users
 * Description: Formularios de registro y login de usuarios para WordPress por shortcode, con reCAPTCHA, verificación por email, roles y redirecciones personalizadas.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Joaquín Dati
 * Author URI: https://joaquindati.com
 * Text Domain: custom-user-login
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('CULR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CULR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CULR_VERSION', '1.0.1');

/**
 * Clase principal del plugin
 */
class Custom_User_Login_Register {
    
    /**
     * Constructor principal
     */
    public function __construct() {
        // Inicializar el plugin
        add_action('init', array($this, 'init'));
        
        // Registrar estilos y scripts
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        
        // Registrar shortcodes
        add_shortcode('custom_register_form', array($this, 'register_form_shortcode'));
        add_shortcode('custom_login_form', array($this, 'login_form_shortcode'));
        
        // Procesar formularios
        add_action('wp_ajax_nopriv_custom_user_registration', array($this, 'process_registration'));
        add_action('wp_ajax_nopriv_custom_user_login', array($this, 'process_login'));
        
        // Añadir scripts a la cabecera para Google reCAPTCHA
        add_action('wp_head', array($this, 'add_recaptcha_script'));
        
        // Inicializar redirecciones
        add_action('template_redirect', array($this, 'handle_redirections'));
        
        // Inicializar panel de administración
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // Funciones de activación/desactivación
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));
    }
    
    /**
     * Inicializar el plugin
     */
    public function init() {
        load_plugin_textdomain('custom-user-login', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Registrar estilos y scripts necesarios
     */
    public function register_scripts() {
        // Estilos principales
        wp_register_style('culr-styles', CULR_PLUGIN_URL . 'assets/css/style.css', array(), CULR_VERSION);
        
        // Script principal
        wp_register_script('culr-script', CULR_PLUGIN_URL . 'assets/js/script.js', array('jquery'), CULR_VERSION, true);
        
        // Configuración para AJAX
        wp_localize_script('culr-script', 'culr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('culr-nonce')
        ));
    }
    
    /**
     * Añadir script de Google reCAPTCHA si está habilitado
     */
    public function add_recaptcha_script() {
        $recaptcha_enabled = get_option('culr_enable_recaptcha', false);
        $recaptcha_site_key = get_option('culr_recaptcha_site_key', '');
        
        if ($recaptcha_enabled && !empty($recaptcha_site_key)) {
            echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
        }
    }

    /**
     * Shortcode para formulario de registro
     */
    public function register_form_shortcode($atts) {
        // Cargar estilos y scripts
        wp_enqueue_style('culr-styles');
        wp_enqueue_script('culr-script');
        
        // Verificar si el usuario ya está logueado
        if (is_user_logged_in()) {
            return '<p class="culr-message">' . esc_html__('Ya has iniciado sesión.', 'custom-user-login') . '</p>';
        }
        
        // Obtener atributos desde el shortcode
        $attributes = shortcode_atts(array(
            'redirect' => '',
            'role' => 'subscriber',
            'show_name' => 'yes',
            'show_username' => 'yes'
        ), $atts);
        
        // Iniciar buffer de salida para el formulario
        ob_start();
        
        // Incluir template del formulario
        include(CULR_PLUGIN_DIR . 'templates/register-form.php');
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode para formulario de login
     */
    public function login_form_shortcode($atts) {
        // Cargar estilos y scripts
        wp_enqueue_style('culr-styles');
        wp_enqueue_script('culr-script');
        
        // Verificar si el usuario ya está logueado
        if (is_user_logged_in()) {
            return '<p class="culr-message">' . esc_html__('Ya has iniciado sesión.', 'custom-user-login') . '</p>';
        }
        
        // Obtener atributos desde el shortcode
        $attributes = shortcode_atts(array(
            'redirect' => '',
            'show_remember' => 'yes',
            'show_register_link' => 'yes',
            'register_url' => '',
            'lost_password' => 'yes',
        ), $atts);
        
        // Iniciar buffer de salida para el formulario
        ob_start();
        
        // Incluir template del formulario
        include(CULR_PLUGIN_DIR . 'templates/login-form.php');
        
        return ob_get_clean();
    }
    
    /**
     * Procesar el registro de usuario
     */
    public function process_registration() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'culr-nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad. Intenta recargar la página.', 'custom-user-login')));
            exit;
        }
        
        // Validar reCAPTCHA si está habilitado
        if (get_option('culr_enable_recaptcha', false)) {
            if (!$this->verify_recaptcha()) {
                wp_send_json_error(array('message' => __('Falló la verificación reCAPTCHA.', 'custom-user-login')));
                exit;
            }
        }
        
        // Obtener y sanitizar datos del formulario
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'subscriber';
        
        // Validar campos requeridos
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => __('Todos los campos requeridos deben ser completados.', 'custom-user-login')));
            exit;
        }
        
        // Validar email
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Por favor, introduce un email válido.', 'custom-user-login')));
            exit;
        }
        
        // Validar que las contraseñas coincidan
        if ($password !== $confirm_password) {
            wp_send_json_error(array('message' => __('Las contraseñas no coinciden.', 'custom-user-login')));
            exit;
        }
        
        // Validar longitud y fortaleza de contraseña
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => __('La contraseña debe tener al menos 8 caracteres.', 'custom-user-login')));
            exit;
        }
        
        // Verificar si el usuario ya existe
        if (username_exists($username)) {
            wp_send_json_error(array('message' => __('Este nombre de usuario ya está en uso.', 'custom-user-login')));
            exit;
        }
        
        // Verificar si el email ya existe
        if (email_exists($email)) {
            wp_send_json_error(array('message' => __('Este email ya está registrado.', 'custom-user-login')));
            exit;
        }
        
        // Verificar si el rol seleccionado es válido
        $allowed_roles = get_option('culr_allowed_roles', array('subscriber'));
        if (!in_array($role, $allowed_roles)) {
            $role = 'subscriber'; // Usar rol por defecto si el especificado no está permitido
        }
        
        // Crear el usuario
        $user_id = wp_create_user($username, $password, $email);
        
        // Verificar si hubo un error al crear el usuario
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
            exit;
        }
        
        // Actualizar información adicional del usuario
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role
        ));
        
        // Verificación de email si está habilitada
        if (get_option('culr_email_verification', false)) {
            $this->send_verification_email($user_id, $email);
            
            // Marca explícita de no verificado: '0'. Un usuario sin la meta (registrado
            // antes de activar la verificación, o un administrador) no queda bloqueado.
            update_user_meta($user_id, 'culr_email_verified', '0');
            
            wp_send_json_success(array(
                'message' => __('Registro exitoso. Por favor, verifica tu correo electrónico para activar tu cuenta.', 'custom-user-login'),
                'redirect' => false
            ));
        } else {
            // Auto login del usuario si está habilitado
            if (get_option('culr_auto_login', true)) {
                wp_set_auth_cookie($user_id, true);
            }
            
            // Obtener URL de redirección
            // sólo destinos del propio sitio: evita usar el formulario como redirección abierta
            $default = get_option('culr_registration_redirect', home_url());
            $redirect = !empty($_POST['redirect'])
                ? wp_validate_redirect(esc_url_raw(wp_unslash($_POST['redirect'])), $default)
                : $default;
            
            // Enviar respuesta exitosa
            wp_send_json_success(array(
                'message' => __('Registro exitoso.', 'custom-user-login'),
                'redirect' => $redirect
            ));
        }
        
        exit;
    }
    
    /**
     * Procesar el login de usuario
     */
    public function process_login() {
        // Verificar nonce para seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'culr-nonce')) {
            wp_send_json_error(array('message' => __('Error de seguridad. Intenta recargar la página.', 'custom-user-login')));
            exit;
        }
        
        // Validar reCAPTCHA si está habilitado
        if (get_option('culr_enable_recaptcha', false)) {
            if (!$this->verify_recaptcha()) {
                wp_send_json_error(array('message' => __('Falló la verificación reCAPTCHA.', 'custom-user-login')));
                exit;
            }
        }
        
        // Obtener y sanitizar datos del formulario
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) ? (bool) $_POST['remember'] : false;
        
        // Validar campos requeridos
        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => __('Por favor, introduce tu nombre de usuario y contraseña.', 'custom-user-login')));
            exit;
        }
        
        // Verificar si es email o nombre de usuario
        if (is_email($username)) {
            $user = get_user_by('email', $username);
            if ($user) {
                $username = $user->user_login;
            }
        }
        
        // Intentar login
        $credentials = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        );
        
        $user = wp_signon($credentials, is_ssl());
        
        // Verificar si hubo un error en el login
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => __('Nombre de usuario o contraseña incorrectos.', 'custom-user-login')));
            exit;
        }
        
        // Verificar si el email está verificado (si es requerido)
        if (get_option('culr_email_verification', false)) {
            $verified = get_user_meta($user->ID, 'culr_email_verified', true);
            if ($verified === '0') {
                // Cerrar sesión si no está verificado
                wp_logout();
                
                wp_send_json_error(array(
                    'message' => __('Tu cuenta no ha sido verificada. Por favor, verifica tu correo electrónico antes de iniciar sesión.', 'custom-user-login')
                ));
                exit;
            }
        }
        
        // Obtener URL de redirección
        // sólo destinos del propio sitio: evita usar el formulario como redirección abierta
        $default = get_option('culr_login_redirect', admin_url());
        $redirect = !empty($_POST['redirect'])
            ? wp_validate_redirect(esc_url_raw(wp_unslash($_POST['redirect'])), $default)
            : $default;
        
        // Enviar respuesta exitosa
        wp_send_json_success(array(
            'message' => __('Inicio de sesión exitoso.', 'custom-user-login'),
            'redirect' => $redirect
        ));
        
        exit;
    }
    
    /**
     * Verificar reCAPTCHA
     */
    private function verify_recaptcha() {
        if (!isset($_POST['g-recaptcha-response'])) {
            return false;
        }
        
        $recaptcha_secret = get_option('culr_recaptcha_secret_key', '');
        if (empty($recaptcha_secret)) {
            return true; // Si no hay clave secreta configurada, omitir verificación
        }
        
        $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);
        
        // Enviar solicitud a Google para verificar
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $recaptcha_secret,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $result = json_decode(wp_remote_retrieve_body($response));
        
        return isset($result->success) && $result->success === true;
    }
    
    /**
     * Enviar email de verificación
     */
    private function send_verification_email($user_id, $email) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Generar token de verificación
        $token = wp_generate_password(32, false);
        update_user_meta($user_id, 'culr_verification_token', $token);
        
        // Construir URL de verificación
        $verification_url = add_query_arg(array(
            'culr_action' => 'verify',
            'user_id' => $user_id,
            'token' => $token
        ), home_url());
        
        // Configurar email
        $subject = sprintf(__('[%s] Verifica tu dirección de correo electrónico', 'custom-user-login'), get_bloginfo('name'));
        
        $message = sprintf(__('Hola %s,', 'custom-user-login'), $user->display_name) . "\r\n\r\n";
        $message .= __('Gracias por registrarte en nuestro sitio. Por favor, verifica tu dirección de correo electrónico haciendo clic en el siguiente enlace:', 'custom-user-login') . "\r\n\r\n";
        $message .= $verification_url . "\r\n\r\n";
        $message .= __('Si no solicitaste esta acción, puedes ignorar este correo.', 'custom-user-login') . "\r\n\r\n";
        $message .= sprintf(__('Saludos,', 'custom-user-login')) . "\r\n";
        $message .= get_bloginfo('name');
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        // Enviar email
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Manejar redirecciones y acciones
     */
    public function handle_redirections() {
        // Verificar si hay una acción de verificación
        if (isset($_GET['culr_action']) && $_GET['culr_action'] === 'verify') {
            $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
            
            if ($user_id && $token) {
                $stored_token = get_user_meta($user_id, 'culr_verification_token', true);
                
                if ($stored_token && hash_equals($stored_token, $token)) {
                    // Marcar como verificado
                    update_user_meta($user_id, 'culr_email_verified', '1');
                    delete_user_meta($user_id, 'culr_verification_token');
                    
                    // Redireccionar a página de éxito
                    $redirect_url = get_option('culr_verification_success_page', home_url());
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    // Token inválido
                    $redirect_url = get_option('culr_verification_error_page', home_url());
                    wp_redirect($redirect_url);
                    exit;
                }
            }
        }
        
        // Redireccionar usuarios ya logueados de la página de login/registro
        if (is_user_logged_in()) {
            $login_page_id = get_option('culr_login_page');
            $register_page_id = get_option('culr_register_page');
            
            if (($login_page_id && is_page($login_page_id)) || ($register_page_id && is_page($register_page_id))) {
                $redirect = get_option('culr_login_redirect', admin_url());
                wp_redirect($redirect);
                exit;
            }
        }
    }
    
    /**
     * Configurar menú de administración
     */
    public function admin_menu() {
        add_menu_page(
            __('Custom Login & Register', 'custom-user-login'),
            __('Custom Login', 'custom-user-login'),
            'manage_options',
            'custom-user-login',
            array($this, 'admin_page'),
            'dashicons-admin-users',
            70
        );
        
        add_submenu_page(
            'custom-user-login',
            __('Configuración General', 'custom-user-login'),
            __('Configuración', 'custom-user-login'),
            'manage_options',
            'custom-user-login',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'custom-user-login',
            __('Personalización de Formularios', 'custom-user-login'),
            __('Formularios', 'custom-user-login'),
            'manage_options',
            'custom-user-login-forms',
            array($this, 'forms_page')
        );
        
        add_submenu_page(
            'custom-user-login',
            __('Redirecciones', 'custom-user-login'),
            __('Redirecciones', 'custom-user-login'),
            'manage_options',
            'custom-user-login-redirects',
            array($this, 'redirects_page')
        );
    }
    
    /**
     * Página principal de administración
     */
    public function admin_page() {
        // Guardar configuración si se envió el formulario
        if (isset($_POST['culr_save_settings']) && check_admin_referer('culr_settings_nonce', 'culr_nonce')) {
            // Guardar configuración general
            update_option('culr_enable_recaptcha', isset($_POST['culr_enable_recaptcha']));
            update_option('culr_recaptcha_site_key', sanitize_text_field($_POST['culr_recaptcha_site_key']));
            update_option('culr_recaptcha_secret_key', sanitize_text_field($_POST['culr_recaptcha_secret_key']));
            update_option('culr_email_verification', isset($_POST['culr_email_verification']));
            update_option('culr_auto_login', isset($_POST['culr_auto_login']));
            
            // Roles permitidos
            $allowed_roles = array();
            $wp_roles = wp_roles();
            foreach ($wp_roles->role_names as $role_key => $role_name) {
                if (isset($_POST['culr_role_' . $role_key])) {
                    $allowed_roles[] = $role_key;
                }
            }
            update_option('culr_allowed_roles', $allowed_roles);
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración guardada correctamente.', 'custom-user-login') . '</p></div>';
        }
        
        // Obtener configuración actual
        $enable_recaptcha = get_option('culr_enable_recaptcha', false);
        $recaptcha_site_key = get_option('culr_recaptcha_site_key', '');
        $recaptcha_secret_key = get_option('culr_recaptcha_secret_key', '');
        $email_verification = get_option('culr_email_verification', false);
        $auto_login = get_option('culr_auto_login', true);
        $allowed_roles = get_option('culr_allowed_roles', array('subscriber'));
        
        // Incluir vista de la página
        include(CULR_PLUGIN_DIR . 'admin/settings.php');
    }
    
    /**
     * Página de configuración de formularios
     */
    public function forms_page() {
        // Guardar configuración si se envió el formulario
        if (isset($_POST['culr_save_forms']) && check_admin_referer('culr_forms_nonce', 'culr_forms_nonce')) {
            // Actualizar configuraciones de formularios
            update_option('culr_login_title', sanitize_text_field($_POST['culr_login_title']));
            update_option('culr_login_button_text', sanitize_text_field($_POST['culr_login_button_text']));
            update_option('culr_register_title', sanitize_text_field($_POST['culr_register_title']));
            update_option('culr_register_button_text', sanitize_text_field($_POST['culr_register_button_text']));
            
            // Campos obligatorios
            update_option('culr_require_first_name', isset($_POST['culr_require_first_name']));
            update_option('culr_require_last_name', isset($_POST['culr_require_last_name']));
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración de formularios guardada correctamente.', 'custom-user-login') . '</p></div>';
        }
        
        // Obtener configuración actual
        $login_title = get_option('culr_login_title', __('Iniciar Sesión', 'custom-user-login'));
        $login_button_text = get_option('culr_login_button_text', __('Acceder', 'custom-user-login'));
        $register_title = get_option('culr_register_title', __('Crear Cuenta', 'custom-user-login'));
        $register_button_text = get_option('culr_register_button_text', __('Registrarse', 'custom-user-login'));
        $require_first_name = get_option('culr_require_first_name', false);
        $require_last_name = get_option('culr_require_last_name', false);
        
        // Incluir vista de la página
        include(CULR_PLUGIN_DIR . 'admin/forms.php');
    }
    
    /**
     * Página de configuración de redirecciones
     */
    public function redirects_page() {
        // Guardar configuración si se envió el formulario
        if (isset($_POST['culr_save_redirects']) && check_admin_referer('culr_redirects_nonce', 'culr_redirects_nonce')) {
            // Actualizar configuraciones de redirección
            update_option('culr_login_page', absint($_POST['culr_login_page']));
            update_option('culr_register_page', absint($_POST['culr_register_page']));
            update_option('culr_login_redirect', esc_url_raw($_POST['culr_login_redirect']));
            update_option('culr_registration_redirect', esc_url_raw($_POST['culr_registration_redirect']));
            update_option('culr_verification_success_page', absint($_POST['culr_verification_success_page']));
            update_option('culr_verification_error_page', absint($_POST['culr_verification_error_page']));
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración de redirecciones guardada correctamente.', 'custom-user-login') . '</p></div>';
        }
        
        // Obtener configuración actual
        $login_page = get_option('culr_login_page', 0);
        $register_page = get_option('culr_register_page', 0);
        $login_redirect = get_option('culr_login_redirect', admin_url());
        $registration_redirect = get_option('culr_registration_redirect', home_url());
        $verification_success_page = get_option('culr_verification_success_page', 0);
        $verification_error_page = get_option('culr_verification_error_page', 0);
        
        // Incluir vista de la página
        include(CULR_PLUGIN_DIR . 'admin/redirects.php');
    }
    
/**
     * Acciones de activación del plugin
     */
    public function plugin_activation() {
        // Crear directorios necesarios
        if (!file_exists(CULR_PLUGIN_DIR . 'assets')) {
            mkdir(CULR_PLUGIN_DIR . 'assets', 0755, true);
            mkdir(CULR_PLUGIN_DIR . 'assets/css', 0755, true);
            mkdir(CULR_PLUGIN_DIR . 'assets/js', 0755, true);
        }
        
        if (!file_exists(CULR_PLUGIN_DIR . 'templates')) {
            mkdir(CULR_PLUGIN_DIR . 'templates', 0755, true);
        }
        
        if (!file_exists(CULR_PLUGIN_DIR . 'admin')) {
            mkdir(CULR_PLUGIN_DIR . 'admin', 0755, true);
        }
        
        // Crear archivos base si no existen
        $this->create_base_files();
        
        // Limpiar reglas de reescritura
        flush_rewrite_rules();
    }
    
    /**
     * Acciones de desactivación del plugin
     */
    public function plugin_deactivation() {
        // Limpiar reglas de reescritura
        flush_rewrite_rules();
    }
    
    /**
     * Crear archivos base del plugin
     */
    private function create_base_files() {
        // Crear archivo CSS
        if (!file_exists(CULR_PLUGIN_DIR . 'assets/css/style.css')) {
            $css_content = "/**
 * Estilos para Custom User Login & Register
 */
.culr-form-container {
    max-width: 400px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.culr-form-title {
    text-align: center;
    margin-bottom: 20px;
    font-size: 24px;
    color: #333;
}

.culr-form-field {
    margin-bottom: 15px;
}

.culr-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.culr-form-field input[type=\"text\"],
.culr-form-field input[type=\"email\"],
.culr-form-field input[type=\"password\"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.culr-form-field input[type=\"checkbox\"] {
    margin-right: 5px;
}

.culr-form-button {
    width: 100%;
    padding: 12px;
    background-color: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.culr-form-button:hover {
    background-color: #005177;
}

.culr-form-links {
    margin-top: 15px;
    text-align: center;
}

.culr-form-links a {
    color: #0073aa;
    text-decoration: none;
}

.culr-form-links a:hover {
    text-decoration: underline;
}

.culr-message {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
}

.culr-message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.culr-message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.g-recaptcha {
    margin-bottom: 15px;
}";
            file_put_contents(CULR_PLUGIN_DIR . 'assets/css/style.css', $css_content);
        }
        
        // Crear archivo JS
        if (!file_exists(CULR_PLUGIN_DIR . 'assets/js/script.js')) {
            $js_content = "/**
 * Scripts para Custom User Login & Register
 */
jQuery(document).ready(function($) {
    // Formulario de registro
    $('#culr-register-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[type=\"submit\"]');
        const messageContainer = form.find('.culr-messages');
        
        // Mostrar estado de carga
        submitButton.prop('disabled', true).text(culr_ajax.loading_text || 'Procesando...');
        messageContainer.html('');
        
        // Recopilar datos del formulario
        const formData = new FormData(form[0]);
        formData.append('action', 'custom_user_registration');
        formData.append('nonce', culr_ajax.nonce);
        
        // Enviar solicitud AJAX
        $.ajax({
            url: culr_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Restaurar botón
                submitButton.prop('disabled', false).text(form.data('button-text') || 'Registrarse');
                
                if (response.success) {
                    // Mostrar mensaje de éxito
                    messageContainer.html('<div class=\"culr-message success\">' + response.data.message + '</div>');
                    
                    // Redireccionar si es necesario
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    // Mostrar mensaje de error
                    messageContainer.html('<div class=\"culr-message error\">' + response.data.message + '</div>');
                }
            },
            error: function() {
                // Restaurar botón
                submitButton.prop('disabled', false).text(form.data('button-text') || 'Registrarse');
                
                // Mostrar mensaje de error
                messageContainer.html('<div class=\"culr-message error\">Ha ocurrido un error. Por favor, intenta nuevamente.</div>');
            }
        });
    });
    
    // Formulario de login
    $('#culr-login-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[type=\"submit\"]');
        const messageContainer = form.find('.culr-messages');
        
        // Mostrar estado de carga
        submitButton.prop('disabled', true).text(culr_ajax.loading_text || 'Procesando...');
        messageContainer.html('');
        
        // Recopilar datos del formulario
        const formData = new FormData(form[0]);
        formData.append('action', 'custom_user_login');
        formData.append('nonce', culr_ajax.nonce);
        
        // Enviar solicitud AJAX
        $.ajax({
            url: culr_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Restaurar botón
                submitButton.prop('disabled', false).text(form.data('button-text') || 'Acceder');
                
                if (response.success) {
                    // Mostrar mensaje de éxito
                    messageContainer.html('<div class=\"culr-message success\">' + response.data.message + '</div>');
                    
                    // Redireccionar
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    // Mostrar mensaje de error
                    messageContainer.html('<div class=\"culr-message error\">' + response.data.message + '</div>');
                }
            },
            error: function() {
                // Restaurar botón
                submitButton.prop('disabled', false).text(form.data('button-text') || 'Acceder');
                
                // Mostrar mensaje de error
                messageContainer.html('<div class=\"culr-message error\">Ha ocurrido un error. Por favor, intenta nuevamente.</div>');
            }
        });
    });
    
    // Validación de formularios en tiempo real
    $('.culr-form-field input[required]').on('blur', function() {
        const input = $(this);
        const value = input.val().trim();
        
        if (!value) {
            input.addClass('error');
        } else {
            input.removeClass('error');
            
            // Validación específica para email
            if (input.attr('type') === 'email') {
                const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                if (!emailRegex.test(value)) {
                    input.addClass('error');
                }
            }
            
            // Validación de contraseña
            if (input.attr('name') === 'password') {
                if (value.length < 8) {
                    input.addClass('error');
                }
            }
            
            // Validación de confirmación de contraseña
            if (input.attr('name') === 'confirm_password') {
                const password = $('#culr-password').val();
                if (value !== password) {
                    input.addClass('error');
                }
            }
        }
    });
});";
            file_put_contents(CULR_PLUGIN_DIR . 'assets/js/script.js', $js_content);
        }
        
        // Crear templates
        $this->create_template_files();
        
        // Crear archivos de administración
        $this->create_admin_files();
    }
    
    /**
     * Crear archivos de plantillas
     */
    private function create_template_files() {
        // Crear plantilla de formulario de registro
        if (!file_exists(CULR_PLUGIN_DIR . 'templates/register-form.php')) {
            $register_form = '<?php
// Prevenir acceso directo
if (!defined("ABSPATH")) {
    exit;
}

// Obtener configuraciones del formulario
$register_title = get_option("culr_register_title", __("Crear Cuenta", "custom-user-login"));
$register_button_text = get_option("culr_register_button_text", __("Registrarse", "custom-user-login"));
$require_first_name = get_option("culr_require_first_name", false);
$require_last_name = get_option("culr_require_last_name", false);

// Obtener si reCAPTCHA está habilitado
$recaptcha_enabled = get_option("culr_enable_recaptcha", false);
$recaptcha_site_key = get_option("culr_recaptcha_site_key", "");
?>

<div class="culr-form-container">
    <h2 class="culr-form-title"><?php echo esc_html($register_title); ?></h2>
    
    <div class="culr-messages"></div>
    
    <form id="culr-register-form" method="post" data-button-text="<?php echo esc_attr($register_button_text); ?>">
        <?php if ($attributes["show_name"] === "yes") : ?>
        <div class="culr-form-field">
            <label for="culr-first-name">
                <?php _e("Nombre", "custom-user-login"); ?>
                <?php if ($require_first_name) : ?><span class="required">*</span><?php endif; ?>
            </label>
            <input type="text" id="culr-first-name" name="first_name" <?php if ($require_first_name) echo "required"; ?>>
        </div>
        
        <div class="culr-form-field">
            <label for="culr-last-name">
                <?php _e("Apellido", "custom-user-login"); ?>
                <?php if ($require_last_name) : ?><span class="required">*</span><?php endif; ?>
            </label>
            <input type="text" id="culr-last-name" name="last_name" <?php if ($require_last_name) echo "required"; ?>>
        </div>
        <?php endif; ?>
        
        <?php if ($attributes["show_username"] === "yes") : ?>
        <div class="culr-form-field">
            <label for="culr-username"><?php _e("Nombre de usuario", "custom-user-login"); ?> <span class="required">*</span></label>
            <input type="text" id="culr-username" name="username" required>
        </div>
        <?php endif; ?>
        
        <div class="culr-form-field">
            <label for="culr-email"><?php _e("Correo electrónico", "custom-user-login"); ?> <span class="required">*</span></label>
            <input type="email" id="culr-email" name="email" required>
        </div>
        
        <div class="culr-form-field">
            <label for="culr-password"><?php _e("Contraseña", "custom-user-login"); ?> <span class="required">*</span></label>
            <input type="password" id="culr-password" name="password" required>
        </div>
        
        <div class="culr-form-field">
            <label for="culr-confirm-password"><?php _e("Confirmar contraseña", "custom-user-login"); ?> <span class="required">*</span></label>
            <input type="password" id="culr-confirm-password" name="confirm_password" required>
        </div>
        
        <?php if ($recaptcha_enabled && !empty($recaptcha_site_key)) : ?>
        <div class="culr-form-field">
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div>
        </div>
        <?php endif; ?>
        
        <div class="culr-form-field">
            <input type="hidden" name="role" value="<?php echo esc_attr($attributes["role"]); ?>">
            <input type="hidden" name="redirect" value="<?php echo esc_url($attributes["redirect"]); ?>">
        </div>
        
        <div class="culr-form-field">
            <button type="submit" class="culr-form-button"><?php echo esc_html($register_button_text); ?></button>
        </div>
        
        <div class="culr-form-links">
            <?php
            $login_page_id = get_option("culr_login_page", 0);
            if ($login_page_id) {
                $login_url = get_permalink($login_page_id);
                echo "<a href=\"" . esc_url($login_url) . "\">" . esc_html__("¿Ya tienes cuenta? Inicia sesión", "custom-user-login") . "</a>";
            }
            ?>
        </div>
    </form>
</div>';
            file_put_contents(CULR_PLUGIN_DIR . 'templates/register-form.php', $register_form);
        }
        
        // Crear plantilla de formulario de login
        if (!file_exists(CULR_PLUGIN_DIR . 'templates/login-form.php')) {
            $login_form = '<?php
// Prevenir acceso directo
if (!defined("ABSPATH")) {
    exit;
}

// Obtener configuraciones del formulario
$login_title = get_option("culr_login_title", __("Iniciar Sesión", "custom-user-login"));
$login_button_text = get_option("culr_login_button_text", __("Acceder", "custom-user-login"));

// Obtener si reCAPTCHA está habilitado
$recaptcha_enabled = get_option("culr_enable_recaptcha", false);
$recaptcha_site_key = get_option("culr_recaptcha_site_key", "");
?>

<div class="culr-form-container">
    <h2 class="culr-form-title"><?php echo esc_html($login_title); ?></h2>
    
    <div class="culr-messages"></div>
    
    <form id="culr-login-form" method="post" data-button-text="<?php echo esc_attr($login_button_text); ?>">
        <div class="culr-form-field">
            <label for="culr-username"><?php _e("Nombre de usuario o Email", "custom-user-login"); ?></label>
            <input type="text" id="culr-username" name="username" required>
        </div>
        
        <div class="culr-form-field">
            <label for="culr-password"><?php _e("Contraseña", "custom-user-login"); ?></label>
            <input type="password" id="culr-password" name="password" required>
        </div>
        
        <?php if ($attributes["show_remember"] === "yes") : ?>
        <div class="culr-form-field">
            <label>
                <input type="checkbox" name="remember" value="1">
                <?php _e("Recordarme", "custom-user-login"); ?>
            </label>
        </div>
        <?php endif; ?>
        
        <?php if ($recaptcha_enabled && !empty($recaptcha_site_key)) : ?>
        <div class="culr-form-field">
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div>
        </div>
        <?php endif; ?>
        
        <div class="culr-form-field">
            <input type="hidden" name="redirect" value="<?php echo esc_url($attributes["redirect"]); ?>">
        </div>
        
        <div class="culr-form-field">
            <button type="submit" class="culr-form-button"><?php echo esc_html($login_button_text); ?></button>
        </div>
        
        <div class="culr-form-links">
            <?php if ($attributes["lost_password"] === "yes") : ?>
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php _e("¿Olvidaste tu contraseña?", "custom-user-login"); ?></a>
            <?php endif; ?>
            
            <?php if ($attributes["show_register_link"] === "yes") : ?>
                <?php
                $register_url = !empty($attributes["register_url"]) ? $attributes["register_url"] : "";
                if (empty($register_url)) {
                    $register_page_id = get_option("culr_register_page", 0);
                    if ($register_page_id) {
                        $register_url = get_permalink($register_page_id);
                    }
                }
                
                if (!empty($register_url)) {
                    echo "<br><a href=\"" . esc_url($register_url) . "\">" . esc_html__("¿No tienes cuenta? Regístrate", "custom-user-login") . "</a>";
                }
                ?>
            <?php endif; ?>
        </div>
    </form>
</div>';
            file_put_contents(CULR_PLUGIN_DIR . 'templates/login-form.php', $login_form);
        }
    }

/**
     * Crear archivos de administración
     */
    private function create_admin_files() {
        // Crear archivo de configuraciones generales
        if (!file_exists(CULR_PLUGIN_DIR . 'admin/settings.php')) {
            $settings_page = '<?php
// Prevenir acceso directo
if (!defined("ABSPATH")) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field("culr_settings_nonce", "culr_nonce"); ?>
        
        <h2><?php _e("Configuración de Seguridad", "custom-user-login"); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e("Verificación de Email", "custom-user-login"); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="culr_email_verification" value="1" <?php checked($email_verification); ?>>
                        <?php _e("Requerir verificación de email antes de permitir el inicio de sesión", "custom-user-login"); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Auto Login", "custom-user-login"); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="culr_auto_login" value="1" <?php checked($auto_login); ?>>
                        <?php _e("Iniciar sesión automáticamente después del registro (solo si la verificación de email está desactivada)", "custom-user-login"); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Google reCAPTCHA", "custom-user-login"); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="culr_enable_recaptcha" value="1" <?php checked($enable_recaptcha); ?>>
                        <?php _e("Habilitar protección reCAPTCHA", "custom-user-login"); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Clave del Sitio reCAPTCHA", "custom-user-login"); ?></th>
                <td>
                    <input type="text" name="culr_recaptcha_site_key" value="<?php echo esc_attr($recaptcha_site_key); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Clave Secreta reCAPTCHA", "custom-user-login"); ?></th>
                <td>
                    <input type="text" name="culr_recaptcha_secret_key" value="<?php echo esc_attr($recaptcha_secret_key); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        
        <h2><?php _e("Roles de Usuario", "custom-user-login"); ?></h2>
        <p><?php _e("Selecciona los roles que estarán disponibles para ser asignados durante el registro de usuarios:", "custom-user-login"); ?></p>
        
        <table class="form-table">
            <?php
            $wp_roles = wp_roles();
            foreach ($wp_roles->role_names as $role_key => $role_name) :
                $checked = in_array($role_key, $allowed_roles);
            ?>
            <tr>
                <th scope="row"><?php echo esc_html($role_name); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="culr_role_<?php echo esc_attr($role_key); ?>" value="1" <?php checked($checked); ?>>
                        <?php printf(__("Permitir rol %s", "custom-user-login"), esc_html($role_name)); ?>
                    </label>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <p class="submit">
            <input type="submit" name="culr_save_settings" class="button-primary" value="<?php _e("Guardar Cambios", "custom-user-login"); ?>">
        </p>
    </form>
</div>';
            file_put_contents(CULR_PLUGIN_DIR . 'admin/settings.php', $settings_page);
        }
        
        // Crear archivo de configuraciones de formularios
        if (!file_exists(CULR_PLUGIN_DIR . 'admin/forms.php')) {
            $forms_page = '<?php
// Prevenir acceso directo
if (!defined("ABSPATH")) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field("culr_forms_nonce", "culr_forms_nonce"); ?>
        
        <h2><?php _e("Formulario de Login", "custom-user-login"); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e("Título del Formulario", "custom-user-login"); ?></th>
                <td>
                    <input type="text" name="culr_login_title" value="<?php echo esc_attr($login_title); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Texto del Botón", "custom-user-login"); ?></th>
                <td>
                    <input type="text" name="culr_login_button_text" value="<?php echo esc_attr($login_button_text); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        
        <h2><?php _e("Formulario de Registro", "custom-user-login"); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e("Título del Formulario", "custom-user-login"); ?></th>
                <td>
                    <input type="text" name="culr_register_title" value="<?php echo esc_attr($register_title); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Texto del Botón", "custom-user-login"); ?></th>
                <td>
                    <input type="text" name="culr_register_button_text" value="<?php echo esc_attr($register_button_text); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Campos Obligatorios", "custom-user-login"); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="culr_require_first_name" value="1" <?php checked($require_first_name); ?>>
                        <?php _e("Requerir Nombre", "custom-user-login"); ?>
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" name="culr_require_last_name" value="1" <?php checked($require_last_name); ?>>
                        <?php _e("Requerir Apellido", "custom-user-login"); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="culr_save_forms" class="button-primary" value="<?php _e("Guardar Cambios", "custom-user-login"); ?>">
        </p>
    </form>
</div>';
            file_put_contents(CULR_PLUGIN_DIR . 'admin/forms.php', $forms_page);
        }
        
        // Crear archivo de configuraciones de redirecciones
        if (!file_exists(CULR_PLUGIN_DIR . 'admin/redirects.php')) {
            $redirects_page = '<?php
// Prevenir acceso directo
if (!defined("ABSPATH")) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field("culr_redirects_nonce", "culr_redirects_nonce"); ?>
        
        <h2><?php _e("Páginas de Formularios", "custom-user-login"); ?></h2>
        <p><?php _e("Selecciona las páginas donde se encuentran tus formularios de login y registro:", "custom-user-login"); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e("Página de Login", "custom-user-login"); ?></th>
                <td>
                    <?php
                    wp_dropdown_pages(array(
                        "name" => "culr_login_page",
                        "selected" => $login_page,
                        "show_option_none" => __("Selecciona una página", "custom-user-login"),
                        "option_none_value" => "0",
                    ));
                    ?>
                    <p class="description"><?php _e("Página que contiene el shortcode [custom_login_form]", "custom-user-login"); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Página de Registro", "custom-user-login"); ?></th>
                <td>
                    <?php
                    wp_dropdown_pages(array(
                        "name" => "culr_register_page",
                        "selected" => $register_page,
                        "show_option_none" => __("Selecciona una página", "custom-user-login"),
                        "option_none_value" => "0",
                    ));
                    ?>
                    <p class="description"><?php _e("Página que contiene el shortcode [custom_register_form]", "custom-user-login"); ?></p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e("Redirecciones", "custom-user-login"); ?></h2>
        <p><?php _e("Configura las URLs para redireccionar a los usuarios después de diferentes acciones:", "custom-user-login"); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e("Redirección después del Login", "custom-user-login"); ?></th>
                <td>
                    <input type="text" name="culr_login_redirect" value="<?php echo esc_url($login_redirect); ?>" class="regular-text">
                    <p class="description"><?php _e("URL donde serán redirigidos los usuarios después de iniciar sesión", "custom-user-login"); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Redirección después del Registro", "custom-user-login"); ?></th>
                <td>
                    <input type="text" name="culr_registration_redirect" value="<?php echo esc_url($registration_redirect); ?>" class="regular-text">
                    <p class="description"><?php _e("URL donde serán redirigidos los usuarios después de registrarse", "custom-user-login"); ?></p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e("Páginas de Verificación de Email", "custom-user-login"); ?></h2>
        <p><?php _e("Selecciona las páginas para redireccionar después de la verificación de email:", "custom-user-login"); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e("Página de Éxito de Verificación", "custom-user-login"); ?></th>
                <td>
                    <?php
                    wp_dropdown_pages(array(
                        "name" => "culr_verification_success_page",
                        "selected" => $verification_success_page,
                        "show_option_none" => __("Selecciona una página", "custom-user-login"),
                        "option_none_value" => "0",
                    ));
                    ?>
                    <p class="description"><?php _e("Página a la que serán redirigidos los usuarios después de verificar su email exitosamente", "custom-user-login"); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e("Página de Error de Verificación", "custom-user-login"); ?></th>
                <td>
                    <?php
                    wp_dropdown_pages(array(
                        "name" => "culr_verification_error_page",
                        "selected" => $verification_error_page,
                        "show_option_none" => __("Selecciona una página", "custom-user-login"),
                        "option_none_value" => "0",
                    ));
                    ?>
                    <p class="description"><?php _e("Página a la que serán redirigidos los usuarios si hay un error en la verificación de email", "custom-user-login"); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="culr_save_redirects" class="button-primary" value="<?php _e("Guardar Cambios", "custom-user-login"); ?>">
        </p>
    </form>
</div>';
            file_put_contents(CULR_PLUGIN_DIR . 'admin/redirects.php', $redirects_page);
        }
    }
}

// Inicializar el plugin
$custom_user_login = new Custom_User_Login_Register();