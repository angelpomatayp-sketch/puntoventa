# Sistema POS Multi-tenant DYM SAC

Sistema de Punto de Venta (POS) multi-tenant desarrollado en PHP para restaurantes y negocios de comida rapida.

## Tecnologias

- **Backend:** PHP 8.1+
- **Base de datos:** MySQL / TiDB Cloud (produccion)
- **Frontend:** Bootstrap 5, JavaScript
- **Servidor:** Apache con mod_rewrite
- **Contenedor:** Docker (para despliegue en Render)

---

## Instalacion Local (XAMPP)

### Requisitos
- XAMPP con PHP 8.1+ y MySQL
- Navegador web moderno

### Pasos

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/angelpomatayp-sketch/puntoventa.git
   cd puntoventa
   ```

2. **Copiar a XAMPP:**
   ```bash
   # Copiar a htdocs
   cp -r . /c/xampp/htdocs/fastfood
   ```

3. **Crear archivo .env:**
   ```bash
   cp .env.example .env
   ```

4. **Configurar .env para desarrollo local:**
   ```env
   # Configuracion de Base de Datos
   DB_HOST=localhost
   DB_PORT=3306
   DB_USER=root
   DB_PASS=
   DB_NAME=sistema_fastfood
   DB_CHARSET=utf8mb4

   # Configuracion del Sistema
   APP_ENV=development
   APP_DEBUG=true
   APP_URL=/fastfood

   # Configuracion de Sesion
   SESSION_SECURE=0
   ```

5. **Importar base de datos:**
   - Abrir phpMyAdmin: `http://localhost/phpmyadmin`
   - Crear base de datos: `sistema_fastfood`
   - Importar: `database/schema.sql`

6. **Acceder al sistema:**
   ```
   http://localhost/fastfood/
   ```

---

## Despliegue en Produccion (Render + TiDB Cloud)

### Paso 1: Configurar TiDB Cloud (Base de datos)

1. Crear cuenta en [TiDB Cloud](https://tidbcloud.com)
2. Crear un cluster **Serverless** (gratuito)
3. Crear base de datos: `sistema_fastfood`
4. Importar el schema usando la funcion de importacion de TiDB
5. Guardar las credenciales de conexion:
   - Host: `gateway01.us-east-1.prod.aws.tidbcloud.com`
   - Port: `4000`
   - User: `xxxx.root`
   - Password: `xxxxxxxx`

### Paso 2: Desplegar en Render

1. Crear cuenta en [Render](https://render.com) conectada a GitHub
2. Crear nuevo **Web Service**
3. Conectar el repositorio de GitHub
4. Configurar:
   - **Name:** `pos-dym-sac`
   - **Runtime:** Docker
   - **Branch:** main

5. **Variables de entorno en Render:**

   | Variable | Valor |
   |----------|-------|
   | `DB_HOST` | `gateway01.us-east-1.prod.aws.tidbcloud.com` |
   | `DB_PORT` | `4000` |
   | `DB_USER` | `tu_usuario.root` |
   | `DB_PASS` | `tu_password` |
   | `DB_NAME` | `sistema_fastfood` |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | (vacio) |
   | `SESSION_SECURE` | `1` |

6. Click en **Create Web Service**

### Paso 3: Verificar despliegue

- URL de produccion: `https://pos-dym-sac.onrender.com`
- El servicio puede tardar unos minutos en iniciar por primera vez

---

## Estructura del Proyecto

```
fastfood/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
│   ├── config.php      # Configuracion general
│   ├── database.php    # Conexion BD (soporta SSL para TiDB)
│   ├── tenant.php      # Funciones multi-tenant
│   └── upload.php      # Manejo de uploads
├── controllers/        # Controladores
├── database/
│   └── schema.sql      # Esquema de base de datos
├── uploads/            # Archivos subidos (logos)
├── views/
│   ├── admin/          # Vistas de administracion
│   ├── auth/           # Login/logout
│   ├── layouts/        # Header/footer
│   └── pos/            # Punto de venta
├── .env.example        # Ejemplo de variables de entorno
├── .htaccess           # Configuracion Apache
├── Dockerfile          # Configuracion Docker
├── index.php           # Entrada principal
└── README.md
```

---

## Credenciales de Acceso (Demo)

Todos los usuarios tienen la contrasena: **password**

### SUPER ADMINISTRADOR (Acceso Global)

| Usuario | Contrasena | Rol |
|---------|------------|-----|
| superadmin | password | SUPER_ADMINISTRADOR |

### TIENDA: Don Pepe

| Usuario | Contrasena | Rol |
|---------|------------|-----|
| admin | password | ADMINISTRADOR |
| cajero | password | CAJERO |

### TIENDA: Skina

| Usuario | Contrasena | Rol |
|---------|------------|-----|
| skina | password | ADMINISTRADOR |
| cajero_skina | password | CAJERO |

---

## Roles y Permisos

### SUPER_ADMINISTRADOR
- Acceso total al sistema
- Gestiona todas las tiendas
- Asigna categorias a tiendas
- Ve datos de todas las tiendas
- Gestiona suscripciones

### ADMINISTRADOR
- Gestiona su tienda asignada
- CRUD de productos, clientes, usuarios
- Acceso a reportes de su tienda
- Gestiona movimientos de inventario

### CAJERO
- Punto de venta (POS)
- Registrar ventas
- Gestionar su caja
- Registrar productos (solo crear)
- Registrar movimientos de inventario (solo entradas)

---

## Configuracion Docker

El proyecto incluye un `Dockerfile` optimizado para Render:

```dockerfile
FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite
# ... configuracion de Apache
EXPOSE 80
CMD ["apache2-foreground"]
```

### Caracteristicas:
- PHP 8.1 con Apache
- Extensiones MySQL habilitadas
- mod_rewrite habilitado
- Soporte SSL para TiDB Cloud

---

## Variables de Entorno

| Variable | Descripcion | Desarrollo | Produccion |
|----------|-------------|------------|------------|
| `DB_HOST` | Host de base de datos | `localhost` | `*.tidbcloud.com` |
| `DB_PORT` | Puerto de BD | `3306` | `4000` |
| `DB_USER` | Usuario de BD | `root` | `user.root` |
| `DB_PASS` | Contrasena de BD | (vacio) | `password` |
| `DB_NAME` | Nombre de BD | `sistema_fastfood` | `sistema_fastfood` |
| `APP_ENV` | Entorno | `development` | `production` |
| `APP_DEBUG` | Mostrar errores | `true` | `false` |
| `APP_URL` | URL base | `/fastfood` | (vacio) |
| `SESSION_SECURE` | Cookie segura | `0` | `1` |

---

## Notas Importantes

1. **TiDB Cloud SSL:** La conexion a TiDB Cloud requiere SSL, el sistema lo detecta automaticamente cuando el host contiene `tidbcloud.com`.

2. **APP_URL vacia en produccion:** En Render, la aplicacion se sirve desde la raiz `/`, no desde `/fastfood`.

3. **Uploads:** Los archivos subidos (logos de tiendas) se almacenan en `/uploads/logos/`.

4. **Logs:** En produccion, los errores se registran en `/logs/error.log`.

---

## Soporte

Desarrollado por **Servicios y Proyectos DYM S.A.C.**

Para soporte tecnico o consultas, contactar al administrador del sistema.
