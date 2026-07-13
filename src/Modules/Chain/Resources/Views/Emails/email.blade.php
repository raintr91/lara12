<!-- Email template for Chain -->
<x-mail::message>
# Hello!

This is an email template for Chain module.

<x-mail::button :url="url('/')">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
