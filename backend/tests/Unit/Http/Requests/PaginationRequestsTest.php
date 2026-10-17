<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\DashboardFilterRequest;
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

    public function test_list_exams_rejects_unknown_status(): void
    {
        $this->assertSame(
            ['status' => ['O status deve ser draft ou published.']],
            $this->validationErrors(ListExamsRequest::class, ['status' => 'archived'], 'GET'),
        );
    }

    public function test_dashboard_filter_reads_exam_and_page(): void
    {
        $request = $this->validatedRequest(DashboardFilterRequest::class, ['exam_id' => '10', 'page' => '3'], 'GET');

        $this->assertSame(10, $request->examId());
        $this->assertSame(3, $request->page());
    }

    public function test_dashboard_filter_defaults(): void
    {
        $request = $this->validatedRequest(DashboardFilterRequest::class, [], 'GET');

        $this->assertNull($request->examId());
        $this->assertSame(1, $request->page());
        $this->assertSame(10, $request->perPage());
    }

    public function test_dashboard_filter_rejects_invalid_exam(): void
    {
        $this->assertSame(
            ['exam_id' => ['A prova deve ser informada pelo id numérico.']],
            $this->validationErrors(DashboardFilterRequest::class, ['exam_id' => 'abc'], 'GET'),
        );
        $this->assertSame(
            ['exam_id' => ['A prova deve ser informada pelo id numérico.']],
            $this->validationErrors(DashboardFilterRequest::class, ['exam_id' => 0], 'GET'),
        );
    }
}
