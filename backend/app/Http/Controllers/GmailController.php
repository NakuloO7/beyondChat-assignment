<?php

namespace App\Http\Controllers;

use Google\Client;
//handles auth
use Google\Service\Gmail;
use Illuminate\Http\Request;
use App\Models\User;

class GmailController extends Controller
{
    public function connectGmail(){
        $client = new Client();  //creates a new google client

        //values in .env file
        $client -> setClientId(env('GOOGLE_CLIENT_ID'));
        $client -> setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client -> setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $client -> addScope(Gmail::MAIL_GOOGLE_COM);  //we request for full gmail access permission
        $client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $client->addScope('https://www.googleapis.com/auth/userinfo.profile');
        //read , send, get emails
        $client->setAccessType('offline');
        $authUrl = $client->createAuthUrl();//generates url

        return redirect($authUrl);  //sends us to google login screen
    }

    public function callback(Request $request){
        $client = new Client();
    
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
    
        $token = $client->fetchAccessTokenWithAuthCode($request->code);
    
        $client->setAccessToken($token);
    
        $oauth2 = new \Google\Service\Oauth2($client);
        $googleUser = $oauth2->userinfo->get();
    
        $user = User::updateOrCreate(
            ['email' => $googleUser->email],
            [
                'google_id' => $googleUser->id,
                'access_token' => json_encode($token),
                'refresh_token' => $token['refresh_token'] ?? null
            ]
        );
    
        return response()->json([
            'message' => 'Gmail connected successfully',
            'user' => $user
        ]);
    }

    public function syncEmails($userId){
        $user = \App\Models\User::findOrFail($userId);  //Gets the user who connected Gmail.

        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
    

        $token = $user->access_token;

        if (is_string($token)) {
            $token = json_decode($token, true);
        }
        $client->setAccessToken($token); //client can call Gmail APIs
    
        $gmail = new Gmail($client);
    
        $messages = $gmail->users_messages->listUsersMessages('me', [
            'maxResults' => 10
        ]);
    
        foreach ($messages->getMessages() as $message) {
    
            $msg = $gmail->users_messages->get('me', $message->getId());
    
            $headers = $msg->getPayload()->getHeaders();
    
            $subject = '';
            $from = '';
    
            foreach ($headers as $header) {
                if ($header->getName() === 'Subject') {
                    $subject = $header->getValue();
                }
                if ($header->getName() === 'From') {
                    $from = $header->getValue();
                }
            }
    
            \App\Models\Email::updateOrCreate(
                ['gmail_msg_id' => $msg->getId()],
                [
                    'thread_id' => $msg->getThreadId(),
                    'sender' => $from,
                    'receiver' => $user->email,
                    'subject' => $subject
                ]
            );   //saves emails in the emails table.
        }
    
        return response()->json([
            'message' => 'Emails synced successfully'
        ]);
    }
}
