<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Models\Category;

defined('APP_ROOT') or exit('No direct access');

class CategoriesController extends Controller
{
    public function index(): void
    {
        $this->authorize('categories');
        $this->render('categories/index', ['title' => 'دسته‌بندی‌ها', 'tree' => Category::tree(), 'flat' => Category::flat()]);
    }

    public function save(): void
    {
        $this->requirePost();
        $this->authorize('categories', Auth::MANAGE);
        $id = input_int('id');
        $name = (string)input('name');
        if ($name === '') {
            json_error('نام دسته الزامی است');
        }
        $parent = input_int('parent_id') ?: null;
        if ($parent && $id) {
            // جلوگیری از حلقه: والد نباید خود دسته یا زیرمجموعه‌اش باشد
            $cur = $parent;
            while ($cur) {
                if ($cur === $id) {
                    json_error('یک دسته نمی‌تواند زیرمجموعه خودش باشد');
                }
                $cur = (int)DB::value('SELECT parent_id FROM categories WHERE id = ?', [$cur]);
            }
        }
        $color = (string)input('color', '#3b82f6');
        $icon = (string)input('icon', 'bi-tag');
        $data = [
            'name' => mb_substr($name, 0, 100),
            'parent_id' => $parent,
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#3b82f6',
            'icon' => preg_match('/^bi-[a-z0-9\-]+$/', $icon) ? $icon : 'bi-tag',
            'sort_order' => input_int('sort_order'),
        ];
        if ($id) {
            $this->findOr404('categories', $id);
            DB::update('categories', $data, 'id = ?', [$id]);
        } else {
            DB::insert('categories', $data);
        }
        json_ok('دسته‌بندی ذخیره شد');
    }

    public function delete(): void
    {
        $this->requirePost();
        $this->authorize('categories', Auth::MANAGE);
        $id = input_int('id');
        $this->findOr404('categories', $id);
        DB::delete('categories', 'id = ?', [$id]);
        json_ok('دسته‌بندی و زیردسته‌هایش حذف شد');
    }
}
