<?php

namespace App\Livewire\Cash;

use App\Models\CashCategory;
use App\Models\CashTransaction;
use App\Models\Location;
use App\Services\DocumentNumberService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $typeFilter = '';

    public string $categoryFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showModal = false;

    public string $type = 'out';

    public ?int $cashCategoryId = null;

    public string $method = 'cash';

    public $amount = 0;

    public string $description = '';

    public string $occurredAt = '';

    public $attachment = null;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function create(): void
    {
        $this->reset(['cashCategoryId', 'amount', 'description', 'attachment']);
        $this->type = 'out';
        $this->method = 'cash';
        $this->occurredAt = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:in,out',
            'cashCategoryId' => 'required|exists:cash_categories,id',
            'method' => 'required|in:cash,bank',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'occurredAt' => 'required|date',
            'attachment' => 'nullable|image|max:2048',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $locationId = auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id');

        $path = $this->attachment ? $this->attachment->store('cash-proofs', 'public') : null;

        CashTransaction::create([
            'number' => app(DocumentNumberService::class)->next($this->type === 'in' ? 'cash_in' : 'cash_out', $locationId),
            'location_id' => $locationId,
            'user_id' => auth()->id(),
            'cash_category_id' => $this->cashCategoryId,
            'type' => $this->type,
            'method' => $this->method,
            'amount' => $this->amount,
            'description' => $this->description,
            'occurred_at' => $this->occurredAt,
            'attachment_path' => $path,
        ]);

        $this->showModal = false;
        $this->dispatch('notify', message: 'Transaksi kas tersimpan.', type: 'success');
    }

    public function render()
    {
        $query = CashTransaction::query()
            ->with(['category', 'shift'])
            ->whereBetween('occurred_at', [Carbon::parse($this->dateFrom)->startOfDay(), Carbon::parse($this->dateTo)->endOfDay()])
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('cash_category_id', $this->categoryFilter));

        $totalIn = (clone $query)->where('type', 'in')->sum('amount');
        $totalOut = (clone $query)->where('type', 'out')->sum('amount');

        return view('livewire.cash.index', [
            'transactions' => $query->latest('occurred_at')->paginate(25),
            'categories' => CashCategory::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'totalIn' => (float) $totalIn,
            'totalOut' => (float) $totalOut,
        ]);
    }
}
