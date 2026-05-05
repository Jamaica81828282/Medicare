<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    // ── Display Page ──────────────────────────────────────────────────

    /**
     * GET /queue/display
     * The fullscreen TV page — no auth required.
     */
    public function display()
    {
        return view('queue.display');
    }

    // ── Polling endpoint ─────────────────────────────────────────────

    /**
     * GET /queue/display/poll
     * Called by the TV page every 4 seconds.
     * Returns JSON: { now_serving, queue[] }
     */
    public function poll()
    {
        $serving = QueueTicket::nowServing();
        $queue   = QueueTicket::readyQueue();

        return response()->json([
            'now_serving'      => $serving?->queue_number,
            'now_serving_name' => $serving?->customer_name,
            'queue'            => $queue->map(fn($t) => [
                'queue_number'  => $t->queue_number,
                'customer_name' => $t->customer_name,
            ]),
        ]);
    }

    // ── Cashier: list tickets for the sidebar ────────────────────────

    /**
     * GET /cashier/queue
     * Returns today's paid + serving tickets for the cashier sidebar.
     */
    public function cashierQueue()
    {
        $tickets = QueueTicket::whereIn('status', ['paid', 'serving'])
                              ->where('queue_date', now()->toDateString())
                              ->orderBy('daily_sequence')
                              ->get();

        return response()->json($tickets);
    }

    // ── Cashier: Call for Pickup ─────────────────────────────────────

    /**
     * POST /cashier/queue/call
     * Body: { ticket_id }
     * Marks the ticket as "serving" (shows on display screen).
     * Any previously "serving" ticket is moved to "done".
     */
    public function call(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queue_tickets,id']);

        DB::transaction(function () use ($request) {
            // Archive the currently serving ticket
            QueueTicket::where('status', 'serving')
                       ->where('queue_date', now()->toDateString())
                       ->update(['status' => 'done', 'done_at' => now()]);

            // Call the new ticket
            QueueTicket::where('id', $request->ticket_id)
                       ->update(['status' => 'serving', 'called_at' => now()]);
        });

        $ticket = QueueTicket::find($request->ticket_id);

        return response()->json([
            'success'      => true,
            'queue_number' => $ticket->queue_number,
        ]);
    }

    // ── Cashier: Mark Done ───────────────────────────────────────────

    /**
     * POST /cashier/queue/done
     * Body: { ticket_id }
     * Moves a serving or paid ticket to done.
     */
    public function done(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queue_tickets,id']);

        QueueTicket::where('id', $request->ticket_id)
                   ->update(['status' => 'done', 'done_at' => now()]);

        return response()->json(['success' => true]);
    }

    // ── Cashier: Skip ────────────────────────────────────────────────

    /**
     * POST /cashier/queue/skip
     * Body: { ticket_id }
     */
    public function skip(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queue_tickets,id']);

        QueueTicket::where('id', $request->ticket_id)
                   ->update(['status' => 'skipped']);

        return response()->json(['success' => true]);
    }

    // ── Manual reset (admin/cashier) ─────────────────────────────────

    /**
     * POST /cashier/queue/reset
     * Marks ALL of today's non-done tickets as done.
     * Use at end of day or for manual reset.
     */
    public function reset(Request $request)
    {
        QueueTicket::where('queue_date', now()->toDateString())
                   ->whereNotIn('status', ['done', 'skipped'])
                   ->update(['status' => 'done', 'done_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Queue reset successfully.']);
    }
}