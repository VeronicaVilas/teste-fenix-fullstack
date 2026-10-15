<?php

namespace Tests\Feature;

use App\Exceptions\BusinessException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::prefix('api/v1/testing')->middleware('api')->group(function () {
            Route::post('validation', fn (Request $request) => $request->validate(['title' => ['required']]));
            Route::get('business', fn () => throw new class('Prova publicada não pode ser alterada.') extends BusinessException {});
            Route::get('failure', fn () => throw new RuntimeException('SQLSTATE secret details'));
        });
    }

    public function test_unknown_api_route_returns_404_envelope(): void
    {
        $this->getJson('/api/v1/rota-inexistente')
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Recurso não encontrado.',
                'status' => 404,
                'errors' => [],
                'data' => [],
            ]);
    }

    public function test_api_returns_json_envelope_even_without_accept_header(): void
    {
        $this->get('/api/v1/rota-inexistente')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso não encontrado.');
    }

    public function test_validation_error_returns_422_envelope_with_errors(): void
    {
        $this->postJson('/api/v1/testing/validation', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Os dados enviados são inválidos.')
            ->assertJsonPath('status', 422)
            ->assertJsonStructure(['errors' => ['title']]);
    }

    public function test_business_exception_returns_its_status_and_message(): void
    {
        $this->getJson('/api/v1/testing/business')
            ->assertConflict()
            ->assertExactJson([
                'message' => 'Prova publicada não pode ser alterada.',
                'status' => 409,
                'errors' => [],
                'data' => [],
            ]);
    }

    public function test_method_not_allowed_keeps_envelope(): void
    {
        $this->deleteJson('/api/v1/testing/business')
            ->assertMethodNotAllowed()
            ->assertJsonPath('status', 405);
    }

    public function test_unexpected_error_hides_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        $this->getJson('/api/v1/testing/failure')
            ->assertInternalServerError()
            ->assertExactJson([
                'message' => 'Erro interno no servidor.',
                'status' => 500,
                'errors' => [],
                'data' => [],
            ]);
    }

    public function test_web_routes_keep_default_rendering(): void
    {
        $response = $this->get('/pagina-inexistente');

        $response->assertNotFound();
        $this->assertStringNotContainsString('Recurso não encontrado.', $response->getContent());
    }
}
