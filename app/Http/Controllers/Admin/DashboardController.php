<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Modules\Forms\Submission;
use App\Support\Stats;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $leads = fn () => Submission::withoutGlobalScopes();

        $perDay = $leads()->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
        $series = Stats::dailySeries($perDay, 30);

        $clientCount = Client::count();
        $active = Client::where('status', 'active')->count();

        return Inertia::render('Admin/Dashboard', [
            'kpis' => [
                'clients' => $clientCount,
                'active' => $active,
                'inactive' => $clientCount - $active,
                'users' => User::count(),
                'leads' => $leads()->count(),
                'unread' => $leads()->whereNull('read_at')->count(),
                'leads30' => array_sum(array_column($series, 'count')),
            ],
            'series' => $series,
            'attention' => $this->attention(),
            'recentLeads' => $leads()->with('client:id,name,slug')->latest()->limit(6)->get()->map(fn ($l) => [
                'id' => $l->id, 'name' => $l->payload['name'] ?? '—', 'contact' => $l->payload['phone'] ?? ($l->payload['email'] ?? ''),
                'client' => $l->client?->name, 'client_slug' => $l->client?->slug, 'read' => (bool) $l->read_at, 'created' => $l->created_at?->diffForHumans(),
            ])->all(),
        ]);
    }

    /**
     * What someone should act on — the overview's own job (the Clients page is the full list).
     * One row per client and problem, most urgent first: [slug, name, reason, count?]. Reasons are translated in Vue.
     */
    private function attention(): array
    {
        $old = now()->subDays(2);
        $waiting = Submission::withoutGlobalScopes()->whereNull('read_at')->where('created_at', '<', $old)
            ->selectRaw('client_id, COUNT(*) as c')->groupBy('client_id')->pluck('c', 'client_id');

        $rows = [];
        foreach (Client::withCount(['users as owners_count' => fn ($q) => $q->where('client_user.role', 'owner')])->orderBy('name')->get() as $c) {
            $row = fn (string $reason, ?int $count = null) => ['slug' => $c->slug, 'name' => $c->name, 'reason' => $reason, 'count' => $count];
            if ($c->status === 'suspended') {
                $rows[] = $row('suspended');
            } elseif ($c->status === 'draft') {
                $rows[] = $row('draft');
            }
            if (isset($waiting[$c->id])) {
                $rows[] = $row('waiting', (int) $waiting[$c->id]);
            }
            if (! $c->isHome() && $c->owners_count === 0) {
                $rows[] = $row('no_owner');
            }
            if (! $c->phone && ! $c->email) {
                $rows[] = $row('no_contact');
            }
        }

        $order = ['waiting' => 0, 'no_owner' => 1, 'suspended' => 2, 'draft' => 3, 'no_contact' => 4];
        usort($rows, fn ($a, $b) => $order[$a['reason']] <=> $order[$b['reason']]);

        return array_slice($rows, 0, 8);
    }
}
