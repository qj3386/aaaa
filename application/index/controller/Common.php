<?php

namespace app\index\controller;

use think\Request;
use think\Db;
use think\Log;
use app\common\lib\Helper;
use app\common\controller\CommonController;

class Common extends CommonController
{
    protected $login_info;

    /**
     * 初始化
     * @param void
     * @return void
     */
    public function _initialize()
    {
        if (strlen($_SERVER['REQUEST_URI']) > 100) {
            header("HTTP/1.1 404 Not Found");
            header("Status: 404 Not Found");
            exit;
        }

        $route = request()->module() . '/' . request()->controller() . '/' . request()->action();
        //店铺登录信息
        $this->login_info = session('shop_info');
        $this->assign('login_info', $this->login_info);

		//分类列表
		$article_type_list = cache("article_type_list");
        if (!$article_type_list) {
            $article_type_list = model('ArticleType')->getAll(['parent_id'=>0], 'listorder asc', 'id,parent_id,name,filename,litpic,listorder', 5);
			if ($article_type_list) {
				foreach ($article_type_list as $k => $v) {
					$sub = model('ArticleType')->getAll(['parent_id'=>$v['id']], 'listorder asc', 'id,parent_id,name,filename,litpic,listorder', 5);
					if ($sub) {
						$article_type_list[$k]['child'] = $sub;
					}
				}
			}
            cache("article_type_list", $article_type_list, 86400); //1小时
        }
		//最新文章RECENT POSTS
		$recent_posts = cache("recent_posts");
        if (!$recent_posts) {
            $where2['status'] = 0;
            $where2['add_time'] = ['<', time()];
            $recent_posts = logic('Article')->getAll($where2, 'update_time desc', ['content'], 6);
            cache("recent_posts", $recent_posts, 86400); //1小时
        }
		//热门文章POPULAR POSTS
        $popular_posts = cache("popular_posts");
        if (!$popular_posts) {
            $where2['tuijian'] = 1;
            $where2['status'] = 0;
            $where2['add_time'] = ['<', time()];
            $popular_posts = logic('Article')->getAll($where2, 'update_time desc', ['content'], 6);
            cache("popular_posts", $popular_posts, 86400); //1小时
        }

        //判断是否拥有权限
        /* if($this->shop_info['role_id'] <> 1)
        {
            $uncheck = array('shop/index/index','shop/index/upconfig','shop/index/upcache','shop/index/welcome');

            if(!in_array($route, $uncheck))
            {

            }
        } */

        //请求日志
        Log::info('【请求地址】：' . request()->ip() . ' [' . date('Y-m-d H:i:s') . '] ' . request()->method() . ' ' . '/' . request()->module() . '/' . request()->controller() . '/' . request()->action());
        Log::info('【请求参数】：' . json_encode(request()->param(), JSON_UNESCAPED_SLASHES));
        Log::info('【请求头】：' . json_encode(request()->header(), JSON_UNESCAPED_SLASHES));
    }

    // 设置空操作
    public function _empty()
    {
        Helper::http404();
    }
}