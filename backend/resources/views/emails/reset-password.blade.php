<x-mail::message>
# Redefinição de Senha

Você solicitou a redefinição de sua senha. Clique no botão abaixo para criar uma nova:

<x-mail::button :url="$resetUrl" color="#f59e0b">
Redefinir Senha
</x-mail::button>

Se o botão não funcionar, copie e cole:
{{ $resetUrl }}

**Este link expira em 60 minutos e só pode ser usado uma vez.**

Se você não solicitou isso, ignore este email. Sua senha não será alterada.

<x-mail::subcopy>
&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
</x-mail::subcopy>
</x-mail::message>
