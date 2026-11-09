<?php

namespace App\Http\Controllers;

use App\Services\ImportProgressService;
use Illuminate\Http\Request;

class ImportProgressController extends Controller
{
    protected $progressService;

    public function __construct(ImportProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    public function getProgress($importId)
    {
        return response()->json($this->progressService->getProgress($importId));
    }
} 