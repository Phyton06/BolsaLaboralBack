# Guía de Seguridad y Autenticación — BolsaLaboralBack API

Este documento explica cómo funciona la seguridad implementada en los endpoints y cómo interactuar con ellos desde Postman y el Frontend.

## 1. Capas de Seguridad Implementadas

Se utiliza un **Middleware de Autenticación JWT**. Antes de que el servidor procese cualquier petición protegida, verifica:

1. **Existencia del Token**: La petición debe incluir un Header `Authorization`.
2. **Validez del Token**: El token debe ser un JWT válido, firmado por el servidor y no expirado.
3. **Lista Negra (Blacklist)**: Se verifica que el token no haya sido revocado (Logout).
4. **Rol del Usuario** (endpoints específicos): Se verifica que el usuario tenga el rol necesario (`candidato`, `empresa`, `admin`).

Si alguna verificación falla, el servidor responde con `401 Unauthorized` o `403 Forbidden`.

---

## 2. Cómo Probar en Postman

### Paso 1: Obtener el Token (Login)

* **Método**: `POST`
* **URL**: `{{url}}/api/v1/login`
* **Body (JSON)**:
  ```json
  {
      "email": "tu@email.com",
      "password": "tu_contrasena"
  }
  ```
* **Respuesta**: Copia el valor del campo `"token"`.

### Paso 2: Usar el Token en Endpoints Protegidos

En cualquier petición protegida:

1. Ve a la pestaña **Authorization**.
2. Selecciona Type: **Bearer Token**.
3. Pega el token en el campo **Token**.

O alternativamente, agrega el header manualmente:

| Key | Value |
|-----|-------|
| Authorization | Bearer `TU_TOKEN_AQUI` |

---

## 3. Flujo Completo de Autenticación

### 3.1 Login
```
POST /api/v1/login
→ Devuelve: { "token": "...", "refresh_token": "..." }
```

### 3.2 Acceder a rutas protegidas
```
GET /api/v1/ofertas
Header: Authorization: Bearer <token>
```

### 3.3 Renovar token expirado
```
POST /api/v1/refresh-token
Body: { "refresh_token": "..." }
→ Devuelve: { "access_token": "..." }
```

### 3.4 Logout / Revocar sesión
```
POST /api/v1/blacklist-token
Body: { "token": "..." }
```

```
POST /api/v1/revoke-refresh-token
Body: { "refresh_token": "..." }
```

### 3.5 Recuperación de contraseña

1. **Solicitar reset:**
   ```
   POST /api/v1/request-password-reset
   Body: { "email": "usuario@email.com" }
   ```

2. **Validar token:**
   ```
   POST /api/v1/validate-reset-token
   Body: { "token": "..." }
   ```

3. **Cambiar contraseña:**
   ```
   POST /api/v1/reset-password
   Body: { "token": "...", "new_password": "...", "confirm_password": "..." }
   ```

---

## 4. Implementación en Frontend

### A. Almacenamiento Seguro

```javascript
// Login.js
const handleLogin = async () => {
    const res = await api.post('/login', credentials);
    if (res.data.token) {
        localStorage.setItem('token', res.data.token);
        localStorage.setItem('refresh_token', res.data.refresh_token);
    }
};
```

### B. Interceptor de Axios (Recomendado)

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8080/api/v1'
});

// Interceptor para agregar Authorization Header
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Interceptor para manejar Expiración (401)
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response && error.response.status === 401) {
            const originalRequest = error.config;

            // Intentar renovar con refresh token
            if (!originalRequest._retry) {
                originalRequest._retry = true;
                try {
                    const refreshToken = localStorage.getItem('refresh_token');
                    const res = await api.post('/refresh-token', { refresh_token: refreshToken });

                    localStorage.setItem('token', res.data.access_token);
                    originalRequest.headers.Authorization = `Bearer ${res.data.access_token}`;

                    return api(originalRequest);
                } catch (refreshError) {
                    // Refresh token también expiró → forzar login
                    localStorage.removeItem('token');
                    localStorage.removeItem('refresh_token');
                    window.location.href = '/login';
                }
            }
        }
        return Promise.reject(error);
    }
);

export default api;
```

### C. Consumo de Endpoints

```javascript
import api from './api';

// Listar ofertas disponibles
const obtenerOfertas = async () => {
    try {
        const response = await api.get('/ofertas');
        console.log(response.data);
    } catch (error) {
        console.error("Error al obtener ofertas", error);
    }
};

// Postularse a una oferta
const postularse = async (ofertaId) => {
    try {
        const response = await api.post('/postulaciones', { oferta_id: ofertaId });
        console.log("Postulación exitosa:", response.data);
    } catch (error) {
        console.error("Error al postularse", error);
    }
};

// Cambiar estado de postulación (solo empresa)
const cambiarEstadoPostulacion = async (postulacionId, nuevoEstado) => {
    try {
        const response = await api.put(`/postulaciones/${postulacionId}/estado`, {
            estado: nuevoEstado // 'revisada', 'aceptada', 'rechazada'
        });
        console.log("Estado actualizado:", response.data);
    } catch (error) {
        console.error("Error al cambiar estado", error);
    }
};
```

---

## 5. Notas sobre Tokens

| Token | Duración | Propósito |
|-------|----------|-----------|
| **Access Token (JWT)** | 1 hora | Autenticación en endpoints protegidos |
| **Refresh Token** | 30 días | Renovar el access token sin re-login |
| **Password Reset Token** | 1 hora | Cambiar contraseña olvidada |

* El refresh token se reutiliza mientras esté vigente y no revocado.
* Los tokens en la blacklist son rechazados inmediatamente.
* Se recomienda limpiar tokens expirados de la blacklist periódicamente.

---

## 6. Endpoints Públicos (sin autenticación)

Estos endpoints **NO** requieren token:

* `POST /api/v1/login`
* `POST /api/v1/register`
* `POST /api/v1/refresh-token`
* `POST /api/v1/request-password-reset`
* `POST /api/v1/validate-reset-token`
* `POST /api/v1/reset-password`
* `GET /api/v1/ofertas` (listado público)
* `GET /api/v1/ofertas/{id}` (detalle público)
* `POST /api/v1/validate-token`

Todos los demás endpoints requieren autenticación.
