<?php

declare(strict_types=1);

/**
 * Outreach module translations (pt-BR).
 *
 * Prospecção comercial e automação de follow-up. Nenhum texto fixo em código:
 * todas as labels/mensagens ficam aqui.
 */

return [
    'channel' => 'Canal',

    'channels' => [
        'whatsapp' => 'WhatsApp',
        'email'    => 'E-mail',
    ],

    'kinds' => [
        'first_contact' => 'Primeiro contato',
        'follow_up'     => 'Follow-up',
        'diagnosis'     => 'Diagnóstico',
        'proposal'      => 'Proposta',
        'meeting'       => 'Reunião',
        'reactivation'  => 'Reativação',
    ],

    'status' => [
        'draft'            => 'Rascunho',
        'pending_approval' => 'Aguardando aprovação',
        'scheduled'        => 'Agendada',
        'sending'          => 'Enviando',
        'sent'             => 'Enviada',
        'delivered'        => 'Entregue',
        'read'             => 'Lida',
        'failed'           => 'Falhou',
        'cancelled'        => 'Cancelada',
        'received'         => 'Recebida',
    ],

    'dashboard' => [
        'title'          => 'Comercial / Outreach',
        'subtitle'       => 'Automatize o trabalho operacional. Você mantém o controle de quem contatar, como abordar e quando encerrar.',
        'window_closed'  => 'Fora da janela de envio. As mensagens serão enviadas automaticamente a partir de :time.',
        'approval_on'    => 'Aprovação humana ativada: nenhuma mensagem é enviada sem sua confirmação.',
        'tasks'          => 'Suas tarefas pendentes',
        'no_tasks'       => 'Nenhuma tarefa pendente.',
    ],

    'metrics' => [
        'sent'          => 'Enviadas',
        'delivered'     => 'Entregues',
        'read'          => 'Lidas',
        'pending'       => 'Pendentes',
        'failed'        => 'Falhas',
        'rates'         => 'Taxas',
        'delivery_rate' => 'Taxa de entrega',
        'read_rate'     => 'Taxa de leitura',
    ],

    'outbox' => [
        'title'          => 'Caixa de saída',
        'count'          => ':count mensagem(ns)',
        'status'         => 'Status',
        'recipient'      => 'Destinatário',
        'preview'        => 'Prévia',
        'approve'        => 'Aprovar',
        'approved'       => 'Mensagem aprovada e agendada para envio.',
        'cancelled'      => 'Mensagem cancelada.',
        'confirm_cancel' => 'Deseja realmente cancelar esta mensagem?',
        'empty_title'    => 'Nenhuma mensagem na caixa de saída',
        'empty_text'     => 'Prepare um contato a partir de uma oportunidade para começar.',
    ],

    'prepare' => [
        'title'              => 'Preparar contato',
        'contact'            => 'Contato',
        'no_contact'         => 'Sem contato específico',
        'template'           => 'Template',
        'no_template'        => 'Sem template (escrever manualmente)',
        'message'            => 'Mensagem',
        'message_placeholder' => 'Escreva a mensagem ou selecione um template...',
        'variables_hint'     => 'Variáveis disponíveis',
        'preview'            => 'Pré-visualizar',
        'save'               => 'Salvar na caixa de saída',
        'saved'              => 'Mensagem preparada com sucesso.',
    ],

    'templates' => [
        'title'          => 'Templates',
        'new'            => 'Novo template',
        'edit'           => 'Editar template',
        'name'           => 'Nome',
        'kind'           => 'Tipo',
        'subject'        => 'Assunto',
        'active'         => 'Ativo',
        'created'        => 'Template criado.',
        'updated'        => 'Template atualizado.',
        'deleted'        => 'Template excluído.',
        'confirm_delete' => 'Deseja realmente excluir este template?',
        'empty_title'    => 'Nenhum template cadastrado',
        'empty_text'     => 'Crie templates reutilizáveis com variáveis para agilizar a abordagem.',
    ],

    'sequences' => [
        'title'          => 'Sequências de follow-up',
        'subtitle'       => 'Defina os passos automáticos. A sequência para automaticamente quando o lead responde, agenda reunião, fecha ou pede opt-out.',
        'new'            => 'Nova sequência',
        'edit'           => 'Editar sequência',
        'name'           => 'Nome',
        'active'         => 'Ativa',
        'steps'          => 'Passos',
        'delay_days'     => 'Dias de espera',
        'stop_on_reply'  => 'Parar ao responder',
        'add_step'       => 'Adicionar passo',
        'created'        => 'Sequência criada.',
        'updated'        => 'Sequência atualizada.',
        'deleted'        => 'Sequência excluída.',
        'confirm_delete' => 'Deseja realmente excluir esta sequência?',
        'empty_title'    => 'Nenhuma sequência cadastrada',
        'empty_text'     => 'Crie uma sequência para automatizar os follow-ups com condições de parada.',
        'enroll_title'   => 'Inscrever em sequência',
        'enroll'         => 'Inscrever',
        'enrolled'       => 'Lead inscrito na sequência.',
    ],

    'conversations' => [
        'title'          => 'Conversas',
        'them'           => 'Contato',
        'you'            => 'Você',
        'needs_human'    => 'Precisa de atenção',
        'open_lead'      => 'Abrir oportunidade',
        'close'          => 'Encerrar',
        'closed'         => 'Conversa encerrada.',
        'confirm_close'  => 'Deseja encerrar esta conversa?',
        'no_messages'    => 'Nenhuma mensagem nesta conversa.',
        'empty_title'    => 'Nenhuma conversa ainda',
        'empty_text'     => 'As respostas dos contatos aparecem aqui.',
        'status' => [
            'open'            => 'Aberta',
            'awaiting_seller' => 'Aguardando vendedor',
            'replied'         => 'Respondida',
            'closed'          => 'Encerrada',
        ],
    ],

    'reports' => [
        'title'          => 'Relatórios comerciais',
        'subtitle'       => 'Diagnósticos compartilháveis por link, com controle de validade e revogação.',
        'report'         => 'Relatório',
        'views'          => 'Visualizações',
        'status'         => 'Status',
        'active'         => 'Ativo',
        'revoked'        => 'Revogado',
        'created'        => 'Relatório criado. O link foi gerado.',
        'revoke'         => 'Revogar',
        'revoked_msg'    => 'Link do relatório revogado.',
        'confirm_revoke' => 'Deseja revogar o link deste relatório? Ele deixará de ser acessível.',
        'public_link'    => 'Link público',
        'expires'        => 'Expira em',
        'score'          => 'Score',
        'preview'        => 'Prévia do conteúdo',
        'no_snapshot'    => 'Sem conteúdo estruturado para exibir.',
        'empty_title'    => 'Nenhum relatório criado',
        'empty_text'     => 'Gere um relatório a partir de uma oportunidade para compartilhar com o cliente.',
    ],

    'suppressions' => [
        'title'          => 'Opt-out / Supressões',
        'subtitle'       => 'Contatos que nunca devem ser abordados novamente. O opt-out é respeitado automaticamente.',
        'add'            => 'Adicionar supressão',
        'value'          => 'Telefone ou e-mail',
        'type'           => 'Tipo',
        'reason'         => 'Motivo',
        'added'          => 'Supressão adicionada.',
        'removed'        => 'Supressão removida.',
        'confirm_remove' => 'Deseja remover esta supressão?',
        'empty_title'    => 'Nenhuma supressão cadastrada',
        'empty_text'     => 'Opt-outs e bloqueios manuais aparecem aqui.',
        'reasons' => [
            'opt_out' => 'Opt-out',
            'manual'  => 'Manual',
            'bounce'  => 'Bounce',
        ],
    ],

    'public' => [
        'diagnosis_for'  => 'Diagnóstico preparado para',
        'default_title'  => 'Diagnóstico',
        'overall_score'  => 'Pontuação geral',
        'recommendations' => 'Recomendações',
        'default_cta'    => 'Quero saber mais',
        'generated_by'   => 'Relatório gerado por :name',
        'unavailable_title' => 'Relatório indisponível',
        'severity' => [
            'critical' => 'Críticos',
            'high'     => 'Altos',
            'medium'   => 'Médios',
            'low'      => 'Baixos',
        ],
        'unavailable' => [
            'not_found' => 'Este link não existe ou foi digitado incorretamente.',
            'expired'   => 'Este relatório expirou e não está mais disponível.',
            'revoked'   => 'Este relatório foi revogado e não está mais disponível.',
        ],
    ],

    'errors' => [
        'no_recipient'       => 'Nenhum destinatário válido (telefone/e-mail) foi encontrado.',
        'suppressed'         => 'Este contato optou por não ser abordado (opt-out).',
        'duplicate'          => 'Já existe uma mensagem equivalente preparada para este contato.',
        'unknown_variables'  => 'O template usa variáveis desconhecidas.',
        'unknown_variable'   => 'Variável de template desconhecida.',
        'not_found'          => 'Registro não encontrado.',
        'not_pending'        => 'A mensagem não está aguardando aprovação.',
        'already_sent'       => 'A mensagem já foi enviada.',
        'already_enrolled'   => 'O lead já está inscrito em uma sequência ativa.',
        'no_steps'           => 'A sequência não possui passos configurados.',
        'validation'         => 'Verifique os dados informados.',
    ],
];
