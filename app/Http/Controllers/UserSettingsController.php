<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSettings;

class UserSettingsController extends Controller
{
    //
    public function saveUserPreferences (Request $request) {
        $data = $request->all();
        $user = Auth::user();

        $preferred_sources = $data['preferred_sources'];
        $category = $data['category'];
        $authors = $data['authors'];

        try{
            // save user preferences
            $user_settings = UserSettings::where('user_id', $user->id)->first();
            if ($user_settings) {
                $user_settings->prefered_source = json_encode($preferred_sources);
                $user_settings->category = json_encode($category);
                $user_settings->authors = json_encode($authors);
                $user_settings->save();
            } else {
                $user_settings = new UserSettings();
                $user_settings->user_id = $user->id;
                $user_settings->prefered_source = json_encode($preferred_sources);
                $user_settings->category = json_encode($category);
                $user_settings->authors = json_encode($authors);
                $user_settings->save();
            }

            $user_settings->prefered_source = json_decode($user_settings->prefered_source);
            $user_settings->category = json_decode($user_settings->category);
            $user_settings->authors = json_decode($user_settings->authors);

            return response()->json([
                'status' => 'success',
                'message' => 'User preferences saved successfully',
                'user_settings' => $user_settings,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
