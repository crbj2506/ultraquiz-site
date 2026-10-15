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
        // A home sorteia uma questão e falha (500) com o banco sem questões; ver risco R11 no plano.
        $this->criarQuestoesAprovadas(1);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
