<?php

namespace Tests\Feature\Status;

use Tests\TestCase;

class IndexTest extends TestCase
{
    public function test_index(): void
    {
        $response = $this->getJson(route('status'));

        $response->assertOk();
    }
}
