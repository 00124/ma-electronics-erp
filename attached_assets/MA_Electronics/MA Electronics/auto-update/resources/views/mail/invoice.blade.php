@component('mail::message')
<h4>Hello,</h4>

{!! $content !!}

Thanks,<br>
{{ config('app.name') }}
@endcomponent