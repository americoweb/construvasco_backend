<?php

namespace App\Http\Requests;

/**
 * Validação dos campos extendidos do briefing (JSON briefing_data).
 * Baseado em questionários de design arquitectónico (site, programa, MEP, sustentabilidade).
 */
class BriefingDataRules
{
    /** @return array<string, mixed> */
    public static function rules(): array
    {
        $nullableString = 'nullable|string|max:500';
        $nullableText = 'nullable|string|max:5000';
        $nullableNum = 'nullable|numeric|min:0';
        $nullableInt = 'nullable|integer|min:0|max:9999';
        $nullableBool = 'nullable|boolean';

        $keys = [
            // Terreno / site (todos os tipos)
            'provincia', 'bairro', 'coordenadas_gps', 'topografia', 'orientacao_solar',
            'acesso_veiculos', 'largura_via_acesso_m', 'rede_agua', 'rede_esgoto', 'rede_energia',
            'internet_fibra', 'edificio_existente', 'ano_edificio_existente', 'estado_edificio_existente',
            'area_demolir_m2', 'restricoes_urbanisticas', 'licenca_anterior', 'vizinhanca_notas',
            'inundacao_risco', 'vento_exposicao',
            // Residencial — programa e divisões
            'num_casas_banho', 'num_suites', 'num_salas_estar', 'num_cozinhas', 'area_cozinha_m2',
            'area_sala_m2', 'num_dependentes', 'idade_ocupantes', 'acessibilidade_mobilidade',
            'garagem_vagas', 'garagem_tipo', 'patio_quintal', 'area_quintal_m2', 'piscina',
            'churrasqueira_area_lazer', 'lavandaria', 'escritorio_casa', 'quarto_visitas',
            'arrumos', 'adega', 'altura_pe_direito_m', 'altura_pe_direito_piso2_m',
            'subsolo', 'cave', 'terraco', 'varanda', 'varanda_area_m2', 'home_office',
            'animais_estimacao', 'estilo_vida_notas', 'frequencia_recepcoes',
            // Residencial — cozinha / casas de banho
            'cozinha_tipo', 'cozinha_ilha', 'cozinha_aberta_sala', 'casas_banho_suite',
            'banheira_duche', 'preferencia_eletrodomesticos',
            // Comercial
            'tipo_negocio', 'nome_marca', 'num_funcionarios', 'num_visitantes_dia',
            'area_util_m2', 'area_atendimento_m2', 'area_armazem_loja_m2', 'fluxo_publico',
            'horario_funcionamento', 'estacionamento_clientes', 'estacionamento_funcionarios',
            'fachada_comercial', 'montra_vitrine', 'sinalizacao_exterior', 'acessibilidade_publica',
            'divisoes_comerciais', 'copa_funcionarios', 'sanitarios_publicos', 'carga_descarga',
            'ar_condicionado_tipo', 'sistema_som_ambiente',
            // Industrial / armazém
            'tipo_operacao_industrial', 'altura_armazem_m', 'area_armazenagem_m2',
            'docas_carga', 'num_docas', 'patio_maniobras', 'carga_pesada_toneladas',
            'ponte_rolante', 'ventilacao_industrial', 'sistema_incendio_industrial',
            'energia_trifasica', 'gerador_emergencia', 'escritorios_planta_m2', 'vestiarios',
            'refeitorio_industrial', 'area_producao_m2',
            // Remodelação
            'tipo_intervencao', 'habitacao_durante_obra', 'area_ampliar_m2', 'paredes_demolir',
            'instalacoes_a_substituir', 'fachada_alterar', 'prazo_obra_urgente',
            // Misto
            'pisos_residenciais', 'pisos_comerciais', 'area_comercial_m2', 'area_residencial_m2',
            'uso_comercial_piso_0', 'separacao_acustica',
            // Estrutura e engenharia
            'sistema_estrutural', 'tipo_fundacao', 'tipo_cobertura', 'laje_ou_telha',
            'resistencia_sismica', 'parede_exterior', 'isolamento_termico', 'isolamento_acustico',
            // MEP / instalações
            'agua_quente', 'solar_termico', 'ar_condicionado', 'aquecimento', 'ventilacao_mecanica',
            'gerador_backup', 'painel_solar_fotovoltaico', 'capacidade_kva',
            // Sustentabilidade
            'certificacao_verde', 'eficiencia_energetica_meta', 'materiais_sustentaveis',
            'captacao_agua_chuva', 'fossa_septica', 'esgoto_municipal',
            // Acabamentos / design
            'materiais_preferidos', 'materiais_evitar', 'tipo_pavimento', 'tipo_revestimento',
            'cor_predominante', 'iluminacao_preferencia', 'mobiliario_incluido',
            // Exterior / paisagismo
            'muro_perimetro', 'portao_entrada', 'jardim_paisagismo', 'iluminacao_exterior',
            'estacionamento_exterior_vagas',
            // Legal / projecto
            'requer_aprovacao_camara', 'topografia_levantada', 'estudo_solo', 'projecto_aprovado_anterior',
            'faseamento_obra', 'data_inicio_desejada', 'financiamento', 'urgencia_obra',
            'contacto_obra_local', 'inspiracao_descricao',
        ];

        $rules = ['briefing_data' => 'nullable|array'];
        foreach ($keys as $key) {
            if (str_ends_with($key, '_notas') || in_array($key, [
                'divisoes_comerciais', 'instalacoes_a_substituir', 'restricoes_urbanisticas',
                'vizinhanca_notas', 'estilo_vida_notas', 'inspiracao_descricao', 'preferencia_eletrodomesticos',
            ], true)) {
                $rules["briefing_data.{$key}"] = $nullableText;
            } elseif (in_array($key, [
                'edificio_existente', 'subsolo', 'cave', 'terraco', 'varanda', 'piscina',
                'patio_quintal', 'churrasqueira_area_lazer', 'lavandaria', 'escritorio_casa',
                'quarto_visitas', 'home_office', 'cozinha_ilha', 'cozinha_aberta_sala',
                'docas_carga', 'ponte_rolante', 'habitacao_durante_obra', 'fachada_alterar',
                'requer_aprovacao_camara', 'topografia_levantada', 'estudo_solo', 'painel_solar_fotovoltaico',
                'gerador_backup', 'gerador_emergencia', 'esgoto_municipal', 'fossa_septica',
                'captacao_agua_chuva', 'mobiliario_incluido', 'muro_perimetro', 'inundacao_risco',
                'rede_agua', 'rede_esgoto', 'rede_energia', 'internet_fibra', 'licenca_anterior',
                'uso_comercial_piso_0', 'separacao_acustica', 'montra_vitrine', 'carga_descarga',
                'sanitarios_publicos', 'copa_funcionarios', 'vestiarios', 'refeitorio_industrial',
                'energia_trifasica', 'ventilacao_industrial', 'prazo_obra_urgente', 'urgencia_obra',
                'animais_estimacao', 'casas_banho_suite', 'banheira_duche', 'jardim_paisagismo',
                'iluminacao_exterior', 'sinalizacao_exterior', 'acessibilidade_publica',
                'acessibilidade_mobilidade', 'fachada_comercial',
            ], true)) {
                $rules["briefing_data.{$key}"] = $nullableBool;
            } elseif (str_contains($key, '_m2') || str_contains($key, '_m') || str_contains($key, '_mt')
                || str_contains($key, '_kva') || str_contains($key, '_toneladas')) {
                $rules["briefing_data.{$key}"] = $nullableNum;
            } elseif (str_starts_with($key, 'num_') || in_array($key, [
                'pisos_residenciais', 'pisos_comerciais', 'garagem_vagas', 'num_docas',
                'estacionamento_clientes', 'estacionamento_funcionarios', 'estacionamento_exterior_vagas',
                'ano_edificio_existente',
            ], true)) {
                $rules["briefing_data.{$key}"] = $nullableInt;
            } else {
                $rules["briefing_data.{$key}"] = $nullableString;
            }
        }

        return $rules;
    }
}
