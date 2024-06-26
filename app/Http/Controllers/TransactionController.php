<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function transfer(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $sender = User::find($request->sender_id);
        $receiver = User::find($request->receiver_id);
        $amount = $request->amount;

        if ($sender->type === 'merchant') {
            return response()->json(['error' => 'Merchants cannot send money'], 400);
        }

        if ($sender->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        // Iniciar a transação no banco de dados
        DB::beginTransaction();

        try {
            // Deduzir o saldo do remetente
            $sender->balance -= $amount;
            $sender->save();

            // Adicionar o saldo ao destinatário
            $receiver->balance += $amount;
            $receiver->save();

            // Registrar a transação
            Transaction::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
            ]);

            // Commit da transação no banco de dados
            DB::commit();

             // Enviar notificação
             $this->sendNotification($receiver);

             return response()->json(['message' => 'Transfer successful']);
         } catch (\Exception $e) {
             // Reverter a transação em caso de erro
             DB::rollback();
             return response()->json(['error' => 'Transfer failed. Transaction rolled back.']);
         }

         // Consultar o serviço autorizador externo
         $client = new Client();
         $response = $client->get('https://util.devi.tools/api/v2/authorize');
         $body = json_decode($response->getBody(), true);
 
         if ($body['message'] !== 'Autorizado') {
             return response()->json(['error' => 'Transfer not authorized'], 400);
         }

    }

    private function sendNotification(User $user)
    {
        $client = new Client();

        try {
            $response = $client->post('https://util.devi.tools/api/v1/notify', [
                'json' => [
                    'user_id' => $user->id,
                    'message' => 'You have received a payment.',
                ],
            ]);

            // Verificar o status da resposta, mas aqui vamos supor que é bem-sucedido
            $body = json_decode($response->getBody(), true);

            if ($body['status'] === 'success') {
                // Notificação enviada com sucesso
                \Log::info('Notification sent successfully.');
            } else {
                // Caso contrário, você pode lidar com o erro conforme necessário
                \Log::error('Failed to send notification: ' . $body['message']);
            }
        } catch (\Exception $e) {
            // Lidar com erros de conexão ou falhas no serviço de notificação
            \Log::error('Failed to send notification: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $transactions = Transaction::all();
        return response()->json($transactions);
    }
}

