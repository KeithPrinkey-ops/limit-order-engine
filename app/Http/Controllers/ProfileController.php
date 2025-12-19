<?php

            namespace App\Http\Controllers;

            use Illuminate\Http\Request;

            class ProfileController extends Controller
            {
                public function show(Request $request)
                {
                    $user = $request->user(); // Use the authenticated user

                    return response()->json([
                        'id' => $user->id, // Add this for Echo subscription
                        'balance' => $user->balance,
                        'assets' => $user->assets()
                            ->select('symbol', 'amount', 'locked_amount')
                            ->orderBy('symbol')
                            ->get(),
                    ]);
                }
            }
