<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_success_and_renders_create_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Create Room')
            ->assertSee('TempChat');
    }
}
