<?php

namespace App\Http\Controllers;

use Google\Client;
//handles auth
use Google\Service\Gmail;
use Illuminate\Http\Request;

class GmailController extends Controller
{
    public function connectGmail(){
        $client = new Client();  //creates a new google client

        //values in .env file
        $client -> setClientId(env('GOOGLE_CLIENT_ID'));
        $client -> setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client -> setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $client -> addScope(Gmail::MAIL_GOOGLE_COM);  //we request for full gmail access permission
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

        $token = $client->fetchAccessTokenWithAuthCode($request->code); //takes the auth code sent by google to us
        return response()->json($token);  //we will store this token in users table as acccess_token
        /*example response
        {
          "access_token": "...",
          "refresh_token": "...",
          "expires_in": 3600
        }
        */

    }
}
