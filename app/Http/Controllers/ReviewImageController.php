<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewImage;
use App\Services\ReviewImageStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewImageController extends Controller
{
    public function __invoke(
        Request $request,
        ReviewImage $reviewImage,
        string $variant,
        ReviewImageStorage $storage,
    ): BinaryFileResponse {
        abort_unless(in_array($variant, ['medium', 'thumb'], true), 404);

        $review = Review::withTrashed()->find($reviewImage->review_id);
        abort_if(!$review || $review->trashed(), 404);

        $user = $request->user();
        $mayViewPrivateStatus = $user
            && ($user->getKey() === (int) $review->user_id || $user->role === 'admin');

        $isPrivateStatus = in_array($review->status, [
            Review::STATUS_PENDING,
            Review::STATUS_REJECTED,
        ], true);

        abort_unless(
            $review->status === Review::STATUS_APPROVED || ($isPrivateStatus && $mayViewPrivateStatus),
            404,
        );

        $response = $storage->response($reviewImage, $variant);
        abort_if($response === null, 404);

        return $response;
    }
}
