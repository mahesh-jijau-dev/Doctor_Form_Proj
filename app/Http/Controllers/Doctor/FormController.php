<?php
namespace App\Http\Controllers\Doctor;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormAssignment;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = FormAssignment::with(['form' => fn($q) => $q->withCount('responses')])
            ->where('doctor_id', $user->id)
            ->where('is_active', true)
            ->whereHas('form');

        if ($search = $request->input('search')) {
            $query->whereHas('form', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $assignments = $query->latest()->paginate(12)->withQueryString();
        return view('doctor.forms.index', compact('assignments'));
    }

    public function show(Form $form)
    {
        // Ensure doctor is assigned to this form
        $this->authorize('view', $form);
        $form->load(['fields.options', 'fields.conditions', 'sections']);
        return view('doctor.forms.show', compact('form'));
    }
}
