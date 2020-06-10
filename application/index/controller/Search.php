<?php

namespace app\index\controller;

use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;

class Search extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    //列表
    public function index()
    {
        $where = [];
        $title = '';

        $key = input('key', null);
        if ($key != null) {
            $arr_key = logic('Article')->getArrByString($key);
            if (!$arr_key) {
                Helper::http404();
            }

            //分类id
            if (isset($arr_key['f']) && $arr_key['f'] > 0) {
                $where['type_id'] = $arr_key['f'];

                $post = model('ArticleType')->getOne(['id' => $arr_key['f']]);
                $this->assign('post', $post);

                //面包屑导航
                $this->assign('bread', logic('Article')->get_article_type_path($where['type_id']));
            }
        }

        $where['delete_time'] = 0;
        $where['status'] = 0;
        $where['add_time'] = ['<', time()];
        $list = $this->getLogic()->getPaginate($where, 'id desc', ['content']);
        if (!$list) {
            Helper::http404();
        }

        $page = $list->render();
        $page = preg_replace('/key=[a-z0-9]+&amp;/', '', $page);
        $page = preg_replace('/&amp;key=[a-z0-9]+/', '', $page);
        $page = preg_replace('/\?page=1"/', '"', $page);
        $this->assign('page', $page);
        $this->assign('list', $list);

        //最新
        $where2['delete_time'] = 0;
        $where2['status'] = 0;
        $where2['add_time'] = ['<', time()];
        $zuixin_list = logic('Article')->getAll($where2, 'id desc', ['content'], 5);
        $this->assign('zuixin_list', $zuixin_list);

        //推荐
        $where3['delete_time'] = 0;
        $where3['status'] = 0;
        $where3['tuijian'] = 1;
        $where3['litpic'] = ['<>', ''];
        $where3['add_time'] = ['<', time()];
        $tuijian_list = logic('Article')->getAll($where3, 'id desc', ['content'], 5);
        $this->assign('tuijian_list', $tuijian_list);

        //seo标题设置
        $title = $title . '最新动态';
        $this->assign('title', $title);
        return $this->fetch();
    }

    //详情
    public function detail()
    {
		$title = '';
		$seo_title = '';
		$seo_keywords = '';
		$seo_description = '';
		//搜索词
        $keyword = input('keyword', null);
        if ($keyword) {
            $where['title'] = array('like', '%' . $keyword . '%');
			$title = $seo_title = $keyword;
			//查找搜索词
			$searchword = model('Searchword')->getOne(['name'=>$keyword]);
			if ($searchword) {
				$this->assign('seo_title', $searchword['title']);
				$this->assign('seo_keywords', $searchword['keywords']);
				$this->assign('seo_description', $searchword['description']);
			}
        }
		//归档：年份
		$year = input('year', null);
        if ($year) {
			if (strlen($year) == 4 && is_numeric($year)) {
				$next_year = $year + 1;
				$start_year = strtotime($year . '-01-01');
				$end_year = strtotime($next_year . '-01-01');
				$where['add_time'] = [['>=', $start_year],['<=', $end_year]];
				$title = $seo_title = '文章歸檔：' . $year;
			} else {
				Helper::http404();
			}
        }
		//归档：年月
		$month = input('month', null);
        if ($month) {
            if (strlen($month) == 7) {
				$start_month = strtotime($month . '-01');
				$end_month = strtotime('+1 month', $start_month);
				$where['add_time'] = [['>=', $start_month],['<=', $end_month]];
				$title = $seo_title = '文章歸檔：' . $month;
			} else {
				Helper::http404();
			}
        }
		//归档：年月日
		$date = input('date', null);
        if ($date) {
            if (strlen($date) == 10) {
				$start_date = strtotime($date);
				$end_date = $start_date + 3600*24;
				$where['add_time'] = [['>=', $start_date],['<=', $end_date]];
				$title = $seo_title = '文章歸檔：' . $date;
			} else {
				Helper::http404();
			}
        }
		//作者
		$writer = input('writer', null);
        if ($writer) {
            $where['writer'] = $writer;
			$title = $seo_title = $writer;
			//查找搜索词
			$searchword = model('Searchword')->getOne(['name'=>$writer]);
			if ($searchword) {
				$this->assign('seo_title', $searchword['title']);
				$this->assign('seo_keywords', $searchword['keywords']);
				$this->assign('seo_description', $searchword['description']);
			}
        }
		//来源
		$source = input('source', null);
        if ($source) {
            $where['source'] = $source;
			$title = $seo_title = $source;
			//查找搜索词
			$searchword = model('Searchword')->getOne(['name'=>$source]);
			if ($searchword) {
				$this->assign('seo_title', $searchword['title']);
				$this->assign('seo_keywords', $searchword['keywords']);
				$this->assign('seo_description', $searchword['description']);
			}
        }
		if (!$seo_title) {
			Helper::http404();
		}

		$pagesize = sysconfig('CMS_PAGESIZE');
        $offset = 0;
        if (isset($_REQUEST['page'])) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        }
        $where['status'] = 0;
        $where['delete_time'] = 0;
        $where['add_time'] = ['<', time()];
        $res = logic('Article')->getList($where, 'id desc', ['content'], $offset, $pagesize);
        if ($res['list']) {
            foreach ($res['list'] as $k => $v) {

            }
        }
        $this->assign('list', $res['list']);
        $totalpage = ceil($res['count'] / $pagesize);
        $this->assign('totalpage', $totalpage);
        if (isset($_REQUEST['page_ajax']) && $_REQUEST['page_ajax'] == 1) {
            $html = '';
            if ($res['list']) {
                foreach ($res['list'] as $k => $v) {
                    $html .= '<div class="list">';
                    if (!empty($v['litpic'])) {
                        $html .= '<a class="limg" href="' . model('Article')->getArticleDetailUrl(array('id' => $v['id'])) . '"><img alt="' . $v['title'] . '" src="' . $v['litpic'] . '"></a>';
                    }
                    $html .= '<strong class="tit"><a href="' . model('Article')->getArticleDetailUrl(array('id' => $v['id'])) . '">' . $v['title'] . '</a></strong><p>' . mb_strcut($v['description'], 0, 150, 'UTF-8') . '..</p>';
                    $html .= '<div class="info"><span class="fl"><em>' . date("m-d H:i", $v['update_time']) . '</em></span><span class="fr"><em>' . $v['click'] . '</em>人阅读</span></div>';
                    $html .= '<div class="cl"></div></div>';
                }
            }

            exit(json_encode($html));
        }

        //最新
        $where2['delete_time'] = 0;
        $where2['status'] = 0;
        $where2['add_time'] = ['<', time()];
        $relate_zuixin_list = logic('Article')->getAll($where2, 'id desc', ['content'], 5);
        $this->assign('relate_zuixin_list', $relate_zuixin_list);

        //推荐
        $where3['delete_time'] = 0;
        $where3['status'] = 0;
        $where3['tuijian'] = 1;
        $where3['litpic'] = ['<>', ''];
        $where3['add_time'] = ['<', time()];
        $relate_tuijian_list = logic('Article')->getAll($where3, 'id desc', ['content'], 5);
        $this->assign('relate_tuijian_list', $relate_tuijian_list);

        $this->assign('title', $title);
		$this->assign('seo_title', $seo_title);
		$this->assign('seo_keywords', $seo_keywords);
        $this->assign('seo_description', $seo_description);

        return $this->fetch();
    }
}