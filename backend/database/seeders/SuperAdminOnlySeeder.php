<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminOnlySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin Account
        SuperAdmin::firstOrCreate(
            ['email' => 'admin@platform.com'],
            [
                'name' => 'المدير العام للمنصة',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Platform Subscription Plans (Required for creating doctors / subscriptions)
        Plan::firstOrCreate(
            ['name_ar' => 'الباقة الأساسية'],
            [
                'price_monthly' => 300,
                'price_yearly' => 3000,
                'max_clinics' => 1,
                'free_whatsapp_quota_monthly' => 50,
                'free_sms_quota_monthly' => 50,
                'allow_custom_domain' => false,
                'allow_custom_website' => false,
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['name_ar' => 'الباقة المتقدمة'],
            [
                'price_monthly' => 600,
                'price_yearly' => 6000,
                'max_clinics' => 3,
                'free_whatsapp_quota_monthly' => 150,
                'free_sms_quota_monthly' => 100,
                'allow_custom_domain' => true,
                'allow_custom_website' => false,
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['name_ar' => 'باقة VIP الشاملة'],
            [
                'price_monthly' => 1200,
                'price_yearly' => 12000,
                'max_clinics' => -1, // Unlimited
                'free_whatsapp_quota_monthly' => 500,
                'free_sms_quota_monthly' => 250,
                'allow_custom_domain' => true,
                'allow_custom_website' => true,
                'is_active' => true,
            ]
        );
    }
}
