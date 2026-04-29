# Parking The Beasts - Sistema de Parqueadero

Sistema completo de gestion de parqueadero con reservas, pagos y administracion.

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web Apache con mod_rewrite habilitado

## Instalacion

### 1. Configurar la Base de Datos

1. Crear una base de datos MySQL:
```sql
CREATE DATABASE parking_db;
```

2. Importar el esquema SQL proporcionado (parking_db.sql) o usar el schema generado:
```bash
mysql -u tu_usuario -p parking_db < config/schema.sql
```

### 2. Configurar la Conexion

Editar `config/database.php` con tus credenciales:

```php
private $host = 'localhost';
private $database = 'parking_db';
private $username = 'tu_usuario';
private $password = 'tu_password';
```

### 3. Configurar el Servidor Web

Para Apache, asegurate de que mod_rewrite este habilitado:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## Estructura del Proyecto

```
/
├── api/                    # Endpoints de la API REST
│   ├── auth.php           # Autenticacion (login/registro)
│   ├── users.php          # Gestion de usuarios
│   ├── reservations.php   # Gestion de reservas
│   ├── payments.php       # Gestion de pagos
│   ├── rates.php          # Tarifas
│   ├── facilities.php     # Instalaciones/Parqueaderos
│   ├── vehicle-types.php  # Tipos de vehiculos
│   └── pqr.php            # Peticiones, quejas y reclamos
├── app/
│   ├── controllers/       # Controladores PHP
│   └── models/            # Modelos de datos
├── config/
│   ├── database.php       # Configuracion de BD
│   └── schema.sql         # Esquema de la BD
├── js/
│   └── api.js             # Cliente JavaScript para la API
├── css/                   # Estilos CSS
├── views/                 # Vistas HTML
└── index.html             # Pagina principal
```

## API Endpoints

### Autenticacion
- `POST /api/auth.php?action=login` - Iniciar sesion
- `POST /api/auth.php?action=register` - Registrar usuario

### Usuarios
- `GET /api/users.php?action=profile` - Obtener perfil
- `PUT /api/users.php?action=update` - Actualizar perfil
- `PUT /api/users.php?action=password` - Cambiar contrasena

### Reservas
- `GET /api/reservations.php?action=list` - Listar reservas del usuario
- `GET /api/reservations.php?action=detail&id=X` - Detalle de reserva
- `POST /api/reservations.php?action=create` - Crear reserva
- `PUT /api/reservations.php?action=update&id=X` - Actualizar reserva
- `PUT /api/reservations.php?action=cancel&id=X` - Cancelar reserva

### Pagos
- `GET /api/payments.php?action=list` - Listar pagos del usuario
- `POST /api/payments.php?action=create` - Crear pago
- `PUT /api/payments.php?action=confirm&id=X` - Confirmar pago

### Tarifas
- `GET /api/rates.php?action=list` - Listar todas las tarifas
- `GET /api/rates.php?action=calculate` - Calcular precio

### Instalaciones
- `GET /api/facilities.php?action=list` - Listar parqueaderos
- `GET /api/facilities.php?action=capacity&id=X` - Capacidad
- `GET /api/facilities.php?action=occupancy&id=X` - Ocupacion actual

### Tipos de Vehiculos
- `GET /api/vehicle-types.php?action=list` - Listar tipos

## Uso del Cliente JavaScript

```javascript
// Login
const response = await AuthAPI.login('email@ejemplo.com', 'password');
if (response.success) {
    storeUserSession(response.user);
}

// Crear reserva
const reserva = await ReservationsAPI.create(
    1,                          // vehicle_type_id
    'ABC123',                   // vehicle_plate
    '2024-01-15 10:00:00',      // start_at
    '2024-01-15 14:00:00',      // end_at
    'Carro rojo',               // vehicle_description (opcional)
    ''                          // notes (opcional)
);

// Obtener tarifas
const tarifas = await RatesAPI.getList();

// Calcular precio
const precio = await ReservationsAPI.calculatePrice(1, startAt, endAt, 1);
```

## Seguridad

- Las contrasenas se almacenan con hash bcrypt
- Los endpoints protegidos requieren autenticacion via header Authorization
- Se implementa proteccion contra SQL Injection con PDO prepared statements
- CORS configurado para permitir solicitudes desde el frontend

## Licencia

MIT License
