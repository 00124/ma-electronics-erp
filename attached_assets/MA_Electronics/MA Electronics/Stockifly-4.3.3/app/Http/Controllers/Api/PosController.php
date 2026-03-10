<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Order\PosRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductDetails;
use App\Models\Settings;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UserDetails;
use App\Models\Warehouse;
use Carbon\Carbon;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;

class PosController extends ApiBaseController
{
    public function posProducts()
    {
        $request = request();
        $allProducs = [];
        $warehouse = warehouse();
        $warehouseId = $warehouse->id;

        $products = Product::select(
            'products.id',
            'products.name',
            'products.image',
            'products.product_type',
            'product_details.sales_price',
            'products.unit_id',
            'product_details.sales_tax_type',
            'product_details.tax_id',
            'product_details.current_stock',
            'taxes.rate'
        )
            ->join('product_details', 'product_details.product_id', '=', 'products.id')
            ->leftJoin('taxes', 'taxes.id', '=', 'product_details.tax_id')
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->where('product_details.warehouse_id', '=', $warehouseId);

        $products = $products->where(function ($query) {
            $query->where(function ($qry) {
                $qry->where('products.product_type', '!=', 'service')
                    ->where('product_details.current_stock', '>', 0);
            })->orWhere('products.product_type', '=', 'service');
        });

        if ($warehouse->products_visibility == 'warehouse') {
            $products->where('products.warehouse_id', '=', $warehouse->id);
        }

        // Category Filters
        if ($request->has('category_id') && $request->category_id != "") {
            $categoryId = $this->getIdFromHash($request->category_id);
            $products = $products->where('category_id', '=', $categoryId);
        }

        // Brand Filters
        if ($request->has('brand_id') && $request->brand_id != "") {
            $brandId = $this->getIdFromHash($request->brand_id);
            $products = $products->where('brand_id', '=', $brandId);
        }


        $products =    $products->get();

        foreach ($products as $product) {
            $stockQuantity = $product->current_stock;
            $unit = $product->unit_id != null ? Unit::find($product->unit_id) : null;
            $tax = $product->tax_id != null ? Tax::find($product->tax_id) : null;
            $taxType = $product->sales_tax_type;

            $unitPrice = $product->sales_price;
            $singleUnitPrice = $unitPrice;

            if ($product->rate != '') {
                $taxRate = $product->rate;

                if ($product->sales_tax_type == 'inclusive') {
                    $subTotal = $singleUnitPrice;
                    $singleUnitPrice =  ($singleUnitPrice * 100) / (100 + $taxRate);
                    $taxAmount = ($singleUnitPrice) * ($taxRate / 100);
                } else {
                    $taxAmount =  ($singleUnitPrice * ($taxRate / 100));
                    $subTotal = $singleUnitPrice + $taxAmount;
                }
            } else {
                $taxAmount = 0;
                $taxRate = 0;
                $subTotal = $singleUnitPrice;
            }

            $allProducs[] = [
                'item_id'    =>  '',
                'xid'    =>  $product->xid,
                'name'    =>  $product->name,
                'image'    =>  $product->image,
                'image_url'    =>  $product->image_url,
                'discount_rate'    =>  0,
                'total_discount'    =>  0,
                'x_tax_id'    => $tax ? $tax->xid : null,
                'tax_type'    =>  $taxType,
                'tax_rate'    =>  $taxRate,
                'total_tax'    =>  $taxAmount,
                'x_unit_id'    =>  $unit ? $unit->xid : null,
                'unit'    =>  $unit,
                'unit_price'    =>  $unitPrice,
                'single_unit_price'    =>  $singleUnitPrice,
                'subtotal'    =>  $subTotal,
                'quantity'    =>  1,
                'stock_quantity'    =>  $stockQuantity,
                'unit_short_name'    =>  $unit ? $unit->short_name : '',
                'product_type'      => $product->product_type
            ];
        }

        $data = [
            'products' => $allProducs,
        ];

        return ApiResponse::make('Data fetched', $data);
    }

    public function addPosPayment(PosRequest $request)
    {
        return ApiResponse::make('Success');
    }

    public function savePosPayments()
    {

        $request = request();
        $loggedInUser = user();
        $warehouse = warehouse();
        $orderDetails = $request->details;
        $oldOrderId = "";
        $posDefaultStatus = $request->order_type == 'quotations' ? 'pending' : $warehouse->default_pos_order_status;

        $allPayments = $request->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        if ($request->has('all_payments') && count($request->all_payments) > 0) {
            $allPayments = collect($request->all_payments);

            $total = $allPayments->sum(function ($item) {
                return $item['amount'];
            });

            if ($total > $orderDetails['subtotal']) {
                throw new ApiException('Paid amount should be less than or equal to Grand Total');
            }
        }

        $order = new Order();
        $order->order_type = $request->order_type == 'quotations' ? 'quotations' : 'sales';
        $order->invoice_type = "pos";
        $order->unique_id = Common::generateOrderUniqueId();
        $order->invoice_number = "";
        $order->order_date = Carbon::now();
        $order->warehouse_id = $warehouse->id;
        $order->user_id = isset($orderDetails['user_id']) ? $orderDetails['user_id'] : null;
        $order->tax_id = isset($orderDetails['tax_id']) ? $orderDetails['tax_id'] : null;
        $order->tax_rate = $orderDetails['tax_rate'];
        $order->tax_amount = $orderDetails['tax_amount'];
        $order->discount = $orderDetails['discount'];
        $order->shipping = $orderDetails['shipping'];
        $order->subtotal = 0;
        $order->total = $orderDetails['subtotal'];
        $order->paid_amount = 0;
        $order->due_amount = $order->total;
        $order->order_status = $posDefaultStatus;
        $salesPersonId = isset($orderDetails['staff_user_id']) ? $orderDetails['staff_user_id'] : null;
        if ($salesPersonId) {
            $decodedId = $this->getIdFromHash($salesPersonId);
            if ($decodedId) {
                $order->staff_user_id = $decodedId;
            } else {
                $order->staff_user_id = $loggedInUser->id;
            }
        } else {
            $order->staff_user_id = $loggedInUser->id;
        }
        $order->save();

        $order->invoice_number = Common::getTransactionNumber($order->order_type, $order->id);
        $order->save();

        Common::storeAndUpdateOrder($order, $oldOrderId);

        // Updating Warehouse History
        Common::updateWarehouseHistory('order', $order, "add_edit");

        $allPayments = $request->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        foreach ($allPayments as $allPayment) {
            // Save Order Payment
            if ($allPayment['amount'] > 0 && $allPayment['payment_mode_id'] != '') {
                $payment = new Payment();
                $payment->warehouse_id = $warehouse->id;
                $payment->payment_type = "in";
                $payment->date = Carbon::now();
                $payment->amount = $allPayment['amount'];
                $payment->paid_amount = $allPayment['amount'];
                $payment->payment_mode_id = $allPayment['payment_mode_id'];
                $payment->notes = $allPayment['notes'];
                $payment->user_id = $order->user_id;
                $payment->save();

                // Generate and save payment number
                $paymentType = 'payment-' . $payment->payment_type;
                $payment->payment_number = Common::getTransactionNumber($paymentType, $payment->id);
                $payment->save();

                $orderPayment = new OrderPayment();
                $orderPayment->order_id = $order->id;
                $orderPayment->payment_id = $payment->id;
                $orderPayment->amount = $allPayment['amount'];
                $orderPayment->save();
            }
        }

        Common::updateOrderAmount($order->id);

        $savedOrder = Order::select('id', 'unique_id', 'invoice_number', 'user_id', 'staff_user_id', 'order_date', 'discount', 'shipping', 'tax_amount', 'subtotal', 'total', 'paid_amount', 'due_amount', 'total_items', 'total_quantity', 'order_type')
            ->with(['user:id,name,email', 'items:id,order_id,product_id,unit_id,unit_price,subtotal,quantity,mrp,total_tax', 'items.product:id,name', 'items.unit:id,name,short_name', 'orderPayments:id,order_id,payment_id,amount', 'orderPayments.payment:id,payment_mode_id', 'orderPayments.payment.paymentMode:id,name', 'staffMember:id,name'])
            ->find($order->id);

        $totalMrp = 0;
        $totalTax = 0;
        foreach ($savedOrder->items as $orderItem) {
            $totalMrp += ($orderItem->quantity * $orderItem->mrp);
            $totalTax += $orderItem->total_tax;
        }

        $savingOnMrp = $totalMrp - $savedOrder->total;
        $saving_percentage = $totalMrp > 0 ? number_format((float)($savingOnMrp / $totalMrp * 100), 2, '.', '') : 0;

        $savedOrder->saving_on_mrp = $savingOnMrp;
        $savedOrder->saving_percentage = $saving_percentage;
        $savedOrder->total_tax_on_items = $totalTax + $savedOrder->tax_amount;

        return ApiResponse::make('POS Data Saved', [
            'order' => $savedOrder,
        ]);
    }

    /**
     * Get product stock across all warehouses for POS location modal (store/location selection).
     */
    public function productWarehousesStock()
    {
        $request = request();
        $productId = $request->get('product_id');
        if (!$productId) {
            throw new ApiException('Product ID is required', null, null, 422);
        }
        $decodedProductId = $this->getIdFromHash($productId);
        if (!$decodedProductId) {
            throw new ApiException('Invalid product', null, null, 422);
        }

        $rows = ProductDetails::withoutGlobalScope('current_warehouse')
            ->where('product_id', $decodedProductId)
            ->with(['warehouse:id,name', 'product:id,unit_id', 'product.unit:id,short_name'])
            ->get();

        $list = [];
        foreach ($rows as $pd) {
            $wh = $pd->warehouse;
            $list[] = [
                'warehouse_xid' => $wh ? $wh->xid : null,
                'warehouse_name' => $wh ? $wh->name : '',
                'current_stock' => (float) $pd->current_stock,
                'unit_short_name' => $pd->product && $pd->product->unit ? $pd->product->unit->short_name : '',
            ];
        }

        return ApiResponse::make('Fetched Successfully', ['warehouses_stock' => $list]);
    }

    /**
     * Find existing customer by phone or create new one. Returns customer so POS never hits duplicate error.
     */
    public function ensureCustomer()
    {
        $request = request();
        $name = trim((string) $request->get('name', ''));
        $phone = trim((string) $request->get('phone', ''));
        if ($name === '' || $phone === '') {
            throw new ApiException('Name and phone are required', null, null, 422);
        }

        $customer = Customer::findByPhoneNumber($phone);
        if ($customer) {
            return response()->json(['data' => $customer]);
        }

        $loggedUser = user();
        $warehouse = warehouse();
        $company = company();
        $warehouseId = $warehouse->id;
        if ($loggedUser->hasRole('admin') && $request->get('warehouse_id')) {
            $decoded = $this->getIdFromHash($request->warehouse_id);
            if ($decoded) {
                $warehouseId = $decoded;
            }
        }

        try {
            $customer = new Customer();
            $customer->name = $name;
            $customer->phone = $phone;
            $customer->status = $request->get('status', 'enabled');
            $customer->warehouse_id = $warehouseId;
            $customer->user_type = 'customers';
            $customer->lang_id = $company->lang_id;
            $customer->save();

            $allWarehouses = Warehouse::select('id')->get();
            foreach ($allWarehouses as $w) {
                $userDetails = new UserDetails();
                $userDetails->warehouse_id = $w->id;
                $userDetails->user_id = $customer->id;
                $userDetails->credit_period = 30;
                $userDetails->save();
            }

            return response()->json(['data' => $customer]);
        } catch (\Throwable $e) {
            $customer = Customer::findByPhoneNumber($phone);
            if ($customer) {
                return response()->json(['data' => $customer]);
            }
            throw $e;
        }
    }
}
