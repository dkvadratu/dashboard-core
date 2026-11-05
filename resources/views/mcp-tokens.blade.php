<x-filament-panels::page>
    <x-filament::section heading="Users MCP">
        @if (\Illuminate\Support\Facades\Route::has('mcp.users'))
            <p>Endpoint: <code>{{ route('mcp.users') }}</code></p>
        @endif
        <p>Use the Authorization: Bearer &lt;token&gt; header. Tokens belong to a separate AI user without admin access.</p>
        @if ($token = session()->pull('dashboard_mcp_token'))
            <p>Copy this token now. It will not be shown again.</p>
            <pre>{{ $token }}</pre>
        @endif
    </x-filament::section>
    {{ $this->table }}
</x-filament-panels::page>
