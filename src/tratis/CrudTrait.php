<?php

namespace Fredpeal\BakaHttp\Traits;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

trait CrudTrait
{
    public $model;

    /**
     * index Function
     */
    public function index(Request $request)
    {
        $request = $request->toArray();
        $data = $this->search($request);
        return response()->json($data);
    }

    /**
     * show function
     *
     */
    public function show(Request $request, $id)
    {
        $request = $request->toArray();
        $model = $this->model::find($id);
        $data = $model->toArray();
        $eagerArray = key_exists('eager', $request) ? $request['eager'] : [];
        foreach ($eagerArray as $eager) {
            $data[$eager] = $model->$eager;
        }
        return response()->json($data);
    }

    /**
     * search function
     *
     * @param  mixed $request
     * @return
     */
    public function search(array $request)
    {
        $model = $this->model::query();

        #Pagination Vars

        $perPage = key_exists('perPage', $request) ? $request['perPage'] : 5;
        $page = key_exists('page', $request) ? $request['page'] : 1;

        if (key_exists('q', $request)) {
            foreach ($request['q'] as $key) {
                $q = json_decode($key, true);
                $this->model = $this->conditions($model, $q);
            }
        }
        $totalResult = $this->model->count();
        $lastPage = ceil($totalResult / intval($perPage));
        if (array_key_exists('fields', $request)) {
            $fields = explode(',', $request['fields']);
            $data = $this->model->orderBy('id', 'desc')
                ->offset(intval($perPage) * $page)
                ->limit($perPage)
                ->get($fields);
        } else {
            $data = $this->model->orderBy('id', 'desc')
                ->offset(intval($perPage) * $page)
                ->limit($perPage)
                ->get();
        }

        if (key_exists('eager', $request)) {
            $eager = $request['eager'];
            $data->load($eager);
        }

        $data = $data->toArray();

        /**
         * paginate data
         */
        $data = collect($data);
        $result = [
            'total' => $totalResult,
            'last_page' => $lastPage,
            'current_page' => $page,
            'data' => $data,
        ];
        return $result;
    }

    /**
     *  store function
     *  @var $request Request
     */
    public function store(Request $request)
    {
        $dataStore = $request->toArray();
        $data = $this->model::create($dataStore);
        return response()->json($data);
    }

    /**
     * update function
     * @var $request Request
     * $id integer
     */
    public function update(Request $request, $id)
    {
        $data = $request->toArray();
        if (array_key_exists('_url', $data)) {
            unset($data['_url']);
        }
        $this->model::where('id', '=', $id)
            ->update($data);
        $data = $this->model::find($id);
        return response()->json($data);
    }

    /**
     * conditions function
     * $model Model,
     * $condition string,
     * $field string,
     * $value string
     */
    public function conditions($model, array $array)
    {
        switch ($array['condition']) {
            case 'where':
                $model->when($array, function ($q, $array) {
                    return $q->where($array['field'], $array['operator'], $array['value']);
                });
                break;
            case 'orWhere':
                $model->orWhere($field, $operator, $value);
                break;
            case 'between':
                $model->when($array, function ($q, $array) {
                    return $q->whereBetween($array['field'], explode('|', $array['value']));
                });
                break;
            case 'whereIn':
                $model->when($array, function ($q, $array) {
                    return $q->whereIn($array['field'], explode(',', $array['value']));
                });
                break;
            default:
                // code...
                break;
        }
        return $model;
    }

    public function destroy($id)
    {
        return $this->model::destroy([$id]);
    }
}
