<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScopeByBranch
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if ($user) {
            // Get user's primary branch (first assigned branch)
            $userBranch = $user->branches()->first();
            
            if ($userBranch) {
                // Add branch_id to request for use in controllers
                $request->merge(['user_branch_id' => $userBranch->id]);
                
                // Set a global scope for the current request
                $this->setGlobalBranchScope($userBranch->id);
            }
        }
        
        return $next($request);
    }
    
    /**
     * Set global branch scope for the current request
     */
    private function setGlobalBranchScope($branchId)
    {
        // Store branch ID in a singleton for use in models
        app()->instance('current_branch_id', $branchId);
    }
}
