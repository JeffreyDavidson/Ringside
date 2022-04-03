<?php

namespace App\Exceptions;

use Exception;

class CannotBeEmployedException extends Exception
{
    /**
     * The default message for sending with exception.
     *
     * @var string
     */
    protected $message = 'This entity cannot be employed. This entity does have a start date.';

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

        return back()->withError($this->message);
    }
}
