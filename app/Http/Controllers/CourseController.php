<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Wishlist;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Tymon\JWTAuth\Facades\JWTAuth;

class CourseController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:api',
    //         [
    //             'except' => [
    //                 'filterCourses'
    //             ]
    //         ]);
    // }
    public function getListAdmin(Request $request)
    {
        // Query để lấy danh sách Course, không kiểm tra trạng thái
        $coursesQuery = Course::with(['language', 'level', 'category', 'sections.lectures']);

        // Lấy số lượng limit và thông tin phân trang từ request
        $limit = $request->get('limit', null);
        $perPage = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);

        // Nếu có limit thì giới hạn kết quả trước khi phân trang thủ công
        if ($limit) {
            // Lấy các kết quả giới hạn
            $courses = $coursesQuery->limit($limit)->get();

            $courses->makeHidden(['category_id', 'level_id', 'language_id']);

            // Lấy tổng số lượng kết quả
            $total = $courses->count();

            // Phân trang thủ công cho kết quả đã giới hạn
            $courses = $courses->forPage($currentPage, $perPage)->values();

            $paginatedCourses = new \Illuminate\Pagination\LengthAwarePaginator(
                $courses,
                $total,
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
            );

            // Chuyển đổi đối tượng phân trang sang mảng với tất cả các thuộc tính chi tiết
            $paginationData = $paginatedCourses->toArray();

            return formatResponse(STATUS_OK, $paginationData, '', __('messages.course_fetch_success'));
        } else {
            // Nếu không có limit, phân trang như bình thường
            $courses = $coursesQuery->paginate($perPage, ['*'], 'page', $currentPage);
            return formatResponse(STATUS_OK, $courses, '', __('messages.course_fetch_success'));
        }
    }

    
    public function search(Request $request)
    {
        // Lấy các tham số lọc từ request

        $category_ids = $request->input('category_ids');
        $level_ids = $request->input('level_ids');
        $language_ids = $request->input('language_ids');
        $keyword = $request->input('keyword');
        // $min_price = $request->input('min_price');
        // $max_price = $request->input('max_price');
        $status = $request->input('status');
        $type_sale = $request->input('type_sale');
        $min_rating = $request->input('min_rating');
        $max_rating = $request->input('max_rating');
        $duration_ranges = explode(',', $request->input('duration_ranges'));

        // Phân trang
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        // Sort
        $sort_by = $request->input('sort_by', 'created_at');
        $sort_order = $request->input('sort_order', 'desc');

        // Lấy khóa học mới, phổ biến, đánh giá cao và yêu thích
        $limitTag = 10;
        $newCourses = Course::orderBy('created_at', 'desc')->take($limitTag)->pluck('id')->toArray();
        $popularCourses = OrderItem::select('course_id', DB::raw('COUNT(*) as purchase_count'))
            ->groupBy('course_id')->orderByDesc('purchase_count')->take($limitTag)->pluck('course_id')->toArray();
        $topRatedCourses = Course::select('courses.id') // Sửa ở đây
            ->leftJoin('reviews', 'courses.id', '=', 'reviews.course_id')
            ->groupBy('courses.id')->orderByRaw('AVG(reviews.rating) DESC')->take($limitTag)->pluck('id')->toArray();
        $favoriteCourses = Wishlist::select('course_id')
            ->groupBy('course_id')->orderByRaw('COUNT(*) DESC')->take($limitTag)->pluck('course_id')->toArray();

        // Query khóa học với điều kiện lọc
        $limit = $request->limit ?? 10;
        $query = Course::with(['category', 'level', 'creator:id,last_name,first_name', 'sections.lectures', 'reviews'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('status', 'active');
        // // Áp dụng các bộ lọc
        if ($category_ids) {
            // Chia nhỏ danh sách category_id thành mảng
            $categoryIds = array_map('intval', explode(',', $category_ids));
            // Mảng để lưu tất cả category ID bao gồm cả ID con
            $allCategoryIds = [];

            // Hàm đệ quy để lấy tất cả ID của các danh mục con
            function getAllChildCategoryIds($categoryId, &$allIds)
            {
                $category = Category::find($categoryId);
                if ($category) {
                    $allIds[] = $categoryId; // Thêm ID của danh mục hiện tại vào mảng
                    $childrenIds = $category->children()->pluck('id')->toArray(); // Lấy ID của các danh mục con

                    foreach ($childrenIds as $childId) {
                        getAllChildCategoryIds($childId, $allIds); // Gọi đệ quy cho các danh mục con
                    }
                }
            }

            // Lặp qua các category ID và thêm ID của các danh mục con
            foreach ($categoryIds as $id) {
                getAllChildCategoryIds($id, $allCategoryIds);
            }
            $allCategoryIds = array_unique($allCategoryIds); // Loại bỏ các ID trùng
            // Sử dụng whereIn để lấy các khóa học
            $query->whereIn('category_id', $allCategoryIds);
        }
        if($level_ids){
            $level_ids = array_map('intval', explode(',', $level_ids));
            if ($level_ids && is_array($level_ids)) {
                $query->whereIn('level_id', $level_ids); // Lọc theo danh sách level_ids
            }
        }
        if($language_ids){
            $language_ids = array_map('intval', explode(',', $language_ids));
            if ($language_ids && is_array($language_ids)) {
                $query->whereIn('language_id', $language_ids); // Lọc theo danh sách level_ids
            }
        }
        // Áp dụng các bộ lọc khác
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere(function ($subQuery) use ($keyword) {
                        $subQuery->whereRaw("CONCAT(users.last_name, ' ', users.first_name) LIKE ?", ['%' . $keyword . '%']);
                    });
            })
                ->join('users', 'users.id', '=', 'created_by'); // Join with users table
        }

        // if ($min_price) {
        //     $query->where('price', '>=', $min_price);
        // }
        // if ($max_price) {
        //     $query->where('price', '<=', $max_price);
        // }
        if ($min_rating) {
            $query->whereHas('reviews', function ($q) use ($min_rating) {
                $q->select('course_id') // Chọn course_id để nhóm
                    ->groupBy('course_id') // Nhóm theo course_id
                    ->havingRaw('AVG(rating) >= ?', [$min_rating]);
            });
        }
        if ($max_rating) {
            $query->whereHas('reviews', function ($q) use ($max_rating) {
                $q->select('course_id') // Chọn course_id để nhóm
                    ->groupBy('course_id') // Nhóm theo course_id
                    ->havingRaw('AVG(rating) <= ?', [$max_rating]);
            });
        }
        if ($duration_ranges = $request->input('duration_ranges')) {
            // Giả sử $duration_ranges là một mảng chứa các khoảng thời gian
            $query->where(function ($q) use ($duration_ranges) {
                // Kiểm tra không có sections
                $q->whereDoesntHave('sections')
                    ->orWhereHas('sections', function ($q) use ($duration_ranges) {
                        $q->join('lectures', 'sections.id', '=', 'lectures.section_id') // Kết nối với bảng lectures
                            ->select('sections.course_id') // Chọn course_id để nhóm
                            ->selectRaw('SUM(lectures.duration) as total_duration') // Tính tổng duration từ lectures
                            ->groupBy('sections.course_id'); // Nhóm theo course_id

                        // Lặp qua từng khoảng thời gian trong mảng
                        $duration_ranges = explode(',', $duration_ranges);
                        foreach ($duration_ranges as $duration_range) {
                            switch ($duration_range) {
                                case '0-48':
                                    // So sánh với thời gian <= 2 giờ
                                    $q->orHaving('total_duration', '<=', 48 * 60 * 60);
                                    break;
                                case '48-128':
                                    // So sánh trong khoảng từ 3 đến 5 giờ
                                    $q->orWhere(function ($query) {
                                        $query->havingBetween('total_duration', [48 * 60 * 60, 128 * 60 * 60]);
                                    });
                                    break;
                                case '128+':
                                    // So sánh với thời gian > 12 giờ
                                    $q->orHaving('total_duration', '>', 128 * 60 * 60);
                                    break;
                            }
                        }
                    });
            });
        }

        // Sắp xếp và phân trang
        $query->orderBy($sort_by, $sort_order);
        if ($limit) {
            $query->limit($limit); // Giới hạn tổng số bản ghi
        }

        // Lấy danh sách các khóa học đã được phân trang
        $total = $query->get()->count();
        $courses = $query->get();
        // Tính toán thông tin bổ sung cho từng khóa học
        $courses = $courses->map(function ($course) use ($newCourses, $popularCourses, $topRatedCourses, $favoriteCourses) {
            $tag = 'none'; // Giá trị mặc định
            if (in_array($course->id, $newCourses)) {
                $tag = __('messages.tag_new');
            } elseif (in_array($course->id, $topRatedCourses)) {
                $tag = __('messages.tag_top_rated');
            } elseif (in_array($course->id, $popularCourses)) {
                $tag = __('messages.tag_popular');
            } elseif (in_array($course->id, $favoriteCourses)) {
                $tag = __('messages.tag_favorite');
            }

            // Đếm số lượng bài học trong sections
            $lectures_count = $course->sections->sum(function ($section) {
                return $section->lectures->count();
            });

            // Tính tổng thời gian của tất cả các bài học
            $total_duration = $course->sections->sum(function ($section) {
                return $section->lectures->sum('duration');
            });
            return [
                'id' => $course->id,
                'title' => $course->title,
                'old_price' => round($course->price, 0),
                'current_price' => round($course->type_sale === 'price' ? $course->price - $course->sale_value : $course->price * (1 - $course->sale_value / 100), 0),
                'thumbnail' => $course->thumbnail,
                'level' => $course->level->name,
                'creator' => ($course->creator && ($course->creator->last_name || $course->creator->first_name)
                    ? trim($course->creator->last_name . ' ' . $course->creator->first_name)
                    : ''),
                'lectures_count' => $lectures_count,
                'total_duration' => round($total_duration / 60 / 60, 1),
                'rating_avg' => round($course->reviews_avg_rating, 2) ?? 0,
                'reviews_count' => $course->reviews_count ?? 0,
                'status' => $course->status,
                'tag' => $tag,
            ];
        });

        // Tạo thông tin phân trang
        $courses = $courses->forPage($page, $perPage)->values();

        $paginatedCourses = new LengthAwarePaginator(
            $courses,
            $total,
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
        
        // Chuyển đổi đối tượng phân trang sang mảng với tất cả các thuộc tính chi tiết
        $paginationData = $paginatedCourses->toArray();
        
        // Trả về dữ liệu phân trang
        return formatResponse(STATUS_OK, $paginationData, '', __('messages.course_fetch_success'));
    }



    public function uploadThumbnail(Request $request)
    {
        // Tải lên tệp hình ảnh
        $path = $request->file('thumbnail')->storePublicly('course-thumbnails');
        if (!$path) {
            return '';
        }

        // Trả về đường dẫn hình ảnh
        $imageUrl = env('URL_IMAGE_S3') . $path;
        return $imageUrl;
    }
    public function deleteThumbnail($thumbnailUrl)
    {
        $currentFilePath = str_replace(env('URL_IMAGE_S3'), '', $thumbnailUrl);

        // Kiểm tra xem tệp có tồn tại trên S3 không
        if (Storage::disk('s3')->exists($currentFilePath)) {
            // Xóa tệp
            Storage::disk('s3')->delete($currentFilePath);
            return formatResponse(STATUS_OK, '', '', __('messages.thumbnail_delete_success'));
        }

        return formatResponse(STATUS_FAIL, '', '', __('messages.thumbnail_not_found'));
    }


    // Tạo mới một khóa học
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'level_id' => 'required|exists:course_levels,id',
            'language_id' => 'required|exists:languages,id',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'price' => 'required|numeric',
            'type_sale' => 'required|in:percent,price',
            'sale_value' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ], [
            'title.required' => __('messages.title_required'),
            'category_id.required' => __('messages.category_id_required'),
            'category_id.exists' => __('messages.category_id_invalid'),
            'level_id.required' => __('messages.level_id_required'),
            'level_id.exists' => __('messages.level_id_invalid'),
            'language_id.required' => __('messages.language_id_required'),
            'language_id.exists' => __('messages.language_id_invalid'),
            'thumbnail.required' => __('messages.thumbnail_required'),
            'thumbnail.image' => __('messages.thumbnail_image'),
            'thumbnail.mimes' => __('messages.thumbnail_mimes'),
            'thumbnail.max' => __('messages.thumbnail_max'),
            'price.required' => __('messages.price_required'),
            'type_sale.required' => __('messages.type_sale_required'),
            'status.required' => __('messages.status_required'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }
        $thumbnailPath = $this->uploadThumbnail($request);
        $course = new Course();
        $course->fill($request->all());
        $course->thumbnail = $thumbnailPath;
        $course->created_by = auth()->id();
        $course->save();

        return formatResponse(STATUS_OK, $course, '', __('messages.course_create_success'));
    }

    // Hiển thị một khóa học cụ thể
    public function detail($id)
    {
        $course = Course::with(['category', 'level', 'creator', 'sections.lectures', 'reviews', 'language'])
            ->where('status', 'active')
            ->where('id', $id)
            ->first();

        if (!$course) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_not_found'));
        }

        // Tính toán các thông tin cần thiết
        $old_price = $course->price; // Thay đổi tên thành old_price
        $sale_value = $course->sale_value;
        $type_sale = $course->type_sale; // Giả sử là 'percentage' hoặc 'fixed'

        // Lấy thông tin sections và lectures
        $sections = $course->sections;
        $sections_count = $sections->count();
        $lectures_count = $sections->reduce(function ($carry, $section) {
            return $carry + $section->lectures->count();
        }, 0);

        // Tính tổng duration
        $total_duration = round($sections->reduce(function ($carry, $section) {
            return $carry + $section->lectures->sum('duration');
        }, 0) / 3600, 1);

        // Tính trung bình rating và số lượng reviews
        $total_reviews = $course->reviews->count();
        $average_rating = $total_reviews > 0 ? round($course->reviews->avg('rating'), 1) : null;

        // Chuẩn bị dữ liệu trả về
        $course_data = [
            'id' => $course->id,
            'title' => $course->title,
            'category' => $course->category->name,
            'level' => $course->level->name,
            'thumbnail' => $course->thumbnail,
            'language' => $course->language->name,
            'old_price' => round($course->price, 0), // Đổi từ original_price sang old_price
            'current_price' => round($course->type_sale === 'price' ? $course->price - $course->sale_value : $course->price * (1 - $course->sale_value / 100), 0),
            'type_sale' => $type_sale,
            'sale_value' => $sale_value,
            'sections' => $sections,
            'sections_count' => $sections_count,
            'lectures_count' => $lectures_count,
            'total_duration' => $total_duration,
            'creator' => ($course->creator && ($course->creator->last_name || $course->creator->first_name)
                ? trim($course->creator->last_name . ' ' . $course->creator->first_name)
                : ''),
            'average_rating' => $average_rating, // Thêm trung bình rating
            'total_reviews' => $total_reviews, // Thêm tổng số reviews
            'status' => $course->status,
        ];

        return formatResponse(STATUS_OK, $course_data, '', __('messages.course_detail_success'));
    }




    // Cập nhật thông tin khóa học
    public function update(Request $request, $id)
    {
        $course = Course::find($id);
        if (!$course) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_not_found'));
        }
        $user = auth()->user();

        if ($user->role === 'instructor') {
            // Kiểm tra xem user có phải là người tạo khóa học không
            if ($course->created_by !== $user->id) {
                return formatResponse(STATUS_FAIL, '', '', __('messages.not_your_course'));
            }
        }
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'level_id' => 'required|exists:course_levels,id',
            'language_id' => 'required|exists:languages,id',
            'title' => [
                'required',
                'string',
                'max:100',
            ],
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'price' => 'required|numeric',
            'type_sale' => 'required|in:percent,price',
            'sale_value' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ], [
            'title.required' => __('messages.title_required'),
            'category_id.required' => __('messages.category_id_required'),
            'category_id.exists' => __('messages.category_id_invalid'),
            'thumbnail.image' => __('messages.thumbnail_image'),
            'thumbnail.mimes' => __('messages.thumbnail_mimes'),
            'thumbnail.max' => __('messages.thumbnail_max'),
            'level_id.required' => __('messages.level_id_required'),
            'level_id.exists' => __('messages.level_id_invalid'),
            'language_id.required' => __('messages.language_id_required'),
            'language_id.exists' => __('messages.language_id_invalid'),
            'price.required' => __('messages.price_required'),
            'type_sale.required' => __('messages.type_sale_required'),
            'status.required' => __('messages.status_required'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }
        $thumbnail = $course->thumbnail;
        $course->fill($request->all());
        $course->thumbnail = $thumbnail;
        if ($request->thumbnail) {
            if ($course->thumbnail) {
                $this->deleteThumbnail($course->thumbnail);
            }
            $thumbnailPath = $this->uploadThumbnail($request);
            $course->thumbnail = $thumbnailPath;
        }
        $course->updated_by = auth()->id();
        $course->save();

            return formatResponse(STATUS_OK, $course, '', __('messages.course_update_success'));
    }
    public function showOne($id) {
    $user = auth()->user(); // Lấy user hiện tại từ auth
    $course = Course::where('id', $id) // Lọc theo id khóa học
        ->where('created_by', $user->id) // Lọc theo user đã tạo khóa học
        ->with(['sections' => function ($query) {
            $query->orderBy('sort', 'desc'); // Sắp xếp sections theo trường 'sort' giảm dần
        }, 'sections.lectures'])
        ->first(); // Lấy bản ghi đầu tiên
    if (!$course) {
        return formatResponse(STATUS_FAIL, '', '', __('messages.course_not_found'));
    }
    return formatResponse(STATUS_OK, $course, '', __('messages.course_found'));
}

    // Xóa mềm khóa học

    public function destroy($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_not_found'));
        }
        $user = auth()->user();

        if ($user->role === 'instructor') {
            // Kiểm tra xem user có phải là người tạo khóa học không
            if ($course->created_by !== $user->id) {
                return formatResponse(STATUS_FAIL, '', '', __('messages.not_your_course'));
            }
        }
        $course->deleted_by = auth()->id();
        $course->save();

        $course->delete();

        return formatResponse(STATUS_OK, '', '', __('messages.course_soft_delete_success'));
    }

    // Khôi phục khóa học đã bị xóa mềm
    public function restore($id)
    {
        $course = Course::onlyTrashed()->find($id);

        if (!$course) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_not_found'));
        }

        $user = auth()->user();

        if ($user->role === 'instructor') {
            // Kiểm tra xem user có phải là người tạo khóa học không
            if ($course->created_by !== $user->id) {
                return formatResponse(STATUS_FAIL, '', '', __('messages.not_your_course'));
            }
        }
        $course->deleted_by = null;
        $course->save();

        $course->restore();

        return formatResponse(STATUS_OK, '', '', __('messages.course_restore_success'));
    }

    // Xóa cứng khóa học
    public function forceDelete($id)
    {
        $course = Course::onlyTrashed()->find($id);
        if (!$course) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_not_found'));
        }
        $user = auth()->user();

        if ($user->role === 'instructor') {
            // Kiểm tra xem user có phải là người tạo khóa học không
            if ($course->created_by !== $user->id) {
                return formatResponse(STATUS_FAIL, '', '', __('messages.not_your_course'));
            }
        }
        if ($course->thumbnail) {
            $this->deleteThumbnail($course->thumbnail);
        }
        $course->forceDelete();

        return formatResponse(STATUS_OK, '', '', __('messages.course_force_delete_success'));
    }

    public function getPopularCourses(Request $request)
    {
        // Kiểm tra xem có giới hạn không, nếu không, mặc định là 10
        $limit = $request->limit ?? 10;
        $category_ids = $request->input('category_ids');
        $allCategoryIds = [];
        if ($category_ids) {
            // Chia nhỏ danh sách category_id thành mảng
            $categoryIds = array_map('intval', explode(',', $category_ids));
            // Mảng để lưu tất cả category ID bao gồm cả ID con


            // Hàm đệ quy để lấy tất cả ID của các danh mục con
            function getAllChildCategoryIds($categoryId, &$allIds)
            {
                $category = Category::find($categoryId);
                if ($category) {
                    $allIds[] = $categoryId; // Thêm ID của danh mục hiện tại vào mảng
                    $childrenIds = $category->children()->pluck('id')->toArray(); // Lấy ID của các danh mục con

                    foreach ($childrenIds as $childId) {
                        getAllChildCategoryIds($childId, $allIds); // Gọi đệ quy cho các danh mục con
                    }
                }
            }

            // Lặp qua các category ID và thêm ID của các danh mục con
            foreach ($categoryIds as $id) {
                getAllChildCategoryIds($id, $allCategoryIds);
            }
            $allCategoryIds = array_unique($allCategoryIds); // Loại bỏ các ID trùng
        }

        // Lấy các khóa học được mua nhiều nhất từ bảng order_items
        $popularCourses = OrderItem::select('course_id', DB::raw('COUNT(*) as purchase_count'))
            ->groupBy('course_id')
            ->orderByDesc('purchase_count')
            ->limit($limit)
            ->get();

        // Lấy chi tiết các khóa học cùng với category và level dựa trên course_id đã gom nhóm
        $courses = Course::with('category', 'level', 'creator')
            ->where('status', 'active')
            ->whereIn('id', $popularCourses->pluck('course_id'))
            ->when($allCategoryIds, function ($query) use ($allCategoryIds) {
                return $query->whereIn('category_id', $allCategoryIds);
            })
            ->get();

        if ($courses->isEmpty()) {
            // Nếu không có khóa học nào phổ biến
            return formatResponse(STATUS_FAIL, '', '', __('messages.no_popular_courses'));
        }
        return formatResponse(STATUS_OK, $this->transform($courses, __('messages.tag_popular')), '', __('messages.popular_courses_found'));
    }

    public function transform($courses, $tag)
    {
        // dd($courses[0]->creator);
        return $courses->map(function ($course) use ($tag) {
            // Tính giá hiện tại
            $current_price = $course->type_sale === 'percent'
                ? round($course->price - ($course->price * ($course->sale_value / 100)), 0)
                : round($course->price - $course->sale_value, 0);

            // Tính tổng số lượng bài giảng và tổng thời lượng
            $lectures_count = $course->sections->reduce(function ($carry, $section) {
                return $carry + $section->lectures->count();
            }, 0);

            $total_duration = $course->sections->reduce(function ($carry, $section) {
                return $carry + $section->lectures->sum('duration');
            }, 0) / 3600; // Đổi tổng thời gian thành giờ

            // Tính trung bình đánh giá và số lượng reviews
            $reviews_count = $course->reviews->count();
            $rating_avg = $reviews_count > 0 ? round($course->reviews->avg('rating'), 1) : null;

            // Trả về dữ liệu đã format
            return [
                'id' => $course->id,
                'title' => $course->title,
                'old_price' => round($course->price, 0), // Giá ban đầu
                'current_price' => $current_price, // Giá hiện tại
                'thumbnail' => $course->thumbnail, // Ảnh thumbnail
                'level' => $course->level->name ?? null, // Mức độ khóa học
                'creator' => ($course->creator && ($course->creator->last_name || $course->creator->first_name)
                    ? trim($course->creator->last_name . ' ' . $course->creator->first_name)
                    : ''),
                'lectures_count' => $lectures_count, // Số bài giảng
                'total_duration' => round($total_duration, 1), // Tổng thời lượng (giờ)
                'rating_avg' => $rating_avg, // Trung bình đánh giá
                'reviews_count' => $reviews_count, // Tổng số reviews
                'status' => $course->status,
                'tag' => $tag, // Thẻ
            ];
        });
    }


    public function getNewCourses(Request $request)
    {
        // Kiểm tra xem có giới hạn không, nếu không, mặc định là 10
        $category_ids = $request->input('category_ids');
        $allCategoryIds = [];
        if ($category_ids) {
            // Chia nhỏ danh sách category_id thành mảng
            $categoryIds = array_map('intval', explode(',', $category_ids));
            // Mảng để lưu tất cả category ID bao gồm cả ID con

            // Hàm đệ quy để lấy tất cả ID của các danh mục con
            function getAllChildCategoryIds($categoryId, &$allIds)
            {
                $category = Category::find($categoryId);
                if ($category) {
                    $allIds[] = $categoryId; // Thêm ID của danh mục hiện tại vào mảng
                    $childrenIds = $category->children()->pluck('id')->toArray(); // Lấy ID của các danh mục con

                    foreach ($childrenIds as $childId) {
                        getAllChildCategoryIds($childId, $allIds); // Gọi đệ quy cho các danh mục con
                    }
                }
            }

            // Lặp qua các category ID và thêm ID của các danh mục con
            foreach ($categoryIds as $id) {
                getAllChildCategoryIds($id, $allCategoryIds);
            }
            $allCategoryIds = array_unique($allCategoryIds); // Loại bỏ các ID trùng
        }

        $limit = $request->limit ?? 10;

        // Lấy các khóa học mới nhất theo ngày tạo và lọc theo category_ids
        $courses = Course::with('category', 'level')
            ->when($allCategoryIds, function ($query) use ($allCategoryIds) {
                return $query->whereIn('category_id', $allCategoryIds);
            })
            ->where('status', 'active')
            ->orderBy('created_at', 'desc') // Sắp xếp theo ngày tạo giảm dần
            ->limit($limit)
            ->get();

        if ($courses->isEmpty()) {
            // Nếu không có khóa học nào mới
            return formatResponse(STATUS_FAIL, '', '', __('messages.no_new_courses'));
        }

        return formatResponse(STATUS_OK, $this->transform($courses, __('messages.tag_new')), '', __('messages.new_courses_found'));
    }

    public function getTopRatedCourses(Request $request)
    {
        // Kiểm tra xem có giới hạn không, nếu không, mặc định là 10
        $limit = $request->limit ?? 10;
        $category_ids = $request->input('category_ids');
        $allCategoryIds = [];
        if ($category_ids) {
            // Chia nhỏ danh sách category_id thành mảng
            $categoryIds = array_map('intval', explode(',', $category_ids));
            // Mảng để lưu tất cả category ID bao gồm cả ID con

            // Hàm đệ quy để lấy tất cả ID của các danh mục con
            function getAllChildCategoryIds($categoryId, &$allIds)
            {
                $category = Category::find($categoryId);
                if ($category) {
                    $allIds[] = $categoryId; // Thêm ID của danh mục hiện tại vào mảng
                    $childrenIds = $category->children()->pluck('id')->toArray(); // Lấy ID của các danh mục con

                    foreach ($childrenIds as $childId) {
                        getAllChildCategoryIds($childId, $allIds); // Gọi đệ quy cho các danh mục con
                    }
                }
            }

            // Lặp qua các category ID và thêm ID của các danh mục con
            foreach ($categoryIds as $id) {
                getAllChildCategoryIds($id, $allCategoryIds);
            }
            $allCategoryIds = array_unique($allCategoryIds); // Loại bỏ các ID trùng
        }

        // Lấy các khóa học được đánh giá cao nhất từ bảng reviews
        $topRatedCourses = Review::select('course_id', DB::raw('AVG(rating) as average_rating'))
        ->groupBy('course_id')
        ->orderByDesc('average_rating')
        ->limit($limit)
        ->get();

        // Lấy chi tiết các khóa học cùng với category và level dựa trên course_id đã gom nhóm
        $courses = Course::with('category', 'level', 'creator')
        ->where('status', 'active')
        ->whereIn('id', $topRatedCourses->pluck('course_id'))
        ->when($allCategoryIds, function ($query) use ($allCategoryIds) {
            return $query->whereIn('category_id', $allCategoryIds);
        })
        ->get();

        if ($courses->isEmpty()) {
            // Nếu không có khóa học nào
            return formatResponse(STATUS_FAIL, '', '', __('messages.no_top_rated_courses'));
        }

        return formatResponse(STATUS_OK, $this->transform($courses, __('messages.tag_top_rated')), '', __('messages.top_rated_courses_found'));
    }


    public function getFavouriteCourses(Request $request)
    {
        // Kiểm tra xem có giới hạn không, nếu không, mặc định là 10
        $limit = $request->limit ?? 10;
        $category_ids = $request->input('category_ids');
        $allCategoryIds = [];
        if ($category_ids) {
            // Chia nhỏ danh sách category_id thành mảng
            $categoryIds = array_map('intval', explode(',', $category_ids));
            // Mảng để lưu tất cả category ID bao gồm cả ID con

            // Hàm đệ quy để lấy tất cả ID của các danh mục con
            function getAllChildCategoryIds($categoryId, &$allIds)
            {
                $category = Category::find($categoryId);
                if ($category) {
                    $allIds[] = $categoryId; // Thêm ID của danh mục hiện tại vào mảng
                    $childrenIds = $category->children()->pluck('id')->toArray(); // Lấy ID của các danh mục con

                    foreach ($childrenIds as $childId) {
                        getAllChildCategoryIds($childId, $allIds); // Gọi đệ quy cho các danh mục con
                    }
                }
            }

            // Lặp qua các category ID và thêm ID của các danh mục con
            foreach ($categoryIds as $id) {
                getAllChildCategoryIds($id, $allCategoryIds);
            }
            $allCategoryIds = array_unique($allCategoryIds); // Loại bỏ các ID trùng
        }

        // Lấy các khóa học được yêu thích nhất từ bảng wishlist
        $favoriteCourses = Wishlist::select('course_id', DB::raw('COUNT(*) as wishlist_count'))
        ->groupBy('course_id')
        ->orderByDesc('wishlist_count')
        ->limit($limit)
        ->get();

        // Lấy chi tiết các khóa học cùng với category, level, và creator dựa trên course_id đã gom nhóm
        $courses = Course::with('category', 'level', 'creator')
        ->where('status', 'active')
        ->whereIn('id', $favoriteCourses->pluck('course_id'))
        ->when($allCategoryIds, function ($query) use ($allCategoryIds) {
            return $query->whereIn('category_id', $allCategoryIds);
        })
        ->get();


        if ($courses->isEmpty()) {
            // Nếu không có khóa học nào
            return formatResponse(STATUS_FAIL, '', '', __('messages.no_favorite_courses'));
        }

        return formatResponse(STATUS_OK, $this->transform($courses, __('messages.tag_favorite')), '', __('messages.favorite_courses_found'));
    }



//     public function filterCourses(Request $request)
//     {
//         $category_id = $request->input('category_id');
//         $title = $request->input('title');
//         $min_price = $request->input('min_price');
//         $max_price = $request->input('max_price');
//         $status = $request->input('status');
//         $type_sale = $request->input('type_sale');
//         $rating = $request->input('rating');
//         $duration_range = $request->input('duration_range');
//
//
//         $page = $request->input('page', 1);
//         $perPage = $request->input('per_page', 10);
//
//         $sort_by = $request->input('sort_by', 'created_at');
//         $sort_order = $request->input('sort_order', 'desc');
//
//         $query = Course::with('reviews');
//         if ($category_id) {
//             $categoryIds = explode(',', $category_id);
//             $query->whereIn('category_id', $categoryIds);
//         }
//         if ($title) {
//             $query->where('title', 'like', '%' . $title . '%');
//         }
//         if ($min_price) {
//             $query->where('price', '>=', $min_price);
//         }
//         if ($max_price) {
//             $query->where('price', '<=', $max_price);
//         }
//         if ($status) {
//             $query->where('status', $status);
//         }
//
//         if ($rating) {
//             $query->whereHas('reviews', function ($q) use ($rating) {
//                 $q->havingRaw('ROUND(AVG(rating),0) = ?', [$rating]);
//             });
//         }
//
//         if ($duration_range) {
//             $query->whereHas('sections.lectures', function ($q) use ($duration_range) {
//                 switch ($duration_range) {
//                     case '0-2':
//                         $q->havingRaw('SUM(duration) <= 120');
//                         break;
//                     case '3-5':
//                         $q->havingRaw('SUM(duration) BETWEEN 180 AND 300');
//                         break;
//                     case '6-12':
//                         $q->havingRaw('SUM(duration) BETWEEN 360 AND 720');
//                         break;
//                     case '12+':
//                         $q->havingRaw('SUM(duration) > 720');
//                         break;
//                 }
//             });
//         }
//
//         $query->orderBy($sort_by, $sort_order);
//         $courses = $query->paginate($perPage, ['*'], 'page', $page);
//         return response()->json([
//             'status' => 'success',
//             'data' => $courses->items(),
//             'pagination' => [
//                 'total' => $courses->total(),
//                 'current_page' => $courses->currentPage(),
//                 'last_page' => $courses->lastPage(),
//                 'per_page' => $courses->perPage(),
//             ],
//         ]);
//     }
}
