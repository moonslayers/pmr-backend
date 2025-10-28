<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Services\Verifiers;

trait ValidatesModelData
{
    protected Verifiers $verifiers;
    protected array $rules = [];
    protected array $rulesUpdate = [];
    protected bool $apllyRulesOnUpdate = false;
    protected array $valoresDefault = [];
    protected bool $useUsuarioId = true;
    protected string $createdByColumn = 'created_by';
    protected array $ignoreArgs = [];

    /**
     * Initialize validators
     */
    protected function initializeValidators(): void
    {
        $this->verifiers = app(Verifiers::class);
    }

    /**
     * Validate data for creation
     */
    protected function validateCreateData(array $data): array
    {
        $validator = Validator::make($data, $this->rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $this->extractModelData($data);
    }

    /**
     * Validate data for update
     */
    protected function validateUpdateData(array $data): array
    {
        $data = $this->extractModelData($data);
        unset($data[$this->createdByColumn]);

        // Apply rules on update if enabled
        if ($this->apllyRulesOnUpdate) {
            $validator = Validator::make($data, $this->rules);
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }

        // Apply update rules
        if (!empty($this->rulesUpdate)) {
            $validator = Validator::make($data, $this->rulesUpdate);
            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }

        return $data;
    }

    /**
     * Validate multiple records for bulk creation
     */
    protected function validateMultipleData(array $data): array
    {
        $errors = [];
        $cleanData = [];

        foreach ($data as $index => $item) {
            try {
                $validatedItem = $this->validateCreateData($item);
                $cleanData[] = $validatedItem;
            } catch (\Illuminate\Validation\ValidationException $e) {
                $errors[$index] = $e->errors();
            }
        }

        if (!empty($errors)) {
            throw new \Illuminate\Validation\ValidationException(
                Validator::make([], []),
                'Se encontraron ' . count($errors) . ' registros con errores',
                $errors
            );
        }

        return $cleanData;
    }

    /**
     * Extract valid model data
     */
    protected function extractModelData(array $arrayArgs, Model $model = null): array
    {
        if ($this->verifiers->arrayType($arrayArgs) !== 'associative') {
            return [];
        }

        $model = $model ?? $this->model;
        $validArgs = [];

        foreach ($arrayArgs as $column => $value) {
            // Check if column exists in table
            if (!Schema::hasColumn($model->getTable(), $column)) {
                continue;
            }

            // Handle JSON columns
            $columnType = Schema::getColumnType($model->getTable(), $column);
            if ($columnType === 'json') {
                $value = json_encode($value);
            }

            $validArgs[$column] = $value;
        }

        // Add user ID if required
        if ($this->useUsuarioId && isset(request()->userData['id'])) {
            $validArgs[$this->createdByColumn] = request()->userData['id'];
        }

        // Apply default values
        $validArgs = $this->verifiers->agregarValoresDefault($validArgs, $this->valoresDefault);

        // Remove ignored arguments
        if (!empty($this->ignoreArgs)) {
            foreach ($this->ignoreArgs as $ignoreArg) {
                unset($validArgs[$ignoreArg]);
            }
        }

        return $validArgs;
    }

    /**
     * Validate array arguments
     */
    protected function checkArrayArgs($arrayArgs, ?Model $model = null): array
    {
        return $this->verifiers->checkArrayArgs($arrayArgs, $model ?? $this->model);
    }

    /**
     * Validate user data for bulk operations
     */
    protected function validateUserDataForBulk($userData): int
    {
        if (!is_array($userData) || !isset($userData['id'])) {
            throw new \Exception('Problema con los datos de sesión de usuario');
        }

        return (int) $userData['id'];
    }

    /**
     * Additional validation before creation (override in child classes)
     */
    protected function beforeCreateValidation(array $data): array
    {
        return $data;
    }

    /**
     * Additional validation before update (override in child classes)
     */
    protected function beforeUpdateValidation($id, array $data): array
    {
        return $data;
    }

    /**
     * Additional validation before delete (override in child classes)
     */
    protected function beforeDeleteValidation($id): void
    {
        // Override in child classes if needed
    }

    /**
     * Convert validator errors to string
     */
    protected function failsToString($validator): string
    {
        return "Se encontraron uno o más errores: " . implode(' ', $validator->errors()->all());
    }
}