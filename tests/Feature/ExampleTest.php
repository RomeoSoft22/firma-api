<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_api_responde_su_estado(): void
    {
        $this->getJson('/api/estado')->assertOk()->assertJson(['estado' => 'ok']);
    }

    public function test_la_raiz_no_revela_el_framework(): void
    {
        $this->get('/')
            ->assertNotFound()
            ->assertHeaderMissing('X-Powered-By')
            ->assertJson(['message' => 'No encontramos lo que buscas.']);
    }
}
