<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Task;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // إنشاء المستخدم المشرف
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
        ]);

        // إنشاء المستخدم العادي
        $user = User::create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'password' => bcrypt('user123'),
            'role' => 'user',
        ]);

        // إضافة بعض المهام التجريبية للمشرف
        Task::create([
            'title' => 'مهمة تجريبية للمشرف',
            'description' => 'هذه مهمة تجريبية للمشرف',
            'user_id' => $admin->id,
            'is_completed' => false,
        ]);

        // إضافة بعض المهام التجريبية للمستخدم
        Task::create([
            'title' => 'مهمتي الأولى',
            'description' => 'أتعلم Laravel وأبني نظام مهام رائع',
            'user_id' => $user->id,
            'is_completed' => false,
        ]);

        Task::create([
            'title' => 'مهمة منجزة',
            'description' => 'هذه المهمة تم إنجازها بالفعل',
            'user_id' => $user->id,
            'is_completed' => true,
        ]);
    }
}
