<?php
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
</div>