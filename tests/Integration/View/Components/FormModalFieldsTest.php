<?php

declare(strict_types=1);

use App\Livewire\Events\Modals\FormModal as EventFormModal;
use App\Livewire\Managers\Modals\FormModal as ManagerFormModal;
use App\Livewire\Promotions\Modals\FormModal as PromotionFormModal;
use App\Livewire\Referees\Modals\FormModal as RefereeFormModal;
use App\Livewire\Stables\Modals\FormModal as StableFormModal;
use App\Livewire\TagTeams\Modals\FormModal as TagTeamFormModal;
use App\Livewire\Titles\Modals\FormModal as TitleFormModal;
use App\Livewire\Users\Modals\FormModal as UserFormModal;
use App\Livewire\Venues\Modals\FormModal as VenueFormModal;
use App\Livewire\Wrestlers\Modals\FormModal as WrestlerFormModal;
use App\Models\Users\User;
use Dom\Element;
use Dom\HTMLDocument;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * @param  array<string, mixed>  $parameters
 */
function renderedFormModal(string $modal, array $parameters = []): HTMLDocument
{
    return HTMLDocument::createFromString(livewire($modal, $parameters)->html(), LIBXML_NOERROR);
}

function formModalField(HTMLDocument $document, string $id): Element
{
    return $document->getElementById($id) ?? $document->querySelector("input[data-field=\"{$id}\"]") ?? throw new RuntimeException("The form has no [{$id}] field.");
}

function formModalFieldIsRequired(Element $field): bool
{
    return $field->hasAttribute('required') || $field->getAttribute('aria-required') === 'true';
}

function formModalLabelMarksRequired(HTMLDocument $document, string $id): bool
{
    $combobox = $document->querySelector("[data-roster-combobox=\"{$id}\"] label");

    if ($combobox instanceof Element) {
        return $combobox->querySelector('[aria-hidden="true"]')?->textContent === '*';
    }

    foreach ($document->querySelectorAll('label') as $label) {
        if ($label->getAttribute('for') === $id) {
            return $label->querySelector('[aria-hidden="true"]')?->textContent === '*';
        }
    }

    throw new RuntimeException("The [{$id}] field has no label.");
}

beforeEach(function (): void {
    actingAs(administrator());
});

describe('form modal fields', function (): void {
    test('it marks required fields on the input and its label and leaves optional fields unmarked', function (
        string $modal,
        array $required,
        array $optional,
    ): void {
        // Act
        $document = renderedFormModal($modal);

        // Assert
        foreach ($required as $id) {
            expect(formModalFieldIsRequired(formModalField($document, $id)))->toBeTrue("[{$id}] should be required.")
                ->and(formModalLabelMarksRequired($document, $id))->toBeTrue("[{$id}] label should be marked.");
        }

        foreach ($optional as $id) {
            expect(formModalFieldIsRequired(formModalField($document, $id)))->toBeFalse("[{$id}] should be optional.")
                ->and(formModalLabelMarksRequired($document, $id))->toBeFalse("[{$id}] label should not be marked.");
        }
    })->with([
        'wrestler' => [WrestlerFormModal::class, ['form.name', 'form.hometown', 'form.height_feet', 'form.height_inches', 'form.weight'], ['form.signature_move', 'form.employment_date']],
        'manager' => [ManagerFormModal::class, ['form.first_name', 'form.last_name'], ['form.employment_date']],
        'referee' => [RefereeFormModal::class, ['form.first_name', 'form.last_name'], ['form.employment_date']],
        'tag team' => [TagTeamFormModal::class, ['form.name', 'form.wrestlerA', 'form.wrestlerB'], ['form.signature_move', 'form.employment_date', 'form.managers']],
        'stable' => [StableFormModal::class, ['form.name'], ['form.started_at', 'form.ended_at', 'form.wrestlers', 'form.tag_teams']],
        'title' => [TitleFormModal::class, ['form.name', 'form.type'], ['form.start_date']],
        'venue' => [VenueFormModal::class, ['form.name', 'form.street_address', 'form.city', 'form.state', 'form.zipcode'], []],
        'event' => [EventFormModal::class, ['form.name'], ['form.date', 'form.venue_id', 'form.preview']],
        'user' => [UserFormModal::class, ['form.first_name', 'form.last_name', 'form.email', 'form.role', 'form.password', 'form.password_confirmation'], []],
        'promotion' => [PromotionFormModal::class, ['form.name', 'form.slug'], []],
    ]);

    test('it only requires a password when creating a user', function (): void {
        // Arrange
        $user = User::factory()->create();

        // Act
        $document = renderedFormModal(UserFormModal::class, ['modelId' => $user->id]);

        // Assert
        expect(formModalField($document, 'form.password')->hasAttribute('required'))->toBeFalse()
            ->and(formModalField($document, 'form.password_confirmation')->hasAttribute('required'))->toBeFalse()
            ->and(formModalField($document, 'form.password')->getAttribute('type'))->toBe('password')
            ->and(formModalField($document, 'form.password_confirmation')->getAttribute('type'))->toBe('password');
    });

    test('it focuses the first field when the modal opens', function (string $modal, string $firstField): void {
        // Act
        $document = renderedFormModal($modal);

        // Assert
        $focused = array_map(
            fn (Element $element): string => (string) $element->getAttribute('id'),
            iterator_to_array($document->querySelectorAll('[data-initial-focus]')),
        );

        expect($focused)->toBe([$firstField]);
    })->with([
        'wrestler' => [WrestlerFormModal::class, 'form.name'],
        'manager' => [ManagerFormModal::class, 'form.first_name'],
        'referee' => [RefereeFormModal::class, 'form.first_name'],
        'tag team' => [TagTeamFormModal::class, 'form.name'],
        'stable' => [StableFormModal::class, 'form.name'],
        'title' => [TitleFormModal::class, 'form.name'],
        'venue' => [VenueFormModal::class, 'form.name'],
        'event' => [EventFormModal::class, 'form.name'],
        'user' => [UserFormModal::class, 'form.first_name'],
        'promotion' => [PromotionFormModal::class, 'form.name'],
    ]);

    test('it brings up the numeric keyboard for numeric text fields', function (string $modal, string $id): void {
        // Act
        $document = renderedFormModal($modal);

        // Assert
        expect(formModalField($document, $id)->getAttribute('inputmode'))->toBe('numeric');
    })->with([
        'wrestler feet' => [WrestlerFormModal::class, 'form.height_feet'],
        'wrestler inches' => [WrestlerFormModal::class, 'form.height_inches'],
        'wrestler weight' => [WrestlerFormModal::class, 'form.weight'],
        'venue zip code' => [VenueFormModal::class, 'form.zipcode'],
    ]);

    test('it disables the footer buttons while the form is working', function (string $modal, string $target): void {
        // Act
        $document = renderedFormModal($modal);

        // Assert
        $buttons = iterator_to_array($document->querySelectorAll('[data-form-footer] button'));

        expect($buttons)->not->toBeEmpty();

        foreach ($buttons as $button) {
            expect($button->getAttribute('wire:loading.attr'))->toBe('disabled')
                ->and($button->getAttribute('wire:target'))->toBe($target);
        }
    })->with([
        'wrestler' => [WrestlerFormModal::class, 'save, clear, fillDummyFields'],
        'venue' => [VenueFormModal::class, 'save, clear, fillDummyFields'],
        'promotion' => [PromotionFormModal::class, 'save'],
    ]);
});
