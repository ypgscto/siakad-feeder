<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class RedirectAfterAuth
{
    public static function intended(string $defaultUrl): RedirectResponse
    {
        ApplicationUrl::apply();

        $intended = session()->pull('url.intended');
        if (! is_string($intended) || $intended === '') {
            return redirect()->to($defaultUrl);
        }

        if (! ApplicationUrl::isUnderSubdirectory($intended)) {
            return redirect()->to($defaultUrl);
        }

        return redirect()->to($intended);
    }
}
