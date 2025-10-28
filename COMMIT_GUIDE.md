# 📝 Guía de Commits - Convenciones

Esta es una guía rápida y estándar para realizar commits de forma clara, consistente y fácil de seguir en este repositorio.

## 🧩 Tipos comunes de commits

| Tipo        | Descripción                                      |
|-------------|--------------------------------------------------|
| `feat`      | Nueva funcionalidad                              |
| `fix`       | Corrección de un bug                             |
| `chore`     | Tareas menores (actualizaciones, mantenimiento)  |
| `docs`      | Cambios en documentación                         |
| `style`     | Cambios estéticos (formato, estilos, etc.)       |
| `refactor`  | Reestructuración sin cambiar comportamiento      |
| `test`      | Añadir o corregir pruebas                        |
| `build`     | Cambios en sistema de build o dependencias       |
| `ci`        | Cambios en configuración de CI/CD                |
| `perf`      | Mejoras de rendimiento                           |
| `config`    | Cambios en archivos de configuración             |

---

## 📌 Formato básico del commit

```bash
tipo/descripcion-breve
```

- Usa minúsculas.
- Separa palabras con `-`.
- Sea claro y descriptivo.

### ✅ Ejemplos buenos

- `feat/cart-summary`: Añade resumen de precios en carrito
- `fix/modal-close`: Corrige error al cerrar el modal
- `chore/npm-updates`: Actualiza dependencias
- `docs/readme-update`: Instrucciones actualizadas

---

## 🧠 Tips rápidos

- No uses: `"update"`, `"change"`, `"modified"` como tipo.
- Puedes añadir detalles adicionales después del título si lo necesitas:

```bash
feat/cart-summary
- Añade resumen de precios en carrito
- Arregla cálculo de impuestos
```

---

## 🛠️ Bonus: Alias de Git (opcional)

Agrega estos alias a Git para agilizar los commits:

```bash
git config --global alias.feat 'commit -m "feat"'
git config --global alias.fix 'commit -m "fix"'
git config --global alias.chore 'commit -m "chore"'
```

Uso rápido:

```bash
git add .
git feat/cart-summary
```
