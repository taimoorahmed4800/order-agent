<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgentController extends Controller
{
    private const KEEP_TURNS = 6; // how many past turns the AI remembers

    public function __construct(private OrderAgent $agent)
    {
    }

    public function show(Request $request)
    {
        return view('agent', [
            'transcript' => $request->session()->get('transcript', []),
            'orders'     => Order::orderBy('order_number')->get(),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $session    = $request->session();
        $turns      = $session->get('turns', []);       // API messages, grouped per turn
        $transcript = $session->get('transcript', []);  // what the page shows

        $transcript[] = ['role' => 'user', 'text' => $data['message'], 'tools' => []];

        // dd($turns,$transcript);

        try {
            // Flatten the saved turns into one list of API messages
            $history = array_merge(...$turns ?: [[]]);

            $result = $this->agent->reply($history, $data['message']);
            $turns[]      = $result['new_messages'];
            $transcript[] = ['role' => 'assistant', 'text' => $result['answer'], 'tools' => $result['tools']];
            
        } catch (Throwable $e) {
            Log::error('Agent failed: '.$e->getMessage());

            $transcript[] = ['role' => 'assistant', 'text' => 'Sorry, something went wrong. Please try again in a moment.', 'tools' => []];
        }

        $session->put('turns', array_slice($turns, -self::KEEP_TURNS));
        $session->put('transcript', $transcript);

        return redirect()->route('agent.show');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(['turns', 'transcript']);

        return redirect()->route('agent.show');
    }
}
