<?php

namespace App\Exceptions;

use Exception;

class NotEnoughMembersException extends Exception
{
    /**
     * The default message for sending with exception.
     *
     * @return self
     */
    public static function forTagTeam()
    {
        return new self('Tag team does not contain enough wrestlers.');
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     *
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
