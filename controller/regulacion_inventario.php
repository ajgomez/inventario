<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of regulacion_inventario
 *
 * @author ajgomez
 */
require_model('stock.php');
require_model('articulo.php');
require_model('almacen.php');
require_model('regularizacion_stock.php');

class regulacion_inventario extends fs_controller {
  public $inventario;
  public $almacenes;
  
  private $articulo;
  
  public function __construct() {
    parent::__construct(__CLASS__, 'Almacen', 'Inventario', FALSE, TRUE);
  }
  
  protected function private_core() {
    
    $this->almacenes = new almacen();
    
    if(isset($_GET['ref'])){
      $this->busca_articulo();
    } elseif (isset ($_GET['entrada'])){
      $this->nueva_entrada();
    } elseif(isset ($_GET['salida'])){
      $this->nueva_salida();
    }
  }
  public function busca_articulo() {
      $art = new articulo();
      $this->articulo = $art->get($_GET['ref']);
      if ($this->articulo) {
	$stock0 = new stock();
	$stock = $stock0->get_by_referencia($_GET['ref']);
	$stock_actual = 0;
	if($stock){
	  $stock_actual = $stock->cantidad;
	}
	$this->template = FALSE;
	header('Content-Type: application/json');
	echo json_encode(["concepto"=>$this->articulo->descripcion,"stock"=>$stock_actual ]);
      }
    
  }
  
  public function nueva_entrada() {
    if ($_POST['referencia'] == "" || $_POST['cantidad'] == 0) {
      $this->new_error_msg("Datos incompletos, no se ha hecho ningún cambio");
    } else {
      $art0 = new stock();
      $art = $art0->get_by_referencia($_POST['referencia']);
      if($art){
	$cantidadini = $art->cantidad;
	$art->cantidad += $_POST['cantidad'];
	$art->codalmacen = $_POST['almacen'];
	
	if($art->save()){
	  $reg = new regularizacion_stock();
	  $reg->cantidadini = $cantidadini;
	  $reg->cantidadfin = $art->cantidad;
	  $reg->codalmacendest = $_POST['almacen'];
	  $reg->fecha = $this->today();
	  $reg->hora = $this->hour();
	  $reg->idstock = $art->idstock;
	  $reg->motivo = "Regularizacion de inventario";
	  $reg->nick = $this->user->nick;
	  if(!$reg->save()){
	    $this->new_error_msg("Se ha producido un error al grabar la regularizacion");
	  }
	  $this->new_message("El stock se ha actualizado, la CANTIDAD ACTUAL de la Referencia " . $_POST['referencia'] . " es " .$art->cantidad);
	} else 
	  $this->new_error_msg ("No se ha podido actualizar el stock, revisa los datos");
      }  else {
	$art = new stock();
	$art->referencia = $_POST['referencia'];
	$art->cantidad = $_POST['cantidad'];
	$art->codalmacen = $_POST['almacen'];
	if($art->save()){
	  $reg = new regularizacion_stock();
	  $reg->cantidadini = $cantidadini;
	  $reg->cantidadfin = $art->cantidad;
	  $reg->codalmacendest = $_POST['almacen'];
	  $reg->fecha = $this->today();
	  $reg->hora = $this->hour();
	  $reg->idstock = $art->idstock;
	  $reg->motivo = "Regularizacion de inventario";
	  $reg->nick = $this->user->nick;
	  if(!$reg->save()){
	    $this->new_error_msg("Se ha producido un error al grabar la regularizacion");
	  }
	  $this->new_message("El stock se ha actualizado, la CANTIDAD ACTUAL de la Referencia " . $_POST['referencia'] . " es " .$art->cantidad);
	}else 
	   $this->new_error_msg ("No se ha podido actualizar el stock, revisa los datos");
      }
    }
  }
  
  public function nueva_salida() {
    if ($_POST['referencia'] == "" || $_POST['cantidad'] == 0) {
      $this->new_error_msg("Datos incompletos, no se ha hecho ningún cambio");
    } else {
      $art0 = new stock();
      $art = $art0->get_by_referencia($_POST['referencia']);
      if($art){
	$art->cantidad -= $_POST['cantidad'];
	$art->codalmacen = $_POST['almacen'];
	if($art->save()){
	  $reg = new regularizacion_stock();
	  $reg->cantidadini = $cantidadini;
	  $reg->cantidadfin = $art->cantidad;
	  $reg->codalmacendest = $_POST['almacen'];
	  $reg->fecha = $this->today();
	  $reg->hora = $this->hour();
	  $reg->idstock = $art->idstock;
	  $reg->motivo = "Regularizacion de inventario";
	  $reg->nick = $this->user->nick;
	  if(!$reg->save()){
	    $this->new_error_msg("Se ha producido un error al grabar la regularizacion");
	  }
	  $this->new_message("El stock se ha actualizado, la CANTIDAD ACTUAL de la Referencia " . $_POST['referencia'] . " es " .$art->cantidad);
	} else 
	  $this->new_error_msg ("No se ha podido actualizar el stock, revisa los datos");
      }  else {
	$art = new stock();
	$art->referencia = $_POST['referencia'];
	$art->codalmacen = $_POST['almacen'];
	$art->cantidad = $_POST['cantidad'];
	if($art->save()){
	  $reg = new regularizacion_stock();
	  $reg->cantidadini = $cantidadini;
	  $reg->cantidadfin = $art->cantidad;
	  $reg->codalmacendest = $_POST['almacen'];
	  $reg->fecha = $this->today();
	  $reg->hora = $this->hour();
	  $reg->idstock = $art->idstock;
	  $reg->motivo = "Regularizacion de inventario";
	  $reg->nick = $this->user->nick;
	  if(!$reg->save()){
	    $this->new_error_msg("Se ha producido un error al grabar la regularizacion");
	  }
	  $this->new_message("El stock se ha actualizado, la CANTIDAD ACTUAL de la Referencia " . $_POST['referencia'] . " es " .$art->cantidad);
	}else 
	   $this->new_error_msg ("No se ha podido actualizar el stock, revisa los datos");
      }
    }  
  }
}
