<?php
namespace Servit\Restsrv\Model;

use Illuminate\Database\Eloquent\Model;
use Servit\Restsrv\Libs\Request;

class BaseModel extends Model
{

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            //dump('creating');
            $input = Request::getInstance();
            // dump($input->posts);
            $columns = $model->getTableColumns();
            // foreach ($columns as $key => $value) {
            //     if ($value == 'created_by') {
            //         isset($input->user) ? $model->created_by = $input->user->username : null;
            //     }
            //     if ($value == 'updated_by') {
            //         isset($input->user) ? $model->updated_by = $input->user->username : null;
            //     }
            // }

            if ($model->hasCreatedBy) {
                isset($input->user) ? $model->created_by = $input->user->username : null;
            }
            if ($model->hasUpdatedBy) {
                isset($input->user) ? $model->updated_by = $input->user->username : null;
            }
        });

        static::created(function ($model) {
            //dump('created');
        });
        static::updating(function ($model) {
            //dump('updating');
            $input = Request::getInstance();
            // $columns = $model->getTableColumns();
            // foreach ($columns as $key => $value) {
            // if ($value == 'updated_by') {
            if ($model->hasUpdateBy) {
                isset($input->user) ? $model->updated_by = $input->user->username : null;
            }
            // }
            // }
        });
        static::updated(function ($model) {
            //dump('updated');
        });
        static::saving(function ($model) {
            //dump('saving');
        });
        static::saved(function ($model) {
            //dump('saved');
        });
        static::loaded(function ($model) {
            //dump('loaded');
        });
    }

    public function newFromBuilder($attributes = array(), $connection = null)
    {
        $instance = parent::newFromBuilder($attributes);
        $instance->fireModelEvent('loaded');
        return $instance;
    }

    public static function loaded($callback, $priority = 0)
    {
        static::registerModelEvent('loaded', $callback, $priority);
    }

    //--- public function in model want start getCapitalname---
    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    /**
     * Reload attributes of model instance
     * @return void
     */
    public function reload()
    {
        if (!$this->exists) {
            return;
        }
        $model = static::find($this->getKey());
        $this->attributes = $model->getAttributes();
        $this->syncOriginal();
    }

}
