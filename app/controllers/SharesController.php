<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Project;

defined('APP_ROOT') or exit('No direct access');

/** سهم ادمین واردشده از سود پروژه‌ها */
class SharesController extends Controller
{
    public function index(): void
    {
        $this->render('shares/index', ['title' => 'سهم من از پروژه‌ها', 'shares' => Project::myShares((int)Auth::id())]);
    }
}
