/**
 * Scripts para Custom User Login & Register
 */
jQuery(document).ready(function($) {
    // Formulario de registro
    $('#culr-register-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
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
                    messageContainer.html('<div class="culr-message success">' + response.data.message + '</div>');
                    
                    // Redireccionar si es necesario
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    // Mostrar mensaje de error
                    messageContainer.html('<div class="culr-message error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                // Restaurar botón
                submitButton.prop('disabled', false).text(form.data('button-text') || 'Registrarse');
                
                // Mostrar mensaje de error
                messageContainer.html('<div class="culr-message error">Ha ocurrido un error. Por favor, intenta nuevamente.</div>');
            }
        });
    });
    
    // Formulario de login
    $('#culr-login-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
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
                    messageContainer.html('<div class="culr-message success">' + response.data.message + '</div>');
                    
                    // Redireccionar
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    // Mostrar mensaje de error
                    messageContainer.html('<div class="culr-message error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                // Restaurar botón
                submitButton.prop('disabled', false).text(form.data('button-text') || 'Acceder');
                
                // Mostrar mensaje de error
                messageContainer.html('<div class="culr-message error">Ha ocurrido un error. Por favor, intenta nuevamente.</div>');
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
});