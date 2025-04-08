<?php
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
</div>