<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use App\Models\RgFiesta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RgFiestasController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('rg_fiestas')->orderByDesc('updated_at');
            return DataTables::query($query)
                ->addColumn('region_label', function ($row) {
                    return RgFiesta::REGION_LABELS[$row->region_cluster] ?? ucfirst($row->region_cluster);
                })
                ->addColumn('month_label', function ($row) {
                    return $row->month ? (RgFiesta::MONTH_LABELS[$row->month] ?? '') : '—';
                })
                ->addColumn('status_label', function ($row) {
                    return $row->is_published
                        ? '<span class="badge bg-success">Published</span>'
                        : '<span class="badge bg-secondary">Draft</span>';
                })
                ->addColumn('actions', function ($row) {
                    $editUrl = route('resort-guru-fiestas.edit', $row->id);
                    $blocksUrl = route('resort-guru-fiestas.blocks', $row->id);
                    $deleteUrl = route('resort-guru-fiestas.destroy', $row->id);
                    return '<div class="btn-group">'
                        . '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i> Edit</a>'
                        . '<a href="' . $blocksUrl . '" class="btn btn-sm btn-outline-success"><i class="bx bx-cube"></i> Blocks</a>'
                        . '<button type="button" class="btn btn-sm btn-outline-danger" onclick="rgDeleteFiesta(' . $row->id . ', \'' . $deleteUrl . '\')"><i class="bx bx-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['status_label', 'actions'])
                ->make(true);
        }
        // Full-page requests land on the unified Spots screen; the ajax
        // branch above still serves this tab's DataTable JSON.
        return redirect()->route('resort-guru-spots.index', ['tab' => 'fiestas']);
    }

    public function create()
    {
        return view('resortGuruAdmin.fiestas.form', [
            'fiesta' => new RgFiesta(),
            'action' => route('resort-guru-fiestas.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $fiesta = RgFiesta::create($data);
        return redirect()->route('resort-guru-fiestas.edit', $fiesta->id)
            ->with('success', 'Fiesta created.');
    }

    public function edit($id)
    {
        $fiesta = RgFiesta::findOrFail($id);
        return view('resortGuruAdmin.fiestas.form', [
            'fiesta' => $fiesta,
            'action' => route('resort-guru-fiestas.update', $fiesta->id),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, $id)
    {
        $fiesta = RgFiesta::findOrFail($id);
        $data = $this->validateInput($request, $id);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $fiesta->update($data);
        return redirect()->route('resort-guru-fiestas.edit', $fiesta->id)
            ->with('success', 'Fiesta updated.');
    }

    public function destroy($id)
    {
        $fiesta = RgFiesta::findOrFail($id);
        DB::table('rg_content_blocks')
            ->where('owner_type', 'fiesta')->where('owner_id', $id)->delete();
        $fiesta->delete();
        return response()->json(['ok' => true]);
    }

    public function blocks($id)
    {
        $fiesta = RgFiesta::findOrFail($id);
        return view('resortGuruAdmin.fiestas.blocks', [
            'fiesta' => $fiesta,
            'ownerType' => 'fiesta',
            'ownerId' => $fiesta->id,
        ]);
    }

    private function validateInput(Request $request, $id = null): array
    {
        $slugRule = 'nullable|regex:/^[a-z0-9-]+$/|max:200';
        if ($id) $slugRule .= '|unique:rg_fiestas,slug,' . $id;
        else $slugRule .= '|unique:rg_fiestas,slug';

        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
            'region_cluster' => 'required|string|in:' . implode(',', array_keys(RgFiesta::REGION_LABELS)),
            'province' => 'nullable|string|max:100',
            'city_or_town' => 'nullable|string|max:100',
            'month' => 'nullable|integer|between:1,12',
            'date_label' => 'nullable|string|max:120',
            'summary' => 'nullable|string|max:500',
            'cover_image_path' => 'nullable|string|max:500',
            'og_image_path' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string',
            'h1' => 'nullable|string|max:200',
            'is_published' => 'nullable|boolean',
        ]);
    }
}
