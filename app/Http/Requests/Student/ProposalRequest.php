<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class ProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->user()?->currentTeam();

        // Only the leader submits the proposal
        return $team !== null && $team->leader_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'description' => ['required', 'string', 'min:100', 'max:3000'],
            'category' => ['required', 'in:web,mobile,ai,network,research,other'],
            'keywords' => ['required', 'array', 'min:3', 'max:8'],
            'keywords.*' => ['required', 'string', 'min:2', 'max:40'],
            'technologies' => ['required', 'array', 'min:1', 'max:12'],
            'technologies.*' => ['required', 'string', 'min:2', 'max:40'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('student.proposal_title'),
            'description' => __('student.proposal_description'),
            'category' => __('student.proposal_category'),
            'keywords' => __('student.proposal_keywords'),
            'technologies' => __('student.proposal_technologies'),
        ];
    }
}
