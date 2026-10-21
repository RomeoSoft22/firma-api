<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as PeticionHttp;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FirmaIdentidad;
use Tests\TestCase;

/** Historial y Configuración con el JWT de identidad (aud = FIRMA, rol ADMIN). */
class AccesoIdentidadTest extends TestCase
{
    use FirmaIdentidad, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararIdentidad();
    }

    public function test_rutas_protegidas_exigen_token_de_identidad(): void
    {
        $this->getJson('/api/documentos')->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado');
        $this->getJson('/api/motivos/todos')->assertUnauthorized();
        $this->putJson('/api/parametros', [])->assertUnauthorized();
    }

    public function test_token_valido_de_administrador_da_acceso(): void
    {
        $jwt = $this->tokenIdentidad(['ADMIN']);

        $this->withToken($jwt)->getJson('/api/acceso/yo')->assertOk()
            ->assertJsonPath('usuario.correo', 'ana@juarezcampos.net')
            ->assertJsonPath('usuario.roles', ['ADMIN']);
        $this->withToken($jwt)->getJson('/api/documentos')->assertOk();
        $this->withToken($jwt)->getJson('/api/motivos/todos')->assertOk();
    }

    public function test_token_sin_rol_de_administracion_recibe_403(): void
    {
        $this->withToken($this->tokenIdentidad(['LECTOR']))->getJson('/api/documentos')
            ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    }

    public function test_rechaza_tokens_invalidos(): void
    {
        $otraLlave = file_get_contents(base_path('tests/fixtures/otra-llave-prueba.key'));
        $invalidos = [
            'otro sistema' => $this->tokenIdentidad(claims: ['aud' => 'ISTQB']),
            'otro emisor' => $this->tokenIdentidad(claims: ['iss' => 'https://falso.test']),
            'vencido' => $this->tokenIdentidad(claims: ['exp' => time() - 120, 'iat' => time() - 1200, 'nbf' => time() - 1200]),
            'firma ajena' => $this->tokenIdentidad(llave: $otraLlave),
            'basura' => 'no.es.jwt',
        ];

        foreach ($invalidos as $caso => $token) {
            $this->withToken($token)->getJson('/api/documentos')->assertUnauthorized();
        }
    }

    public function test_la_clave_de_administracion_antigua_ya_no_existe(): void
    {
        $this->postJson('/api/acceso', ['clave' => 'cualquiera'])->assertNotFound();
    }

    public function test_canje_de_codigo_entrega_token_y_cookie_httponly(): void
    {
        $jwt = $this->tokenIdentidad(['ADMIN']);
        $this->respuestaToken = ['access_token' => $jwt, 'refresh_token' => 'refresh-1', 'expires_in' => 900];

        $respuesta = $this->postJson('/api/acceso/canjear', ['code' => 'codigo-1', 'code_verifier' => str_repeat('v', 64)])
            ->assertOk()
            ->assertJsonPath('access_token', $jwt)
            ->assertJsonPath('usuario.nombre', 'Ana Pérez')
            ->assertCookie('firma_refresh');
        $cookie = collect($respuesta->headers->getCookies())->first(fn ($c) => $c->getName() === 'firma_refresh');
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->getSameSite());

        Http::assertSent(fn (PeticionHttp $r) => $r->url() === self::IDENTIDAD.'/api/oauth/token'
            && $r['grant_type'] === 'authorization_code'
            && $r['client_secret'] === 'secreto-firma'
            && $r['redirect_uri'] === 'https://firma.test/callback');
    }

    public function test_canje_de_usuario_sin_rol_admin_no_entrega_token(): void
    {
        $this->respuestaToken = ['access_token' => $this->tokenIdentidad(['LECTOR']), 'refresh_token' => 'r', 'expires_in' => 900];

        $this->postJson('/api/acceso/canjear', ['code' => 'c', 'code_verifier' => str_repeat('v', 64)])
            ->assertForbidden()->assertJsonMissingPath('access_token');
    }

    public function test_identidad_rechaza_el_canje(): void
    {
        $this->respuestaToken = ['error' => 'invalid_grant'];
        $this->estadoToken = 400;

        $this->postJson('/api/acceso/canjear', ['code' => 'c', 'code_verifier' => str_repeat('v', 64)])->assertUnauthorized();
    }

    public function test_renovar_sin_cookie_y_salir(): void
    {
        $this->postJson('/api/acceso/renovar')->assertUnauthorized();

        $this->postJson('/api/acceso/salir')->assertOk()
            ->assertJsonPath('logout_url', self::IDENTIDAD.'/logout?post_logout_redirect_uri='.urlencode('https://firma.test/'));
    }

    public function test_config_publica_para_iniciar_el_flujo(): void
    {
        $this->getJson('/api/acceso/config')->assertOk()
            ->assertJsonPath('authorize_endpoint', self::IDENTIDAD.'/authorize')
            ->assertJsonPath('client_id', 'cliente-firma')
            ->assertJsonMissingPath('client_secret');
    }
}
