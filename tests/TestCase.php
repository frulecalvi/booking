<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;

abstract class TestCase extends BaseTestCase
{
    use MakesJsonApiRequests;

    public function setUp(): void
    {
        parent::setUp();

        // $this->withoutExceptionHandling();
    }
}
