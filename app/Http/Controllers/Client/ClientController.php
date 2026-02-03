<?php

namespace App\Http\Controllers\Client;

use App\Enum\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\InviteClientRequest;
use App\Models\User;
use App\Services\ClientServices;
use App\Services\User\CreateUserClientServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    private ClientServices $clientServices;
    private CreateUserClientServices $createUserClientServices;
    public function __construct(
        ClientServices $clientServices,
        CreateUserClientServices $createUserClientServices
    )
    {
        $this->clientServices = $clientServices;
        $this->createUserClientServices = $createUserClientServices;
    }
    public function index(int $id)
    {
        $loggedUser = Auth::user();
        if($loggedUser->role_id != RoleEnum::Master){
            return response()->json(
            [
                'status' => '401',
                'message' => 'Não autorizado',
                'logged' => $loggedUser->role_id
            ],401);
        }
        $get = $this->clientServices->get($id);
        if(!$get){
            return response()->json([
                'message' => 'Usuário não encontrado',
                'status'=> 402
            ], 402);
        }
        return response()->json([
            'status'=> '200',
            'message'=> 'Usuário encontrado!',
            'user' => $get,
            'logged' =>$loggedUser->role_id
        ], 200);
    }
    public function store()
    {
        $clients = $this->clientServices->getAll();

        return response()->json([
            'clients' => $clients,
            'status'=> 200
        ], 200);
    }
    public function addInvestment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'investment_id' => 'required|integer|exists:investments,id',
            'transiction_wallet' => 'boolean|required'
        ]);
        $validator->validate();

        $response = $this->clientServices->addInvestimentUser($request->user_id, $request->investment_id, $request->transiction_wallet);
        if($response->exception){
            return response()->json([
                'message' => 'Error Ao tentar adicionar investimento.',
                'exception' => $response->exception,
                'status'=> 402
            ], 402);
        }
        return $response;
    }
    public function deleteInvestment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);
        $validator->validate();

        return $this->clientServices->deleteInvestimentUser($request->user_id);
    }
    /**
     * @param InviteClientRequest $request
     * @return JsonResponse
     */
    public function invitation(InviteClientRequest $request)
    {
        // dd($request['current_balance']);
        $this->createUserClientServices->setClientData( $request->only([
            'name',
            'email',
            'person',
            'birthday',
            'notifications',
            'telephone',
            'phone' ,
            'genre',
            'address_street',
            'address_state',
            'address_number',
            'address_district',
            'address_zip_code',
            'address_city',
            'address_country',
            'current_balance',
            'type_of_investor',
            'last_month'
        ]));
        $this->createUserClientServices->execute();
        return new JsonResponse(
            [
                'message' =>  'Usuário criado, envio de ',
                'user' => $this->createUserClientServices->getUserData()
            ],
            200
        );
    }
}
