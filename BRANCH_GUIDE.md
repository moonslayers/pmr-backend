# 🌿 Guía Rápida: Gestión de Ramas

## 📋 Convención de nombres para ramas

Usa esta estructura clara para cada rama:

```bash
tipo/nombre-o-id-del-issue
```

### ✅ Tipos comunes

| Tipo     | Uso                                      |
|----------|------------------------------------------|
| `feat`   | Nuevas funcionalidades                   |
| `fix`    | Correcciones de bugs                     |
| `chore`  | Tareas menores o mantenimiento           |
| `docs`   | Cambios en documentación                 |
| `hotfix` | Corrección urgente                       |

### 📌 Ejemplos

- `feat/user-login`
- `fix/modal-bug`
- `docs/setup-guide`
- `chore/update-deps`
- `hotfix/server-error`

---

## 🔁 Flujo recomendado

1. **Rama principal**:  
   Trabaja con `main` como tu única rama base.

2. **Crear una rama por issue/feature**:  

   ```bash
   git checkout -b feat/issue-123 main
   ```

3. **Hacer commits claros y relacionados con el issue**  
   Usa la [guía rápida de commits](./COMMIT_GUIDE.md).

4. **Subir la rama al remoto**:

   ```bash
   git push origin feat/issue-123
   ```

5. **Crear un Pull Request (PR)** desde esa rama hacia `main`.

6. **Revisar, comentar y mergear cuando esté listo**.

7. **Eliminar la rama local y remota después del merge**:

   ```bash
   git branch -d feat/issue-123         # Elimina rama local
   git push origin --delete feat/issue-123  # Elimina rama remota
   ```

---

## 🧹 Buenas prácticas para mantener orden

✅ **Siempre crea una rama nueva para cada cambio**, aunque sea pequeño.

✅ **Sincroniza antes de empezar**:

```bash
git checkout main
git pull origin main
```

✅ **Elimina las ramas ya mergiadas** para no acumular basura.

✅ **Nombres descriptivos** en ramas. Evita cosas como `fix`, `update`, `temp`.

✅ **Mantén los PR pequeños y enfocados** en un solo tema.

✅ **Usa números de issue en los PRs o commits** si usas GitHub/GitLab:

```bash
feat/user-profile (#45)
```

---

## 📝 Ejemplo rápido de flujo

```bash
# Actualizar main antes de empezar
git checkout main
git pull origin main

# Crear rama nueva
git checkout -b fix/button-crash

# Hacer cambios y commit
git add .
git commit -m "fix/button-crash"

# Subir rama
git push origin fix/button-crash

# En GitHub/GitLab: crear PR → revisar → mergear

# Eliminar rama tras mergear
git branch -d fix/button-crash
git push origin --delete fix/button-crash
```
