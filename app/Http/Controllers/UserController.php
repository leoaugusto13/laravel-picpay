<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Método para registrar um novo usuário
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string|max:20|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'type' => 'required|string|in:user,merchant',
            'balance' => 'required|numeric|min:0', // Validação para o saldo inicial
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'cpf_cnpj' => $request->cpf_cnpj,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type,
        ]);

        // Criar a carteira para o novo usuário
        $user->wallet()->create([
            'balance' => 0, // Saldo inicial da carteira
        ]);

        return response()->json(['message' => 'User registered successfully']);
    }

    // Método para listar todos os usuários
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Método para obter um usuário específico
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    // Método para atualizar um usuário específico
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'cpf_cnpj' => 'sometimes|required|string|max:20|unique:users,cpf_cnpj,' . $id,
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('full_name')) {
            $user->full_name = $request->full_name;
        }
        if ($request->has('cpf_cnpj')) {
            $user->cpf_cnpj = $request->cpf_cnpj;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json($user);
    }

    // Método para deletar um usuário específico
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}


