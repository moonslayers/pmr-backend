---
name: Chore maintenance
about: Solicitud de mantenimiento
title: "[CHORE]"
labels: mantenimiento
assignees: ''

---

<!---
Plantilla para tareas de mantenimiento del backend (Laravel/PHP).
-->

## 🛠️ **Descripción detallada**  
Explica **qué** se debe hacer, **por qué** es necesario y **dónde** se aplica. Sé técnico pero claro:  
```plaintext
Ejemplos:
- "Refactorizar el servicio `PaymentProcessor` para eliminar código duplicado (archivo: `app/Services/PaymentProcessor.php`)."
- "Actualizar el paquete `laravel-excel` a la versión 3.1 por vulnerabilidades en dependencias."
- "Migrar consultas RAW a Eloquent en el modelo `Invoice` para mejorar mantenibilidad."
```

## 📍 **Ámbito técnico**  
**Archivos/rutas afectadas:**  
```plaintext
- `app/Models/User.php`  
- `routes/api.php` (Endpoint: GET /api/v1/backups)  
- Configuración: `config/database.php`  
```

## � **Impacto potencial**  
¿Qué podría romperse si no se hace correctamente?  
```plaintext
- "Cambios en el modelo `Order` podrían afectar el webhook de PayPal."  
- "La migración requiere reiniciar los workers de Horizon."  
```

## ✅ **Criterios de éxito**  
Lista de comprobación para validar que la tarea está completa:  
```markdown
- [ ] Tests unitarios pasan (`php artisan test`).  
- [ ] Documentación actualizada (si aplica).  
- [ ] No hay regresiones en la API (verificar con Postman/Newman).  
```

## 🔍 **Contexto adicional**  
```plaintext
- ¿Requiere revisión de otro dev? (ej. @dev-frontend para cambios en API).  
- ¿Está vinculado a un ticket externo? (ej. JIRA-123).  
- Registros de errores relacionados: "Ver logs de Sentry (Error ID: #ABC123)."  
```

## 🖥️ **Entorno**  
**Requisitos técnicos:**  
```plaintext
- PHP 8.2+  
- Laravel 10.x  
- Composer 2.5    
```
```bash
# Comandos útiles (opcional):  
php artisan optimize:clear  
composer install --no-dev  
```
