<?php/** * Created by PhpStorm. * User: Baymax * Date: 2017/12/20 * Time: 19:27 */namespace app\Admin\controller;use think\Request;use think\Session;use app\Common\Controller\AuthAdminInterManage;class AvatarManage extends AuthAdminInterManage{    /**     * @param sortName sortOrder pageSize pageNumber     * 管理员头像接口     */    public function admin_avatar()    {        $uid = Session::get('admin_id');        $result   = array('status' => false, 'msg' => "");        $all = Request::instance()->post();        $user = model('AdminUser');        //保存base64字符串为图片        //匹配出图片的格式        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $all['avatar'], $avatar)){            $type = $avatar[2];            $name = $uid.'_'.intval(time());            $new_file = "./static/img/avatar/{$name}.{$type}";            $avatarname = $name.'.'.$type;            if (file_put_contents($new_file, base64_decode(str_replace($avatar[1], '', $all['avatar'])))){                $avatarurl = $user -> where('uid',$uid) -> column('avatar');                if(!empty($avatarurl[0])){                    if(file_exists("./static/img/avatar/{$avatarurl[0]}")) {                        if (!unlink("./static/img/avatar/{$avatarurl[0]}")) {                            $result['status'] = 0;                            $result['msg'] = '旧头像删除失败！';                            return json($result);                        }                    }                }                $reset_result = $user -> where('uid='.$uid) -> setField('avatar',$avatarname);                if($reset_result ){                    Session::set('avatar_admin' , $avatarname);                    $result['status'] = 1;                    $result['msg'] = '头像修改成功！';                    return json($result);                }else{                    $result['status'] = 0;                    $result['msg'] = '头像修改失败！';                    return json($result);                }            }else{                $result['status'] = 0;                $result['msg'] = '头像写入失败！';                return json($result);            }        }    }}