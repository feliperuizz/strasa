<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private User $member;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Strasa Agência',
            'slug' => 'strasa-agencia',
        ]);

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Admin Financeiro',
            'email' => 'admin_fin@strasa.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->member = User::create([
            'company_id' => $this->company->id,
            'name' => 'João Colaborador',
            'email' => 'joao_fin@strasa.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_MEMBER,
        ]);

        $this->client = Client::create([
            'company_id' => $this->company->id,
            'name' => 'Cliente Financeiro',
            'slug' => 'cliente-financeiro',
            'segment' => 'Marketing',
        ]);
    }

    public function test_collaborator_cannot_access_financial_dashboard(): void
    {
        $response = $this->actingAs($this->member)->get(route('financial.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_financial_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('financial.index'));
        $response->assertStatus(200);
        $response->assertSee('Gestão Financeira');
        $response->assertSee('Recebido no Mês');
        $response->assertSee('A Receber');
        $response->assertSee('Em Atraso');
    }

    public function test_admin_can_create_payment_and_calculate_metrics(): void
    {
        $response = $this->actingAs($this->admin)->post(route('financial.store'), [
            'client_id' => $this->client->id,
            'title' => 'Mensalidade Agosto',
            'amount' => '2500.00',
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => Payment::STATUS_PENDING,
            'payment_method' => Payment::METHOD_PIX,
            'reference_month' => now()->format('Y-m'),
            'recurrence' => 'monthly',
            'notes' => 'Contrato anual recorrente',
        ]);

        $response->assertRedirect();

        $payment = Payment::where('title', 'Mensalidade Agosto')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(2500.00, (float) $payment->amount);
        $this->assertEquals($this->client->id, $payment->client_id);
        $this->assertEquals($this->company->id, $payment->company_id);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->status);

        // Acessar tela financeira e verificar presença da cobrança
        $viewResponse = $this->actingAs($this->admin)->get(route('financial.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Mensalidade Agosto');
        $viewResponse->assertSee('2.500,00');
    }

    public function test_admin_can_mark_payment_as_paid(): void
    {
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'title' => 'Consultoria Estratégica',
            'amount' => 1800.00,
            'due_date' => now()->toDateString(),
            'status' => Payment::STATUS_PENDING,
            'reference_month' => now()->format('Y-m'),
        ]);

        $response = $this->actingAs($this->admin)->post(route('financial.mark-paid', $payment), [
            'paid_at' => now()->toDateString(),
            'payment_method' => Payment::METHOD_PIX,
        ]);

        $response->assertRedirect();
        $payment->refresh();

        $this->assertEquals(Payment::STATUS_PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals(Payment::METHOD_PIX, $payment->payment_method);
    }

    public function test_admin_can_update_and_delete_payment(): void
    {
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'title' => 'Campanha Tráfego Pago',
            'amount' => 3000.00,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => Payment::STATUS_PENDING,
            'reference_month' => now()->format('Y-m'),
        ]);

        // 1. Atualizar
        $responseUpdate = $this->actingAs($this->admin)->patch(route('financial.update', $payment), [
            'client_id' => $this->client->id,
            'title' => 'Campanha Tráfego Pago - Revisado',
            'amount' => 3500.00,
            'due_date' => now()->addDays(12)->toDateString(),
            'status' => Payment::STATUS_PENDING,
            'payment_method' => Payment::METHOD_CREDIT_CARD,
        ]);

        $responseUpdate->assertRedirect();
        $payment->refresh();
        $this->assertEquals('Campanha Tráfego Pago - Revisado', $payment->title);
        $this->assertEquals(3500.00, (float) $payment->amount);

        // 2. Excluir
        $responseDelete = $this->actingAs($this->admin)->delete(route('financial.destroy', $payment));
        $responseDelete->assertRedirect();

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }
}
