<?php

            namespace App\Http\Controllers;

            use App\Models\User;
            use Illuminate\Http\Request;
            use Illuminate\Support\Facades\Auth;
            use Illuminate\Support\Facades\Hash;

            class RegisterController extends Controller
            {
                public function register(Request $request)
                {
                    $validatedData = $request->validate([
                        'name' => 'required|string|max:255',
                        'email' => 'required|email|max:255|unique:users,email',
                        'password' => 'required|string|min:8|confirmed',
                    ]);

                    $user = User::create([
                        'name' => $validatedData['name'],
                        'email' => $validatedData['email'],
                        'password' => Hash::make($validatedData['password']),
                    ]);

                    Auth::login($user);

                    if ($request->wantsJson()) {
                        return response()->json([
                            'message' => 'Registration successful',
                            'user' => $user->only(['id', 'name', 'email']),
                        ], 201);
                    }

                    return view('app');
                }

                public function login(Request $request)
                {
                    $credentials = $request->validate([
                        'email' => 'required|email',
                        'password' => 'required|string',
                    ]);

                    if (Auth::attempt($credentials)) {
                        if ($request->wantsJson()) {
                            return response()->json([
                                'message' => 'Login successful',
                                'user' => Auth::user()->only(['id', 'name', 'email']),
                            ], 200);
                        }

                        return redirect()->route('/home')->with('success', 'Login successful!');
                    }

                    if ($request->wantsJson()) {
                        return response()->json(['message' => 'The provided credentials do not match our records.'], 422);
                    }

                    return back()->withErrors([
                        'email' => 'The provided credentials do not match our records.',
                    ])->onlyInput('email');
                }

                public function logout(Request $request)
                {
                    Auth::logout();

                    if ($request->wantsJson()) {
                        return response()->json([
                            'message' => 'Logout successful'
                        ], 200);
                    }

                    return redirect()->route('login')->with('success', 'You have been logged out successfully!');
                }
            }
