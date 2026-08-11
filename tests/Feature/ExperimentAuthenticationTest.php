<?php

namespace Tests\Feature;

use Database\Seeders\BenchmarkDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperimentAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fortify_session_prepared_before_timing_authenticates_the_experiment_api(): void
    {
        if (! config('experiment.session_authentication')) {
            $this->markTestSkipped('Run with TCC_EXPERIMENT_SESSION_AUTH=true.');
        }

        $this->seed();

        $this->getJson('/experiment/auth/csrf')
            ->assertOk()
            ->assertJsonStructure(['csrf_token']);

        $this->postJson('/login', [
            'email' => BenchmarkDataset::BENCHMARK_EMAIL,
            'password' => BenchmarkDataset::BENCHMARK_PASSWORD,
        ])->assertSuccessful();

        $this->getJson('/api/v1/products')->assertOk();
        $this->getJson('/api/v1/orders')->assertOk();
        $this->getJson('/api/v1/orders/1')->assertOk();
    }
}
