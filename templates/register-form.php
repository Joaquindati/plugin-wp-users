<?php
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
</div>