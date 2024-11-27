<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;


class CategoryController extends Controller
{
    public function getListAdmin(Request $request)
    {
        // Query để lấy danh sách Category, không kiểm tra trạng thái
        $categoriesQuery = Category::query();

        // Lấy số lượng limit và thông tin phân trang từ request
        $limit = $request->get('limit', null);
        $perPage = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);

        // Nếu có limit thì giới hạn kết quả trước khi phân trang thủ công
        if ($limit) {
            // Lấy các kết quả giới hạn
            $categories = $categoriesQuery->limit($limit)->get();

            // Lấy tổng số lượng kết quả
            $total = $categories->count();

            // Phân trang thủ công cho kết quả đã giới hạn
            $categories = $categories->forPage($currentPage, $perPage)->values();

            $paginatedCategories = new \Illuminate\Pagination\LengthAwarePaginator(
                $categories,
                $total,
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
            );

            // Chuyển đổi đối tượng phân trang sang mảng với tất cả các thuộc tính chi tiết
            $paginationData = $paginatedCategories->toArray();

            return formatResponse(STATUS_OK, $paginationData, '', __('messages.category_fetch_success'));
        } else {
            // Nếu không có limit, phân trang như bình thường
            $categories = $categoriesQuery->paginate($perPage, ['*'], 'page', $currentPage);
            return formatResponse(STATUS_OK, $categories, '', __('messages.category_fetch_success'));
        }
    }



    public function index(Request $request)
    {
        // Số mục trên mỗi trang, mặc định là 10 nếu không có trong request
        $perPage = $request->get('per_page', 10);

        // Lấy tất cả các danh mục cùng với danh mục con
        $categories = Category::with('children')->withCount('courses')->get();
        $childCategoryIds = collect();

        // Thu thập các id của các danh mục con
        foreach ($categories as $category) {
            if ($category->children) {
                $childCategoryIds = $childCategoryIds->merge($category->children->pluck('id'));
            }
        }

        // Loại bỏ các danh mục con khỏi danh sách chính
        $filteredCategories = $categories->reject(function ($category) use ($childCategoryIds) {
            return $childCategoryIds->contains($category->id);
        });

        // Phân trang
        $currentPage = $request->get('page', 1); // Lấy trang hiện tại từ request, mặc định là 1
        $total = $filteredCategories->count(); // Tổng số danh mục đã lọc
        $filteredCategories = $filteredCategories->slice(($currentPage - 1) * $perPage, $perPage)->values(); // Lấy các mục cho trang hiện tại
        // Tạo thông tin phân trang
        $paginated = [
            'data' => $filteredCategories,
            'current_page' => $currentPage,
            'last_page' => (int) ceil($total / $perPage), // Tính số trang cuối cùng
            'per_page' => $perPage,
            'total' => $total,
        ];

        // Phản hồi theo chuẩn
        return formatResponse(STATUS_OK, $paginated, '', __('messages.category_fetch_success'));
    }


    // Tạo mới category
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:categories',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'keyword' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'parent_id' => 'nullable|exists:categories,id',
        ], [
            'name.required' => __('messages.name_required'),
            'name.string' => __('messages.name_string'),
            'name.max' => __('messages.name_max'),
            'name.unique' => __('messages.name_unique'),
            'image.image' => __('messages.thumbnail_image'),
            'image.mimes' => __('messages.thumbnail_mimes'),
            'image.max' => __('messages.thumbnail_max'),
            'description.string' => __('messages.description_string'),
            'icon.string' => __('messages.icon_string'),
            'keyword.string' => __('messages.keyword_string'),
            'status.required' => __('messages.status_required'),
            'status.in' => __('messages.status_invalid'),
            'parent_id.exists' => __('messages.parent_id_invalid'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        $category = new Category();
        $category->name = $request->name;
        $category->description = $request->description;
        $category->icon = $request->icon;
        $category->keyword = $request->keyword;
        $category->status = $request->status;
        $category->parent_id = $request->parent_id;
        if($request->image){
            $category->image=$this->uploadImage($request);
        }
        $category->created_by = auth()->id();
        $category->save();

        return formatResponse(STATUS_OK, $category, '', __('messages.category_create_success'));
    }
    public function addChildren(){
        
    }

    public function uploadImage(Request $request)
    {
        // Tải lên tệp hình ảnh
        $path = $request->file('image')->storePublicly('category-image');
        if (!$path) {
            return '';
        }

        // Trả về đường dẫn hình ảnh
        $imageUrl = env('URL_IMAGE_S3') . $path;
        return $imageUrl;
    }
    public function deleteImage($image)
    {
        $currentFilePath = str_replace(env('URL_IMAGE_S3'), '', $image);

        // Kiểm tra xem tệp có tồn tại trên S3 không
        if (Storage::disk('s3')->exists($currentFilePath)) {
            // Xóa tệp
            Storage::disk('s3')->delete($currentFilePath);
        }
    }

    // Hiển thị một category cụ thể
    public function show($id)
    {
        $category = Category::with('children')->withCount('courses')->find($id);
        if (!$category) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.category_not_found'));
        }

        return formatResponse(STATUS_OK, $category, '', __('messages.category_detail_success'));
    }

    // Cập nhật category
    public function update(Request $request, $id)
    {
        // Kiểm tra xem category có tồn tại hay không
        $category = Category::find($id);
        if (!$category) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.category_not_found'));
        }

        // Validation rules cho việc update
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories')->ignore($category->id) // Bỏ qua unique cho chính category hiện tại
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'keyword' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'parent_id' => 'nullable|exists:categories,id',
        ], [
            'name.required' => __('messages.name_required'),
            'name.string' => __('messages.name_string'),
            'name.max' => __('messages.name_max'),
            'name.unique' => __('messages.name_unique'),
            'image.image' => __('messages.thumbnail_image'),
            'image.mimes' => __('messages.thumbnail_mimes'),
            'image.max' => __('messages.thumbnail_max'),
            'description.string' => __('messages.description_string'),
            'icon.string' => __('messages.icon_string'),
            'keyword.string' => __('messages.keyword_string'),
            'status.required' => __('messages.status_required'),
            'status.in' => __('messages.status_invalid'),
            'parent_id.exists' => __('messages.parent_id_invalid'),
        ]);
        
        // Kiểm tra validation
        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        // Cập nhật thông tin category
        $category->name = $request->name;
        $category->description = $request->description;
        $category->icon = $request->icon;
        $category->keyword = $request->keyword;
        $category->status = $request->status;
        if($request->image){
            if($category->image){
                $this->deleteImage($category->image);
            }
            $imagePath = $this->uploadImage($request);
            $category->image=$imagePath;
        }
        $category->parent_id = $request->parent_id;
        $category->updated_by = auth()->id(); // Thêm thông tin người cập nhật
        $category->save();

        return formatResponse(STATUS_OK, $category, '', __('messages.category_update_success'));
    }

    // Cập nhật status của categorry
    public function updateStatus(Request $request, $id)
    {
        // Tìm category
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'status' => 'FAIL',
                'message' => __('messages.category_not_found')
            ], 404);
        }

        // Validation chỉ yêu cầu trường `status`
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive'
        ], [
            'status.required' => __('messages.status_required'),
            'status.in' => __('messages.status_invalid')
        ]);

        // Kiểm tra validation
        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAIL',
                'error' => $validator->errors(),
                'message' => __('messages.validation_error')
            ], 422);
        }

        // Cập nhật status
        $category->status = $request->status;
        $category->updated_by = auth()->id(); // Thêm thông tin người cập nhật
        $category->save();

        return response()->json([
            'status' => 'OK',
            'data' => $category,
            'message' => __('messages.category_update_success')
        ]);
    }

    // Xóa mềm category (cập nhật is_deleted và deleted_by)
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.category_not_found'));
        }

        // Cập nhật is_deleted và deleted_by
        $category->is_deleted = true;
        $category->deleted_by = auth()->id();
        $category->save();

        $category->delete(); // Thực hiện soft delete

        return formatResponse(STATUS_OK, '', '', __('messages.category_soft_delete_success'));
    }

    // Khôi phục category bị soft deleted (cập nhật is_deleted)
    public function restore($id)
    {
        // Tìm danh mục bị xóa mềm
        $category = Category::onlyTrashed()->find($id);

        // Kiểm tra xem danh mục có bị xóa mềm hay không
        if (!$category) {
            // Kiểm tra xem danh mục có tồn tại nhưng chưa bị xóa mềm không
            $existingCategory = Category::find($id);
            if ($existingCategory) {
                return formatResponse(STATUS_FAIL, '', '', __('messages.category_not_deleted')); // Thông báo danh mục chưa bị xóa
            }

            // Nếu danh mục không tồn tại
            return formatResponse(STATUS_FAIL, '', '', __('messages.category_not_found')); // Thông báo danh mục không tồn tại
        }

        // Cập nhật lại is_deleted và deleted_by
        $category->is_deleted = false;
        $category->deleted_by = null; // Xóa thông tin deleted_by khi khôi phục
        $category->save();

        // Khôi phục danh mục
        $category->restore();

        return formatResponse(STATUS_OK, '', '', __('messages.category_restore_success')); // Thông báo khôi phục thành công
    }

    // Xóa cứng category
    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->find($id);
        if (!$category) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.category_not_found'));
        }
        if($category->image){
            $this->deleteImage($category->image);
        }
        // Xóa vĩnh viễn
        $category->forceDelete();

        return formatResponse(STATUS_OK, '', '', __('messages.category_force_delete_success'));
    }
}
