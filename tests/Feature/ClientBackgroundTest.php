<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientBackgroundTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_client_with_custom_solid_color(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'slug' => 'empresa-teste']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Admin Teste',
            'email' => 'admin@teste.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Cliente Cor Solida',
            'segment' => 'Tecnologia',
            'bg_type' => 'color',
            'bg_color' => '#0f172a',
        ]);

        $response->assertRedirect();

        $client = Client::where('name', 'Cliente Cor Solida')->first();
        $this->assertNotNull($client);
        $this->assertEquals('color', $client->bg_type);
        $this->assertEquals('#0f172a', $client->bg_color);
        $this->assertEquals('background-color: #0f172a;', $client->background_style);
    }

    public function test_can_create_client_with_gradient(): void
    {
        $company = Company::create(['name' => 'Empresa Teste 2', 'slug' => 'empresa-teste-2']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Admin Teste 2',
            'email' => 'admin2@teste.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $gradient = 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%)';

        $response = $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Cliente Gradiente',
            'segment' => 'Design',
            'bg_type' => 'gradient',
            'bg_gradient' => $gradient,
        ]);

        $response->assertRedirect();

        $client = Client::where('name', 'Cliente Gradiente')->first();
        $this->assertNotNull($client);
        $this->assertEquals('gradient', $client->bg_type);
        $this->assertEquals($gradient, $client->bg_gradient);
        $this->assertEquals("background: {$gradient};", $client->background_style);
    }

    public function test_can_update_client_background(): void
    {
        $company = Company::create(['name' => 'Empresa Teste 3', 'slug' => 'empresa-teste-3']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Admin Teste 3',
            'email' => 'admin3@teste.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $client = Client::create([
            'company_id' => $company->id,
            'name' => 'Cliente Para Atualizar',
            'slug' => 'cliente-para-atualizar',
            'bg_type' => 'default',
        ]);

        // Atualiza para cor sólida
        $response = $this->actingAs($user)->patch(route('clients.update', $client), [
            'name' => 'Cliente Para Atualizar',
            'bg_type' => 'color',
            'bg_color' => '#1e1b4b',
        ]);

        $response->assertRedirect();
        $client->refresh();
        $this->assertEquals('color', $client->bg_type);
        $this->assertEquals('#1e1b4b', $client->bg_color);
        $this->assertEquals('background-color: #1e1b4b;', $client->background_style);

        // Atualiza para gradiente
        $gradient = 'linear-gradient(135deg, #022c22 0%, #064e3b 100%)';
        $response = $this->actingAs($user)->patch(route('clients.update', $client), [
            'name' => 'Cliente Para Atualizar',
            'bg_type' => 'gradient',
            'bg_gradient' => $gradient,
        ]);

        $response->assertRedirect();
        $client->refresh();
        $this->assertEquals('gradient', $client->bg_type);
        $this->assertEquals($gradient, $client->bg_gradient);
        $this->assertEquals("background: {$gradient};", $client->background_style);
    }
}
