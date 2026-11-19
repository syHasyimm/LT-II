<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GudepTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Daftar gudep untuk verifikasi (FR-VER-02). */
class GudepController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/gudeps/index', [
            'counts' => GudepTable::statusCounts(),
            ...GudepTable::fromRequest($request),
        ]);
    }
}
