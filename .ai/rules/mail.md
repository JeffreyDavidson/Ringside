---
paths:
  - 'app/Mail/**'
---

# Mail

## Mailables use envelope() and content()
Build mail with `envelope()` and `content()`, never the legacy `build()`. Take the models it needs as readonly promoted constructor properties, take the subject and body text from translation keys (`mail.*`), and make the class `final`, grouped by domain (`Mail/Promotions`). Name a new mailable for what it announces with no `Mail` suffix (`PromotionInvited`); the existing `PromotionInvitationMail` keeps its name until it is next reworked.

## Send from an Action and reveal nothing extra
An Action sends the mail (`InvitePromotionMemberAction` calls `Mail::to(...)->send(...)`), not a controller, component or model. A message that announces an invitation is identical whether or not an account exists for the address, carries no token and grants nothing: the recipient signs in and accepts inside the application. Do not put secrets, signed URLs that grant access, or account-existence hints in mail.

## Test the rendered message
Test each mailable at its mirrored path in `tests/Integration/Mail`: subject, rendered content, the plain-text alternative, and that it reads the same whether or not an account exists. The sending Action's own test fakes mail and asserts it sent exactly one.
