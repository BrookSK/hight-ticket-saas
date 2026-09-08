<?php

declare(strict_types=1);

/**
 * Prospecting module translations (pt-BR).
 */

return [
    'provider' => [
        'imported_list' => 'Lista importada',
    ],

    'dashboard' => [
        'title'     => 'Prospecção',
        'campaigns' => 'Campanhas',
        'new_opps'  => 'Novas oportunidades',
        'hot_opps'  => 'Oportunidades quentes',
        'converted' => 'Convertidas',
        'how_title' => 'Como funciona',
        'how_text'  => 'Crie uma campanha com uma lista de empresas, o sistema descobre, enriquece, audita os sites e calcula um score de oportunidade. Você revisa e converte as melhores em oportunidades comerciais.',
    ],

    'campaigns' => [
        'title'         => 'Campanhas',
        'new'           => 'Nova campanha',
        'new_first'     => 'Criar primeira campanha',
        'new_hint'      => 'Defina quem você quer encontrar e cole a lista de empresas.',
        'count'         => ':count campanha(s)',
        'empty_title'   => 'Você ainda não criou nenhuma campanha',
        'empty_text'    => 'Crie sua primeira campanha para encontrar novas oportunidades comerciais.',
        'name'          => 'Nome da campanha',
        'name_ph'       => 'Ex.: Clínicas odontológicas de São Paulo',
        'segment'       => 'Segmento',
        'segment_ph'    => 'Ex.: Restaurantes, Clínicas, Advocacia',
        'city'          => 'Cidade',
        'state'         => 'Estado',
        'status'        => 'Status',
        'list'          => 'Lista de empresas',
        'list_ph'       => "Uma empresa por linha:\nEmpresa ABC; https://empresaabc.com.br; (17) 99999-0000; São Paulo\nEmpresa XYZ; ; (11) 3333-4444; Campinas",
        'list_hint'     => 'Formato por linha: Nome; Site (opcional); Telefone (opcional); Cidade (opcional). O site é usado para auditoria automática.',
        'min_score'     => 'Score mínimo',
        'max_results'   => 'Máx. de resultados',
        'max_audits'    => 'Máx. de auditorias',
        'auto_audit'    => 'Auditar sites automaticamente',
        'preview_note'  => 'A campanha é processada em segundo plano. As empresas com site serão auditadas (respeitando os limites) e receberão um score de oportunidade para sua revisão.',
        'create_action' => 'Criar campanha',
        'discovered'    => 'Encontradas',
        'audited'       => 'Auditadas',
        'run'           => 'Iniciar campanha',
        'pause_action'  => 'Pausar',
        'created'       => 'Campanha criada. Revise e inicie quando quiser.',
        'started'       => 'Campanha iniciada. O processamento ocorre em segundo plano.',
        'paused'        => 'Campanha pausada.',
        'cancelled'     => 'Campanha cancelada.',
        'deleted'       => 'Campanha removida.',
        'limit_reached' => 'Você atingiu o limite de campanhas ativas. Conclua ou cancele uma campanha antes de criar outra.',
    ],

    'status' => [
        'draft'      => 'Rascunho',
        'scheduled'  => 'Agendada',
        'processing' => 'Processando',
        'paused'     => 'Pausada',
        'completed'  => 'Concluída',
        'partial'    => 'Parcialmente concluída',
        'failed'     => 'Falhou',
        'cancelled'  => 'Cancelada',
    ],

    'step' => [
        'queued'      => 'Na fila...',
        'discovering' => 'Descobrindo empresas...',
        'enriching'   => 'Enriquecendo dados...',
        'scoring'     => 'Calculando oportunidades...',
    ],

    'funnel' => [
        'discovered' => 'Encontradas',
        'duplicated' => 'Duplicadas',
        'enriched'   => 'Enriquecidas',
        'audited'    => 'Auditadas',
        'qualified'  => 'Qualificadas',
        'converted'  => 'Convertidas',
    ],

    'review' => [
        'title'            => 'Revisar oportunidades',
        'count'            => ':count oportunidade(s)',
        'empty_title'      => 'Nenhuma oportunidade para revisar',
        'empty_text'       => 'Execute uma campanha para gerar oportunidades qualificadas.',
        'company'          => 'Empresa',
        'opp_score'        => 'Score de oportunidade',
        'site_score'       => 'Score do site',
        'service'          => 'Serviço recomendado',
        'priority'         => 'Prioridade',
        'status'           => 'Status',
        'website'          => 'Website',
        'with_site'        => 'Com site',
        'without_site'     => 'Sem site',
        'no_site'          => 'Sem site identificado',
        'convert'          => 'Converter em oportunidade',
        'convert_selected' => 'Converter selecionadas',
        'confirm_batch'    => 'Converter as oportunidades selecionadas em leads?',
        'converted'        => 'Oportunidade convertida em lead.',
        'already_converted'=> 'Esta oportunidade já foi convertida.',
        'converted_badge'  => 'Convertida',
        'batch_result'     => ':converted convertida(s), :skipped ignorada(s).',
        'discard'          => 'Descartar',
        'discarded'        => 'Oportunidade descartada.',
        'confirm_discard'  => 'Descartar esta oportunidade?',
        'ignore'           => 'Ignorar empresa',
        'ignored'          => 'Empresa adicionada à lista de exclusão.',
        'confirm_ignore'   => 'Ignorar e impedir esta empresa em campanhas futuras?',
        'why_title'        => 'Por que essa pontuação?',
        'no_factors'       => 'Sem fatores registrados.',
        'recommended'      => 'Serviço recomendado',
        'collected'        => 'Dados coletados',
        'socials'          => 'Redes sociais',
        'data_note'        => 'Apenas informações públicas coletadas durante a descoberta/auditoria.',
        'other_actions'    => 'Outras ações',
    ],

    'result_status' => [
        'qualified' => 'Qualificada',
        'converted' => 'Convertida',
        'discarded' => 'Descartada',
    ],

    'priority' => [
        'high'   => 'Alta',
        'medium' => 'Média',
        'low'    => 'Baixa',
    ],

    'website_state' => [
        'none'        => 'Sem site',
        'unreachable' => 'Site indisponível',
        'ok'          => 'Site OK',
        'blocked'     => 'Site bloqueou análise',
    ],

    'service' => [
        'site_creation' => 'Criação de site',
        'optimization'  => 'Otimização',
        'seo'           => 'SEO',
        'security'      => 'Segurança',
        'maintenance'   => 'Manutenção',
    ],

    'exclusion' => [
        'title'      => 'Lista de exclusão',
        'hint'       => 'Empresas nesta lista não serão prospectadas novamente.',
        'add'        => 'Adicionar exclusão',
        'type'       => 'Tipo',
        'value'      => 'Valor',
        'reason'     => 'Motivo',
        'empty'      => 'Nenhuma exclusão cadastrada.',
        'added'      => 'Exclusão adicionada.',
        'removed'    => 'Exclusão removida.',
        'type_domain'  => 'Domínio',
        'type_email'   => 'E-mail',
        'type_phone'   => 'Telefone',
        'type_cnpj'    => 'CNPJ',
        'type_company' => 'Empresa',
    ],
];
