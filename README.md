# Plugin de registro y login de usuarios para WordPress

Formularios personalizados de **registro e inicio de sesión para WordPress** que se insertan
con un shortcode en cualquier página. Tienen **reCAPTCHA**, **verificación por email**,
elección de **rol de usuario** al registrarse y **redirecciones** después del login o del
registro. Reemplazan el `wp-login.php` por defecto sin depender de plugins pesados de
membresía.

> 🇬🇧 **English:** Custom WordPress user registration and login forms via shortcodes, with
> Google reCAPTCHA, email verification, selectable user roles and custom redirects. AJAX
> forms, no page reloads.

## Características

- **Shortcodes** `[custom_register_form]` y `[custom_login_form]`, con el estilo de tu theme.
- **Formularios por AJAX**: muestran los errores en el mismo formulario, sin recargar la página.
- **Login con usuario o email.**
- **Google reCAPTCHA v2** en los dos formularios (opcional).
- **Verificación por email**: la cuenta nueva no puede iniciar sesión hasta abrir el enlace
  que le llega por correo. Los usuarios que ya existían, incluido el administrador, siguen
  entrando con normalidad.
- **Roles al registrarse**: se eligen en el panel qué roles se pueden pedir. Cualquier otro
  rol, por ejemplo `administrator`, se convierte en `subscriber`.
- **Redirecciones** configurables después del registro, del login y de la verificación
  (éxito o error). Solo se aceptan destinos del propio sitio, así el formulario no sirve
  como redirección abierta.
- **Inicio de sesión automático** al registrarse (si la verificación está desactivada).
- **Seguridad**: nonces en cada envío, datos sanitizados, contraseñas de 8 caracteres como
  mínimo y token de verificación comparado con `hash_equals`.
- **Panel de administración** con tres secciones: Configuración general, Personalización
  de formularios (títulos, textos de botones, campos obligatorios) y Redirecciones.

## Instalación

1. Descarga el repo como ZIP o clónalo en `wp-content/plugins/`:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/Joaquindati/plugin-wp-users.git
   ```
2. Activa **Custom User Registration & Login** en *Plugins*.
3. Crea dos páginas y pega los shortcodes:
   ```
   [custom_register_form]
   [custom_login_form]
   ```
4. Configura las opciones en *Custom Login* (menú del administrador).

### Atributos de los shortcodes

```
[custom_register_form redirect="/bienvenida/" role="subscriber" show_name="yes" show_username="yes"]
[custom_login_form redirect="/mi-cuenta/" show_remember="yes" show_register_link="yes" register_url="/registro/" lost_password="yes"]
```

## reCAPTCHA

Genera las claves de **reCAPTCHA v2 (casilla)** en
[google.com/recaptcha/admin](https://www.google.com/recaptcha/admin), pégalas en
*Custom Login → Configuración general* y activa la opción.

## Requisitos

WordPress 5.8+ · PHP 7.4+ (probado con PHP 8.3 y la última versión de WordPress).

## Estructura

```
custom-user-login.php     clase principal: shortcodes, AJAX, verificación, redirecciones
admin/                    pantallas del panel (configuración, formularios, redirecciones)
templates/                HTML de los formularios
assets/                   estilos y JavaScript (envío por AJAX)
```

## Autor

**Joaquín Dati**, desarrollador WordPress y full stack.
[joaquindati.com](https://joaquindati.com)

Licencia GPL v2 o posterior.
