<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class Login extends Page
{
    /**
     * Get the URL for the page.
     *
     * @return string
     */
    public function url()
    {
        return route('login');
    }

    /**
     * Assert that the browser is on the page.
     *
     * @param  Browser  $browser
     * @return void
     */
    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array
     */
    public function elements()
    {
        return [
            '@login-button' => 'button[type="submit"]',
        ];
    }

    /**
     * Fill out login page.
     *
     * @param  \Laravel\Dusk\Browser  $browser
     * @param  string  $email
     * @param  string  $password
     * @return void
     */
    public function fillInLoginForm(Browser $browser, $email, $password)
    {
        $browser->type('email', $email)
                ->type('password', $password)
                ->click('@login-button');
    }
}
