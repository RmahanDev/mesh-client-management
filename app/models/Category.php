<?php
namespace App\Models;

use App\Core\DB;

defined('APP_ROOT') or exit('No direct access');

class Category
{
    /** درخت دسته‌ها: والدها با کلید children */
    public static function tree(): array
    {
        $rows = DB::all('SELECT c.*, (SELECT COUNT(*) FROM project_categories pc WHERE pc.category_id = c.id) AS projects_count
                         FROM categories c ORDER BY c.sort_order, c.id');
        $byId = [];
        foreach ($rows as $r) {
            $r['children'] = [];
            $byId[$r['id']] = $r;
        }
        $tree = [];
        foreach ($byId as $id => &$r) {
            if ($r['parent_id'] && isset($byId[$r['parent_id']])) {
                $byId[$r['parent_id']]['children'][] = &$r;
            } else {
                $tree[] = &$r;
            }
        }
        unset($r);
        return $tree;
    }

    /** لیست مسطح برای select با تورفتگی */
    public static function flat(): array
    {
        $out = [];
        $walk = function ($nodes, $depth) use (&$walk, &$out) {
            foreach ($nodes as $n) {
                $out[] = ['id' => $n['id'], 'name' => $n['name'], 'depth' => $depth, 'color' => $n['color'], 'icon' => $n['icon'], 'parent_id' => $n['parent_id']];
                $walk($n['children'], $depth + 1);
            }
        };
        $walk(self::tree(), 0);
        return $out;
    }
}
