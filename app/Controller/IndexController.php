<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use Hyperf\View\RenderInterface;

class IndexController extends AbstractController
{
    public function index(RenderInterface $render)
    {
        $post = config('server.servers')[1]['port'];
        $server = 'ws://127.0.0.1:'.$post;
        return $render->render('index', compact('server'));
    }
}
