<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\ActivityLog;
use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\ChatHistory;
use App\Models\Member;
use App\Services\ActivityLogger;
use App\Services\HarvestCompareService;
use App\Services\HarvestInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\AI\AiConversationContext;

class HarvestReportController extends Controller
{
    public function __construct(
        private readonly HarvestInsightService $insights,
    ) {}

    /**
     * Farmer-facing list of their own harvest reports.
     * Reads the same harvest_reports table the admin page reads, so a report
     * recorded here is immediately visible to the admin ("synced").
     */
    public function farmerIndex(Request $request)
    {
        $query = HarvestReport::with('farmer')
            ->where('user_id', Auth::id());

        [$reports, $stats, $allReports, $yearOptions, $monthOptions] = $this->applyFiltersAndStats($query, $request);

        return view('farmer.harvests.index', compact('reports', 'stats', 'allReports', 'yearOptions', 'monthOptions'));
    }

    /**
     * Farmer-facing harvest report submission form (dashboard sub-page).
     *
     * The form starts from a crop batch: only admin-confirmed batches that
     * have not been reported yet are offered, and picking one auto-fills the
     * crop, season and land area. Yield, expenses and the date stay manual.
     */
    public function farmerCreate()
    {
        $cropTypes = Crop::where('is_category_placeholder', false)->orderBy('name')->pluck('name');

        $batches = FarmerCrop::with('crop')
            ->where('user_id', Auth::id())
            ->confirmed()
            ->whereNull('harvest_report_id')
            ->where('growth_stage', '!=', 'failed')
            ->orderByDesc('planting_date')
            ->get();

        return view('farmer.harvests.create', compact('cropTypes', 'batches'));
    }

    /**
     * Admin landing page: headline metrics plus the period / comparison /
     * farmer charts, and navigation into the supporting sub-pages. The raw
     * report list lives on `admin.harvests.reports`.
     *
     * The charts read from the same HarvestInsightService digest the AI page
     * sends to the model, so the picture on this page and the figures the
     * analysis quotes can never drift apart.
     */
    public function adminIndex(Request $request)
    {
        [, $stats] = $this->applyFiltersAndStats(HarvestReport::with(['farmer', 'member']), $request);

        return view('admin.harvests.index', [
            'stats'   => $stats,
            'context' => $this->insights->context(),
        ]);
    }

    /**
     * Full harvest history — the wet-vs-dry trend at page size, reached from
     * the landing page's Full view button. It was a dialog once, and a dialog
     * never had the room to read twenty periods at a glance, so it became a
     * page of its own: breadcrumbed back to the landing page, deliberately
     * absent from the sidebar (it is a view of that page, not a destination).
     */
    public function adminHistory()
    {
        $periods = $this->insights->historyPeriods();

        return view('admin.harvests.history', [
            'periods' => $periods,
            'from'    => $periods[0]['first_harvest'] ?? null,
            'to'      => $periods ? end($periods)['last_harvest'] : null,
        ]);
    }

    /**
     * Admin sub-page holding the full, filterable harvest report table.
     */
    public function reportsIndex(Request $request)
    {
        [$reports, $stats, $allReports, $yearOptions, $monthOptions] = $this->applyFiltersAndStats(
            HarvestReport::with(['farmer', 'member']),
            $request
        );

        $farmerOptions = $allReports->map(fn (HarvestReport $report) => $report->reporterName())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.harvests.reports', compact(
            'reports',
            'stats',
            'allReports',
            'farmerOptions',
            'yearOptions',
            'monthOptions'
        ));
    }

    /**
     * "Remove selected records" — the row-level half of removing wrong data.
     *
     * The officer ticks rows in the table (select-all covers every row the
     * toolbar is currently matching, across pages) and confirms once. Only
     * the listed ids go: the archived source file stays in Import Uploads so
     * a wrong removal is fixed by re-importing it, and planting batches tied
     * to a removed record survive untouched (the foreign key nulls itself)
     * and simply become reportable again.
     */
    public function removeRecords(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $raw = $request->input('ids', '');
        $ids = collect(is_array($raw) ? $raw : explode(',', (string) $raw))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('admin.harvests.reports')
                ->with('error', 'Tick at least one record first, then choose Remove selected.');
        }

        $removed = (int) HarvestReport::withTrashed()->whereIn('id', $ids)->forceDelete();

        if ($removed > 0) {
            ActivityLogger::log(
                ActivityLog::TYPE_HARVEST_DELETED,
                'Removed ' . $removed . ' harvest record' . ($removed > 1 ? 's' : '') . ' from All Harvest Reports',
                null,
                ['count' => $removed, 'ids' => $ids->all()]
            );
        }

        return redirect()
            ->route('admin.harvests.reports')
            ->with(
                'success',
                $removed > 0
                    ? number_format($removed) . ' record' . ($removed > 1 ? 's' : '') . ' removed.'
                    : 'Those records are no longer on the list.'
            );
    }

    /**
     * Archive selected records so they leave normal reports without being
     * permanently deleted.
     */
    public function archiveRecords(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $ids = collect(explode(',', (string) $request->input('ids', '')))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()
                ->route('admin.harvests.reports')
                ->with('error', 'Tick at least one record first, then choose Archive selected.');
        }

        $archived = (int) HarvestReport::whereIn('id', $ids)->delete();

        return redirect()
            ->route('admin.harvests.reports')
            ->with(
                'success',
                $archived > 0
                    ? number_format($archived) . ' record' . ($archived > 1 ? 's' : '') . ' archived.'
                    : 'Those records are already archived.'
            );
    }

    /**
     * Restore one report from the administrator's Archive.
     */
    public function restoreArchived(int $harvestReport): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $report = HarvestReport::onlyTrashed()->findOrFail($harvestReport);
        $report->restore();

        return redirect()
            ->route('admin.archive', ['type' => 'harvest-reports'])
            ->with('success', 'Harvest report restored.');
    }

    /**
     * AI Harvest Insights subpage.
     *
     * Rendered with the digest already in hand so the page can show exactly
     * which harvest records the analysis will draw from, before anything is
     * sent to the model.
     */
    public function aiIndex()
    {
        $context = $this->insights->context();
        $user = auth()->user();

        return view('admin.harvests.ai', [
            'context' => $context,
            'aiAvatar' => $user?->aiAvatarUrl() ?? \App\Services\AiAvatars::url(\App\Services\AiAvatars::defaultKey()),
            'aiAvatarKey' => $user?->ai_avatar && \App\Services\AiAvatars::has($user->ai_avatar)
                ? $user->ai_avatar
                : \App\Services\AiAvatars::defaultKey(),
            'aiAvatars' => \App\Services\AiAvatars::choices(),
        ]);
    }

    /**
     * Farmer Estimate vs Actual — planting estimate against filed yield,
     * for the signed-in farmer's own batches/reports only.
     */
    public function farmerCompare(Request $request, HarvestCompareService $compare)
    {
        $data = $compare->compare('farmer', Auth::id(), $request->query());

        return view('farmer.harvests.compare', $data);
    }

    /**
     * Admin Estimate vs Actual — the whole association, with a farmer filter.
     */
    public function adminCompare(Request $request, HarvestCompareService $compare)
    {
        $data = $compare->compare('admin', null, $request->query());

        return view('admin.harvests.compare', $data);
    }

    /**
     * Answer one chat question about the harvest digest. Returns JSON so the
     * chat can run its own loading and error states without a page round trip.
     *
     * Always answers with 200 + a `reply`: when the model is offline or
     * unusable, `source` reports `fallback` and the text comes from
     * deterministic PHP over the same records.
     */
    public function aiAsk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'              => ['required', 'string', 'min:1', 'max:500'],
            'messages'             => ['sometimes', 'array', 'max:20'],
            'messages.*.role'      => ['required', 'in:user,assistant'],
            'messages.*.content'   => ['required', 'string', 'max:2000'],
        ]);

        $history = collect($validated['messages'] ?? [])
            ->map(fn (array $turn) => [
                'role'    => $turn['role'],
                'content' => mb_substr($turn['content'], 0, 300),
            ])
            ->reject(fn (array $turn) => trim($turn['content']) === '')
            ->values()
            ->all();

        $result = $this->insights->ask(trim($validated['message']), $history);

        return response()->json([
            'source'         => $result['source'],
            'reply'          => $result['reply'],
            'clarification'  => $result['clarification'] ?? null,
            'context'        => $result['context'],
        ]);
    }

    public function adminAiHistories(): JsonResponse
    {
        $histories = auth()->user()
            ->chatHistories()
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'updated_at']);

        return response()->json([
            'histories' => $histories->map(fn (ChatHistory $history) => [
                'id' => $history->id,
                'title' => $history->title,
                'updated_at' => $history->updated_at->diffForHumans(),
            ])->values(),
        ]);
    }

    public function adminAiHistory(ChatHistory $chatHistory): JsonResponse
    {
        abort_unless($chatHistory->user_id === auth()->id(), 403);

        return response()->json([
            'messages' => $chatHistory->messages,
            'context' => $chatHistory->ai_context,
        ]);
    }

    public function saveAdminAiHistory(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['nullable', 'integer'],
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:4000'],
            'context' => ['nullable', 'array'],
        ]);

        $messages = array_values(array_filter(
            $request->input('messages'),
            fn (array $message) => in_array($message['role'], ['user', 'assistant'], true)
        ));

        $firstUser = collect($messages)->firstWhere('role', 'user');
        $payload = [
            'title' => mb_strimwidth((string) ($firstUser['content'] ?? 'Chat'), 0, 60, '…'),
            'messages' => $messages,
            'ai_context' => AiConversationContext::fromArray($request->input('context'))->toArray(),
        ];

        if ($request->filled('id')) {
            $history = auth()->user()->chatHistories()->findOrFail($request->integer('id'));
            $history->update($payload);
        } else {
            $history = auth()->user()->chatHistories()->create($payload);
        }

        return response()->json(['id' => $history->id, 'title' => $history->title]);
    }

    public function destroyAdminAiHistory(ChatHistory $chatHistory): JsonResponse
    {
        abort_unless($chatHistory->user_id === auth()->id(), 403);
        $chatHistory->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Shared filter + summary logic for both index pages.
     */
    protected function applyFiltersAndStats($query, Request $request): array
    {
        $search = trim((string) $request->query('q', ''));
        $crop = trim((string) $request->query('crop', ''));
        $season = trim((string) $request->query('season', ''));
        $farmer = trim((string) $request->query('farmer', ''));
        $year = trim((string) $request->query('year', ''));
        $month = trim((string) $request->query('month', ''));

        if ($search !== '') {
            $query->where('crop_type', 'like', "%{$search}%");
        }
        if ($crop !== '') {
            $query->where('crop_type', $crop);
        }
        if (in_array($season, ['wet', 'dry'], true)) {
            $query->where('season', $season);
        }
        if ($farmer !== '') {
            // A report's reporter is either an account or a member record, so
            // the filter has to reach both: account names in the query, member
            // names resolved here (a member's display name is composed from
            // name parts and does not exist as a single column).
            $memberIds = $this->memberIdsNamed($farmer);

            $query->where(function ($q) use ($farmer, $memberIds) {
                $q->whereHas('farmer', fn ($fq) => $fq->where('name', $farmer));

                if ($memberIds->isNotEmpty()) {
                    $q->orWhereIn('member_id', $memberIds);
                }
            });
        }
        if ($year !== '' && $year !== 'all') {
            $query->whereYear('harvest_date', $year);
        }
        if ($month !== '' && $month !== 'all') {
            $query->whereMonth('harvest_date', $month);
        }

        // Summary figures reflect the user's full dataset (before filters),
        // mirroring how the quotations/activity pages compute their stats.
        $allReports = collect(match (true) {
            Auth::user()->role === 'admin' => HarvestReport::with(['farmer', 'member'])->orderByDesc('harvest_date')->get(),
            default => HarvestReport::with(['farmer', 'member'])->where('user_id', Auth::id())->orderByDesc('harvest_date')->get(),
        });

        $stats = [
            'reports' => $allReports->count(),
            'yield' => SettingsHelper::formatYield((float) $allReports->sum('actual_yield_metric_tons')),
            'yield_atoms' => $allReports->sum('actual_yield_metric_tons'),
            'expenses' => (float) $allReports->sum('actual_total_expenses'),
            'area' => (float) $allReports->sum('land_area'),
            // Distinct reporters, not distinct accounts: member-filed history
            // counts the member as the farmer who reported it.
            'farmers' => $allReports->map(fn (HarvestReport $report) => $report->reporterKey())->unique()->count(),
        ];

        // Get unique years and months for filter dropdowns
        $yearOptions = $allReports->pluck('harvest_date')
            ->filter()
            ->map(fn ($d) => $d->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn ($y) => [(string) $y, (string) $y])
            ->all();

        $monthOptions = $allReports->pluck('harvest_date')
            ->filter()
            ->map(fn ($d) => $d->month)
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($m) => [(string) $m, Carbon::createFromDate(2000, $m, 1)->format('F')])
            ->all();

        $reports = $query->orderByDesc('harvest_date')->get();

        return [$reports, $stats, $allReports, $yearOptions, $monthOptions];
    }

    /**
     * Ids of members whose composed display name equals the given name
     * (case- and period-insensitive), so the farmer filter reaches
     * member-filed history as well as account-filed reports.
     *
     * @return Collection<int, int>
     */
    private function memberIdsNamed(string $name): Collection
    {
        $needle = self::normalizeReporterName($name);

        if ($needle === '') {
            return collect();
        }

        return Member::query()
            ->get(['id', 'name', 'first_name', 'middle_initial', 'last_name'])
            ->filter(fn (Member $member) => self::normalizeReporterName($member->fullName()) === $needle)
            ->pluck('id')
            ->values();
    }

    /**
     * Comparison form of a reporter's name: case-folded, whitespace-collapsed
     * and free of the periods used in middle initials.
     */
    private static function normalizeReporterName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', str_replace('.', '', trim($name))));
    }

    /**
     * Record a new harvest report (farmer only).
     *
     * Reports now start from a crop batch. The batch dictates crop type,
     * season and land area (never trust those from the client), and the
     * report links back to it so the Estimate vs Actual comparison can pair
     * the planting estimate with the reported yield. The batch is also
     * marked harvested, mirroring what the My Crops flow does.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_batch_id' => ['required', 'exists:farmer_crops,id'],
            'crop_type' => ['required', 'string', 'max:100'],
            'season' => ['required', 'in:wet,dry'],
            'land_area' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'actual_yield_metric_tons' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'actual_total_expenses' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'harvest_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $batch = FarmerCrop::with('crop')->find($validated['crop_batch_id']);

        if ($batch->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $batch->isConfirmed()) {
            return back()->withErrors([
                'crop_batch_id' => 'This crop batch has not been confirmed by an admin yet.',
            ])->withInput();
        }

        if ($batch->harvest_report_id) {
            return back()->withErrors([
                'crop_batch_id' => 'This crop batch already has a harvest report.',
            ])->withInput();
        }

        // The batch is the source of truth for these fields — including the
        // seed variety, so a manually filed report carries the same
        // registry-backed label an imported one does.
        $validated['crop_type'] = $batch->crop?->name ?? $validated['crop_type'];
        $validated['season'] = $batch->season ?? $validated['season'];
        $validated['land_area'] = (float) $batch->land_area;
        $validated['variety'] = $batch->variety;
        $validated['variety_key'] = $batch->variety_key;

        $validated['user_id'] = Auth::id();
        $validated['quotation_id'] = null;

        $report = HarvestReport::create($validated);

        ActivityLogger::harvestReportCreated($report);

        // Close the loop on the batch: link it, mark it harvested and record
        // the actual yield (same MT convention syncHarvestReport uses).
        $batch->update([
            'harvest_report_id' => $report->id,
            'growth_stage' => 'harvested',
            'actual_harvest_date' => $report->harvest_date,
            'actual_yield' => $batch->actual_yield ?? (float) $report->actual_yield_metric_tons,
        ]);
        ActivityLogger::cropHarvested($batch);

        return back()->with('success', sprintf(
            'Harvest report recorded for %s — %s recorded on %s.',
            $report->crop_type,
            SettingsHelper::formatYield((float) $report->actual_yield_metric_tons),
            $report->harvest_date->format('M d, Y')
        ));
    }

    /**
     * Remove a harvest report (farmer only, own reports).
     */
    public function destroy(HarvestReport $harvestReport)
    {
        if ($harvestReport->user_id !== Auth::id()) {
            abort(403);
        }

        // Unlink any crop batch pointing here first — the foreign key would
        // otherwise block the delete, and the batch should be reportable again.
        FarmerCrop::where('harvest_report_id', $harvestReport->id)
            ->update(['harvest_report_id' => null]);

        ActivityLogger::harvestReportDeleted($harvestReport);
        $harvestReport->delete();

        return back()->with('success', 'Harvest report has been removed.');
    }
}
