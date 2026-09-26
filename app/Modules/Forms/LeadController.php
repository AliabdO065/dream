<?php

namespace App\Modules\Forms;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The client's leads inbox. All queries are scoped to the client automatically
 * (BelongsToClient + the tenant pinned by the manage middleware); a lead id from another client is a 404.
 */
class LeadController
{
    public function index(Request $request, Client $client)
    {
        $unreadOnly = $request->query('filter') === 'unread';

        $leads = Submission::query()
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->when($request->query('q'), fn ($q, $s) => $q->where('payload', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $s) . '%'))
            ->latest()->paginate(20)->withQueryString();

        return Inertia::render('Manage/Leads/Index', [
            'leads' => $leads->through(fn ($l) => $this->present($l)),
            'unread' => Submission::whereNull('read_at')->count(),
            'total' => Submission::count(),
            'filters' => ['filter' => $unreadOnly ? 'unread' : 'all', 'q' => (string) $request->query('q', '')],
        ]);
    }

    /** Opening a lead marks it as read. */
    public function show(Client $client, int $lead)
    {
        $lead = Submission::findOrFail($lead);
        if (! $lead->read_at) {
            $lead->update(['read_at' => now()]);
        }

        return Inertia::render('Manage/Leads/Show', ['lead' => $this->present($lead, full: true)]);
    }

    /** POST read=1 marks as read, read=0 marks as unread. */
    public function mark(Request $request, Client $client, int $lead)
    {
        $lead = Submission::findOrFail($lead);
        $lead->update(['read_at' => $request->boolean('read') ? ($lead->read_at ?? now()) : null]);

        return back();
    }

    public function markAllRead(Client $client)
    {
        Submission::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', __('All leads marked as read.'));
    }

    public function destroy(Client $client, int $lead)
    {
        Submission::findOrFail($lead)->delete();

        return redirect()->route('manage.leads.index', $client)->with('status', __('Lead deleted.'));
    }

    private function present(Submission $l, bool $full = false): array
    {
        $p = $l->payload;

        return [
            'id' => $l->id,
            'name' => $p['name'] ?? '—',
            'phone' => $p['phone'] ?? null,
            'email' => $p['email'] ?? null,
            'message' => $full ? ($p['message'] ?? null) : \Illuminate\Support\Str::limit($p['message'] ?? '', 110),
            'read' => (bool) $l->read_at,
            'read_at' => $l->read_at?->translatedFormat('j M Y · H:i'),
            'created' => $l->created_at?->diffForHumans(),
            'created_at' => $l->created_at?->translatedFormat('j M Y · H:i'),
        ];
    }
}
