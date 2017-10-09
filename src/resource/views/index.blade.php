@extends('layouts.app')

@section('content')
	<link rel="stylesheet" type="text/css" href="/plugins/datatables/jquery.dataTables.min.css"/>
	<link href="/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
	<div class="row">
		<div class="col-md-12">
			<div class="white-box">
				<h3 class="box-title m-b-0">
					{!! $titulo !!}
					 @if(in_array('create', $permisos))
						<button class="btn btn-success waves-effect pull-right" onclick="agregar()">Agregar</button>
					@endif
				</h3>
				{{-- <div class="table-responsive"> --}}
					<table id="table1" class="table table-striped table-hover table-fw-widget">
						<thead>
							<tr>
							@foreach($columnas as $c)
								<td>{{$c}}</td>
							@endforeach
								<td width="15%"></td>
							</tr>
						</thead>
						<tbody>
							@foreach($data as $d)
								<tr>
								<?php $id = null; ?>
								@foreach($d as $k => $v)
									@if($k !== '__id__')
										<td>{{$v}}</td>
									@else
										<?php $id = $v; ?>
									@endif
								@endforeach
									<td class="text-center">
										@if(!empty($botones) && is_array($botones))
											@foreach($botones as $b)
												<?php $b['url'] = str_replace('{id}',$id,$b['url']); ?>
												<a href="{{$b['url']}}" class="btn btn-xs btn-{{$b['class']}}"><span class="zmdi zmdi-{{$b['icon']}}"></span></a>
											@endforeach
										@elseif(!empty($botones))
											<a href="javascript:void(0)" data-modal="full-success" class="btn btn-xs btn-default md-trigger" id="options" onclick="opciones({{ $botones }}, {{ $id }})"><span class="zmdi zmdi-settings"></span></a>
										@endif
										

										@if(in_array('edit', $permisos))
											<a href="javascript:void(0)" onclick="edit('{{Crypt::encrypt($id)}}')" class="btn btn-xs btn-primary"><span class="zmdi zmdi-edit"></span></a>
										@endif
										@if(in_array('destroy', $permisos))
											<a href="javascript:void(0)" onclick="destroy('{{Crypt::encrypt($id)}}')" class="btn btn-xs btn-danger"><span class="zmdi zmdi-delete"></span></a>
										@endif
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				{{-- </div> --}}
			</div>		
		</div>
	</div>
@endsection
@section('scripts')
	<script src="/plugins/datatables/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
    <script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
    <script src="/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="/plugins/sweetalert/jquery.sweet-alert.custom.js"></script>
	<script>
	// $.fn.niftyModal('setDefaults',{
 //      	overlaySelector: '.modal-overlay',
 //      	closeSelector: '.modal-close',
 //      	classAddAfterOpen: 'modal-show',
 //     });
	$('#table1').DataTable({
		"bLengthChange": false,
		"sortable": false,
		"sDom": '<"row"<"col-sm-8 pull-left"f><"col-sm-4" <"btn-toolbar pull-right">>>t<"pull-left"i><"pull-right"p>'
	});
	
	function agregar(){
		location.replace((String(window.location).includes('?') ? String(window.location).split('?')[0] : window.location)+'/create'+(String(window.location).includes('?') ? '?'+String(window.location).split('?')[1] : ''));
	}
	function edit(id){
		var url = String(window.location);
		var id2 = null;
		if(url.includes('?')){
			var result = url.split('?');
			url = result[0];
			id2 = '?'+result[1];
		}
		window.location.replace(url+'/'+id+'/edit'+(id2 != null ? id2 : ''));
	}
	function destroy(id){
		var url = String(window.location);
		var id2 = null;
		if(url.includes('?')){
			var result = url.split('?');
			url = result[0];
			id2 = '?'+result[1];
		}else{
			id2 = '';
		}
		$.post(url+'/'+id+id2,{_token:'{{csrf_token()}}', _method:'DELETE'}, function(data){
			if(data == 1) {
				window.location.reload();
			}else{
				alert(data);
			}
		});
	}
	function opciones(botones, id) {
		swal('hello');
		var buttons = '';
		$.each(botones, function(index, val) {
			var url = val.url.replace('{id}', id);
			buttons += '<div class="col-md-6"><a href="'+url+'" class="btn btn-'+val.class+' btn-block"><span class="zmdi zmdi-'+val.icon+'"></span> '+val.nombre+'</a></div>'
		});
		$('#modalContent').empty();
		$('#modalContent').append(buttons);
	}
	</script>
@endsection