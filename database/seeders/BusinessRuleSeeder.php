<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessRule;
use App\Models\BusinessRuleVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BusinessRuleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::create(['name' => 'Owner System', 'email' => 'owner@pertashop.com', 'password' => bcrypt('password')]);

        $rulesData = [
            [
                'code' => 'PAYROLL_RATE',
                'name' => 'Tarif Payroll Operator per Liter',
                'category' => 'payroll',
                'data_type' => 'currency',
                'is_system_rule' => true,
                'description' => 'Besaran bonus/gaji insentif per liter penjualan yang diterima operator pertashop.',
                'value' => 200.00,
                'version' => 'BR-PAYROLL-v1.0',
            ],
            [
                'code' => 'LOSS_TOLERANCE',
                'name' => 'Toleransi Susut/Losses Tangki (%)',
                'category' => 'inventory',
                'data_type' => 'percentage',
                'is_system_rule' => true,
                'description' => 'Persentase batas wajar penyusutan volume BBM akibat penguapan/suhu.',
                'value' => 0.50,
                'version' => 'BR-LOSS-v1.0',
            ],
            [
                'code' => 'TOLI_TOLERANCE',
                'name' => 'Toleransi Carry Forward Totalisator (Liter)',
                'category' => 'validation',
                'data_type' => 'decimal',
                'is_system_rule' => true,
                'description' => 'Toleransi selisih totalisator awal Excel dibanding totalisator akhir DB kemarin.',
                'value' => 1.00,
                'version' => 'BR-TOLI-v1.0',
            ],
            [
                'code' => 'MAX_DAILY_VOLUME',
                'name' => 'Kapasitas Harian Maksimum Wajar (Liter)',
                'category' => 'validation',
                'data_type' => 'decimal',
                'is_system_rule' => true,
                'description' => 'Batas atas volume penjualan harian wajar untuk deteksi outlier input.',
                'value' => 15000.00,
                'version' => 'BR-MAXVOL-v1.0',
            ],
            [
                'code' => 'EXTREME_INCOME_MIN',
                'name' => 'Batas Bawah Pendapatan Bersih Normal (Rp)',
                'category' => 'validation',
                'data_type' => 'currency',
                'is_system_rule' => true,
                'description' => 'Batas toleransi kerugian harian maksimum sebelum memicu warning outlier.',
                'value' => -5000000.00,
                'version' => 'BR-INCOME-v1.0',
            ],
            [
                'code' => 'PERLAKUAN_LOSSES_GAIN',
                'name' => 'Perlakuan Losses/Gain pada Omset Operator',
                'category' => 'payroll',
                'data_type' => 'string',
                'is_system_rule' => true,
                'description' => 'Aturan perlakuan losses/gain terhadap omset komisi: losses_only (hanya losses mengurangi, gain diabaikan), losses_dan_gain, atau abaikan_losses_gain.',
                'value' => 'losses_only',
                'version' => 'BR-LOSSGAIN-v1.0',
            ],
        ];

        // Bersihkan versi non-default hasil tes sebelumnya
        BusinessRuleVersion::where('version_code', 'not like', '%v1.0%')->delete();

        foreach ($rulesData as $item) {
            $rule = BusinessRule::updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'data_type' => $item['data_type'],
                    'is_system_rule' => $item['is_system_rule'],
                    'description' => $item['description'],
                ]
            );

            $isNumeric = is_numeric($item['value']);
            $valNumeric = $isNumeric ? $item['value'] : null;
            $valString = is_string($item['value']) && !$isNumeric ? $item['value'] : null;

            $exists = DB::table('business_rule_versions')
                ->where('business_rule_id', $rule->id)
                ->where('version_code', $item['version'])
                ->exists();

            if ($exists) {
                DB::table('business_rule_versions')
                    ->where('business_rule_id', $rule->id)
                    ->where('version_code', $item['version'])
                    ->update([
                        'effective_from'  => '2026-01-01 00:00:00',
                        'effective_until' => null,
                        'value_numeric'   => $valNumeric,
                        'value_string'    => $valString,
                        'is_active'       => true,
                        'updated_at'      => now(),
                    ]);
            } else {
                DB::table('business_rule_versions')->insert([
                    'business_rule_id' => $rule->id,
                    'version_code'     => $item['version'],
                    'value_numeric'    => $valNumeric,
                    'value_string'     => $valString,
                    'effective_from'   => '2026-01-01 00:00:00',
                    'effective_until'  => null,
                    'is_active'        => true,
                    'created_by'       => $user->id,
                    'change_reason'    => 'Initial system default rule version seeding.',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }
}
