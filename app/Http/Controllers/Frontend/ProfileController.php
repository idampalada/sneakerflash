<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function index()
    {
        // Manual auth check like your OrderController
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access your profile.');
        }

        try {
            $user = Auth::user();
            
            // Check if profile is locked (completed) - using existing fields only
            $isProfileLocked = $this->isProfileLocked($user);
            
            // Get user statistics - use existing total_orders and total_spent columns if available
            $totalOrders = $user->total_orders ?? Order::where('user_id', $user->id)->count();
            $totalSpent = $user->total_spent ?? Order::where('user_id', $user->id)
                              ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
                              ->sum('total_amount');
            
            // Get recent orders for display
            $recentOrders = Order::with(['orderItems.product'])
                               ->where('user_id', $user->id)
                               ->orderBy('created_at', 'desc')
                               ->limit(5)
                               ->get();

            // Check which fields are locked (have data and cannot be edited)
            $lockedFields = [
                'name' => $this->isFieldLocked($user, 'name'),
                'email' => $this->isFieldLocked($user, 'email'),
                'phone' => $this->isFieldLocked($user, 'phone'),
                'gender' => $this->isFieldLocked($user, 'gender'),
                'birthdate' => $this->isFieldLocked($user, 'birthdate'),
            ];

            Log::info('Profile page accessed', [
                'user_id' => $user->id,
                'total_orders' => $totalOrders,
                'total_spent' => $totalSpent,
                'profile_locked' => $isProfileLocked,
                'locked_fields' => $lockedFields
            ]);

            return view('frontend.profile.index', compact(
                'user', 
                'totalOrders', 
                'totalSpent', 
                'recentOrders',
                'isProfileLocked',
                'lockedFields'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading profile page: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')->with('error', 'Failed to load profile. Please try again.');
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        // Manual auth check
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to update profile.');
        }

        try {
            $user = Auth::user();
            
            // Check which fields are locked (cannot be changed once filled)
            if ($this->isFieldLocked($user, 'name') && $request->filled('name') && $request->name !== $user->name) {
                return redirect()->back()->with('error', 'Name cannot be changed once set.');
            }
            
            if ($this->isFieldLocked($user, 'email') && $request->filled('email') && $request->email !== $user->email) {
                return redirect()->back()->with('error', 'Email cannot be changed once set.');
            }
            
            if ($this->isFieldLocked($user, 'phone') && $request->filled('phone') && $request->phone !== $user->phone) {
                return redirect()->back()->with('error', 'Phone number cannot be changed once set.');
            }
            
            // Validation rules - only validate fields that can be updated
            $rules = [];
            
            // Only validate name if it's not locked or if it's the first time setting it
            if (!$this->isFieldLocked($user, 'name')) {
                $rules['name'] = 'required|string|max:255';
            }
            
            // Only validate email if it's not locked or if it's the first time setting it
            if (!$this->isFieldLocked($user, 'email')) {
                $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)];
            }
            
            // Only validate phone if it's not locked or if it's the first time setting it
            if (!$this->isFieldLocked($user, 'phone') && $request->has('phone')) {
                $rules['phone'] = 'required|string|max:20';
            }

            // Gender and birthdate can always be updated (optional fields)
            if ($request->has('gender')) {
                $rules['gender'] = 'nullable|in:mens,womens,kids';
            }

            if ($request->has('birthdate')) {
                $rules['birthdate'] = 'nullable|date|before:today';
            }

            $validated = $request->validate($rules);

            // Update fields - only update non-locked fields
            $updateData = [];
            
            // Update name only if not locked and provided
            if (!$this->isFieldLocked($user, 'name') && array_key_exists('name', $validated)) {
                $updateData['name'] = $validated['name'];
            }
            
            // Update email only if not locked and provided
            if (!$this->isFieldLocked($user, 'email') && array_key_exists('email', $validated)) {
                $updateData['email'] = $validated['email'];
            }
            
            // Update phone only if not locked and provided
            if (!$this->isFieldLocked($user, 'phone') && array_key_exists('phone', $validated)) {
                $updateData['phone'] = $validated['phone'];
            }
            
            // Gender and birthdate can always be updated
            if (array_key_exists('gender', $validated)) {
                $updateData['gender'] = $validated['gender'];
            }
            
            if (array_key_exists('birthdate', $validated)) {
                $updateData['birthdate'] = $validated['birthdate'];
            }

            // Update user fields
            foreach ($updateData as $field => $value) {
                $user->$field = $value;
            }
            
            $saved = $user->save();

            if ($saved) {
                // Check if profile is now complete after update
                $isNowComplete = $this->calculateProfileCompletion($user) >= 100;
                
                Log::info('Profile updated successfully', [
                    'user_id' => $user->id,
                    'updated_fields' => array_keys($updateData),
                    'profile_completion' => $this->calculateProfileCompletion($user),
                    'is_complete' => $isNowComplete
                ]);

                $message = $isNowComplete ? 
                    'Profile completed successfully! All required information has been filled.' :
                    'Profile updated successfully!';

                return redirect()->route('profile.index')
                               ->with('success', $message);
            }

            throw new \Exception('Save operation returned false');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->validator)
                           ->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->except(['password', '_token']),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to update profile: ' . $e->getMessage()])
                           ->withInput();
        }
    }

    /**
     * Show change password form.
     */
    public function showChangePassword()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to change password.');
        }

        try {
            $user = Auth::user();
            
            Log::info('Change password page accessed', [
                'user_id' => $user->id
            ]);
            
            return view('frontend.profile.change-password', compact('user'));

        } catch (\Exception $e) {
            Log::error('Error loading change password page: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);

            return redirect()->route('profile.index')->with('error', 'Failed to load change password page.');
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to change password.');
        }

        try {
            $user = Auth::user();

            $validated = $request->validate([
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($validated['current_password'], $user->password)) {
                Log::warning('Incorrect current password attempt', [
                    'user_id' => $user->id
                ]);

                return redirect()->back()
                               ->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $user->password = Hash::make($validated['password']);
            $saved = $user->save();

            if ($saved) {
                Log::info('Password updated successfully', [
                    'user_id' => $user->id
                ]);

                return redirect()->route('profile.index')
                               ->with('success', 'Password updated successfully!');
            }

            throw new \Exception('Password save operation failed');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->validator)
                           ->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating password: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                           ->withErrors(['error' => 'Failed to update password: ' . $e->getMessage()]);
        }
    }

    /**
     * Get user profile data for checkout auto-fill (API endpoint).
     */
    public function getProfileData()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required.'
            ], 401);
        }

        try {
            $user = Auth::user();
            
            $data = [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'gender' => $user->gender ?? '',
                'birthdate' => $user->birthdate ? $user->birthdate->format('Y-m-d') : '',
                'profile_completion_percentage' => $this->calculateProfileCompletion($user),
                'is_profile_complete' => $this->calculateProfileCompletion($user) >= 100,
            ];

            Log::info('Profile data accessed via API', [
                'user_id' => $user->id,
                'completion_percentage' => $data['profile_completion_percentage']
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting profile data: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load profile data.'
            ], 500);
        }
    }

    /**
     * Calculate profile completion percentage based on required fields.
     */
    private function calculateProfileCompletion($user)
    {
        $requiredFields = ['name', 'email', 'phone'];
        $completedFields = 0;
        
        foreach ($requiredFields as $field) {
            if (!empty($user->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($requiredFields)) * 100);
    }

    /**
     * Check if profile is locked (completed and cannot be edited).
     * Profile is considered "locked" when all required fields are completed.
     */
    private function isProfileLocked($user)
    {
        // Profile is locked when completion is 100%
        return $this->calculateProfileCompletion($user) >= 100;
    }

    /**
     * Check if a specific field is locked (cannot be edited because it has data).
     */
    private function isFieldLocked($user, $field)
    {
        return !empty($user->$field);
    }
}