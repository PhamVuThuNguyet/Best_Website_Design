@extends('welcome')
@section('title',"Khóa học của tôi")
@section('content')
<div class="container">
	<?php $i=0?>
	<br>
	<br>
	<br>
	<h3>Khóa học đã mua</h3>
	<div class="row">
		@foreach($rs as $key=>$value)
		<?php
    	$i++;
    	$desc=$value->desc_course;
	    if (strlen($desc)>60) {
	    	$desc= substr($desc, 0,60) . '...</p>';
	    }
		?>
		<article class="course-item col-xs-6 col-md-4 col-lg-3">
			<div class="border_course">
				<div class="wrap-course-item">
					<div class="course-thumb">
						<a href="{{URL::to('/course/post/'.$value->id_course)}}" title="{{$value->title_course}}">
							<img style="width: 100%;height: 170px;" src="{{asset('public'.$value->img_course)}}" alt="{{$value->title_course}}">
						</a>
					</div>
					<div class="view-content">
						<h3 class="course-title"><a href="{{URL::to('/course/post/'.$value->id_course)}}">{{$value->title_course}}</a></h3>
						<div class="wrap_desc">
							{!!$desc!!}
						</div>
						<br>
						<div class="user-rating">
							<a class="btn btn-success btn-sm" href="{{URL::to('/course/post/'.$value->id_course)}}">Học</a>
							<span class="star-rating"><span style="width:90.0%"></span></span>
						</div>
					</div>
				</div>
			</div>
		</article>
		@endforeach
	</div>
</div>
@endsection