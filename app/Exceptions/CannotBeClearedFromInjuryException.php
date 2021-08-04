<?php

namespace App\Exceptions;

use Exception;

class CannotBeClearedFromInjuryException extends Exception
{
    protected $message = 'This entity could not be cleared from an injury.';

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->message], 400);
        }

        return back()->withErrors(['message' => $this->message]);
    }
}
