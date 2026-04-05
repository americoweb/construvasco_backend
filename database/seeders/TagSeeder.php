<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\Tag;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Material Tags
            ['name' => 'Papel 115gsm', 'slug' => 'papel-115gsm', 'color' => '#E5E7EB'],
            ['name' => 'Papel 170gsm', 'slug' => 'papel-170gsm', 'color' => '#D1D5DB'],
            ['name' => 'Papel 250gsm', 'slug' => 'papel-250gsm', 'color' => '#9CA3AF'],
            ['name' => 'Papel 350gsm', 'slug' => 'papel-350gsm', 'color' => '#6B7280'],
            ['name' => 'PVC', 'slug' => 'pvc', 'color' => '#4B5563'],
            ['name' => 'Vinil', 'slug' => 'vinil', 'color' => '#374151'],
            ['name' => 'Correx', 'slug' => 'correx', 'color' => '#1F2937'],
            ['name' => 'ABS', 'slug' => 'abs', 'color' => '#111827'],
            ['name' => 'Alumínio', 'slug' => 'aluminio', 'color' => '#C0C0C0'],
            ['name' => 'Bambu', 'slug' => 'bambu', 'color' => '#10B981'],
            
            // Print Method Tags
            ['name' => 'Impressão Digital', 'slug' => 'impressao-digital', 'color' => '#3B82F6'],
            ['name' => 'Offset', 'slug' => 'offset', 'color' => '#2563EB'],
            ['name' => 'Serigrafia', 'slug' => 'serigrafia', 'color' => '#1D4ED8'],
            ['name' => 'Sublimação', 'slug' => 'sublimacao', 'color' => '#1E40AF'],
            ['name' => 'Bordado', 'slug' => 'bordado', 'color' => '#9333EA'],
            ['name' => 'Laminação', 'slug' => 'laminacao', 'color' => '#F59E0B'],
            ['name' => 'Acabamento Brilhante', 'slug' => 'acabamento-brilhante', 'color' => '#F97316'],
            ['name' => 'Acabamento Fosco', 'slug' => 'acabamento-fosco', 'color' => '#EA580C'],
            
            // Size/Format Tags
            ['name' => 'Formato A6', 'slug' => 'formato-a6', 'color' => '#8B5CF6'],
            ['name' => 'Formato A5', 'slug' => 'formato-a5', 'color' => '#7C3AED'],
            ['name' => 'Formato A4', 'slug' => 'formato-a4', 'color' => '#6D28D9'],
            ['name' => 'Formato A3', 'slug' => 'formato-a3', 'color' => '#5B21B6'],
            ['name' => 'Formato A2', 'slug' => 'formato-a2', 'color' => '#4C1D95'],
            ['name' => 'Formato A1', 'slug' => 'formato-a1', 'color' => '#3B1F7A'],
            ['name' => 'Formato DL', 'slug' => 'formato-dl', 'color' => '#6366F1'],
            ['name' => 'Formato Personalizado', 'slug' => 'formato-personalizado', 'color' => '#4F46E5'],
            
            // Usage Tags
            ['name' => 'Uso Interior', 'slug' => 'uso-interior', 'color' => '#10B981'],
            ['name' => 'Uso Exterior', 'slug' => 'uso-exterior', 'color' => '#059669'],
            ['name' => 'Uso Temporário', 'slug' => 'uso-temporario', 'color' => '#047857'],
            ['name' => 'Uso Permanente', 'slug' => 'uso-permanente', 'color' => '#065F46'],
            ['name' => 'Evento', 'slug' => 'evento', 'color' => '#EC4899'],
            ['name' => 'Conferência', 'slug' => 'conferencia', 'color' => '#DB2777'],
            ['name' => 'Exposição', 'slug' => 'exposicao', 'color' => '#BE185D'],
            ['name' => 'Promoção', 'slug' => 'promocao', 'color' => '#9F1239'],
            ['name' => 'Escritório', 'slug' => 'escritorio', 'color' => '#06B6D4'],
            ['name' => 'Loja', 'slug' => 'loja', 'color' => '#0891B2'],
            ['name' => 'Restaurante', 'slug' => 'restaurante', 'color' => '#0E7490'],
            
            // Lead Time Tags (CRITICAL for Mozambique)
            ['name' => 'Stock Imediato', 'slug' => 'stock-imediato', 'color' => '#10B981'],
            ['name' => 'Produção Rápida', 'slug' => 'producao-rapida', 'color' => '#F59E0B'],
            ['name' => 'Produção Normal', 'slug' => 'producao-normal', 'color' => '#F97316'],
            ['name' => 'Importação', 'slug' => 'importacao', 'color' => '#EF4444'],
            
            // Budget Tags
            ['name' => 'Económico', 'slug' => 'economico', 'color' => '#10B981'],
            ['name' => 'Médio', 'slug' => 'medio', 'color' => '#F59E0B'],
            ['name' => 'Premium', 'slug' => 'premium', 'color' => '#EF4444'],
            
            // Quantity Tags
            ['name' => 'Ordem Pequena', 'slug' => 'ordem-pequena', 'color' => '#8B5CF6'],
            ['name' => 'Ordem Média', 'slug' => 'ordem-media', 'color' => '#6366F1'],
            ['name' => 'Ordem Grande', 'slug' => 'ordem-grande', 'color' => '#4F46E5'],
            
            // Additional useful tags
            ['name' => 'Ecológico', 'slug' => 'ecologico', 'color' => '#10B981'],
            ['name' => 'Tecnologia', 'slug' => 'tecnologia', 'color' => '#3B82F6'],
            ['name' => 'Destaque', 'slug' => 'destaque', 'color' => '#EC4899'],
        ];

        foreach ($tags as $tagData) {
            // Check if tag already exists by slug
            $tag = Tag::where('slug', $tagData['slug'])->first();
            if (!$tag) {
                $tagData['uuid'] = Str::uuid();
                Tag::create($tagData);
            } else {
                // Update existing tag
                $tag->update($tagData);
            }
        }
    }
}
