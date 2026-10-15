<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $this->criarQuestoesAprovadas(1);

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_home_sem_questoes_no_banco_nao_quebra()
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ainda não há questões disponíveis');
    }
}
