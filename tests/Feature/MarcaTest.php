<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\FirmaIdentidad;
use Tests\TestCase;

/** Logos de la marca: lectura pública; subir o restaurar solo con identidad de administrador. */
class MarcaTest extends TestCase
{
    use FirmaIdentidad, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->prepararIdentidad();
    }

    public function test_sin_logos_propios_informa_que_no_hay_personalizados(): void
    {
        $this->getJson('/api/marca')->assertOk()->assertJsonPath('version', 0)->assertJsonPath('personalizados', []);
        $this->get('/api/marca/simbolo-claro')->assertNotFound();
    }

    public function test_subir_y_restaurar_exige_identidad(): void
    {
        $imagen = $this->png();
        $this->postJson('/api/marca/logo-claro', ['imagen' => $imagen])->assertUnauthorized();
        $this->deleteJson('/api/marca/logo-claro')->assertUnauthorized();
        $this->withToken($this->tokenIdentidad(['LECTOR']))
            ->postJson('/api/marca/logo-claro', ['imagen' => $imagen])->assertForbidden();
    }

    public function test_administrador_sube_y_restaura_un_logo(): void
    {
        $jwt = $this->tokenIdentidad(['ADMIN']);

        $this->withToken($jwt)
            ->postJson('/api/marca/simbolo-oscuro', ['imagen' => $this->png()])
            ->assertOk()->assertJsonPath('personalizados.simbolo-oscuro', true);

        $this->getJson('/api/marca')->assertJsonPath('personalizados.simbolo-oscuro', true);
        $this->get('/api/marca/simbolo-oscuro')->assertOk()->assertHeader('Content-Type', 'image/png');

        $this->withToken($jwt)->deleteJson('/api/marca/simbolo-oscuro')->assertOk()->assertJsonPath('personalizados', []);
        $this->get('/api/marca/simbolo-oscuro')->assertNotFound();
    }

    /** PNG real de 1×1 (no requiere la extensión GD). */
    private function png(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('logo.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        ));
    }

    public function test_rechaza_variantes_y_archivos_no_validos(): void
    {
        $jwt = $this->tokenIdentidad(['ADMIN']);
        $this->get('/api/marca/otro')->assertNotFound();
        $this->withToken($jwt)
            ->postJson('/api/marca/logo-claro', ['imagen' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')])
            ->assertUnprocessable();
    }
}
