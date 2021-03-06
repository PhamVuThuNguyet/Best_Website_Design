<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Session;
use Carbon\Carbon;
use App\Forum;
use App\Admin;
use App\Comment;
use App\CateForum;
use App\Notification;
class ForumController extends Controller
{
    public function index()
    {
    	$data = DB::table('forum')->selectRaw('COUNT(react.id_post) as react,COUNT(cmt.id_forum) as cmt,user, name_cate,img_cate,title_post,forum.created_at,avatar,color_cate,forum.id_post,slug_forum,like_post,comments,views,accounts.id')->Join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->Join('accounts','forum.auth_post','=','accounts.id')
            ->leftJoin('react', 'forum.id_post', '=', 'react.id_post')
            ->leftJoin('cmt', 'forum.id_post', '=', 'cmt.id_forum')
            ->groupBy('forum.id_post')
            ->orderBy('forum.created_at','DESC')->paginate(6);


            $data_new = DB::table('forum')->selectRaw('COUNT(react.id_post) as react,COUNT(cmt.id_forum) as cmt,user, name_cate,img_cate,title_post,forum.created_at,avatar,color_cate,forum.id_post,slug_forum,like_post,comments,views,accounts.id')->Join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->Join('accounts','forum.auth_post','=','accounts.id')
            ->leftJoin('react', 'forum.id_post', '=', 'react.id_post')
            ->leftJoin('cmt', 'forum.id_post', '=', 'cmt.id_forum')
            ->groupBy('forum.id_post')
            ->orderBy('forum.comments','DESC')->skip(0)->take(5)->get();;
		      $cate_forum= DB::table('forum_cate')->join('forum','forum_cate.id_cate','=','forum.id_cate_forum')
		                  ->select('id_post','name_cate','id_cate','title_post',DB::raw('COUNT(forum.id_cate_forum) as sum_post,max(forum.id_post) as MAXimum'))->distinct()
		                  ->whereRaw('id_post','max(forum.id_post)')
		                   ->groupBy('forum.id_cate_forum')
		                   ->get();
		   	$arr = [ 
		   		'data' => $data,
		        'cate_forum'=>$cate_forum,
		        'data_new'=>$data_new,
		        'user'=> Session::get('id')
		   	];
         return response()->json($arr);
    }
    public function view($id,Request $req)
     {
      $id_auth= $req->session()->get('id') or null;
      $user = Admin::find($id_auth) or null;
      $data = DB::table('forum')->select('forum.*','forum.created_at as time_created','forum_cate.name_cate','forum_cate.id_cate','img_cate','accounts.*')->join('accounts','forum.auth_post','=','accounts.id')->join('forum_cate','forum.id_cate_forum','=','forum_cate.id_cate')->where('forum.id_post',$id)->get()->first();
      $user_react = DB::table('react')->join('accounts','react.id_auth','=','accounts.id')->where('accounts.id',$id_auth)->where('react.id_post',$id)->get();
      if (count($user_react)) {
        $user_react=true;
      }else{
        $user_react=false;
      }
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
      $allcmt = DB::table('accounts')->join('cmt','accounts.id','=','cmt.id_auth')->where('id_forum',$id)->where('id_parent',0)->paginate(10);
      $cmt_child = DB::table('accounts')->join('cmt','accounts.id','=','cmt.id_auth')->where('id_forum',$id)->where('id_parent','!=',0)->get();
      $forum_view = 'forum_'.$id;
      $update= Forum::find($id);
      if (!Session::has($forum_view)) {
         $update->views=$update->views+1;
         $update->save();
         Session::put($forum_view,1);
      }
      $arr = [ 
          'user' => $user,
         'datas' => $data,
         'user_react'=> $user_react,
         'cmt_child'=>$cmt_child,
         'allcmt'=> $allcmt,
         'allreact'=> $list_react,
      ];
    return response()->json($arr);
   }
   public function add(Request $req)
   {
        $id_auth= Session::get('id');
         $data = new Comment;
         $data->content_cmt=$req->content;
         $data->id_blog=0;
         $data->id_forum=$req->id_post;
         $data->id_parent=0;
         $data->id_auth=$id_auth;
         $data->save();
        $id_insert = DB::getPdo()->lastInsertId();
        // $cmt_add = Comment::find($id_insert);
        $notify = new Notification;
         $notify->content_notify='đã bình luận về bài viết của bạn';
         // if($id_auth=!$req->get('id_auth_rec')) {
         $notify->id_send=$id_auth;
         $notify->id_rec=$req->id_user;
         $notify->id_forum=$req->id_post;
         $notify->id_blog=0;
         $notify->id_cmt=0;
         $notify->type_notify=1;
         $notify->status_notify=0;
         $notify->link_notify=$req->link.'#comment_'.$id_insert;
         $notify->save();
        $cmt_add=DB::table('accounts')->join('cmt','accounts.id','=','cmt.id_auth')->where('id_forum',$data->id_forum)->where('id_parent',0)->where('id_cmt',$id_insert)->first();
         return response()->json($cmt_add);
   }
   public function reply(Request $req)
   {
          $id_auth= Session::get('id');
         $data = new Comment;
         $data->content_cmt=$req->content;
         $data->id_blog=0;
         $data->id_forum=$req->id_post;
         $data->id_parent=$req->id_cmt;
         $data->id_auth=$id_auth;
         $data->save();
        $id_insert = DB::getPdo()->lastInsertId();
         $notify = new Notification;
         // echo $id_link_comment;

         $notify->content_notify='đã trả lời bình luận của bạn trong <span class="name_user_notify">'.$req->title.'</span>';
         $notify->id_send=$id_auth;
         $notify->id_rec=$req->id_user;
         $notify->id_forum=$req->id_post;
         $notify->id_blog=0;
         $notify->id_cmt=$req->id_cmt;
         $notify->type_notify=0;
         $notify->status_notify=0;
         $notify->link_notify=$req->link.'#comment_'.$id_insert;

         $notify->save();
        // $cmt_add = Comment::find($id_insert);
        $cmt_add=DB::table('accounts')->join('cmt','accounts.id','=','cmt.id_auth')->where('id_cmt',$id_insert)->first();
         return response()->json($cmt_add);
   }
   public function cate()
   {
    $id_auth= Session::get('id');
    $user = Admin::find($id_auth) or null;
     $cate_forum= CateForum::all();
     $arr=[
      'user' =>$user,
      'cate_forum' =>$cate_forum
     ];
      return response()->json($arr);
   }
   public function delete(Request $r)
   {
     $res=Comment::where('id_cmt',$r->id)->delete();
     $res=Comment::where('id_parent',$r->id)->delete();
   }
}
