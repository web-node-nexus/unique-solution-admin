<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Services\ActivationGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait TogglesPublishable
{
    protected function togglePublishStatus(
        Request $request,
        Model $model,
        string $permission,
        string $type,
        string $module,
        callable $apply,
    ): JsonResponse {
        abort_unless($request->user()?->can($permission), 403);

        $active = $request->boolean('active');

        if ($active) {
            try {
                app(ActivationGuard::class)->assertCanActivate($type, $model);
            } catch (ValidationException $e) {
                $messages = collect($e->errors())->flatten()->unique()->values()->all();

                return response()->json([
                    'success' => false,
                    'message' => $messages[0] ?? 'Complete required fields before activating.',
                    'errors' => $messages,
                ], 422);
            }
        }

        $apply($model, $active);
        $model->refresh();

        activity_log(
            $active ? 'activated' : 'deactivated',
            $module,
            ucfirst($type).' #'.$model->getKey().' set to '.($active ? 'active' : 'deactive')
        );

        return response()->json([
            'success' => true,
            'active' => $active,
            'message' => $active
                ? 'Marked active. It will show on the app only when its schedule allows.'
                : 'Deactivated. Hidden from the app.',
        ]);
    }
}
