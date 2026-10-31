<?php

namespace Tests\Fixtures;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class TestCsrfMiddleware extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }

    protected function inExceptArray($request): bool
    {
        return false;
    }
}
