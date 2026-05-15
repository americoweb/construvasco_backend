<?php

namespace Database\Seeders;

use App\Models\Construction\ProjectTemplate;
use Illuminate\Database\Seeder;

class ProjectTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTemplate('Casa T1/T2 Unifamiliar', 'residencial', 't1-t2', [
            ['Briefing técnico', 'Levantamento e validação do briefing', 3],
            ['Planta Baixa', 'Distribuição de ambientes', 7],
            ['Cortes e Fachadas', 'Documentação gráfica', 10],
            ['Render 3D', 'Visualização arquitectónica', 5],
            ['Documentação Final', 'Entrega ao cliente', 3],
        ]);

        $t3 = $this->seedTemplate('Casa T3+ Unifamiliar', 'residencial', 't3-plus', [
            ['Briefing técnico', null, 3],
            ['Planta Baixa', null, 10],
            ['Cortes e Fachadas', null, 12],
            ['Memorial Descritivo', 'Especificações técnicas', 5],
            ['Render 3D', null, 7],
            ['Documentação Final', null, 4],
        ]);

        $this->seedTemplate('Edifício Multifamiliar', 'comercial', 'multifamiliar', [
            ['Estudo preliminar', 'Viabilidade e volumetria', 10],
            ['Plantas tipo', 'Pavimentos repetíveis', 15],
            ['Fachadas e cortes', null, 12],
            ['Infraestruturas', 'Coordenação básica', 8],
            ['Documentação Final', null, 5],
        ]);
    }

    private function seedTemplate(string $name, string $type, string $tipologia, array $phases): ProjectTemplate
    {
        $template = ProjectTemplate::updateOrCreate(
            ['name' => $name],
            [
                'project_type' => $type,
                'tipologia' => $tipologia,
                'description' => "Template padrão: {$name}",
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        foreach ($phases as $i => [$phaseName, $desc, $days]) {
            $template->phases()->updateOrCreate(
                ['name' => $phaseName, 'project_template_id' => $template->id],
                [
                    'description' => $desc,
                    'order_position' => $i,
                    'estimated_days' => $days,
                    'is_required' => true,
                ]
            );
        }

        return $template;
    }
}
