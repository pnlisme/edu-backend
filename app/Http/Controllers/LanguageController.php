<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Language;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    // Lấy danh sách các ngôn ngữ
    public function index(Request $request)
    {
        $languagesQuery = Language::where('status', 'active');
        if ($request->has('limit')) {
            $limit = $request->get('limit');
            $languages = $languagesQuery->limit($limit)->get();
        } else {
            $perPage = $request->get('per_page', 10);
            $currentPage = $request->get('page', 1);
            $languages = $languagesQuery->paginate($perPage, ['*'], 'page', $currentPage);
        }

        return formatResponse(STATUS_OK, $languages, '', __('messages.language_fetch_success'));
    }

    // Hiển thị chi tiết ngôn ngữ cụ thể
    public function show($id)
    {
        $language = Language::find($id);
        if (!$language) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.language_not_found'));
        }

        return formatResponse(STATUS_OK, $language, '', __('messages.language_detail_success'));
    }

    // Tạo mới ngôn ngữ
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:languages',
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => __('messages.name_language_required'),
            'name.string' => __('messages.name_language_string'),
            'name.max' => __('messages.name_language_max'),
            'name.unique' => __('messages.name_language_unique'),
            'status.required' => __('messages.status_required'),
            'status.in' => __('messages.status_invalid'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        $language = new Language();
        $language->name = $request->name;
        $language->description = $request->description;
        $language->status = $request->status;
        $language->created_by = auth()->id();
        $language->save();

        return formatResponse(STATUS_OK, $language, '', __('messages.language_create_success'));
    }

    // Cập nhật ngôn ngữ
    public function update(Request $request, $id)
    {
        $language = Language::find($id);
        if (!$language) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.language_not_found'));
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('languages')->ignore($language->id),
            ],
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => __('messages.name_language_required'),
            'name.string' => __('messages.name_language_string'),
            'name.max' => __('messages.name_language_max'),
            'name.unique' => __('messages.name_language_unique'),
            'status.required' => __('messages.status_required'),
            'status.in' => __('messages.status_invalid'),
        ]);

        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }

        $language->name = $request->name;
        $language->description = $request->description;
        $language->status = $request->status;
        $language->updated_by = auth()->id();
        $language->save();

        return formatResponse(STATUS_OK, $language, '', __('messages.language_update_success'));
    }

    // Xóa mềm ngôn ngữ
    public function destroy($id)
    {
        $language = Language::find($id);
        if (!$language) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.language_not_found'));
        }

        $language->deleted_by = auth()->id();
        $language->save();
        $language->delete();

        return formatResponse(STATUS_OK, '', '', __('messages.language_soft_delete_success'));
    }

    // Khôi phục ngôn ngữ bị xóa mềm
    public function restore($id)
    {
        $language = Language::onlyTrashed()->find($id);
        if (!$language) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.language_not_found'));
        }

        $language->deleted_by = null;
        $language->restore();

        return formatResponse(STATUS_OK, '', '', __('messages.language_restore_success'));
    }

    // Xóa vĩnh viễn ngôn ngữ
    public function forceDelete($id)
    {
        $language = Language::onlyTrashed()->find($id);
        if (!$language) {
            return formatResponse(STATUS_FAIL, '', '', __('messages.language_not_found'));
        }

        $language->forceDelete();

        return formatResponse(STATUS_OK, '', '', __('messages.language_force_delete_success'));
    }
}
