<?php


namespace App\WebSocket\Controller;


use App\WebSocket\BaseController;

class Index extends BaseController
{
    public function index()
    {
        var_dump($this->frame->fd);
    }

    public function actionNotFound()
    {
        return $this->push($this->getFd(), '请求错误，请检查！');
    }

    public function info()
    {
        var_dump($this->frame->fd);
    }
}