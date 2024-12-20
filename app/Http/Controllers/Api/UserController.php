<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserDetailResource;

class UserController
{
    // View list data user
    public function index()
    {
        //get data api to view
        // Using eager loading request data to database for efficiency data
        //in case calling data relation
        $data_user = User::orderby('user_id', 'asc')->with('partner')->get();

        return response()->json([
            'success' => true,
            'message' => 'Display List User Successfully',
            'data' => UserResource::collection($data_user)
        ]);
    }

    // Store data user to database
    public function store(Request $request)
    {
        // Data input rules
        $rules = [
            'bp_code' => 'required|string|max:25',
            'name' => 'required|string|max:25',
            'role' => 'required|string|max:25',
            'status' => 'required|string|max:25',
            'username' => 'required|string|unique:user,username|max:25', // username must unique
            'password' => 'required|string|min:8|max:25', //min and max leght 8/25
            'email' => 'required|email|unique:user,email|max:255' // email must unique
        ];

        // Validator instance
        $validator = Validator::make($request->all(), $rules);

        // Check validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Register validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Checking input if username have duplicate in database
        // *note for checking username
        // $usernameExist = User::where('username', $request->username)->first();
        // if ($usernameExist) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Username has already been used',
        //     ], 422);
        // }

        // Create data
        $data_create = User::create(array_merge(
            $validator->validated(),
            ['password' => Hash::make($request->password)]
        ));

        // Return value
        return response()->json([
            'success' => true,
            'message' => 'Data User "' . $data_create->username . '" Successfully Created',
            'data' => new UserResource($data_create)
        ]);
    }

    //Show edit form user
    public function edit($user)
    {
        // Find user
        $data_edit = User::findOrFail($user);
        return new UserDetailResource($data_edit);
    }

    public function update(Request $request, $user)
    {
        // Find user
        $data_edit = User::findOrFail($user);

        // Fail find user
        if (!$data_edit) {
            return response()->json([
                'success' => false,
                'errors' => 'User not found'
            ], 404);
        }

        // Data input rules
        $rules = [
            'bp_code' => 'required|string|max:25',
            'name' => 'required|string|max:25',
            'role' => 'required|string|max:25',
            'password' => 'nullable|string|min:8',
            'username' => 'nullable|string|unique:user,username|max:25', // username must unique
            'email' => 'required|email|max:255|unique:user,email,' . $data_edit->user_id . ',user_id' // email must be unique
        ];

        // Validator instance
        $validator = Validator::make($request->all(), $rules);

        // Check validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Update validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the user with validated data
        $validatedData = $validator->validated();

        // Process the username if it was provided
        if (empty($validatedData['username'])) {
            unset($validatedData['username']); // Remove username if not provided
        }

        // Hash the password if it was provided
        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']); // Remove password if not provided
        }

        // Update the user instance
        $data_edit->update($validatedData);

        // Return value
        return response()->json([
            'success' => true,
            'message' => 'Data User "' . $data_edit->username . '" Successfully Updated',
            'data' => new UserResource($data_edit)
        ]);
    }


    public function updateStatus(Request $request, $user)
    {
        // Find user
        $data_edit = User::findOrFail($user);

        // Fail find user
        if (!$user) {
            return response()->json([
                'success' => false,
                'errors' => 'User Not Found'
            ], 404);
        }

        // Data input rules
        $rules = [
            'status' => 'required|string|max:25',
        ];

        // Validator instance
        $validator = Validator::make($request->all(), $rules);

        // Check validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Update Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the user with validated data
        $validatedData = $validator->validated();

        // Update the user instance
        $data_edit->update($validatedData);

        // Return value
        return response()->json([
            'success' => true,
            'message' => 'Data User "' . $data_edit->username . '" Successfully Updated',
            'data' => new UserResource($data_edit)
        ]);
    }
}
