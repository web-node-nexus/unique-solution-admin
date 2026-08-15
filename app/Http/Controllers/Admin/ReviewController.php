<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ReviewController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Review::class);

        return view('admin.reviews.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Review::class);

        $query = Review::query()
            ->with(['product:id,name', 'user:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->input('rating'));
        }

        return DataTables::of($query)
            ->addColumn('product_name', fn (Review $review) => $review->product?->name ?? '—')
            ->addColumn('customer', fn (Review $review) => $review->user?->name ?? '—')
            ->addColumn('status', function (Review $review) {
                $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                $badge = $map[$review->status] ?? 'secondary';

                return '<span class="badge bg-'.$badge.'">'.e(ucfirst($review->status)).'</span>';
            })
            ->addColumn('action', function (Review $review) {
                $buttons = '';
                if (auth()->user()?->can('reviews.approve') && $review->status === 'pending') {
                    $buttons .= '<form action="'.route('admin.reviews.approve', $review).'" method="POST" class="d-inline me-1">'
                        .csrf_field()
                        .'<button type="submit" class="btn btn-sm btn-outline-success">Approve</button></form>';
                    $buttons .= '<form action="'.route('admin.reviews.reject', $review).'" method="POST" class="d-inline me-1">'
                        .csrf_field()
                        .'<button type="submit" class="btn btn-sm btn-outline-warning">Reject</button></form>';
                }
                if (auth()->user()?->can('reviews.update')) {
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-reply-review" data-id="'.$review->id.'">Reply</button>';
                }
                if (auth()->user()?->can('reviews.delete')) {
                    $buttons .= '<form action="'.route('admin.reviews.destroy', $review).'" method="POST" class="d-inline" data-confirm="Delete this review?">'
                        .csrf_field().method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function approve(Review $review): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $review);

        $review->update(['status' => 'approved']);
        activity_log('approved', 'reviews', "Approved review #{$review->id}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Review approved.']);
        }

        return back()->with('success', 'Review approved.');
    }

    public function reject(Review $review): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $review);

        $review->update(['status' => 'rejected']);
        activity_log('rejected', 'reviews', "Rejected review #{$review->id}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Review rejected.']);
        }

        return back()->with('success', 'Review rejected.');
    }

    public function destroy(Review $review): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $review);

        $id = $review->id;
        $review->delete();
        activity_log('deleted', 'reviews', "Deleted review #{$id}");

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Review deleted.']);
        }

        return back()->with('success', 'Review deleted.');
    }

    public function reply(Request $request, Review $review): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $review);

        $data = $request->validate([
            'admin_reply' => ['required', 'string', 'max:2000'],
        ]);

        $review->update(['admin_reply' => $data['admin_reply']]);
        activity_log('replied', 'reviews', "Replied to review #{$review->id}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Reply saved.']);
        }

        return back()->with('success', 'Reply saved.');
    }
}
