<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditCreated();
        });

        static::updated(function ($model) {
            $model->auditUpdated();
        });

        static::deleted(function ($model) {
            $model->auditDeleted();
        });
    }

    /**
     * Log model creation
     */
    protected function auditCreated()
    {
        $auditService = app(AuditService::class);
        $user = Auth::user();
        
        $auditService->log(
            $user,
            'create',
            $this->getAuditModule(),
            $this->getKey(),
            $this->getAuditDescription('created'),
            get_class($this),
            null,
            $this->getAuditableAttributes()
        );
    }

    /**
     * Log model update
     */
    protected function auditUpdated()
    {
        $auditService = app(AuditService::class);
        $user = Auth::user();
        
        // Get changed attributes
        $dirty = $this->getDirty();
        if (empty($dirty)) {
            return; // No changes
        }

        $original = [];
        $new = [];
        
        foreach ($dirty as $key => $value) {
            $original[$key] = $this->getOriginal($key);
            $new[$key] = $value;
        }

        $auditService->log(
            $user,
            'update',
            $this->getAuditModule(),
            $this->getKey(),
            $this->getAuditDescription('updated'),
            get_class($this),
            $original,
            $new
        );
    }

    /**
     * Log model deletion
     */
    protected function auditDeleted()
    {
        $auditService = app(AuditService::class);
        $user = Auth::user();
        
        $auditService->log(
            $user,
            'delete',
            $this->getAuditModule(),
            $this->getKey(),
            $this->getAuditDescription('deleted'),
            get_class($this),
            $this->getAuditableAttributes(),
            null
        );
    }

    /**
     * Get module name for audit log
     */
    protected function getAuditModule(): string
    {
        // Default to table name
        return $this->getTable();
    }

    /**
     * Get human-readable description
     */
    protected function getAuditDescription(string $action): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getAuditIdentifier();
        
        return ucfirst($action) . " {$modelName}: {$identifier}";
    }

    /**
     * Get identifier for the model (name, title, code, etc.)
     */
    protected function getAuditIdentifier(): string
    {
        // Try common identifier fields
        $fields = ['name', 'title', 'product_name', 'full_name', 'code', 'number'];
        
        foreach ($fields as $field) {
            if (isset($this->$field)) {
                return $this->$field;
            }
        }
        
        return "ID: {$this->getKey()}";
    }

    /**
     * Get attributes to log (exclude sensitive fields)
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        
        // Remove sensitive fields
        $exclude = ['password', 'remember_token', 'api_token'];
        
        foreach ($exclude as $field) {
            unset($attributes[$field]);
        }
        
        return $attributes;
    }
}
