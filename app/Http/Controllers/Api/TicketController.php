<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Ticket;
use App\Jobs\ProcessTicketOCR;
use Illuminate\Http\JsonResponse;
use App\Traits\HandlesFileUploads;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\BetResource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\TicketResource;
use App\Http\Requests\TicketStoreRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    use HandlesFileUploads, AuthorizesRequests;

    public function store(TicketStoreRequest $request): JsonResponse
    {
        try {
            // Upload and store the ticket image
            $imagePath = $this->uploadFile(
                $request->file('ticket_image'),
                'tickets'
            );

            // Create the ticket record
            $ticket = Auth::user()->tickets()->create([
                'image_path' => $imagePath
            ]);

            // Dispatch job to process the ticket
            ProcessTicketOCR::dispatch($ticket);

            return response()->json([
                'message' => 'Ticket uploaded successfully and is being processed',
                'ticket_id' => $ticket->id
            ]);
        } catch (\Throwable $th) {
            report($th);
            return response()->json([
                'message' => 'Failed to upload ticket',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }

    public function index(): AnonymousResourceCollection
    {
        $tickets = Ticket::where('user_id', Auth::id())
            ->select('tickets.*')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return TicketResource::collection($tickets);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load('bets');

        return response()->json([
            'ticket' => new TicketResource($ticket),
            'bets' => BetResource::collection($ticket->bets),
            'image_url' => $this::getFileUrl($ticket->image_path)
        ]);
    }

    public function  history(): AnonymousResourceCollection
    {
        $tickets = Ticket::where('user_id', Auth::id())
            ->whereIn('status', ['lost', 'all_won'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return TicketResource::collection($tickets);
    }

    public function toggleFavorite(Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $ticket->update([
            'is_favorite' => !$ticket->is_favorite
        ]);

        return response()->json([
            'message' => $ticket->is_favorite ? 'Marked as favorite' : 'Removed from favorites',
            'is_favorite' => $ticket->is_favorite
        ]);
    }
}
