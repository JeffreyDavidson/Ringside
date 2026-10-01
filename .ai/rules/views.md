---
paths:
  - 'resources/views/**'
---

# Views

## Blade and Livewire frontend
Build pages in Blade and mount Livewire components for server-driven interaction.

## Compose Blade with anonymous components
Compose views with <x-*> Blade components instead of includes. Implement presentation-only reusable components as anonymous components unless PHP behavior requires a class.

## Short translation keys
Localize application UI with short dotted keys backed by lang/{locale}/*.php files. Do not add sentence-key JSON translations.

## Bind dynamic component attributes with a colon
Pass dynamic data to Blade components as :attr="$expr", never attr="{{ $expr }}". The echo form is escaped by the tag compiler and then escaped again by the component, which double-escapes names containing apostrophes or ampersands.
