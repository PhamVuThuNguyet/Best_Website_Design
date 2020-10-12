<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Session;
use App\Forum;
use App\React;
use App\Admin;
use App\Comment;
use App\CateForum;
use App\Notification;
use Carbon\Carbon;
class BlogHomeController extends Controller
{
   public function index()
   {
            $data = DB::table('forum')->selectRaw('COUNT(react.id_post) as react,COUNT(cmt.id_forum) as cmt,user, name_cate,img_cate,title_post,forum.created_at,avatar,color_cate,forum.id_post,slug_forum,like_post,comments,views,accounts.id')->Join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->Join('accounts','forum.auth_post','=','accounts.id')
            ->leftJoin('react', 'forum.id_post', '=', 'react.id_post')
            ->leftJoin('cmt', 'forum.id_post', '=', 'cmt.id_forum')
            ->groupBy('forum.id_post')
            ->orderBy('forum.created_at','DESC')->paginate(10);


            $data_new = DB::table('forum')->selectRaw('COUNT(react.id_post) as react,COUNT(cmt.id_forum) as cmt,user, name_cate,img_cate,title_post,forum.created_at,avatar,color_cate,forum.id_post,slug_forum,like_post,comments,views,accounts.id')->Join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->Join('accounts','forum.auth_post','=','accounts.id')
            ->leftJoin('react', 'forum.id_post', '=', 'react.id_post')
            ->leftJoin('cmt', 'forum.id_post', '=', 'cmt.id_forum')
            ->groupBy('forum.id_post')
            ->orderBy('forum.comments','DESC')->whereDate('forum.created_at', Carbon::today())->skip(0)->take(5)->get();;

         
      // $data_new= DB::table('forum')->select('user', 'name_cate','title_post','forum.created_at','avatar','color_cate','id_post','slug_forum','like_post','comments','views','id')->join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->join('accounts','forum.auth_post','=','accounts.id')->orderBy('forum.comments','DESC')->whereDate('forum.created_at', Carbon::today())->get();

 
      $cate_forum= DB::table('forum_cate')->join('forum','forum_cate.id_cate','=','forum.id_cate_forum')
                  ->select('id_post','name_cate','id_cate','title_post',DB::raw('COUNT(forum.id_cate_forum) as sum_post,max(forum.id_post) as MAXimum'))->distinct()
                  ->whereRaw('id_post','max(forum.id_post)')
                   ->groupBy('forum.id_cate_forum')
                   ->get();
   	$arr = [ 
   		'data' => $data,
         'cate_forum'=>$cate_forum,
         'data_new'=>$data_new
   	];
   	return view('page.blog', $arr);
   }
   public function view_cate($id)
   {
      $info_cate= CateForum::find($id);
      $data = DB::table('forum')->select('user', 'name_cate','img_cate','title_post','forum.created_at','avatar','color_cate','id_post','slug_forum','like_post','comments','views','id')->join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->join('accounts','forum.auth_post','=','accounts.id')->orderBy('forum.created_at','DESC')->where('forum_cate.id_cate',$id)->paginate(10);
      $data_new= DB::table('forum')->select('user', 'name_cate','title_post','forum.created_at','avatar','color_cate','id_post','slug_forum','like_post','comments','views','id')->join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->join('accounts','forum.auth_post','=','accounts.id')->orderBy('forum.comments','DESC')->whereDate('forum.created_at', Carbon::today())->where('forum_cate.id_cate',$id)->get();
      $arr = [ 
         'data' => $data,
         'data_new'=>$data_new,
         'info_cate'=>$info_cate
      ];
      return view('page.cate_forum', $arr);
   }
   public function view_forum($id,Request $req)
   {
      $id_auth= $req->session()->get('id');
      $data = DB::table('forum')->select('forum.*','forum.created_at as time_created','forum_cate.name_cate','forum_cate.id_cate','img_cate','accounts.*')->join('accounts','forum.auth_post','=','accounts.id')->join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->where('forum.id_post',$id)->get()->first();
      if (!$data) {
         return view('errors.404');
      }
      $user_react = DB::table('react')->join('accounts','react.id_auth','=','accounts.id')->where('accounts.id',$id_auth)->where('react.id_post',$id)->get();
      $allreact = DB::table('react')->join('accounts','react.id_auth','=','accounts.id')->where('react.id_post',$id)->get();
      $list_react = '';
      $myself='';
      $count_react=count($allreact);
      $i=1;
      $check=false;
      if ($count_react) {
         foreach ($allreact as $key => $value) {
            if ($value->id==$id_auth) {
               $myself.='<span class="seft_react">Bạn, </span>';
               $i++;
               $check=true;
               break;
            }
         }
         
         $id_auth = Session::get('id') or '';
         foreach ($allreact as $key => $value) {
            if ($count_react>=4) {
               $i++;
            }
            if ($check && $value->id==$id_auth) {

            }else{
               $list_react.='<a class="link_user" status="false" username="'.$value->id.'" href="http://'.$_SERVER['SERVER_NAME'].'/'.dirname($_SERVER['PHP_SELF']).'/profile/'.$value->id.'">'.$value->displayname.',
               <span class="display_name"></span>
               <div class="user_name"> 
               </div>
               </a> ';
            }
            if ($i>3) {
              break;
            }
         }
         if ($count_react>=4) {
            $list_react=$myself.$list_react.'và <a class="other_react" href="#">'.($count_react-3).'</a> người khác đã thích!';
         }else{
            $list_react=$myself.$list_react.'đã thích!';
         }
      }else{
        $list_react="Hãy là người đầu tiên thích bài viết này!"; 
      }
      $allcmt = DB::table('accounts')->join('cmt','accounts.id','=','cmt.id_auth')->where('id_forum',$id)->where('id_parent',0)->paginate(5);
      $allcmt1 = DB::table('accounts')->join('cmt','accounts.id','=','cmt.id_auth')->where('id_forum',$id)->get();
      $forum_view = 'forum_'.$id;
      $update= Forum::find($id);
      if (!Session::has($forum_view)) {
         $update->views=$update->views+1;
         $update->save();
         Session::put($forum_view,1);
      }
      
      $arr = [ 
         'data' => $data,
         'user_react'=> $user_react,
         'allcmt'=> $allcmt,
         'allcmt1'=> $allcmt1,
         'allreact'=> $list_react
      ];
   	return view('page.view_forum',$arr);
   }
   public function react_forum(Request $req)
   {
      if ($req->session()->get('id')) {
         $id_post = $req->get('id_post');
         $id_auth =$req->session()->get('id');
         $user_react = DB::table('react')->join('accounts','react.id_auth','=','accounts.id')->where('accounts.id',$id_auth)->where('react.id_post',$id_post)->get();
         if (count($user_react)) {
            $delReact = React::where('id_auth',$id_auth)->delete();
            echo 'Thích[ ';
         }else{
            $addReact = new React;
            $addReact->id_auth=$id_auth;
            $addReact->id_post=$id_post;
            $addReact->id_cmt=0;
            $addReact->save();
            echo '<i style="color: red" class="fas fa-heart"></i> Đã Thích[Bạn ,';
         }
      }else{
         echo '<script>$(".btn_modal").click();</script>';
      }
   }
   public function create_forum()
   {
      $cate_forum= CateForum::all()->toArray();
      $arr=[
         'cate_forum'=>$cate_forum
      ];
   	return view('page.create_forum',$arr);
      
   }
   public function insert_forum(Request $req)
   {
      
   	$data = new Forum;
   	$title_post= $req->title_forum;
   	$slug_forum= $req->slug_forum;
   	$content_post= $req->content_forum;
      $cate_forum= $req->cate_forum;
   	$auth_post = $req->session()->get('id');
   	$data->title_post= $title_post;
   	$data->slug_forum= $slug_forum;
   	$data->content_post= $content_post;
   	$data->auth_post= $auth_post;
      $data->bgcolor= $req->bgcolor;
      $data->like_post= 0;
      $data->views= 0;
      $data->comments= 0;

      $data->id_cate_forum= $cate_forum;
   	try {
   		$data->save();
   		return back()->with('success_forum',"Tạo bài viết thành công");
   	} catch (Exception $e) {
   		return back()->with('error_forum',"Có lỗi sảy ra, vui lòng thử lại");
   	}
   	
   	
   }
   public function cmt_forum(Request $req)
   {
      if ($req->get('cmt') && $req->get('id_forum')) {
        if (Session::get('id')) {
         $id_auth= Session::get('id');
         $html="";
         $name=$req->get('name_auth');
         $cmt=$req->get('cmt');
         $id_forum =$req->get('id_forum');
         $img_auth=$req->get('img_auth');

         $data = new Comment;
         $data->content_cmt=$cmt;
         $data->id_blog=0;
         $data->id_forum=$id_forum;
         $data->id_parent=0;
         $data->id_auth=$id_auth;
         $data->save();

         $id_link_comment = DB::getPdo()->lastInsertId();
         $notify = new Notification;
         $notify->content_notify='đã bình luận về bài viết của bạn';
         // if($id_auth=!$req->get('id_auth_rec')) {
         $notify->id_send=$id_auth;
         $notify->id_rec=$req->get('id_auth_rec');
         $notify->id_forum=$id_forum;
         $notify->id_blog=0;
         $notify->id_cmt=0;
         $notify->type_notify=1;
         $notify->status_notify=0;
         $notify->link_notify=$req->get('link_url').'#comment_'.$id_link_comment;
         $notify->save();
         // }

         $post= Forum::find($id_forum);
         $post->comments= $post->comments+1;
         $post->save();
        }
      }
   }
   public function delete_cmt(Request $req)
   {
      $id_cmt=$req->id_cmt;
      Comment::where('id_cmt',$id_cmt)->delete();
      Comment::where('id_parent',$id_cmt)->delete();
      echo "Done!";

   }
   public function reply_cmt(Request $req)
   {
      if ($req->get('reply_cmt') && $req->get('id_forum') && $req->get('id_cmt') && $req->get('id_auth_rec')) {
        if (Session::get('id')) {
         $id_auth= Session::get('id');
         $cmt=$req->get('reply_cmt');
         $id_forum =$req->get('id_forum');
         $id_cmt=$req->get('id_cmt');
         $tag_name = $req->get('tag_name');
         

         $data = new Comment;
         $cmt_forum= $req->get('cmt_forum');
         if ($tag_name!='') {
            $data->content_cmt='
            <div class="div_reply_cmt">
            <span class="tag_name_reply"> @'.$tag_name.'</span> '.$cmt_forum.'</div>'.$cmt;
         }else{
            $data->content_cmt=$cmt;
         }
         
         $data->id_blog=0;
         $data->id_forum=$id_forum;
         $data->id_parent=$id_cmt;
         $data->id_auth=$id_auth;
         $data->save();

         // $id_link_comment=$data->id_cmt;

         $id_link_comment = DB::getPdo()->lastInsertId();

         // if ($id_auth=!$req->get('id_auth_rec')) {
         $notify = new Notification;
         // echo $id_link_comment;

         $notify->content_notify='đã trả lời bình luận của bạn trong <span <span class="name_user_notify">'.$req->get('title_web').'</span>';
         echo 'đã trả lời bình luận của bạn trong <span class="name_user_notify">'.$req->get('title_web').'</span>';
         $notify->id_send=$id_auth;
         $notify->id_rec=$req->get('id_auth_rec');
         $notify->id_forum=$id_forum;
         $notify->id_blog=0;
         $notify->id_cmt=$id_cmt;
         $notify->type_notify=0;
         $notify->status_notify=0;
         $notify->link_notify=$req->get('link_url').'#comment_'.$id_link_comment;

         $notify->save();
         
      // }
         $post= Forum::find($id_forum);
         $post->comments= $post->comments+1;
         
         $post->save();
        }
      }else{
         echo "?????";
      }
   }
}
