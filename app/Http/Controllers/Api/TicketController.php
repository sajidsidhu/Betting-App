<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketStoreRequest;
use App\Jobs\ProcessTicketOCR;
use App\Models\Ticket;
use App\Traits\HandlesFileUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    use HandlesFileUploads;

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

    public function index(): JsonResponse
    {
        $tickets = Auth::user()->tickets()
            ->latest()
            ->paginate(10);

        return response()->json($tickets);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load('bets');
        
        return response()->json([
            'ticket' => $ticket,
            'image_url' => $this->getFileUrl($ticket->image_path)
        ]);
    }

    public function history(): JsonResponse
    {
        $tickets = Auth::user()->tickets()
            ->whereNotIn('status', ['pending'])
            ->with('bets')
            ->latest()
            ->paginate(10);

        return response()->json($tickets);
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
