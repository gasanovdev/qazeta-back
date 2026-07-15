<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PublicAssetUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:30', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', Rule::in(['admin', 'user', 'branch'])],
            'interest_ids' => ['sometimes', 'array'],
            'interest_ids.*' => ['string', 'max:100'],
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => $validated['password'],
            'role' => $validated['role'] ?? 'user',
            'interest_ids' => $validated['interest_ids'] ?? [],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadCount('subscribers');

        return response()->json([
            'user' => $this->serializeUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['sometimes', 'string', 'max:30', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', Rule::in(['admin', 'user', 'branch'])],
            'interest_ids' => ['sometimes', 'array'],
            'interest_ids.*' => ['string', 'max:100'],
            'avatar' => ['sometimes', 'image', 'max:5120'],
        ]);

        if (array_key_exists('full_name', $validated)) {
            $user->full_name = $validated['full_name'];
        }

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        if (array_key_exists('phone_number', $validated)) {
            $user->phone_number = $validated['phone_number'];
        }

        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role'];
        }

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        if (array_key_exists('interest_ids', $validated)) {
            $user->interest_ids = $validated['interest_ids'];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return response()->json([
            'user' => $this->serializeUser($user),
        ]);
    }

    private function serializeUser(User $user): array
    {
        $subscribedBranchIds = $user->relationLoaded('subscribedBranches')
            ? $user->subscribedBranches->pluck('id')->map(fn ($id) => (string) $id)->values()->all()
            : $user->subscribedBranches()->pluck('users.id')->map(fn ($id) => (string) $id)->values()->all();

        return [
            'id' => (string) $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number ?? '',
            'role' => $user->role,
            'avatar_url' => PublicAssetUrl::fromStoragePath($user->avatar),
            'interest_ids' => $user->interest_ids ?? [],
            'subscribers_count' => $user->subscribers_count ?? ($user->role === 'branch' ? $user->subscribers()->count() : 0),
            'subscribed_branch_ids' => $subscribedBranchIds,
        ];
    }
}
