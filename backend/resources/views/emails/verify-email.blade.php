<x-mail::message>
# Bem-vindo ao {{ config('app.name') }}!

Obrigado por se cadastrar. Clique no botão abaixo para verificar seu endereço de email:

<x-mail::button :url="$verificationUrl" color="#10b981">
Verificar Email
</x-mail::button>

Se o botão não funcionar, copie e cole este link no navegador:

{{ $verificationUrl }}

**Este link expira em 60 minutos.**

Se você não criou esta conta, ignore este email.

<x-mail::subcopy>
&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
</x-mail::subcopy>
</x-mail::message>
