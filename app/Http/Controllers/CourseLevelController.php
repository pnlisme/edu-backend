<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseLevel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CourseLevelController extends Controller
{
    // Lấy danh sách các cấp độ khóa học
    public function index(Request $request)
    {
        $courseLevelsQuery = CourseLevel::query();
        if ($request->has('limit')) {
            $limit = $request->get('limit');
            $courseLevels = $courseLevelsQuery->limit($limit)->get();
        } else {
            $perPage = $request->get('per_page', 10);
            $currentPage = $request->get('page', 1);
            $courseLevels = $courseLevelsQuery->paginate($perPage, ['*'], 'page', $currentPage);
        }

        return formatResponse(STATUS_OK, $courseLevels, '', __('messages.course_level_fetch_success'));
    }

    // Hiển thị một cấp độ cụ thể
    public function show($id)
    {
        $courseLevel = CourseLevel::find($id);
        if (!$courseLevel) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_level_not_found'));
        }

        return formatResponse(STATUS_OK, $courseLevel, '', __('messages.course_level_detail_success'));
    }

    // Tạo mới cấp độ khóa học
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:course_levels',
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => __('messages.name_course_level_required'),
            'name.string' => __('messages.name_course_level_string'),
            'name.max' => __('messages.name_course_level_max'),
            'name.unique' => __('messages.name_course_level_unique'),
            'status.required' => __('messages.status_required'),
            'status.in' => __('messages.status_invalid'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        $courseLevel = new CourseLevel();
        $courseLevel->name = $request->name;
        $courseLevel->status = $request->status;
        $courseLevel->created_by = auth()->id();
        $courseLevel->save();

        return formatResponse(STATUS_OK, $courseLevel, '', __('messages.course_level_create_success'));
    }

    // Cập nhật cấp độ khóa học
    public function update(Request $request, $id)
    {
        $courseLevel = CourseLevel::find($id);
        if (!$courseLevel) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_level_not_found'));
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('course_levels')->ignore($courseLevel->id),
            ],
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => __('messages.name_course_level_required'),
            'name.string' => __('messages.name_course_level_string'),
            'name.max' => __('messages.name_course_level_max'),
            'name.unique' => __('messages.name_course_level_unique'),
            'status.required' => __('messages.status_required'),
            'status.in' => __('messages.status_invalid'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        $courseLevel->name = $request->name;
        $courseLevel->status = $request->status;
        $courseLevel->created_by = $request->created_by;

        $courseLevel->updated_by = auth()->id();
        $courseLevel->save();

        return formatResponse(STATUS_OK, $courseLevel, '', __('messages.course_level_update_success'));
    }

    // Xóa mềm cấp độ khóa học
    public function destroy($id)
    {
        $courseLevel = CourseLevel::find($id);
        if (!$courseLevel) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_level_not_found'));
        }

        $courseLevel->deleted_by = auth()->id();
        $courseLevel->save();
        $courseLevel->delete();

        return formatResponse(STATUS_OK, '', '', __('messages.course_level_soft_delete_success'));
    }

    // Khôi phục cấp độ khóa học bị xóa mềm
    public function restore($id)
    {
        $courseLevel = CourseLevel::onlyTrashed()->find($id);
        if (!$courseLevel) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_level_not_found'));
        }

        $courseLevel->deleted_by = null;
        $courseLevel->restore();

        return formatResponse(STATUS_OK, '', '', __('messages.course_level_restore_success'));
    }

    // Xóa vĩnh viễn cấp độ khóa học
    public function forceDelete($id)
    {
        $courseLevel = CourseLevel::onlyTrashed()->find($id);
        if (!$courseLevel) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.course_level_not_found'));
        }

        $courseLevel->forceDelete();

        return formatResponse(STATUS_OK, '', '', __('messages.course_level_force_delete_success'));
    }
}
