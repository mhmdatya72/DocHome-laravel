<?php

namespace App\Http\Requests;

use App\Models\Caregiver;
use App\Models\Chat;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $chatModel = get_class(new Chat());
        $caregiverModel = get_class(new Caregiver());
        return [
            'chat_id'=>"required|exists:{$chatModel},id",
            'caregiver_id'=>"required|exists:{$caregiverModel},id",
            'message'=>'required|string'
        ];
    }
}
