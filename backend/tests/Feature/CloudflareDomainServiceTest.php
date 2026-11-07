<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\CloudflareDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudflareDomainServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name_ar' => 'باقة VIP',
            'price_monthly' => 1000,
            'price_yearly' => 10000,
            'max_clinics' => 5,
            'allow_custom_domain' => true,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'د. سامح فهمي',
            'specialty_key' => 'dermatology',
            'plan_id' => $plan->id,
            'status' => 'active',
            'subdomain' => 'dr-sameh',
            'public_booking_api_key' => 'pbk_cf_test_1',
        ]);
    }

    /**
     * Test registerCustomHostname in mock environment sets pending_cname and fallback target.
     */
    public function test_register_custom_hostname_mock_fallback(): void
    {
        config([
            'services.cloudflare.zone_id' => null,
            'services.cloudflare.api_token' => null,
            'services.cloudflare.fallback_origin' => 'cname.platform.com',
        ]);

        $service = app(CloudflareDomainService::class);
        $result = $service->registerCustomHostname($this->tenant, 'drsamehderma.com');

        $this->assertTrue($result['success']);
        $this->assertEquals('pending_cname', $result['status']);
        $this->assertEquals('cname.platform.com', $result['cname_target']);

        $this->tenant->refresh();
        $this->assertEquals('drsamehderma.com', $this->tenant->custom_domain);
        $this->assertEquals('pending_cname', $this->tenant->domain_status);
        $this->assertNotNull($this->tenant->cloudflare_hostname_id);
    }

    /**
     * Test checkHostnameStatus returns current tenant domain status.
     */
    public function test_check_hostname_status(): void
    {
        $this->tenant->update([
            'custom_domain' => 'mycustomclinic.com',
            'domain_status' => 'active',
        ]);

        $service = app(CloudflareDomainService::class);
        $status = $service->checkHostnameStatus($this->tenant);

        $this->assertEquals('active', $status['status']);
        $this->assertEquals('mycustomclinic.com', $status['custom_domain']);
    }
}
