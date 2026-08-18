@component('mail::message')
# Convite para {{ $companyName }}

@if($inviterName){{ $inviterName }} convidou você @else Você foi convidado @endif para colaborar no painel de gestão de conteúdo da agência **{{ $companyName }}**.

@component('mail::button', ['url' => $acceptUrl])
Aceitar convite
@endcomponent

Se você não esperava este convite, basta ignorar este e-mail.

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
