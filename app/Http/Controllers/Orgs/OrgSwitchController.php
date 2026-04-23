<?php

namespace App\Http\Controllers\Orgs;

use App\Http\Controllers\Controller;
use App\Models\Org;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrgSwitchController extends Controller
{
    /**
     * Switch the user's current organization.
     */
    public function __invoke(Request $request, Org $org): RedirectResponse
    {
        abort_unless($request->user()?->switchOrg($org), 403);

        return back();
    }
}
