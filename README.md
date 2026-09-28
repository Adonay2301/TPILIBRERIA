# TPILIBRERIA — Punto y Aparte

Sistema web de la librería **Punto y Aparte** (El Salvador): catálogo, carrito, pedidos con pago contra entrega o PayPal, y panel para administrador y empleados.

PHP 8 (MVC propio, sin framework) · MariaDB/MySQL · Bootstrap 5 · JavaScript sin frameworks.

---

## Instalación (XAMPP en Windows)

1. **Clonar el repositorio** y cambiarse a la rama de trabajo:
   ```bash
   git clone https://github.com/Adonay2301/TPILIBRERIA.git
   cd TPILIBRERIA
   git checkout desarrollo
   ```

2. **Servir la carpeta con Apache.** Se puede clonar directo dentro de `C:\xampp\htdocs\`, o crear un enlace desde PowerShell:
   ```powershell
   New-Item -ItemType Junction -Path C:\xampp\htdocs\punto-y-aparte -Target "C:\ruta\donde\clonaste\TPILIBRERIA"
   ```
   Apache debe tener `mod_rewrite` activo (en XAMPP ya viene activo).

3. **Crear la base de datos.** Con MySQL encendido en XAMPP, ejecutar en este orden desde Git Bash:
   ```bash
   /c/xampp/mysql/bin/mysql -uroot --default-character-set=utf8mb4 < database/schema.sql
   /c/xampp/mysql/bin/mysql -uroot --default-character-set=utf8mb4 < database/ubicaciones.sql
   /c/xampp/mysql/bin/mysql -uroot --default-character-set=utf8mb4 < database/seed.sql
   ```
   (También se pueden importar los tres archivos, en ese orden, desde phpMyAdmin.)

4. **Crear los archivos de configuración** a partir de las plantillas (estos archivos NO se suben a Git):
   - `app/config/database.example.php` → copiar como `app/config/database.php` y poner sus datos (en XAMPP: host `127.0.0.1`, usuario `root`, contraseña vacía).
   - `app/config/paypal.example.php` → copiar como `app/config/paypal.php` (ya viene en modo `simulado`, no necesita cuenta).

5. Abrir **http://localhost/punto-y-aparte**

### Cuentas de prueba

| Rol           | Correo                              | Contraseña     |
|---------------|-------------------------------------|----------------|
| Administrador | admin@puntoyaparte.com.sv           | Admin2026!     |
| Empleado      | carlos.mejia@puntoyaparte.com.sv    | Empleado2026!  |
| Cliente       | maria.hernandez@ejemplo.com         | Cliente2026!   |

---

## Cómo trabajamos con Git

- **`main`** es la versión estable. Nadie sube cambios directo a `main`: solo el responsable del proyecto (@Adonay2301) aprueba y une los cambios.
- **`desarrollo`** es la rama del equipo. Ahí se suben los cambios.

Flujo de trabajo:

```bash
git checkout desarrollo
git pull                      # traer lo último antes de empezar
# ... hacer cambios ...
git add .
git commit -m "Describe qué cambiaste"
git pull                      # por si alguien subió algo mientras trabajabas
git push
```

Cuando un cambio esté listo para `main`, abrir un **Pull Request** en GitHub de `desarrollo` → `main`. El responsable lo revisa y lo une.

**No subir nunca** `app/config/database.php` ni `app/config/paypal.php` (ya están en `.gitignore`).
