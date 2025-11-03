README — Panel de Administración Vestige
=============================================

¿Qué es?
--------
Un módulo **autónomo** de administración para tu proyecto **Vestige** (PHP + MySQL),
compatible con tu base de datos actual (tabla `usuarios.rol_id`, `publicaciones.estado_id`, etc.).
Permite:

- Ver resumen general.
- Listar usuarios, cambiar **estado** (activo/suspendido/eliminado) y **rol** (usa `roles.id`).
- Moderar publicaciones: cambiar estado (`estados_publicacion`) y **eliminar**.
- Revisar ofertas: cambiar estado (pendiente/aceptada/rechazada).

Requisitos
---------
- Tu proyecto debe tener `config/config.php` con `DB::conn()` (PDO).
- El login debe guardar `$_SESSION['usuario_id']` y `$_SESSION['rol_id']`.
- En tu tabla `roles`: `1=admin`, `2=vendedor`, `3=comprador` (según tu dump).

Instalación
-----------
1. Copia la carpeta **admin/** dentro de tu proyecto, idealmente dentro de **public/**:
   - `C:\xampp\htdocs\vestige_es\public\admin\`
   - o `/var/www/vestige_es/public/admin/`

2. Asegúrate de tener al menos un usuario con rol admin:
   ```sql
   UPDATE usuarios SET rol_id = 1 WHERE correo = 'admin@vestige.com';
   ```

3. Inicia sesión como ese usuario admin y visita:
   - `http://localhost/vestige_es/public/admin/index.php`

4. Si tu sistema usa rutas distintas, ajusta las rutas relativas de imágenes en `publicaciones.php`
   (línea del `<img src="../<?= h($p['img']) ?>">`) según dónde coloques `admin/`.

Seguridad
---------
- El panel exige `rol_id = 1` para entrar (ver `admin/_auth.php`).
- Incluye CSRF tokens en todos los formularios POST (`_common.php`).

Personalización
---------------
- Estilos en `admin/styles.css`.
- Lógica de acciones en `admin/actions.php`.

Compatibilidad
--------------
Este pack **no reemplaza** tus clases (`Publicacion.php`, `Favorito.php`, etc.).
Todo corre con consultas PDO directas y sólo espera `DB::conn()` para abrir la conexión.

Soporte
-------
Si algo no carga:
- Verifica `config/config.php` y que `DB::conn()` funcione.
- Comprueba que tu sesión guarde `$_SESSION['rol_id']` y que sea `1` para el admin.
- Checa la ruta relativa a las imágenes en `publicaciones.php` según tu estructura.
