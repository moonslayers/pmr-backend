# Guía de Estilo y Convenciones - Backend Laravel

## 📌 Convenciones Generales

### Estructura de Archivos

- **Todos los archivos** siguen PSR-1, PSR-2 y PSR-12
- **Nombres en inglés** (excepto términos de dominio específico en español)
- **Siempre usar Artisan** para generar código base:

  ```bash
  php artisan make:model Product -mcr # Modelo + migración + controlador
  ```

## 🏗️ Estructura de Proyecto

### 1. Modelos

- **Nomenclatura**: Singular (ej: `User` para tabla `users`)
- **Relaciones**: Nombres en camelCase referenciando tabla relacionada:

  ```php
  public function orderItems() // Para relación con tabla order_items
  public function paymentMethod() // Para tabla payment_methods
  ```

### 2. Migraciones

- **Siempre crear migraciones** para cambios estructurales
- **Nombre descriptivo**:

  ```bash
  php artisan make:migration add_discount_column_to_orders_table
  ```

### 3. Controladores

- **Herencia obligatoria** de `SuperController` para CRUDs de tablas
- **Métodos estándar** (usar los proporcionados por SuperController):

  ```php
  class ProductController extends SuperController
  {
          public function __construct()
        {
            parent::__construct(new Product());
            //Reglas de validacion con Validator de Laravel
            $this->rules = [
                'email' => 'required|email|unique:usuarios,email',
                'password' => 'required|string|min:6',
                'nombre' => 'required|string|min:4|max:255',
                'apellido_paterno' => 'required|string|max:255',
                'apellido_materno' => 'required|string|max:255',
                'usuario_id' => 'required|integer|min:1|exists:usuarios,id',
            ];

            //relaciones del modelo y sub relaciones disponibles para servir
            $this->mainRelations = [
                'perfilesUsuarios',
                'perfilesUsuarios.tiposUsuarios',
                'perfilesUsuarios.permisos',
                'perfilesUsuarios.permisosUsuarios',
                'perfilesUsuarios.permisosUsuarios.permisos',
                'perfilesUsuarios.sucursales',
                'usuarios'
            ];
        }
  }
  ```

## ✨ Buenas Prácticas

### Base de Datos

1. **Orden de preferencia**:
   - Eloquent ORM (80% de los casos)
   - Query Builder (15%)
   - Raw SQL (5% - solo con aprobación del equipo)

2. **Ejemplo óptimo**:

   ```php
   // Bueno
   User::where('status', 'active')->with('orders')->get();

   // Aceptable (cuando se necesita performance)
   DB::table('users')->where(...)->join(...)->get();

   // Último recurso (requiere revisión)
   DB::select("SELECT complex_query FROM ...");
   ```

### Documentación

**Todo modelo debe incluir**:

```php
/**
 * @property int $id
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|Order[] $orders
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 */
class User extends Model
{
    // ...
}
```

## ⚙️ Configuraciones Especiales

### Valores por Defecto

1. **Prioridad en migraciones**:

   ```php
   $table->string('status')->default('pending');
   ```

2. **SuperController solo cuando**:
   - El default depende de lógica compleja
   - Es temporal (hasta próxima migración)

### Manejo de ENUMs

**Regla estricta**:

- Cambios de valores ENUM → **siempre migración**
- Ejemplo:

  ```php
  // Migración para añadir nuevo estado
  DB::statement("ALTER TABLE orders MODIFY status ENUM('new','processed')");
  ```

## 🧹 Código Limpio

### Reglas Obligatorias

1. **PHPDoc completo** para:
   - Clases
   - Métodos públicos/protegidos
   - Propiedades importantes

2. **Ejemplo de método bien documentado**:

   ```php
   /**
    * Calcula el total de pedidos para un usuario
    * @param int $userId
    * @param string|null $month Filtro opcional (formato YYYY-MM)
    * @return float
    * @throws \InvalidArgumentException
    */
   public function calculateUserTotal(int $userId, ?string $month = null): float
   {
       // ... implementación
   }
   ```

## 🔄 Workflow de Desarrollo

1. **Para cambios estructurales**:

   ```markdown
   1. Crear migración
   2. Crear seeder si afecta datos iniciales
   3. Actualizar modelos relacionados
   ```

2. **Para nuevos endpoints**:

   ```markdown
   1. Actualizar rutas
   2. Extender SuperController
   3. Documentar en PHPDoc
   ```

---

📌 **Nota Final**: Esta guía es de cumplimiento obligatorio. Cualquier excepción debe ser aprobada por el equipo técnico mediante revisión de código.
