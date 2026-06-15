<?php

namespace App\Traits;

use App\Services\IdPrefixService;
use Illuminate\Database\Eloquent\Model;

trait HasIdPrefix
{
    /**
     * Boot the trait
     */
    protected static function bootHasIdPrefix()
    {
        static::creating(function (Model $model) {
            $entityType = $model->getEntityType();
            if ($entityType) {
                $idField = $model->getIdField();
                if (empty($model->{$idField})) {
                    $model->{$idField} = app(IdPrefixService::class)->generateId($entityType);
                }
            }
        });
    }

    /**
     * Get the entity type for ID generation
     * Override this method in your model if needed
     */
    protected function getEntityType()
    {
        return $this->entityType ?? strtolower(class_basename($this));
    }

    /**
     * Get the field name where the generated ID should be stored
     * Override this method in your model if needed
     */
    protected function getIdField()
    {
        return $this->idField ?? 'id';
    }

    /**
     * Generate ID for this model
     */
    public function generateId()
    {
        $entityType = $this->getEntityType();
        if (!$entityType) {
            return null;
        }
        return app(IdPrefixService::class)->generateId($entityType);
    }

    /**
     * Get the next ID that would be generated
     */
    public function getNextId()
    {
        $entityType = $this->getEntityType();
        if (!$entityType) {
            return null;
        }
        return app(IdPrefixService::class)->testIdGeneration($entityType)['test_id'];
    }
}
