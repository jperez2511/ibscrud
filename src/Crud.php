<?php

namespace App\Http\Controllers\Crud;

use KituKizuri;
use App\Http\Controllers\Controller;
use App\Models\Municipio;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Mockery\Exception;

class CrudController extends Controller {
	private $id       = '__id__';
	private $model    = null;
	private $titulo   = null;
	private $editId   = null;
	private $parentid = null;
	private $embed    = [];
	private $campos   = [];
	private $joins    = [];
	private $rt       = [];
	private $botones  = [];
	private $wheres   = [];
	private $parents  = [];
	public function setParents($nombre, $value){
		array_push($this->parents, ['nombre' => $nombre, 'value'=>$value]);
	}

	public function setPermisos($id){
		$kitukizuri = new KituKizuri;
		return $kitukizuri->getPermisos($id);
	}

	//metodos para el construc
	public function setModel($model){
		$this->model = $model;
	}

	public  function setTitulo($titulo){
		$this->titulo = $titulo;
	}

	public function setCampo($params){
		$allowed = [
			'campo','nombre','edit','show','tipo','class','default','reglas','reglasmensaje', 'decimales',
			'collect','enumarray','filepath', 'filewidth','fileheight','target', 'value'
		];

		$tipos = [
			'string','numeric', 'date','datetime', 'bool','combobox', 'password','enum',
			'file','image', 'textarea','url','summernote', 'hidden'
		];

		$this->allowed($params, $allowed);

		$params['nombre']    = (!array_key_exists('nombre', $params) ? str_replace('_', ' ', ucfirst($params['campo'])) : $params['nombre']);
		$params['edit']      = (!array_key_exists('edit', $params) ? true : $params['edit']);
		$params['show']      = (!array_key_exists('show', $params) ? true : $params['show']);
		$params['tipo']      = (!array_key_exists('tipo', $params) ? 'string' : $params['tipo']);
		$params['decimales'] = (!array_key_exists('decimales', $params) ? 0 : $params['decimales']);
		$params['collect']   = (!array_key_exists('collect', $params) ? '' : $params['collect']);
		$params['filepath']  = (!array_key_exists('filepath', $params) ? '' : $params['filepath']);
		$params['value']     = (!array_key_exists('value', $params) ? '' : $params['value']);

		if(!array_key_exists('campo', $params)) dd('"campo" es un parametro requerido');

		if (!in_array($params['tipo'], $tipos)) dd('El tipo configurado (' . $params['tipo'] . ') no existe! solamente se permiten: ' . implode(', ', $tipos));

		if($params['tipo'] == 'combobox' && ($params['collect'] == '')) dd('Para el tipo combobox el collection es requerido');
		if($params['tipo'] == 'combobox') $params['show'] = false;
		if($params['tipo'] == 'file' && $params['filepath'] == '') dd('Para el tipo file falta parametro filepath');
		//if($params['tipo'] == 'image' && $params['filepath'] == '') dd('Para el tipo image para parametro filepath');
		if($params['tipo'] == 'enum' && count($params['enumarray'] ) == 0) dd('Para el tipo enum el enumarray es requerido');
		if($params['tipo'] == 'hidden' && $params['value'] == '') dd('Para el tipo hidden requiere agregar value');

		if (!strpos($params['campo'], ')')) {
			$arr = explode('.', $params['campo']);
			if (count($arr)>=2) $params['campoReal'] = $arr[count($arr) - 1];
		}

		//validando si las llaves existen
		if (!array_key_exists('show', $params))
			$params['show'] = true;

		if (array_key_exists('tipo', $params)) {
			if (!in_array($params['tipo'], $tipos))
				dd('tipo: ' . $params['tipo'] . ' no es un tipo valido.');
			else if (in_array($params['tipo'], $tipos) && $params['tipo'] == 'combobox') {
				$warning = !array_key_exists('collect',$params) ? dd('comobox requiere del campo collect') : null;
				$collect = $params['collect']->toArray();
				$options = [];
				foreach ($collect as $k) {
					$option =[];
					foreach	($k as $v){
						array_push($option, $v);
					}
					array_push($options, $option);
				}
				$params['options'] = $options;
			}
		}else{
			$params['tipo'] = 'string';
		}
		array_push($this->campos, $params);
	}

	public  function setJoin($tabla, $v1, $operador, $v2){
		array_push($this->joins, ['tabla'=>$tabla,'value1'=>$v1,'operador'=>$operador, 'value2'=>$v2]);
	}

	public  function  setWhere($column, $op='=', $column2){
		array_push($this->wheres, [$column, $op, $column2]);
	}

	public  function setBoton($params){
		$allowed = ['nombre', 'url', 'class', 'icon'];
		$this->allowed($params, $allowed);
		array_push($this->botones, $params);
	}

	public function embedView($view, $model, $idRelation, $campos){
		array_push($this->embed,[view($view, ['id' => Crypt::encrypt($this->editId)]), $model, $idRelation, $campos]);
	}

	public  function setParentId($text){
		$this->parentid = $text;
	}

	public function getMunicipios($departamentoid){
		$municipios = Municipio::select('municipioid', 'nombre')->where('departamentoid',$departamentoid)->get();
		return $municipios;
	}

	// Metodos y funciones utilitarios
	private function getData(){
		$campos = $this->getSelectShow();
		$data = $this->model->select($this->getSelect($campos));
		$data->addSelect($this->model->getKeyName().' as '.$this->id);
		foreach($this->joins as $join){
			$data->join($join['tabla'],$join['value1'], $join['operador'], $join['value2']);
		}
		foreach ($this->wheres as $where){
			$data->where($where[0], $where[1],$where[2]);
		}
		return $data->get();
	}

	private function getSelectShow(){
		$campos = $this->campos;
		for ($i = 0; $i<count($campos); $i++){
			if ($campos[$i]['show'] == false){
				unset($campos[$i]);
			}
		}
		return array_values($campos);
}

	private function getSelect($campos){
		$s = [];
		for ($i = 0; $i <count($campos); $i++){
			if ($campos[$i]['show'] == true)
				array_push($s,$campos[$i]);
		}
		return array_map(function($c){ return DB::raw($c['campo']); }, $s);
	}

	private function getColumnas($campos){
		$s = [];
		for ($i = 0; $i <count($campos); $i++){
			if ($campos[$i]['show'] == true)
				array_push($s,$campos[$i]);
		}
		return array_map(function ($c){return $c['nombre'];}, $s);
	}

	private function makeArrayData($data){
		for ($i = 0; $i< count($this->campos); $i++){
			$this->campos[$i]['value'] = $data->{(array_key_exists('campoReal', $this->campos[$i]) ? $this->campos[$i]['campoReal'] : $this->campos[$i]['campo'] )};
		}
	}

	private function getUrl($url){
		$url = explode('/', $url);
		if ($url[count($url)-1] == 'create'){
			unset($url[count($url)-1]);
		}else{
			unset($url[count($url)-1]);
			unset($url[count($url)-1]);
		}
		return implode('/', $url);
	}

	//funciones propias del controller

	public function index(){
		if	($this->model == null)
			dd('El Modelo es requerido');

		$botones = $this->botones;
		if (count($botones) > 1 ) {
			$botones = json_encode($botones);
		}	

		if (!empty(Kitukizuri)) {
			$permisos = $this->setPermisos(Auth::id());	
		} else {
			$permisos = ['create'=>true, 'edit'=>true, 'destroy'=>true];
		}
		return view('index', [
			'titulo'   => $this->titulo,
			'columnas' => $this->getColumnas($this->getSelectShow()),
			'data'     => $this->getData()->toArray(),
			'botones'  => $botones,
			'permisos' => $permisos
		]);
	}

	public  function create(Request $request){
		return $this->edit(Crypt::encrypt(0), $request);
	}

	public  function edit($id, Request $request){
		try{
			$id = Crypt::decrypt($id);
			$parentid= $request->get('parent');
		}catch(Exception $e){
			dd($e);
		}
		$this->editId = $id;
		if ($id != 0){
			$data = $this->model->find($id);
			$titulo = 'Editar '.$this->titulo;
			$this->makeArrayData($data);
		}else{
			$data = null;
			$titulo = 'Agregar '.$this->titulo;
		}

		$url = $this->getUrl($request->url());
		return view('edit', [
			'titulo'   => $titulo,
			'campos'   => $this->campos,
			'action'   => $url,
			'id'       => Crypt::encrypt($id),
			'rt'       => $this->rt,
			'embed'    => $this->embed,
			'parent'   => $this->parentid,
			'parentid' => $parentid,
			'parents'  => $this->parents
		]);
	}

	public function	store(Request $request){
		$param = null;

		try{
			$id = Crypt::decrypt($request->input('id'));
		}catch(Exception $e){
			dd($e);
		}

		if($id != 0)
			$this->model = $this->model->find($id);
		foreach	($request->all() as $k => $v){
			if ($k != '_token' && $k != 'id'){
				foreach ($this->campos as $c){

					if ($c['campo'] == $k && $c['tipo'] == 'date'){
						$v = $this->toDateMysql($v);
					}
					if ($c['campo'] == $k && $c['tipo'] == 'numeric'){
						$v = str_replace(',', '', $v);
					}
					if ($c['campo'] == $k && $c['tipo'] == 'password'){
						$v = bcrypt($v);
					}
					if ($c['campo'] == $k && $c['tipo'] == 'image') {						
						if ($request->hasFile($c['campo'])) {
							$file = $request->file($c['campo']);
							$file = 'data:image/jpeg;base64,'.base64_encode(file_get_contents($file));
							
							//$filename = date('Ymdhis') . mt_rand(1, 1000) . '.' . strtolower($file->getClientOriginalExtension());
							//$path     = public_path() . $campo['filepath'];

							// if (!file_exists($path)) {
		    	 				// mkdir($path, 0777, true);
							// }

							// $file->move($path, $filename);
							$v = $file;
						}
					}
				}
				$this->model->{$k} = $v;
			}
			if ($this->parentid == $k){
				$param = '?parent='.$v;
			}
		}

		foreach	($this->campos as $c){
			if ($c['tipo'] == 'hidden'){
				$this->model->{$c['campo']} = $c['value'] == 'userid' ? Auth::id() : $c['value'];
			}
		}

		$this->model->save();
		return redirect($request->url().$param);
	}

	public function destroy ($id, Request $request){
		try{
			$id = Crypt::decrypt($id);
		}catch(Exception $e){
			return $e;
		}
		try{
			$this->model->destroy($id);
		}catch (QueryException $e){
			return $e;
		}

		return 1;
	}

	public function toDateMysql($date){
		$date = str_replace('/','-', $date);
		$date = date('Y-m-d H:s:i', strtotime($date));
		return $date;
	}

	public function mesesEntreFechas($aFechaInicio, $aFechaFin) {
		$fi = new Carbon($aFechaInicio);
		$ff = new Carbon($aFechaFin);

		return $ff->diffInMonths($fi);
	}

	private function allowed($params, $allowed){
		foreach ($params as $key => $val) { //Validamos que todas las variables del array son permitidas.
			if (!in_array($key, $allowed)) {
				dd($key . ' no es un parametro permitido ' . implode(', ', $allowed));
			}
		}
	}

}
