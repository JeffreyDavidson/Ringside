<?php

declare(strict_types=1);
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a publisher saves previews and releases a fan card and deliberately separate results', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];

    $page = visit(route('events.public-pages.edit', $event));
    resizeAndSettle($page, 390, 844);
    $page->assertSee('Public page')->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->type('Public summary', 'A night at the harbor.')
        ->check('Announce this match')->check('Match type')->check('input[name="results[]"]')
        ->press('Save private draft')->assertSee('Nothing was published.')
        ->screenshot(fullPage: true, filename: 'public-event-editor-mobile')
        ->click('Preview card')->assertSee('Draft')->assertSee('Robin North vs Casey Shore')->assertDontSee('Pinfall')
        ->screenshot(fullPage: true, filename: 'public-event-preview-mobile')->assertNoJavascriptErrors();
    $page = visit(route('events.public-pages.edit', $event));
    $page->check('input[name=reviewed]')
        ->press('Publish card')->assertSee('Last published');
    $page->check('input[name=reviewed]')
        ->press('Publish selected results')->assertSee('Last published');
    $page->click('Open released page');
    resizeAndSettle($page, 360, 800);
    $page->assertSee('A night at the harbor.')
        ->assertDontSee('Pinfall')->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->screenshot(fullPage: true, filename: 'public-event-card-mobile')
        ->resize(1280, 900)->screenshot(fullPage: true, filename: 'public-event-card-desktop')
        ->click('View results — spoilers')->assertSee('Robin North — Pinfall')->assertSee('Harbor Night')
        ->script('window.scrollTo({top: 0, behavior: "instant"})');
    $page->screenshot(fullPage: true, filename: 'public-event-results-desktop')
        ->assertNoJavascriptErrors();
});

test('an approved poster stays readable on phones and the public page works at zoomed text size', function (): void {
    Storage::fake('local');
    $world = publicEventFixture();
    $event = $world['event'];
    $file = storage_path('framework/testing/public-event-demo-poster.png');

    if (! is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }
    $pixels = imagecreatetruecolor(300, 500);
    imagefill($pixels, 0, 0, imagecolorallocate($pixels, 16, 17, 18) ?: throw new RuntimeException('Test color unavailable'));
    imagestring($pixels, 5, 60, 150, 'DEMO POSTER', imagecolorallocate($pixels, 247, 247, 245) ?: throw new RuntimeException('Test color unavailable'));
    imagepng($pixels, $file);

    savePublicDraft($event, ['poster' => new UploadedFile($file, 'demo-poster.png', 'image/png', null, true), 'poster_alt' => 'Harbor Night demonstration poster']);
    $editor = visit(route('events.public-pages.edit', $event));
    resizeAndSettle($editor, 390, 844);
    $editor->type('Poster alternative text', 'Harbor Night demonstration poster')
        ->press('Save private draft')->assertSee('Nothing was published.')->check('input[name=reviewed]')
        ->press('Publish card')->assertSee('Last published')->click('Open released page');
    $editor->resize(390, 844)->assertSee('Harbor Night')->assertPresent('img[alt="Harbor Night demonstration poster"]')
        ->assertScript('document.querySelector("img[alt]").naturalWidth > 0')
        ->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->screenshot(fullPage: true, filename: 'public-event-poster-mobile')
        ->assertNoAccessibilityIssues()->assertNoJavascriptErrors();
    $editor->script('document.documentElement.style.fontSize = "200%"');
    $editor->assertScript('document.documentElement.scrollWidth <= innerWidth')->assertSee('Harbor Night');
    unlink($file);
});
