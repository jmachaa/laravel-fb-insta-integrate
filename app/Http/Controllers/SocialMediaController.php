<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Facebook\Facebook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;



class SocialMediaController extends Controller
{
    protected $fb;
    protected $instagramAccountId;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'default_graph_version' => 'v22.0',
        ]);
        $this->instagramAccountId = env('INSTA_ACCOUNT_ID');
    }

    public function post(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'message' => 'required|string',
            'images' => 'required|array|min:1', // Ensure at least one image is uploaded
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validate each image
        ]);
        $imageUrls = [];
        // Store uploaded images
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('uploads', 'public'); // Store the image in storage/app/public/uploads
                $uploadedImages[] = storage_path('app/public/' . $path); // Add full path to uploaded images
                $publicUrl = asset(Storage::url(str_replace('public/', '', $path)));
                $imageUrls[] = $publicUrl;
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
        $getContainerResponse = $this->getContainerResponse($imageUrls);
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

    public function post_to_insta()
    {
        $insta_acc = env('INSTA_ACCOUNT_ID');
    }

    public function getContainerResponse($imgArr)
    {
        foreach ($imgArr as $imageUrl) {
            $containerResponse = Http::post("https://graph.facebook.com/v18.0/{$this->instagramAccountId}/media", [
                'image_url' => $imageUrl,
                'access_token' => env('FACEBOOK_ACCESS_TOKEN'),
            ]);
            $containerData = $containerResponse->json();
dd( $containerData);
            if (isset($containerData['id'])) {
                $containerIds[] = $containerData['id'];
            } else {
                return response()->json(['error' => 'Failed to create Instagram container'], 400);
            }
        }
    }
}
