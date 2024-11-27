<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Faker\Factory as Faker;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Lấy tất cả ID từ bảng courses
        $courseIds = Course::pluck('id')->toArray();

        // Nếu không có dữ liệu trong courses, hiển thị thông báo
        if (empty($courseIds)) {
            $this->command->info('Không có dữ liệu trong bảng courses.');
            return;
        }
        $userIds = User::pluck('id')->toArray();
         // Tạo 10 Section cho mỗi khóa học
         foreach ($courseIds as $courseId) {
            // Khởi tạo biến đếm cho `sort`
            $sortOrder = 1;

            // Tạo 10 section cho mỗi khóa học (bạn có thể thay đổi số lượng này)
            for ($i = 0; $i < 10; $i++) {
                Section::create([
                    'course_id' => $courseId,                         // Gán `course_id` cho mỗi Section
                    'name' => $faker->sentence(2),                    // Tên section với 2 từ
                    'status' => $faker->randomElement(['active', 'inactive']), // Trạng thái ngẫu nhiên
                    'sort' => $sortOrder++,                            // Số thứ tự tăng dần
                    'deleted_by' => null,                              // Giá trị mặc định là null
                    'created_by' => $faker->randomElement($userIds), // Chọn ngẫu nhiên ID từ danh sách user
                    'updated_by' => $faker->optional()->randomElement($userIds), // Người cập nhật ngẫu nhiên hoặc null
                ]);
            }
        }
        // Tạo 100 section cho các khóa học
        
    }
}
