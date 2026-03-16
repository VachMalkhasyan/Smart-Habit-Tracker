<?php

namespace App\Http\Controllers;

use App\Models\JobContact;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class JobContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = $request->user()->jobContacts()->with('jobApplication')->orderBy('name')->get();
        return \Inertia\Inertia::render('Jobs/Contacts', [
            'contacts' => $contacts
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'job_application_id' => 'nullable|exists:job_applications,id',
            'name'               => 'required|string|max:255',
            'role'               => 'nullable|string|max:255',
            'company'            => 'nullable|string|max:255',
            'linkedin_url'       => 'nullable|url',
            'email'              => 'nullable|email',
            'last_contact_date'  => 'nullable|date',
            'notes'              => 'nullable|string|max:2000',
        ]);

        $request->user()->jobContacts()->create($data);

        return back()->with('success', 'Contact added!');
    }

    public function update(Request $request, JobContact $jobContact)
    {
        abort_if($jobContact->user_id !== $request->user()->id, 403);

        $jobContact->update($request->validate([
            'name'              => 'sometimes|string|max:255',
            'role'              => 'nullable|string|max:255',
            'company'          => 'nullable|string|max:255',
            'linkedin_url'      => 'nullable|url',
            'email'             => 'nullable|email',
            'last_contact_date' => 'nullable|date',
            'notes'             => 'nullable|string|max:2000',
        ]));

        return back()->with('success', 'Contact updated!');
    }

    public function destroy(Request $request, JobContact $jobContact)
    {
        abort_if($jobContact->user_id !== $request->user()->id, 403);
        $jobContact->delete();
        return back()->with('success', 'Contact removed.');
    }
}
