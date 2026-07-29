<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Http\Controllers\Controller;
use App\Models\AsCroppingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CroppingScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = AsCroppingSchedule::active()
            ->forUser(Auth::id())
            ->with(['anisystemUser', 'owner'])
            ->withCount([
                'lots as lots_count' => fn($q) => $q->where('as_schedule_lots.deleteStatus', 1),
                'workers as workers_count' => fn($q) => $q->where('as_schedule_workers.deleteStatus', 1),
                'activities as activities_count' => fn($q) => $q->where('as_schedule_activities.deleteStatus', 1),
            ]);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('aniSensoAdmin.scheduleManager.index', compact('schedules'));
    }

    public function create()
    {
        return view('aniSensoAdmin.scheduleManager.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ], [
            'title.required' => 'Cropping schedule title is required.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $schedule = AsCroppingSchedule::create([
                'usersId' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'setup',
                'isActive' => 1,
                'deleteStatus' => 1,
            ]);

            return redirect()
                ->route('anisenso-schedule-manager.setup', ['id' => $schedule->id])
                ->with('success', 'Cropping schedule created. Now set up its sub-modules.');
        } catch (\Throwable $e) {
            Log::error('CroppingSchedule store failed: '.$e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create cropping schedule.');
        }
    }

    public function setup(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'));
        $schedule->load([
            'lots',
            'workers.offDates',
            'workers.offDays',
            'protocol',
            'materials',
            'services',
            'activities.items.material',
            'activities.items.service',
            'irrigations.assignedWorker',
            'currentGeneration',
            'defaultGroupings.lots',
            'versions',
            'dateNotes',
            'attachments',
            'criticalRules',
        ]);

        return view('aniSensoAdmin.scheduleManager.setup', compact('schedule'));
    }

    public function update(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'));

        $validator = Validator::make($request->all(), [
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string|max:5000',
            'dayType'            => 'nullable|in:DAP,DAS,DAT',
            'defaultStaggerDays' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $payload = [
            'title'       => $request->title,
            'description' => $request->description,
        ];
        if ($request->filled('dayType')) {
            $payload['dayType'] = $request->dayType;
        }
        if ($request->has('defaultStaggerDays')) {
            $payload['defaultStaggerDays'] = (int) $request->input('defaultStaggerDays', 0);
        }

        $schedule->update($payload);

        return response()->json(['success' => true, 'message' => 'Schedule updated.', 'data' => $schedule]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->findOwnedOrFail($request->query('id'));
        $schedule->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Schedule deleted.']);
    }

    protected function findOwnedOrFail($id)
    {
        if (!$id) {
            abort(400, 'Missing schedule id.');
        }
        $schedule = AsCroppingSchedule::active()->forUser(Auth::id())->where('id', $id)->first();
        if (!$schedule) {
            abort(404, 'Cropping schedule not found.');
        }
        return $schedule;
    }
}
