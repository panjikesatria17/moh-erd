<?php

namespace App\Providers;

use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Policies\DeliveryPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseRequestPolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // On production/staging, ignore accidentally deployed public/hot dev marker.
        if (! App::environment('local')) {
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }

        Blade::directive('rupiah', function ($expression) {
            return "<?php \$__rupiahValue = {$expression}; echo \$__rupiahValue === null || \$__rupiahValue === '' ? '-' : 'Rp&nbsp;'.rtrim(rtrim(number_format((float) \$__rupiahValue, 2, ',', '.'), '0'), ','); ?>";
        });

        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(Delivery::class, DeliveryPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }
}
