<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FirmaIdentidad;
use Tests\TestCase;

/** «Opciones de administración» de la aplicación de escritorio: una contraseña definida desde la web. */
class AccesoEscritorioTest extends TestCase
{
    use FirmaIdentidad, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararIdentidad();
    }

    public function test_sin_clave_definida_avisa_donde_definirla(): void
    {
        $this->postJson('/api/acceso/clave', ['clave' => 'cualquiera'])->assertStatus(409)->assertJsonPath('codigo', 'sin_clave');
    }

    public function test_un_administrador_define_la_clave_y_con_ella_se_entra_a_configuracion(): void
    {
        $jwt = $this->tokenIdentidad(['ADMIN']);
        $this->withToken($jwt)->getJson('/api/acceso/clave-escritorio')->assertJsonPath('definida', false);
        $this->withToken($jwt)->putJson('/api/acceso/clave-escritorio', ['clave' => 'corta', 'clave_confirmation' => 'corta'])
            ->assertUnprocessable();
        $this->withToken($jwt)->putJson('/api/acceso/clave-escritorio', ['clave' => 'Secreta-2026', 'clave_confirmation' => 'Secreta-2026'])
            ->assertOk()->assertJsonPath('definida', true);

        // El hash nunca sale en los parámetros públicos.
        $this->getJson('/api/parametros')->assertJsonMissingPath('clave_escritorio');

        $this->postJson('/api/acceso/clave', ['clave' => 'otra'])->assertUnauthorized()->assertJsonPath('codigo', 'clave_incorrecta');

        $token = $this->postJson('/api/acceso/clave', ['clave' => 'Secreta-2026'])->assertOk()->json('access_token');
        $this->assertStringStartsWith('fdc_', $token);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->putJson('/api/parametros', ['ubicacion' => 'Arequipa'])->assertOk()->assertJsonPath('ubicacion', 'Arequipa');
        // Con la clave de escritorio no se puede cambiar la propia clave.
        $this->withToken($token)->putJson('/api/acceso/clave-escritorio', ['clave' => 'Nueva-2026x', 'clave_confirmation' => 'Nueva-2026x'])
            ->assertForbidden();

        $this->withToken($token)->postJson('/api/acceso/salir')->assertOk();
        $this->withToken($token)->getJson('/api/acceso/yo')->assertUnauthorized();
    }

    public function test_limita_los_intentos_de_clave(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/acceso/clave', ['clave' => 'x'])->assertStatus(409);
        }
        $this->postJson('/api/acceso/clave', ['clave' => 'x'])->assertStatus(429);
    }

    public function test_token_inventado_no_sirve(): void
    {
        $this->withToken('fdc_'.str_repeat('a', 64))->getJson('/api/documentos')->assertUnauthorized();
    }
}
