<?php


namespace App\Repository;


use Carbon\Carbon;
use Illuminate\Container\Container as Application;
use Illuminate\Support\Facades\Cache;
use Prettus\Repository\Eloquent\BaseRepository;

abstract class AbstractRepository extends BaseRepository
{

    public $keyCache;

    public $tagsCache;

    public $expireCache;

    /**
     * @return array
     */
    public function getFillable()
    {
        return $this->model->getFillable();
    }


    /**
     * @param array $ids
     * @return mixed
     */
    public function bulkDelete(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * @param array $data
     * @return bool
     */
    public function bulkInsert(array $data)
    {
        $now = Carbon::now();

        $prepareData = array_map(function ($value) use ($now) {
            $value['created_at'] = $value['updated_at'] = $now;

            return $value;
        }, $data);

        return $this->model->insert($prepareData);
    }

    /**
     * @param array $values [['id' => '', 'key' => 'value']]
     * @param string $index
     * @return int
     */
    public function bulkUpdate(array $values, $index = 'id')
    {
        $table = $this->model->getTable();
        $ids = $finals = $bindings = $clause = [];
        $cases = '';

        foreach ($values as $i => $value) {
            array_push($ids, $value[$index]);

            if ($index !== 'id') {
                $clause[] = $index . " = '" . $value[$index] . "'";
            }

            foreach (array_keys($value) as $field) {
                if ($field !== $index) {
                    $finals[$field][] = ' WHEN ' . $index . ' = :id' . $i . ' THEN :' . $field . $i . ' ';
                    $bindings[':id' . $i] = $value[$index];
                    $bindings[':' . $field . $i] = $value[$field];
                }
            }
        }

        foreach ($finals as $field => $query) {
            $cases .= $field . ' = (CASE ' . implode('', $query) . ' ELSE ' . $field . ' END), ';
        }

        $query = 'Update ' . $table . ' SET ' . substr($cases, 0, -2) . ', updated_at = :updated_at WHERE ';
        $bindings[':updated_at'] = now();

        if ($index === 'id') {
            $query .= $index . ' in(' . implode(',', $ids) . ')';
        } else {
            $query .= implode(' OR ', $clause);
        }

        return \DB::statement($query, $bindings);
    }


    /**
     * @param string $attribute
     * @param string $value
     * @param array $columns
     * @return mixed
     */
    public function findBy(string $attribute, string $value, array $columns = array('*'))
    {
        return $this->model->where($attribute, '=', $value)->first($columns);
    }


    /**
     * Mass (bulk) insert or update on duplicate for Laravel 4/5
     *
     * insertOrUpdate([
     *   ['id'=>1,'value'=>10],
     *   ['id'=>2,'value'=>60]
     * ]);
     *
     *
     * @param array $rows
     */
    public function insertOrUpdate(array $rows)
    {
        $table = $this->model->getTable();
        $first = reset($rows);

        $columns = implode(
            ',',
            array_map(function ($value) {
                return "$value";
            }, array_keys($first))
        );

        $values = implode(
            ',',
            array_map(function ($row) {
                return '(' . implode(
                    ',',
                    array_map(function ($value) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }, $row)
                ) . ')';
            }, $rows)
        );

        $updates = implode(
            ',',
            array_map(function ($value) {
                return "$value = VALUES($value)";
            }, array_keys($first))
        );

        $sql = "INSERT INTO {$table}({$columns}) VALUES {$values} ON DUPLICATE KEY UPDATE {$updates}";

        return \DB::statement($sql);
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->model->updateOrCreate($attributes, $values);
    }


    /**
     * @param string|null $keyCache
     * @return $this
     */
    public function setKeyCache(string $keyCache = null, $tagCache = null)
    {
        $this->keyCache = $keyCache;
        $this->tagsCache = $tagCache;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getKeyCache()
    {
        return $this->keyCache;
    }

    /**
     * @return mixed
     */
    public function getTagsCache()
    {
        return $this->tagsCache;
    }

    public function setExpireCache($expireCache = null)
    {
        if ($expireCache) {
            $this->expireCache = $expireCache;
        } else {
            $this->expireCache = config('fa_cache_keys.default_cache_time');
        }
        return $this;
    }


    /**
     * @return mixed
     */
    public function getExpireCache()
    {
        return $this->expireCache;
    }


    /**
     * @return mixed|null
     */
    public function retrievingItems()
    {
        if ($this->getKeyCache()) {
            if ($this->getTagsCache()) {
                $object = Cache::tags($this->getTagsCache())->get($this->getKeyCache(), null);
            } else {
                $object = Cache::get($this->getKeyCache(), null);
            }
            return $object;
        }
        return null;
    }

    /**
     * @return bool
     */
    public function removeItemForget()
    {
        if ($this->getKeyCache()) {
            Cache::forget($this->getKeyCache());
        }
        return true;
    }


    /**
     * @param $object
     * @param null $expires
     * @return bool
     */
    public function storingItems($object, $expires = null)
    {
        $this->setExpireCache($expires);
        if ($this->getKeyCache()) {
            Cache::put($this->getKeyCache(), $object, $this->getExpireCache());
        }
        return true;
    }


    /**
     * @param null $object
     * @return bool
     */
    public function storingItemsForever($object = null)
    {
        if ($this->getKeyCache() && !empty($object)) {
            Cache::forever($this->getKeyCache(), $object);
        }
        return true;
    }


    /**
     * Trigger method calls to the model
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $this->applyCriteria();
        $this->applyScope();

        return call_user_func_array([$this->model, $method], $arguments);
    }

    /**
     * @param $storeId
     * @param $field
     * @param $value
     *
     * @return mixed
     */
    public function deleteAllBy($storeId, $field, $value)
    {
        $delete = $this->model->where($field, $value)->delete();
        return $delete;
    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param null|int $limit
     * @param array    $columns
     * @param string   $method
     *
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'], $method = "paginate")
    {
        $this->applyCriteria();
        $this->applyScope();
        $limit = is_null($limit) ? config('repository.pagination.limit', 15) : $limit;
        $results = $this->model->{$method}($limit, $columns);
        // $results->appends(app('request')->query());
        $this->resetModel();

        return $this->parserResult($results);
    }

    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->find($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }
}
