<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     tags={"Product"},
     *     path="/api/products",
     *     @OA\Response(response="200", description="List products.")
     * )
     */
    public function getList(){
        $data = Products::all();
        return response()->json($data)->header("Content-Type", "application/json; charset=utf8");
    }

    /**
     * @OA\Post(
     *     tags={"Product"},
     *     path="/api/products",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","image"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="file",
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Add product.")
     * )
     */

    public function create(Request $request){
        $input = $request->all();
        $image = $request->file("image");
        // create image manager with desired driver
        $manager = new ImageManager(new Driver());
        $imageName=uniqid().".webp";

        $sizes = [50, 150, 300, 600, 1200];
        // read image from file system
        foreach ($sizes as $size){
            $imageSave = $manager->read($image);
            // resize image proportionally to 600px width
            $imageSave->scale(width: $size);
            $path = public_path("upload/".$size."_".$imageName);
            // save modified image in new format
            $imageSave->toWebp()->save($path);
        }
        $input["image"] = $imageName;
        $products = Products::create($input);
        return response()->json($products,201,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     tags={"Product"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Ідентифікатор категорії",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успішне видалення категорії"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Категорії не знайдено"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизований"
     *     )
     * )
     */
    public function delete($id) {
        //Отримуємо запис по id
        $product = Products::findOrFail($id);
        //Розміри фото, які збережено у папці
        $sizes = [50,150,300,600,1200];
        foreach ($sizes as $size) { //перебираємо усі розміри
            $fileSave = $size."_".$product->image; //Формуємо назву файла у папці
            $path=public_path('upload/'.$fileSave); //Робимо шлях до файлу
            if(file_exists($path)) //Перевіряємо чи даний файл є
                unlink($path); //Якщо він є то видаляємо
        }
        //Після видалення усіх файлів видаляємо саму категорію
        $product->delete();
        //Вертаємо пустий результат
        return response()->json("",200, ['Charset' => 'utf-8']);
    }

    /**
     * @OA\Post(
     *     tags={"Product"},
     *     path="/api/products/edit/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Ідентифікатор категорії",
     *         required=true,
     *         @OA\Schema(
     *             type="number",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="file"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Add Category.")
     * )
     */
    public function edit($id, Request $request) {
        $product = Products::findOrFail($id);
        $imageName=$product->image;
        $inputs = $request->all();
        if($request->hasFile("image")) {
            $image = $request->file("image");
            $imageName = uniqid() . ".webp";
            $sizes = [50, 150, 300, 600, 1200];
            // create image manager with desired driver
            $manager = new ImageManager(new Driver());
            foreach ($sizes as $size) {
                $fileSave = $size . "_" . $imageName;
                $imageRead = $manager->read($image);
                $imageRead->scale(width: $size);
                $path = public_path('upload/' . $fileSave);
                $imageRead->toWebp()->save($path);
                $removeImage = public_path('upload/'.$size."_". $product->image);
                if(file_exists($removeImage))
                    unlink($removeImage);
            }
        }
        $inputs["image"]= $imageName;
        $product->update($inputs);
        return response()->json($product,200,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }
}
