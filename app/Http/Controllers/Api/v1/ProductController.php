<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Resources\v1\product\ProductResource;
use App\Models\Api\v1\Product;
use App\Models\Api\v1\ProductImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends ApiController
{

    public function index()
    {
        $products = Product::paginate(3);
        return $this::successResponse(200,[
            'products'=>ProductResource::collection($products->load('images')),
            'links'=> ProductResource::collection($products)->response()->getData()->links,
            'meta'=> ProductResource::collection($products)->response()->getData()->meta,
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'brand_id'=>'required|integer',
            'category_id'=>'required|integer',
            'primary_image'=>'required|image',
            'description'=>'required|string',
            'price'=>'required|integer',
            'quantity'=>'required|integer',
            'delivery_amount'=>'required|integer',
            'images.*'=>'image'
        ]);
        //return $request->all();

        if ($validator->fails()){
            return $this::errorResponse(422,$validator->messages());
        }

        $primaryImageName = Carbon::now()->microsecond.'.'.$request->primary_image->extension();
        //return $primaryImageName;
        $request->primary_image->move('images/products/primary',$primaryImageName);

        $imagesName = [];
        if ($request->has('images')){
            foreach ($request->images as $image){
                $imageName = Carbon::now()->microsecond.'.'.$image->extension();
                $image->move('images/products/secondary',$imageName);
                array_push($imagesName,$imageName);
            }
        }

        $product = Product::create([
            'name'=>$request->name,
            'brand_id'=>$request->brand_id,
            'category_id'=>$request->category_id,
            'primary_image'=>$primaryImageName,
            'description'=>$request->description,
            'price'=>$request->price,
            'quantity'=>$request->quantity,
            'delivery_amount'=>$request->delivery_amount,
        ]);

        foreach ($imagesName as $imageName){
            ProductImage::create([
                'product_id'=>$product->id,
                'image'=>$imageName
            ]);
        }

        return $this::successResponse(201,new ProductResource($product,));


    }


    public function show($id)
    {
        $product = Product::findOrFail($id);
        return $this::successResponse(200,new ProductResource($product->load('images')));
    }


    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'brand_id'=>'required|integer',
            'category_id'=>'required|integer',
            'primary_image'=>'image|nullable',
            'description'=>'required|string',
            'price'=>'required|integer',
            'quantity'=>'required|integer',
            'delivery_amount'=>'required|integer',
            'images.*'=>'image|nullable'
        ]);
        //return $request->all();

        if ($validator->fails()){
            return $this::errorResponse(422,$validator->messages());
        }
        global $primaryImageName;
        if ($request->has('primary_image')){
             $primaryImageName = Carbon::now()->microsecond.'.'.$request->primary_image->extension();
            $request->primary_image->move('images/products/primary',$primaryImageName);
        }

        $imagesName = [];
        if ($request->has('images')){
            foreach ($request->images as $image){
                $imageName = Carbon::now()->microsecond.'.'.$image->extension();
                $image->move('images/products/secondary',$imageName);
                array_push($imagesName,$imageName);
            }
        }

        $product->update([
            'name'=>$request->name,
            'brand_id'=>$request->brand_id,
            'category_id'=>$request->category_id,
            'primary_image'=>$request->has('primary_image')?$primaryImageName:$product->primary_image,
            'description'=>$request->description,
            'price'=>$request->price,
            'quantity'=>$request->quantity,
            'delivery_amount'=>$request->delivery_amount,
        ]);

        if ($imagesName != []){
            foreach ($product->images as $productImg){
                $productImg->delete();
            }
            foreach ($imagesName as $imageName){
                ProductImage::create([
                    'product_id'=>$product->id,
                    'image'=>$imageName
                ]);
            }

        }
        return $this::successResponse(201,new ProductResource($product));
    }


    public function destroy(Product $product)
    {
        foreach ($product->images as $productImg){
            $productImg->delete();
        }

        $product->delete();

        return $this::successResponse(201,new ProductResource($product));
    }
}
