<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $companyId = $request->user()->company_id;
        $today = now()->toDateString();

        // 1. Clientes ativos para os formulários e filtros
        $clients = Client::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        // 2. Filtros
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $selectedClient = $request->input('client_id');
        $selectedStatus = $request->input('status');
        $selectedMethod = $request->input('method');
        $search = $request->input('search');

        // 3. Query Principal de Pagamentos
        $paymentsQuery = Payment::where('company_id', $companyId)
            ->with(['client', 'creator']);

        if ($selectedMonth && $selectedMonth !== 'all') {
            $startDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromFormat('Y-m', $selectedMonth)->endOfMonth()->toDateString();

            $paymentsQuery->where(function ($q) use ($selectedMonth, $startDate, $endDate) {
                $q->where('reference_month', $selectedMonth)
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->whereNull('reference_month')
                            ->whereBetween('due_date', [$startDate, $endDate]);
                    });
            });
        }

        if (!empty($selectedClient)) {
            $paymentsQuery->where('client_id', $selectedClient);
        }

        if (!empty($selectedStatus)) {
            if ($selectedStatus === 'late') {
                $paymentsQuery->where('status', Payment::STATUS_PENDING)
                    ->whereDate('due_date', '<', $today);
            } elseif ($selectedStatus === 'pending') {
                $paymentsQuery->where('status', Payment::STATUS_PENDING)
                    ->whereDate('due_date', '>=', $today);
            } else {
                $paymentsQuery->where('status', $selectedStatus);
            }
        }

        if (!empty($selectedMethod)) {
            $paymentsQuery->where('payment_method', $selectedMethod);
        }

        if (!empty($search)) {
            $paymentsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        // Ordenação inteligente: Atrasados e Pendentes com vencimento mais próximo primeiro, depois pagos
        $payments = (clone $paymentsQuery)
            ->orderByRaw("CASE 
                WHEN status = 'pending' AND due_date < '{$today}' THEN 1
                WHEN status = 'pending' THEN 2
                WHEN status = 'paid' THEN 3
                ELSE 4 END")
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        // 4. Métricas / KPIs para o período selecionado
        $basePeriodQuery = Payment::where('company_id', $companyId);
        if ($selectedMonth && $selectedMonth !== 'all') {
            $startDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromFormat('Y-m', $selectedMonth)->endOfMonth()->toDateString();

            $basePeriodQuery->where(function ($q) use ($selectedMonth, $startDate, $endDate) {
                $q->where('reference_month', $selectedMonth)
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->whereNull('reference_month')
                            ->whereBetween('due_date', [$startDate, $endDate]);
                    });
            });
        }

        $allPeriodPayments = $basePeriodQuery->get();

        $receivedAmount = $allPeriodPayments->where('status', Payment::STATUS_PAID)->sum('amount');
        $pendingAmount = $allPeriodPayments->where('status', Payment::STATUS_PENDING)
            ->filter(fn ($p) => !$p->isLate())
            ->sum('amount');
        $lateAmount = $allPeriodPayments->where('status', Payment::STATUS_PENDING)
            ->filter(fn ($p) => $p->isLate())
            ->sum('amount');
        $projectedTotal = $receivedAmount + $pendingAmount + $lateAmount;
        $collectionRate = $projectedTotal > 0 ? round(($receivedAmount / $projectedTotal) * 100) : 0;
        $totalAllTime = Payment::where('company_id', $companyId)->where('status', Payment::STATUS_PAID)->sum('amount');

        $stats = [
            'received' => $receivedAmount,
            'pending' => $pendingAmount,
            'late' => $lateAmount,
            'projected' => $projectedTotal,
            'collection_rate' => $collectionRate,
            'total_all_time' => $totalAllTime,
            'late_count' => $allPeriodPayments->filter(fn ($p) => $p->isLate())->count(),
            'paid_count' => $allPeriodPayments->where('status', Payment::STATUS_PAID)->count(),
            'pending_count' => $allPeriodPayments->where('status', Payment::STATUS_PENDING)->filter(fn ($p) => !$p->isLate())->count(),
        ];

        // 5. Gráfico 1: Evolução Mensal do Faturamento (Últimos 12 meses)
        $revenueChartData = ['labels' => [], 'received' => [], 'pending' => []];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $monthKey = $m->format('Y-m');
            $startM = $m->copy()->startOfMonth()->toDateString();
            $endM = $m->copy()->endOfMonth()->toDateString();

            $revenueChartData['labels'][] = $m->translatedFormat('M/y');

            $monthPayments = Payment::where('company_id', $companyId)
                ->where(function ($q) use ($monthKey, $startM, $endM) {
                    $q->where('reference_month', $monthKey)
                        ->orWhere(function ($sub) use ($startM, $endM) {
                            $sub->whereNull('reference_month')
                                ->whereBetween('due_date', [$startM, $endM]);
                        });
                })->get();

            $revenueChartData['received'][] = (float) $monthPayments->where('status', Payment::STATUS_PAID)->sum('amount');
            $revenueChartData['pending'][] = (float) $monthPayments->where('status', '!=', Payment::STATUS_PAID)->where('status', '!=', Payment::STATUS_CANCELLED)->sum('amount');
        }

        // 6. Gráfico 2: Composição por Métodos de Pagamento (Valores Recebidos)
        $methodLabels = [];
        $methodValues = [];
        $paidPayments = Payment::where('company_id', $companyId)
            ->where('status', Payment::STATUS_PAID)
            ->get();

        foreach (Payment::METHODS as $key => $label) {
            $sum = (float) $paidPayments->where('payment_method', $key)->sum('amount');
            if ($sum > 0) {
                $methodLabels[] = $label;
                $methodValues[] = $sum;
            }
        }
        if ($paidPayments->whereNull('payment_method')->sum('amount') > 0) {
            $methodLabels[] = 'Outro / Não Definido';
            $methodValues[] = (float) $paidPayments->whereNull('payment_method')->sum('amount');
        }

        $methodChartData = [
            'labels' => $methodLabels,
            'values' => $methodValues,
        ];

        // 7. Top Clientes por Faturamento no Período
        $topClients = $clients->map(function ($client) use ($allPeriodPayments) {
            $clientPayments = $allPeriodPayments->where('client_id', $client->id);
            $client->period_paid = (float) $clientPayments->where('status', Payment::STATUS_PAID)->sum('amount');
            $client->period_pending = (float) $clientPayments->where('status', '!=', Payment::STATUS_PAID)->where('status', '!=', Payment::STATUS_CANCELLED)->sum('amount');
            $client->period_total = $client->period_paid + $client->period_pending;
            return $client;
        })->sortByDesc('period_total')->take(5);

        return view('financial.index', [
            'clients' => $clients,
            'payments' => $payments,
            'stats' => $stats,
            'selectedMonth' => $selectedMonth,
            'selectedClient' => $selectedClient,
            'selectedStatus' => $selectedStatus,
            'selectedMethod' => $selectedMethod,
            'search' => $search,
            'revenueChartData' => $revenueChartData,
            'methodChartData' => $methodChartData,
            'topClients' => $topClients,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::in([Payment::STATUS_PENDING, Payment::STATUS_PAID, Payment::STATUS_CANCELLED])],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(Payment::METHODS))],
            'reference_month' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'recurrence' => ['nullable', 'string', Rule::in(['one_time', 'monthly'])],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if (empty($data['reference_month']) && !empty($data['due_date'])) {
            $data['reference_month'] = Carbon::parse($data['due_date'])->format('Y-m');
        }

        if ($data['status'] === Payment::STATUS_PAID && empty($data['paid_at'])) {
            $data['paid_at'] = now()->toDateString();
        } elseif ($data['status'] !== Payment::STATUS_PAID) {
            $data['paid_at'] = null;
        }

        $data['company_id'] = $companyId;
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $disk = config('filesystems.attachments_disk', 'local');
            $path = $request->file('attachment')->store("company-{$companyId}/financial", $disk);
            $data['attachment_path'] = $path;
            $data['attachment_disk'] = $disk;
        }

        Payment::create($data);

        return back()->with('status', 'Cobrança cadastrada com sucesso!');
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($payment->company_id === $request->user()->company_id, 403);

        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::in([Payment::STATUS_PENDING, Payment::STATUS_PAID, Payment::STATUS_CANCELLED])],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(Payment::METHODS))],
            'reference_month' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'recurrence' => ['nullable', 'string', Rule::in(['one_time', 'monthly'])],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if (empty($data['reference_month']) && !empty($data['due_date'])) {
            $data['reference_month'] = Carbon::parse($data['due_date'])->format('Y-m');
        }

        if ($data['status'] === Payment::STATUS_PAID && empty($data['paid_at'])) {
            $data['paid_at'] = now()->toDateString();
        } elseif ($data['status'] !== Payment::STATUS_PAID) {
            $data['paid_at'] = null;
        }

        if ($request->hasFile('attachment')) {
            $disk = config('filesystems.attachments_disk', 'local');
            if ($payment->attachment_path) {
                Storage::disk($payment->attachment_disk ?: $disk)->delete($payment->attachment_path);
            }
            $path = $request->file('attachment')->store("company-{$companyId}/financial", $disk);
            $data['attachment_path'] = $path;
            $data['attachment_disk'] = $disk;
        }

        $payment->update($data);

        return back()->with('status', 'Cobrança atualizada com sucesso!');
    }

    public function markPaid(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($payment->company_id === $request->user()->company_id, 403);

        $validated = $request->validate([
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(Payment::METHODS))],
        ]);

        $payment->update([
            'status' => Payment::STATUS_PAID,
            'paid_at' => $validated['paid_at'] ?? now()->toDateString(),
            'payment_method' => $validated['payment_method'] ?? ($payment->payment_method ?: Payment::METHOD_PIX),
        ]);

        return back()->with('status', 'Pagamento confirmado com sucesso! 🎉');
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($payment->company_id === $request->user()->company_id, 403);

        if ($payment->attachment_path) {
            Storage::disk($payment->attachment_disk ?: config('filesystems.attachments_disk', 'local'))
                ->delete($payment->attachment_path);
        }

        $payment->delete();

        return back()->with('status', 'Registro financeiro excluído com sucesso.');
    }
}
