<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyVcardRequest;
use App\Models\Vcard;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

use App\Http\Resources\VcardResource;
use App\Http\Requests\UpdateVCardRequest;
use App\Http\Requests\LoginVCardTAESRequest;
use App\Http\Requests\StoreVCardRequest;
use App\Http\Requests\StoreVCardTAESRequest;
use App\Http\Requests\UpdateVcardConfirmationCodeRequest;
use App\Http\Requests\UpdateVCardTAESRequest;
use App\Http\Requests\UpdateVcardPasswordRequest;
use App\Http\Requests\VcardRequest;
use App\Models\Category;
use App\Models\DefaultCategory;
use App\Services\Base64Services;
use Illuminate\Support\Facades\Storage;

class VcardController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Vcard::class, 'vcard', [
            'except' => ['showExtraInfo', 'showCategories', 'show_me', 'showTransactions', 'updatePassword', 'updateConfirmationCode', 'storeBase64AsFile', 'store'],
        ]);
    }

    public function index()
    {
        return VcardResource::collection(Vcard::all());
    }

    public function show(Vcard $vcard)
    {
        VcardResource::$format = 'default';
        return new VcardResource($vcard);
    }

    public function show_me(Request $request)
    {
        //$this->authorize('viewMe');
        VcardResource::$format = 'default';
        return new VcardResource($request->user());
    }

    public function show_extra_info(Vcard $vcard)
    {
        //$this->authorize('viewExtraInfo');
        return response()->json([
            'max_debit' => $vcard->max_debit,
            'balance' => $vcard->balance,
        ]);
    }

    public function show_categories(Request $request, Vcard $vcard)
    {
        $this->authorize('viewCategories', $vcard);
        $categoriesQuery = $vcard->categories();

        if ($request->has('page')) {

            $filterByType = $request->type ?? '';
            $filterByName = $request->name ?? '';
            if ($filterByType != "") {
                $categoriesQuery->where('type', '=', $filterByType);
            }
            if ($filterByName !== "") {
                $categoriesQuery->whereRaw('UPPER(name) LIKE ?', ['%' . strtoupper($filterByName) . '%']);
            }
            $categories = $categoriesQuery->paginate(10);
        } else {
            $categories = $categoriesQuery->get();
        }

        return $categories;
    }

    public function show_transactions(Request $request, Vcard $vcard)
    {
        $this->authorize('viewTransactions', $vcard);
        $query = $vcard->transactions();

        $filterByDateStart = $request->date_start ?? '';
        $filterByDateFinish = $request->date_finish ?? '';
        $filterByPaymentType = $request->payment_type ?? '';
        $filterByType = $request->type ?? '';
        $filterByVcard = $request->vcard ?? '';

        if ($filterByDateStart != "") {
            $query->where('date', '>=', $filterByDateStart);
        }
        if ($filterByDateFinish != "") {
            $query->where('date', '<=', $filterByDateFinish);
        }

        if ($filterByPaymentType != "") {
            $query->where('payment_type', '=', $filterByPaymentType);
            if ($filterByPaymentType == "VCARD" && $filterByVcard != "") {
                $query->where('pair_vcard', '=', $filterByVcard);
            }
        }

        if ($filterByType != "") {
            $query->where('type', '=', $filterByType);
        }

        return response()->json([
            'transactions' => $query->paginate(10)
        ]);
    }

    private function storeBase64AsFile(Vcard $vcard, String $base64String)
    {
        $targetDir = storage_path('app/public/fotos');
        $newfilename = $vcard->id . "_" . rand(1000, 9999);
        $base64Service = new Base64Services();
        return $base64Service->saveFile($base64String, $targetDir, $newfilename);
    }

    public function store(StoreVCardRequest $request)
    {
        $dataToSave = $request->validated();

        $base64ImagePhoto = array_key_exists("base64ImagePhoto", $dataToSave) ?
            $dataToSave["base64ImagePhoto"] : ($dataToSave["base64ImagePhoto"] ?? null);
        unset($dataToSave["base64ImagePhoto"]);


        $vcard = new VCard();

        $vcard->phone_number = $dataToSave['phone_number'];
        $vcard->name = $dataToSave['name'];
        $vcard->email = $dataToSave['email'];
        $vcard->password = bcrypt($dataToSave['password']);
        $vcard->blocked = 0;
        $vcard->balance = 0.00;
        $vcard->max_debit = 5000;
        $vcard->confirmation_code = bcrypt($dataToSave['confirmation_code']);
        $vcard->custom_options = $dataToSave['custom_options'] ?? null;
        $vcard->custom_data = $dataToSave['custom_data'] ?? null;

        if ($base64ImagePhoto) {
            $vcard->photo_url = $this->storeBase64AsFile($vcard, $base64ImagePhoto);
        }

        $vcard->save();

        //Load the default categories into the Categories table associated with the vcard
        $defaultCategories = DefaultCategory::all();

        foreach ($defaultCategories as $defaultCategory) {
            $category = new Category([
                'vcard' => $vcard->phone_number,
                'name' => $defaultCategory->name,
                'type' => $defaultCategory->type,
                'custom_options' => $defaultCategory->custom_options ?? null,
                'custom_data' => $defaultCategory->custom_data ?? null,
            ]);
            //$category->vcard()->associate($vcard); unnecessary?
            $category->save();
            //$vcard->categories()->attach($category); unnecessary?
        }

        VcardResource::$format = 'default';
        return new VcardResource($vcard);
    }


    public function update(UpdateVCardRequest $request, Vcard $vcard)
    {
        $dataToSave = $request->validated();

        if (Gate::denies('admin')) {
            $vcard->name = $dataToSave['name'] ?? $vcard->name;
            $vcard->email = $dataToSave['email'] ?? $vcard->email;

            $base64ImagePhoto = array_key_exists("base64ImagePhoto", $dataToSave) ?
                $dataToSave["base64ImagePhoto"] : ($dataToSave["base64ImagePhoto"] ?? null);
            $deletePhotoOnServer = array_key_exists("deletePhotoOnServer", $dataToSave) && $dataToSave["deletePhotoOnServer"];
            unset($dataToSave["base64ImagePhoto"]);
            unset($dataToSave["deletePhotoOnServer"]);

            if ($vcard->photo_url && ($deletePhotoOnServer || $base64ImagePhoto)) {
                if (Storage::exists('public/storage/fotos/' . $vcard->photo_url)) {
                    Storage::delete('public/storage/fotos/' . $vcard->photo_url);
                }
                $vcard->photo_url = null;
            }

            if ($base64ImagePhoto) {
                $vcard->photo_url = $this->storeBase64AsFile($vcard, $base64ImagePhoto);
            }

            $vcard->custom_options = $dataToSave['custom_options'] ?? $vcard->custom_options;
            $vcard->custom_data = $dataToSave['custom_data'] ?? $vcard->custom_data;
        } else {
            $vcard->blocked = $dataToSave['blocked'] ?? $vcard->blocked;
            $vcard->max_debit = $dataToSave['max_debit'] ?? $vcard->max_debit;
            $vcard->custom_options = $dataToSave['custom_options'] ?? $vcard->custom_options;
            $vcard->custom_data = $dataToSave['custom_data'] ?? $vcard->custom_data;
        }

        $vcard->save();
        return new VcardResource($vcard);
    }

    public function update_password(UpdateVcardPasswordRequest $request, Vcard $vcard)
    {
        $this->authorize('updatePassword', $vcard);
        $vcard->password = bcrypt($request->validated()['password']);
        $vcard->save();
        return new VcardResource($vcard);
    }

    public function update_confirmation_code(UpdateVcardConfirmationCodeRequest $request, Vcard $vcard)
    {
        $this->authorize('updateConfirmationCode', $vcard);
        $vcard->confirmation_code = bcrypt($request->validated()['confirmation_code']);
        $vcard->save();
        return new VcardResource($vcard);
    }

    public function destroy(DestroyVcardRequest $request, Vcard $vcard)
    {
        $request->validated();

        try {
            if ($vcard->balance != 0) {
                throw new \Exception('Cannot delete Vcard with non-zero balance');
            }
            if ($vcard->transactions()->count() > 0) {
                $vcard->delete();
            } else {
                $vcard->categories()->forceDelete();
                $vcard->forceDelete();
            }
            return new VcardResource($vcard);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }














    //-------TAES--------functions

    //Guarda um novo VCard
    public function storeTAES(StoreVCardTAESRequest $request)
    {
        $dataToSave = $request->validated();

        //$customData = $request->input('custom_data', null);

        $vcard = new VCard();
        $vcard->phone_number = $dataToSave['phone_number'];
        $vcard->name = $dataToSave['phone_number'];
        $vcard->email = $dataToSave['phone_number'] . 'taes@mail.pt';
        $vcard->password = bcrypt($dataToSave['password']);
        $vcard->confirmation_code = bcrypt("123");
        $vcard->blocked = 0;
        $vcard->balance = 0.00;
        $vcard->max_debit = 5000;

        $customData = array(
            "piggy_bank" => "0",
            "pin" => $dataToSave['pin'],
            "receive_notifications" => true
        );

        //return $customData;

        $vcard->custom_data = json_encode($customData);

        //get the array
        //$value = json_decode($vcard->custom_data, true);

        // if ($customData) {
        //     $vcard->custom_data = json_encode($customData['balance']);
        // }

        $vcard->save();

        $VCardUpdated = Vcard::where('phone_number', $vcard->phone_number)->first();

        VcardResource::$format = 'TAES';
        return new VcardResource($VCardUpdated);
    }

    public function updateTAES(UpdateVCardTAESRequest $request, VCard $vcard)
    {
        $data = $request->validated();

        //Podia ser verificado se o $vcard existe porque se não existir envia erro de query

        if ($vcard->custom_data == null) {
            $vcard->custom_data = array(
                "piggy_bank" => "0",
                "pin" => "1234",
                "receive_notifications" => true
            );
            $vcard->save();
        }

        $customData = json_decode($vcard->custom_data, true);

        switch ($data['type']) {
            case 'ADD':
                if ($data['value'] + $customData['piggy_bank'] <= $vcard->balance) {
                    //Add to PiggyBank
                    $customData['piggy_bank'] += $data['value'];
                    $customData['piggy_bank'] = strval($customData['piggy_bank']);
                    $vcard->custom_data = $customData; //Updates PiggyBank Balance
                    $vcard->save();
                }
                break;
            case 'WITHDRAW':
                if ($data['value'] <= $customData['piggy_bank']) {
                    //Withdraw
                    $customData['piggy_bank'] -= $data['value'];
                    $customData['piggy_bank'] = strval($customData['piggy_bank']);
                    $vcard->custom_data = $customData; //Updates PiggyBank Balance
                    $vcard->save();
                }
                break;
            default:
                return "How did you get here";
                break;
        }
        $VCardUpdated = Vcard::where('phone_number', $vcard->phone_number)->first();

        VcardResource::$format = 'TAES';
        return new VcardResource($VCardUpdated);
    }

    public function getTAES(String $phone_number)
    {
        try {
            $vcard1 = Vcard::findOrFail($phone_number);
            if (!isset($vcard1->custom_data)) {
                $vcard1->custom_data = array(
                    "piggy_bank" => "0",
                    "pin" => "1234",
                    "receive_notifications" => true
                );
                $vcard1->save();
            }

            $vcard = Vcard::findOrFail($phone_number);

            VcardResource::$format = 'TAES';
            return new VcardResource($vcard);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Vcard not found'], 404);
        }
    }

    public function loginTAES(LoginVCardTAESRequest $request)
    {
        $data = $request->validated();

        $VCardToCompare = Vcard::where('phone_number', $data['phone_number'])->first();

        if (!isset($VCardToCompare)) {
            return response()->json(['message' => 'Not found user with phone number'], 404);
        }

        if ($VCardToCompare->custom_data == null) {
            $VCardToCompare->custom_data = array(
                "piggy_bank" => "0",
                "pin" => "1234",
                "receive_notifications" => true
            );
            $VCardToCompare->save();
        }

        if (!isset($data['password'])) {
            return response()->json(['message' => 'The password field is required'], 400);
        }

        if (Hash::check($data['password'], $VCardToCompare->password)) {
            $VCardToCompare = Vcard::where('phone_number', $data['phone_number'])->first();
            VcardResource::$format = 'TAES';
            return new VcardResource($VCardToCompare);
        }
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function loginPinTAES(LoginVCardTAESRequest $request)
    {
        $data = $request->validated();

        $VCardToCompare = Vcard::where('phone_number', $data['phone_number'])->first();

        if (!isset($VCardToCompare)) {
            return response()->json(['message' => 'Not found user with phone number'], 404);
        }

        if ($VCardToCompare->custom_data == null) {
            $VCardToCompare->custom_data = array(
                "piggy_bank" => "0",
                "pin" => "1234",
                "receive_notifications" => true
            );
            $VCardToCompare->save();
            //Refresh the $VCard because it breaks when tries to json_decode
            $VCardToCompare = Vcard::where('phone_number', $data['phone_number'])->first();
        }

        if (!isset($data['pin'])) {
            return response()->json(['message' => 'The pin field is required'], 400);
        }
        if ($data['pin'] == json_decode($VCardToCompare->custom_data, true)['pin']) {
            $VCardToCompare = Vcard::where('phone_number', $data['phone_number'])->first();
            VcardResource::$format = 'TAES';
            return new VcardResource($VCardToCompare);
        }
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function updateSaldo(Request $request, String $phone_number)
    {
        $VCard = Vcard::where('phone_number', $phone_number)->first();
        if (!isset($VCard)) {
            return response()->json(['message' => 'Not found user with phone number'], 404);
        }

        if ($VCard->custom_data == null) {
            $VCard->custom_data = array(
                "piggy_bank" => "0",
                "pin" => "1234",
                "receive_notifications" => true
            );
            $VCard->save();
            //Refresh the $VCard because it breaks when tries to json_decode
            $VCard = Vcard::where('phone_number', $phone_number)->first();
        }

        $VCard->balance = $request['balance'];
        $VCard->save();

        $VCard = Vcard::where('phone_number', $phone_number)->first();

        VcardResource::$format = 'TAES';
        return new VcardResource($VCard);
    }



    //multiple numbers
    public function validNumbers(Request $request)
    {
        $phoneNumbersJson = $request->input([]);

        $phoneNumbers = $phoneNumbersJson['phone_numbers'];

        $validNumbers = [];
        foreach ($phoneNumbers as $phone_number) {
            $vcard = Vcard::where('phone_number', $phone_number)->first();
            if ($vcard) {
                $validNumbers[] = $vcard->phone_number;
            }
        }

        return response()->json(['phone_numbers' => $validNumbers], 200);
    }

    public function updateReceiveNotificationsTAES(Request $request, String $phone_number)
    {
        $VCard = Vcard::where('phone_number', $phone_number)->first();
        if (!isset($VCard)) {
            return response()->json(['message' => 'Not found user with phone number'], 404);
        }

        if ($VCard->custom_data == null || !isset($VCard->custom_data->receive_notifications)) {
            $VCard->custom_data = array(
                "piggy_bank" => "0",
                "pin" => "1234",
                "receive_notifications" => true
            );
            $VCard->save();
            //Refresh the $VCard because it breaks when tries to json_decode
            $VCard = Vcard::where('phone_number', $phone_number)->first();
        }
        $customData = json_decode($VCard->custom_data, true);
        $customData['receive_notifications'] = $request->input('receivesNotifications');
        $VCard->custom_data = $customData;
        $VCard->save();


        $VCard = Vcard::where('phone_number', $phone_number)->first();

        VcardResource::$format = 'TAES';
        return new VcardResource($VCard);
    }
}
