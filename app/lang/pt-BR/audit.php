<?php

declare(strict_types=1);

/**
 * Audit module translations (pt-BR).
 */

return [
    'title'     => 'Auditorias',
    'new'       => 'Nova auditoria',
    'new_hint'  => 'Informe a URL do site que deseja analisar.',
    'start'     => 'Iniciar análise',
    'count'     => ':count auditoria(s)',
    'report'    => 'Ver relatório',
    'to_lead'   => 'Transformar em oportunidade',
    'transform_success' => 'Auditoria transformada em oportunidade com sucesso.',
    'transform_failed'  => 'Não foi possível transformar em oportunidade.',
    'pages'     => 'páginas',
    'deleted'   => 'Auditoria removida.',
    'queued_message' => 'Auditoria criada e adicionada à fila. O processamento começa em instantes.',
    'processing_note' => 'A análise está em andamento. Esta página atualiza automaticamente.',

    'empty_title' => 'Nenhuma auditoria ainda',
    'empty_text'  => 'Crie sua primeira auditoria para analisar um site e gerar um diagnóstico.',

    'failed_title' => 'Não foi possível concluir a auditoria',
    'failed_text'  => 'Não conseguimos acessar o site ou ocorreu um erro durante a análise. Verifique a URL e tente novamente.',
    'partial_note' => 'Esta auditoria foi concluída parcialmente: algumas páginas não puderam ser verificadas. Os resultados obtidos são válidos.',
    'report_not_ready' => 'O relatório ficará disponível quando a auditoria for concluída.',

    'overall_score' => 'Pontuação geral',
    'positives'     => 'Pontos positivos',
    'issues'        => 'Pontos de melhoria',
    'no_issues'     => 'Nenhum problema relevante encontrado. Excelente!',
    'search_issues' => 'Buscar...',
    'technologies'  => 'Tecnologias',
    'contacts'      => 'Contatos públicos',
    'impact'        => 'Impacto',
    'recommendation'=> 'Recomendação',
    'not_available' => 'Não disponível',
    'cwv_note'      => 'Core Web Vitals exigem uma fonte confiável (ex.: PageSpeed Insights) e serão habilitados futuramente.',

    'field' => [
        'url'       => 'URL do site',
        'status'    => 'Status',
        'score'     => 'Nota',
        'date'      => 'Data',
        'scope'     => 'Escopo da análise',
        'max_pages' => 'Máximo de páginas',
        'max_depth' => 'Profundidade máxima',
    ],

    'scope' => [
        'homepage' => 'Apenas página inicial',
        'full'     => 'Site completo (múltiplas páginas)',
    ],
    'advanced' => 'Configurações avançadas',

    'status' => [
        'queued'     => 'Na fila',
        'processing' => 'Processando',
        'completed'  => 'Concluída',
        'partial'    => 'Parcialmente concluída',
        'failed'     => 'Falhou',
        'cancelled'  => 'Cancelada',
    ],

    'step' => [
        'validating_domain' => 'Validando domínio...',
        'crawling'          => 'Descobrindo e analisando páginas...',
        'analyzing'         => 'Analisando SEO, performance e segurança...',
        'scoring'           => 'Calculando pontuações...',
        'persisting'        => 'Gerando resultado...',
    ],

    'category' => [
        'performance'    => 'Performance',
        'seo'            => 'SEO',
        'security'       => 'Segurança',
        'accessibility'  => 'Acessibilidade',
        'content'        => 'Conteúdo',
        'technology'     => 'Tecnologia',
        'best_practices' => 'Boas práticas',
    ],

    'severity' => [
        'critical' => 'Crítico',
        'high'     => 'Alto',
        'medium'   => 'Médio',
        'low'      => 'Baixo',
        'info'     => 'Informativo',
    ],

    'confidence' => [
        'high'   => 'Alta confiança',
        'medium' => 'Média confiança',
        'low'    => 'Baixa confiança',
    ],

    'filter' => [
        'all' => 'Todos',
    ],

    'band' => [
        'excellent'         => 'Excelente',
        'good'              => 'Bom',
        'needs_improvement' => 'Precisa melhorar',
        'problematic'       => 'Problemático',
        'critical'          => 'Crítico',
    ],

    'errors' => [
        'invalid_url'  => 'A URL informada é inválida ou não pode ser analisada.',
        'rate_limited' => 'Você atingiu o limite de auditorias por hoje. Tente novamente amanhã.',
    ],

    // Report
    'report_title'   => 'Relatório de auditoria — :host',
    'report_heading' => 'Relatório de Auditoria de Site',
    'report_date'    => 'Data: :date',
    'exec_summary'   => 'Resumo executivo',
    'exec_intro'     => 'Analisamos o site :host e encontramos :count ponto(s) que podem ser melhorados.',
    'conclusion'     => 'Conclusão',
    'conclusion_text'=> 'Este relatório apresenta oportunidades de melhoria priorizadas por impacto. Corrigir os pontos críticos e altos tende a trazer os maiores ganhos de desempenho, visibilidade e confiança.',
    'cta_report'     => 'Quer corrigir esses pontos? Fale com nossa equipe:',
    'report_footer'  => 'Relatório gerado por :brand',
    'report_public_note' => 'Contém apenas informações públicas coletadas durante a análise.',
    'print_save_pdf' => 'Imprimir / Salvar como PDF',
];
