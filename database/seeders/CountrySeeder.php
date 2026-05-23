<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::updateOrCreate(
            ['iso_code' => 'PK'],
            [
                'name' => 'Pakistan',
                'country_code' => '+92',
                'currency' => 'Pakistani Rupee',
                'currency_code' => 'PKR',
                'code_prepend_or_postpend' => 'prepend',
                'thousands_separator' => ',',
                'hundreds_separator' => ',',
                'decimal_values' => 0,
                'decimal_separator' => '.',
                'is_default' => true,
                'is_fallback' => false,
                'timezone' => 'Asia/Karachi',
                'sms_gateway' => config('sms.default_gateway', 'onpremise'),
                'conversion_rate' => 1.0,
                'conversion_markup_percent' => 0.0,
                'is_active' => true,
            ],
        );

        Country::updateOrCreate(
            ['iso_code' => 'US'],
            [
                'name' => 'United States',
                'country_code' => '+1',
                'currency' => 'US Dollar',
                'currency_code' => 'USD',
                'code_prepend_or_postpend' => 'prepend',
                'thousands_separator' => ',',
                'hundreds_separator' => ',',
                'decimal_values' => 2,
                'decimal_separator' => '.',
                'is_default' => false,
                'is_fallback' => true,
                'timezone' => 'America/New_York',
                // Observe-only — no SMS gateway means users from US cannot
                // sign up / log in. Admin can change this later.
                'sms_gateway' => null,
                // 1 PKR ≈ 0.0036 USD; markup applied separately at runtime.
                'conversion_rate' => 0.0036,
                'conversion_markup_percent' => 30.0,
                'is_active' => true,
            ],
        );
    }
}
