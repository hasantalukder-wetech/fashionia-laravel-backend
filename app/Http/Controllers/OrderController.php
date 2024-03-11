<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderRequestLog;
use App\Models\Product;

// Add the Product model
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {

            DB::beginTransaction();

            $invoiceNumber = uniqid();

            // Validate request data
            $request->validate([
                'customer_info.customer_name' => 'required|string',
                'customer_info.phone' => 'required|string',
                'customer_info.address' => 'required|string',
                'customer_info.shipping_type' => 'required|string',
                'payment_option.method' => 'required|string',
                'payment_option.transaction_number' => '',
                'product_items.*.product_id' => 'required|integer',
                'product_items.*.size' => 'required|string',
                'product_items.*.quantity' => 'required|integer',
            ]);


            // Create order with invoice number
            $order = Order::create([
                'customer_name' => $request->input('customer_info.customer_name'),
                'phone' => $request->input('customer_info.phone'),
                'address' => $request->input('customer_info.address'),
                'shipping_type' => $request->input('customer_info.shipping_type'),
                'payment_method' => $request->input('payment_option.method'),
                'transaction_number' => $request->input('payment_option.transaction_number'),
                'invoice_number' => $invoiceNumber, // Insert invoice number
            ]);

            $purchase_amount = 0;

            // Add product items
            foreach ($request->input('product_items') as $item) {
                // Get product details
                try {
                    // Get product details
                    $product = Product::findOrFail($item['product_id']);
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    // Handle case where product is not found
                    throw new Exception('Product not found. Product id: ' . $item['product_id']);
                }

                // Calculate total price based on quantity and product price
                $product_price = $product->price;
                if (!is_null($product->discount_price)) {
                    $product_price = $product->price - $product->discount_price;
                }

                $totalPrice = $item['quantity'] * $product_price;

                // Subtract discount amount if available

                // Create order item with calculated total price
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'size' => $item['size'],
                    'quantity' => $item['quantity'],
                    'total_price' => $totalPrice,
                ]);

                $purchase_amount += $totalPrice;
            }

            $order->update(['purchase_amount' => $purchase_amount]);

            DB::commit();

            try {
                OrderRequestLog::create([
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'headers' => json_encode($request->headers->all()),
                    'payload' => json_encode($request->all()),
                    'response_code' => 201, // Add response code if needed
                    'response_content' => $order, // Add response content if needed
                ]);
            } catch (Exception $exception) {

            }


            return response()->json($order, 201);
        } catch (\Exception $e) {
            DB::rollBack();

            try {
                OrderRequestLog::create([
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'headers' => json_encode($request->headers->all()),
                    'payload' => json_encode($request->all()),
                    'response_code' => 422, // Add response code if needed
                    'response_content' => $e->getMessage(),
                ]);

                return response()->json(['message' => $e->getMessage()], 422);

            } catch (Exception $exception) {

            }
        }
    }

    public function index()
    {
        $orders = Order::with(['items.product:id,single_image,prd_title', 'items'])->get();

        // Modify the response to include image URL from the product table
        $orders->transform(function ($order) {
            $order->items->transform(function ($item) {
                $product = $item->product;
                $item->image_url = $product->single_image; // Assuming the image URL is stored in the 'single_image' column
                $item->product_title = $product->prd_title; // Assuming the image URL is stored in the 'single_image' column
                unset($item->product); // Remove the product object from the item
                return $item;
            });

            // Format the dates using Carbon
            $order->order_date = date('d F, Y \a\t h:ia', strtotime($order->created_at));

            return $order;
        });

        return response()->json($orders, 200);
    }

    public function show($id)
    {
        try {
            $order = Order::with(['items.product:id,single_image,prd_title', 'items'])->findOrFail($id);

            // Modify the response to include image URL from the product table
            $order->items->transform(function ($item) {
                $product = $item->product;
                $item->image_url = $product->single_image; // Assuming the image URL is stored in the 'single_image' column
                $item->product_title = $product->prd_title; // Assuming the image URL is stored in the 'single_image' column
                unset($item->product); // Remove the product object from the item
                return $item;
            });

            // Format the date using Carbon
            $order->order_date = $order->created_at->format('d F, Y \a\t h:ia');
        } catch (ModelNotFoundException  $exception) {
            return response()->json(['message' => 'Order not found'], 404);
        }


        return response()->json($order, 200);
    }





//    public function show($id)
//    {
//        $order = Order::with('items')->findOrFail($id); // Fetch order by ID with its associated items
//        return response()->json($order, 200);
//    }


    public function destroy(Request $request, $id)
    {
        // Find the product by its ID
        $order = Order::find($id);

        // If the product is not found, return 404 response
        if (!$order) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Delete the product
        $order->delete();

        // Return success response
        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'status' => 'required', // Define the allowed status values
            ]);

            $valide_status = ['pending', 'processing', 'delivered', 'cancelled'];

            if (!in_array($validatedData['status'], $valide_status)) {
                // Handle additional checks here
                throw new Exception("Invalid status. Allow status: pending, processing, delivered, cancelled", 422);
            }

            // Find the order by ID
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            // Validate the status transition
            $currentStatus = $order->status;
            $newStatus = $validatedData['status'];


            if ($currentStatus === 'pending' && !in_array($newStatus, ['processing', 'cancelled'])) {
                throw new Exception('Invalid status transition. Current status: ' . $currentStatus);
            }

            if ($currentStatus === 'processing' && !in_array($newStatus, ['delivered', 'cancelled'])) {
                throw new Exception('Invalid status transition. Current status: ' . $currentStatus);
            }

            if ($currentStatus === 'delivered' && in_array($newStatus, ['pending', 'cancelled', 'processing', 'delivered'])) {
                throw new Exception('Invalid status transition. Current status: ' . $currentStatus);
            }

            if ($currentStatus === 'cancelled' && in_array($newStatus, ['processing', 'delivered', 'pending', 'cancelled'])) {
                throw new Exception('Invalid status transition. Current status: ' . $currentStatus);
            }

            // Update the status of the order
            $order->update(['status' => $newStatus]);

            // Return the updated order
            return response()->json($order, 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }


}
