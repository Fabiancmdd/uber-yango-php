# UberYango PHP 🚖

MVP de un sistema de transporte tipo **Uber / Yango** construido con **PHP 8 puro + MySQL/MariaDB**, sin frameworks pesados ni dependencias `composer`. Pensado como base educativa y como punto de partida para un sistema real.

> Stack: PHP 8.1+, PDO, MySQL 5.7+/MariaDB 10.3+, HTML/CSS/JS vanilla, [Leaflet](https://leafletjs.com/) + OpenStreetMap (mapas gratis, sin API key).

---

## ✨ Funcionalidades

### 👤 Pasajero
- Registro y login con sesiones seguras.
- Solicitar viaje seleccionando origen/destino directamente en un mapa.
- Cálculo de tarifa estimada en vivo (distancia Haversine + duración + tarifa por categoría).
- 3 categorías de vehículo (Economy / Comfort / XL) y método de pago (efectivo / tarjeta).
- Ver estado del viaje en tiempo real (polling cada 4s).
- Cancelar viaje, calificar al conductor (1-5 ⭐), historial completo.

### 🚗 Conductor
- Conectarse / desconectarse para recibir solicitudes.
- Cola de viajes solicitados filtrada por categoría de su vehículo.
- Aceptar viaje, iniciarlo, finalizarlo (recalcula tarifa con duración real).
- Calificar al pasajero, ver ganancias totales y por día.

### 🛠️ Admin
- Resumen general (usuarios, viajes activos, ingresos).
- Gestión de usuarios (suspender / activar).
- Listado completo de viajes con su estado.
- Configuración de tarifas por categoría (base, por km, por minuto, mínimo, moneda).

### 🔒 Seguridad
- Contraseñas con `password_hash()` (bcrypt).
- Tokens CSRF en todas las acciones POST.
- Sesiones HttpOnly + SameSite=Lax + regeneración de id al login.
- Consultas con PDO + sentencias preparadas (anti-inyección).
- Roles separados (`passenger`, `driver`, `admin`) con autorización por endpoint.

---

## 🗂️ Estructura del proyecto

```
uber-yango-php/
├── app/
│   ├── config.php            # carga .env + arreglo de configuración
│   ├── db.php                # PDO singleton
│   ├── helpers.php           # csrf, view, render, haversine, calculate_fare, etc.
│   ├── auth.php              # current_user / require_role / login / logout
│   ├── router.php            # rutas y dispatcher
│   ├── controllers/          # 6 controladores (Home/Auth/Passenger/Driver/Admin/Api)
│   └── views/                # plantillas PHP (layout + por sección)
├── public/
│   ├── index.php             # front controller
│   └── assets/{css,js}/      # estilos + JS de UI
├── database/
│   ├── schema.sql            # CREATE DATABASE + tablas
│   └── seed.sql              # datos demo (5 usuarios, 2 vehículos, tarifas)
├── .env.example
├── .gitignore
└── README.md
```

---

## 🚀 Instalación local

### 1. Requisitos

- PHP **8.1+** con extensión `pdo_mysql` (`sudo apt install php-cli php-mysql`).
- MySQL 5.7+ o MariaDB 10.3+.
- Cualquier servidor: el **built-in server** de PHP es suficiente para desarrollo.

### 2. Clonar y configurar entorno

```bash
git clone https://github.com/Fabiancmdd/uber-yango-php.git
cd uber-yango-php
cp .env.example .env
# edita .env con tus credenciales de MySQL
```

### 3. Crear la base de datos y cargar datos demo

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

> El script crea la base `uber_yango`, las tablas y un set de 5 usuarios demo.

### 4. Levantar el servidor

```bash
php -S localhost:8000 -t public public/index.php
```

Abre <http://localhost:8000>.

---

## 🔑 Cuentas de demostración

| Rol         | Email              | Contraseña |
|-------------|--------------------|------------|
| Admin       | admin@demo.com     | demo1234   |
| Pasajero    | ana@demo.com       | demo1234   |
| Pasajero    | bruno@demo.com     | demo1234   |
| Conductor   | carlos@demo.com    | demo1234   |
| Conductor   | diana@demo.com     | demo1234   |

> Si el hash bcrypt incluido te falla en tu MySQL, regéneralo con:
> ```bash
> php -r "echo password_hash('demo1234', PASSWORD_BCRYPT);"
> ```
> y reemplaza el valor en `database/seed.sql`.

---

## 🧮 Modelo de tarifa

```
fare = base_fare + (per_km × km) + (per_min × min)
fare = max(fare, min_fare)
```

La distancia se calcula con la **fórmula de Haversine** entre las coordenadas de origen y destino. La duración se estima asumiendo 30 km/h promedio urbano (configurable en `helpers.php`). Cuando un conductor finaliza el viaje, la duración real se usa para recalcular la tarifa final.

Configura los valores por categoría desde `/admin/fares`.

---

## 🌐 Endpoints principales

| Método | Ruta                                 | Descripción                          |
|--------|--------------------------------------|--------------------------------------|
| GET    | `/`                                  | Landing                              |
| GET/POST | `/login`, `/register`              | Autenticación                        |
| POST   | `/logout`                            | Cerrar sesión                        |
| GET    | `/passenger`                         | Panel del pasajero                   |
| GET/POST | `/passenger/request`               | Solicitar viaje                      |
| GET    | `/passenger/rides/{id}`              | Detalle del viaje (con polling)      |
| POST   | `/passenger/rides/{id}/cancel`       | Cancelar viaje                       |
| POST   | `/passenger/rides/{id}/rate`         | Calificar viaje                      |
| GET    | `/driver`                            | Panel del conductor                  |
| POST   | `/driver/online`                     | Cambiar estado en línea / fuera      |
| POST   | `/driver/rides/{id}/{accept,start,complete,rate}` | Acciones de viaje |
| GET    | `/admin`, `/admin/users`, `/admin/rides`, `/admin/fares` | Admin    |
| POST   | `/api/fare-estimate`                 | Estimar tarifa (JSON)                |
| GET    | `/api/rides/{id}/status`             | Estado del viaje (JSON, polling)     |
| GET    | `/api/driver/queue`                  | Cola de solicitudes del conductor    |

---

## 🛣️ Roadmap (no incluido en el MVP)

- Pagos reales (Stripe / PayPal / Mercado Pago).
- Tracking GPS en tiempo real con WebSockets / Pusher.
- App móvil para conductor / pasajero.
- Geocoding inverso (autocompletar direcciones reales).
- Asignación automática del conductor más cercano.
- Notificaciones push y SMS (Twilio).
- Promociones, cupones y comisiones por viaje.

---

## 📄 Licencia

MIT — úsalo, modifícalo y conviértelo en tu propio sistema de transporte.
