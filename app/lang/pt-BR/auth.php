<?php

declare(strict_types=1);

/**
 * Authentication translations (pt-BR).
 */

return [
    'login' => [
        'title'       => 'Entrar',
        'email'       => 'E-mail',
        'password'    => 'Senha',
        'submit'      => 'Entrar',
        'placeholder' => [
            'email'    => 'seu@email.com',
            'password' => 'Sua senha',
        ],
    ],
    'failed'  => 'Credenciais inválidas.',
    'logout'  => 'Você saiu com segurança.',
    'welcome' => 'Bem-vindo, :name.',

    'forgot' => [
        'title'    => 'Recuperar senha',
        'subtitle' => 'Informe seu e-mail e enviaremos um link para redefinir sua senha.',
        'submit'   => 'Enviar link',
        'link'     => 'Esqueci minha senha',
        'sent'     => 'Se o e-mail existir em nossa base, enviaremos as instruções em instantes.',
    ],

    'reset' => [
        'title'            => 'Redefinir senha',
        'new_password'     => 'Nova senha',
        'confirm_password' => 'Confirmar nova senha',
        'submit'           => 'Redefinir senha',
        'invalid'          => 'Link inválido ou expirado. Solicite um novo.',
        'success'          => 'Senha redefinida com sucesso. Você já pode entrar.',
    ],
];
