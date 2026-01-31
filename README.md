# Sistema POS FastFood - Usuarios del Sistema

## Credenciales de Acceso

Todos los usuarios tienen la misma contraseña: **password**

---

### SUPER ADMINISTRADOR (Acceso Global)

| Usuario | Contraseña | Rol | Tienda |
|---------|------------|-----|--------|
| superadmin | password | SUPER_ADMINISTRADOR | Todas (Global) |

---

### TIENDA: Don Pepe (ID: 1)

| Usuario | Contraseña | Rol | Nombre |
|---------|------------|-----|--------|
| admin | password | ADMINISTRADOR | Admin Don Pepe |
| cajero | password | CAJERO | Cajero Don Pepe |

**Datos de la tienda:**
- RUC: 20123456789
- Direccion: Av. Principal 123
- Telefono: 987654321
- Email: donpepe@gmail.com

---

### TIENDA: Skina (ID: 2)

| Usuario | Contraseña | Rol | Nombre |
|---------|------------|-----|--------|
| skina | password | ADMINISTRADOR | Admin Skina |
| cajero_skina | password | CAJERO | Cajero Skina |

**Datos de la tienda:**
- RUC: 20987654321
- Direccion: Jr. Comercio 456
- Telefono: 912345678
- Email: skina@gmail.com

---

## Roles y Permisos

### SUPER_ADMINISTRADOR
- Acceso total al sistema
- Gestiona todas las tiendas
- Asigna categorias a tiendas
- Ve datos de todas las tiendas

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

## URL de Acceso

```
http://localhost/fastfood/
```
