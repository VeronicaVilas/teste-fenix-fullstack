<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

trait BuildsFormRequests
{
    /**
     * @template T of FormRequest
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function validatedRequest(string $class, array $data = [], string $method = 'POST'): FormRequest
    {
        $request = $class::create('/', $method, $data);
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        $request->validateResolved();

        return $request;
    }

    /**
     * @param  class-string<FormRequest>  $class
     * @return array<string, array<int, string>>
     */
    protected function validationErrors(string $class, array $data = [], string $method = 'POST'): array
    {
        try {
            $this->validatedRequest($class, $data, $method);
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        return [];
    }
}
