# API de Solicitudes Resueltas

## Endpoints

### 1. Listar Solicitudes Resueltas

**GET** `/api/solicitudes-resueltas`

Lista solicitudes con estatus "RESUELTA" con filtros opcionales.

**Parámetros Query:**
- `folio` - Filtrar por folio de solicitud (ID)
- `rfc` - Filtrar por RFC de la empresa
- `empresa` - Filtrar por nombre de empresa (razón social o nombre comercial)
- `tematica_id` - Filtrar por ID de temática
- `gobierno_nivel_id` - Filtrar por ID de nivel de gobierno
- `tipo_id` - Filtrar por ID de tipo de solicitud
- `fecha_inicio` - Filtrar por fecha de inicio (YYYY-MM-DD)
- `fecha_fin` - Filtrar por fecha fin (YYYY-MM-DD)
- `page` - Número de página (default: 1)
- `per_page` - Elementos por página (default: 10)

**Permisos:**
- **Admin-General/Admin-Sistema**: Ve todas las solicitudes resueltas
- **Usuario-SEI**: Ve solo sus solicitudes asignadas resueltas

**Respuesta:**
```json
{
  "status": true,
  "message": "Consulta exitosa",
  "data": [
    {
      "id": 1,
      "estatus": "RESUELTA",
      "empresa_razon_social": "Empresa Ejemplo SA de CV",
      "empresa_rfc": "EMPJ123456789",
      "solicitudTipo": { "id": 1, "nombre": "Tipo A" },
      "solicitudTematica": { "id": 1, "nombre": "Temática X" },
      "asignadoA": { "usuarioSei": { "id": 1, "name": "Juan Pérez" } }
    }
  ],
  "page": 1,
  "per_page": 10,
  "total_pages": 1,
  "total_items": 1
}
```

### 2. Obtener Detalles Completos (Sección A)

**GET** `/api/solicitudes-resueltas/{id}/detalles-completos`

Obtiene información completa de una solicitud resuelta, estructurada para los apartados I y II de la Sección A.

**Parámetros URL:**
- `id` - ID de la solicitud resuelta

**Permisos:**
- **Admin-General/Admin-Sistema**: Puede ver cualquier solicitud resuelta
- **Usuario-SEI**: Solo puede ver sus solicitudes asignadas resueltas

**Respuesta:**
```json
{
  "status": true,
  "message": "Consulta exitosa",
  "data": {
    "folio": 1,
    "solicitud": { /* Datos completos de la solicitud */ },
    "apartado_i": {
      "nombre_completo": "Juan Pérez",
      "correo_electronico": "juan@empresa.com",
      "rfc": "JUPR123456789",
      "tipo_usuario": "EXTERNO",
      "telefono": "55-1234-5678",
      "cargo": "Gerente",
      "fecha_registro": "2024-01-15 10:30:00",
      "correo_verificado": true,
      "roles": ["solicitante"],
      "empresa_asociada": { "id": 1, "razon_social": "Empresa Asociada" }
    },
    "apartado_ii": {
      "tipo_persona": "moral",
      "rfc": "EMPJ123456789",
      "razon_social": "Empresa Ejemplo SA de CV",
      "nombre_comercial": "Ejemplo Comercial",
      "sector_comercial": 1,
      "actividad_economica": 1,
      "tipo_empresa": 1,
      "pais_procedencia": "México",
      "nombre_solicitante": "Juan Pérez",
      "cargo_solicitante": "Gerente",
      "correo_solicitante": "juan@empresa.com",
      "telefono_solicitante": "55-1234-5678",
      "municipio_id": 1,
      "numero_empleados": 50,
      "relacion_empresa": { "id": 1, "datos_completos": { /* ... */ } }
    }
  }
}
```

### 3. Generar Estadísticas y Reportes

**GET** `/api/solicitudes-resueltas/estadisticas`

Genera datos estadísticos para gráficos y reportes de solicitudes resueltas.

**Parámetros Query:**
- `periodo` - Período para estadísticas por folio: `dia`, `semana`, `mes`, `año` (default: `mes`)
- `limit` - Límite de resultados para estadísticas por empresa (default: 10)

**Permisos:**
- **Admin-General/Admin-Sistema**: Estadísticas de todas las solicitudes resueltas
- **Usuario-SEI**: Estadísticas solo de sus solicitudes asignadas resueltas

**Respuesta:**
```json
{
  "status": true,
  "message": "Estadísticas generadas exitosamente",
  "data": {
    "folio": [
      { "periodo": "2024-01", "total": 15 },
      { "periodo": "2024-02", "total": 23 }
    ],
    "empresa": [
      { "empresa_razon_social": "Empresa A", "empresa_rfc": "AAA123456789", "total": 8 },
      { "empresa_razon_social": "Empresa B", "empresa_rfc": "BBB987654321", "total": 5 }
    ],
    "sector_comercial": [
      { "empresa_sector_comercial_id": 1, "total": 12 },
      { "empresa_sector_comercial_id": 2, "total": 8 }
    ],
    "tematica": [
      { "solicitud_tematica_id": 1, "total": 10, "solicitudTematica": { "nombre": "Temática X" } },
      { "solicitud_tematica_id": 2, "total": 8, "solicitudTematica": { "nombre": "Temática Y" } }
    ],
    "tipo_solicitud": [
      { "solicitud_tipo_id": 1, "total": 15, "solicitudTipo": { "nombre": "Tipo A" } },
      { "solicitud_tipo_id": 2, "total": 5, "solicitudTipo": { "nombre": "Tipo B" } }
    ],
    "estatus": [
      { "estatus": "RESUELTA", "total": 20 }
    ],
    "orden_gobierno": [
      { "solicitud_gobierno_nivel_id": 1, "total": 12, "solicitudGobiernoNivel": { "nombre": "Federal" } },
      { "solicitud_gobierno_nivel_id": 2, "total": 8, "solicitudGobiernoNivel": { "nombre": "Estatal" } }
    ]
  }
}
```

## Códigos de Error

- **401**: No autenticado
- **403**: Permisos insuficientes
- **404**: Solicitud no encontrada o no está resuelta
- **422**: Errores de validación
- **500**: Error interno del servidor

## Uso en Frontend

### Ejemplo de llamada con filtros:

```javascript
// Listar solicitudes resueltas con filtros
const response = await fetch('/api/solicitudes-resueltas?' + new URLSearchParams({
  rfc: 'ABC',
  empresa: 'Empresa',
  fecha_inicio: '2024-01-01',
  fecha_fin: '2024-12-31',
  page: 1,
  per_page: 20
}), {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
```

### Ejemplo de gráficos:

```javascript
// Obtener estadísticas
const statsResponse = await fetch('/api/solicitudes-resueltas/estadisticas?periodo=mes', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const stats = await statsResponse.json();

// Usar datos para gráficos
const chartData = stats.data.folio.map(item => ({
  month: item.periodo,
  count: item.total
}));
```

## Implementación Técnica

### Scopes Disponibles en Solicitud Model

- `scopeResueltas()` - Filtrar solicitudes resueltas
- `scopePorRfc($rfc)` - Filtrar por RFC
- `scopePorEmpresa($empresa)` - Filtrar por nombre de empresa
- `scopePorTematica($tematicaId)` - Filtrar por temática
- `scopePorTipo($tipoId)` - Filtrar por tipo
- `scopePorOrdenGobierno($gobiernoNivelId)` - Filtrar por orden de gobierno
- `scopeAsignadasA($usuarioSeiId)` - Filtrar por usuario SEI asignado
- `scopePorRangoFechas($fechaInicio, $fechaFin)` - Filtrar por rango de fechas

### Datos del Apartado I (Usuario UE)

- `nombre_completo` - Nombre completo del usuario
- `correo_electronico` - Correo electrónico
- `rfc` - RFC del usuario
- `tipo_usuario` - Tipo de usuario (INTERNO/EXTERNO)
- `telefono` - Teléfono del solicitante
- `cargo` - Cargo del solicitante
- `fecha_registro` - Fecha de registro del usuario
- `correo_verificado` - Estado de verificación de correo
- `roles` - Roles del usuario
- `empresa_asociada` - Datos de empresa asociada (si existe)

### Datos del Apartado II (Empresa UE)

- Todos los campos de snapshot de empresa almacenados en la solicitud
- `relacion_empresa` - Datos completos de la empresa relacionada (si existe)