<?php

namespace Tests\Unit\Http\Requests;

use App\Enums\StudentExamStatus;
use App\Http\Requests\ListAvailableExamsRequest;
use App\Http\Requests\ListExamsRequest;
use App\Http\Requests\PaginationRequest;
use Tests\Concerns\BuildsFormRequests;
use Tests\TestCase;

class PaginationRequestsTest extends TestCase
{
    use BuildsFormRequests;

    public function test_pagination_defaults_to_ten_items(): void
    {
        $request = $this->validatedRequest(PaginationRequest::class, [], 'GET');

        $this->assertTrue($request->authorize());
        $this->assertSame(1, $request->page());
        $this->assertSame(10, $request->perPage());
    }

    public function test_pagination_reads_page_size(): void
    {
        $request = $this->validatedRequest(PaginationRequest::class, ['page' => '3', 'per_page' => '25'], 'GET');

        $this->assertSame(3, $request->page());
        $this->assertSame(25, $request->perPage());
    }

    public function test_pagination_rejects_invalid_values(): void
    {
        $this->assertSame([
            'page' => ['A página deve ser no mínimo 1.'],
            'per_page' => ['A quantidade por página deve ser no máximo 50.'],
        ], $this->validationErrors(PaginationRequest::class, ['page' => 0, 'per_page' => 51], 'GET'));

        $this->assertSame([
            'page' => ['A página deve ser um número inteiro.'],
            'per_page' => ['A quantidade por página deve ser um número inteiro.'],
        ], $this->validationErrors(PaginationRequest::class, ['page' => 'x', 'per_page' => 'y'], 'GET'));
    }

    public function test_list_exams_accepts_status_filter(): void
    {
        $request = $this->validatedRequest(ListExamsRequest::class, ['status' => 'draft', 'per_page' => 5], 'GET');

        $this->assertSame('draft', $request->validated('status'));
        $this->assertSame(5, $request->perPage());
    }

    public function test_list_available_exams_reads_status_and_trimmed_search(): void
    {
        $request = $this->validatedRequest(ListAvailableExamsRequest::class, ['status' => 'pending', 'search' => '  Geografia ', 'per_page' => '4'], 'GET');

        $this->assertSame(StudentExamStatus::Pending, $request->status());
        $this->assertSame('Geografia', $request->search());
        $this->assertSame(4, $request->perPage());
    }

    public function test_list_available_exams_defaults_to_no_filters(): void
    {
        $request = $this->validatedRequest(ListAvailableExamsRequest::class, ['search' => '   '], 'GET');

        $this->assertNull($request->status());
        $this->assertNull($request->search());
    }

    public function test_list_available_exams_rejects_invalid_filters(): void
    {
        $this->assertSame(
            ['status' => ['A situação deve ser pending ou answered.']],
            $this->validationErrors(ListAvailableExamsRequest::class, ['status' => 'archived'], 'GET'),
        );
        $this->assertSame(
            ['search' => ['A busca deve ser um texto.']],
            $this->validationErrors(ListAvailableExamsRequest::class, ['search' => ['geo']], 'GET'),
        );
    }

    public function test_list_exams_rejects_unknown_status(): void
    {
        $this->assertSame(
            ['status' => ['O status deve ser draft ou published.']],
            $this->validationErrors(ListExamsRequest::class, ['status' => 'archived'], 'GET'),
        );
    }
}
