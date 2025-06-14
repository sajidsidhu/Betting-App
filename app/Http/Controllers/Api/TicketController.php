<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Ticket;
use App\Jobs\ProcessTicketOCR;
use Illuminate\Http\JsonResponse;
use App\Traits\HandlesFileUploads;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\BetResource;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\TicketResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\TicketStoreRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    use HandlesFileUploads, AuthorizesRequests;

    public function store(TicketStoreRequest $request): JsonResponse
    {
        try {
            // If ticket_id is provided, handle as re-upload
            if ($request->has('ticket_id')) {
                $existingTicket = Ticket::find($request->ticket_id);
                
                // Delete existing ticket (this will handle bets and image deletion)
                $this->delete($existingTicket);
            }

            // Upload and store the ticket image
            $imagePath = $this->uploadFile(
                $request->file('ticket_image'),
                'tickets'
            );

            // Create the ticket record
            $ticket = Auth::user()->tickets()->create([
                'image_path' => $imagePath,
                'status' => 'pending',
                'is_favorite' => $request->has('ticket_id') ? $existingTicket->is_favorite : false
            ]);

            // Dispatch job to process the ticket
            ProcessTicketOCR::dispatch($ticket);

            $message = $request->has('ticket_id') ? 
                'Ticket re-uploaded successfully and is being processed' :
                'Ticket uploaded successfully and is being processed';

            return response()->json([
                'message' => $message,
                'ticket_id' => $ticket->id
            ]);

        } catch (\Throwable $th) {
            report($th);
            
            return response()->json([
                'message' => 'Failed to process ticket',
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

    public function favorites(): AnonymousResourceCollection
    {
        $tickets = Ticket::where('user_id', Auth::id())
            ->where('is_favorite', true)
            ->with('bets')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
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

    /**
     * Clear user's ticket history (soft delete)
     */
    public function clearHistory(): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get all non-pending tickets for the user
            $tickets = Ticket::where('user_id', Auth::id())
                ->whereNotIn('status', ['pending'])
                ->get();

            foreach ($tickets as $ticket) {
                // Soft delete bets first
                $ticket->bets()->delete();
                // Soft delete the ticket
                $ticket->delete();
            }

            DB::commit();

            return response()->json([
                'message' => 'History cleared successfully',
                'count' => $tickets->count()
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return response()->json([
                'message' => 'Failed to clear history',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted ticket and its bets
     */
    public function restore(int $ticketId): JsonResponse
    {
        try {
            $ticket = Ticket::onlyTrashed()
                ->where('id', $ticketId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            DB::beginTransaction();

            // Restore the bets first
            $ticket->bets()->restore();
            // Restore the ticket
            $ticket->restore();

            DB::commit();

            return response()->json([
                'message' => 'Ticket restored successfully',
                'ticket' => new TicketResource($ticket->load('bets'))
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            
            if ($th instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'message' => 'Ticket not found'
                ], 404);
            }

            report($th);
            return response()->json([
                'message' => 'Failed to restore ticket',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get list of deleted tickets that can be restored
     */
    public function deleted(): AnonymousResourceCollection
    {
        $tickets = Ticket::onlyTrashed()
            ->where('user_id', Auth::id())
            ->with(['bets' => function ($query) {
                $query->withTrashed();
            }])
            ->orderBy('deleted_at', 'desc')
            ->paginate(10);

        return TicketResource::collection($tickets);
    }

    public function delete(Ticket $ticket): JsonResponse
    {
        try {
            // Authorize the deletion
            $this->authorize('delete', $ticket);

            // Start database transaction
            DB::beginTransaction();

            // Delete associated bets first
            $ticket->bets()->forceDelete();
            // Delete the ticket image file            
            $this->deleteFile($ticket->image_path);
            // Delete the ticket record
            $ticket->forceDelete();

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'Ticket and all associated data deleted successfully'
            ]);

        } catch (\Throwable $th) {

            // Rollback in case of error
            DB::rollBack();

            report($th);
            return response()->json([
                'message' => 'Failed to delete ticket',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }
}
