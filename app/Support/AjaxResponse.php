<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AjaxResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function success(Request $request, string $message, ?string $redirect = null, bool $reload = true, array $data = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'message' => $message,
                'redirect' => $redirect,
                'reload' => $redirect ? false : $reload,
            ], $data));
        }

        $response = $redirect ? redirect()->to($redirect) : back();

        return $response->with('status', $message);
    }

    public static function error(Request $request, string $message, int $status = 422, string $key = 'form'): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'errors' => [$key => [$message]],
            ], $status);
        }

        return back()->withErrors([$key => $message]);
    }
}
