# MoniDuo - Sistema de Monitoreo de Recursos del Sistema

Proyecto de monitoreo en tiempo real e histórico de recursos del sistema (CPU, Memoria RAM y Disco).

- **Backend**: API en PHP 8 conectada a script de métricas con Python (`psutil`) y base de datos MySQL.
- **Frontend**: Aplicación SPA en React 19 + Vite con React Router.




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

