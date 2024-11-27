<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lecture;
use App\Models\Section;
use App\Models\User;
use Faker\Factory as Faker;

class LectureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Lấy tất cả ID từ bảng sections
        $sectionIds = Section::pluck('id')->toArray();

        // Nếu không có dữ liệu trong sections, hiển thị thông báo
        if (empty($sectionIds)) {
            $this->command->info('Không có dữ liệu trong bảng sections.');
            return;
        }

        // Tạo lectures cho từng section
        foreach ($sectionIds as $sectionId) {
            $sortOrder = 1; // Khởi tạo lại sortOrder cho mỗi section

            for ($i = 0; $i < 10; $i++) { // Ví dụ tạo 10 lectures cho mỗi section
                // Xác định loại nội dung và tạo đường dẫn tương ứng
                $type = $faker->randomElement(['video', 'file']);
                $contentLink = $type === 'video' 
                            ? 'https://www.w3schools.com/html/mov_bbb.mp4'   // URL video mẫu
                            : 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';   // URL file tài liệu giả
                $userIds = User::pluck('id')->toArray();

                // Tạo lecture với sort
                Lecture::create([
                    'section_id' => $sectionId,  // Chọn ngẫu nhiên section_id từ mảng sectionIds
                    'type' => $type,             // Loại bài giảng ngẫu nhiên (video hoặc file)
                    'title' => $faker->sentence(4), // Tạo tiêu đề với 4 từ ngẫu nhiên
                    'content_link' => $contentLink, // Đường dẫn nội dung được tạo theo loại
                    'duration' => $faker->numberBetween(60, 3600), // Thời lượng bài giảng (giây), ngẫu nhiên từ 1 phút đến 1 giờ
                    'preview' => $faker->randomElement(['can', 'cant']), // Trạng thái preview
                    'status' => $faker->randomElement(['active', 'inactive']), // Trạng thái bài giảng (active hoặc inactive)
                    'sort' => $sortOrder++, // Số thứ tự tăng dần cho mỗi lecture trong cùng một section
                    'deleted_by' => null, // Giá trị mặc định là null
                    'created_by' => $faker->randomElement($userIds), // Chọn ngẫu nhiên ID từ danh sách user
                    'updated_by' => $faker->optional()->randomElement($userIds), // Người cập nhật ngẫu nhiên hoặc null
                ]);
            }
        }
    }
}
