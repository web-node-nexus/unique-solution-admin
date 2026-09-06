<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->latest('is_default')
            ->latest('id')
            ->get()
            ->map(fn (CustomerAddress $a) => $this->transform($a));

        return response()->json(['success' => true, 'data' => $addresses]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        if (($data['is_default'] ?? false) || $user->addresses()->count() === 0) {
            $user->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        $address = $user->addresses()->create($data);

        return response()->json(['success' => true, 'data' => $this->transform($address)], 201);
    }

    public function update(Request $request, CustomerAddress $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($data['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json(['success' => true, 'data' => $this->transform($address->fresh())]);
    }

    public function destroy(Request $request, CustomerAddress $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();

        return response()->json(['success' => true, 'data' => null]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'address' => $address->address,
            'city' => $address->city,
            'state' => $address->state,
            'pincode' => $address->pincode,
            'is_default' => (bool) $address->is_default,
        ];
    }
}
