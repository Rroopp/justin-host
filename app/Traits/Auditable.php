<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            if ($model->shouldAudit('created')) {
                $model->logAudit('create');
            }
        });

        static::updated(function ($model) {
            if ($model->shouldAudit('updated')) {
                $model->logAudit('update');
            }
        });

        static::deleted(function ($model) {
            if ($model->shouldAudit('deleted')) {
                $model->logAudit('delete');
            }
        });
    }

    public function shouldAudit($event)
    {
        if (property_exists($this, 'auditEvents')) {
            return in_array($event, $this->auditEvents);
        }
        return true;
    }

    public function logAudit($action)
    {
        $auditService = app(AuditService::class);
        $user = Auth::user();
        
        $original = $action === 'update' ? $this->getOriginal() : null;
        $current = $this->getAttributes();
        
        // Filter out sensitive fields or hidden fields if needed
        if (isset($this->hidden)) {
            $original = $original ? array_diff_key($original, array_flip($this->hidden)) : null;
            $current = array_diff_key($current, array_flip($this->hidden));
        }

        // For updates, only log changed fields
        if ($action === 'update') {
            $changes = $this->getChanges();
            $newValues = array_intersect_key($current, $changes);
            $oldValues = array_intersect_key($original, $changes);
            
            // If no changes (e.g. touch), skip
            if (empty($newValues)) {
                return;
            }
        } else {
            $newValues = $current;
            $oldValues = null;
        }

        $className = class_basename($this);
        $module = $this->getAuditModule($className);
        $description = "$action $className #{$this->id}";

        // Customize description if possible
        if (isset($this->product_name)) {
            $description .= " ({$this->product_name})";
        } elseif (isset($this->name)) {
            $description .= " ({$this->name})";
        } elseif (isset($this->full_name)) {
            $description .= " ({$this->full_name})";
        } elseif (isset($this->invoice_number)) {
            $description .= " ({$this->invoice_number})";
        }

        $auditService->log(
            $user, 
            $action, 
            $module, 
            $this->id, 
            $description, 
            get_class($this), 
            $oldValues, 
            $newValues
        );
    }

    protected function getAuditModule($className)
    {
        // Simple mapping based on class name or folder
        // Can be overridden in the model
        if (defined('static::AUDIT_MODULE')) {
            return static::AUDIT_MODULE;
        }

        return strtolower($className);
    }
}
