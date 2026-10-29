<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Status Agent</title>
    <style>
        :root { --bg:#f4f6fb; --card:#fff; --text:#1d2433; --muted:#6b7280; --brand:#2e5c9a; --line:#e3e7ef; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:var(--bg); color:var(--text); }
        .wrap { max-width: 860px; margin: 0 auto; padding: 24px 16px 60px; }
        h1 { margin: 0 0 4px; font-size: 24px; color: var(--brand); }
        .sub { color: var(--muted); margin-bottom: 16px; }
        .card { background: var(--card); border:1px solid var(--line); border-radius:10px; padding:16px; margin-bottom:16px; }
        .msg { margin: 10px 0; display:flex; }
        .msg.user { justify-content:flex-end; }
        .bubble { max-width: 82%; padding:10px 14px; border-radius:12px; line-height:1.45; }
        .user .bubble { background: var(--brand); color:#fff; }
        .assistant .bubble { background:#eef2f9; }
        .tools { margin-top:8px; font-size:12px; color:var(--muted); border-top:1px dashed #c9d2e3; padding-top:6px; }
        .tools code { background:#fff; padding:1px 5px; border-radius:4px; }
        form.send { display:flex; gap:8px; }
        input[type=text] { flex:1; padding:10px; border:1px solid var(--line); border-radius:6px; font: inherit; }
        button { background: var(--brand); color:#fff; border:0; padding:10px 16px; border-radius:6px; font: inherit; cursor:pointer; }
        button.secondary { background:#fff; color:var(--brand); border:1px solid var(--brand); }
        .err { color:#b42318; font-size:13px; margin-top:6px; }
        table { width:100%; border-collapse: collapse; font-size:13px; }
        th, td { text-align:left; padding:6px 8px; border-bottom:1px solid var(--line); }
        .muted { color: var(--muted); font-size:13px; }
        summary { cursor:pointer; font-weight:600; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Order Status Agent</h1>
    <div class="sub">Laravel + AI tool calling: the AI decides which PHP function to run.</div>

    <div class="card">
        <details>
            <summary>Demo orders (use these to test)</summary>
            <table style="margin-top:8px">
                <tr><th>Order</th><th>Email</th><th>Status</th><th>Items</th></tr>
                @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->customer_email }}</td>
                        <td>{{ $order->status }}</td>
                        <td>{{ $order->items }}</td>
                    </tr>
                @endforeach
            </table>
        </details>
    </div>

    <div class="card">
        @forelse ($transcript as $item)
            <div class="msg {{ $item['role'] }}">
                <div class="bubble">
                    {!! nl2br(e($item['text'])) !!}

                    @if (! empty($item['tools']))
                        <div class="tools">
                            Tools used:
                            @foreach ($item['tools'] as $tool)
                                <div>
                                    <code>{{ $tool['name'] }}</code>
                                    ({{ collect($tool['args'])->map(fn ($v, $k) => $k.'='.(is_scalar($v) ? $v : json_encode($v)))->implode(', ') }})
                                    &rarr; {{ \Illuminate\Support\Str::limit(json_encode($tool['result'], JSON_UNESCAPED_UNICODE), 140) }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="muted">Say hello, or ask: "Where is my order SE-1001? My email is ali@example.com"</p>
        @endforelse
    </div>

    <form class="send" method="POST" action="{{ route('agent.send') }}">
        @csrf
        <input type="text" name="message" placeholder="Type your message..." value="{{ old('message') }}" autofocus required>
        <button type="submit">Send</button>
    </form>
    @error('message') <div class="err">{{ $message }}</div> @enderror

    <form method="POST" action="{{ route('agent.reset') }}" style="margin-top:12px">
        @csrf
        <button class="secondary" type="submit">Clear chat</button>
    </form>
</div>
</body>
</html>
