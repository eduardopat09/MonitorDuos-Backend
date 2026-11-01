# MoniDuo - Sistema de Monitoreo de Recursos del Sistema

Proyecto de monitoreo en tiempo real e histórico de recursos del sistema (CPU, Memoria RAM y Disco).

- **Backend**: API en PHP 8 conectada a script de métricas con Python (`psutil`) y base de datos MySQL.
- **Frontend**: Aplicación SPA en React 19 + Vite con React Router.

  ## Estructura General del Proyecto

```text
MonitorEquipo/
│
├── DOCUMENTACION.md                  # Este documento
├── reporte_de_proyecto.md            # Reporte de práctica de refactorización y PSR
│
├── MonitorDuos-Backend/              # Repositorio del Backend
│   ├── README.md
│   └── backend/
│       ├── Dockerfile                # Configuración de contenedor Docker
│       ├── composer.json             # Manifiesto y mapeo PSR-4
│       ├── index.php                 # Punto de entrada HTTP principal
│       ├── services.php              # Enrutador frontal y despachador (Dispatcher)
│       ├── config/
│       │   └── DatabaseConfig.php    # Configuración de base de datos
│       ├── database/
│       │   └── Connection.php        # Conexión Singleton a MySQL vía PDO
│       ├── python/
│       │   └── obtencion_datos_rendimiento.py  # Script de lectura con psutil
│       ├── sessions/
│       │   └── Session.php           # Manejo de sesiones y cookies
│       └── utils/
│           ├── Errors.php            # Emisión de errores JSON estandarizados
│           ├── Tools.php             # Utilidades, puente Python y CORS
│           └── ValueValidation.php   # Validaciones de entradas y fechas
│
└── MonitorDuos-Frontend/             # Repositorio del Frontend
    ├── README.md
    └── MonitorDuo/
        ├── .env                      # Variables de entorno locales
        ├── .env.example              # Plantilla de variables de entorno
        ├── .gitignore
        ├── .oxlintrc.json
        ├── Estado_Temporal_Reglas.md # Matriz de cumplimiento de requerimientos
        ├── index.html                # Plantilla HTML base
        ├── package.json              # Dependencias y scripts de npm
        ├── vite.config.js            # Configuración de Vite y proxy /api
        └── src/
            ├── App.css
            ├── App.jsx               # Enrutamiento y rutas protegidas
            ├── index.css             # Estilos globales y temas del panel
            ├── main.jsx              # Montaje de React en el DOM
            ├── Pages/
            │   ├── AlertsPage.jsx    # Configuración de umbrales y alertas
            │   ├── HomePage.jsx      # Panel principal (en vivo e histórico)
            │   └── Login.jsx         # Pantalla de inicio de sesión
            └── services/
                ├── api.js            # Cliente HTTP base con timeout y CORS
                ├── authService.js    # Servicio de login, sesión y logout
                ├── monitorService.js # Servicio de métricas en vivo e histórico
                └── thresholdStorage.js # Almacenamiento local de umbrales
```
## Funcionamiento General de Cada Archivo

### Backend 

- **`index.php`**:
  Es el enrutador frontal (Front Controller). Se encarga del autocargado de clases del espacio de nombres `App\`, aplica las cabeceras CORS de inmediato, decodifica el cuerpo JSON de las peticiones entrantes y despacha a la acción correspondiente (`live`, `history`, `login`, `profile`, `logout`). Si la ruta no existe, emite un error 404 estandarizado.

- **`Services.php`**:
  Contiene la lógica de negocio principal:
  - `login($usuario)`: Inicia la sesión PHP y registra el usuario en `$_SESSION`.
  - `checkSession()`: Verifica si existe una sesión activa y devuelve el usuario autenticado (o HTTP 401 si no existe).
  - `logout()`: Destruye los datos de sesión y revoca la cookie en el navegador.
  - `obtenerYGuardarDatos()`: Ejecuta mediante `shell_exec` el script de Python, valida la salida JSON, registra una nueva sesión en la tabla `monitoreo`, inserta los porcentajes en las tablas `cpu`, `ram` y `disco`, e incluye en la respuesta el nombre del equipo (`gethostname()`) y marca de tiempo.
  - `historialFechas($inicio, $fin)`: Valida el rango de fechas, realiza un `INNER JOIN` entre las tablas de métricas y devuelve las lecturas cronológicas.

- **`Connection.php`**:
  Implementa el patrón de diseño Singleton para gestionar la conexión a MySQL a través de PDO. En caso de error de conexión, captura la excepción y emite una respuesta JSON con código HTTP 500 y cabeceras CORS activas para no generar bloqueos en el navegador.

- **`config/DatabaseConfig.php`**:
  Centraliza las variables de conexión a la base de datos MySQL (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`), permitiendo sobreescribirlas mediante variables de entorno o utilizando los valores predeterminados (`localhost`, `rendimiento_componentes`, `root`, `sasageyo`).

- **`sessions/Session.php`**:
  Encapsula el manejo de la sesión nativa de PHP (`session_start()`, lectura, escritura y destrucción). Configura los atributos de la cookie (`lifetime`, `httponly`, `samesite`, `secure`) adaptándose automáticamente a entornos HTTP locales y HTTPS en producción.

- **`ValueValidation.php`**:
  Proporciona métodos estáticos para validar IDs enteros, campos de texto obligatorios y cadenas de fecha. Normaliza formatos de solo fecha (`YYYY-MM-DD`) a marcas de tiempo completas (`00:00:00` para inicio y `23:59:59` para fin).

- **`Tools.php`**:
  Ofrece funciones auxiliares:
  - `applyCorsHeaders()`: Gestiona las cabeceras de control de acceso HTTP (`Access-Control-Allow-*`) y procesa solicitudes preflight `OPTIONS`.
  - `jsonResponse($data, $statusCode)`: Aplica cabeceras, define el código de estado HTTP y emite la respuesta JSON terminando la ejecución.

- **`Errors.php`**:
  Enum de PHP con plantillas predefinidas de error en formato JSON (datos inválidos, falta de autorización, registros no encontrados o errores de base de datos).

- **`python/obtencion_datos_rendimiento.py`**:
  Script en Python que utiliza la biblioteca `psutil`:
  - `psutil.cpu_percent(interval=1)`: Mide el porcentaje de uso de CPU en un lapso de 1 segundo.
  - `psutil.virtual_memory().percent`: Porcentaje de memoria RAM en uso.
  - `psutil.disk_usage('/').percent`: Porcentaje de ocupación del disco principal.
  - Emite el resultado como un JSON en la salida estándar.

- **`Dockerfile`**:
  Especificación de contenedor basada en `php:8.2-apache`, instala extensiones MySQL PDO, crea un entorno virtual de Python con `psutil`, habilita el módulo rewrite de Apache y expone el puerto 80.
  
## Inicio Rápido

### 1. Iniciar el Backend (Terminal 1)
```powershell
php -S 127.0.0.1:8000 
```

### 2. Iniciar el Frontend (Terminal 2)
```powershell
& "C:\Program Files\nodejs\npm.cmd" run dev
```

Acceder en el navegador a: `http://localhost:5173`
