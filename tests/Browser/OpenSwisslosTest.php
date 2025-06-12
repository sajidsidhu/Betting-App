<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class OpenSwisslosTest extends DuskTestCase
{
    /**
     * A test to open the Swisslos homepage.
     *
     * @return void
     */
    public function testOpenSwisslosHomePage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://www.swisslos.ch')
                    ->waitFor('#vue-header', 1000)
                    ->click('.navigation-wrapper .nav-item .logo__sporttip')
                    ->pause(60000); // Wait for 1 minute (60000 ms)
        });
        // $this->browse(function (Browser $browser) {
        //     $browser->visit('https://www.swisslos.ch')
        //             // Accept cookies if the button appears
        //             ->whenAvailable('[data-testid="uc-accept-all-button"]', function ($modal) {
        //                 $modal->click('[data-testid="uc-accept-all-button"]');
        //             }, 5)
        //             ->waitFor('.login-wrapper .btn-global.btn-primary', 1000)
        //             ->click('.login-wrapper .btn-global.btn-primary')
        //             ->waitFor('#nav-login', 1000)
        //             ->type('#nav-login', 'sajidsidhu95@gmail.com') // Replace with valid username
        //             ->type('#nav-password', '1wt7&PBSNDF!sa0i') // Replace with valid password
        //             ->press('Login')
        //             ->pause(60000); // Wait for 1 minute (60000 ms)
        // });
    }
}
