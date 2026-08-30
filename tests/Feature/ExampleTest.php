<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_home_mengarahkan_ke_halaman_masuk_saat_belum_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }
}