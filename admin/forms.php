<?php
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
</div>