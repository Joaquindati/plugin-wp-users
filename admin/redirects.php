<?php
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
</div>