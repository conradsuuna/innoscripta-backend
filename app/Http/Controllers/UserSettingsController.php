<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserSettings;
use Illuminate\Support\Facades\Http;


class UserSettingsController extends Controller
{
    //

    public function getNewsAPINews(Request $request)
    {
        try{
            $guard = Auth::guard('sanctum');
            $api_key = env('NEWS_API_KEY');
            // get query params
            $query_params = $request->query();
            $q = $request->has('q') ? $query_params['q'] : 'money+sports';
            $sortBy = $request->has('publishedAt') ? $query_params['publishedAt'] : date("Y-m-d");
            $sources = $request->has('sources') ? $query_params['sources'] : 'bbc-news';

            if ($guard->check()) {
                $user = $guard->user();
                $user_settings = UserSettings::where('user_id', $user->id)->first();
                $user_settings->prefered_source = json_decode($user_settings->prefered_source);
                $user_settings->category = json_decode($user_settings->category);
                $user_settings->authors = json_decode($user_settings->authors);

                // personalisation focuses on sources, categories and authors
                // params: country, category, sources, q, pageSize, page, apiKey, publishedAt
                $stored_sources = $request->has('sources') ? $sources :
                    implode(',', $user_settings->prefered_source);
                $category = $user_settings->category;
                $authors = $user_settings->authors;

                $url = "https://newsapi.org/v2/everything?sources={$stored_sources}&sortBy={$sortBy}&apiKey={$api_key}";
                if ($q) {
                    $url .= "&q={$q}";
                }

                $results = Http::get($url);
                $all_articles = json_decode($results->getBody(), true);     

                // filter articles based on user preferences
                if (count($authors) > 0) {
                    $articles = $all_articles['articles'];
                    $filtered_articles = [];
                    foreach ($articles as $article) {
                        if (in_array($article['author'], $authors)) {
                            array_push($filtered_articles, $article);
                        }
                    }
    
                    $all_articles['articles'] = $filtered_articles;
                    $all_articles['totalResults'] = count($filtered_articles);
                }

                $response = $all_articles;
            } else {
                // search results by date, category and source
                // params: source, category, publishedAt, q
                $url = "https://newsapi.org/v2/everything?sources={$sources}&sortBy={$sortBy}&apiKey={$api_key}";
                if ($q) {
                    $url .= "&q={$q}";
                }

                $results = Http::get($url);
                $all_articles = json_decode($results->getBody(), true); 

                $response = $all_articles;
            }

            return response()->json([
                'status'=> 'success',
                'response' => $response,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'=> 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getGuardianNews (Request $request) {
        $guard = Auth::guard('sanctum');

        if ($guard->check()) {
            $user = $guard->user();
            $user_settings = UserSettings::where('user_id', $user->id)->first();

            $api_key = env('GUARDIAN_API_KEY');
            // personalisation focuses on sources, categories and authors
            // params: country, tag, sources, q, pageSize, page, apiKey, from-date and contributor
            $query_url = "https://content.guardianapis.com/search?api-key=$api_key";
            
            $data = $response->json();
        } else {
            // search results by date, category and source
            // params: source, category, publishedAt, q
            $user = "good";
        }

        return response()->json([
            'settings' => $user,
        ], 200);
    }

    public function getNYTimesNews (Request $request) {
        $guard = Auth::guard('sanctum');

        if ($guard->check()) {
            $user = $guard->user();
            $user_settings = UserSettings::where('user_id', $user->id)->first();

            $api_key = env('NYTIMES_API_KEY');
            // personalisation focuses on sources, categories and authors
            // params: glocations, tag, source, q, pageSize, page, apiKey, pub-date and persons
            $query_url = "https://api.nytimes.com/svc/search/v2/articlesearch.json?api-key=$api_key";
            
            $data = $response->json();
        } else {
            // search results by date, category and source
            // params: source, category, publishedAt, q
            $user = "good";
        }

        return response()->json([
            'settings' => $user,
        ], 200);
    }

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
