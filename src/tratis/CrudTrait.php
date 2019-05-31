<?php

namespace Fredpeal\BakaHttp\Traits;

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
        $data = $this->search($request);
        return response()->json($data);
    }

    public function search($request)
    {
        $model = $this->model::query();
        if (key_exists('fields', $request)) {
            foreach ($request['fields'] as $key) {
                $fields = json_decode($key, true);
                $this->model = $this->conditions($model, $fields);
            }
        }

        if (array_key_exists('columns', $request)) {
            $columns = explode(',', $request['columns']);
            $data = $this->model->get($columns);
        } else {
            $data = $this->model->get();
        }

        if (key_exists('eager', $request) || key_exists('rs', $request)) {
            $rs = key_exists('eager', $request) ? $request['eager'] : $request['rs'];
            $data->load($rs);
        }
        return $data;
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
