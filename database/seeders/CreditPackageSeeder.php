<?php

namespace Database\Seeders;

use App\Models\Credits\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Iniciante', 'credits_amount' => 10, 'price_mt' => 500, 'sort_order' => 1, 'description' => 'Pacote inicial'],
            ['name' => 'Padrão', 'credits_amount' => 25, 'price_mt' => 1000, 'sort_order' => 2, 'description' => 'Melhor relação custo/benefício'],
            ['name' => 'Profissional', 'credits_amount' => 60, 'price_mt' => 2000, 'sort_order' => 3, 'description' => 'Para projectos intensivos'],
        ];

        foreach ($packages as $p) {
            CreditPackage::updateOrCreate(
                ['name' => $p['name']],
                array_merge($p, ['is_active' => true])
            );
        }
    }
}
