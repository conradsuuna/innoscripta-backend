<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSettings;
use Illuminate\Support\Facades\Http;

class ArticlesController extends Controller
{
    //
    public function getNewsAPISources(Request $request) {
        try{
            $news_sources = [];
            $news_categories = [];

            $api_key = env('NEWS_API_KEY');
            $url = "https://newsapi.org/v2/top-headlines/sources?apiKey={$api_key}";
            $results = Http::get($url);
            $sources = json_decode($results->getBody(), true);

            foreach ($sources['sources'] as $source) {
                $news_sources[] = array(
                    'id' => $source['id'],
                    'name' => $source['name'],
                );

                // only add unique categories
                if (!in_array(array('category' => $source['category'],), $news_categories)) {
                    $news_categories[] = array(
                        'category' => $source['category'],
                    );
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'News sources fetched successfully',
                'news_sources' => $news_sources,
                'news_categories' => $news_categories,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getNewsAPINews(Request $request)
    {
        try{
            $guard = Auth::guard('sanctum');
            $api_key = env('NEWS_API_KEY');
            // get query params
            $query_params = $request->query();
            $q = $request->has('q') ? $query_params['q'] : 'money+sports';
            // $from = $request->has('from') ? $query_params['from'] : date("Y-m-d");
            // $from = date('Y-m-d', strtotime($from . ' -29 days'));
            // $to = $request->has('to') ? $query_params['to'] : date("Y-m-d");
            $sources = $request->has('sources') ? $query_params['sources'] : 'bbc-news, Engadget';

            if ($guard->check()) {
                $user = $guard->user();
                $user_settings = UserSettings::where('user_id', $user->id)->first();
                $user_settings->prefered_source = json_decode($user_settings->prefered_source);
                $user_settings->category = json_decode($user_settings->category);
                $user_settings->authors = json_decode($user_settings->authors);

                $stored_sources = $request->has('sources') ? $sources :
                    implode(',', $user_settings->prefered_source);
                $category = $user_settings->category;
                $authors = $user_settings->authors;

                $url = "https://newsapi.org/v2/everything?sources={$stored_sources}&apiKey={$api_key}";
                if ($q) {
                    $url .= "&q={$q}";
                }

                $results = Http::get($url);
                $all_articles = json_decode($results->getBody(), true);

                if (!array_key_exists('articles', $all_articles)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No articles found',
                    ], 404);
                }

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
                $url = "https://newsapi.org/v2/everything?sources={$sources}&apiKey={$api_key}";
                if ($q) {
                    $url .= "&q={$q}";
                }

                $results = Http::get($url);
                $all_articles = json_decode($results->getBody(), true); 

                $response = $all_articles;
            }

            return response()->json($response['articles'], 200);

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

}
