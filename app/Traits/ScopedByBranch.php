<?php

namespace App\Traits;

trait ScopedByBranch
{
    /**
     * Boot the trait and add global scope
     */
    protected static function bootScopedByBranch()
    {
        static::addGlobalScope('branch', function ($builder) {
            $branchId = app('current_branch_id');
            
            if ($branchId && static::hasBranchColumn()) {
                $builder->where('branch_id', $branchId);
            }
        });
    }
    
    /**
     * Check if the model has a branch_id column
     */
    protected static function hasBranchColumn()
    {
        return in_array('branch_id', (new static)->getFillable()) || 
               in_array('branch_id', (new static)->getGuarded()) ||
               (new static)->getConnection()->getSchemaBuilder()->hasColumn((new static)->getTable(), 'branch_id');
    }
    
    /**
     * Get query without branch scope (for admin purposes)
     */
    public function scopeAllBranches($query)
    {
        return $query->withoutGlobalScope('branch');
    }
    
    /**
     * Get query for specific branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->withoutGlobalScope('branch')->where('branch_id', $branchId);
    }
}
