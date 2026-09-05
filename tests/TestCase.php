<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every request in this application is expected to originate from the
     * SPA frontend, so requests are treated as such by default — matching
     * Sanctum's stateful-domain check (`EnsureFrontendRequestsAreStateful`),
     * which otherwise skips attaching session/cookie middleware entirely.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Referer', config('app.frontend_url'));
    }
}
