<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class RgBlogCommentsController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_blog_comments')) {
            return view('resortGuruAdmin.pending', ['title' => 'Blog Comments']);
        }
        if ($request->ajax()) {
            $rows = DB::table('rg_blog_comments as c')
                ->leftJoin('rg_blog_posts as p', 'p.id', '=', 'c.blog_post_id')
                ->select('c.*', 'p.title as post_title', 'p.slug as post_slug');
            return DataTables::of($rows)
                ->addColumn('post', fn($r) => $r->post_title ? '<a href="/blog/' . $r->post_slug . '" target="_blank">' . e(\Illuminate\Support\Str::limit($r->post_title, 50)) . '</a>' : '—')
                ->addColumn('snippet', fn($r) => e(\Illuminate\Support\Str::limit($r->comment_text, 120)))
                ->addColumn('rating_stars', function ($r) {
                    if (empty($r->rating)) return '<span class="text-muted">—</span>';
                    $stars = str_repeat('★', (int) $r->rating) . str_repeat('☆', 5 - (int) $r->rating);
                    return '<span class="text-warning" title="' . $r->rating . '/5">' . $stars . '</span>';
                })
                ->addColumn('status_pill', function ($r) {
                    $cls = ['approved' => 'success', 'pending' => 'warning', 'spam' => 'danger'][$r->status] ?? 'secondary';
                    return '<span class="badge bg-' . $cls . '">' . ucfirst($r->status) . '</span>';
                })
                ->addColumn('seeded_pill', fn($r) => $r->is_seeded ? '<span class="badge bg-info text-dark">Seeded</span>' : '<span class="badge bg-light text-dark">User</span>')
                ->addColumn('actions', function ($r) {
                    return '<div class="d-flex gap-1">'
                        . ($r->status !== 'approved' ? '<button onclick="approveComment(' . $r->id . ')" class="btn btn-sm btn-success" title="Approve"><i class="bx bx-check"></i></button>' : '')
                        . ($r->status !== 'spam' ? '<button onclick="spamComment(' . $r->id . ')" class="btn btn-sm btn-warning" title="Mark spam"><i class="bx bx-error"></i></button>' : '')
                        . '<button onclick="deleteComment(' . $r->id . ')" class="btn btn-sm btn-danger" title="Delete"><i class="bx bx-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['post', 'rating_stars', 'status_pill', 'seeded_pill', 'actions'])
                ->make(true);
        }
        return view('resortGuruAdmin.blog-comments-index');
    }

    public function setStatus(Request $request)
    {
        $id = (int) $request->input('id');
        $status = $request->input('status', 'approved');
        if (!in_array($status, ['approved', 'pending', 'spam'])) abort(400);
        DB::table('rg_blog_comments')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_blog_comments')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
