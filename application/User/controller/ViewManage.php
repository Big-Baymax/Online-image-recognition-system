<?php/** * Created by PhpStorm. * User: Baymax * Date: 2018/1/2 * Time: 16:49 */namespace app\user\controller;use think\Controller;use app\Common\Controller\AuthUserController;use think\Session;use think\View;use think\Db;use think\Request;use app\User\model\News;class ViewManage extends AuthUserController{    /**     * @param null     * 用户主页     */    public function user_index(){        $user = model('User');        $id = Session::get('user_id');        $login_list = $user -> where('uid',$id) -> column('login_info');        //json字符串转为数组，true转为可直接引用的数组，否则不可直接引用的        $list = json_decode($login_list[0], true);        //根据登陆时间排序        array_multisort(array_column($list,'login_time'),SORT_DESC,$list);        if(count($list)==1){            $list[1]['login_time'] = "首次登陆";            $list[1]['ip'] = "首次登陆";        }        //最近调用        $interlog = model('InterLog');        $type = ['','人脸识别','图像识别','印刷文字识别'];        $inter_new = $interlog->where('user_id',$id)->order('time', 'desc')->limit(1)->select();        if(!$inter_new){            $inter_new = "暂未申请";        }else{            $inter_new = $type[$inter_new[0]['type']];        }        $this->assign('inter_new',$inter_new);        //调用次数        $faces_count = $interlog->where('user_id',$id)->where('type',1)->count();        $pic_count = $interlog->where('user_id',$id)->where('type',2)->count();        $text_count = $interlog->where('user_id',$id)->where('type',3)->count();        //今日调用次数        $day_count = $interlog->where('user_id',$id)->whereTime('time', 'today')->count();        //本月调用次数        $month_count = $interlog->where('user_id',$id)->whereTime('time', 'month')->count();        $this->assign([            'faces_count'  => $faces_count,            'pic_count' => $pic_count,            'text_count' => $text_count,            'day_count' => $day_count,            'month_count' => $month_count,        ]);        return $this->fetch('guest@user/index',['login_list'=>$list]);    }    /**     * @param null     * 用户头像页     */    public function user_avatar(){        $avatarurl = Session::get('avatar_user');        $this->assign('avatar',$avatarurl);        return $this->fetch('guest@user/edit_avatar');    }    /**     * @param null     * 用户信息编辑     */    public function user_info(){        $user = model('User');        $id = Session::get('user_id');        $info = $user -> where('uid',$id) -> select();        $this->assign('info',$info);        return $this->fetch('guest@user/info_edit');    }    /**     * @param null     * 用户密码修改     */    public function user_password(){        return $this->fetch('guest@user/password_edit');    }    /**     * @param null     * 用户登陆记录     */    public function login_list(){        $user = model('User');        $id = Session::get('user_id');        $login_list = $user -> where('uid',$id) -> column('login_info');        //json字符串转为数组，true转为可直接引用的数组，否则不可直接引用的        $list = json_decode($login_list[0], true);        if(!empty($list)) {            //根据登陆时间排序            array_multisort(array_column($list,'login_time'),SORT_DESC,$list);        }        return $this->fetch('guest@user/login_list',['login_list'=>$list]);    }    /** * @param null * 用户消息记录 */    public function user_news(){        $id = Session::get('user_id');        //$news = News::get(21);        // 一对多关联，再对$user_news进行过滤查看为0以及用户id为当前的        $list = News::hasWhere('comments',['status'=>0,'user_id'=>$id,])->order('time','desc')->select();        $list_view  = News::hasWhere('comments',['status'=>1,'user_id'=>$id,])->order('time','desc')->select();        $this->assign('list_view',$list_view);        $this->assign('list',$list);        // $list_view = $news->where('user_id=0 OR user_id='.$id)->where('view_count','>',0)->select();        // $list = $news->where('user_id=0 OR user_id='.$id)->where('view_count','=',0)->select();        //$list_view = $news->where('user_id='.$id)->where('view_count','>',0)->whereOr('user_id=0')->select();        // $this->assign('list_view',$list_view);        return $this->fetch('guest@user/user_news');    }    /**     * @param null     * 用户接口信息     */    public function interface_list(){        $id = Session::get('user_id');        $inter = model('Inter');        $inter_info = $inter->where('user_id',$id)->where('status','2')->select();        $this->assign('inter_info',$inter_info);        $this->assign('type',['','人脸识别','图像识别','印刷文字识别']);        //var_dump($inter_info);        if(!sizeof($inter_info)){            return $this->fetch('guest@interface/interface_submit');        }else{            return $this->fetch('guest@interface/interface');        }    }    /** * @param null * 用户接口申请 */    public function interface_submit(){        $id = Session::get('user_id');        $inter = model('Inter');        $inter_info = $inter->where('user_id',$id)->select();        $this->assign('inter_info',$inter_info);        $this->assign('type',['','人脸识别','图像识别','印刷文字识别']);        $this->assign('status',['','待审核','已通过','未通过']);        //var_dump($inter_info);        //each();        return $this->fetch('guest@interface/interface_submit');    }    /**     * @param null     * 用户接口KEY查看页面     */    public function interface_key(){        $id = Session::get('user_id');        $inter = model('Inter');        $inter_info = $inter->where('user_id',$id)->where('status','2')->select();        $this->assign('inter_info',$inter_info);        $this->assign('type',['','人脸识别','图像识别','印刷文字识别']);        return $this->fetch('guest@interface/interface_key');    }    /**     * @param null     * 用户接口统计页面     */    public function interface_count(){        $id = Session::get('user_id');        $inter = model('Inter');        $inter_info = $inter->where('user_id',$id)->where('status','2')->select();        $this->assign('inter_info',$inter_info);        $this->assign('type',['','人脸识别','图像识别','印刷文字识别']);        $interlog = model('InterLog');        $inter_new = $interlog->where('user_id',$id)->order('time', 'desc')->limit(3)->select();        $this->assign('inter_new',$inter_new);        //count 人脸识别接口使用的数据        $success_count_faces = $interlog->where('user_id',$id)->where('type',1)->where('status',1)->count();        $fail_count_faces = $interlog->where('user_id',$id)->where('type',1)->where('status',0)->count();        $this->assign('success_count_faces',$success_count_faces);        $this->assign('fail_count_faces',$fail_count_faces);        //count 图像识别接口使用的数据        $success_count_pic = $interlog->where('user_id',$id)->where('type',2)->where('status',1)->count();        $fail_count_pic = $interlog->where('user_id',$id)->where('type',2)->where('status',0)->count();        $this->assign('success_count_pic',$success_count_pic);        $this->assign('fail_count_pic',$fail_count_pic);        //count 文本识别接口使用的数据        $success_count_text = $interlog->where('user_id',$id)->where('type',3)->where('status',1)->count();        $fail_count_text = $interlog->where('user_id',$id)->where('type',3)->where('status',0)->count();        $this->assign('success_count_text',$success_count_text);        $this->assign('fail_count_text',$fail_count_text);        return $this->fetch('guest@interface/interface_count');    }    /**     * @param null     * 接口文档页面     */    public function introduce_file(){        return $this->fetch('guest@interface/introduce_file');    }    /**     * @param null     * 在线体验页面     */    public function interface_example(){        return $this->fetch('guest@interface/example');    }    /**     * @param null     * 在线特征值提取体验页面     */    public function example_fast(){        return $this->fetch('guest@example/fast');    }    /**     * @param null     * 在线录像特征值提取体验页面     */    public function example_fast_video(){        return $this->fetch('guest@example/fast_video');    }    /**     * @param null     * 录像人脸识别体验页面     */    public function example_face_video(){        return $this->fetch('guest@example/face_video');    }    /**     * @param null     * 照片人脸识别体验页面     */    public function example_face_pic(){        return $this->fetch('guest@example/face_pic');    }    /**     * @param null     * 录像颜色识别体验页面     */    public function example_color_video(){        return $this->fetch('guest@example/color_video');    }    /**     * @param null     * 照片颜色识别体验页面     */    public function example_color_pic(){        return $this->fetch('guest@example/color_pic');    }    /**     * @param null     * 在线识别二维码页面     */    public function example_qrcode(){        return $this->fetch('guest@example/qrcode');    }    /**     * @param null     * 文本转换页面     */    public function example_text(){        return $this->fetch('guest@example/text');    }    /**     * @param null     * 应用场景页面     */    public function interface_scenario(){        return $this->fetch('guest@interface/scenario');    }    /**     * @param null     * 应用接口使用记录     */    public function interface_log(){        return $this->fetch('guest@interface/interface_log');    }}