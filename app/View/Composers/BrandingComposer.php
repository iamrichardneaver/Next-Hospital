<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Services\BrandingService;

class BrandingComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('hospitalBranding', BrandingService::getBranding());
    }
}

