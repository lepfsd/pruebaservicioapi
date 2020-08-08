<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;

class UserController extends Controller
{

     /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('JWT');
    }


    public function index()
    {
        $user = auth()->user();

        $transactions = $user->transactions()->get();
        
        return response()->json([
            'transactions' => $transactions
        ], Response::HTTP_OK);
        
    }

    /**
     * store credit/debit transaction
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $data = ['type'  =>  'credit',
                'amount' => $request->amount,
                'description' =>  $request->description,
                'status' => 1,
                'identification' => $request->identification,
                'telephone' => $request->telephone,
        ];

        $result = $user->transactions()->create($data);

        if(empty($result)) {
            return response()->json([
                'message' => 'Invalid request'
            ], Response::HTTP_SERVICE_UNAVAILABLE);
           
        }

        return response()->json([
            'message' => 'ok'
        ], Response::HTTP_OK);
    }

    /**
     * withdraw request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function withdraw(Request $request)
    {
        $user = auth()->user();
        
        if(!$user->allowWithdraw($request->amount)) {
            return response()->json([
                'message' => 'Invalid request'
            ], Response::HTTP_CONFLICT);
        }

        $token = Str::random(6);
        $data = [
            'type' => 'debit',  
            'identification' => $request->identification,
            'telephone' => $request->telephone,
            'amount' => $request->amount,
            'description' => $request->description,
            'status' => 0,
            'token' => $token,
        ];

        $result = $user->transactions()->create($data);

        if(empty($result)) {
            return response()->json([
                'message' => 'Invalid request'
            ], Response::HTTP_SERVICE_UNAVAILABLE);
           
        }

        $dataEmail = new \stdClass();
        $dataEmail->token = $token;
        $dataEmail->amount = $request->amount;
        $dataEmail->description = $request->description;
        $dataEmail->status = 'PENDING';
        $dataEmail->name = auth()->user()->name;
        Mail::to(auth()->user()->email)->send(new SendMail($dataEmail));

        return response()->json([
            'status' => 'pending',
            'token' => $token,
            'amount' => $result->amount
        ], Response::HTTP_OK);
    }

    /**
     * check amount avaliable
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request  $request) 
    {
        $user = auth()->user();

        if(!$user->validateInfoUser($request->identification, $request->telephone)) {
            return response()->json([
                'message' => 'Invalid request'
            ], Response::HTTP_CONFLICT);
        }

        $result = $user->balance();

        return response()->json([
            'balance' => $result
        ], Response::HTTP_OK);

    }

    /**
     * payment confirmation request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function payment(Request $request)
    {
        
        $user = auth()->user();

        $payment = $user->confirmPayment($request->token); 
        

        if(null == $payment) {
            return response()->json([
                'message' => 'Invalid request'
            ], Response::HTTP_CONFLICT);
        } 

        if($payment->token == $request->token) {

            $data = [
                'type' => 'debit',  
                'identification' => $payment->identification,
                'telephone' => $payment->telephone,
                'amount' => $payment->amount,
                'description' => $payment->description,
                'status' => 1,
                
            ];
    
            $result = $user->transactions()->create($data);

            if(empty($result)) {
                return response()->json([
                    'message' => 'Invalid request'
                ], Response::HTTP_SERVICE_UNAVAILABLE);
               
            }
    
            return response()->json([
                'message' => 'ok'
            ], Response::HTTP_OK);
        }
    
    }
}
