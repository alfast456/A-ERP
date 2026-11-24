<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderReviseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessReviseOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Order $order;

    public array $revisionItems;

    public User $requestedBy;

    public ?User $approvedBy;

    public string $reasonNotes;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Order $order,
        array $revisionItems,
        User $requestedBy,
        ?User $approvedBy = null,
        string $reasonNotes = ''
    ) {
        $this->order = $order;
        $this->revisionItems = $revisionItems;
        $this->requestedBy = $requestedBy;
        $this->approvedBy = $approvedBy;
        $this->reasonNotes = $reasonNotes;
    }

    /**
     * Execute the job.
     */
    public function handle(OrderReviseService $service): void
    {
        $service->applyRevision(
            $this->order,
            $this->revisionItems,
            $this->requestedBy,
            $this->approvedBy,
            $this->reasonNotes
        );
    }
}
