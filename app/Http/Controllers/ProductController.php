<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request  $request)
    {
        $products = Product::with(['variant_prices'=>function($query){
            return $query->with(['variant_one', 'variant_two', 'variant_three']);
        }])->searchBy($request)->paginate(3);

        $variants = Variant::with(['product_variants'=>function($query){
            return $query->groupBy('variant');
        }])->get();
        return view('products.index', compact('products', 'variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'title'=>'required|string',
            'sku'=>'required|string|max:120|unique:products',
            'description'=>'required|string',
            'product_image'=>'required|array',
            'product_variant'=>'required|array',
            'product_variant_prices'=>'required|array',
        ]);

        if($validator->passes()){
            try{
                DB::beginTransaction();
                $product = Product::create([
                    'title'=>$request->title,
                    'sku'=>$request->sku,
                    'description'=>$request->description,
                ]);
                if (!empty($product)){
                    if (!empty($request->product_image) && count($request->product_image) > 0){
                        $proImages = array();
                        foreach ($request->product_image as $key=> $image){
                            $imagePath = $this->image_store($image);
                            if (!empty($imagePath)){
                                array_push($proImages, [
                                    'product_id'=>$product->id,
                                    'file_path'=>$imagePath,
                                    'thumbnail'=>0,
                                    'created_at'=>now()
                                ]);
                            }
                        }
                        if(!empty($proImages) && count($proImages) > 0){
                            ProductImage::insert($proImages);
                        }

                    }

                    if (!empty($request->product_variant)){
                        $proVariants = array();

                        foreach ($request->product_variant as $variant){
                            /*return \response()->json($variant['tags']);*/
                            if (!empty($variant['tags']) && count($variant['tags']) > 0){
                                foreach ($variant['tags'] as $key => $tag){
                                    if (!empty($tag)){
                                        array_push($proVariants, [
                                            'variant'=>$tag,
                                            'variant_id'=>$variant['option'],
                                            'product_id'=>$product->id,
                                            'created_at'=>now()
                                        ]);
                                    }
                                }
                            }
                        }

                        if(!empty($proVariants) && count($proVariants) > 0){
                            $proVar= ProductVariant::insert($proVariants);
                            if (!empty($proVar)){

                                if (!empty($request->product_variant_prices) && count($request->product_variant_prices)> 0){
                                    $proVarPrices = array();

                                    foreach ($request->product_variant_prices as $product_variant_price){
                                        if (!empty($product_variant_price['title'])){
                                            $tagArray = explode('/', $product_variant_price['title']);

                                            $variantOne = null;
                                            $variantTwo = null;
                                            $variantThree = null;
                                            if(!empty($tagArray[0])){
                                                $variant = ProductVariant::where('product_id', $product->id)
                                                    ->where('variant', $tagArray[0])->first();
                                                if (!empty($variant)){
                                                    $variantOne = $variant->id;
                                                }
                                            }
                                            if(!empty($tagArray[0])){
                                                $variant = ProductVariant::where('product_id', $product->id)
                                                    ->where('variant', $tagArray[1])->first();
                                                if (!empty($variant)){
                                                    $variantTwo = $variant->id;
                                                }
                                            }
                                            if(!empty($tagArray[0])){
                                                $variant = ProductVariant::where('product_id', $product->id)
                                                    ->where('variant', $tagArray[2])->first();
                                                if (!empty($variant)){
                                                    $variantThree = $variant->id;
                                                }
                                            }

                                            array_push($proVarPrices, [
                                                'product_variant_one'=>$variantOne,
                                                'product_variant_two'=>$variantTwo,
                                                'product_variant_three'=>$variantThree,
                                                'price'=>$product_variant_price['price'],
                                                'stock'=>$product_variant_price['stock'],
                                                'product_id'=>$product->id,
                                                'created_at'=>now()
                                            ]);
                                        }
                                    }

                                    if (!empty($proVarPrices) && count($proVarPrices) > 0){
                                        ProductVariantPrice::insert($proVarPrices);
                                    }
                                }
                            }

                        }
                    }

                    DB::commit();
                    return \response()->json(
                        [
                            'statusText'=>'success',
                            'status'=>Response::HTTP_CREATED,
                            'message'=>'Product Created Successfully'
                        ], 200
                    );
                }else{
                    throw new Exception('Invalid Product Information', Response::HTTP_BAD_REQUEST);
                }

            }catch (Exception $ex){
                DB::rollBack();
                return \response()->json(
                    [
                        'statusText'=>'error',
                        'status'=>$ex->getCode(),
                        'message'=>$ex->getMessage()
                    ], 200
                );
            }
        }else{
            $errors = array_values($validator->errors()->getMessages());
            $message = null;
            foreach ($errors as $error){
                if(!empty($error)){
                    foreach ($error as $errorItem){
                        $message .=  $errorItem .'<br/> ';
                    }
                }
            }
            return response()->json([
                'statusText'=>'error',
                'status'=>Response::HTTP_NOT_ACCEPTABLE,
                'message'=>$message,
            ], 200);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|Response|\Illuminate\View\View
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        $product = $product->load(['images', 'variant_prices'=>function($query){
            return $query->with(['variant_one', 'variant_two', 'variant_three']);
        }]);

        $pVariants = ProductVariant::where('product_id', $product->id)->get();
        $pVariants = $pVariants
            ->groupBy(function($item){
                return $item['variant_id'];
            });
        $vData = array();
        foreach ($pVariants as $key=> $item){
            array_push($vData, [
                'option'=> $key,
                'tags'=>$item->pluck('variant')
            ]);
        }

        $vPData = array();
        foreach ($product->variant_prices as $variant_price) {
            $title = '';
            if (!empty($variant_price->variant_one)){
                $title .= $variant_price->variant_one->variant;
            }
            if (!empty($variant_price->variant_two)){
                $title = $title.'/'. $variant_price->variant_two->variant;
            }
            if (!empty($variant_price->variant_three)){
                $title = $title.'/'. $variant_price->variant_three->variant;
            }

            array_push($vPData, [
                'title'=> $title,
                'price'=> $variant_price->price,
                'stock'=> $variant_price->stock,
            ]);
        }

        $data = [
            'id'=> $product->id,
            'title'=> $product->title,
            'sku'=> $product->sku,
            'description'=> $product->description,
            'variants'=> $vData,
            'images'=> $product->images->pluck('file_path'),
            'variant_prices'=>$vPData
        ];

        return view('products.edit', compact('variants', 'data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Product $product)
    {
        $proId = $product->id;
        $validator = Validator::make($request->all(),[
            'title'=>'required|string',
            'sku'=>'required|string|max:120|unique:products,sku,'.$proId.',id',
            'description'=>'required|string',
            'product_image'=>'required|array',
            'product_variant'=>'required|array',
            'product_variant_prices'=>'required|array',
        ]);

        if($validator->passes()){
            try{
                DB::beginTransaction();

                $productU = $product->update([
                    'title'=>$request->title,
                    'sku'=>$request->sku,
                    'description'=>$request->description,
                ]);
                if (!empty($productU)){
                    if (!empty($request->product_image) && count($request->product_image) > 0){
                        $proImages = array();
                        foreach ($request->product_image as $key=> $image){
                            if (!empty($image)){
                                $imagePath = $this->image_store($image);
                                if (!empty($imagePath)){
                                    array_push($proImages, [
                                        'product_id'=>$proId,
                                        'file_path'=>$imagePath,
                                        'thumbnail'=>0,
                                        'created_at'=>now()
                                    ]);
                                }
                            }
                        }
                        if(!empty($proImages) && count($proImages) > 0){
                            ProductImage::insert($proImages);
                        }

                    }
                    ProductVariant::where('product_id', $proId)->delete();
                    ProductVariantPrice::where('product_id', $proId)->delete();
                    if (!empty($request->product_variant)){
                        $proVariants = array();
                        foreach ($request->product_variant as $variant){
                            /*return \response()->json($variant['tags']);*/
                            if (!empty($variant['tags']) && count($variant['tags']) > 0){
                                foreach ($variant['tags'] as $key => $tag){
                                    if (!empty($tag)){
                                        array_push($proVariants, [
                                            'variant'=>$tag,
                                            'variant_id'=>$variant['option'],
                                            'product_id'=>$proId,
                                            'created_at'=>now()
                                        ]);
                                    }
                                }
                            }
                        }

                        if(!empty($proVariants) && count($proVariants) > 0){
                            $proVar= ProductVariant::insert($proVariants);
                            if (!empty($proVar)){

                                if (!empty($request->product_variant_prices) && count($request->product_variant_prices)> 0){
                                    $proVarPrices = array();

                                    foreach ($request->product_variant_prices as $product_variant_price){
                                        if (!empty($product_variant_price['title'])){
                                            $tagArray = explode('/', $product_variant_price['title']);

                                            $variantOne = null;
                                            $variantTwo = null;
                                            $variantThree = null;
                                            if(!empty($tagArray[0])){
                                                $variant = ProductVariant::where('product_id', $proId)
                                                    ->where('variant', $tagArray[0])->first();
                                                if (!empty($variant)){
                                                    $variantOne = $variant->id;
                                                }
                                            }
                                            if(!empty($tagArray[0])){
                                                $variant = ProductVariant::where('product_id', $proId)
                                                    ->where('variant', $tagArray[1])->first();
                                                if (!empty($variant)){
                                                    $variantTwo = $variant->id;
                                                }
                                            }
                                            if(!empty($tagArray[0])){
                                                $variant = ProductVariant::where('product_id', $proId)
                                                    ->where('variant', $tagArray[2])->first();
                                                if (!empty($variant)){
                                                    $variantThree = $variant->id;
                                                }
                                            }

                                            array_push($proVarPrices, [
                                                'product_variant_one'=>$variantOne,
                                                'product_variant_two'=>$variantTwo,
                                                'product_variant_three'=>$variantThree,
                                                'price'=>$product_variant_price['price'],
                                                'stock'=>$product_variant_price['stock'],
                                                'product_id'=>$proId,
                                                'created_at'=>now()
                                            ]);
                                        }
                                    }

                                    if (!empty($proVarPrices) && count($proVarPrices) > 0){
                                        ProductVariantPrice::insert($proVarPrices);
                                    }
                                }
                            }

                        }
                    }

                    DB::commit();
                    return \response()->json(
                        [
                            'statusText'=>'success',
                            'status'=>Response::HTTP_OK,
                            'message'=>'Product Updated Successfully'
                        ], 200
                    );
                }else{
                    throw new Exception('Invalid Product Information', Response::HTTP_BAD_REQUEST);
                }

            }catch (Exception $ex){
                DB::rollBack();
                return \response()->json(
                    [
                        'statusText'=>'error',
                        'status'=>$ex->getCode(),
                        'message'=>$ex->getMessage()
                    ], 200
                );
            }
        }else{
            $errors = array_values($validator->errors()->getMessages());
            $message = null;
            foreach ($errors as $error){
                if(!empty($error)){
                    foreach ($error as $errorItem){
                        $message .=  $errorItem .'<br/> ';
                    }
                }
            }
            return response()->json([
                'statusText'=>'error',
                'status'=>Response::HTTP_NOT_ACCEPTABLE,
                'message'=>$message,
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    private function image_store($imageURL) {

        $image_array_1 = explode(";", $imageURL);
        $image_array_2 = explode(",", $image_array_1[1]);
        $ImageData = base64_decode($image_array_2[1]);

        try {

            $imageInfo = getimagesizefromstring($ImageData);
            $ext = image_type_to_extension($imageInfo[2]);
            $name =  md5(rand(1111, 9999). time()).$ext;
            $name_S = 'product/' . $name;
            $name_full = 'public/' . $name_S;
            Storage::disk('local')->put( $name_full,$ImageData);

            return $name_S;
        }catch (Exception $ex) {
            return false;
        }

    }
}
