<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeDeactivatedException extends Exception
{
    /**
     * The default message for sending with exception.
     *
     * @var string
     */
    protected $message = 'Entity cannot be deactivated. This entity is not currently activated.';

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->message], 400);
        }

        return redirect()->back()->withError($this->message);
    }
}
