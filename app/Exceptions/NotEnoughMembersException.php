<?php

namespace App\Exceptions;

use App\Models\TagTeam;
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
        return new self(sprintf(
            'A tag team must contain %u wrestlers to be on a tag team.',
            [TagTeam::NUMBER_OF_WRESTLERS_ON_TEAM]
        ));
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
