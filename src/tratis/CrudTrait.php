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
        foreach($request['eager'] as $eager){
            $data[$eager] = $model->$eager;
        }
        return response()->json($data);
    }

    public function search($request)
    {
        $model = $this->model::query();
        if (key_exists('q', $request)) {
            foreach ($request['q'] as $key) {
                $q = json_decode($key, true);
                $this->model = $this->conditions($model, $q);
            }
        }

        if (array_key_exists('fields', $request)) {
            $fields = explode(',', $request['fields']);
            $data = $this->model->get($fields);
        } else {
            $data = $this->model->get();
        }
        
        
        if (key_exists('eager', $request)) {
            $eager = $request['eager'];
            $data->load($eager);
        }
        $rs = key_exists('rs' , $request) ? $request['rs'] : [];
        
        foreach($rs as $r){
            
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
            case 'between':
                $model->when($array, function($q, $array){
                    return $q->whereBetween($array['field'], explode('|',$array['value']));
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
