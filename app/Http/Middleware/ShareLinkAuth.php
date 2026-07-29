<?php

namespace App\Http\Middleware;

use App\Models\DocumentAccessLink;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareLinkAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $link = DocumentAccessLink::where('token', $request->route('token'))->first();

        if (!$link || $link->isExpired()) {
            abort(404);
        }

        $request->merge(['access_link' => $link]);

        return $next($request);
    }
}
