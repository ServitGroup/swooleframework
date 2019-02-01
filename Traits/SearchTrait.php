<?php
namespace Servit\Restsrv\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait SearchableTrait {

    /**
     * Scope a query to searchable.
     * Credit: https://medium.com/@brarear/%E0%B9%80%E0%B8%9A%E0%B8%B7%E0%B9%88%E0%B8%AD%E0%B9%84%E0%B8%AB%E0%B8%A1%E0%B8%81%E0%B8%B1%E0%B8%9A-where-%E0%B9%81%E0%B8%A5%E0%B8%B0-orwhere-%E0%B8%97%E0%B8%B5%E0%B9%88%E0%B8%A1%E0%B8%B5%E0%B8%A1%E0%B8%AB%E0%B8%B2%E0%B8%A8%E0%B8%B2%E0%B8%A5%E0%B9%80%E0%B8%A1%E0%B8%B7%E0%B9%88%E0%B8%AD%E0%B9%83%E0%B8%8A%E0%B9%89%E0%B8%87%E0%B8%B2%E0%B8%99-eloquent-1f7fd7c6da88
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $keyword
     * @param null|string|array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch(Builder $builder, $keyword, $filters = null)
    {
        $fields = array();
        switch ($filters) {
            case null:
            $fields = $this->fillable;
            break;
            case is_string($filters):
            if ($filters == '*') {
                $fields = Schema::getColumnListing($this->table);
            } else {
                array_push($fields, $filters);
            }
            break;
            case is_array($filters):
            $fields = $filters;
            break;
            default:
            $fields = $this->fillable;
        }
        if (!empty($keyword) || !is_null($keyword)) {
            $builder->where(function ($builder) use ($keyword, $fields) {
                foreach ($fields as $key => $field) {
                    if ($key == 0) {
                        $builder->where($field, "LIKE","%$keyword%");
                    } else {
                        $builder->orWhere($field, "LIKE", "%$keyword%");
                    }
                }
            });
        }
        return $builder;
    }
    
}