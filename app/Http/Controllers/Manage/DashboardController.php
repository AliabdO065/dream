<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Modules\Forms\Submission;
use App\Support\Stats;
use Inertia\Inertia;

/** The client's overview page: the numbers that matter, a 30-day chart, and the latest leads. */
class DashboardController extends Controller
{
    public function index(Client $client)
    {
        // Submission is tenant-scoped (BelongsToClient + the tenant pinned by the manage middleware).
        $perDay = Submission::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
        $series = Stats::dailySeries($perDay, 30);

        $sections = $client->sections()->get(['id', 'is_enabled']);

        return Inertia::render('Manage/Dashboard', [
            'kpis' => [
                'leads' => Submission::count(),
                'unread' => Submission::whereNull('read_at')->count(),
                'leads7' => array_sum(array_column(array_slice($series, -7), 'count')),
                'leads30' => array_sum(array_column($series, 'count')),
                'sections' => $sections->count(),
                'sections_on' => $sections->where('is_enabled', true)->count(),
            ],
            'series' => $series,
            'recentLeads' => Submission::latest()->limit(6)->get()->map(fn ($l) => [
                'id' => $l->id, 'name' => $l->payload['name'] ?? '—', 'phone' => $l->payload['phone'] ?? null,
                'email' => $l->payload['email'] ?? null, 'message' => \Illuminate\Support\Str::limit($l->payload['message'] ?? '', 90),
                'read' => (bool) $l->read_at, 'created' => $l->created_at?->diffForHumans(),
            ])->all(),
            'languages' => collect($client->siteLocales())->map(fn ($l) => strtoupper($l))->all(),
            // "getting started" steps, each linking to the screen that fixes it
            'checklist' => [
                ['label' => 'Add your business phone and email', 'done' => (bool) ($client->phone && $client->email), 'route' => 'manage.profile.edit'],
                ['label' => 'Upload your logo', 'done' => (bool) $client->logo_path, 'route' => 'manage.profile.edit'],
                ['label' => 'Write a short tagline (used by search engines)', 'done' => (bool) $client->tagline, 'route' => 'manage.profile.edit'],
                ['label' => 'Add your address', 'done' => (bool) $client->address_line1, 'route' => 'manage.profile.edit'],
                ['label' => 'Publish at least one section', 'done' => $sections->where('is_enabled', true)->isNotEmpty(), 'route' => 'manage.sections.index'],
                ['label' => 'Add a contact form to receive leads', 'done' => $client->sections()->where('type', 'contact_form')->where('is_enabled', true)->exists(), 'route' => 'manage.sections.index'],
            ],
        ]);
    }
}
