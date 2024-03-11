<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Mockery\Exception;

class ProductController extends Controller
{

    public function index()
    {
        // Fetch all products from the database
        $products = Product::all();

        // Transform the products data to match the desired response format
        $transformedProducts = [];
        foreach ($products as $product) {
            $transformedProducts[] = [
                'id' => $product->id,
                'prd_title' => $product->prd_title,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'type_product' => $product->type_product,
                'prdSizeList' => json_decode($product->prdSizeList),
                'single_image' => $product->single_image,
                'multiple_images' => json_decode($product->multiple_images),
                'createdAt' => $product->created_at->toIso8601String(),
                'updatedAt' => $product->updated_at->toIso8601String(),
            ];
        }

        // Return response with the list of products
        return response()->json($transformedProducts, 200);
    }

    public function show($id)
    {
        // Find the product by its ID
        $product = Product::find($id);

        // If the product is not found, return 404 response
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Transform the product data to match the desired response format
        $transformedProduct = [
            'id' => $product->id,
            'prd_title' => $product->prd_title,
            'quantity' => $product->quantity,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'type_product' => $product->type_product,
            'prdSizeList' => json_decode($product->prdSizeList),
            'single_image' => $product->single_image,
            'multiple_images' => json_decode($product->multiple_images),
            'createdAt' => $product->created_at->toIso8601String(),
            'updatedAt' => $product->updated_at->toIso8601String(),
        ];

        // Return response with the single product
        return response()->json($transformedProduct, 200);
    }

    public function store(Request $request)
    {

        try {

            // Validate request data
            $request->validate([
                'prd_title' => 'required|string',
                'quantity' => 'required|integer',
                'price' => 'required|numeric',
                'discount_price' => 'nullable|numeric',
                'type_product' => 'required|string',
                'prdSizeList' => 'required|array',
                'prdSizeList.*' => 'string',
            ]);

            // Upload single image
            $singleImage = $this->uploadImage($request->file('single_image'), 'single_image');

            // Upload multiple images
            $multipleImages = [];
            if ($request->hasFile('multiple_images')) {
                foreach ($request->file('multiple_images') as $image) {
                    $multipleImages[] = $this->uploadImage($image, 'multiple_images');
                }
            }
            else{
                $multipleImages = ['No Data in Multiple Image Field'];
            }

            // Create a new Product instance and store in the database
            $product = Product::create([
                'prd_title' => $request->input('prd_title'),
                'quantity' => $request->input('quantity'),
                'price' => $request->input('price'),
                'discount_price' => $request->input('discount_price'),
                'type_product' => $request->input('type_product'),
                'single_image' => $singleImage,
                'multiple_images' => json_encode($multipleImages, JSON_UNESCAPED_SLASHES),
                'prdSizeList' => json_encode($request->input('prdSizeList'))
            ]);

            $transformedProduct = [
                'id' => $product->id,
                'prd_title' => $product->prd_title,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'type_product' => $product->type_product,
                'prdSizeList' => json_decode($product->prdSizeList),
                'single_image' => $product->single_image,
                'multiple_images' => $product->multiple_images,
                'createdAt' => $product->created_at->toIso8601String(),
                'updatedAt' => $product->updated_at->toIso8601String()
            ];
        } catch (Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        // Return response with the created product data
        return response()->json($transformedProduct, 201);
    }

    private function uploadImage($image, $type = 'none')
    {
        try {
            if (!$image) {
                throw new Exception("Image not found in: " . $type, 422);
            }

            $baseUrl = url('/');
            $randomNumber = rand(10000, 99999);
            // Generate a unique image name using the current timestamp and original image name

            $extension = $image->getClientOriginalExtension();
            $imageName = time() . '_' . $randomNumber . '.' . $extension;

            // Define the upload path
            $uploadPath = public_path("uploads/images");

            // Create the folder if it doesn't exist
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Move the uploaded image to the upload path with the generated image name
            $image->move($uploadPath, $imageName);

            $imagePath_share_url = $baseUrl . "/uploads/images/{$imageName}";
            return $imagePath_share_url;

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }


    public function update(Request $request, $id)
    {
        try {

            // Find the product by its ID
            $product = Product::find($id);

            // If the product is not found, return 404 response
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }


            // Prepare the data to update
            $updateData = [
                'prd_title' => $request->input('prd_title'),
                'quantity' => $request->input('quantity'),
                'price' => $request->input('price'),
                'discount_price' => $request->input('discount_price'),
                'type_product' => $request->input('type_product'),
                'prdSizeList' => json_encode($request->input('prdSizeList'))
            ];

            if (is_null($request->input('single_image'))) {
                $updateData['single_image'] = null;
            }

            // If a new single image is provided, upload and update the single_image field
            if ($request->hasFile('single_image')) {
                $singleImage = $this->uploadImage($request->file('single_image'));
                $updateData['single_image'] = $singleImage;
            }


            // If new multiple images are provided, upload and update the multiple_images field
            if (is_null($request->input('multiple_images'))) {
                $updateData['multiple_images'] = null;
            }

// If new multiple images are provided, upload and update the multiple_images field
            if ($request->hasFile('multiple_images')) {
                $multipleImages = [];
                foreach ($request->file('multiple_images') as $image) {
                    $multipleImages[] = $this->uploadImage($image);
                }
                $updateData['multiple_images'] = json_encode($multipleImages, JSON_UNESCAPED_SLASHES);
            }

            // Update the product
            $product->update($updateData);

            // Return response with the updated product data

        } catch (Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getCode());
        }

        return response()->json($product, 200);
    }

    public function destroy(Request $request, $id)
    {
        // Find the product by its ID
        $product = Product::find($id);

        // If the product is not found, return 404 response
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Delete the product
        $product->delete();

        // Return success response
        return response()->json(['message' => 'Product deleted successfully','status'=>200], 200);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string',
                'password' => 'required|string',
            ]);

            $user_name = $request->input('email');
            $password = $request->input('password');

            if ($user_name != 'admin@gmail.com') {
                throw new Exception("Invalid Username");
            }
            if ($password != '123456') {
                throw new Exception("Invalid Password");
            }

            return response()->json(['message' => 'Successfully Login', 'token' => "7878aYt&82@", 'status' => 200], 200);

        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage(), "data" => $request->input()], 422);
        }

    }

    public function logout(Request $request)
    {
        try {
            // Extract the token from the request headers
            $token = $request->header('Authorization');

            // Validate the token against the expected value
            if ($token != '7878aYt&82@') {
                throw new Exception("Invalid Token");
            }

            // Perform logout actions here (e.g., invalidate the token, logout the user)

            return response()->json(['message' => 'Successfully Logged out', 'status' => 200], 200);

        } catch (\Exception $exception) {
            // Return error response if token is invalid or any other exception occurs
            return response()->json(['error' => $exception->getMessage()], 422);
        }
    }

}
