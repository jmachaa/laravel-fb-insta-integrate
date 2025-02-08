<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Facebook\Facebook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class SocialMediaController extends Controller
{
    protected $fb;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'default_graph_version' => 'v22.0',
        ]);
    }

    public function post(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'message' => 'required|string',
            'images' => 'required|array|min:1', // Ensure at least one image is uploaded
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate each image
        ]);

        // Store uploaded images
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('uploads', 'public'); // Store the image in storage/app/public/uploads
                $uploadedImages[] = storage_path('app/public/' . $path); // Add full path to uploaded images
            }
        }
          $attachedMedia = [];
            foreach ($uploadedImages as $index => $imagePath) {
                $photo = $this->uploadPhoto($imagePath);
                // dd($photo);
                $attachedMedia[] = ['media_fbid' => $photo['id']];
                
            }
            $finalAttachedMedia = ['attached_media' => $attachedMedia];
// dd($finalAttachedMedia);
        // Postdddd to Facebook
        $fbResponse = $this->postToFacebook($request->message,$finalAttachedMedia);

        // Return response
        return response()->json([
            'facebook' => $fbResponse,
        ]);
    }

    public function postToFacebook($message, $attachedMedia)
    {
        try {
            // $ch = curl_init();
            // Prepare the 'attached_media' array for each uploaded image
           
            // Create the Facebook post with multiple images attached
            $response = $this->fb->post(
                '/' . env('FACEBOOK_PAGE_ID') . '/feed',
                [
                    'message' => $message,
                    'attached_media' => json_encode($attachedMedia['attached_media'])
                ],
                env('FACEBOOK_ACCESS_TOKEN')
             );

            return $response; // Return response from Facebook API
        } catch (\Exception $e) {
            // Log the error if something goes wrong
            Log::error('Facebook Post Error: ' . $e->getMessage());
            return false; // Return false in case of an error
        }
    }

    private function uploadPhoto($photoPath)
    {
        // Step 1: Upload a photo to Facebook
        $response = Http::attach('source', file_get_contents($photoPath), basename($photoPath))
            ->post('https://graph.facebook.com/me/photos', [
                'access_token' => env('FACEBOOK_ACCESS_TOKEN'),
                'published' => false
            ]);

        // Step 2: Return the response containing the media_fbid
        return $response->json();
    }   
}
