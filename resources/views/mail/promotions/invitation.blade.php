<x-mail::message>
# {{ __('mail.promotion_invitation.heading', ['promotion' => $promotion]) }}

{{ __('mail.promotion_invitation.intro', ['inviter' => $inviter, 'promotion' => $promotion, 'role' => $role]) }}

{{ __('mail.promotion_invitation.expires', ['date' => $expires]) }}

{{ __('mail.promotion_invitation.accept') }}

<x-mail::button :url="$loginUrl">
{{ __('mail.promotion_invitation.login_button') }}
</x-mail::button>

{{ __('mail.promotion_invitation.register') }} {{ $registerUrl }}

{{ __('mail.promotion_invitation.ignore') }}

{{ __('mail.promotion_invitation.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
