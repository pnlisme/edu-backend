<?php

return [
    'welcome' => 'Chào mừng bạn đến với ứng dụng của chúng tôi!',
    'user_signup_success' => 'Đăng ký thành công.',
    'user_login_success' => 'Đăng nhập thành công.',
    'user_logout_success' => 'Đăng xuất thành công.',
    'login_google_success' => 'Đăng nhập google thất bại.',

    'validation_error' => 'Có lỗi xác thực.',
    // Message for rolecheck Middleware
    'no_permission' => 'Bạn không có quyền thực hiện hành động này.',
    // Message for JWT Middleware
    'token_invalid' => 'Token không hợp lệ',
    'token_expired' => 'Token đã hết hạn',
    'token_not_found' => 'Không tìm thấy Token',
    'create_token_failed' => 'Tạo token thất bại',

    'user_not_found' => 'Không tìm thấy người dùng',

    // Message for category
    'name_required' => 'Tên không được để trống.',
    'email_required' => 'Email không được để trống.',

    'first_name_required' => 'Tên không được để trống.',
    'first_name_string' => 'Tên phải là chuỗi.',
    'first_name_max' => 'Tên không được quá :max ký tự.',
    'last_name_required' => 'Họ không được để trống.',
    'last_name_string' => 'Họ phải là chuỗi.',
    'last_name_max' => 'Họ không được quá :max ký tự.',

    'password_required' => 'Mật khẩu không được để trống.',
    'password_string' => 'Mật khẩu phải là chuỗi.',
    'password_min' => 'Mật khẩu ít nhất :min ký tự.',
    'password_incorrect' => 'Mật khẩu không chính xác.',

    'role_required' => 'Vài trò không được để trống.',
    'role_in' => 'Vai trò không tồn tại.',
    'gender_invalid' => 'Giới tính không tồn tại.',
    'date_of_birth_invalid' => 'Phải đúng định YYYY-MM-DD.',


    'validation_error_role' => 'Không được phép vài trò này.',

    'url_not_found' => 'Không tìm thấy đường dẫn.',
    'get_url_ok' => 'Lấy đường dẫn thành công.',

    'verify_email_ok' => 'Xác thực tài khoản thành công.',


    'name_string' => 'Tên phải là chuỗi.',
    'email_string' => 'Email phải là chuỗi.',
    'email_email' => 'Email phải đúng định dạng email.',
    'name_max' => 'Tên không được quá :max ký tự.',
    'email_max' => 'Email không được quá :max ký tự.',
    'name_unique' => 'Tên phải là duy nhất.',
    'email_unique' => 'Email đã có người sử dụng.',
    'email_exist' => 'Email không tồn tại.',
    'email_not_verified' => 'Email chưa được xác thực.',
    'email_send_ok' => 'Gửi email thành công.',

    'description_string' => 'Mô tả phải là chuỗi.',
    'icon_string' => 'Biểu tượng phải là chuỗi.',
    'keyword_string' => 'Từ khóa phải là chuỗi.',
    'status_required' => 'Trạng thái không được để trống.',
    'status_invalid' => 'Trạng thái không hợp lệ.',
    'parent_id_invalid' => 'ID cha không hợp lệ.',
    'category_fetch_success' => 'Lấy danh mục thành công.',
    'category_not_found' => 'Không tìm thấy danh mục',
    'category_detail_success' => 'Lấy thông tin danh mục thành công.',
    'category_create_success' => 'Danh mục đã được tạo thành công.',
    'category_update_success' => 'Danh mục đã được cập nhật thành công.',
    'category_soft_delete_success' => 'Danh mục đã được xóa thành công.',
    'category_restore_success' => 'Danh mục đã được khôi phục thành công.',
    'category_force_delete_success' => 'Danh mục đã được xóa vĩnh viễn thành công.',
    'category_not_deleted' => 'Danh mục chưa bị xóa.',

    //Message of Course
    'course_not_found' => 'Không tìm thấy khóa học.',
    'course_detail_success' => 'Lấy thông tin khóa học thành công.',
    'course_create_success' => 'Khóa học đã được tạo thành công.',
    'course_update_success' => 'Khóa học đã được cập nhật thành công.',
    'course_soft_delete_success' => 'Khóa học đã được xóa thành công.',
    'course_restore_success' => 'Khóa học đã được khôi phục thành công.',
    'course_force_delete_success' => 'Khóa học đã được xóa vĩnh viễn thành công.',
    'course_fetch_success' => 'Lấy danh sách khóa học thành công.',
    'title_required' => 'Tên khóa học là bắt buộc.',
    'title_unique' => 'Tên khóa học phải là duy nhất.',
    'category_id_required' => 'Danh mục là bắt buộc.',
    'category_id_invalid' => 'Danh mục không tồn tại.',
    'level_id_required' => 'Cấp độ là bắt buộc.',
    'level_id_invalid' => 'Cấp độ không tồn tại.',
    'language_id_required' => 'Ngôn ngữ là bắt buộc.',
    'language_id_invalid' => 'Ngôn ngữ không tồn tại.',
    'thumbnail_required' => 'Ảnh đại diện là bắt buộc.',
    'thumbnail_image' => 'Tệp tải lên phải là hình ảnh.',
    'thumbnail_mimes' => 'Chỉ chấp nhận các định dạng hình ảnh: jpeg, png, jpg, gif, svg.',
    'thumbnail_max' => 'Kích thước ảnh đại diện không được vượt quá 2MB.',
    'price_required' => 'Giá là bắt buộc.',
    'type_sale_required' => 'Loại hình bán hàng là bắt buộc.',
    'language_required' => 'Ngôn ngữ là bắt buộc.',
    'not_your_course' => 'Đây không phải là khóa học của bạn.',
    'no_popular_courses' => 'Hiện không có khóa học phổ biến nào.',
    'popular_courses_found' => 'Đã lấy thành công các khóa học phổ biến.',
    'no_new_courses' => 'Không có khóa học nào mới.',
    'new_courses_found' => 'Đã lấy thành công các khóa học mới.',
    'top_rated_courses_found' => 'Đã lấy thành công các khóa học hàng đầu.',
    'no_top_rated_courses' => 'Không có khóa học nào được đánh giá cao.',
    'tag_new' => 'Mới nhất',
    'tag_top_rated' => 'Đánh giá cao',
    'tag_popular' => 'Phổ biến nhất',
    'tag_favorite' => 'Yêu thích nhất',

    'error_save' => 'Lưu không thành công.',
    'successful_save' => 'Lưu thành công.',
    'update_fail' => 'Cập nhật thành công.',
    'update_success' => 'Cập nhật thành công.',

    //Message for Course Level
    'course_level_fetch_success' => 'Lấy danh sách cấp độ khóa học thành công',
    'course_level_not_found' => 'Cấp độ khóa học không được tìm thấy',
    'course_level_detail_success' => 'Lấy chi tiết cấp độ khóa học thành công',
    'course_level_create_success' => 'Tạo cấp độ khóa học thành công',
    'course_level_update_success' => 'Cập nhật cấp độ khóa học thành công',
    'course_level_soft_delete_success' => 'Xóa mềm cấp độ khóa học thành công',
    'course_level_restore_success' => 'Khôi phục cấp độ khóa học thành công',
    'course_level_force_delete_success' => 'Xóa vĩnh viễn cấp độ khóa học thành công',
    'name_course_level_required' => 'Tên là bắt buộc',
    'name_course_level_string' => 'Tên phải là chuỗi',
    'name_course_level_max' => 'Tên không được vượt quá 100 ký tự',
    'name_course_level_unique' => 'Tên đã tồn tại',

    //Message for Language
    'name_language_required' => 'Tên ngôn ngữ là bắt buộc.',
    'name_language_string' => 'Tên ngôn ngữ phải là chuỗi ký tự.',
    'name_language_max' => 'Tên ngôn ngữ không được vượt quá 100 ký tự.',
    'name_language_unique' => 'Tên ngôn ngữ đã tồn tại.',
    'status_required' => 'Trạng thái là bắt buộc.',
    'status_invalid' => 'Trạng thái không hợp lệ.',
    'language_fetch_success' => 'Lấy danh sách ngôn ngữ thành công.',
    'language_detail_success' => 'Lấy chi tiết ngôn ngữ thành công.',
    'language_create_success' => 'Tạo ngôn ngữ mới thành công.',
    'language_update_success' => 'Cập nhật ngôn ngữ thành công.',
    'language_soft_delete_success' => 'Xóa mềm ngôn ngữ thành công.',
    'language_restore_success' => 'Khôi phục ngôn ngữ thành công.',
    'language_force_delete_success' => 'Xóa vĩnh viễn ngôn ngữ thành công.',
    'language_not_found' => 'Ngôn ngữ không tồn tại.',

    // CART
    'cart_items_fetched' => 'Danh sách khóa học trong giỏ hàng đã được lấy thành công.',
    'course_not_found_in_cart' => 'Khóa học không có trong giỏ hàng.',
    'course_added_success' => 'Khóa học đã được yêu thich.',
    'course_already_in_cart' => 'Khóa học đã có trong giỏ hàng.',
    'course_already_in_paid_order' => 'Khóa học đã có trong đơn hàng đã thanh toán.',
    'course_removed_success' => 'Khóa học đã được xóa thành công.',
    'cart_cleared' => 'Tất cả các khóa học đã được xóa khỏi giỏ hàng.',

    // VOUCHER
    'voucher_created_success' => 'Voucher đã được tạo thành công.',
    'voucher_code_already_exists' => 'Mã voucher đã tồn tại.',
    'voucher_creation_failed' => 'Tạo voucher thất bại.',
    'voucher_not_found' => 'Không tìm thấy voucher.',
    'voucher_updated_success' => 'Voucher đã được cập nhật thành công.',
    'voucher_soft_deleted_success' => 'Voucher đã được xóa mềm thành công.',
    'voucher_restore_success' => 'Voucher đã được khôi phục thành công.',
    'voucher_has_expired' => 'Voucher đã hết hạn.',
    'voucher_usage_limit_reached' => 'Đã đạt đến giới hạn sử dụng voucher.',
    'voucher_apply_success' => 'Áp dụng voucher thành công.',

    // ORDER
    'order_created_success' => 'Đơn hàng đã được tạo thành công.',
    'order_create_failed' => 'Tạo đơn hàng thất bại.',

    // Admin user
    'getUsers' => 'Lấy dữ liệu thành công',
    'updateUser' => 'Cập nhật thành công',
    'phone_number_update' => 'Số điện thoại phải là số.',
    'address_update' => 'Địa chỉ phải là chuỗi.',
    'contactInfo_update' => 'Thông tin liên lạc phải là chuỗi.',


];
