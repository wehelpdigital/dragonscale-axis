<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsGenerationGrouping;
use App\Models\AsGenerationGroupingLot;
use App\Models\AsGenerationOffDate;
use App\Models\AsGenerationOffDay;
use App\Models\AsScheduleGeneration;
use App\Models\AsScheduleLot;
use App\Services\ScheduleManager\CalendarGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GenerationController extends BaseScheduleController
{
    public function form(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $schedule->load([
            'lots', 'workers', 'activities', 'irrigations',
            'currentGeneration.groupings.lots',
            'currentGeneration.offDates',
            'currentGeneration.offDays',
            'defaultGroupings.lots',
        ]);

        $issues = $schedule->getReadinessIssues();

        if (!empty($issues)) {
            return redirect()
                ->route('anisenso-schedule-manager.setup', ['id' => $schedule->id])
                ->with('error', 'Cannot open the generator yet — finish setup first: ' . implode(' • ', $issues));
        }

        return view('aniSensoAdmin.scheduleManager.generate', compact('schedule', 'issues'));
    }

    public function generate(Request $request, CalendarGeneratorService $generator)
    {
        $schedule = $this->scheduleFromRequest($request);
        $issues = $schedule->getReadinessIssues();
        if (!empty($issues)) {
            return redirect()
                ->route('anisenso-schedule-manager.setup', ['id' => $schedule->id])
                ->with('error', 'Schedule is missing required setup: ' . implode(' • ', $issues));
        }

        $validator = Validator::make($request->all(), $this->generationRules(), $this->generationMessages());
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::transaction(function () use ($schedule, $request) {
                AsScheduleGeneration::where('croppingScheduleId', $schedule->id)
                    ->where('isCurrent', 1)
                    ->update(['isCurrent' => 0]);

                $latestNumber = (int) (AsScheduleGeneration::where('croppingScheduleId', $schedule->id)->max('generationNumber') ?? 0);

                $generation = AsScheduleGeneration::create([
                    'croppingScheduleId' => $schedule->id,
                    'generationNumber'   => $latestNumber + 1,
                    'seasonStartDate'    => $request->seasonStartDate,
                    'generatedAt'        => now(),
                    'generatedBy'        => Auth::id(),
                    'isCurrent'          => 1,
                    'notes'              => $request->input('notes'),
                    'deleteStatus'       => 1,
                ]);

                $this->persistGenerationConfig($generation, $request, $schedule);

                app(CalendarGeneratorService::class)->generate($generation, false);
            });

            return redirect()
                ->route('anisenso-schedule-manager.calendar', ['scheduleId' => $schedule->id])
                ->with('success', 'Calendar generated. Review and adjust as needed.');
        } catch (\Throwable $e) {
            Log::error('Schedule generation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to generate calendar: ' . $e->getMessage());
        }
    }

    public function regenerate(Request $request, CalendarGeneratorService $generator)
    {
        $schedule = $this->scheduleFromRequest($request);
        $issues = $schedule->getReadinessIssues();
        if (!empty($issues)) {
            return redirect()
                ->route('anisenso-schedule-manager.setup', ['id' => $schedule->id])
                ->with('error', 'Schedule is missing required setup: ' . implode(' • ', $issues));
        }

        $currentGen = AsScheduleGeneration::where('croppingScheduleId', $schedule->id)->where('isCurrent', 1)->first();
        if (!$currentGen) {
            return redirect()->back()->with('error', 'No existing calendar to regenerate. Generate one first.');
        }

        $validator = Validator::make($request->all(), $this->generationRules(), $this->generationMessages());
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::transaction(function () use ($schedule, $currentGen, $request) {
                AsGenerationGroupingLot::whereIn('groupingId', AsGenerationGrouping::where('generationId', $currentGen->id)->pluck('id'))->delete();
                AsGenerationGrouping::where('generationId', $currentGen->id)->delete();
                AsGenerationOffDate::where('generationId', $currentGen->id)->delete();
                AsGenerationOffDay::where('generationId', $currentGen->id)->delete();

                $currentGen->update([
                    'seasonStartDate' => $request->seasonStartDate,
                    'generatedAt'     => now(),
                    'generatedBy'     => Auth::id(),
                    'notes'           => $request->input('notes'),
                ]);

                $this->persistGenerationConfig($currentGen, $request, $schedule);

                app(CalendarGeneratorService::class)->generate($currentGen->fresh(['groupings.lots', 'offDates', 'offDays']), true);
            });

            return redirect()
                ->route('anisenso-schedule-manager.calendar', ['scheduleId' => $schedule->id])
                ->with('success', 'Calendar regenerated. Completed activities were preserved.');
        } catch (\Throwable $e) {
            Log::error('Schedule regeneration failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to regenerate: ' . $e->getMessage());
        }
    }

    protected function persistGenerationConfig(AsScheduleGeneration $generation, Request $request, $schedule): void
    {
        $lotIds = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->pluck('id')->all();

        foreach ((array) $request->input('groupings', []) as $idx => $g) {
            if (empty($g['name'])) continue;
            $startDate = !empty($g['startDate']) ? $g['startDate'] : null;
            $grouping = AsGenerationGrouping::create([
                'generationId' => $generation->id,
                'groupName'    => $g['name'],
                'staggerDays'  => $startDate ? 0 : (int) ($g['staggerDays'] ?? 0),
                'startDate'    => $startDate,
                'groupOrder'   => $idx,
                'deleteStatus' => 1,
            ]);
            foreach ((array) ($g['lotIds'] ?? []) as $lotId) {
                if (!in_array((int) $lotId, $lotIds, true)) continue;
                AsGenerationGroupingLot::create([
                    'groupingId' => $grouping->id,
                    'lotId'      => (int) $lotId,
                ]);
            }
        }

        foreach (array_filter((array) $request->input('offDates', [])) as $d) {
            AsGenerationOffDate::create([
                'generationId' => $generation->id,
                'offDate'      => $d,
                'reason'       => null,
            ]);
        }

        foreach (array_unique((array) $request->input('offDays', [])) as $dow) {
            AsGenerationOffDay::create([
                'generationId' => $generation->id,
                'dayOfWeek'    => (int) $dow,
            ]);
        }
    }

    protected function generationRules(): array
    {
        return [
            'seasonStartDate'           => 'required|date',
            'groupings'                 => 'required|array|min:1',
            'groupings.*.name'          => 'required|string|max:255',
            'groupings.*.staggerDays'   => 'nullable|integer|min:0',
            'groupings.*.startDate'     => 'nullable|date',
            'groupings.*.lotIds'        => 'required|array|min:1',
            'groupings.*.lotIds.*'      => 'integer',
            'offDates'                  => 'nullable|array',
            'offDates.*'                => 'nullable|date',
            'offDays'                   => 'nullable|array',
            'offDays.*'                 => 'integer|min:0|max:6',
            'notes'                     => 'nullable|string|max:2000',
        ];
    }

    protected function generationMessages(): array
    {
        return [
            'seasonStartDate.required'     => 'Season start date is required.',
            'groupings.required'           => 'Define at least one grouping of lots.',
            'groupings.*.name.required'    => 'Each grouping needs a name.',
            'groupings.*.lotIds.required'  => 'Each grouping must contain at least one lot.',
        ];
    }
}
