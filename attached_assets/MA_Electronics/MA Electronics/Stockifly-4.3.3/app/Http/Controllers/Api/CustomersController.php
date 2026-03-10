<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Customer\IndexRequest;
use App\Http\Requests\Api\Customer\StoreRequest;
use App\Http\Requests\Api\Customer\UpdateRequest;
use App\Http\Requests\Api\Customer\DeleteRequest;
use App\Models\Customer;
use App\Traits\PartyTraits;
use Illuminate\Http\Request;

class CustomersController extends ApiBaseController
{
	use PartyTraits;

	protected $model = Customer::class;

	protected $indexRequest = IndexRequest::class;
	protected $storeRequest = StoreRequest::class;
	protected $updateRequest = UpdateRequest::class;
	protected $deleteRequest = DeleteRequest::class;

	public function __construct()
	{
		parent::__construct();

		$this->userType = "customers";
	}

	/**
	 * Find customer by phone number (for POS).
	 */
	public function findByPhone(Request $request)
	{
		$phone = $request->get('phone');
		$customer = Customer::findByPhoneNumber($phone);
		return response()->json(['data' => $customer]);
	}
}
