<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class SaludTest extends TestCase
{
    public function test_la_api_responde_correctamente(): void
    {
        $this->getJson('/api/salud')
            ->assertOk()
            ->assertJson([
                'servicio' => 'Banco Central',
                'estado' => 'OK',
            ]);
    }
}
