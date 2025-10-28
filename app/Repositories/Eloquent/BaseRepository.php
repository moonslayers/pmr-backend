<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\BaseRepositoryInterface;
use App\Contracts\Criteria\CriteriaInterface;
use App\Criteria\RelationCriteria;
use App\DTOs\SearchQueryDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;
    protected Builder $query;
    protected array $criteria = [];
    protected bool $withTrashed = false;
    protected array $mainRelations = [];
    protected array $excludedColumnsInSearch = ['id', 'created_at', 'deleted_at', 'created_by', 'updated_at', 'password', 'token'];

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    public function all(array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        return $this->query->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], int $page = 1): LengthAwarePaginator
    {
        $this->applyCriteria();
        return $this->query->paginate($perPage, $columns, 'page', $page);
    }

    public function find($id, array $columns = ['*']): ?Model
    {
        $this->applyCriteria();
        return $this->query->find($id, $columns);
    }

    public function findOrFail($id, array $columns = ['*']): Model
    {
        $this->applyCriteria();
        return $this->query->findOrFail($id, $columns);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update($id, array $data): bool
    {
        $model = $this->model->findOrFail($id);
        return $model->update($data);
    }

    public function delete($id): bool
    {
        $model = $this->model->findOrFail($id);
        return $model->delete();
    }

    public function restore($id): bool
    {
        $model = $this->model->withTrashed()->findOrFail($id);
        return $model->restore();
    }

    public function withTrashed(): self
    {
        $this->withTrashed = true;
        $this->query = $this->model->withTrashed();
        return $this;
    }

    public function with(array $relations): self
    {
        $this->criteria[] = new RelationCriteria($relations);
        return $this;
    }

    public function withCriteria(array $criteria): self
    {
        $this->criteria = array_merge($this->criteria, $criteria);
        return $this;
    }

    public function search(array $params): LengthAwarePaginator
    {
        $searchDTO = new SearchQueryDTO(...$params);

        $this->withTrashed();

        if ($searchDTO->hasRelations()) {
            $this->with($searchDTO->relations);
        }

        return $this->paginate($searchDTO->perPage, $searchDTO->columns, $searchDTO->page);
    }

    public function advancedSearch(array $params): LengthAwarePaginator
    {
        $searchDTO = SearchQueryDTO::fromRequest((object) $params);

        $this->withTrashed();

        // Apply search criteria
        if ($searchDTO->search) {
            $this->criteria[] = new \App\Criteria\SearchCriteria(
                search: $searchDTO->search,
                excludedColumns: $this->excludedColumnsInSearch
            );
        }

        // Apply filter criteria
        if (!empty($searchDTO->filters)) {
            $this->criteria[] = new \App\Criteria\FilterCriteria($searchDTO->filters);
        }

        // Apply sorting criteria
        if ($searchDTO->sort) {
            $this->criteria[] = new \App\Criteria\SortingCriteria($searchDTO->sort);
        }

        // Apply advanced search criteria
        if (!empty($searchDTO->busquedaAvanzada)) {
            $this->criteria[] = new \App\Criteria\AdvancedSearchCriteria(
                $searchDTO->busquedaAvanzada,
                $this->mainRelations
            );
        }

        // Apply relations
        if ($searchDTO->hasRelations()) {
            $this->with($searchDTO->relations);
        }

        return $this->paginate($searchDTO->perPage, $searchDTO->columns, $searchDTO->page);
    }

    public function massiveUpdate(array $data): int
    {
        $updated = 0;
        foreach ($data as $row) {
            if (!isset($row['id'])) {
                continue;
            }

            unset($row['created_at']);
            $row['updated_at'] = now();

            if ($this->model->where('id', $row['id'])->update($row)) {
                $updated++;
            }
        }
        return $updated;
    }

    public function getQuery(): Builder
    {
        $this->applyCriteria();
        return $this->query;
    }

    public function setMainRelations(array $relations): self
    {
        $this->mainRelations = $relations;
        return $this;
    }

    public function setExcludedColumnsInSearch(array $columns): self
    {
        $this->excludedColumnsInSearch = $columns;
        return $this;
    }

    protected function applyCriteria(): void
    {
        foreach ($this->criteria as $criterion) {
            if ($criterion instanceof CriteriaInterface) {
                $this->query = $criterion->apply($this->query);
            }
        }
    }

    protected function resetQuery(): void
    {
        $this->query = $this->withTrashed ? $this->model->withTrashed() : $this->model->newQuery();
    }

    protected function clearCriteria(): self
    {
        $this->criteria = [];
        $this->resetQuery();
        return $this;
    }
}