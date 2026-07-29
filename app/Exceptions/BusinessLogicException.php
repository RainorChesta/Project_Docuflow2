<?php

namespace App\Exceptions;

use Exception;

class BusinessLogicException extends Exception
{
    public function render()
    {
        return back()->withErrors(['error' => $this->getMessage()])->throwResponse();
    }
}
