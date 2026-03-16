<?php

namespace App\Http\Controllers;

use App\Models\JobInterview;
use App\Models\JobApplication;
use App\Services\AiService;
use App\Services\XpService;
use Illuminate\Http\Request;

class JobInterviewController extends Controller
{
    public function store(Request $request, JobApplication $jobApplication)
    {
        abort_if($jobApplication->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'interview_type'   => 'required|in:phone,technical,behavioral,final,other',
            'scheduled_at'     => 'required|date',
            'interviewer_name' => 'nullable|string|max:255',
            'notes'            => 'nullable|string|max:5000',
        ]);

        $interview = $jobApplication->interviews()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'outcome' => 'pending',
        ]);

        // Auto-generate AI prep guide
        app(AiService::class)->generateInterviewPrep($request->user(), $interview);

        // Update application status to interview
        if (!in_array($jobApplication->status, ['interview', 'offer'])) {
            $jobApplication->update(['status' => 'phone_screen']);
        }

        return back()->with('success', 'Interview scheduled! AI prep generated ✅');
    }

    public function update(Request $request, JobInterview $jobInterview)
    {
        abort_if($jobInterview->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'outcome'          => 'sometimes|in:pending,passed,failed,cancelled',
            'notes'            => 'nullable|string|max:5000',
            'interviewer_name' => 'nullable|string|max:255',
            'scheduled_at'     => 'sometimes|date',
        ]);

        $jobInterview->update($data);

        // Award XP when interview is completed
        if (isset($data['outcome']) && $data['outcome'] === 'passed') {
            XpService::award(
                $request->user(),
                25,
                "Passed interview at {$jobInterview->application->company_name} 🎯",
                'job_interview',
                $jobInterview->id
            );

            // Auto-advance application status
            $jobInterview->application->update(['status' => 'interview']);
        }

        return back()->with('success', 'Interview updated!');
    }

    public function generatePrep(Request $request, JobInterview $jobInterview)
    {
        abort_if($jobInterview->user_id !== $request->user()->id, 403);

        $prep = app(AiService::class)->generateInterviewPrep(
            $request->user(),
            $jobInterview
        );

        return response()->json(['prep' => $prep]);
    }

    public function destroy(Request $request, JobInterview $jobInterview)
    {
        abort_if($jobInterview->user_id !== $request->user()->id, 403);
        $jobInterview->delete();
        return back()->with('success', 'Interview removed.');
    }
}
