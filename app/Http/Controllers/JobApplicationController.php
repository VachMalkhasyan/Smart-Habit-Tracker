<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Services\AiService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $applications = $user->jobApplications()
            ->with(['interviews' => fn($q) => $q->orderBy('scheduled_at')])
            ->orderBy('order')
            ->get()
            ->groupBy('status');

        $stats = $user->jobSearchStats();

        // Upcoming interviews across all applications
        $upcomingInterviews = $user->jobInterviews()
            ->with('application')
            ->where('scheduled_at', '>=', now())
            ->where('outcome', 'pending')
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();

        return Inertia::render('Jobs/Index', [
            'applications'       => $applications,
            'stats'              => $stats,
            'statuses'           => JobApplication::$statuses,
            'upcoming_interviews'=> $upcomingInterviews,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'role_title'   => 'required|string|max:255',
            'job_url'      => 'nullable|url',
            'status'       => 'required|in:wishlist,applied,phone_screen,interview,offer,rejected,withdrawn',
            'priority'     => 'required|integer|in:1,2,3',
            'salary_min'   => 'nullable|integer|min:0',
            'salary_max'   => 'nullable|integer|min:0',
            'currency'     => 'nullable|string|max:10',
            'location'     => 'nullable|string|max:255',
            'is_remote'    => 'boolean',
            'applied_date' => 'nullable|date',
            'notes'        => 'nullable|string|max:5000',
        ]);

        $application = $request->user()->jobApplications()->create($data);

        // Award XP for adding an application
        if ($data['status'] === 'applied') {
            XpService::award(
                $request->user(),
                10,
                "Applied to {$application->company_name} 💼",
                'job_application',
                $application->id
            );
        }

        return back()->with('success', 'Application added! 💼');
    }

    public function show(Request $request, JobApplication $jobApplication)
    {
        $this->authorizeApplication($jobApplication, $request->user());

        $jobApplication->load(['interviews', 'contacts']);

        return Inertia::render('Jobs/Show', [
            'application' => $jobApplication,
            'statuses'    => JobApplication::$statuses,
        ]);
    }

    public function update(Request $request, JobApplication $jobApplication)
    {
        $this->authorizeApplication($jobApplication, $request->user());

        $oldStatus = $jobApplication->status;

        $data = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'role_title'   => 'sometimes|string|max:255',
            'job_url'      => 'nullable|url',
            'status'       => 'sometimes|in:wishlist,applied,phone_screen,interview,offer,rejected,withdrawn',
            'priority'     => 'sometimes|integer|in:1,2,3',
            'salary_min'   => 'nullable|integer',
            'salary_max'   => 'nullable|integer',
            'currency'     => 'nullable|string|max:10',
            'location'     => 'nullable|string|max:255',
            'is_remote'    => 'boolean',
            'applied_date' => 'nullable|date',
            'notes'        => 'nullable|string|max:5000',
        ]);

        $jobApplication->update($data);

        // Award XP on status progression
        $xpMap = [
            'applied'      => [10,  "Applied to {$jobApplication->company_name} 💼"],
            'phone_screen' => [20,  "Phone screen at {$jobApplication->company_name} 📞"],
            'interview'    => [30,  "Interview secured at {$jobApplication->company_name} 🎯"],
            'offer'        => [100, "Offer received from {$jobApplication->company_name} 🎉"],
        ];

        if (isset($data['status'])
            && $data['status'] !== $oldStatus
            && isset($xpMap[$data['status']])) {
            [$amount, $reason] = $xpMap[$data['status']];
            XpService::award($request->user(), $amount, $reason, 'job_application', $jobApplication->id);
        }

        return back()->with('success', 'Application updated!');
    }

    public function destroy(Request $request, JobApplication $jobApplication)
    {
        $this->authorizeApplication($jobApplication, $request->user());
        $jobApplication->delete();
        return back()->with('success', 'Application removed.');
    }

    // Kanban reorder
    public function reorder(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'applications'   => 'required|array',
            'applications.*' => 'integer|exists:job_applications,id',
            'status'         => 'required|string',
        ]);

        foreach ($request->applications as $index => $id) {
            JobApplication::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->update(['order' => $index, 'status' => $request->status]);
        }

        return response()->json(['success' => true]);
    }

    // AI endpoints
    public function generateCoverLetter(Request $request, JobApplication $jobApplication)
    {
        $this->authorizeApplication($jobApplication, $request->user());

        $request->validate([
            'job_description' => 'required|string|max:5000',
        ]);

        $coverLetter = app(AiService::class)->generateCoverLetter(
            $request->user(),
            $jobApplication,
            $request->job_description
        );

        return response()->json(['cover_letter' => $coverLetter]);
    }

    public function companyResearch(Request $request, JobApplication $jobApplication)
    {
        $this->authorizeApplication($jobApplication, $request->user());

        $research = app(AiService::class)->generateCompanyResearch(
            $request->user(),
            $jobApplication->company_name,
            $jobApplication->role_title
        );

        return response()->json(['research' => $research]);
    }

    private function authorizeApplication(JobApplication $app, $user): void
    {
        abort_if($app->user_id !== $user->id, 403, 'Unauthorized');
    }
}
