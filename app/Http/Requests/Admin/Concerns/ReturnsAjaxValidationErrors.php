<?php

namespace App\Http\Requests\Admin\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Auth\Access\AuthorizationException;

trait ReturnsAjaxValidationErrors
{
    protected function failedAuthorization(): void
    {
        if ($this->ajax() || $this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => 'You do not have permission to perform this action.',
                'errors' => ['permission' => ['You do not have permission to perform this action.']],
            ], 403));
        }

        throw new AuthorizationException;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->ajax() || $this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->messages(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
