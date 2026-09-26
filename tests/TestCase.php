<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The dashboard is a Vite-built Vue app; PHP tests must not depend on `npm run build` having been run.
        $this->withoutVite();
    }
}
