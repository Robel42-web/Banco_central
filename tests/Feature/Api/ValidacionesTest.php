<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\AuthenticateNodeApiKey;
use Tests\TestCase;

class ValidacionesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(AuthenticateNodeApiKey::class, function ($mock) {
            $mock->shouldReceive('handle')
                ->andReturnUsing(function ($request, $next) {
                    $request->attributes->set(
                        'node_api_key',
                        (object) [
                            'id' => 'test-api-key-id',
                            'node_type' => 'branch',
                            'branch_id' => '00000000-0000-4000-8000-000000000001',
                        ]
                    );

                    return $next($request);
                });
        });
    }

    public function test_no_permite_crear_una_cuenta_sin_datos(): void
    {
        $this->postJson('/api/cuentas', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'titular',
                'numero_cuenta',
            ]);
    }

    public function test_no_permite_un_deposito_sin_datos(): void
    {
        $this->postJson('/api/operaciones/depositos', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'cuenta_id',
                'monto_centavos',
                'referencia',
                'idempotency_key',
            ]);
    }

    public function test_no_permite_un_retiro_sin_datos(): void
    {
        $this->postJson('/api/operaciones/retiros', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'cuenta_id',
                'monto_centavos',
                'referencia',
                'idempotency_key',
            ]);
    }
}
