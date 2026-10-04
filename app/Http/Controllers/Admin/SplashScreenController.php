<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SplashScreen;
use App\Services\ImageService;
use App\Services\SplashScreenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SplashScreenController extends Controller
{
    public function __construct(
        protected ImageService $images,
        protected SplashScreenService $splashes,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', SplashScreen::class);

        $default = SplashScreen::query()->where('is_default', true)->first();
        $schedules = SplashScreen::query()
            ->where('is_default', false)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();
        $live = $this->splashes->current();
        $today = SplashScreen::today();

        return view('admin.splashes.index', compact('default', 'schedules', 'live', 'today'));
    }

    public function updateDefault(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('banners.update'), 403);

        $request->validate([
            'image' => image_upload_rules(true),
        ]);

        $default = SplashScreen::query()->firstOrCreate(
            ['is_default' => true],
            [
                'image_path' => 'splashes/default.jpg',
                'is_enabled' => true,
            ]
        );

        $path = $this->images->upload($request->file('image'), 'splashes');
        $previous = $default->image_path;
        $default->update(['image_path' => $path, 'is_enabled' => true]);
        $this->deleteStored($previous, $path);

        return back()->with('success', 'Default splash screen updated.');
    }

    public function create(): View
    {
        $this->authorize('create', SplashScreen::class);

        return view('admin.splashes.form', ['splash' => new SplashScreen(['is_enabled' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SplashScreen::class);

        $data = $this->validatedSchedule($request);
        if ($data instanceof RedirectResponse) {
            return $data;
        }

        $data['image_path'] = $this->images->upload($request->file('image'), 'splashes');
        $data['is_default'] = false;
        SplashScreen::query()->create($data);

        return redirect()
            ->route('admin.splashes.index')
            ->with('success', 'Splash screen scheduled.');
    }

    public function edit(SplashScreen $splash): View
    {
        $this->authorize('update', $splash);
        abort_if($splash->is_default, 404);

        return view('admin.splashes.form', ['splash' => $splash]);
    }

    public function update(Request $request, SplashScreen $splash): RedirectResponse
    {
        $this->authorize('update', $splash);
        abort_if($splash->is_default, 404);

        $data = $this->validatedSchedule($request, $splash, false);
        if ($data instanceof RedirectResponse) {
            return $data;
        }

        if ($request->hasFile('image')) {
            $path = $this->images->upload($request->file('image'), 'splashes');
            $previous = $splash->image_path;
            $data['image_path'] = $path;
            $splash->update($data);
            $this->deleteStored($previous, $path);
        } else {
            $splash->update($data);
        }

        return redirect()
            ->route('admin.splashes.index')
            ->with('success', 'Splash schedule updated.');
    }

    public function toggle(SplashScreen $splash): RedirectResponse
    {
        $this->authorize('update', $splash);
        abort_if($splash->is_default, 404);

        $enable = ! $splash->is_enabled;
        if ($enable) {
            $clash = $this->splashes->overlapping(
                $splash->starts_on->toDateString(),
                $splash->ends_on->toDateString(),
                $splash->id
            );
            if ($clash) {
                return back()->with('error', $this->overlapMessage($clash));
            }
        }

        $splash->update(['is_enabled' => $enable]);

        return back()->with('success', $enable ? 'Schedule enabled.' : 'Schedule disabled.');
    }

    public function destroy(SplashScreen $splash): RedirectResponse
    {
        $this->authorize('delete', $splash);
        abort_if($splash->is_default, 404);

        $previous = $splash->image_path;
        $splash->delete();
        $this->deleteStored($previous, null);

        return back()->with('success', 'Scheduled splash deleted.');
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function validatedSchedule(Request $request, ?SplashScreen $splash = null, bool $imageRequired = true): array|RedirectResponse
    {
        $data = $request->validate([
            'image' => image_upload_rules($imageRequired),
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $enabled = $request->boolean('is_enabled');
        if ($enabled) {
            $clash = $this->splashes->overlapping($data['starts_on'], $data['ends_on'], $splash?->id);
            if ($clash) {
                return back()->withInput()->with('error', $this->overlapMessage($clash));
            }
        }

        return [
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'is_enabled' => $enabled,
        ];
    }

    private function overlapMessage(SplashScreen $clash): string
    {
        $start = $clash->starts_on?->format('d M Y');
        $end = $clash->ends_on?->format('d M Y');

        return "Those dates overlap the schedule from {$start} to {$end}. Disable or edit that schedule so only one splash is on at a time.";
    }

    private function deleteStored(?string $previous, ?string $keep): void
    {
        if (! $previous || $previous === $keep || $previous === 'splashes/default.jpg') {
            return;
        }

        Storage::disk('public')->delete($previous);
    }
}
