<?php

namespace App\Http\Requests;

use App\Models\Caregiver;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreChatRequest extends FormRequest
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
       // $userModel = get_class(new User());
        $caregiverModel = get_class(new Caregiver());
        return [
          // 'user_id' => "required|exists:{$userModel},id",
           'caregiver_id' => "required|exists:{$caregiverModel},id",
           'name' => 'nullable',
           'is_private' => 'nullable|boolean',
        ];
    }
}
