# 🚀 CerriFlash - Plataforma Web de Envíos y Domicilios

> **“Tu pedido, rápido y seguro.”**

CerriFlash es una aplicación web inspirada en plataformas como DiDi, Uber Eats y Rappi, diseñada para conectar restaurantes, comercios y empresas locales con repartidores independientes disponibles para prestar el servicio en **El Cerrito, Valle del Cauca**.

---

## 🛠️ Tecnologías Empleadas

* **Backend:** PHP 8.2 (Programación Orientada a Objetos, PDO, Prepared Statements).
* **Base de Datos:** MariaDB / MySQL (14 tablas relacionales normalizadas con llaves foráneas e índices).
* **Frontend:** HTML5, CSS3, JavaScript Vanilla / AJAX, Bootstrap 5, Bootstrap Icons.
* **Mapas y Rutas:** Leaflet.js + OpenStreetMap (geocodificación, cálculo Haversine de distancia y visualización de ruta).
* **Seguridad:** Encriptación BCrypt (`password_hash`), mitigación de ataques CSRF mediante tokens, escape XSS y control estricto de roles.
* **API REST:** Endpoints en `/api/` para integración con futuras aplicaciones móviles (Android e iOS) mediante Bearer Token.

---

## 👥 Cuentas de Prueba y Credenciales (Seed Data)

Todas las cuentas de prueba tienen la contraseña: **`admin123`**

| Rol | Correo Electrónico | Contraseña | Panel de Acceso |
|---|---|---|---|
| **Administrador** | `admin@cerriflash.com` | `admin123` | `/admin/index.php` |
| **Restaurante** | `pizzas@cerriflash.com` | `admin123` | `/restaurante/index.php` |
| **Domiciliario** | `domi@cerriflash.com` | `admin123` | `/domiciliario/index.php` |
| **Cliente** | `cliente@cerriflash.com` | `admin123` | `/cliente/index.php` |

---

## 💻 CÓMO COPIAR Y PRESENTAR EL PROYECTO EN OTRO COMPUTADOR CON VISUAL STUDIO CODE

### Paso 1: Copiar la Carpeta del Proyecto
1. Copia toda la carpeta **`cerriflash`** en una memoria USB o comprímela en un archivo `.zip`.
2. En el otro computador, colócala en una de estas dos ubicaciones:
   - **Opción recomendada para XAMPP:** `C:\xampp\htdocs\cerriflash`
   - **O cualquier carpeta de trabajo:** ej. `C:\proyectos\cerriflash` o el Escritorio.

---

### Paso 2: Importar la Base de Datos en MySQL
1. Abre el **XAMPP Control Panel** en el nuevo computador y presiona **Start** en **MySQL** y **Apache**.
2. Abre tu navegador e ingresa a **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Haz clic en la pestaña **Importar** (arriba).
4. Haz clic en **Seleccionar archivo** y busca el archivo:
   `cerriflash/database/cerriflash.sql`
5. Haz clic en el botón **Importar** (al final de la página).
   *(El script creará automáticamente la base de datos `cerriflash`, todas sus tablas relacionales y las cuentas demo).*

> **Alternativa por terminal (CMD o PowerShell):**
> ```bash
> C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\cerriflash\database\cerriflash.sql"
> ```

---

### Paso 3: Abrir y Ejecutar el Proyecto en Visual Studio Code
1. Abre **Visual Studio Code**.
2. Ve a **Archivo (File) > Abrir Carpeta (Open Folder...)** y selecciona la carpeta **`cerriflash`**.
3. Abre una terminal integrada en VS Code con el atajo: `Ctrl + ñ` (o menú **Terminal > New Terminal**).
4. Tienes dos formas de ejecutar la aplicación:

#### Método A: Con el servidor integrado de PHP en VS Code (Más Rápido y Portátil)
En la terminal de VS Code ejecuta:
```bash
php -S localhost:8000
```
*(Si no reconoce `php`, usa la ruta de XAMPP: `C:\xampp\php\php.exe -S localhost:8000`)*

Luego abre en tu navegador:
👉 **[http://localhost:8000](http://localhost:8000)**

#### Método B: Con el servidor Apache de XAMPP
Si colocaste la carpeta en `C:\xampp\htdocs\cerriflash`, abre directamente en tu navegador:
👉 **[http://localhost/cerriflash](http://localhost/cerriflash)**

---

## 🎬 GUION SUGERIDO PARA LA DEMOSTRACIÓN / PRESENTACIÓN

Para impresionar a los evaluadores durante la presentación, sigue este orden:

1. **Página Principal (Landing Page):**
   - Muestra la marca **CerriFlash**, el eslogan *“Tu pedido, rápido y seguro.”*, la sección de cobertura, las preguntas frecuentes y cómo funciona el cálculo de tarifas.
2. **Acceso como Administrador (`admin@cerriflash.com`):**
   - Muestra el Dashboard con métricas globales.
   - En **Tarifas**, muestra el simulador interactivo de reparto.
   - En **Usuarios**, muestra cómo se aprueban o suspenden comercios y domiciliarios con sus documentos.
3. **Acceso como Restaurante (`pizzas@cerriflash.com`):**
   - Presiona el botón grande **“CREAR DOMICILIO”**.
   - Muestra el **Mapa interactivo Leaflet**: arrastra el pin verde del cliente o haz clic en el mapa para ver cómo la distancia y el precio se recalculan al instante.
   - Presiona **“PUBLICAR DOMICILIO”**.
4. **Acceso como Domiciliario (`domi@cerriflash.com`):**
   - Muestra el switch **🟢 DISPONIBLE / ⚪ NO DISPONIBLE**.
   - Al activarlo, entra la tarjeta **NUEVO DOMICILIO** con sonido audible, mostrando ganancia, distancia y tiempo.
   - Presiona **ACEPTAR** (se bloquea para los demás).
   - Avanza por los 10 estados secuenciales (*En camino a recogida → Llegó al restaurante → Pedido recogido → En camino al cliente → Entregado → Finalizado*).
   - Abre **Mi Billetera** para mostrar el dinero acumulado y la liquidación automática.
5. **Seguimiento Público y Cliente (`cliente@cerriflash.com`):**
   - Abre la página de rastreo con el código del pedido (ej. `CF-XXXXXX`).
   - Muestra el mapa en vivo con el repartidor en movimiento y la línea de tiempo de 5 etapas.
6. **Reportes e Incidencias (Admin):**
   - Muestra la generación de reportes y la exportación a Excel / CSV.
7. **Documentación API Móvil:**
   - Abre `http://localhost/cerriflash/api/index.php` para demostrar que la plataforma está lista para conectar aplicaciones nativas en Android e iOS.

